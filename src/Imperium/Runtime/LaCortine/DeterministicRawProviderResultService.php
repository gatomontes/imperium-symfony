<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicRawProviderResultService
{
    public const string RESULTS = 'var/imperium/la-cortine/deterministic-raw-provider-results';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function seal(string $admissionId, int $httpStatus, string $responseBytes, \DateTimeImmutable $observedAt, \DateTimeImmutable $receivedAt): array
    {
        if (!preg_match('/^deterministic-provider-invocation-admission-[a-f0-9]{20}$/', $admissionId)
            || $httpStatus < 100 || $httpStatus > 599 || '' === $responseBytes || $receivedAt < $observedAt) {
            throw new \InvalidArgumentException('IGR700_PROVIDER_RESULT_INVALID');
        }
        $admission = $this->validator->read($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/'.$admissionId.'.json', 'IGR701_PROVIDER_INVOCATION_ADMISSION_ABSENT');
        if (!$this->validator->isIntact($admission)
            || DeterministicProviderInvocationAdmissionContract::REQUIRED_FIELDS !== array_keys($admission)
            || DeterministicProviderInvocationAdmissionContract::SCHEMA !== ($admission['schema'] ?? null)
            || $admissionId !== ($admission['admission_id'] ?? null)
            || true !== ($admission['credential_use']['credential_use_committed'] ?? null)
            || false !== ($admission['credential_use']['credential_secret_persisted'] ?? null)
            || true !== ($admission['provider_request']['provider_callback_may_have_run'] ?? null)
            || 'UNKNOWN_REPLAY_PROHIBITED' !== ($admission['provider_request']['outcome'] ?? null)
            || new \DateTimeImmutable((string) ($admission['admitted_at'] ?? '1970-01-01')) > $observedAt) {
            throw new \RuntimeException('IGR702_PROVIDER_INVOCATION_ADMISSION_INVALID');
        }
        $claimId = $admission['execution_claim']['id'] ?? null;
        if (!is_string($claimId) || !preg_match('/^deterministic-execution-claim-[a-f0-9]{20}$/', $claimId)) {
            throw new \RuntimeException('IGR703_EXECUTION_CLAIM_INVALID');
        }
        $claim = $this->validator->read($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$claimId.'.json', 'IGR703_EXECUTION_CLAIM_INVALID');
        if (!$this->validator->isIntact($claim) || ($admission['execution_claim']['digest'] ?? null) !== ($claim['record_digest'] ?? null)) {
            throw new \RuntimeException('IGR703_EXECUTION_CLAIM_INVALID');
        }

        $accepted = $httpStatus >= 200 && $httpStatus < 300;
        $providerIdentity = null;
        if ($accepted) {
            try {
                $decoded = json_decode($responseBytes, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new \RuntimeException('IGR704_AGENTMAIL_RECEIPT_INVALID', 0, $exception);
            }
            if (!is_array($decoded) || !is_string($decoded['message_id'] ?? null) || '' === $decoded['message_id'] || !is_string($decoded['thread_id'] ?? null) || '' === $decoded['thread_id']) {
                throw new \RuntimeException('IGR704_AGENTMAIL_RECEIPT_INVALID');
            }
            $providerIdentity = ['message_id' => $decoded['message_id'], 'thread_id' => $decoded['thread_id']];
        }

        $contentDigest = hash('sha256', $responseBytes);
        $rawReceiptId = 'deterministic-raw-provider-receipt-'.substr(hash('sha256', CanonicalJson::encode([$admissionId, $contentDigest, $httpStatus])), 0, 20);
        $resultId = 'deterministic-raw-provider-result-'.substr(hash('sha256', CanonicalJson::encode([$admissionId, $admission['record_digest'], $rawReceiptId])), 0, 20);
        $record = [
            'schema' => DeterministicRawProviderResultContract::SCHEMA,
            'result_id' => $resultId,
            'instance_id' => $admission['instance_id'],
            'provider_invocation_admission' => ['id' => $admissionId, 'digest' => $admission['record_digest']],
            'execution_claim' => ['id' => $claimId, 'digest' => $claim['record_digest']],
            'provider_outcome' => ['status' => $accepted ? 'ACCEPTED' : 'REJECTED', 'http_status' => $httpStatus, 'provider_receipt_identity' => $providerIdentity, 'effect_started_at' => $admission['admitted_at'], 'resolved_at' => $receivedAt->format(DATE_ATOM), 'provider_idempotency_key' => $admission['provider_request']['idempotency_key']],
            'raw_receipt' => ['id' => $rawReceiptId, 'schema' => 'agentmail.http-response/v1', 'content_digest' => $contentDigest, 'content_base64' => base64_encode($responseBytes), 'content_type' => $accepted ? 'application/json' : 'application/octet-stream', 'observed_at' => $observedAt->format(DATE_ATOM), 'received_at' => $receivedAt->format(DATE_ATOM)],
            'recovery' => ['checkpoint' => 'RAW_RECEIPT_SEALED', 'automatic_replay_permitted' => false, 'provider_reinvoked' => false, 'forward_recovery_source' => $rawReceiptId],
            'recorded_at' => $receivedAt->format(DATE_ATOM),
            'sealed' => true,
        ];

        return $this->atomic->run('iron-gate-provider-result:'.$admissionId, function () use ($admissionId, $resultId, $record): array {
            foreach (glob($this->root.'/'.self::RESULTS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'IGR705_PROVIDER_RESULT_CONFLICT');
                if (($prior['provider_invocation_admission']['id'] ?? null) !== $admissionId) continue;
                if (!$this->validator->isIntact($prior) || ($prior['result_id'] ?? null) !== $resultId) throw new \RuntimeException('IGR705_PROVIDER_RESULT_CONFLICT');
                return $prior;
            }
            return $this->records->put(self::RESULTS, $resultId, $record);
        });
    }
}
