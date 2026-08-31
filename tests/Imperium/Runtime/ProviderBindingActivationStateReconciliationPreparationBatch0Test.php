<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationStateReconciliationPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesTheLegacyActivationAsIncompatible(): void
    {
        $inventory = $this->document(
            'docs/provider-binding-activation-state-reconciliation-preparation-inventory.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_IMMUTABLE_BINDING_SUCCESSOR_REQUIRED',
            'ATTESTED_INERT',
            'ACTIVATED_UNCONSUMED',
            'provider_binding_activated=false',
            'canonical principal truth is a separate immutable ACTIVE activation',
            'EXISTS_CANONICALLY_BUT_INCOMPATIBLE',
            'Owner of BOUND_ACTIVE',
            'ABSENT',
            'immutable operation-scoped successor',
            'global BOUND_ACTIVE mutation',
            'promote legacy ACTIVATED_UNCONSUMED',
            'cross-process custody refusal remains authoritative',
            'provider binding remains BOUND_INACTIVE',
            'UNKNOWN_REPLAY_PROHIBITED remains binding',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testRuntimeSourcesExposeTheExactSemanticConflict(): void
    {
        $binding = $this->source('ProviderImplementationBindingContract.php');
        $legacy = $this->source('SingleOperationProviderBindingActivationIssuanceService.php');
        $principal = $this->source('ProviderExecutorPrincipalActivationService.php');

        self::assertStringContainsString("'BOUND_ACTIVE'", $binding);
        self::assertStringContainsString("'BOUND_INACTIVE'", $legacy);
        self::assertStringContainsString("'ATTESTED_INERT'", $legacy);
        self::assertStringContainsString("'ACTIVATED_UNCONSUMED'", $legacy);
        self::assertStringContainsString("'provider_binding_activated' => false", $legacy);
        self::assertStringContainsString("'status' => 'ACTIVE'", $principal);
    }

    public function testHandoffAuthorizesAuthorityEmptyContractsOnly(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-preparation-batch-0-complete.md',
        );

        foreach ([
            'Only Provider Binding Activation State Reconciliation Batch 1',
            'authority-empty contracts',
            'may not implement a producer',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'Iron Gate',
            'Lazaretto',
            'approximately six batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function source(string $name): string
    {
        return (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'.$name,
        );
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
