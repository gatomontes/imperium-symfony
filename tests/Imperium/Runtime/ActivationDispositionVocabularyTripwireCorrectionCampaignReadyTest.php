<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ActivationDispositionVocabularyTripwireCorrectionCampaignReadyTest extends TestCase
{
    public function testCampaignNamesTheMaterialEscapeAndNarrowCorrection(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents(
            $root.'/docs/next-campaign-activation-disposition-vocabulary-tripwire-correction.md',
        );

        foreach ([
            'ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_SELECTED',
            'single-quoted PHP string literals',
            'Double-quoted',
            'demonstrated escape',
            'comments containing a governed token do not become producers',
            'BATCH_1_COMPLETE_QUOTE_INDEPENDENT_VOCABULARY_TRIPWIRE_PROVED',
            'Runtime source under `src/Imperium/Runtime` is outside this campaign',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $campaign, $boundary);
        }
    }

    public function testPreparationBatchZeroOnlyIsAuthorized(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents(
            $root.'/docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-ready.md',
        );

        foreach ([
            'ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_CAMPAIGN_READY',
            'Only Preparation Batch 0 may next be considered',
            'Do not repair the detector in Preparation Batch 0',
            'Batch 2 must remain separately sequenced after Batch 1 merge',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
    }

    public function testLocalPreparationHandoffPinsScopeAndCommands(): void
    {
        $handoff = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/docs/handoffs/'
            .'activation-disposition-vocabulary-tripwire-correction-preparation-batch-0-local-ready.md',
        );

        foreach ([
            'git pull --ff-only origin main',
            'PREPARATION_BATCH_0_COMPLETE_VOCABULARY_DETECTOR_SEMANTICS_CLASSIFIED',
            'Only Preparation Batch 0 may be performed',
            'Do not repair the detector',
            'New-chat prompt',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
    }

    public function testCampaignRequalifiesRatherThanSilentlyErasesTheRejectedClosure(): void
    {
        $root = dirname(__DIR__, 3);
        $documents = (string) file_get_contents(
            $root.'/docs/next-campaign-activation-disposition-vocabulary-tripwire-correction.md',
        ).(string) file_get_contents(
            $root.'/docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-ready.md',
        );

        self::assertStringContainsString(
            'FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CLOSURE_REJECTED_WITH_MATERIAL_VOCABULARY_TRIPWIRE_GAP',
            $documents,
        );
        self::assertStringNotContainsString(
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT` is removed',
            $documents,
        );
    }
}
