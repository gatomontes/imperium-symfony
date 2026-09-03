<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Atomic authority/capability consumption and effect-start; no credential or provider edge. */
final class NativeEffectAtomicAdmissionService
{
    public const string ADMISSIONS = 'var/imperium/runtime/canonical-native-effect-admissions';
    public const string DISPOSITIONS = 'var/imperium/runtime/canonical-native-effect-tuple-dispositions';
    private readonly AtomicTransition $atomic;
    private readonly ImmutableRecordStore $records;

    /** @var array<string, NativeEffectAdmissionOutcome> */
    private array $outcomes = [];

    public function __construct(
        private readonly NativeState $state,
        private readonly NativeEffectCredentialCapabilityIssuer $capabilities,
        private readonly NativeEffectContinuationCapabilityIssuer $continuations = new NativeEffectContinuationCapabilityIssuer(),
    ) {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
    }

    public function admit(array $authority, NativeEffectCredentialCapability $capability, int $at): NativeEffectAdmissionOutcome
    {
        $authorityId = $authority['authority_id'] ?? null;
        if (!is_string($authorityId)) {
            throw new \RuntimeException('CNE301_EFFECT_AUTHORITY_ID_INVALID');
        }
        $tupleId = NativeEffectSemanticIdentity::tupleId($authority);
        $authorityConsumptionId = NativeEffectSemanticIdentity::authorityConsumptionId($authority);
        $admissionId = NativeEffectSemanticIdentity::admissionId($tupleId);

        [$record, $newlyPublished] = $this->state->locked(fn (): array => $this->atomic->run(
            'canonical-native-effect-authority:'.hash('sha256', $authorityId),
            fn (): array => $this->atomic->run('canonical-native-effect-tuple:'.$tupleId, function () use ($authority, $authorityId, $authorityConsumptionId, $capability, $at, $tupleId, $admissionId): array {
                foreach (glob($this->state->root.'/'.self::ADMISSIONS.'/*.json') ?: [] as $path) {
                    $existing = $this->records->read(self::ADMISSIONS, basename($path, '.json'));
                    if (($existing['effect_authority']['id'] ?? null) !== $authorityId) {
                        continue;
                    }
                    if (($existing['effect_authority']['digest'] ?? null) !== ($authority['record_digest'] ?? null)
                        || ($existing['semantic_effect_tuple_id'] ?? null) !== $tupleId
                        || ($existing['authority_consumption_id'] ?? null) !== $authorityConsumptionId
                        || (isset($this->outcomes[$admissionId])
                            && ($existing['credential_scope']['capability']['capability_id'] ?? null) !== $capability->capabilityId)) {
                        throw new \RuntimeException('CNE302_EFFECT_AUTHORITY_ALREADY_USED');
                    }
                    return [$existing, false];
                }

                $view = (new NativeEffectAdmissionValidator($this->state))->inspect($authority, $at);
                try {
                    $existing = $this->records->read(self::ADMISSIONS, $admissionId);
                    $this->recordLosingDisposition($existing, $authority, $authorityConsumptionId, $tupleId, $at);
                    throw new \RuntimeException('CNE306_EFFECT_TUPLE_ALREADY_WON');
                } catch (\RuntimeException $error) {
                    if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) {
                        throw $error;
                    }
                }

                $this->consumeCapability($authority, $capability, $at);
                $effectAuthority = NativeState::ref($authority, 'authority_id');
                $providerRequest = [
                    'operation' => $authority['operation'],
                    'destination' => $authority['destination'],
                    'payload_digest' => $authority['payload_digest'],
                    'request_fingerprint' => $authority['request_fingerprint'],
                    'provider_idempotency_key_digest' => $authority['provider_idempotency_key_digest'],
                ];
                $provider = $authority['provider'];
                $receiptInput = NativeState::seal([
                    'schema' => NativeEffectReceiptInputContract::SCHEMA,
                    'semantic_effect_tuple_id' => $tupleId,
                    'authority_consumption_id' => $authorityConsumptionId,
                    'effect_authority' => $effectAuthority,
                    'native_root' => $view['native_root'],
                    'native_transition' => $authority['native_transition'],
                    'native_receipt' => $authority['native_receipt'],
                    'successor' => $authority['successor'],
                    'v3_admission' => $authority['v3_admission'],
                    'executor_principal' => $authority['executor_principal'],
                    'execution_boundary' => $authority['execution_boundary'],
                    'provider_binding' => $authority['provider_binding'],
                    'provider_request' => $providerRequest,
                    'provider' => $provider,
                    'credential_scope' => ['credential_family' => $authority['credential_scope']['credential_family']],
                    'expected_return_contract' => $authority['expected_return_contract'],
                    'admitted_at' => $at,
                    'expires_at' => $authority['expires_at'],
                    'sealed' => true,
                ]);
                $record = [
                    'schema' => NativeEffectAdmissionContract::SCHEMA,
                    'admission_id' => $admissionId,
                    'semantic_effect_tuple_id' => $tupleId,
                    'authority_consumption_id' => $authorityConsumptionId,
                    'effect_replay_identity' => $tupleId,
                    'native_root' => $view['native_root'],
                    'native_receipt' => $authority['native_receipt'],
                    'effect_authority' => $effectAuthority,
                    'authority_consumption' => [
                        'consumed' => true,
                        'single_use' => true,
                        'continuing_authority' => false,
                    ],
                    'effect_start' => [
                        'checkpoint' => NativeEffectAdmissionContract::CHECKPOINT,
                        'outcome' => 'UNKNOWN_REPLAY_PROHIBITED',
                        'automatic_replay_permitted' => false,
                        'credential_resolved' => false,
                        'capability_consumed' => true,
                        'callback_started' => false,
                        'external_io_may_have_started' => false,
                        'provider_invoked' => false,
                    ],
                    'provider_request' => $providerRequest,
                    'provider' => $provider,
                    'credential_scope' => [
                        'provider_id' => $capability->providerId,
                        'credential_family' => $capability->credentialFamily,
                        'stationary_same_process' => true,
                        'capability' => $capability->metadata(),
                    ],
                    'receipt_input' => $receiptInput,
                    'admitted_at' => $at,
                    'expires_at' => $authority['expires_at'],
                    'sealed' => true,
                ];
                return [$this->records->put(self::ADMISSIONS, $admissionId, $record), true];
            }),
        ));

