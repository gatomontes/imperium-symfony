<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final readonly class DecisionSurfaceMaterialChangeDetector
{
    public function __construct(
        private InstitutionalDecisionSurfaceValidator $validator = new InstitutionalDecisionSurfaceValidator(),
        private DecisionSurfaceMaterialFactsFingerprint $fingerprint = new DecisionSurfaceMaterialFactsFingerprint(),
    ) {
    }

    public function assess(array $prior, array $current): array
    {
        $this->validator->validate($prior);
        $this->validator->validate($current);
        if ($prior['instance_id'] !== $current['instance_id'] || $prior['proceeding_id'] !== $current['proceeding_id']) {
            throw new \RuntimeException('DI160_REAUTHORIZATION_PROCEEDING_MISMATCH');
        }

        $changed = $this->fingerprint->changedCategories($prior, $current);
        $stale = [] !== $changed;

        return [
            'status' => $stale ? 'FRESH_DECISION_SURFACE_REQUIRED' : 'MATERIAL_FACTS_UNCHANGED',
            'prior_surface' => ['id' => $prior['surface_id'], 'digest' => $prior['record_digest']],
            'current_surface' => ['id' => $current['surface_id'], 'digest' => $current['record_digest']],
            'changed_material_fact_categories' => $changed,
            'prior_consent_stale' => $stale,
            'fresh_decision_surface_required' => $stale,
            'authority_granted' => false,
            'continuation_authority' => false,
        ];
    }
}
