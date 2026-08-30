<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\ActivationCorridorDispositionInterruptionDemonstration;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationBatch4Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    { $this->root = sys_get_temp_dir().'/imperium-corridor-disposition-batch4-'.bin2hex(random_bytes(6)); mkdir($this->root, 0770, true); }

    protected function tearDown(): void
    { if (!is_dir($this->root)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testBothOutcomesAndAllCutsConvergeOfflineAndRefuseUnsafeReplay(): void
    {
        $result = (new ActivationCorridorDispositionInterruptionDemonstration($this->root))->run($this->root.'/evidence', new \DateTimeImmutable('2026-08-30T16:00:00+00:00'));
        self::assertCount(6, $result['evidence']);
        self::assertSame(ActivationCorridorDispositionInterruptionDemonstration::DISPOSITIONS, $result['summary']['dispositions']);
        self::assertSame(ActivationCorridorDispositionInterruptionDemonstration::CUTS, $result['summary']['cuts']);
        foreach (['exact_replay_proved', 'changed_evidence_refused', 'expiry_refusal_proved', 'revocation_refusal_proved', 'single_consumer_outcome_winner_proved', 'read_only_recovery_proved'] as $claim) self::assertTrue($result['summary'][$claim]);
        foreach (['activation_artifact_mutated', 'live_authority_issued', 'live_authority_consumed', 'live_disposition_sealed', 'external_action_performed'] as $nonAction) self::assertFalse($result['summary'][$nonAction]);
        self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', $result['summary']['continuing_custody_refusal']);
        foreach ($result['evidence'] as $case) {
            self::assertSame('CONVERGENT_RECOVERABLE', $case['classification']);
            foreach (['same_consumer_converged', 'exact_disposition_converged', 'changed_evidence_refused'] as $claim) self::assertTrue($case['retry'][$claim]);
            self::assertTrue($case['lifecycle']['expired_authority_refused']); self::assertTrue($case['lifecycle']['revoked_authority_refused']);
            self::assertTrue($case['contention']['single_consumer_outcome_winner']); self::assertTrue($case['reconstruction']['read_only']);
            self::assertSame($case['activation_artifact']['digest_before'], $case['activation_artifact']['digest_after']); self::assertFalse($case['activation_artifact']['mutated']);
            $expected = match ($case['cut']) { 'BEFORE_AUTHORITY_CONSUMPTION' => [false, false], 'AFTER_CONSUMPTION_BEFORE_DISPOSITION_COMMIT' => [true, false], 'AFTER_DISPOSITION_COMMIT' => [true, true] };
            self::assertSame(['authority_consumption_exists' => $expected[0], 'disposition_exists' => $expected[1]], $case['pre_cut_state']);
            self::assertSame(['authority_consumption_exists' => true, 'disposition_exists' => true], $case['post_restart_state']);
        }
        self::assertFileExists($result['private_evidence_file']); self::assertFileExists($result['sanitized_summary_file']);
    }

    public function testDocumentationStopsBeforeTheGatedProducer(): void
    {
        $root = dirname(__DIR__, 3);
        $proof = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-binding-activation-corridor-disposition-interruption-evidence.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-batch-4-complete.md'));
        foreach (['BEFORE_AUTHORITY_CONSUMPTION', 'AFTER_CONSUMPTION_BEFORE_DISPOSITION_COMMIT', 'AFTER_DISPOSITION_COMMIT', 'changed-evidence refusal', 'expiry and revocation refusal', 'one consumer/outcome winner', 'read-only recovery', 'no activation artifact mutation'] as $claim) self::assertNotFalse(stripos($proof, $claim), $claim);
        foreach (['Batch 5 is not authorized', 'instance-specific active principal evidence', 'explicit caller authority', 'may not issue or consume live authority', 'seal a live disposition', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
