<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationPreparationBatch0Test extends TestCase
{
    public function testEveryCurrentProofCheckboxHasATypedReplacementBoundary(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-derivation-remediation-preparation-inventory.md');
        foreach ([
            '`interruption_classifications_proved`',
            '`exact_replay_convergence_proved`',
            '`changed_evidence_refusal_proved`',
            '`same_root_contention_refusal_proved`',
            '`partial_write_refusal_proved`',
            '`automatic_repair_refusal_proved`',
            '`secret_exclusion_proved`',
            '`non_authority_perimeter_proved`',
            'No current boolean is admissible as evidence merely because it is `true`.',
        ] as $claim) {
            self::assertStringContainsString($claim, $inventory);
        }
    }

    public function testInventorySeparatesCanonicalEvidenceFromMissingDerivation(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-derivation-remediation-preparation-inventory.md');
        foreach ([
            'Strict journal, winner and receipt validation',
            'Deterministic interruption classification',
            'Deterministic pair comparison',
            'Reusable canonical fixture factory',
            'Typed adversarial case contract',
            'Deterministic adversarial case executor',
            'Finding derivation',
            'Case and aggregate evidence digests',
            'Immutable read-only audit receipt',
            'Action-capability manifest',
            'Value-aware secret exclusion',
            'Terminal evidence-chain recomputation',
            '`EXISTS_CANONICALLY`',
            '`EXISTS_FRAGMENTED`',
            '`ABSENT`',
            '`DEFERRED_BOUNDARY`',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
    }

    public function testEmptyEvidenceCannotProveTheAdversarialMatrix(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-derivation-remediation-preparation-inventory.md');
        foreach ([
            '`ABSENT` may prove only `NO_ACTION`',
            'It may not prove replay, contention, committed-state, partial-write or tamper behavior.',
            'Replay or contention additionally requires a second complete evidence set.',
            'No single classification proves the entire adversarial matrix.',
        ] as $scope) {
            self::assertStringContainsString($scope, $inventory);
        }
    }

    public function testHandoffAuthorizesTypedContractsOnlyAndPreservesQualification(): void
    {
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-preparation-batch-0-complete.md');
        foreach ([
            'Only Atomic Transition Evidence Derivation Remediation Batch 1 typed adversarial case, mutation, expected-result and reusable immutable fixture contracts with pure validation may next be considered.',
            'may not execute an adversarial case',
            'derive an audit finding',
            'seal an audit receipt',
            'remove the campaign qualification',
            'may not change runtime behavior',
            'may not persist a journal',
            'may not acquire a live lock',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not open Iron Gate or Lazaretto',
            '`CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT`',
            'Estimated remediation countdown after Preparation Batch 0: five batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
