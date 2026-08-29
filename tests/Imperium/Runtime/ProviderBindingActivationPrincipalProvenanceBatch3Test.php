<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\ImperatorPrincipalProvenanceInterruptionDemonstration;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationPrincipalProvenanceBatch3Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-principal-provenance-batch3-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->root);
    }

    public function testAllTransitionCutsConvergeOfflineAndRefuseReplayExpiryAndContention(): void
    {
        $result = (new ImperatorPrincipalProvenanceInterruptionDemonstration($this->root))->run($this->root.'/evidence', new \DateTimeImmutable('2026-08-29T23:40:00+00:00'));
        self::assertCount(24, $result['evidence']);
        self::assertSame(ImperatorPrincipalProvenanceInterruptionDemonstration::TRANSITIONS, $result['summary']['transitions']);
        self::assertSame(ImperatorPrincipalProvenanceInterruptionDemonstration::CUTS, $result['summary']['cuts']);
        self::assertTrue($result['summary']['same_consumer_convergence_proved']);
        self::assertTrue($result['summary']['exact_replay_proved']);
        self::assertTrue($result['summary']['conflicting_replay_refused']);
        self::assertTrue($result['summary']['expired_authority_refused']);
        self::assertTrue($result['summary']['single_winner_contention_proved']);
        self::assertTrue($result['summary']['read_only_reconstruction_proved']);
        self::assertFalse($result['summary']['live_authority_consumed']);
        self::assertFalse($result['summary']['live_principal_created']);
        self::assertFalse($result['summary']['external_action_performed']);
        foreach ($result['evidence'] as $case) {
            self::assertSame('CONVERGENT_RECOVERABLE', $case['classification']);
            self::assertTrue($case['retry']['same_consumer_converged']);
            self::assertTrue($case['retry']['exact_target_converged']);
            self::assertTrue($case['retry']['conflicting_replay_refused']);
            self::assertTrue($case['expiry']['expired_authority_refused']);
            self::assertTrue($case['contention']['single_winner']);
            self::assertTrue($case['reconstruction']['read_only']);
            self::assertFalse($case['live_authority_consumed']);
            self::assertFalse($case['live_principal_created']);
            self::assertFalse($case['external_action_performed']);
            $expected = match ($case['cut']) {
                'BEFORE_AUTHORITY_CONSUMPTION' => [false, false],
                'AFTER_AUTHORITY_CONSUMPTION_BEFORE_TARGET_COMMIT' => [true, false],
                'AFTER_TARGET_COMMIT' => [true, true],
            };
            self::assertSame(['authority_consumption_exists' => $expected[0], 'target_exists' => $expected[1]], $case['pre_cut_state']);
            self::assertSame(['authority_consumption_exists' => true, 'target_exists' => true], $case['post_restart_state']);
        }
        self::assertFileExists($result['private_evidence_file']);
        self::assertFileExists($result['sanitized_summary_file']);
    }

    public function testDocumentationAuthorizesOnlyFutureInstanceProductionNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-batch-3-complete.md');
        foreach (['Only Batch 4 is authorized', 'future-instance root-establishment producer', 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'generation-one', 'PENDING_ACTIVATION', 'without reopening', 'may not implement existing-instance remediation', 'issue caller authority', 'current-state index', 'reconsider corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
