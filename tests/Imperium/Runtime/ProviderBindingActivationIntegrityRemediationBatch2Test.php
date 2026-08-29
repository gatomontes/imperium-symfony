<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\ActivationTransitionInterruptionDemonstration;
use App\Imperium\Runtime\Imperator\ActivationTransitionInterruptionEvidenceContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch2Test extends TestCase
{
    private string $evidence;

    protected function setUp(): void
    {
        $this->evidence = sys_get_temp_dir().'/imperium-activation-interruption-test-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->evidence)) return;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->evidence, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->evidence);
    }

    public function testAllTransitionCutsConvergeOfflineAndRefuseExpiryAndConflict(): void
    {
        $result = (new ActivationTransitionInterruptionDemonstration(dirname(__DIR__, 3)))->run($this->evidence, new \DateTimeImmutable('2026-08-29T18:00:00+00:00'));
        self::assertFileExists($result['private_evidence_file']);
        self::assertFileExists($result['sanitized_summary_file']);
        self::assertCount(6, $result['evidence']);
        self::assertSame(ActivationTransitionInterruptionEvidenceContract::TRANSITIONS, array_values(array_unique(array_column($result['evidence'], 'transition'))));
        foreach ($result['evidence'] as $evidence) {
            self::assertContains($evidence['cut'], ActivationTransitionInterruptionEvidenceContract::CUTS);
            self::assertSame('CONVERGENT_RECOVERABLE', $evidence['classification']);
            self::assertTrue($evidence['retry']['same_consumer_converged']);
            self::assertTrue($evidence['retry']['exact_target_converged']);
            self::assertTrue($evidence['retry']['conflicting_replay_refused']);
            self::assertTrue($evidence['expiry']['expired_authority_refused']);
            self::assertTrue($evidence['contention']['different_consumer_refused']);
            self::assertFalse($evidence['external_action_performed']);
        }
        self::assertTrue($result['summary']['same_consumer_convergence_proved']);
        self::assertFalse($result['summary']['live_authority_consumed']);
        self::assertFalse($result['summary']['live_target_created']);
    }

    public function testDocumentationAuthorizesOnlyStrandedArtifactDispositionNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-integrity-remediation-batch-2-complete.md');
        foreach (['Only Batch 3 is authorized', 'stranded-artifact disposition', 'may not mutate', 'principal provenance', 'credential-reference handling', 'process-loss custody', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
