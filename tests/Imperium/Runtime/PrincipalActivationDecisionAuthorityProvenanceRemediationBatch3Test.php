<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\PrincipalActivationDecisionAuthorityProvenanceRemediationInterruptionDemonstration as Demo;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch3Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-pad-batch3-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testEveryFixturePathAndCutProvesConvergenceAndRefusal(): void
    {
        $result = (new Demo(dirname(__DIR__, 3)))->run(
            $this->root,
            new \DateTimeImmutable('2026-08-30T13:00:00+00:00'),
        );

        self::assertCount(6, $result['evidence']);
        foreach ($result['evidence'] as $case) {
            self::assertTrue($case['retry']['exact_replay_converged']);
            self::assertTrue($case['retry']['same_root_contenders_converged']);
            self::assertTrue($case['retry']['changed_evidence_refused']);
            self::assertTrue($case['expiry']['refused']);
            self::assertTrue($case['revocation']['refused']);
            self::assertTrue($case['contention']['one_immutable_winner']);
            self::assertTrue($case['contention']['changed_contender_refused']);
            self::assertTrue($case['recovery']['read_only']);
            self::assertFalse($case['recovery']['repair_performed']);
            self::assertFalse($case['authority_issued_or_consumed']);
            self::assertFalse($case['principal_or_binding_activated']);
            self::assertFalse($case['credential_or_capability_handled']);
            self::assertFalse($case['provider_invoked']);
            self::assertFalse($case['external_action_performed']);
        }
    }

    public function testCutStatesAreExactAndSanitizedSummaryIsWritten(): void
    {
        $result = (new Demo(dirname(__DIR__, 3)))->run(
            $this->root,
            new \DateTimeImmutable('2026-08-30T13:01:00+00:00'),
        );

        foreach ($result['evidence'] as $case) {
            self::assertSame(
                'AFTER_FIXTURE_COMMIT' === $case['cut'],
                $case['pre_cut_state']['fixture_exists'],
            );
        }
        self::assertFileExists($result['private_evidence_file']);
        self::assertFileExists($result['sanitized_summary_file']);
        self::assertSame('PROVED_OFFLINE', $result['summary']['disposition']);
        self::assertFalse($result['summary']['authority_issued_or_consumed']);
    }

    public function testProofSourceContainsNoAuthorityConsumptionOrRuntimeEffectPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Evidence/'
                .'PrincipalActivationDecisionAuthorityProvenanceRemediationInterruptionDemonstration.php',
        );

        foreach ([
            'AuthorityConsumptionStore',
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'ProviderExecutorPrincipalActivationService',
            'public function issue',
            'public function consume',
            'public function activate',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesReadOnlyReconstructionOnly(): void
    {
        $doc = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-interruption-evidence.md',
        );
        $handoff = $this->document(
            'docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-3-complete.md',
        );

        foreach ([
            'BATCH_3_OFFLINE_INTERRUPTION_REPLAY_AND_CONTENTION_PROOF_COMPLETE',
            'scope grant',
            'scope successor',
            'decision-issuance authorization',
            'absent before commit',
            'exact replay',
            'changed evidence',
            'expiry and revocation',
            'same-root contention',
            'read-only recovery',
        ] as $claim) {
            self::assertNotFalse(stripos($doc, $claim), $claim);
        }
        foreach ([
            'Only remediation Batch 4 may next be considered',
            'read-only aggregate reconstruction',
            'ELIGIBLE',
            'INCOMPLETE',
            'CONFLICTED',
            'REFUSED',
            'may not issue or consume authority',
            'Iron Gate',
            'Lazaretto',
            'approximately four batches',
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
