<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch10TerminalAuditTest extends TestCase
{
    public function testAuditNamesTheUnconsumedSingleOperationActivation(): void
    {
        $audit = preg_replace(
            '/\\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/provider-execution-boundary-redesign-terminal-audit.md',
            ),
        );

        foreach ([
            'BATCH_10_TERMINAL_AUDIT_REFUSED_ACTIVATION_NOT_CONSUMED',
            'ACTIVATED_UNCONSUMED',
            'single_operation: true',
            'activation-keyed consumption record',
            'Authority-level single use',
            'does not prove activation-level single use',
            'TRUSTED_WRITER_CANONICAL_INTEGRITY',
            'No live adoption may proceed',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }
    }

    public function testRuntimeSourcesConfirmActivationIsValidatedButNotConsumed(): void
    {
        $root = dirname(__DIR__, 3);
        $authorityIssuer = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/'
            .'DurableProviderExecutionAuthorityIssuanceService.php',
        );
        $admission = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
            .'GovernedProviderExecutionAdmissionService.php',
        );

        self::assertStringContainsString(
            "'ACTIVATED_UNCONSUMED' !== (\$activation['status'] ?? null)",
            $authorityIssuer,
        );
        self::assertStringContainsString(
            "'ACTIVATED_UNCONSUMED' !== (\$activation['status'] ?? null)",
            $admission,
        );
        self::assertStringContainsString(
            "'authority_id' => \$authorityId",
            $admission,
        );
        self::assertStringNotContainsString(
            'activation_consumption',
            $authorityIssuer.$admission,
        );
        self::assertStringNotContainsString(
            'provider-binding-activation-consumption:',
            $authorityIssuer.$admission,
        );
        self::assertStringNotContainsString(
            'SingleOperationProviderBindingActivationConsumption',
            $authorityIssuer.$admission,
        );
    }

    public function testAuditPreservesNarrowerEvidenceWithoutClosingCampaign(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/provider-execution-boundary-redesign-terminal-audit.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-execution-boundary-redesign-batch-10-terminal-audit-complete.md',
            ),
        );

        foreach ([
            'does not close Provider Execution Boundary Redesign',
            'same-process stationary possession',
            'durable authority identity',
            'secret-free local resolution',
            'Provider Execution Assurance resumption',
            'UNKNOWN_REPLAY_PROHIBITED',
            'No runtime remediation is authorized',
            'No principal or binding was activated',
            'no activation or authority was consumed',
            'no provider was invoked',
            'no external I/O occurred',
            'Iron Gate',
            'Lazaretto',
            'countdown is suspended',
        ] as $boundary) {
            self::assertNotFalse(stripos($audit.$handoff, $boundary), $boundary);
        }
    }

    public function testEveryCompletedBatchHandoffRemainsPresent(): void
    {
        $root = dirname(__DIR__, 3).'/docs/handoffs';
        self::assertFileExists(
            $root.'/provider-execution-boundary-redesign-preparation-batch-0-complete.md',
        );
        for ($batch = 1; $batch <= 9; ++$batch) {
            self::assertFileExists(
                $root.'/provider-execution-boundary-redesign-batch-'
                .$batch.'-complete.md',
            );
        }
    }
}
