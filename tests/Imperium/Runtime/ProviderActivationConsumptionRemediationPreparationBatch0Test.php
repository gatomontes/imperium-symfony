<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderActivationConsumptionRemediationPreparationBatch0Test extends TestCase
{
    public function testPreparationSelectsOneActivationKeyedCombinedWinner(): void
    {
        $inventory = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/provider-activation-consumption-remediation-preparation-inventory.md',
            ),
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_COMBINED_WINNER_SELECTED_NO_RUNTIME_CHANGE',
            'activation-keyed',
            'one immutable v2 admission',
            'activation_consumption.single_operation: true',
            'authority_consumption.single_use: true',
            'One immutable admission avoids that partial dual-write state',
            'single activation scope eliminates lock-order inversion',
            'second authority, same activation',
            'Existing v1 admissions remain immutable historical evidence',
            'ABSENT_CONTRACTUALLY',
        ] as $decision) {
            self::assertNotFalse(stripos($inventory, $decision), $decision);
        }
    }

    public function testCurrentSourcesStillExhibitTheAuditedGap(): void
    {
        $root = dirname(__DIR__, 3);
        $contract = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
            .'GovernedProviderExecutionAdmissionContract.php',
        );
        $service = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
            .'GovernedProviderExecutionAdmissionService.php',
        );

        self::assertStringContainsString(
            "'authority_consumption', 'effect_start'",
            $contract,
        );
        self::assertStringNotContainsString(
            "'activation_consumption'",
            $contract,
        );
        self::assertStringContainsString(
            "'governed-provider-execution-admission:'.\$authorityId",
            $service,
        );
        self::assertStringNotContainsString(
            "'governed-provider-execution-admission:'.\$activation",
            $service,
        );
    }

    public function testPreparationAuthorizesContractDefinitionOnlyNext(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-activation-consumption-remediation-preparation-batch-0-complete.md',
            ),
        );
        $selection = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/next-campaign-provider-activation-consumption-remediation.md',
            ),
        );

        foreach ([
            'CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY',
            'Only remediation Batch 1 may next be considered',
            'contract definition',
            'No producer or consumer change is authorized',
            'No runtime contract was defined',
            'runtime behavior is unchanged',
            'No activation, principal or binding was activated or consumed',
            'no authority was issued or consumed',
            'no credential or capability was handled',
            'no provider was invoked',
            'no external I/O occurred',
            'Iron Gate',
            'Lazaretto',
            'four batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff.$selection, $boundary), $boundary);
        }
    }
}
