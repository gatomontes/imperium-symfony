<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\AgentMailIdempotencyHeaderAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalContract;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalService;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use App\Imperium\Runtime\LaCortine\DeterministicProviderInvocationAdmissionContract;
use App\Imperium\Runtime\LaCortine\DeterministicProviderInvocationCheckpointContract;
use App\Imperium\Runtime\LaCortine\DeterministicProviderResponseEnvelopeContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicJournalBoundCredentialBroker
{
    public const string ADMISSIONS = 'var/imperium/la-cortine/deterministic-provider-invocation-admissions';
    public const string RESPONSE_ENVELOPES = 'var/imperium/la-cortine/deterministic-provider-response-envelopes';
    public const string RESPONSE_CONTENT = 'var/imperium/la-cortine/deterministic-provider-response-content';
    public const string CREDENTIAL_ATTEMPTS = 'var/imperium/la-cortine/deterministic-credential-consumption-attempts';
    public const string CALLBACK_STARTS = 'var/imperium/la-cortine/deterministic-provider-callback-starts';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        private CredentialBroker $credentials,
        private AgentMailIdempotencyHeaderAdapter $adapter,
    ) {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function invoke(string $journalId, CredentialCapability $capability, string $payload, \DateTimeImmutable $at, callable $providerCallback): mixed
    {
        if (!preg_match('/^deterministic-effect-start-journal-[a-f0-9]{20}$/', $journalId)) {
            throw new \InvalidArgumentException('IGB610_EFFECT_START_JOURNAL_ID_INVALID');
        }
        $journal = $this->validator->read($this->root.'/'.DeterministicEffectStartJournalService::JOURNALS.'/'.$journalId.'.json', 'IGB611_EFFECT_START_JOURNAL_ABSENT');
        if (!$this->validator->isIntact($journal)
            || DeterministicEffectStartJournalContract::REQUIRED_FIELDS !== array_keys($journal)
            || DeterministicEffectStartJournalContract::SCHEMA !== ($journal['schema'] ?? null)
            || $journalId !== ($journal['journal_id'] ?? null)
            || 'EFFECT_STARTED' !== ($journal['effect']['checkpoint'] ?? null)
            || true !== ($journal['effect']['external_io_may_have_started'] ?? null)
            || 'UNKNOWN_REPLAY_PROHIBITED' !== ($journal['effect']['outcome'] ?? null)
            || false !== ($journal['effect']['provider_invoked_by_transition'] ?? null)
            || true !== ($journal['credential_use']['consumption_required'] ?? null)
            || false !== ($journal['credential_use']['consumed_by_journal'] ?? null)
            || false !== ($journal['credential_use']['credential_resolved'] ?? null)
            || new \DateTimeImmutable((string) ($journal['started_at'] ?? '1970-01-01')) > $at
            || new \DateTimeImmutable((string) ($journal['expires_at'] ?? '1970-01-01')) <= $at) {
            throw new \RuntimeException('IGB612_EFFECT_START_JOURNAL_INVALID');
        }
        $claimId = $journal['execution_claim']['id'] ?? null;
        if (!is_string($claimId) || !preg_match('/^deterministic-execution-claim-[a-f0-9]{20}$/', $claimId)) {
            throw new \RuntimeException('IGB613_EXECUTION_CLAIM_ABSENT');
        }
        $claim = $this->validator->read($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$claimId.'.json', 'IGB613_EXECUTION_CLAIM_ABSENT');
        if (!$this->validator->isIntact($claim)
            || DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim)
            || ($journal['execution_claim']['digest'] ?? null) !== ($claim['record_digest'] ?? null)
            || ($journal['execution_claim']['replay_fingerprint'] ?? null) !== ($claim['replay_fingerprint'] ?? null)
            || ($journal['execution_claim']['execution_id'] ?? null) !== ($claim['execution_identity']['execution_id'] ?? null)
            || $capability->capabilityId !== ($claim['credential_capability']['capability_id'] ?? null)
            || $capability->credentialReferenceDigest !== ($claim['credential_capability']['credential_reference_digest'] ?? null)
            || $capability->commissionId !== ($claim['request']['commission_id'] ?? null)
            || $capability->operation !== ($claim['request']['operation'] ?? null)
            || 'email.send' !== ($claim['request']['operation'] ?? null)
            || 1 !== $capability->maxUses
            || $capability->expiresAt <= $at
            || !hash_equals((string) ($claim['request']['payload_digest'] ?? ''), hash('sha256', $payload))) {
            throw new \RuntimeException('IGB614_PROVIDER_INVOCATION_SCOPE_INVALID');
        }

        $admissionId = 'deterministic-provider-invocation-admission-'.substr(hash('sha256', CanonicalJson::encode([$journalId, $journal['record_digest'], $claim['record_digest']])), 0, 20);
        $admission = $this->atomic->run('iron-gate-provider-invocation:'.$journalId, function () use ($journalId, $journal, $claim, $capability, $at, $admissionId): array {
            foreach (glob($this->root.'/'.self::ADMISSIONS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'IGB615_PROVIDER_INVOCATION_REPLAY_PROHIBITED');
                if (($prior['effect_start_journal']['id'] ?? null) === $journalId) {
                    throw new \RuntimeException('IGB615_PROVIDER_INVOCATION_REPLAY_PROHIBITED');
                }
            }
            return $this->records->put(self::ADMISSIONS, $admissionId, [
                'schema' => DeterministicProviderInvocationAdmissionContract::SCHEMA,
                'admission_id' => $admissionId,
                'instance_id' => $journal['instance_id'],
                'effect_start_journal' => ['id' => $journalId, 'digest' => $journal['record_digest']],
                'execution_claim' => ['id' => $claim['claim_id'], 'digest' => $claim['record_digest']],
                'credential_use' => ['capability_id' => $capability->capabilityId, 'credential_reference_digest' => $capability->credentialReferenceDigest, 'admission_committed' => true, 'consumption_attempted' => false, 'credential_secret_persisted' => false],
                'provider_request' => ['operation' => $claim['request']['operation'], 'destination' => $claim['request']['destination'], 'payload_digest' => $claim['request']['payload_digest'], 'idempotency_key' => $journal['provider_safety']['provider_idempotency_key'], 'request_fingerprint' => $journal['provider_safety']['request_fingerprint'], 'callback_admitted' => false, 'provider_callback_may_have_run' => false, 'outcome' => 'NOT_ATTEMPTED'],
                'admitted_at' => $at->format(DATE_ATOM),
                'expires_at' => $journal['expires_at'],
                'sealed' => true,
            ]);
        });

        $attempt = $this->checkpoint(DeterministicProviderInvocationCheckpointContract::CREDENTIAL_ATTEMPT_SCHEMA, self::CREDENTIAL_ATTEMPTS, 'credential-attempt', $admission, $claim, ['credential_consumption_attempted' => true, 'provider_callback_may_have_run' => false, 'outcome' => 'UNKNOWN_REPLAY_PROHIBITED'], $at);

        return $this->credentials->consume($capability, function (mixed $authentication) use ($journal, $admission, $claim, $payload, $providerCallback, $at, $attempt): mixed {
            $callbackStart = null;
            $wrapped = function (array $request) use (&$callbackStart, $admission, $claim, $providerCallback, $at, $attempt): mixed {
                $callbackStart = $this->checkpoint(DeterministicProviderInvocationCheckpointContract::CALLBACK_START_SCHEMA, self::CALLBACK_STARTS, 'callback-start', $admission, $claim, ['credential_consumption_attempt_id' => $attempt['checkpoint_id'], 'provider_callback_may_have_run' => true, 'outcome' => 'UNKNOWN_REPLAY_PROHIBITED'], $at);
                return $providerCallback($request);
            };
            $response = $this->adapter->invoke($journal, $admission['provider_request']['destination'], $payload, $authentication, $wrapped);
            if ($this->isObservedResponse($response)) {
                if (!is_array($callbackStart)) throw new \RuntimeException('IGB618_PROVIDER_CALLBACK_START_ABSENT');
                $this->captureResponse($journal, $admission, $callbackStart, $claim, $response, $at, $authentication);
            }

            return $response;
        });
    }

    private function isObservedResponse(mixed $response): bool
    {
        return is_array($response) && ['http_status', 'headers', 'body', 'observed_at', 'received_at'] === array_keys($response);
    }

    private function captureResponse(array $journal, array $admission, array $callbackStart, array $claim, array $response, \DateTimeImmutable $callbackStartedAt, mixed $authentication): array
    {
        if (!is_int($response['http_status']) || $response['http_status'] < 100 || $response['http_status'] > 599
            || !is_array($response['headers']) || !is_string($response['body'])
            || !is_string($response['observed_at']) || !is_string($response['received_at'])) {
            throw new \RuntimeException('IGB616_PROVIDER_RESPONSE_OBSERVATION_INVALID');
        }
        try {
            $observedAt = new \DateTimeImmutable($response['observed_at']);
            $receivedAt = new \DateTimeImmutable($response['received_at']);
        } catch (\Exception $exception) {
            throw new \RuntimeException('IGB616_PROVIDER_RESPONSE_OBSERVATION_INVALID', 0, $exception);
        }
        if ($observedAt < $callbackStartedAt || $receivedAt < $observedAt || $receivedAt > new \DateTimeImmutable($journal['expires_at'])) {
            throw new \RuntimeException('IGB616_PROVIDER_RESPONSE_OBSERVATION_INVALID');
        }
        foreach ($response['headers'] as $name => $value) {
            if (!is_string($name) || '' === trim($name) || (!is_string($value) && !is_array($value))) throw new \RuntimeException('IGB616_PROVIDER_RESPONSE_OBSERVATION_INVALID');
            if (is_array($value)) foreach ($value as $item) if (!is_string($item)) throw new \RuntimeException('IGB616_PROVIDER_RESPONSE_OBSERVATION_INVALID');
        }
        if (is_string($authentication) && '' !== $authentication && (str_contains($response['body'], $authentication) || str_contains(CanonicalJson::encode($response['headers']), $authentication))) {
            throw new \RuntimeException('IGB616_PROVIDER_RESPONSE_OBSERVATION_INVALID');
        }

        $contentDigest = hash('sha256', $response['body']);
        $headersDigest = hash('sha256', CanonicalJson::encode($response['headers']));
        $contentId = 'deterministic-provider-response-content-'.substr(hash('sha256', CanonicalJson::encode([$admission['admission_id'], $contentDigest])), 0, 20);
        $envelopeId = 'deterministic-provider-response-envelope-'.substr(hash('sha256', CanonicalJson::encode([$admission['admission_id'], $admission['record_digest'], $response['http_status'], $headersDigest, $contentDigest])), 0, 20);
        $envelope = [
            'schema' => DeterministicProviderResponseEnvelopeContract::SCHEMA,
            'envelope_id' => $envelopeId,
            'instance_id' => $journal['instance_id'],
            'provider_invocation_admission' => ['id' => $admission['admission_id'], 'digest' => $admission['record_digest']],
            'provider_callback_start' => ['id' => $callbackStart['checkpoint_id'], 'digest' => $callbackStart['record_digest']],
            'effect_start_journal' => ['id' => $journal['journal_id'], 'digest' => $journal['record_digest']],
            'execution_claim' => ['id' => $claim['claim_id'], 'digest' => $claim['record_digest']],
            'source_authorization' => ['id' => $claim['source_authorization']['id'], 'digest' => $claim['source_authorization']['digest']],
            'request' => ['operation' => $claim['request']['operation'], 'destination' => $claim['request']['destination'], 'payload_digest' => $claim['request']['payload_digest'], 'provider_idempotency_key' => $journal['provider_safety']['provider_idempotency_key'], 'request_fingerprint' => $journal['provider_safety']['request_fingerprint']],
            'provider_observation' => ['http_status' => $response['http_status'], 'headers_digest' => $headersDigest, 'content_digest' => $contentDigest, 'sealed_content_reference' => self::RESPONSE_CONTENT.'/'.$contentId.'.json#content_base64', 'callback_started_at' => $callbackStartedAt->format(DATE_ATOM), 'response_observed_at' => $observedAt->format(DATE_ATOM), 'received_at' => $receivedAt->format(DATE_ATOM)],
            'recovery' => ['checkpoint' => 'PROVIDER_RESPONSE_OBSERVED', 'automatic_replay_permitted' => false, 'provider_reinvoked' => false],
            'produced_by' => DeterministicProviderResponseEnvelopeContract::PRODUCER,
            'captured_at' => $receivedAt->format(DATE_ATOM),
            'sealed' => true,
        ];

        return $this->atomic->run('iron-gate-provider-response:'.$admission['admission_id'], function () use ($admission, $contentId, $contentDigest, $response, $envelopeId, $envelope): array {
            foreach (glob($this->root.'/'.self::RESPONSE_ENVELOPES.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'IGB617_PROVIDER_RESPONSE_ENVELOPE_CONFLICT');
                if (($prior['provider_invocation_admission']['id'] ?? null) !== $admission['admission_id']) continue;
                if (!$this->validator->isIntact($prior) || ($prior['envelope_id'] ?? null) !== $envelopeId) throw new \RuntimeException('IGB617_PROVIDER_RESPONSE_ENVELOPE_CONFLICT');
                return $prior;
            }
            $this->records->put(self::RESPONSE_CONTENT, $contentId, ['schema' => 'imperium.la-cortine.deterministic-provider-response-content/v1', 'content_id' => $contentId, 'provider_invocation_admission' => ['id' => $admission['admission_id'], 'digest' => $admission['record_digest']], 'content_digest' => $contentDigest, 'content_base64' => base64_encode($response['body']), 'sealed' => true]);

            return $this->records->put(self::RESPONSE_ENVELOPES, $envelopeId, $envelope);
        });
    }

    private function checkpoint(string $schema, string $directory, string $kind, array $admission, array $claim, array $state, \DateTimeImmutable $at): array
    {
        $id = 'deterministic-provider-'.$kind.'-'.substr(hash('sha256', CanonicalJson::encode([$admission['admission_id'], $admission['record_digest'], $state])), 0, 20);
        return $this->records->put($directory, $id, ['schema' => $schema, 'checkpoint_id' => $id, 'instance_id' => $admission['instance_id'], 'provider_invocation_admission' => ['id' => $admission['admission_id'], 'digest' => $admission['record_digest']], 'execution_claim' => ['id' => $claim['claim_id'], 'digest' => $claim['record_digest']], 'state' => $state, 'recorded_at' => $at->format(DATE_ATOM), 'sealed' => true]);
    }

}
