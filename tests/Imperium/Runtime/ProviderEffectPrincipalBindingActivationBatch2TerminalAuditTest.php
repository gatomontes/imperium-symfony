<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;
use PHPUnit\Framework\TestCase;

final class ProviderEffectPrincipalBindingActivationBatch2TerminalAuditTest extends TestCase
{
    public function testAuditRefusesUnprovenDecisionAuthorityProvenance(): void
    {
        $audit = $this->document(
            'docs/provider-effect-principal-binding-activation-batch-2-terminal-audit.md',
        );

        foreach ([
            'BATCH_2_TERMINAL_AUDIT_REFUSED_UNPROVEN_DECISION_AUTHORITY_PROVENANCE',
            'PRINCIPAL_PRODUCTION_SUB_BOUNDARY_REFUSED_PENDING_DECISION_AUTHORITY_REMEDIATION',
            'Atomic consumption does not prove',
            'caller-supplied array',
            'syntactically valid reference',
            'does not resolve that reference',
            'Competent decision issuance and custody',
            'UNPROVED — blocking',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }
    }

    public function testContractAndServiceConfirmTheBlockingGap(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationService.php',
        );
        $validator = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationContractValidator.php',
        );

        self::assertSame(
            'future-imperator-provider-executor-principal-activation-decision',
            ProviderExecutorPrincipalActivationDecisionContract::PRODUCER_POSTURE,
        );
        self::assertStringContainsString(
            'public function activate(',
            $service,
        );
        self::assertStringContainsString(
            'array $decision',
            $service,
        );
        self::assertStringNotContainsString(
            'ProviderExecutorPrincipalActivationDecisionStore',
            $service,
        );
        self::assertStringNotContainsString(
            'ProviderExecutorPrincipalActivationDecisionIssuer',
            $service,
        );
        self::assertStringContainsString(
            "\$this->reference(\$decision['source_authority'] ?? null)",
            $validator,
        );
    }

    public function testAuditRetainsValidAtomicAndSafetyEvidence(): void
    {
        $audit = $this->document(
            'docs/provider-effect-principal-binding-activation-batch-2-terminal-audit.md',
        );

        foreach ([
            'Combined consumption-and-activation record',
            'Mechanically sound',
            'No consumption-only state',
            'Exact replay',
            'Same-root contention',
            'Fail closed',
            'Credential and capability exclusion',
            'Batch 1 result is retained',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }
    }

    public function testHandoffPausesBindingAndAllowsOnlyRemediationPreparation(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-batch-2-refused.md',
        );
        $ready = $this->document(
            'docs/handoffs/principal-activation-decision-authority-provenance-remediation-campaign-ready.md',
        );

        foreach ([
            'Provider Effect Principal and Binding Activation is paused',
            'Binding activation is not authorized',
            'Only Preparation Batch 0',
            'Selection grants no decision',
            'provider binding remains BOUND_INACTIVE',
            'Iron Gate and Lazaretto remain closed',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff.' '.$ready, $boundary), $boundary);
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
