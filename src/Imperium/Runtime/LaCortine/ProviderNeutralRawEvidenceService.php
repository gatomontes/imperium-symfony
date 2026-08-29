<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderNeutralRawEvidenceService
{
    public const string EVIDENCE = 'var/imperium/offices/la-cortine/provider-neutral-raw-evidence';
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function preserve(array $binding, array $toolOperation, array $sourceAuthorization, array $executionClaim, int $httpStatus, string $contentType, string $rawContent, \DateTimeImmutable $observedAt): array
    {
        foreach ([$toolOperation, $sourceAuthorization, $executionClaim] as $reference) {
            if (['id', 'digest', 'schema'] !== array_keys($reference) || !preg_match('/^[a-f0-9]{64}$/', (string) $reference['digest'])) throw new \RuntimeException('GTP600_RAW_EVIDENCE_CONTEXT_INVALID');
        }
        $bindingDigest = $binding['record_digest'] ?? null;
        $unsealedBinding = $binding;
        unset($unsealedBinding['record_digest']);
        if ('BOUND_INACTIVE' !== ($binding['status'] ?? null) || !is_string($bindingDigest) || !hash_equals($bindingDigest, hash('sha256', CanonicalJson::encode($unsealedBinding)))
            || $toolOperation !== ($binding['tool_operation'] ?? null)
            || ($sourceAuthorization['id'] ?? null) !== ($binding['scope']['authorization_target_id'] ?? null)
            || ($sourceAuthorization['digest'] ?? null) !== ($binding['scope']['authorization_target_digest'] ?? null)
            || $httpStatus < 100 || $httpStatus > 599
            || '' === trim($contentType) || '' === $rawContent) throw new \RuntimeException('GTP600_RAW_EVIDENCE_CONTEXT_INVALID');

        $bindingReference = ['id' => $binding['binding_id'], 'digest' => $binding['record_digest'], 'schema' => $binding['schema']];
        $contentDigest = hash('sha256', $rawContent);
        $id = 'provider-neutral-raw-evidence-'.substr(hash('sha256', CanonicalJson::encode([$bindingReference, $executionClaim, $contentDigest, $httpStatus])), 0, 20);

        return $this->records->put(self::EVIDENCE, $id, [
            'schema' => ProviderNeutralRawEvidenceContract::SCHEMA, 'evidence_id' => $id, 'instance_id' => $binding['instance_id'],
            'tool_operation' => $toolOperation, 'source_authorization' => $sourceAuthorization, 'execution_claim' => $executionClaim, 'provider_binding' => $bindingReference,
            'provider_observation' => ['http_status' => $httpStatus, 'content_type' => $contentType, 'content_digest' => $contentDigest],
            'content_base64' => base64_encode($rawContent), 'observed_at' => $observedAt->format(DATE_ATOM), 'sealed' => true,
        ]);
    }
}
