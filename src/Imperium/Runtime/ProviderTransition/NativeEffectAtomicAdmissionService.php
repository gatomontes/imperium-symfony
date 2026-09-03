<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Atomic authority/capability consumption and effect-start; no credential or provider edge. */
final readonly class NativeEffectAtomicAdmissionService
{
    public const string ADMISSIONS = 'var/imperium/runtime/canonical-native-effect-admissions';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;

    public function __construct(private NativeState $state, private NativeEffectCredentialCapabilityIssuer $capabilities)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
    }

    public function admit(array $authority, NativeEffectCredentialCapability $capability, int $at): array
    {
        $authorityId = $authority['authority_id'] ?? null;
        if (!is_string($authorityId)) {
            throw new \RuntimeException('CNE301_EFFECT_AUTHORITY_ID_INVALID');
        }
        $replay = NativeEffectAdmissionValidator::replayIdentity($authority);
        $admissionId = 'canonical-native-effect-admission-'.substr($replay, 0, 20);

        return (new NativeBindingReader($this->state))->legacy(fn (): array => $this->atomic->run(
            'canonical-native-effect-authority:'.hash('sha256', $authorityId),
            fn (): array => $this->atomic->run('canonical-native-effect:'.$replay, function () use ($authority, $authorityId, $capability, $at, $replay, $admissionId): array {
                foreach (glob($this->state->root.'/'.self::ADMISSIONS.'/*.json') ?: [] as $path) {
                    $existing = $this->records->read(self::ADMISSIONS, basename($path, '.json'));
                    if (($existing['effect_authority']['id'] ?? null) !== $authorityId) {
                        continue;
                    }
                    if (($existing['effect_authority']['digest'] ?? null) !== ($authority['record_digest'] ?? null)
                        || ($existing['effect_replay_identity'] ?? null) !== $replay
                        || ($existing['credential_scope']['capability']['capability_id'] ?? null) !== $capability->capabilityId) {
                        throw new \RuntimeException('CNE302_EFFECT_AUTHORITY_ALREADY_USED');
                    }
                    return $existing;
                }

                $view = (new NativeEffectAdmissionValidator($this->state))->inspect($authority, $at);
                $this->assertCapability($authority, $capability, $at);
                $record = [
                    'schema' => NativeEffectAdmissionContract::SCHEMA,
                    'admission_id' => $admissionId,
                    'effect_replay_identity' => $replay,
                    'native_root' => $view['native_root'],
                    'native_receipt' => $authority['native_receipt'],
                    'effect_authority' => NativeState::ref($authority, 'authority_id'),
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
                    'provider_request' => [
                        'operation' => $authority['operation'],
                        'destination' => $authority['destination'],
                        'payload_digest' => $authority['payload_digest'],
                        'request_fingerprint' => $authority['request_fingerprint'],
                        'provider_idempotency_key_digest' => $authority['provider_idempotency_key_digest'],
                    ],
                    'credential_scope' => [
                        'provider_id' => $capability->providerId,
                        'credential_family' => $capability->credentialFamily,
                        'stationary_same_process' => true,
                        'capability' => $capability->metadata(),
                    ],
                    'admitted_at' => $at,
                    'expires_at' => $authority['expires_at'],
                    'sealed' => true,
                ];
                return $this->records->put(self::ADMISSIONS, $admissionId, $record);
            }),
        ));
    }

    private function assertCapability(array $authority, NativeEffectCredentialCapability $capability, int $at): void
    {
        if (!$this->capabilities->recognizes($capability)
            || $capability->effectAuthorityId !== $authority['authority_id']
            || $capability->providerId !== $authority['provider']['provider_id']
            || $capability->credentialFamily !== $authority['credential_scope']['credential_family']
            || $capability->processBoundaryId !== $authority['execution_boundary']['id']
            || $at >= $capability->expiresAt) {
            throw new \RuntimeException('CNE303_CAPABILITY_INVALID');
        }
    }
}
