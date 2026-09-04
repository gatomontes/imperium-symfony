<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Provider-double proof boundary. No credential resolver or network transport is reachable. */
final readonly class NativeEffectDoubleExecutionService
{
    public const string CALLBACK_STARTS = 'var/imperium/runtime/canonical-native-effect-callback-starts';
    public const string RESPONSES = 'var/imperium/runtime/canonical-native-effect-responses';
    public const string RECEIPTS = 'var/imperium/runtime/canonical-native-effect-receipts';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private NativeEffectReceiptBindingService $binder;

    public function __construct(
        private NativeState $state,
        private NativeEffectContinuationCapabilityIssuer $continuations,
        private ?\Closure $checkpoint = null,
    )
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->binder = new NativeEffectReceiptBindingService($state);
    }

    public function execute(
        string $admissionId,
        NativeEffectContinuationCapability $continuation,
        string $payload,
        string $idempotencyKey,
        int $at,
        callable $providerDouble,
    ): array {
        $scope = 'canonical-native-effect-continuation:'.hash('sha256', $admissionId);
        $phase = $this->atomic->run($scope, function () use ($admissionId, $continuation, $payload, $idempotencyKey, $at): array {
            $admission = $this->records->read(NativeEffectAtomicAdmissionService::ADMISSIONS, $admissionId);
            $receiptId = NativeEffectForwardRecoveryClaimAdmissionService::receiptId($admissionId);
            try {
                $this->records->read(self::RECEIPTS, $receiptId);
                throw new \RuntimeException('CNE507_FIRST_EXECUTION_ALREADY_COMPLETED');
            } catch (\RuntimeException $error) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) { throw $error; }
            }

            $callbackId = 'canonical-native-effect-callback-'.substr(hash('sha256', $admissionId), 0, 20);
            try {
                $priorStart = $this->records->read(self::CALLBACK_STARTS, $callbackId);
                $responseId = 'canonical-native-effect-response-'.substr(hash('sha256', $callbackId), 0, 20);
                try {
                    $this->records->read(self::RESPONSES, $responseId);
                    throw new \RuntimeException('CNE508_FORWARD_RECOVERY_REQUIRED');
                } catch (\RuntimeException $responseError) {
                    if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $responseError->getMessage()) { throw $responseError; }
                    if (($priorStart['effect_admission']['digest'] ?? null) === $admission['record_digest']) {
                        throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
                    }
                    throw new \RuntimeException('CNE403_CALLBACK_START_CONFLICT');
                }
            } catch (\RuntimeException $error) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) { throw $error; }
            }

            $this->assertAndConsumeContinuation($admission, $continuation, $payload, $idempotencyKey, $at);

            $callback = $this->records->put(self::CALLBACK_STARTS, $callbackId, [
                'schema' => 'imperium.la-cortine.canonical-native-effect-callback-start/v1',
                'callback_start_id' => $callbackId,
                'effect_admission' => $this->ref($admission, 'admission_id'),
                'effect_authority' => $admission['effect_authority'],
                'provider_callback_may_have_run' => true,
                'outcome' => 'UNKNOWN_REPLAY_PROHIBITED',
                'automatic_replay_permitted' => false,
                'started_at' => $at,
                'sealed' => true,
            ]);

            return ['completed' => false, 'admission' => $admission, 'callback' => $callback, 'receipt_id' => $receiptId];
        });

        $admission = $phase['admission'];
        $callback = $phase['callback'];

        try {
            $observed = $providerDouble([
                'operation' => $admission['receipt_input']['provider_request']['operation'],
                'destination' => $admission['receipt_input']['provider_request']['destination'],
                'payload' => $payload,
                'idempotency_key' => $idempotencyKey,
                'provider_id' => $admission['receipt_input']['provider']['provider_id'],
                'adapter_id' => $admission['receipt_input']['provider']['adapter_id'],
                'adapter_version' => $admission['receipt_input']['provider']['adapter_version'],
                'authentication_present' => false,
                'provider_double_only' => true,
            ]);
            $response = $this->response($admission, $callback, $observed, $at);
            $response = $this->records->put(self::RESPONSES, $response['response_id'], $response);
            if (null !== $this->checkpoint) { ($this->checkpoint)('response.sealed'); }
        } catch (\Throwable $error) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED', 0, $error);
        }

        return $this->atomic->run($scope, function () use ($admissionId, $response, $phase, $at): array {
            $storedAdmission = $this->records->read(NativeEffectAtomicAdmissionService::ADMISSIONS, $admissionId);
            if ($storedAdmission['record_digest'] !== $response['effect_admission']['digest']) {
                throw new \RuntimeException('CNE403_CALLBACK_START_CONFLICT');
            }
            return $this->binder->bind($storedAdmission, $response, $phase['receipt_id'], $at);
        });
    }

    public function reconstruct(string $receiptId): array
    {
        $receipt = $this->records->read(self::RECEIPTS, $receiptId);
        return ['receipt' => $receipt, 'read_only' => true, 'provider_reinvoked' => false,
            'credential_resolved' => false, 'retry_authorized' => false, 'continuing_authority' => false];
    }

    private function assertAndConsumeContinuation(array $admission, NativeEffectContinuationCapability $continuation, string $payload, string $key, int $at): void
    {
        $input = $this->receiptInput($admission);
        if (NativeEffectAdmissionContract::SCHEMA !== ($admission['schema'] ?? null)
            || NativeEffectAdmissionContract::CHECKPOINT !== ($admission['effect_start']['checkpoint'] ?? null)
            || true !== ($admission['authority_consumption']['consumed'] ?? null)
            || true !== ($admission['effect_start']['capability_consumed'] ?? null)
            || false !== ($admission['effect_start']['credential_resolved'] ?? null)
            || false !== ($admission['effect_start']['callback_started'] ?? null)
            || $continuation->admissionId !== $admission['admission_id']
            || $continuation->admissionDigest !== $admission['record_digest']
            || $continuation->semanticEffectTupleId !== $admission['semantic_effect_tuple_id']
            || $continuation->authorityConsumptionId !== $admission['authority_consumption_id']
            || $continuation->processBoundaryId !== $input['execution_boundary']['id']
            || $input['provider_request']['payload_digest'] !== hash('sha256', $payload)
            || $input['provider_request']['provider_idempotency_key_digest'] !== hash('sha256', $key)
            || $at < $admission['admitted_at'] || $at >= $admission['expires_at']
            || $at >= $continuation->expiresAt
            || !$this->continuations->consume($continuation)) {
            throw new \RuntimeException('CNE400_EFFECT_CONTINUATION_INVALID');
        }
    }

    private function response(array $admission, array $callback, mixed $observed, int $at): array
    {
        if (!is_array($observed) || array_keys($observed) !== ['http_status', 'headers', 'body', 'observed_at', 'received_at']
            || !is_int($observed['http_status']) || $observed['http_status'] < 100 || $observed['http_status'] > 599
            || !is_array($observed['headers']) || !is_string($observed['body'])
            || !is_int($observed['observed_at']) || !is_int($observed['received_at'])
            || $observed['observed_at'] < $at || $observed['received_at'] < $observed['observed_at']) {
            throw new \RuntimeException('CNE401_PROVIDER_OBSERVATION_INVALID');
        }
        $responseId = 'canonical-native-effect-response-'.substr(hash('sha256', $callback['callback_start_id']), 0, 20);
        return [
            'schema' => NativeEffectResultContract::RESPONSE_SCHEMA,
            'response_id' => $responseId,
            'effect_admission' => $this->ref($admission, 'admission_id'),
            'callback_start' => $this->ref($callback, 'callback_start_id'),
            'provider_observation' => [
                'http_status' => $observed['http_status'],
                'headers_digest' => hash('sha256', CanonicalJson::encode($observed['headers'])),
                'content_digest' => hash('sha256', $observed['body']),
                'observed_at' => $observed['observed_at'],
                'received_at' => $observed['received_at'],
                'local_callback_lineage_only' => true,
                'remote_cryptographic_authorship_proved' => false,
            ],
            'raw_content' => ['content_base64' => base64_encode($observed['body']), 'credential_material_present' => false],
            'recovery' => ['checkpoint' => 'RAW_RESPONSE_SEALED', 'automatic_replay_permitted' => false, 'provider_reinvoked' => false],
            'sealed_at' => $observed['received_at'],
            'sealed' => true,
        ];
    }

    private function receiptInput(array $admission): array
    {
        $input = $admission['receipt_input'] ?? null;
        if (!is_array($input)
            || NativeEffectReceiptInputContract::REQUIRED_FIELDS !== array_keys($input)
            || NativeEffectReceiptInputContract::SCHEMA !== ($input['schema'] ?? null)
            || NativeState::seal($input) !== $input
            || ($input['semantic_effect_tuple_id'] ?? null) !== ($admission['semantic_effect_tuple_id'] ?? null)
            || ($input['authority_consumption_id'] ?? null) !== ($admission['authority_consumption_id'] ?? null)
            || ($input['effect_authority'] ?? null) !== ($admission['effect_authority'] ?? null)
            || ($input['native_root'] ?? null) !== ($admission['native_root'] ?? null)
            || ($input['native_receipt'] ?? null) !== ($admission['native_receipt'] ?? null)
            || NativeEffectReceiptInputContract::REQUIRED_PROVIDER_REQUEST_FIELDS !== array_keys($input['provider_request'] ?? [])
            || NativeEffectReceiptInputContract::REQUIRED_PROVIDER_FIELDS !== array_keys($input['provider'] ?? [])
            || NativeEffectReceiptInputContract::REQUIRED_CREDENTIAL_SCOPE_FIELDS !== array_keys($input['credential_scope'] ?? [])) {
            throw new \RuntimeException('CNE406_RECEIPT_INPUT_INVALID');
        }
        return $input;
    }

    private function ref(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'schema' => $record['schema'], 'digest' => $record['record_digest']];
    }
}
