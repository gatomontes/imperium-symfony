<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicLazarettoReceiptAdmissionService
{
    public const string BINDINGS = 'var/imperium/lazaretto/deterministic-receipt-bindings';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function admit(string $resultId, \DateTimeImmutable $admittedAt): array
    {
        if (!preg_match('/^deterministic-raw-provider-result-[a-f0-9]{20}$/', $resultId)) {
            throw new \InvalidArgumentException('IGL800_RAW_PROVIDER_RESULT_ID_INVALID');
        }
        $result = $this->validator->read($this->root.'/'.DeterministicRawProviderResultService::RESULTS.'/'.$resultId.'.json', 'IGL801_RAW_PROVIDER_RESULT_ABSENT');
        if (!$this->validator->isIntact($result)
            || DeterministicRawProviderResultContract::REQUIRED_FIELDS !== array_keys($result)
            || DeterministicRawProviderResultContract::SCHEMA !== ($result['schema'] ?? null)
            || $resultId !== ($result['result_id'] ?? null)
            || 'ACCEPTED' !== ($result['provider_outcome']['status'] ?? null)
            || 'RAW_RECEIPT_SEALED' !== ($result['recovery']['checkpoint'] ?? null)
            || true === ($result['recovery']['automatic_replay_permitted'] ?? null)
            || true === ($result['recovery']['provider_reinvoked'] ?? null)
            || new \DateTimeImmutable((string) ($result['recorded_at'] ?? '1970-01-01')) > $admittedAt) {
            throw new \RuntimeException('IGL802_RAW_PROVIDER_RESULT_NOT_ADMISSIBLE');
        }
        $claimId = $result['execution_claim']['id'] ?? null;
        if (!is_string($claimId) || !preg_match('/^deterministic-execution-claim-[a-f0-9]{20}$/', $claimId)) throw new \RuntimeException('IGL803_EXECUTION_CLAIM_INVALID');
        $claim = $this->validator->read($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$claimId.'.json', 'IGL803_EXECUTION_CLAIM_INVALID');
        if (!$this->validator->isIntact($claim)
            || DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim)
            || ($result['execution_claim']['digest'] ?? null) !== ($claim['record_digest'] ?? null)
            || 'agentmail.message/v1' !== ($claim['request']['expected_return_contract'] ?? null)) {
            throw new \RuntimeException('IGL803_EXECUTION_CLAIM_INVALID');
        }
        $bytes = base64_decode((string) ($result['raw_receipt']['content_base64'] ?? ''), true);
        if (!is_string($bytes) || !hash_equals((string) ($result['raw_receipt']['content_digest'] ?? ''), hash('sha256', $bytes))) throw new \RuntimeException('IGL804_RAW_RECEIPT_INVALID');
        try {
            $receipt = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('IGL804_RAW_RECEIPT_INVALID', 0, $exception);
        }
        if (!is_array($receipt)
            || ($result['provider_outcome']['provider_receipt_identity']['message_id'] ?? null) !== ($receipt['message_id'] ?? null)
            || ($result['provider_outcome']['provider_receipt_identity']['thread_id'] ?? null) !== ($receipt['thread_id'] ?? null)) {
            throw new \RuntimeException('IGL804_RAW_RECEIPT_INVALID');
        }

        $artifactId = 'lazaretto-agentmail-receipt-'.substr(hash('sha256', CanonicalJson::encode([$resultId, $result['record_digest'], $result['raw_receipt']['content_digest']])), 0, 20);
        $artifactDigest = hash('sha256', CanonicalJson::encode(['message_id' => $receipt['message_id'], 'thread_id' => $receipt['thread_id'], 'raw_digest' => $result['raw_receipt']['content_digest']]));
        $bindingId = 'deterministic-receipt-binding-'.substr(hash('sha256', CanonicalJson::encode([$artifactId, $claimId, $resultId])), 0, 20);
        $record = [
            'schema' => DeterministicReceiptBindingContract::SCHEMA,
            'binding_id' => $bindingId,
            'instance_id' => $result['instance_id'],
            'execution_claim' => ['id' => $claimId, 'digest' => $claim['record_digest'], 'replay_fingerprint' => $claim['replay_fingerprint'], 'execution_id' => $claim['execution_identity']['execution_id']],
            'source_authorization' => ['id' => $claim['source_authorization']['id'], 'digest' => $claim['source_authorization']['digest']],
            'request' => ['id' => $claim['request']['id'], 'commission_id' => $claim['request']['commission_id'], 'authorization_id' => $claim['request']['authorization_id'], 'authorization_digest' => $claim['request']['authorization_digest'], 'operation' => $claim['request']['operation'], 'destination' => $claim['request']['destination'], 'payload_digest' => $claim['request']['payload_digest'], 'credential_capability_id' => $claim['credential_capability']['capability_id'], 'expected_return_contract' => $claim['request']['expected_return_contract']],
            'provider_outcome' => ['status' => $result['provider_outcome']['status'], 'effect_started_at' => $result['provider_outcome']['effect_started_at'], 'resolved_at' => $result['provider_outcome']['resolved_at'], 'provider_idempotency_key' => $result['provider_outcome']['provider_idempotency_key'], 'provider_receipt_identity' => $result['provider_outcome']['provider_receipt_identity'], 'provider_contract_reference' => $claim['provider_safety']['provider_contract_reference']],
            'raw_receipt' => ['id' => $result['raw_receipt']['id'], 'schema' => $result['raw_receipt']['schema'], 'content_digest' => $result['raw_receipt']['content_digest'], 'sealed_content_reference' => DeterministicRawProviderResultService::RESULTS.'/'.$resultId.'.json#raw_receipt.content_base64', 'observed_at' => $result['raw_receipt']['observed_at'], 'received_at' => $result['raw_receipt']['received_at']],
            'lazaretto_admission' => ['artifact_id' => $artifactId, 'artifact_digest' => $artifactDigest, 'expected_return_contract_validated' => true, 'admitted_at' => $admittedAt->format(DATE_ATOM), 'transformation' => 'VALIDATED_AGENTMAIL_MESSAGE_RECEIPT_NO_CONTENT_MUTATION'],
            'recovery' => ['checkpoint' => 'COMPLETE', 'automatic_replay_permitted' => false, 'provider_reinvoked' => false, 'forward_recovery_source' => $result['raw_receipt']['id']],
            'bound_at' => $admittedAt->format(DATE_ATOM),
            'sealed' => true,
        ];

        return $this->atomic->run('iron-gate-receipt-binding:'.$resultId, function () use ($resultId, $bindingId, $record): array {
            foreach (glob($this->root.'/'.self::BINDINGS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'IGL805_RECEIPT_BINDING_CONFLICT');
                if (str_contains((string) ($prior['raw_receipt']['sealed_content_reference'] ?? ''), '/'.$resultId.'.json#')) {
                    if (!$this->validator->isIntact($prior) || ($prior['binding_id'] ?? null) !== $bindingId) throw new \RuntimeException('IGL805_RECEIPT_BINDING_CONFLICT');
                    return $prior;
                }
            }
            return $this->records->put(self::BINDINGS, $bindingId, $record);
        });
    }
}
