<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract as Decision;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicCreationWinnerBoundaryContract as Winner;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesEveryLiveAdoptionBoundary(): void
    {
        $inventory = $this->document(
            'docs/provider-binding-successor-live-adoption-preparation-inventory.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_LIVE_ADOPTION_DECISION_AUTHORITY_AND_ATOMIC_V3_TRANSITION_REQUIRED',
            'Competent live-adoption decision principal and issuer | ABSENT',
            'Live-adoption authority contract, issuer and durable custody | ABSENT',
            'v3 successor execution-admission contract and validator | EXISTS_CANONICALLY',
            'v3 admission production service | ABSENT',
            'Authority consumption, v3 admission, adoption and binding transition as one winner | ABSENT',
            'Crash, replay, contention, expiry and revocation proof | EXISTS_FRAGMENTED',
            'Read-only live-adoption aggregate reconstruction | ABSENT',
            'Durable secret exclusion | EXISTS_CANONICALLY',
            'Credential resolution, provider invocation, external I/O and effect start | DEFERRED_BOUNDARY',
        ] as $finding) {
            self::assertStringContainsString($finding, $inventory);
        }
    }

    public function testExistingContractsRemainAuthorityEmptyAndInert(): void
    {
        self::assertSame('CONTRACT_ONLY_NOT_DECIDED', Decision::STATUS);
        self::assertTrue(Decision::INVARIANTS['authority_empty']);
        self::assertFalse(Decision::INVARIANTS['decision_performed']);
        self::assertFalse(Decision::INVARIANTS['live_adoption_performed']);

        self::assertSame('INERT_NOT_EXECUTED', Winner::STATUS);
        self::assertFalse(Winner::INVARIANTS['authority_consumed']);
        self::assertFalse(Winner::INVARIANTS['successor_created']);

        self::assertSame('NOT_IMPLEMENTED', V3::STATUS);
        self::assertFalse(V3::INVARIANTS['execution_admitted']);
        self::assertFalse(V3::INVARIANTS['live_adoption_performed']);
        self::assertFalse(V3::INVARIANTS['effect_start_permitted']);
    }

    public function testHandoffAuthorizesBatchOneContractsOnly(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-preparation-batch-0-complete.md',
        );

        foreach ([
            'Only Provider Binding Successor Live Adoption Batch 1 authority-empty competent decision-principal, issuer and immutable decision-lineage contracts may next be considered.',
            'Batch 1 may define contracts and pure validators only.',
            'may not produce a decision, issue or consume authority, admit execution, adopt a successor or change binding state',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
            'The v3 execution admission remains NOT_IMPLEMENTED.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    public function testInventoryPreservesTheProviderEffectCutoff(): void
    {
        $inventory = $this->document(
            'docs/provider-binding-successor-live-adoption-preparation-inventory.md',
        );

        self::assertStringContainsString(
            'this campaign must stop before them and may not splice them into adoption',
            $inventory,
        );
        self::assertStringContainsString(
            'UNKNOWN_REPLAY_PROHIBITED remains authoritative after effect start and is outside live adoption',
            $inventory,
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
