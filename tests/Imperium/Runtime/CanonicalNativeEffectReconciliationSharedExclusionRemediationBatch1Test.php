<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeStateSharedExclusionContract;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationBatch1Test extends TestCase
{
    public function testCanonicalOrderIsSharedBeforeTargetBeforePublication(): void
    {
        self::assertSame('native-provider-transition', NativeStateSharedExclusionContract::OUTER_SCOPE);
        self::assertSame([
            'native_shared_exclusion',
            'semantic_target_lock',
            'immutable_publication_lock',
        ], NativeStateSharedExclusionContract::PERMITTED_ORDER);
        self::assertContains('semantic_target_to_native_shared_exclusion', NativeStateSharedExclusionContract::PROHIBITED_NESTING);
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['currentness_and_use_share_one_exclusion']);
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['changed_input_conflicts']);
        self::assertFalse(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['distributed_or_hostile_writer_exclusion_proved']);
    }

    public function testSharedExclusionIsDistinctFromSemanticWinnerSerialization(): void
    {
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['shared_exclusion_is_not_target_serialization']);
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['shared_lock_precedes_target_lock']);
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['reverse_acquisition_prohibited']);
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['locks_are_non_reentrant']);
    }

    public function testInterruptionLawAllowsOnlyExactCompletion(): void
    {
        self::assertSame('before_currentness_no_output', NativeStateSharedExclusionContract::INTERRUPTION_CUTS[0]);
        self::assertSame('after_evidence_publication_established_result', NativeStateSharedExclusionContract::INTERRUPTION_CUTS[4]);
        self::assertTrue(NativeStateSharedExclusionContract::REQUIRED_INVARIANTS['exact_retry_may_finish_only_same_publication']);
    }
}
