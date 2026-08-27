<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final readonly class DecisionIntegrityReconstructionService
{
    public function __construct(private DecisionIntegrityRecordStore $store)
    {
    }

    public function reconstruct(string $decisionRecordId): array
    {
        $decision = $this->store->readDecision($decisionRecordId);
        $surface = $this->store->readSurface($decision['source_decision_surface']['id']);
        $universe = $this->store->readOptionUniverse($surface['source_option_universe']['id']);
        $directive = $this->store->readPresentationDirective($surface['source_presentation_directive']['id']);
        $priorDecisions = [];
        foreach ($decision['prior_decisions'] as $reference) {
            $prior = $this->store->readDecision($reference['id']);
            if ($prior['record_digest'] !== $reference['digest']) {
                throw new \RuntimeException('DI180_DECISION_RECONSTRUCTION_INVALID');
            }
            $priorDecisions[] = $prior;
        }

        $requiredUnderlying = array_merge(
            [$surface['source_option_universe'], $surface['source_presentation_directive']],
            array_map(static fn (array $evidence): array => ['id' => $evidence['artifact_id'], 'digest' => $evidence['record_digest']], $surface['evidence']),
        );
        if ($surface['record_digest'] !== $decision['source_decision_surface']['digest']
            || $universe['record_digest'] !== $surface['source_option_universe']['digest']
            || $directive['record_digest'] !== $surface['source_presentation_directive']['digest']
            || $directive['source_option_universe'] !== $surface['source_option_universe']
            || $decision['instance_id'] !== $surface['instance_id']
            || $decision['proceeding_id'] !== $surface['proceeding_id']
            || $universe['instance_id'] !== $surface['instance_id']
            || $universe['proceeding_id'] !== $surface['proceeding_id']
            || $directive['instance_id'] !== $surface['instance_id']
            || $directive['proceeding_id'] !== $surface['proceeding_id']
            || $decision['evidence_relied_on'] !== $surface['evidence']
            || !$this->containsEveryReference($decision['underlying_proceeding_evidence'], $requiredUnderlying)) {
            throw new \RuntimeException('DI180_DECISION_RECONSTRUCTION_INVALID');
        }

        return [
            'decision_record' => $decision,
            'decision_surface' => $surface,
            'option_universe' => $universe,
            'presentation_directive' => $directive,
            'evidence' => $surface['evidence'],
            'source_requests' => $decision['source_requests'],
            'prior_decisions' => $priorDecisions,
            'reconstruction_complete' => true,
            'authority_granted' => false,
            'authority_consumed' => false,
            'continuation_authority' => false,
        ];
    }

    private function containsEveryReference(array $actual, array $required): bool
    {
        $indexed = [];
        foreach ($actual as $reference) {
            $indexed[($reference['id'] ?? '').':'.($reference['digest'] ?? '')] = true;
        }
        foreach ($required as $reference) {
            if (!isset($indexed[$reference['id'].':'.$reference['digest']])) {
                return false;
            }
        }

        return true;
    }
}
