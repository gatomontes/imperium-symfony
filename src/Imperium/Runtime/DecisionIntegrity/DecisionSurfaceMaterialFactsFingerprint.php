<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

use App\Bootstrap\CanonicalJson;

final class DecisionSurfaceMaterialFactsFingerprint
{
    public const array CATEGORIES = [
        'options',
        'consequences',
        'evidence',
        'risk',
        'scope',
        'limitations',
        'expiry',
        'recipient',
        'requested_authority',
    ];

    public function fingerprint(array $surface): string
    {
        return hash('sha256', CanonicalJson::encode($this->facts($surface)));
    }

    public function changedCategories(array $prior, array $current): array
    {
        $priorFacts = $this->facts($prior);
        $currentFacts = $this->facts($current);

        return array_values(array_filter(self::CATEGORIES, static fn (string $category): bool => CanonicalJson::encode($priorFacts[$category]) !== CanonicalJson::encode($currentFacts[$category])));
    }

    public function facts(array $surface): array
    {
        $optionSets = array_intersect_key($surface, array_flip([
            'options_presented',
            'unavailable_options',
            'prohibited_options',
            'rejected_options',
            'unexamined_options',
        ]));
        $allOptions = array_merge(...array_values($optionSets));

        return [
            'options' => array_map(static fn (array $options): array => array_map(static fn (array $option): array => array_intersect_key($option, array_flip([
                'option_id', 'classification_reason',
            ])), $options), $optionSets),
            'consequences' => [
                'material_consequences' => $surface['material_consequences'],
                'option_effects' => array_map(static fn (array $option): array => array_intersect_key($option, array_flip([
                    'option_id', 'plain_language_explanation', 'material_consequences', 'costs', 'external_effects', 'reversibility', 'authority_effect',
                ])), $allOptions),
            ],
            'evidence' => [
                'artifacts' => $surface['evidence'],
                'option_bindings' => array_map(static fn (array $option): array => [
                    'option_id' => $option['option_id'],
                    'evidence' => $option['evidence'] ?? [],
                ], $allOptions),
            ],
            'risk' => [
                'surface' => $surface['risks'],
                'options' => array_map(static fn (array $option): array => ['option_id' => $option['option_id'], 'risks' => $option['risks']], $allOptions),
            ],
            'scope' => [
                'decision_question' => $surface['decision_question'],
                'allowed_dispositions' => $surface['allowed_dispositions'],
                'reversibility' => $surface['reversibility'],
                'authority_not_requested' => $surface['authority_not_requested'],
                'recommendation' => $surface['recommendation'],
            ],
            'limitations' => $surface['limitations'],
            'expiry' => $surface['expires_at'],
            'recipient' => $surface['decision_owner'],
            'requested_authority' => $surface['requested_authority'],
        ];
    }
}
