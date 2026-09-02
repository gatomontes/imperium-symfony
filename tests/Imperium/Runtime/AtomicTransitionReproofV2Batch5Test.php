<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\ReproofV2\Contract;
use App\ReproofV2\Records;
use PHPUnit\Framework\TestCase;

/** Public candidate/documentary checks only; never selects a private receipt or runs a mission. */
final class AtomicTransitionReproofV2Batch5Test extends TestCase
{
    public function testPublicCandidateMatchesApprovedEventAndRemainsUnverified(): void
    {
        $root = dirname(__DIR__, 3);
        $candidate = json_decode(file_get_contents($root.'/docs/evidence/atomic-transition-reproof-v2-proof-2-candidate.json'), true, flags: JSON_THROW_ON_ERROR);
        $request = json_decode(file_get_contents($root.'/docs/atomic-transition-reproof-v2-execution-request.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(Contract::FIELDS['candidate'], array_keys($candidate));
        self::assertSame(Contract::SCHEMAS['candidate'], $candidate['schema']);
        self::assertSame($candidate, Records::seal($candidate));
        foreach (['proof_id', 'source_commit', 'source_manifest_root'] as $field) { self::assertSame($request[$field], $candidate[$field]); }
        self::assertSame('CANDIDATE_NOT_VERIFIED', $candidate['disposition']);
        self::assertSame(Contract::RETENTION, $candidate['retention']);
        self::assertSame('cc86f24082e3d254e6802e8d81f675e334f4577790f92997d088b4c0c64fb3ab', $candidate['record_digest']);
        foreach (['origin_digest', 'receipt_digest', 'input_root', 'expected_root', 'observed_root'] as $field) {
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $candidate[$field]);
        }
        foreach (['receipt', 'cases', 'source', 'graph', 'signature', 'public_key', 'private_locator'] as $field) { self::assertArrayNotHasKey($field, $candidate); }
    }

    public function testOperatorApprovalAndCompletionDoNotAuthorizeBatch6OrClosure(): void
    {
        $root = dirname(__DIR__, 3);
        $approval = file_get_contents($root.'/docs/atomic-transition-reproof-v2-batch-5-operator-approval.md');
        self::assertStringContainsString('batch 5 approved', $approval);
        self::assertStringContainsString('b3595f520434d4db6ce035910795fd20c96dded4456cb3a719bf166a624de4de', $approval);
        $handoff = file_get_contents($root.'/docs/handoffs/atomic-transition-reproof-v2-batch-5-complete.md');
        foreach (['REPROOF_BATCH_5_LOCAL_EXECUTION_COMPLETE_CANDIDATE_NOT_VERIFIED', 'ran once and exited 0',
            'not independent semantic verification', 'Three stages remain', 'V1 remains',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
