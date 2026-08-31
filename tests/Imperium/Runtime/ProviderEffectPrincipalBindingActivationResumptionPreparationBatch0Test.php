<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderEffectPrincipalBindingActivationResumptionPreparationBatch0Test
    extends TestCase
{
    public function testInventoryClassifiesEveryRequiredJoinBoundary(): void
    {
        $inventory = $this->document(
            'docs/provider-effect-principal-binding-activation-resumption-preparation-batch-0.md',
        );

        foreach ([
            'RESUMPTION_PREPARATION_BATCH_0_COMPLETE_CANONICAL_DECISION_TO_ACTIVATION_JOIN_REQUIRED',
            'Canonical decision production and custody',
            'Canonical production reconstruction',
            'Existing principal-activation transition',
            'Canonical decision resolution into activation',
            'Activation target identity',
            'Activation-authority custody',
            'Activation-authority consumption',
            'Cross-boundary lock identity',
            'Cross-boundary lock ordering',
            'Exact replay',
            'Changed-evidence contention',
            'Expiry and revocation',
            'Before/after-commit recovery',
            'Read-only reconstruction',
            'Secret exclusion',
            'Provider-binding activation boundary',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testSourceConfirmsCanonicalEndpointsAndAbsentJoin(): void
    {
        $root = dirname(__DIR__, 3);
        $production = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/'
                .'PrincipalActivationDecisionAuthorityProvenanceProductionService.php',
        );
        $activation = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationService.php',
        );

        self::assertStringContainsString('public function reconstruct(', $production);
        self::assertStringContainsString("'activation_decision' => \$decision", $production);
        self::assertStringContainsString("'activation_authority_consumed' => false", $production);

        self::assertStringContainsString('public function activate(', $activation);
        self::assertStringContainsString('array $decision', $activation);
        self::assertStringContainsString("'principal-activation:'", $activation);
        self::assertStringNotContainsString(
            'PrincipalActivationDecisionAuthorityProvenanceProductionService',
            $activation,
        );
        self::assertStringNotContainsString('production_id', $activation);
    }

    public function testInventorySelectsTheSmallestSafeSequence(): void
    {
        $inventory = $this->document(
            'docs/provider-effect-principal-binding-activation-resumption-preparation-batch-0.md',
        );

        foreach ([
            'Batch 1: authority-empty canonical resolution/admission contracts',
            'Batch 2: pure validators',
            'Batch 3: read-only aggregate reconstruction',
            'Batch 4: one canonical activation entry point',
            'Batch 5: read-only adversarial audit',
            'Batch 6: terminal audit',
            'approximately six batches',
        ] as $step) {
            self::assertNotFalse(stripos($inventory, $step), $step);
        }
    }

    public function testHandoffAuthorizesContractsOnlyAndPreservesClosedPerimeter(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-preparation-batch-0-complete.md',
        );

        foreach ([
            'Only Provider Effect Principal and Binding Activation Resumption Batch 1',
            'authority-empty contracts',
            'production-winner resolution/admission record',
            'activation target',
            'single-use activation-authority identity',
            'shared replay/contention root',
            'must create no record',
            'may not alter either production service',
            'provider binding remains BOUND_INACTIVE',
            'Iron Gate or Lazaretto',
            'approximately six batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
