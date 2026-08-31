<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditResultContract;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionContract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch7TerminalAuditTest
    extends TestCase
{
    public function testCampaignLedgerRetainsRefusalsRepairsAndEveryClearThroughBatch6(): void
    {
        $ledger = $this->document('docs/deferred-local-test-ledger.md');

        foreach ([
            'Principal Activation Decision Authority Provenance Remediation Preparation Batch 0',
            'Source PR: #615',
            'Source PR: #616',
            'Source PR: #617',
            'Repair PR: #618',
            'Source PR: #619',
            'Repair PR: #620',
            'Source PR: #621',
            'Source PR: #622',
            'BATCH_5_PRODUCTION_REFUSED_SUCCESSOR_PRINCIPAL_AND_DECISION_LINEAGE_CONTRACTS_ABSENT',
            'Source PR: #623',
            'Repair PR: #624',
            'Source PR: #625',
            'Source PR: #626',
            'Source PR: #627',
            'CLEAR_OPERATOR_REPORTED',
            '## Pending None.',
        ] as $entry) {
            self::assertNotFalse(stripos($ledger, $entry), $entry);
        }
    }

    public function testCorrectedRouteSupersedesTheHistoricalBlockingAbsenceExactly(): void
    {
        $old = $this->document(
            'docs/provider-effect-principal-binding-activation-batch-2-terminal-audit.md',
        );
        $terminal = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-batch-7-terminal-audit.md',
        );

        self::assertNotFalse(stripos(
            $old,
            'BATCH_2_TERMINAL_AUDIT_REFUSED_UNPROVEN_DECISION_AUTHORITY_PROVENANCE',
        ));
        foreach ([
            'remains an accurate historical refusal',
            'blocking absence is superseded for the corrected v3 route',
            'canonical v3 Imperator principal',
            'read-only aggregate eligibility reconstruction',
            'one immutable combined',
            'read-only adversarial audit',
        ] as $finding) {
            self::assertNotFalse(stripos($terminal, $finding), $finding);
        }
    }

    public function testCanonicalContractsAndProductionPathNowExist(): void
    {
        self::assertSame(
            'imperium.imperator-runtime-principal/v3',
            ImperatorRuntimePrincipalVersionV3Contract::SCHEMA,
        );
        self::assertContains(
            'provider_executor_principal_activation_decision_authority',
            ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_AUTHORITY_SCOPE_FIELDS,
        );
        self::assertNotEmpty(
            ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::AUTHORIZATION_BOUND_FIELDS,
        );
        self::assertContains(
            'activation_decision',
            PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_FIELDS,
        );
        self::assertContains(
            'PASSED',
            PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditResultContract::CLASSIFICATIONS,
        );

        $producer = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'PrincipalActivationDecisionAuthorityProvenanceProductionService.php',
        );
        self::assertStringContainsString('AtomicTransition', $producer);
        self::assertStringContainsString('ImmutableRecordStore', $producer);
        self::assertStringContainsString("'activation_decision' => \$decision", $producer);
        self::assertStringContainsString("'consumed' => true", $producer);
        self::assertStringContainsString("'external_action_performed' => false", $producer);
    }

    public function testTerminalAuditDoesNotPretendTheLegacyActivationJoinIsComplete(): void
    {
        $terminal = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-batch-7-terminal-audit.md',
        );
        $service = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationService.php',
        );

        self::assertStringContainsString('array $decision', $service);
        foreach ([
            'does not silently retrofit',
            'still accepts a caller-supplied decision array',
            'must be opened separately and proved',
            'provider binding remains BOUND_INACTIVE',
            'Iron Gate and Lazaretto remain closed',
        ] as $boundary) {
            self::assertNotFalse(stripos($terminal, $boundary), $boundary);
        }
    }

    public function testHandoffAuthorizesPreparationOnlyAndNoRuntimeAction(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-preparation-ready.md',
        );

        foreach ([
            'Only Provider Effect Principal and Binding Activation Resumption Preparation Batch 0',
            'exact join',
            'canonical decision resolution',
            'activation-authority custody and consumption',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
            'may not alter either production transition',
            'issue or consume authority',
            'provider binding remains BOUND_INACTIVE',
            'Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
