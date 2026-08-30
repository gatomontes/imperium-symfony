<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration as Demo;
use PHPUnit\Framework\TestCase;

final class CorridorDispositionPrincipalAuthorityRemediationBatch3Test extends TestCase
{
    private string $root;
    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-cpa-batch3-'.bin2hex(random_bytes(6)); }
    protected function tearDown(): void { if (!is_dir($this->root)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testAllTransitionsAndCutsProveOfflineRecoveryAndRefusal(): void
    {
        $result = (new Demo(dirname(__DIR__, 3)))->run($this->root, new \DateTimeImmutable('2026-08-30T13:00:00+00:00'));
        self::assertCount(12, $result['evidence']);
        foreach ($result['evidence'] as $case) {
            self::assertTrue($case['retry']['same_consumer_converged']); self::assertTrue($case['retry']['exact_target_converged']); self::assertTrue($case['retry']['changed_evidence_refused']);
            self::assertTrue($case['expiry']['refused']); self::assertTrue($case['revocation']['refused']); self::assertTrue($case['contention']['single_winner']); self::assertTrue($case['recovery']['read_only']);
            self::assertFalse($case['live_authority_issued_or_consumed']); self::assertFalse($case['live_principal_or_binding_activated']); self::assertFalse($case['activation_artifact_mutated']); self::assertFalse($case['external_action_performed']);
            self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', $case['continuing_custody_refusal']);
        }
    }

    public function testCutStatesAreExact(): void
    {
        $cases = (new Demo(dirname(__DIR__, 3)))->run($this->root, new \DateTimeImmutable('2026-08-30T13:01:00+00:00'))['evidence'];
        foreach ($cases as $case) {
            $expected = match ($case['cut']) { 'BEFORE_AUTHORITY_CONSUMPTION' => [false, false], 'AFTER_AUTHORITY_CONSUMPTION_BEFORE_TARGET_COMMIT' => [true, false], 'AFTER_TARGET_COMMIT' => [true, true] };
            self::assertSame($expected[0], $case['pre_cut_state']['authority_consumption_exists']); self::assertSame($expected[1], $case['pre_cut_state']['target_exists']);
        }
    }

    public function testDocumentationAuthorizesReadOnlyAggregateReconstructionOnly(): void
    {
        $root = dirname(__DIR__, 3); $doc = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/corridor-disposition-principal-authority-remediation-interruption-evidence.md')); $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/corridor-disposition-principal-authority-remediation-batch-3-complete.md'));
        foreach (['BATCH_3_OFFLINE_REPLAY_AND_INTERRUPTION_PROOF_COMPLETE', 'scope-grant issuance and consumption', 'successor commit', 'separate activation', 'caller-authority issuance', 'changed evidence', 'expiry and revocation', 'single-winner contention', 'read-only recovery'] as $claim) self::assertNotFalse(stripos($doc, $claim), $claim);
        foreach (['Only remediation Batch 4 is authorized', 'read-only aggregate reconstruction', 'ELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED', 'may not issue live authority', 'activate a principal', 'seal a disposition', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
