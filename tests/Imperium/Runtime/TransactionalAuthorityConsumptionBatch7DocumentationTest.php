<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch7DocumentationTest extends TestCase
{
    public function testOnlyTheThreeExactModelBoundProfileOpeningConsumersAdoptTheSharedTransition(): void
    {
        $root = dirname(__DIR__, 3);
        $adopted = [
            'ModelBoundProfileExaminationTestimonyOpeningService',
            'ModelBoundProfileFindingAuthorityOpeningService',
            'ModelBoundProfileDeliberationOpeningService',
        ];
        foreach ($adopted as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/'.$service.'.php');
            self::assertStringContainsString('ProfileSenateAuthorityTransition::run', $source, $service);
            self::assertStringContainsString('ProfileSenateAuthorityTransition::put', $source, $service);
            self::assertStringContainsString('ProfileSenateAuthorityTransition::isExactOrHistorical', $source, $service);
        }

        $excluded = [
            'ProfileExaminationTestimonyOpeningService',
            'ProfileExaminationDeliberationOpeningService',
            'ProfileExaminationDispositionAuthorityOpeningService',
            'ProfileExaminationQuestionAuthorshipService',
            'ProfileExaminationSenatorFindingService',
            'ProfileExaminationReconciliationService',
            'ProfileExaminationDispositionService',
            'ModelBoundProfileEvidenceQuestioningService',
            'ModelBoundProfileSenatorFindingService',
            'ModelBoundProfileReconciliationService',
            'ModelBoundProfileDispositionAuthorityOpeningService',
            'ModelBoundProfileDispositionService',
        ];
        foreach ($excluded as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/'.$service.'.php');
            self::assertStringNotContainsString('ProfileSenateAuthorityTransition', $source, $service);
        }
        foreach (['ProfileApprovalDecisionService', 'ModelBoundProfileApprovalDecisionService'] as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/'.$service.'.php');
            self::assertStringNotContainsString('ProfileSenateAuthorityTransition', $source, $service);
        }

        $transition = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/ProfileSenateAuthorityTransition.php');
        foreach (['TransactionalAuthorityConsumptionEnvelope::complete', 'ReplayFingerprint::of', "'profile-senate-authority:'", 'ImmutableRecordStore'] as $proof) {
            self::assertStringContainsString($proof, $transition);
        }

        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-7-complete.md');
        foreach (['exactly three', 'boolean', 'multi-write', 'commit timestamp', '`RECOVERY_INCOMPLETE`', 'Iron Gate', 'Lazaretto', 'sortie', 'Six estimated batches remain', 'Batch 8 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
