<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class DelegateRuntimeSourceFormattingTest extends TestCase
{
    private const EXPANDED_CRITICAL_CLASSES = [
        'Conscription/DelegateMissionOperationalManifestationAssemblyService.php',
        'Conscription/DelegateMissionOperationalManifestationSeatBindingService.php',
        'Conscription/DelegateMissionOperationalProfileQualificationService.php',
        'Conscription/ModelBoundOperationalManifestationAssemblyService.php',
        'Conscription/ModelBoundOperationalManifestationSeatBindingService.php',
        'Senate/ModelBoundProfileDispositionAuthorityOpeningService.php',
        'Conscription/DelegateMissionRuntimeActivationService.php',
        'Curia/DelegateMissionDeploymentAuthorizationService.php',
        'Curia/OperationalDeploymentAuthorizationService.php',
        'Garrison/DelegateMissionOperationalCustodyTransitionService.php',
        'Garrison/OperationalCustodyTransitionService.php',
        'Conscription/ModelBoundOperationalProfileQualificationService.php',
        'Senate/ModelBoundProfileDeliberationOpeningService.php',
        'Senate/ModelBoundProfileDispositionService.php',
        'Senate/ModelBoundProfileEvidenceQuestioningService.php',
        'Senate/ModelBoundProfileExaminationCommissionAcceptanceService.php',
        'Senate/ModelBoundProfileExaminationOpeningService.php',
        'Senate/ModelBoundProfileExaminationTestimonyOpeningService.php',
        'Senate/ModelBoundProfileFindingAuthorityOpeningService.php',
        'Senate/ModelBoundProfileReconciliationService.php',
        'Senate/ModelBoundProfileSenatorFindingService.php',
        'Clavium/DelegateMissionModelAccessAttestationService.php',
        'Clavium/DelegateMissionProviderInvocationActivationService.php',
        'Curia/DelegateMissionBoundedCognitionCommissionService.php',
        'Curia/DelegateMissionCognitionResultDispositionService.php',
        'Curia/DelegateMissionControlIntakeDispositionService.php',
        'Curia/DelegateMissionModelCriteriaRequestService.php',
        'Curia/DelegateMissionOracleCommissionIssuanceService.php',
        'Curia/DelegateMissionResourceInvocationReadinessAssessmentService.php',
        'Curia/DelegateMissionReturnAuthorizationService.php',
        'Imperator/DelegateMissionModelCriteriaDecisionService.php',
        'Imperator/DelegateMissionResourceInvocationDecisionService.php',
        'Citadel/DelegateMissionBoundedCognitionTurnService.php',
        'Garrison/DelegateMissionTerminalReturnService.php',
    ];

    public function testExpandedCriticalClassesRemainPhysicallyReadable(): void
    {
        $runtime = dirname(__DIR__, 3).'/src/Imperium/Runtime';

        foreach (self::EXPANDED_CRITICAL_CLASSES as $relative) {
            $lines = file($runtime.'/'.$relative, FILE_IGNORE_NEW_LINES);
            self::assertIsArray($lines);
            self::assertGreaterThan(40, count($lines), $relative.' has been recompressed.');

            $source = implode("\n", $lines);
            self::assertStringNotContainsString(
                'new\\',
                $source,
                $relative.' contains malformed namespace-qualified construction.',
            );
            self::assertDoesNotMatchRegularExpression(
                '/declare\\(strict_types=1\\);[ \\t]*namespace/',
                $source,
                $relative.' compresses the declaration and namespace onto one line.',
            );

            foreach ($lines as $number => $line) {
                self::assertLessThanOrEqual(
                    240,
                    strlen($line),
                    sprintf('%s:%d exceeds the bounded readability limit.', $relative, $number + 1),
                );
            }
        }
    }
}
