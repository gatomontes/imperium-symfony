<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionEffectReadinessBatch5TerminalAuditTest extends TestCase
{
    public function testAuditClosesOnlyTheDocumentaryAssuranceSubBoundary(): void
    {
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-assurance-terminal-audit.md',
        );

        foreach ([
            'BATCH_5_DOCUMENTARY_ASSURANCE_SUB_BOUNDARY_CLOSED',
            'DOCUMENTARY_ASSURANCE_SUB_BOUNDARY_CLOSED',
            'does not select REFUSED_PENDING_STERILE_CONFORMANCE',
            'in-progress duplicate semantics',
            'query by idempotency key before retry',
            'completion time when no response is observed',
            'remote cryptographic authorship',
            'authenticated-channel trust remains the ceiling',
            'remote provider conformance Not proved',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }
    }

    public function testAuditPreservesUnknownReplayAndCompletionAnchoredRetention(): void
    {
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-assurance-terminal-audit.md',
        );
        $profile = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
                .'AgentMailDirectSendAssuranceProfileContract.php',
        );
        $admission = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
                .'ProviderAssuranceEvidenceAdmissionContract.php',
        );

        foreach ([
            'UNKNOWN_REPLAY_PROHIBITED',
            'provider completion',
            'Completed exact-duplicate documentation is evidence, not retry authority',
            'local effect start, timeout or process restart cannot establish that anchor',
        ] as $posture) {
            self::assertNotFalse(stripos($audit, $posture), $posture);
        }

        self::assertStringContainsString(
            "'completion_time_without_response'",
            $profile.$admission,
        );
        self::assertStringContainsString(
            "'remote_cryptographic_authorship'",
            $profile.$admission,
        );
        self::assertStringContainsString(
            "'authorizes_retry' => false",
            $profile.$admission,
        );
    }

    public function testAuditAndReconstructionRemainAuthorityEmptyAndReadOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-assurance-terminal-audit.md',
        );
        $reconstructor = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderAssuranceEvidenceAggregateReconstructor.php',
        );

        foreach ([
            'defines no live-call contract',
            'does not activate a principal or binding',
            'issue or consume execution authority',
            'handle or resolve a credential or capability',
            'invoke a provider',
            'perform external I/O',
            'authorize retry',
            'Iron Gate',
            'Lazaretto',
        ] as $boundary) {
            self::assertNotFalse(stripos($audit, $boundary), $boundary);
        }

        self::assertStringContainsString("'read_only' => true", $reconstructor);
        self::assertStringContainsString(
            "'execution_authority_created' => false",
            $reconstructor,
        );
        self::assertStringContainsString(
            "'retry_authority_created' => false",
            $reconstructor,
        );
        self::assertStringNotContainsString('AgentMailEmailTransport', $reconstructor);
    }

    public function testHandoffAllowsOnlyAuthorityEmptyPrincipalContractsNext(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-execution-effect-readiness-batch-5-complete.md',
        );

        foreach ([
            'BATCH_5_DOCUMENTARY_ASSURANCE_SUB_BOUNDARY_CLOSED',
            'Only Batch 6 may next be considered',
            'authority-empty contracts',
            'exact attested inert executor principal',
            'may not activate the principal',
            'provider binding remains inactive',
            'no live-call contract exists',
            'no provider effect is authorized',
            'approximately five batches',
        ] as $gate) {
            self::assertNotFalse(stripos($handoff, $gate), $gate);
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
