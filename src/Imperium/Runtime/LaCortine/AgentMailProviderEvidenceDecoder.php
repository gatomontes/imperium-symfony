<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final readonly class AgentMailProviderEvidenceDecoder
{
    public function decode(array $binding, array $rawResult, string $rawContent, \DateTimeImmutable $decodedAt): array
    {
        if (AgentMailProviderProfile::PROVIDER_ID !== ($binding['provider_implementation']['provider_id'] ?? null)
            || AgentMailProviderProfile::EVIDENCE_DECODER_ID !== ($binding['evidence_decoder']['id'] ?? null)
            || !is_string($rawResult['id'] ?? null)
            || !is_string($rawResult['digest'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $rawResult['digest'])
            || !hash_equals($rawResult['content_digest'] ?? '', hash('sha256', $rawContent))) {
            throw new \RuntimeException('GTP410_AGENTMAIL_DECODER_CONTEXT_INVALID');
        }
        try {
            $receipt = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('GTP411_AGENTMAIL_RECEIPT_INVALID', 0, $exception);
        }
        foreach (AgentMailProviderProfile::RECEIPT_FIELDS as $field) {
            if (!is_array($receipt) || !is_string($receipt[$field] ?? null) || '' === trim($receipt[$field])) {
                throw new \RuntimeException('GTP411_AGENTMAIL_RECEIPT_INVALID');
            }
        }

        $record = [
            'schema' => ProviderEvidenceDecoderContract::SCHEMA,
            'decoder_id' => AgentMailProviderProfile::EVIDENCE_DECODER_ID,
            'decoder_version' => AgentMailProviderProfile::ADAPTER_VERSION,
            'provider_binding' => ['id' => $binding['binding_id'], 'digest' => $binding['record_digest'], 'schema' => $binding['schema']],
            'raw_provider_result' => ['id' => $rawResult['id'], 'digest' => $rawResult['digest'], 'schema' => $rawResult['schema']],
            'normalized_result_contract' => NormalizedToolResultContract::SCHEMA,
            'normalized_attributes' => ['provider_message_id' => $receipt['message_id'], 'provider_thread_id' => $receipt['thread_id']],
            'decoded_at' => $decodedAt->format(DATE_ATOM),
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
