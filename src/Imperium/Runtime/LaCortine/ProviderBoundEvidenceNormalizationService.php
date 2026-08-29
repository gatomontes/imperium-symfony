<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBoundEvidenceNormalizationService
{
    public const string RESULTS = 'var/imperium/offices/la-cortine/normalized-tool-results';
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private BoundProviderEvidenceDecoder $decoder)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function normalize(array $binding, array $rawEvidence, \DateTimeImmutable $at): array
    {
        $bytes = base64_decode((string) ($rawEvidence['content_base64'] ?? ''), true);
        $bindingRef = ['id' => $binding['binding_id'] ?? null, 'digest' => $binding['record_digest'] ?? null, 'schema' => $binding['schema'] ?? null];
        $rawDigest = $rawEvidence['record_digest'] ?? null;
        $unsealedRaw = $rawEvidence;
        unset($unsealedRaw['record_digest']);
        $bindingDigest = $binding['record_digest'] ?? null;
        $unsealedBinding = $binding;
        unset($unsealedBinding['record_digest']);
        if (ProviderNeutralRawEvidenceContract::REQUIRED_FIELDS !== array_keys($rawEvidence) || !is_string($bytes)
            || 'BOUND_INACTIVE' !== ($binding['status'] ?? null)
            || !is_string($rawDigest) || !hash_equals($rawDigest, hash('sha256', CanonicalJson::encode($unsealedRaw)))
            || !is_string($bindingDigest) || !hash_equals($bindingDigest, hash('sha256', CanonicalJson::encode($unsealedBinding)))
            || $bindingRef !== ($rawEvidence['provider_binding'] ?? null) || !$this->decoder->supports($binding)
            || !hash_equals((string) ($rawEvidence['provider_observation']['content_digest'] ?? ''), hash('sha256', $bytes))) throw new \RuntimeException('GTP610_BOUND_DECODER_CONTEXT_INVALID');

        $decoded = $this->decoder->decode($binding, ['id' => $rawEvidence['evidence_id'], 'digest' => $rawEvidence['record_digest'], 'schema' => $rawEvidence['schema'], 'content_digest' => $rawEvidence['provider_observation']['content_digest']], $bytes, $at);
        $status = $rawEvidence['provider_observation']['http_status'] >= 200 && $rawEvidence['provider_observation']['http_status'] < 300 ? 'ACCEPTED' : 'REJECTED';
        $id = 'normalized-tool-result-'.substr(hash('sha256', CanonicalJson::encode([$bindingRef, $rawEvidence['record_digest'], $decoded['record_digest']])), 0, 20);

        return $this->records->put(self::RESULTS, $id, [
            'schema' => NormalizedToolResultContract::SCHEMA, 'result_id' => $id, 'instance_id' => $rawEvidence['instance_id'],
            'tool_operation' => $rawEvidence['tool_operation'], 'source_authorization' => $rawEvidence['source_authorization'], 'execution_claim' => $rawEvidence['execution_claim'], 'provider_binding' => $bindingRef,
            'provider_evidence' => ['raw_result_id' => $rawEvidence['evidence_id'], 'raw_result_digest' => $rawEvidence['record_digest'], 'sealed_content_reference' => ProviderNeutralRawEvidenceService::EVIDENCE.'/'.$rawEvidence['evidence_id'].'.json#content_base64', 'content_digest' => $rawEvidence['provider_observation']['content_digest']],
            'decoder' => ['decoder_id' => $decoded['decoder_id'], 'decoder_version' => $decoded['decoder_version'], 'decoder_contract' => $decoded['schema']],
            'effect_outcome' => ['status' => $status, 'provider_status' => $rawEvidence['provider_observation']['http_status'], 'effect_started_at' => $rawEvidence['observed_at'], 'resolved_at' => $at->format(DATE_ATOM)],
            'normalized_attributes' => $decoded['normalized_attributes'], 'recovery' => ['checkpoint' => 'NORMALIZED_PENDING_LAZARETTO_ADMISSION', 'automatic_replay_permitted' => false, 'provider_reinvoked' => false],
            'normalized_at' => $at->format(DATE_ATOM), 'sealed' => true,
        ]);
    }
}
