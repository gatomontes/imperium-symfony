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

    public function __construct(private NativeState $state)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
    }

    public function execute(
        string $admissionId,
        array $authority,
        string $payload,
        string $idempotencyKey,
        int $at,
        callable $providerDouble,
    ): array {
        return $this->atomic->run('canonical-native-effect-continuation:'.hash('sha256', $admissionId), function () use ($admissionId, $authority, $payload, $idempotencyKey, $at, $providerDouble): array {
            $admission = $this->records->read(NativeEffectAtomicAdmissionService::ADMISSIONS, $admissionId);
            $receiptId = 'canonical-native-effect-receipt-'.substr(hash('sha256', $admissionId), 0, 20);
            try {
                return $this->records->read(self::RECEIPTS, $receiptId);
            } catch (\RuntimeException $error) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) { throw $error; }
            }

            $this->assertContinuation($admission, $authority, $payload, $idempotencyKey, $at);
            $callbackId = 'canonical-native-effect-callback-'.substr(hash('sha256', $admissionId), 0, 20);
            try {
                $priorStart = $this->records->read(self::CALLBACK_STARTS, $callbackId);
                $responseId = 'canonical-native-effect-response-'.substr(hash('sha256', $callbackId), 0, 20);
                try {
                    $response = $this->records->read(self::RESPONSES, $responseId);
                    return $this->bindReceipt($admission, $authority, $response, $receiptId, $at);
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

            try {
                $observed = $providerDouble([
                    'operation' => $admission['provider_request']['operation'],
                    'destination' => $admission['provider_request']['destination'],
                    'payload' => $payload,
                    'idempotency_key' => $idempotencyKey,
                    'authentication_present' => false,
                    'provider_double_only' => true,
                ]);
                $response = $this->response($admission, $callback, $observed, $at);
                $response = $this->records->put(self::RESPONSES, $response['response_id'], $response);
            } catch (\Throwable $error) {
                throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED', 0, $error);
            }

            return $this->bindReceipt($admission, $authority, $response, $receiptId, $at);
        });
    }

    public function reconstruct(string $receiptId): array
    {
        $receipt = $this->records->read(self::RECEIPTS, $receiptId);
        return ['receipt' => $receipt, 'read_only' => true, 'provider_reinvoked' => false,
            'credential_resolved' => false, 'retry_authorized' => false, 'continuing_authority' => false];
    }

    private function assertContinuation(array $admission, array $authority, string $payload, string $key, int $at): void
    {
        if (NativeEffectAdmissionContract::SCHEMA !== ($admission['schema'] ?? null)
            || NativeEffectAdmissionContract::CHECKPOINT !== ($admission['effect_start']['checkpoint'] ?? null)
            || true !== ($admission['authority_consumption']['consumed'] ?? null)
            || true !== ($admission['effect_start']['capability_consumed'] ?? null)
            || false !== ($admission['effect_start']['credential_resolved'] ?? null)
            || false !== ($admission['effect_start']['callback_started'] ?? null)
            || $admission['effect_authority'] !== NativeState::ref($authority, 'authority_id')
            || $admission['provider_request']['payload_digest'] !== hash('sha256', $payload)
            || $admission['provider_request']['provider_idempotency_key_digest'] !== hash('sha256', $key)
            || $at < $admission['admitted_at'] || $at >= $admission['expires_at']) {
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

    private function bindReceipt(array $admission, array $authority, array $response, string $receiptId, int $at): array
    {
        $bytes = base64_decode($response['raw_content']['content_base64'] ?? '', true);
        if (!is_string($bytes) || hash('sha256', $bytes) !== ($response['provider_observation']['content_digest'] ?? null)) {
            throw new \RuntimeException('CNE404_RAW_RESPONSE_INVALID');
        }
        $accepted = $response['provider_observation']['http_status'] >= 200 && $response['provider_observation']['http_status'] < 300;
        $normalized = null;
        if ($accepted) {
            try { $decoded = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR); }
            catch (\Throwable $error) { throw new \RuntimeException('CNE405_EXPECTED_RETURN_INVALID', 0, $error); }
            if (!is_array($decoded) || !is_string($decoded['message_id'] ?? null) || '' === $decoded['message_id']
                || !is_string($decoded['thread_id'] ?? null) || '' === $decoded['thread_id']) {
                throw new \RuntimeException('CNE405_EXPECTED_RETURN_INVALID');
            }
            $normalized = ['provider_message_id' => $decoded['message_id'], 'provider_thread_id' => $decoded['thread_id']];
        }
        return $this->records->put(self::RECEIPTS, $receiptId, [
            'schema' => NativeEffectResultContract::RECEIPT_SCHEMA,
            'receipt_id' => $receiptId,
            'effect_admission' => $this->ref($admission, 'admission_id'),
            'effect_authority' => NativeState::ref($authority, 'authority_id'),
            'native_receipt' => $admission['native_receipt'],
            'provider_outcome' => [
                'status' => $accepted ? 'ACCEPTED' : 'REJECTED',
                'http_status' => $response['provider_observation']['http_status'],
                'normalized_attributes' => $normalized,
            ],
            'raw_response' => $this->ref($response, 'response_id'),
            'lazaretto_admission' => [
                'expected_return_contract' => $authority['expected_return_contract'],
                'expected_return_contract_validated' => $accepted,
                'admitted' => $accepted,
                'authority' => 'none',
            ],
            'recovery' => ['checkpoint' => 'RECEIPT_BOUND', 'automatic_replay_permitted' => false, 'provider_reinvoked' => false],
            'bound_at' => $at,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
    }

    private function ref(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'schema' => $record['schema'], 'digest' => $record['record_digest']];
    }
}