        if (!$newlyPublished) {
            $cached = $this->outcomes[$admissionId] ?? null;
            if (null !== $cached && $cached->admission['authority_consumption_id'] === $authorityConsumptionId) {
                return $cached;
            }
            return new NativeEffectAdmissionOutcome($record, null, false);
        }

        $continuation = $this->continuations->issueForNewWinner($record, $authority['execution_boundary']['id']);
        return $this->outcomes[$admissionId] = new NativeEffectAdmissionOutcome($record, $continuation, true);
    }

    private function consumeCapability(array $authority, NativeEffectCredentialCapability $capability, int $at): void
    {
        if (!$this->capabilities->recognizes($capability)
            || $capability->effectAuthorityId !== $authority['authority_id']
            || $capability->providerId !== $authority['provider']['provider_id']
            || $capability->credentialFamily !== $authority['credential_scope']['credential_family']
            || $capability->processBoundaryId !== $authority['execution_boundary']['id']
            || $at >= $capability->expiresAt
            || !$this->capabilities->consume($capability)) {
            throw new \RuntimeException('CNE303_CAPABILITY_INVALID');
        }
    }

    private function recordLosingDisposition(array $winner, array $candidate, string $authorityConsumptionId, string $tupleId, int $at): void
    {
        $id = 'canonical-native-effect-tuple-disposition-'.$tupleId.'-'.$authorityConsumptionId;
        $this->records->put(self::DISPOSITIONS, $id, [
            'schema' => NativeEffectTupleDispositionContract::SCHEMA,
            'disposition_id' => $id,
            'semantic_effect_tuple_id' => $tupleId,
            'candidate_authority' => NativeState::ref($candidate, 'authority_id'),
            'winning_authority' => $winner['effect_authority'],
            'outcome' => NativeEffectTupleDispositionContract::LOSER,
            'candidate_authority_consumed' => false,
            'callback_permitted' => false,
            'continuation_capability_minted' => false,
            'automatic_retry_permitted' => false,
            'decided_at' => $at,
            'sealed' => true,
        ]);
    }
}
