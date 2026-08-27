<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

use App\Bootstrap\CanonicalJson;

final readonly class DecisionSurfaceAssembler
{
    public function __construct(private DecisionIntegrityRecordStore $store)
    {
    }

    public function assemble(array $universe, array $directive, \DateTimeImmutable $presentedAt): array
    {
        $this->validateEnvelope($universe, DecisionSurfaceOptionUniverseContract::SCHEMA, DecisionSurfaceOptionUniverseContract::REQUIRED_FIELDS, 'DI150_OPTION_UNIVERSE_INVALID');
        $this->validateEnvelope($directive, DecisionSurfacePresentationDirectiveContract::SCHEMA, DecisionSurfacePresentationDirectiveContract::REQUIRED_FIELDS, 'DI151_PRESENTATION_DIRECTIVE_INVALID');
        foreach ([[$universe, 'universe_id'], [$universe, 'instance_id'], [$universe, 'proceeding_id'], [$directive, 'directive_id'], [$directive, 'instance_id'], [$directive, 'proceeding_id']] as [$record, $field]) {
            DecisionIntegrityValidation::requireText($record[$field], 'DI152_DECISION_SURFACE_SOURCE_MISMATCH');
        }
        $this->reference($directive['source_option_universe'], 'DI152_DECISION_SURFACE_SOURCE_MISMATCH');
        if (($universe['instance_id'] ?? null) !== ($directive['instance_id'] ?? null)
            || ($universe['proceeding_id'] ?? null) !== ($directive['proceeding_id'] ?? null)
            || ($directive['source_option_universe']['id'] ?? null) !== ($universe['universe_id'] ?? null)
            || ($directive['source_option_universe']['digest'] ?? null) !== ($universe['record_digest'] ?? null)) {
            throw new \RuntimeException('DI152_DECISION_SURFACE_SOURCE_MISMATCH');
        }
        $authoredAt = DecisionIntegrityValidation::requireUtcTime($directive['authored_at'], 'DI151_PRESENTATION_DIRECTIVE_INVALID');
        if ($authoredAt > $presentedAt || '+00:00' !== $presentedAt->format('P')) {
            throw new \RuntimeException('DI151_PRESENTATION_DIRECTIVE_INVALID');
        }
        $options = $this->options($universe);
        $evidence = $this->evidence($universe, $directive, $presentedAt);
        $categories = $this->categories($directive, $options);

        $recommendation = $directive['recommendation'];
        if (!is_array($recommendation)) {
            throw new \RuntimeException('DI153_CURIA_AUTHORSHIP_INVALID');
        }
        DecisionIntegrityValidation::requireFields($recommendation, ['author', 'recommended_option_id', 'rationale'], 'DI153_CURIA_AUTHORSHIP_INVALID');
        foreach (['author', 'recommended_option_id', 'rationale'] as $field) {
            DecisionIntegrityValidation::requireText($recommendation[$field], 'DI153_CURIA_AUTHORSHIP_INVALID');
        }
        if (!in_array($recommendation['recommended_option_id'], $directive['presented_option_ids'], true)) {
            throw new \RuntimeException('DI153_CURIA_AUTHORSHIP_INVALID');
        }

        $sourceUniverse = ['id' => $universe['universe_id'], 'digest' => $universe['record_digest']];
        $sourceDirective = ['id' => $directive['directive_id'], 'digest' => $directive['record_digest']];
        $fingerprint = hash('sha256', CanonicalJson::encode([
            'source_option_universe' => $sourceUniverse,
            'source_presentation_directive' => $sourceDirective,
            'categories' => array_intersect_key($directive, array_flip(DecisionSurfacePresentationDirectiveContract::CATEGORIES)),
            'requested_authority' => $directive['requested_authority'],
            'authority_not_requested' => $directive['authority_not_requested'],
            'limitations' => $directive['limitations'],
            'expires_at' => $directive['expires_at'],
            'evidence' => array_map(static fn (array $item): array => ['artifact_id' => $item['artifact_id'], 'record_digest' => $item['record_digest']], $evidence),
        ]));
        $surfaceId = 'decision-surface-'.substr(hash('sha256', CanonicalJson::encode([$sourceUniverse, $sourceDirective, $fingerprint])), 0, 20);
        $surface = [
            'schema' => InstitutionalDecisionSurfaceContract::SCHEMA,
            'surface_id' => $surfaceId,
            'instance_id' => $universe['instance_id'],
            'proceeding_id' => $universe['proceeding_id'],
            'source_option_universe' => $sourceUniverse,
            'source_presentation_directive' => $sourceDirective,
            'decision_owner' => $directive['decision_owner'],
            'decision_question' => $directive['decision_question'],
            'options_presented' => $this->presented($categories['presented_option_ids'], $options),
            'unavailable_options' => $this->classified($categories['unavailable_option_ids'], $options),
            'prohibited_options' => $this->classified($categories['prohibited_option_ids'], $options),
            'rejected_options' => $this->classified($categories['rejected_option_ids'], $options),
            'unexamined_options' => $this->classified($categories['unexamined_option_ids'], $options),
            'material_consequences' => $directive['material_consequences'],
            'risks' => $directive['risks'],
            'reversibility' => $directive['reversibility'],
            'recommendation' => $recommendation,
            'evidence' => $evidence,
            'requested_authority' => $directive['requested_authority'],
            'authority_not_requested' => $directive['authority_not_requested'],
            'limitations' => $directive['limitations'],
            'expires_at' => $directive['expires_at'],
            'material_facts_fingerprint' => $fingerprint,
            'allowed_dispositions' => $directive['allowed_dispositions'],
            'authorization_state' => [
                'decision_pending' => true,
                'authority_granted' => false,
                'decision_inferred' => false,
                'non_authorizing_signals' => InstitutionalDecisionSurfaceContract::NON_AUTHORIZING_SIGNALS,
            ],
            'presented_at' => $presentedAt->format(DATE_ATOM),
            'sealed' => true,
        ];

        return $this->store->putSurface($surface);
    }

    private function validateEnvelope(array $record, string $schema, array $required, string $error): void
    {
        DecisionIntegrityValidation::requireFields($record, $required, $error);
        if ($schema !== $record['schema'] || true !== $record['sealed']) {
            throw new \RuntimeException($error);
        }
        DecisionIntegrityValidation::requireDigestIntegrity($record, $error);
    }

    private function reference(mixed $reference, string $error): void
    {
        if (!is_array($reference)) {
            throw new \RuntimeException($error);
        }
        DecisionIntegrityValidation::requireFields($reference, ['id', 'digest'], $error);
        DecisionIntegrityValidation::requireText($reference['id'], $error);
        if (!is_string($reference['digest']) || 1 !== preg_match('/^[a-f0-9]{64}$/', $reference['digest'])) {
            throw new \RuntimeException($error);
        }
    }

    private function options(array $universe): array
    {
        $indexed = [];
        foreach (DecisionIntegrityValidation::requireList($universe['options'], 'DI150_OPTION_UNIVERSE_INVALID') as $option) {
            if (!is_array($option)) {
                throw new \RuntimeException('DI150_OPTION_UNIVERSE_INVALID');
            }
            DecisionIntegrityValidation::requireFields($option, DecisionSurfaceOptionUniverseContract::REQUIRED_OPTION_FIELDS, 'DI150_OPTION_UNIVERSE_INVALID');
            $id = DecisionIntegrityValidation::requireText($option['option_id'], 'DI150_OPTION_UNIVERSE_INVALID');
            if (isset($indexed[$id]) || !is_bool($option['materially_relevant']) || !in_array($option['availability'], DecisionSurfaceOptionUniverseContract::AVAILABILITY, true)) {
                throw new \RuntimeException('DI150_OPTION_UNIVERSE_INVALID');
            }
            foreach (['classification_reason', 'plain_language_explanation', 'material_consequences', 'reversibility', 'authority_effect'] as $field) {
                DecisionIntegrityValidation::requireText($option[$field], 'DI150_OPTION_UNIVERSE_INVALID');
            }
            foreach (['risks', 'costs', 'external_effects', 'evidence'] as $field) {
                DecisionIntegrityValidation::requireList($option[$field], 'DI150_OPTION_UNIVERSE_INVALID', true);
            }
            $indexed[$id] = $option;
        }

        return $indexed;
    }

    private function categories(array $directive, array $options): array
    {
        $categories = [];
        $seen = [];
        foreach (DecisionSurfacePresentationDirectiveContract::CATEGORIES as $category) {
            $categories[$category] = DecisionIntegrityValidation::requireList($directive[$category], 'DI154_OPTION_CLASSIFICATION_INVALID', 'presented_option_ids' !== $category);
            foreach ($categories[$category] as $id) {
                DecisionIntegrityValidation::requireText($id, 'DI154_OPTION_CLASSIFICATION_INVALID');
                if (!isset($options[$id]) || isset($seen[$id])) {
                    throw new \RuntimeException('DI154_OPTION_CLASSIFICATION_INVALID');
                }
                $availability = $options[$id]['availability'];
                if (('unavailable_option_ids' === $category && 'UNAVAILABLE' !== $availability)
                    || ('prohibited_option_ids' === $category && 'PROHIBITED' !== $availability)
                    || (in_array($category, ['presented_option_ids', 'rejected_option_ids', 'unexamined_option_ids'], true) && 'AVAILABLE' !== $availability)) {
                    throw new \RuntimeException('DI154_OPTION_CLASSIFICATION_INVALID');
                }
                $seen[$id] = $category;
            }
        }
        foreach ($options as $id => $option) {
            if (true === $option['materially_relevant'] && !isset($seen[$id])) {
                throw new \RuntimeException('DI155_MATERIAL_OPTION_OMITTED');
            }
        }

        return $categories;
    }

    private function evidence(array $universe, array $directive, \DateTimeImmutable $presentedAt): array
    {
        $indexed = [];
        foreach ([$universe['evidence'], $directive['evidence']] as $set) {
            foreach (DecisionIntegrityValidation::requireList($set, 'DI156_ASSEMBLY_EVIDENCE_INVALID') as $evidence) {
                if (!is_array($evidence)) {
                    throw new \RuntimeException('DI156_ASSEMBLY_EVIDENCE_INVALID');
                }
                DecisionIntegrityValidation::validateEvidence($evidence, $presentedAt, InstitutionalDecisionSurfaceContract::REQUIRED_EVIDENCE_FIELDS, 'DI156_ASSEMBLY_EVIDENCE_INVALID');
                $id = $evidence['artifact_id'];
                if (isset($indexed[$id]) && CanonicalJson::encode($indexed[$id]) !== CanonicalJson::encode($evidence)) {
                    throw new \RuntimeException('DI156_ASSEMBLY_EVIDENCE_INVALID');
                }
                $indexed[$id] = $evidence;
            }
        }
        foreach ($universe['options'] as $option) {
            foreach ($option['evidence'] as $evidenceId) {
                if (!is_string($evidenceId) || !isset($indexed[$evidenceId])) {
                    throw new \RuntimeException('DI156_ASSEMBLY_EVIDENCE_INVALID');
                }
            }
        }
        ksort($indexed, SORT_STRING);

        return array_values($indexed);
    }

    private function presented(array $ids, array $options): array
    {
        return array_map(static fn (string $id): array => array_intersect_key($options[$id], array_flip(InstitutionalDecisionSurfaceContract::REQUIRED_OPTION_FIELDS)), $ids);
    }

    private function classified(array $ids, array $options): array
    {
        return array_map(static fn (string $id): array => array_intersect_key($options[$id], array_flip(InstitutionalDecisionSurfaceContract::REQUIRED_CLASSIFIED_OPTION_FIELDS)), $ids);
    }
}
