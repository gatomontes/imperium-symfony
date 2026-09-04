<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Admission-derived local receipt binding. It has no callback or credential edge. */
final readonly class NativeEffectReceiptBindingService
{
    private ImmutableRecordStore $records;

    public function __construct(private NativeState $state)
    {
        $this->records = new ImmutableRecordStore($state->root, new AtomicTransition($state->root));
    }

    public function bind(array $admission, array $response, string $receiptId, int $at): array
    {
        $input = $this->receiptInput($admission);
        $bytes = base64_decode($response['raw_content']['content_base64'] ?? '', true);
        if (!is_string($bytes) || hash('sha256', $bytes) !== ($response['provider_observation']['content_digest'] ?? null)) {
            throw new \RuntimeException('CNE404_RAW_RESPONSE_INVALID');
        }
        if (($response['effect_admission']['digest'] ?? null) !== ($admission['record_digest'] ?? null)) {
            throw new \RuntimeException('CNE409_RESPONSE_ADMISSION_MISMATCH');
        }
        $accepted = $response['provider_observation']['http_status'] >= 200 && $response['provider_observation']['http_status'] < 300;
        $normalized = null;
        if ($accepted) {
            if ('agentmail.message/v1' !== $input['expected_return_contract']) {
                throw new \RuntimeException('CNE405_EXPECTED_RETURN_INVALID');
            }
            try {
                $decoded = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
            } catch (\Throwable $error) {
                throw new \RuntimeException('CNE405_EXPECTED_RETURN_INVALID', 0, $error);
            }
            if (!is_array($decoded) || !is_string($decoded['message_id'] ?? null) || '' === $decoded['message_id']
                || !is_string($decoded['thread_id'] ?? null) || '' === $decoded['thread_id']) {
                throw new \RuntimeException('CNE405_EXPECTED_RETURN_INVALID');
            }
            $normalized = ['provider_message_id' => $decoded['message_id'], 'provider_thread_id' => $decoded['thread_id']];
        }

        return $this->records->put(NativeEffectDoubleExecutionService::RECEIPTS, $receiptId, [
            'schema' => NativeEffectResultContract::RECEIPT_SCHEMA,
            'receipt_id' => $receiptId,
            'effect_admission' => $this->ref($admission, 'admission_id'),
            'effect_authority' => $input['effect_authority'],
            'native_receipt' => $input['native_receipt'],
            'provider_outcome' => [
                'status' => $accepted ? 'ACCEPTED' : 'REJECTED',
                'http_status' => $response['provider_observation']['http_status'],
                'normalized_attributes' => $normalized,
            ],
            'raw_response' => $this->ref($response, 'response_id'),
            'lazaretto_admission' => [
                'expected_return_contract' => $input['expected_return_contract'],
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
