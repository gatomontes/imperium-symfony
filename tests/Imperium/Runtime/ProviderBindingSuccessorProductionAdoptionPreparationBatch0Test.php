<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionAdoptionPreparationBatch0Test extends TestCase
{
    public function testCampaignSelectionNamesPreparationBatchZeroOnly(): void
    {
        $ready = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-campaign-ready.md',
        );

        self::assertStringContainsString(
            'Begin Provider Binding Successor Production Adoption Preparation Batch 0 only.',
            $ready,
        );
        self::assertStringContainsString(
            'provider-binding-activation-state-reconciliation-campaign-complete.md',
            $ready,
        );
        self::assertStringContainsString(
            'GovernedProviderExecutionCombinedAdmissionService.php',
            $ready,
        );
    }

    public function testInventoryClassifiesTheProductionAuthorityAndAdoptionGap(): void
    {
        $inventory = $this->document(
            'docs/provider-binding-successor-production-adoption-preparation-inventory.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_PRODUCTION_SUCCESSOR_DECISION_AND_ATOMIC_ADOPTION_ROUTE_REQUIRED',
            'Credential possession is not execution authority.',
            'process-local capability identity',
            'credential-owning executor',
            'one activation-keyed atomic winner',
            'Crash before the atomic put leaves no successor and no consumed authority.',
            'Exact replay converges; changed evidence conflicts.',
            'Same-root contention yields one winner.',
            'Expired or revoked decision lineage and authority refuse before creation.',
            'Reconstruction never repairs, replaces, promotes or reissues authority.',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $required) {
            self::assertStringContainsString($required, $inventory);
        }
    }

    public function testPreparationPreservesTheClosedRuntimePerimeter(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-preparation-batch-0-complete.md',
        );

        foreach ([
            'may not define runtime contracts or change runtime behavior',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The original binding remains BOUND_INACTIVE.',
        ] as $prohibition) {
            self::assertStringContainsString($prohibition, $handoff);
        }
    }

    public function testOnlyAuthorityEmptyBatchOneContractsAreNext(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-preparation-batch-0-complete.md',
        );

        self::assertStringContainsString(
            'Only Provider Binding Successor Production Adoption Batch 1 authority-empty contracts',
            $handoff,
        );
        self::assertStringNotContainsString('provider_invoked'." => true", $handoff);
        self::assertStringNotContainsString('external_io_started'." => true", $handoff);
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
