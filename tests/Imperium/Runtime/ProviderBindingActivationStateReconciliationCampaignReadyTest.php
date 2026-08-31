<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationStateReconciliationCampaignReadyTest extends TestCase
{
    public function testPreparationIsSelectedWithoutRuntimeAuthority(): void
    {
        $next = $this->document(
            'docs/next-campaign-provider-binding-activation-state-reconciliation.md',
        );
        $ready = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-campaign-ready.md',
        );

        foreach ([
            'operation-scoped activation evidence versus durable implementation-binding state',
            'exact meaning and owner of BOUND_INACTIVE and BOUND_ACTIVE',
            'active executor-principal generation',
            'competent activation and revocation authorities',
            'credential and process-local capability non-authority',
            'candidate boundary postures',
            'UNKNOWN_REPLAY_PROHIBITED remains binding',
        ] as $finding) {
            self::assertNotFalse(stripos($next, $finding), $finding);
        }

        foreach ([
            'CAMPAIGN_READY_PREPARATION_BATCH_0_ONLY',
            'every required source it names',
            'Begin Provider Binding Activation State Reconciliation Preparation Batch 0 only',
            'Do not define a runtime contract',
            'activate a provider binding',
            'handle or resolve a credential or capability',
            'invoke a provider',
            'perform external I/O',
            'Iron Gate',
            'Lazaretto',
            'provider binding remains BOUND_INACTIVE',
        ] as $boundary) {
            self::assertNotFalse(stripos($ready, $boundary), $boundary);
        }
    }

    public function testMissionFlowNamesTheClosedCampaignAndNextPreparationOnly(): void
    {
        $flow = $this->document('docs/delegate-mission-flow.md');

        foreach ([
            'Provider Effect Principal and Binding Activation Resumption campaign is complete',
            'No resumption batch remains',
            'Provider Binding Activation State Reconciliation',
            'Preparation Batch 0 only',
            'No provider-binding activation is authorized',
        ] as $entry) {
            self::assertNotFalse(stripos($flow, $entry), $entry);
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
