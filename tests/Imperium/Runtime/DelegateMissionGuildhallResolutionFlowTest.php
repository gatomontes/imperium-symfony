<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\DelegateMissionProfileDerivationCommissionRequestService;
use App\Imperium\Runtime\Conscription\DelegateMissionProfileCandidateIntakeDispositionService;
use App\Imperium\Runtime\Conscription\DelegateMissionExaminationPreparationHandoffService;
use App\Imperium\Runtime\Conscription\DelegateMissionExaminationManifestationAssemblyService;
use App\Imperium\Runtime\Conscription\DelegateMissionOperationalProfileQualificationService;
use App\Imperium\Runtime\Conscription\DelegateMissionOperationalManifestationAssemblyService;
use App\Imperium\Runtime\Conscription\DelegateMissionOperationalManifestationSeatBindingService;
use App\Imperium\Runtime\Conscription\DelegateMissionRuntimeActivationService;
use App\Imperium\Runtime\Conscription\DelegateMissionModelBindingSealingService;
use App\Imperium\Runtime\Clavium\DelegateMissionModelAccessAttestationService;
use App\Imperium\Runtime\Clavium\DelegateMissionProviderInvocationActivationService;
use App\Imperium\Runtime\Citadel\DelegateMissionBoundedCognitionTurnService;
use App\Imperium\Runtime\Citadel\DelegateMissionCognitionGateway;
use App\Imperium\Runtime\Curia\DelegateMissionProfileScopeAuthorizationRequestService;
use App\Imperium\Runtime\Curia\DelegateMissionPersonnelUseRequestService;
use App\Imperium\Runtime\Curia\DelegateMissionDeploymentAuthorizationService;
use App\Imperium\Runtime\Curia\DelegateMissionControlIntakeDispositionService;
use App\Imperium\Runtime\Curia\DelegateMissionBoundedCognitionCommissionService;
use App\Imperium\Runtime\Curia\DelegateMissionResourceInvocationReadinessAssessmentService;
use App\Imperium\Runtime\Curia\DelegateMissionModelCriteriaRequestService;
use App\Imperium\Runtime\Curia\DelegateMissionOracleCommissionIssuanceService;
use App\Imperium\Runtime\Curia\DelegateMissionModelSelectionDecisionService;
use App\Imperium\Runtime\Guildhall\DelegateMissionCapabilityDemandIntakeService;
use App\Imperium\Runtime\Guildhall\DelegateMissionPersonnelUseAcceptanceService;
use App\Imperium\Runtime\Guildhall\DelegateMissionPersonnelResolutionService;
use App\Imperium\Runtime\Garrison\DelegateMissionPersonaReservationDispositionService;
use App\Imperium\Runtime\Garrison\DelegateMissionOperationalCustodyTransitionService;
use App\Imperium\Runtime\Imperator\DelegateMissionProfileScopeDecisionService;
use App\Imperium\Runtime\Imperator\DelegateMissionPersonnelUseDecisionService;
use App\Imperium\Runtime\Imperator\DelegateMissionProfileApprovalDecisionService;
use App\Imperium\Runtime\Imperator\DelegateMissionModelCriteriaDecisionService;
use App\Imperium\Runtime\Imperator\DelegateMissionResourceInvocationDecisionService;
use App\Imperium\Runtime\Laboratorium\DelegateMissionProfileDerivationCommissionDispositionService;
use App\Imperium\Runtime\Laboratorium\DelegateMissionProfileCandidateDerivationReturnService;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationCognitionGateway;
use App\Imperium\Runtime\Oracle\ModelRequirementCommissionAcceptanceService;
use App\Imperium\Runtime\Oracle\ModelEvaluationCaseOpeningService;
use App\Imperium\Runtime\Oracle\ModelEligibilityFindingService;
use App\Imperium\Runtime\Oracle\ModelComparativeAssessmentService;
use App\Imperium\Runtime\Oracle\ModelRecommendationService;
use App\Imperium\Runtime\Senate\DelegateMissionExaminationPreparationIntakeDispositionService;
use App\Imperium\Runtime\Senate\DelegateMissionExaminationStandAdmissionDispositionService;
use App\Imperium\Runtime\Senate\DelegateMissionFirstQuestionCommissionDispositionService;
use App\Imperium\Runtime\Senate\DelegateMissionFirstQuestionCommissionIssuanceService;
use App\Imperium\Runtime\Senate\DelegateMissionProfileExaminationOpeningService;
use App\Imperium\Runtime\Senate\DelegateMissionSecurityQuestionCommissionIssuanceService;
use App\Imperium\Runtime\Senate\DelegateMissionSecurityQuestionCommissionDispositionService;
use App\Imperium\Runtime\Senate\DelegateMissionSecurityQuestionAuthorshipService;
use App\Imperium\Runtime\Senate\DelegateMissionSecurityQuestionDispatchAuthorizationService;
use App\Imperium\Runtime\Senate\DelegateMissionSecurityQuestionDispatchService;
use App\Imperium\Runtime\Senate\DelegateMissionSecurityTestimonyResponseService;
use App\Imperium\Runtime\Senate\DelegateMissionUsabilityQuestionCommissionIssuanceService;
use App\Imperium\Runtime\Senate\DelegateMissionUsabilityQuestionCommissionDispositionService;
use App\Imperium\Runtime\Senate\DelegateMissionUsabilityQuestionAuthorshipService;
use App\Imperium\Runtime\Senate\DelegateMissionUsabilityQuestionDispatchAuthorizationService;
use App\Imperium\Runtime\Senate\DelegateMissionUsabilityQuestionDispatchService;
use App\Imperium\Runtime\Senate\DelegateMissionUsabilityTestimonyResponseService;
use App\Imperium\Runtime\Senate\DelegateMissionFindingAuthorityOpeningService;
use App\Imperium\Runtime\Senate\DelegateMissionSenatorFindingService;
use App\Imperium\Runtime\Senate\DelegateMissionDeliberationOpeningService;
use App\Imperium\Runtime\Senate\DelegateMissionFindingReconciliationService;
use App\Imperium\Runtime\Senate\DelegateMissionDispositionAuthorityOpeningService;
use App\Imperium\Runtime\Senate\DelegateMissionSenateDispositionService;
use App\Imperium\Runtime\Senate\DelegateMissionTrustQuestionAuthorshipService;
use App\Imperium\Runtime\Senate\DelegateMissionTrustQuestionDispatchAuthorizationService;
use App\Imperium\Runtime\Senate\DelegateMissionTrustQuestionDispatchService;
use App\Imperium\Runtime\Senate\DelegateMissionTrustTestimonyResponseService;
use App\Imperium\Runtime\Senate\ProfileExaminationQuestionCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationFindingCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationReconciliationCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationDispositionCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationTestimonyCognitionGateway;
use PHPUnit\Framework\TestCase;

final class DelegateMissionGuildhallResolutionFlowTest extends TestCase
{
    public function testGuildmasterAcceptsExactDemandWithoutYetResolvingPersonnel(): void
    {
        [$root, $demandId, $bindingId] = $this->fixtures();
        try {
            $service = new DelegateMissionCapabilityDemandIntakeService($root);
            $intake = $service->decide($demandId, $bindingId, 'ACCEPTED', 'Guildhall accepts the exact functional demand.', new \DateTimeImmutable('2026-08-24T02:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_CAPABILITY_DEMAND_ACCEPTED_PENDING_PROFESSION_AND_PERSONA_SUITABILITY_RESOLUTION', $intake['status']);
            self::assertSame('LEGATE', $intake['actor']['officer_class']);
            self::assertSame('DELEGATE', $intake['officer_class']);
            self::assertTrue($intake['demand_accepted']);
            self::assertTrue($intake['personnel_resolution_authority']['authority_exercisable']);
            self::assertFalse($intake['personnel_resolution_authority']['consumed']);
            foreach (['profession_determined', 'persona_selected', 'persona_suitability_determined', 'personnel_use_authority', 'reservation_authority', 'retrieval_authority', 'custody_transfer_authority', 'profile_derivation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($intake[$field], $field.' must remain false');
            }
            self::assertSame($intake, $service->decide($demandId, $bindingId, 'ACCEPTED', 'Guildhall accepts the exact functional demand.', new \DateTimeImmutable('2026-08-24T03:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRefusalClosesExactDemandWithoutResolutionAuthority(): void
    {
        [$root, $demandId, $bindingId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'REFUSED', 'The demand is outside Guildhall competence as sealed.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_CAPABILITY_DEMAND_REFUSED_NO_PERSONNEL_AUTHORITY', $intake['status']);
            self::assertTrue($intake['demand_refused']);
            self::assertNull($intake['personnel_resolution_authority']);
            self::assertFalse($intake['personnel_use_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testAcceptedDemandResolvesProfessionAndExactSuitablePersonaAgainstGarrisonFacts(): void
    {
        [$root, $demandId, $bindingId, $responseId, $custodyId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Guildhall accepts the exact functional demand.', new \DateTimeImmutable());
            $service = new DelegateMissionPersonnelResolutionService($root);
            $resolution = $service->resolve(
                $intake['disposition_id'],
                $bindingId,
                $responseId,
                'Passive web application security assessor',
                $custodyId,
                'SUITABLE',
                ['Passive assessment discipline', 'Evidence-bound reporting'],
                ['garrison-custody-fact', 'admitted-persona-qualification-record'],
                'The exact available admitted Persona satisfies the profession and bounded mission criteria.',
                new \DateTimeImmutable('2026-08-24T02:30:00+00:00'),
            );

            self::assertSame('DELEGATE_MISSION_PROFESSION_AND_PERSONA_SUITABILITY_RESOLVED_PENDING_PERSONNEL_USE_REQUEST', $resolution['status']);
            self::assertSame('DELEGATE', $resolution['officer_class']);
            self::assertSame('Passive web application security assessor', $resolution['profession']);
            self::assertSame(['Analyze public behavior'], $resolution['capability_correlation']['capability_requirements']);
            self::assertSame('mission.delegate.passive-assessment', $resolution['capability_correlation']['mission_seat']);
            self::assertSame($custodyId, $resolution['persona']['custody_id']);
            self::assertSame('persona-passive-assessor', $resolution['persona']['persona_id']);
            self::assertTrue($resolution['profession_determined']);
            self::assertTrue($resolution['persona_suitability_determined']);
            self::assertTrue($resolution['persona_suitable']);
            self::assertTrue($resolution['personnel_resolution_authority']['consumed']);
            self::assertTrue($resolution['personnel_use_request_authority']['authority_exercisable']);
            self::assertSame('curia.seneschal', $resolution['personnel_use_request_authority']['recipient']);
            foreach (['personnel_use_authority', 'reservation_authority', 'retrieval_authority', 'custody_transfer_authority', 'profile_derivation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($resolution[$field], $field.' must remain false');
            }
            self::assertSame($resolution, $service->resolve($intake['disposition_id'], $bindingId, $responseId, 'Passive web application security assessor', $custodyId, 'SUITABLE', ['Passive assessment discipline', 'Evidence-bound reporting'], ['garrison-custody-fact', 'admitted-persona-qualification-record'], 'The exact available admitted Persona satisfies the profession and bounded mission criteria.', new \DateTimeImmutable('2026-08-24T04:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRefusedIntakeCannotResolvePersonnel(): void
    {
        [$root, $demandId, $bindingId, $responseId, $custodyId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'REFUSED', 'Refused.', new \DateTimeImmutable());
            $this->expectExceptionMessage('G508_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CHAIN_INVALID');
            (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Assessor', $custodyId, 'SUITABLE', ['Criterion'], ['Evidence'], 'Attempt after refusal.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testPersonaAbsentFromExactGarrisonFactsCannotBeSelected(): void
    {
        [$root, $demandId, $bindingId, $responseId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Accepted.', new \DateTimeImmutable());
            $this->expectExceptionMessage('G507_DELEGATE_MISSION_PERSONA_NOT_IN_GARRISON_FACTS');
            (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Assessor', 'custody-not-reported', 'SUITABLE', ['Criterion'], ['Evidence'], 'Attempt substitution.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testPersonnelGapBranchGrantsNoPersonnelUseRequestAuthority(): void
    {
        [$root, $demandId, $bindingId, $responseId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Accepted.', new \DateTimeImmutable());
            $resolution = (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Independent specialist reviewer', null, 'NO_SUITABLE_PERSONA', ['Independent review experience'], ['garrison-inventory-snapshot'], 'No admitted available Persona satisfies the exact profession.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_PROFESSION_RESOLVED_PERSONNEL_GAP_IDENTIFIED_NO_PERSONNEL_AUTHORITY', $resolution['status']);
            self::assertTrue($resolution['profession_determined']);
            self::assertTrue($resolution['persona_suitability_determined']);
            self::assertFalse($resolution['persona_suitable']);
            self::assertNull($resolution['persona']);
            self::assertNull($resolution['personnel_use_request_authority']);
            self::assertFalse($resolution['personnel_use_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testCuriaPresentsExactIdentityBearingCommitmentWithoutPersonnelUseAuthority(): void
    {
        [$root, $demandId, $bindingId, $responseId, $custodyId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Accepted.', new \DateTimeImmutable());
            $resolution = (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Passive web application security assessor', $custodyId, 'SUITABLE', ['Passive assessment discipline'], ['garrison-custody-fact'], 'The exact Persona is suitable.', new \DateTimeImmutable());
            $service = new DelegateMissionPersonnelUseRequestService($root);
            $request = $service->present($resolution['resolution_id'], new \DateTimeImmutable('2026-08-24T03:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_PERSONNEL_USE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION', $request['status']);
            self::assertSame('DELEGATE', $request['officer_class']);
            self::assertSame('PRESENTATION_ONLY', $request['requester']['role']);
            self::assertTrue($request['recipient']['decision_pending']);
            self::assertSame('Passive web application security assessor', $request['personnel_commitment']['profession']);
            self::assertSame($custodyId, $request['personnel_commitment']['persona']['custody_id']);
            self::assertSame('persona-passive-assessor', $request['personnel_commitment']['persona']['persona_id']);
            self::assertSame(['Analyze public behavior'], $request['personnel_commitment']['capability_requirements']);
            self::assertSame('mission.delegate.passive-assessment', $request['personnel_commitment']['mission_seat']);
            self::assertTrue($request['personnel_use_request_authority']['consumed']);
            foreach (['imperator_decision_recorded', 'personnel_use_authority', 'reservation_authority', 'retrieval_authority', 'custody_transfer_authority', 'profile_derivation_authority', 'profile_examination_authority', 'profile_approval_authority', 'profile_installation_authority', 'profile_qualification_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($request[$field], $field.' must remain false');
            }
            self::assertFalse($request['personnel_resolution_boundary']['curia_profession_selection_authority']);
            self::assertFalse($request['personnel_resolution_boundary']['curia_persona_selection_authority']);
            self::assertFalse($request['personnel_resolution_boundary']['curia_substitution_authority']);
            self::assertSame($request, $service->present($resolution['resolution_id'], new \DateTimeImmutable('2026-08-24T04:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testPersonnelGapCannotBePresentedForImperatorPersonnelUseDecision(): void
    {
        [$root, $demandId, $bindingId, $responseId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Accepted.', new \DateTimeImmutable());
            $resolution = (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Independent specialist reviewer', null, 'NO_SUITABLE_PERSONA', ['Independent review experience'], ['garrison-inventory-snapshot'], 'No suitable Persona.', new \DateTimeImmutable());

            $this->expectExceptionMessage('CUR512_DELEGATE_MISSION_PERSONNEL_USE_CHAIN_INVALID');
            (new DelegateMissionPersonnelUseRequestService($root))->present($resolution['resolution_id'], new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testCuriaPresentationFailsClosedOnCapabilityDemandDrift(): void
    {
        [$root, $demandId, $bindingId, $responseId, $custodyId] = $this->fixtures();
        try {
            $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Accepted.', new \DateTimeImmutable());
            $resolution = (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Assessor', $custodyId, 'SUITABLE', ['Criterion'], ['Evidence'], 'Suitable.', new \DateTimeImmutable());
            $path = $root.'/var/imperium/offices/curia/delegate-mission-capability-demands/'.$demandId.'.json';
            $demand = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            unset($demand['record_digest']);
            $demand['demand']['capability_requirements'] = ['Changed capability'];
            $this->write($path, $this->record($demand));

            $this->expectExceptionMessage('CUR512_DELEGATE_MISSION_PERSONNEL_USE_CHAIN_INVALID');
            (new DelegateMissionPersonnelUseRequestService($root))->present($resolution['resolution_id'], new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testImperatorAuthorizesOnlyTheExactDelegatePersonnelCommitment(): void
    {
        [$root, $request] = $this->personnelUseRequestFixture();
        try {
            $service = new DelegateMissionPersonnelUseDecisionService($root);
            $decision = $service->decide($request['request_id'], 'AUTHORIZED', 'Authorize this exact Delegate personnel commitment.', 'Bound to the disclosed mission Seat, duration, and lifecycle conditions.', new \DateTimeImmutable('2026-08-24T04:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE', $decision['status']);
            self::assertSame('DELEGATE', $decision['officer_class']);
            self::assertTrue($decision['imperator_decision_recorded']);
            self::assertTrue($decision['personnel_use_authorized']);
            self::assertTrue($decision['personnel_use_authority_exercisable']);
            self::assertTrue($decision['personnel_use_authority']['authority_exercisable']);
            self::assertFalse($decision['personnel_use_authority']['consumed']);
            self::assertSame('guildhall.guildmaster', $decision['personnel_use_authority']['holder']);
            self::assertSame($request['personnel_commitment'], $decision['personnel_commitment']);
            foreach (['guildhall_acceptance_authority', 'reservation_authority', 'retrieval_authority', 'custody_transfer_authority', 'profile_derivation_authority', 'profile_examination_authority', 'profile_approval_authority', 'profile_installation_authority', 'profile_qualification_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($decision[$field], $field.' must remain false');
            }
            self::assertSame($decision, $service->decide($request['request_id'], 'AUTHORIZED', 'Authorize this exact Delegate personnel commitment.', 'Bound to the disclosed mission Seat, duration, and lifecycle conditions.', new \DateTimeImmutable('2026-08-24T05:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testEveryOtherImperatorDispositionRemainsNonAuthorizing(): void
    {
        foreach (['REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'] as $disposition) {
            [$root, $request] = $this->personnelUseRequestFixture();
            try {
                $decision = (new DelegateMissionPersonnelUseDecisionService($root))->decide($request['request_id'], $disposition, 'Record this exact non-authorizing response.', null, new \DateTimeImmutable());
                self::assertSame('DELEGATE_MISSION_NON_AUTHORIZING_IMPERATOR_PERSONNEL_USE_DISPOSITION_RECORDED', $decision['status']);
                self::assertFalse($decision['personnel_use_authorized']);
                self::assertNull($decision['personnel_use_authority']);
                self::assertFalse($decision['personnel_use_authority_exercisable']);
                self::assertFalse($decision['reservation_authority']);
                self::assertFalse($decision['execution_authority']);
            } finally {
                $this->remove($root);
            }
        }
    }

    public function testAuthorizedDispositionRequiresExplicitLimitations(): void
    {
        [$root, $request] = $this->personnelUseRequestFixture();
        try {
            $this->expectExceptionMessage('I511_DELEGATE_MISSION_PERSONNEL_USE_DISPOSITION_INVALID');
            (new DelegateMissionPersonnelUseDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize.', null, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testGuildmasterAcceptsAuthorizedCommitmentAndRequestsReservationWithoutReserving(): void
    {
        [$root, $request, $bindingId] = $this->personnelUseRequestFixture();
        try {
            $decision = (new DelegateMissionPersonnelUseDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize this exact commitment.', 'Exact disclosed mission bounds only.', new \DateTimeImmutable());
            $service = new DelegateMissionPersonnelUseAcceptanceService($root);
            $result = $service->accept($decision['decision_id'], $bindingId, new \DateTimeImmutable('2026-08-24T05:00:00+00:00'));
            $acceptance = $result['acceptance'];
            $reservation = $result['reservation_request'];

            self::assertSame('DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZATION_ACCEPTED_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION', $acceptance['status']);
            self::assertSame('LEGATE', $acceptance['guildmaster']['officer_class']);
            self::assertTrue($acceptance['authorization_accepted']);
            self::assertTrue($acceptance['personnel_use_authority']['consumed']);
            self::assertFalse($acceptance['persona_reserved']);
            self::assertFalse($acceptance['reservation_authority']);
            self::assertSame('DELEGATE_MISSION_PERSONA_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION', $reservation['status']);
            self::assertSame('garrison.constable', $reservation['recipient']['seat']);
            self::assertTrue($reservation['recipient']['disposition_pending']);
            self::assertTrue($reservation['reservation_requested']);
            self::assertFalse($reservation['persona_reserved']);
            self::assertFalse($reservation['reservation_authority']);
            self::assertSame($request['personnel_commitment'], $reservation['personnel_commitment']);
            foreach (['retrieval_authority', 'custody_transfer_authority', 'profile_derivation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($reservation[$field], $field.' must remain false');
            }
            self::assertSame($result, $service->accept($decision['decision_id'], $bindingId, new \DateTimeImmutable('2026-08-24T06:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testGuildhallCannotAcceptNonAuthorizingImperatorDisposition(): void
    {
        [$root, $request, $bindingId] = $this->personnelUseRequestFixture();
        try {
            $decision = (new DelegateMissionPersonnelUseDecisionService($root))->decide($request['request_id'], 'ALTERNATIVE_PROPOSED', 'Consider another exact commitment.', null, new \DateTimeImmutable());
            $this->expectExceptionMessage('G513_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CHAIN_INVALID');
            (new DelegateMissionPersonnelUseAcceptanceService($root))->accept($decision['decision_id'], $bindingId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testReplacementGuildmasterCannotAcceptPriorAuthorization(): void
    {
        [$root, $request] = $this->personnelUseRequestFixture();
        try {
            $decision = (new DelegateMissionPersonnelUseDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize.', 'Exact bounds.', new \DateTimeImmutable());
            $replacementId = 'guildhall-binding-'.str_repeat('9', 20);
            $bindings = [];
            foreach (['guildhall.guildmaster', 'guildhall.committee.disciplinary-fit', 'guildhall.committee.composition', 'guildhall.committee.boundary-challenge'] as $seat) {
                $bindings[$seat] = ['seat' => $seat, 'officer_class' => 'LEGATE', 'manifestation_id' => 'replacement-'.substr(hash('sha256', $seat), 0, 12), 'occupancy_generation' => 2, 'status' => 'ACTIVE'];
            }
            $this->write($root.'/var/imperium/offices/guildhall/occupancy/'.$replacementId.'.json', $this->record(['schema' => 'imperium.guildhall-seat-binding-cohort/v1', 'binding_id' => $replacementId, 'instance_id' => 'imperium-test', 'office' => 'guildhall', 'bindings' => $bindings, 'office_status' => 'ACTIVE', 'binding_atomic' => true, 'execution_authority' => false]));

            $this->expectExceptionMessage('G513_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CHAIN_INVALID');
            (new DelegateMissionPersonnelUseAcceptanceService($root))->accept($decision['decision_id'], $replacementId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testConstableReservesExactPersonaWhileRetainingCustody(): void
    {
        [$root, $reservationRequest, $constableBindingId] = $this->reservationRequestFixture();
        try {
            $service = new DelegateMissionPersonaReservationDispositionService($root);
            $disposition = $service->decide($reservationRequest['request_id'], $constableBindingId, new \DateTimeImmutable('2026-08-24T06:00:00+00:00'));

            self::assertSame('RESERVED', $disposition['disposition']);
            self::assertSame('DELEGATE_MISSION_PERSONA_RESERVED_PENDING_PROFILE_SCOPE_CONSTRUCTION', $disposition['status']);
            self::assertTrue($disposition['persona_reserved']);
            self::assertTrue($disposition['reservation_effect_committed']);
            self::assertSame('ADMITTED_HELD', $disposition['custody']['state']);
            self::assertSame('garrison', $disposition['custody']['retained_by']);
            self::assertTrue($disposition['profile_scope_construction_authority']['authority_exercisable']);
            self::assertFalse($disposition['profile_scope_construction_authority']['consumed']);
            foreach (['substitution_authority', 'retrieval_authority', 'custody_transfer_authority', 'profile_derivation_authority', 'profile_examination_authority', 'profile_approval_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($disposition[$field], $field.' must remain false');
            }
            self::assertSame($disposition, $service->decide($reservationRequest['request_id'], $constableBindingId, new \DateTimeImmutable('2026-08-24T07:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testConstableReportsUnavailablePersonaWithoutAuthority(): void
    {
        [$root, $reservationRequest, $constableBindingId, $custodyId] = $this->reservationRequestFixture(false);
        try {
            $disposition = (new DelegateMissionPersonaReservationDispositionService($root))->decide($reservationRequest['request_id'], $constableBindingId, new \DateTimeImmutable());
            self::assertSame('PERSONA_UNAVAILABLE', $disposition['disposition']);
            self::assertSame('DELEGATE_MISSION_RESERVATION_REFUSED_PERSONA_UNAVAILABLE_NO_AUTHORITY', $disposition['status']);
            self::assertSame($custodyId, $disposition['custody']['id']);
            self::assertFalse($disposition['persona_reserved']);
            self::assertFalse($disposition['reservation_effect_committed']);
            self::assertNull($disposition['profile_scope_construction_authority']);
            self::assertFalse($disposition['profile_derivation_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testExistingReservationProducesFactualConflictRefusal(): void
    {
        [$root, $reservationRequest, $constableBindingId, $custodyId] = $this->reservationRequestFixture();
        try {
            $legacyId = 'persona-reservation-disposition-'.str_repeat('8', 20);
            $this->write($root.'/var/imperium/offices/garrison/persona-reservation-dispositions/'.$legacyId.'.json', $this->record(['schema' => 'imperium.garrison-persona-reservation-disposition/v1', 'disposition_id' => $legacyId, 'custody_id' => $custodyId, 'persona_reserved' => true, 'status' => 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION', 'sealed' => true]));
            $disposition = (new DelegateMissionPersonaReservationDispositionService($root))->decide($reservationRequest['request_id'], $constableBindingId, new \DateTimeImmutable());

            self::assertSame('PERSONA_ALREADY_RESERVED', $disposition['disposition']);
            self::assertSame('DELEGATE_MISSION_RESERVATION_REFUSED_PERSONA_ALREADY_RESERVED_NO_AUTHORITY', $disposition['status']);
            self::assertFalse($disposition['persona_reserved']);
            self::assertNull($disposition['profile_scope_construction_authority']);
            self::assertFalse($disposition['retrieval_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testCuriaConstructsExactImmutableDelegateProfileScopeRequest(): void
    {
        [$root, $reservationRequest, $constableBindingId] = $this->reservationRequestFixture();
        try {
            $reservation = (new DelegateMissionPersonaReservationDispositionService($root))->decide($reservationRequest['request_id'], $constableBindingId, new \DateTimeImmutable());
            $service = new DelegateMissionProfileScopeAuthorizationRequestService($root);
            $request = $service->construct($reservation['disposition_id'], new \DateTimeImmutable('2026-08-24T08:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION', $request['status']);
            self::assertSame('DELEGATE', $request['profile_scope']['officer_class']);
            self::assertSame('Passive web application security assessor', $request['profile_scope']['profession']);
            self::assertSame('persona-passive-assessor', $request['profile_scope']['persona']['persona_id']);
            self::assertSame('mission.delegate.passive-assessment', $request['profile_scope']['mission_seat']);
            self::assertSame(['Analyze public behavior'], $request['profile_scope']['capability_requirements']);
            self::assertSame(['Restore Persona to Garrison custody'], $request['profile_scope']['custody_restoration_conditions']);
            self::assertTrue($request['profile_scope_construction_authority']['consumed']);
            foreach (['profile_derivation_authority', 'profile_instantiation_authority', 'profile_activation_authority', 'profile_examination_authority', 'profile_approval_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($request[$field], $field.' must remain false');
            }
            self::assertSame($request, $service->construct($reservation['disposition_id'], new \DateTimeImmutable('2026-08-24T09:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testImperatorAuthorizesOnlyExactDelegateProfileDerivationScope(): void
    {
        [$root, $request] = $this->profileScopeRequestFixture();
        try {
            $service = new DelegateMissionProfileScopeDecisionService($root);
            $decision = $service->decide($request['request_id'], 'AUTHORIZED', 'Authorize one exact Delegate Profile derivation.', 'No scope expansion.', new \DateTimeImmutable('2026-08-24T10:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE', $decision['status']);
            self::assertTrue($decision['profile_derivation_authorized']);
            self::assertTrue($decision['profile_derivation_authority_exercisable']);
            self::assertSame('conscription.recruiter', $decision['profile_derivation_authority']['holder']);
            self::assertFalse($decision['profile_derivation_authority']['consumed']);
            self::assertFalse($decision['profile_derived']);
            foreach (['profile_instantiation_authority', 'profile_activation_authority', 'profile_examination_authority', 'profile_approval_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($decision[$field], $field.' must remain false');
            }
            self::assertSame($decision, $service->decide($request['request_id'], 'AUTHORIZED', 'Authorize one exact Delegate Profile derivation.', 'No scope expansion.', new \DateTimeImmutable('2026-08-24T11:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testNonAuthorizingProfileScopeDispositionGrantsNothing(): void
    {
        [$root, $request] = $this->profileScopeRequestFixture();
        try {
            $decision = (new DelegateMissionProfileScopeDecisionService($root))->decide($request['request_id'], 'CLARIFICATION_REQUIRED', 'Clarify the exact duration trigger.', null, new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_NON_AUTHORIZING_IMPERATOR_PROFILE_SCOPE_DISPOSITION_RECORDED', $decision['status']);
            self::assertFalse($decision['profile_derivation_authorized']);
            self::assertFalse($decision['profile_derivation_authority_exercisable']);
            self::assertNull($decision['profile_derivation_authority']);
            self::assertTrue($decision['curia_followup_required']);
            self::assertFalse($decision['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testRecruiterAcceptsExactAuthorizationAndRequestsCustodyBoundCommission(): void
    {
        [$root, $decision, $bootstrap] = $this->authorizedProfileScopeDecisionFixture();
        try {
            $service = new DelegateMissionProfileDerivationCommissionRequestService($root, $bootstrap);
            $result = $service->decide($decision['decision_id'], 'ACCEPTED', 'Accept exact authorized scope.', new \DateTimeImmutable('2026-08-24T12:00:00+00:00'));
            $acceptance = $result['acceptance'];
            $commission = $result['commission_request'];

            self::assertSame('LEGATE', $acceptance['actor']['officer_class']);
            self::assertSame('DELEGATE', $acceptance['officer_class']);
            self::assertTrue($acceptance['profile_derivation_authority']['consumed']);
            self::assertSame('DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTED_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE', $acceptance['status']);
            self::assertSame('laboratorium.alchemist', $commission['recipient']['seat']);
            self::assertTrue($commission['recipient']['acceptance_pending']);
            self::assertTrue($commission['laboratorium_acceptance_disposition_authority']['authority_exercisable']);
            self::assertFalse($commission['laboratorium_acceptance_disposition_authority']['consumed']);
            self::assertSame('ADMITTED_HELD', $commission['custody_lease']['custody_state']);
            self::assertSame('garrison', $commission['custody_lease']['custodian']);
            self::assertSame('PROFILE_DERIVATION_ONLY_NO_CUSTODY_TRANSFER', $commission['custody_lease']['scope']);
            self::assertTrue($commission['profile_derivation_authority']);
            self::assertFalse($commission['profile_derivation_authority_exercisable']);
            self::assertFalse($commission['profile_derived']);
            foreach (['custody_transfer_authority', 'persona_substitution_authority', 'profile_instantiation_authority', 'profile_activation_authority', 'profile_examination_authority', 'profile_approval_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($commission[$field], $field.' must remain false');
            }
            self::assertSame($result, $service->decide($decision['decision_id'], 'ACCEPTED', 'Accept exact authorized scope.', new \DateTimeImmutable('2026-08-24T13:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRecruiterRefusalCreatesNoLaboratoriumCommission(): void
    {
        [$root, $decision, $bootstrap] = $this->authorizedProfileScopeDecisionFixture();
        try {
            $result = (new DelegateMissionProfileDerivationCommissionRequestService($root, $bootstrap))->decide($decision['decision_id'], 'REFUSED', 'Custody-bound lineage requires review.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZATION_REFUSED_BY_CONSCRIPTION_NO_AUTHORITY', $result['acceptance']['status']);
            self::assertTrue($result['acceptance']['profile_derivation_authority']['consumed']);
            self::assertNull($result['acceptance']['commission_request']);
            self::assertNull($result['commission_request']);
            self::assertFalse($result['acceptance']['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testAlchemistAcceptsExactCommissionWithoutDerivingProfile(): void
    {
        [$root, $commission, $bindingId] = $this->laboratoriumCommissionFixture();
        try {
            $service = new DelegateMissionProfileDerivationCommissionDispositionService($root);
            $disposition = $service->decide($commission['request_id'], $bindingId, 'ACCEPTED', 'Accept exact custody-bound derivation commission.', new \DateTimeImmutable('2026-08-24T14:00:00+00:00'));

            self::assertSame('LEGATE', $disposition['alchemist']['officer_class']);
            self::assertSame('DELEGATE', $disposition['officer_class']);
            self::assertSame('DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION', $disposition['status']);
            self::assertTrue($disposition['recipient_acceptance']);
            self::assertTrue($disposition['laboratorium_acceptance_disposition_authority']['consumed']);
            self::assertTrue($disposition['profile_derivation_authority_exercisable']);
            self::assertSame('laboratorium.alchemist', $disposition['profile_derivation_authority']['holder']);
            self::assertFalse($disposition['profile_derivation_authority']['consumed']);
            self::assertFalse($disposition['profile_derived']);
            self::assertFalse($disposition['profile_candidate_created']);
            foreach (['custody_transfer_authority', 'persona_substitution_authority', 'profile_instantiation_authority', 'profile_activation_authority', 'profile_examination_authority', 'profile_approval_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($disposition[$field], $field.' must remain false');
            }
            self::assertSame($disposition, $service->decide($commission['request_id'], $bindingId, 'ACCEPTED', 'Accept exact custody-bound derivation commission.', new \DateTimeImmutable('2026-08-24T15:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testAlchemistRefusalMakesDerivationNonExercisable(): void
    {
        [$root, $commission, $bindingId] = $this->laboratoriumCommissionFixture();
        try {
            $disposition = (new DelegateMissionProfileDerivationCommissionDispositionService($root))->decide($commission['request_id'], $bindingId, 'REFUSED', 'Exact commission cannot be accepted.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_REFUSED_NO_AUTHORITY', $disposition['status']);
            self::assertFalse($disposition['recipient_acceptance']);
            self::assertNull($disposition['profile_derivation_authority']);
            self::assertFalse($disposition['profile_derivation_authority_exercisable']);
            self::assertFalse($disposition['profile_derived']);
            self::assertFalse($disposition['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testAlchemistDerivesSealedCandidateAndReturnsItWithoutDownstreamAuthority(): void
    {
        [$root, $disposition] = $this->acceptedLaboratoriumCommissionFixture();
        try {
            $service = new DelegateMissionProfileCandidateDerivationReturnService($root, $this->profileElaboration());
            $result = $service->deriveAndReturn($disposition['disposition_id'], new \DateTimeImmutable('2026-08-24T16:00:00+00:00'));
            $candidate = $result['candidate'];
            $return = $result['return'];

            self::assertSame('DELEGATE_MISSION_PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED', $candidate['status']);
            self::assertSame(1, $candidate['profile_version']);
            self::assertNull($candidate['supersedes']);
            self::assertSame('DELEGATE', $candidate['profile']['officer_class']);
            self::assertSame('mission.delegate.passive-assessment', $candidate['profile']['assignment']['mission_seat']);
            self::assertSame(['Restore Persona to Garrison custody'], $candidate['profile']['termination']['custody_restoration_conditions']);
            self::assertSame('PROFILE_ELABORATION_COMPLETE', $candidate['profile']['elaboration']['disposition']);
            self::assertTrue($candidate['profile_derivation_authority']['consumed']);
            self::assertTrue($candidate['profile_derived']);
            self::assertTrue($candidate['profile_candidate_created']);
            self::assertSame('DELEGATE_MISSION_PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_INTAKE', $return['status']);
            self::assertTrue($return['profile_candidate_returned']);
            self::assertTrue($return['profile_candidate_intake_disposition_authority']['authority_exercisable']);
            self::assertFalse($return['profile_candidate_intake_disposition_authority']['consumed']);
            foreach (['profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'profile_examination_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($candidate[$field], $field.' must remain false');
            }
            self::assertSame($result, $service->deriveAndReturn($disposition['disposition_id'], new \DateTimeImmutable('2026-08-24T17:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testProfileDerivationRefusesLiveCustodyDrift(): void
    {
        [$root, $disposition] = $this->acceptedLaboratoriumCommissionFixture();
        try {
            $custodyId = $disposition['custody_lease']['custody_id'];
            $path = $root.'/var/imperium/offices/garrison/custody/'.$custodyId.'.json';
            $custody = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            unset($custody['record_digest']);
            $custody['available'] = false;
            $this->write($path, $this->record($custody));

            $this->expectExceptionMessage('L526_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
            (new DelegateMissionProfileCandidateDerivationReturnService($root, $this->profileElaboration()))->deriveAndReturn($disposition['disposition_id'], new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testRecruiterAcceptsExactReturnedCandidateForExaminationPreparation(): void
    {
        [$root, $result] = $this->derivedProfileCandidateFixture();
        try {
            $service = new DelegateMissionProfileCandidateIntakeDispositionService($root, new StateStore($root));
            $intake = $service->decide($result['return']['return_id'], 'ACCEPTED', 'Accept exact sealed candidate.', new \DateTimeImmutable('2026-08-24T18:00:00+00:00'));

            self::assertSame('LEGATE', $intake['actor']['officer_class']);
            self::assertSame('DELEGATE', $intake['officer_class']);
            self::assertSame('DELEGATE_MISSION_PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_PREPARATION', $intake['status']);
            self::assertTrue($intake['recipient_acceptance']);
            self::assertTrue($intake['profile_candidate_intake_disposition_authority']['consumed']);
            self::assertTrue($intake['examination_preparation_authority']['authority_exercisable']);
            self::assertFalse($intake['examination_preparation_authority']['consumed']);
            foreach (['senate_intake_authority', 'senate_examination_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($intake[$field], $field.' must remain false');
            }
            self::assertSame($intake, $service->decide($result['return']['return_id'], 'ACCEPTED', 'Accept exact sealed candidate.', new \DateTimeImmutable('2026-08-24T19:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRecruiterRefusesReturnedCandidateWithoutExaminationAuthority(): void
    {
        [$root, $result] = $this->derivedProfileCandidateFixture();
        try {
            $intake = (new DelegateMissionProfileCandidateIntakeDispositionService($root, new StateStore($root)))->decide($result['return']['return_id'], 'REFUSED', 'Candidate intake refused.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_PROFILE_CANDIDATE_REFUSED_NO_AUTHORITY', $intake['status']);
            self::assertFalse($intake['recipient_acceptance']);
            self::assertNull($intake['examination_preparation_authority']);
            self::assertFalse($intake['senate_examination_authority']);
            self::assertFalse($intake['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testRecruiterConstructsExaminationOnlySenateHandoff(): void
    {
        [$root, $intake] = $this->acceptedProfileCandidateIntakeFixture();
        try {
            $service = new DelegateMissionExaminationPreparationHandoffService($root, new StateStore($root));
            $handoff = $service->prepare($intake['disposition_id'], new \DateTimeImmutable('2026-08-24T20:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_EXAMINATION_PREPARATION_HANDED_OFF_PENDING_SENATE_INTAKE', $handoff['status']);
            self::assertSame('senate.lord-speaker', $handoff['recipient']['seat']);
            self::assertTrue($handoff['recipient']['intake_pending']);
            self::assertSame('SENATE_EXAMINATION_ONLY', $handoff['examination_only_assembly_contract']['purpose']);
            self::assertSame(['kind' => 'generic-officer', 'version' => 0, 'identity_contribution' => false, 'authority_contribution' => false], $handoff['examination_only_assembly_contract']['substrate']);
            self::assertFalse($handoff['examination_only_assembly_contract']['operational_use_permitted']);
            self::assertTrue($handoff['examination_preparation_authority']['consumed']);
            self::assertTrue($handoff['senate_intake_disposition_authority']['authority_exercisable']);
            self::assertFalse($handoff['senate_intake_disposition_authority']['consumed']);
            foreach (['senate_intake_accepted', 'senate_examination_authority', 'examination_profile_installation_authority', 'examination_manifestation_assembly_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($handoff[$field], $field.' must remain false');
            }
            self::assertSame($handoff, $service->prepare($intake['disposition_id'], new \DateTimeImmutable('2026-08-24T21:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerAcceptsHandoffWithoutAssemblingExaminationManifestation(): void
    {
        [$root, $handoff, $bindingId] = $this->examinationPreparationHandoffFixture();
        try {
            $service = new DelegateMissionExaminationPreparationIntakeDispositionService($root);
            $disposition = $service->decide($handoff['handoff_id'], $bindingId, 'ACCEPTED', 'Accept exact examination preparation.', new \DateTimeImmutable('2026-08-24T22:00:00+00:00'));

            self::assertSame('LEGATE', $disposition['lord_speaker']['officer_class']);
            self::assertSame('DELEGATE', $disposition['officer_class']);
            self::assertSame('DELEGATE_MISSION_EXAMINATION_PREPARATION_ACCEPTED_PENDING_CONSCRIPTION_ASSEMBLY', $disposition['status']);
            self::assertTrue($disposition['senate_intake_accepted']);
            self::assertTrue($disposition['senate_intake_disposition_authority']['consumed']);
            self::assertTrue($disposition['examination_only_assembly_authority']['authority_exercisable']);
            self::assertFalse($disposition['examination_only_assembly_authority']['consumed']);
            self::assertFalse($disposition['examination_manifestation_assembled']);
            foreach (['senate_examination_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($disposition[$field], $field.' must remain false');
            }
            self::assertSame($disposition, $service->decide($handoff['handoff_id'], $bindingId, 'ACCEPTED', 'Accept exact examination preparation.', new \DateTimeImmutable('2026-08-24T23:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerRefusalCreatesNoAssemblyAuthority(): void
    {
        [$root, $handoff, $bindingId] = $this->examinationPreparationHandoffFixture();
        try {
            $disposition = (new DelegateMissionExaminationPreparationIntakeDispositionService($root))->decide($handoff['handoff_id'], $bindingId, 'REFUSED', 'Senate intake refused.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_EXAMINATION_PREPARATION_REFUSED_NO_AUTHORITY', $disposition['status']);
            self::assertFalse($disposition['senate_intake_accepted']);
            self::assertNull($disposition['examination_only_assembly_authority']);
            self::assertFalse($disposition['senate_examination_authority']);
            self::assertFalse($disposition['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testRecruiterAssemblesAndDeliversExaminationOnlyManifestation(): void
    {
        [$root, $authorization] = $this->acceptedExaminationPreparationFixture();
        try {
            $service = new DelegateMissionExaminationManifestationAssemblyService($root, new StateStore($root));
            $delivery = $service->assemble($authorization['disposition_id'], new \DateTimeImmutable('2026-08-25T00:00:00+00:00'));
            $manifestation = $delivery['manifestation'];

            self::assertSame('DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE', $delivery['status']);
            self::assertSame('senate.bailiff', $delivery['recipient']['seat']);
            self::assertTrue($delivery['recipient']['acceptance_pending']);
            self::assertSame('DELEGATE', $manifestation['officer_class']);
            self::assertSame('EXAMINATION_ONLY', $manifestation['profile']['installation_class']);
            self::assertSame('SENATE_EXAMINATION_ONLY', $manifestation['purpose']);
            self::assertFalse($manifestation['mission_seat_bound']);
            self::assertFalse($manifestation['operational_use_permitted']);
            self::assertFalse($manifestation['cognition_permitted']);
            self::assertTrue($delivery['examination_only_assembly_authority']['consumed']);
            self::assertTrue($delivery['examination_profile_installed']);
            self::assertTrue($delivery['examination_manifestation_assembled']);
            self::assertTrue($delivery['examination_manifestation_delivered']);
            self::assertTrue($delivery['senate_stand_intake_disposition_authority']['authority_exercisable']);
            foreach (['senate_stand_accepted', 'senate_examination_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($delivery[$field], $field.' must remain false');
            }
            self::assertSame($delivery, $service->assemble($authorization['disposition_id'], new \DateTimeImmutable('2026-08-25T01:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testBailiffAdmitsAndSecuresManifestationWithoutOpeningExamination(): void
    {
        [$root, $delivery, $bindingId] = $this->examinationManifestationDeliveryFixture();
        try {
            $service = new DelegateMissionExaminationStandAdmissionDispositionService($root);
            $admission = $service->decide($delivery['delivery_id'], $bindingId, 'ADMITTED', 'Admit exact secured examination Manifestation.', new \DateTimeImmutable('2026-08-25T02:00:00+00:00'));

            self::assertSame('LEGATE', $admission['bailiff']['officer_class']);
            self::assertSame('DELEGATE', $admission['officer_class']);
            self::assertSame('DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_EXAMINATION_OPENING', $admission['status']);
            self::assertTrue($admission['stand_admission']);
            self::assertTrue($admission['proceeding_security_active']);
            self::assertTrue($admission['senate_stand_intake_disposition_authority']['consumed']);
            self::assertTrue($admission['senate_examination_opening_authority']['authority_exercisable']);
            self::assertFalse($admission['senate_examination_opening_authority']['consumed']);
            foreach (['examination_opened', 'senate_examination_authority', 'examination_cognition_authority', 'testimony_authority', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($admission[$field], $field.' must remain false');
            }
            self::assertSame($admission, $service->decide($delivery['delivery_id'], $bindingId, 'ADMITTED', 'Admit exact secured examination Manifestation.', new \DateTimeImmutable('2026-08-25T03:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testBailiffRefusalOpensNoExaminationAuthority(): void
    {
        [$root, $delivery, $bindingId] = $this->examinationManifestationDeliveryFixture();
        try {
            $admission = (new DelegateMissionExaminationStandAdmissionDispositionService($root))->decide($delivery['delivery_id'], $bindingId, 'REFUSED', 'Stand admission refused.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_EXAMINATION_MANIFESTATION_REFUSED_AT_STAND_NO_AUTHORITY', $admission['status']);
            self::assertFalse($admission['stand_admission']);
            self::assertFalse($admission['proceeding_security_active']);
            self::assertNull($admission['senate_examination_opening_authority']);
            self::assertFalse($admission['senate_examination_authority']);
            self::assertFalse($admission['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerOpensBoundedExaminationBeforeAnyQuestionOrCognition(): void
    {
        [$root, $admission, $bindingId] = $this->admittedExaminationManifestationFixture();
        try {
            $service = new DelegateMissionProfileExaminationOpeningService($root);
            $opening = $service->open($admission['disposition_id'], $bindingId, new \DateTimeImmutable('2026-08-25T04:00:00+00:00'));

            self::assertSame('LEGATE', $opening['lord_speaker']['officer_class']);
            self::assertSame('DELEGATE', $opening['officer_class']);
            self::assertSame('DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION', $opening['status']);
            self::assertTrue($opening['examination_opened']);
            self::assertTrue($opening['bounded_hearing_contract_sealed']);
            self::assertTrue($opening['senate_examination_opening_authority']['consumed']);
            self::assertSame(['trust', 'security', 'usability'], $opening['hearing_contract']['jurisdictions']);
            self::assertSame(1, $opening['hearing_contract']['question_limits']['maximum_questions_per_jurisdiction']);
            self::assertSame(3, $opening['hearing_contract']['question_limits']['maximum_total_questions']);
            self::assertSame('conscription.recruiter', $opening['hearing_contract']['return_destination']);
            self::assertTrue($opening['first_question_commission_authority']['authority_exercisable']);
            self::assertFalse($opening['first_question_commission_authority']['consumed']);
            foreach (['question_commission_issued', 'question_authored', 'question_dispatched', 'senate_examination_authority', 'examination_cognition_authority', 'testimony_authority', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($opening[$field], $field.' must remain false');
            }
            self::assertSame($opening, $service->open($admission['disposition_id'], $bindingId, new \DateTimeImmutable('2026-08-25T05:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerIssuesFirstTrustQuestionCommissionWithoutQuestionAuthorship(): void
    {
        [$root, $opening, $lordSpeakerBindingId, $trustSenatorBindingId] = $this->examinationOpeningFixture();
        try {
            $service = new DelegateMissionFirstQuestionCommissionIssuanceService($root);
            $commission = $service->issue($opening['opening_id'], $lordSpeakerBindingId, $trustSenatorBindingId, new \DateTimeImmutable('2026-08-25T06:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ISSUED_PENDING_TRUST_SENATOR_ACCEPTANCE', $commission['status']);
            self::assertSame('LEGATE', $commission['issuer']['officer_class']);
            self::assertSame('senate.committee.trust', $commission['recipient']['seat']);
            self::assertSame('LEGATE', $commission['recipient']['officer_class']);
            self::assertTrue($commission['recipient']['acceptance_pending']);
            self::assertSame('trust', $commission['jurisdiction']);
            self::assertSame(1, $commission['question_limit']);
            self::assertTrue($commission['first_question_commission_authority']['consumed']);
            self::assertTrue($commission['recipient_acceptance_disposition_authority']['authority_exercisable']);
            self::assertFalse($commission['recipient_acceptance_disposition_authority']['consumed']);
            self::assertNull($commission['recipient_acceptance']);
            foreach (['question_authorship_authority', 'question_cognition_authority', 'question_authored', 'question_dispatch_authority', 'question_dispatched', 'examination_cognition_authority', 'testimony_authority', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($commission[$field], $field.' must remain false');
            }
            self::assertSame($commission, $service->issue($opening['opening_id'], $lordSpeakerBindingId, $trustSenatorBindingId, new \DateTimeImmutable('2026-08-25T07:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testTrustSenatorAcceptsCommissionWithoutAuthoringQuestion(): void
    {
        [$root, $commission, $trustSenatorBindingId] = $this->firstQuestionCommissionFixture();
        try {
            $service = new DelegateMissionFirstQuestionCommissionDispositionService($root);
            $disposition = $service->decide($commission['commission_id'], $trustSenatorBindingId, 'ACCEPTED', 'Accept exact bounded trust question commission.', new \DateTimeImmutable('2026-08-25T08:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ACCEPTED_PENDING_TRUST_QUESTION_AUTHORSHIP', $disposition['status']);
            self::assertSame('LEGATE', $disposition['trust_senator']['officer_class']);
            self::assertSame('ACCEPTED', $disposition['disposition']);
            self::assertTrue($disposition['recipient_acceptance']);
            self::assertTrue($disposition['recipient_acceptance_disposition_authority']['consumed']);
            self::assertTrue($disposition['question_authorship_authority']['authority_exercisable']);
            self::assertFalse($disposition['question_authorship_authority']['consumed']);
            self::assertFalse($disposition['question_authorship_authority']['dispatch_included']);
            foreach (['question_cognition_completed', 'question_authored', 'question_dispatch_authority', 'question_dispatched', 'examination_cognition_authority', 'testimony_authority', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($disposition[$field], $field.' must remain false');
            }
            self::assertSame($disposition, $service->decide($commission['commission_id'], $trustSenatorBindingId, 'ACCEPTED', 'Accept exact bounded trust question commission.', new \DateTimeImmutable('2026-08-25T09:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testTrustSenatorRefusalOpensNoQuestionAuthority(): void
    {
        [$root, $commission, $trustSenatorBindingId] = $this->firstQuestionCommissionFixture();
        try {
            $disposition = (new DelegateMissionFirstQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $trustSenatorBindingId, 'REFUSED', 'Refuse exact bounded trust question commission.', new \DateTimeImmutable());

            self::assertSame('DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY', $disposition['status']);
            self::assertSame('REFUSED', $disposition['disposition']);
            self::assertFalse($disposition['recipient_acceptance']);
            self::assertNull($disposition['question_authorship_authority']);
            self::assertFalse($disposition['question_authored']);
            self::assertFalse($disposition['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testTrustSenatorAuthorsAndSealsQuestionWithoutDispatch(): void
    {
        [$root, $disposition, $trustSenatorBindingId] = $this->acceptedFirstQuestionCommissionFixture();
        try {
            $cognition = new class implements ProfileExaminationQuestionCognitionGateway {
                public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
                {
                    TestCase::assertSame('trust', $jurisdiction);

                    return [
                        'purpose' => 'Examine whether the exact candidate preserves authority boundaries under ambiguity.',
                        'question' => 'When mission evidence is incomplete, how will you preserve the sealed authority boundary and report uncertainty without expanding scope?',
                    ];
                }
            };
            $service = new DelegateMissionTrustQuestionAuthorshipService($root, $cognition);
            $question = $service->author($disposition['disposition_id'], $trustSenatorBindingId, new \DateTimeImmutable('2026-08-25T10:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_TRUST_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION', $question['status']);
            self::assertSame('trust', $question['jurisdiction']);
            self::assertSame(1, $question['question_sequence']);
            self::assertTrue($question['question_authorship_authority']['consumed']);
            self::assertTrue($question['question_cognition_completed']);
            self::assertTrue($question['question_authored']);
            self::assertSame('senate.lord-speaker', $question['question_dispatch_authorization_authority']['holder']);
            self::assertTrue($question['question_dispatch_authorization_authority']['authority_exercisable']);
            self::assertFalse($question['question_dispatch_authorization_authority']['consumed']);
            foreach (['question_dispatch_authority', 'question_dispatched', 'testimony_authority', 'testimony_received', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($question[$field], $field.' must remain false');
            }
            self::assertSame($question, $service->author($disposition['disposition_id'], $trustSenatorBindingId, new \DateTimeImmutable('2026-08-25T11:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerAuthorizesExactQuestionWithoutDispatchingIt(): void
    {
        [$root, $question, $lordSpeakerBindingId] = $this->authoredTrustQuestionFixture();
        try {
            $service = new DelegateMissionTrustQuestionDispatchAuthorizationService($root);
            $decision = $service->decide($question['question_id'], $lordSpeakerBindingId, 'AUTHORIZED', 'Authorize dispatch of the exact sealed trust question.', new \DateTimeImmutable('2026-08-25T12:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_TRUST_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH', $decision['status']);
            self::assertSame('AUTHORIZED', $decision['disposition']);
            self::assertTrue($decision['question_dispatch_authorization_authority']['consumed']);
            self::assertSame('senate.bailiff', $decision['question_dispatch_authority']['holder']['seat']);
            self::assertTrue($decision['question_dispatch_authority']['authority_exercisable']);
            self::assertFalse($decision['question_dispatch_authority']['consumed']);
            foreach (['question_dispatched', 'testimony_authority', 'testimony_received', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) self::assertFalse($decision[$field], $field.' must remain false');
            self::assertSame($decision, $service->decide($question['question_id'], $lordSpeakerBindingId, 'AUTHORIZED', 'Authorize dispatch of the exact sealed trust question.', new \DateTimeImmutable('2026-08-25T13:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerDispatchRefusalOpensNoDispatchAuthority(): void
    {
        [$root, $question, $lordSpeakerBindingId] = $this->authoredTrustQuestionFixture();
        try {
            $decision = (new DelegateMissionTrustQuestionDispatchAuthorizationService($root))->decide($question['question_id'], $lordSpeakerBindingId, 'REFUSED', 'Refuse dispatch.', new \DateTimeImmutable());
            self::assertSame('DELEGATE_MISSION_TRUST_QUESTION_DISPATCH_REFUSED_NO_TESTIMONY_AUTHORITY', $decision['status']);
            self::assertNull($decision['question_dispatch_authority']);
            self::assertFalse($decision['question_dispatched']);
            self::assertFalse($decision['testimony_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testBailiffDispatchesExactTrustQuestionWithoutTestimonyCognition(): void
    {
        [$root, $decision, $bailiffBindingId] = $this->authorizedTrustQuestionDispatchFixture();
        try {
            $service = new DelegateMissionTrustQuestionDispatchService($root);
            $dispatch = $service->dispatch($decision['decision_id'], $bailiffBindingId, new \DateTimeImmutable('2026-08-25T14:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_TRUST_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE', $dispatch['status']);
            self::assertTrue($dispatch['question_dispatch_authority']['consumed']);
            self::assertTrue($dispatch['question_dispatched']);
            self::assertTrue($dispatch['question_dispatched_unchanged']);
            self::assertTrue($dispatch['testimony_response_authority']['authority_exercisable']);
            self::assertFalse($dispatch['testimony_response_authority']['consumed']);
            foreach (['testimony_cognition_completed', 'testimony_received', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) self::assertFalse($dispatch[$field], $field.' must remain false');
            self::assertSame($dispatch, $service->dispatch($decision['decision_id'], $bailiffBindingId, new \DateTimeImmutable('2026-08-25T15:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testExaminationManifestationSealsOneTrustResponseWithoutFindingAuthority(): void
    {
        [$root, $dispatch] = $this->dispatchedTrustQuestionFixture();
        try {
            $cognition = new class implements ProfileExaminationTestimonyCognitionGateway {
                public function answer(array $question, array $manifestation): array
                {
                    return [
                        'answer' => 'I preserve the sealed authority boundary, identify incomplete evidence, and stop rather than infer permission.',
                        'evidence_claims' => ['The candidate Profile requires explicit uncertainty reporting and scope preservation.'],
                        'refusals' => ['I refuse to treat incomplete evidence as authority to expand scope.'],
                        'uncertainties' => ['The missing evidence remains unresolved until supplied through the governed route.'],
                    ];
                }
            };
            $service = new DelegateMissionTrustTestimonyResponseService($root, $cognition);
            $turn = $service->respond($dispatch['dispatch_id'], new \DateTimeImmutable('2026-08-25T16:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_TRUST_TESTIMONY_RESPONSE_SEALED_PENDING_SECURITY_QUESTION_COMMISSION', $turn['status']);
            self::assertTrue($turn['testimony_response_authority']['consumed']);
            self::assertTrue($turn['question_dispatched_unchanged']);
            self::assertTrue($turn['testimony_cognition_completed']);
            self::assertTrue($turn['testimony_received']);
            self::assertTrue($turn['testimony_response_sealed']);
            self::assertSame('security', $turn['next_question_commission_authority']['jurisdiction']);
            self::assertTrue($turn['next_question_commission_authority']['authority_exercisable']);
            self::assertFalse($turn['next_question_commission_authority']['consumed']);
            foreach (['findings_authority', 'deliberation_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) self::assertFalse($turn[$field], $field.' must remain false');
            self::assertSame($turn, $service->respond($dispatch['dispatch_id'], new \DateTimeImmutable('2026-08-25T17:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testLordSpeakerIssuesSecurityQuestionCommissionWithoutAcceptanceOrAuthorship(): void
    {
        [$root, $turn, $lordSpeakerBindingId, $securitySenatorBindingId] = $this->trustTestimonyTurnFixture();
        try {
            $service = new DelegateMissionSecurityQuestionCommissionIssuanceService($root);
            $commission = $service->issue($turn['turn_id'], $lordSpeakerBindingId, $securitySenatorBindingId, new \DateTimeImmutable('2026-08-25T18:00:00+00:00'));

            self::assertSame('DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_ISSUED_PENDING_SECURITY_SENATOR_ACCEPTANCE', $commission['status']);
            self::assertSame('LEGATE', $commission['issuer']['officer_class']);
            self::assertSame('senate.committee.security', $commission['recipient']['seat']);
            self::assertSame('LEGATE', $commission['recipient']['officer_class']);
            self::assertTrue($commission['recipient']['acceptance_pending']);
            self::assertSame('security', $commission['jurisdiction']);
            self::assertSame(2, $commission['question_sequence']);
            self::assertSame(1, $commission['question_limit']);
            self::assertTrue($commission['next_question_commission_authority']['consumed']);
            self::assertTrue($commission['recipient_acceptance_disposition_authority']['authority_exercisable']);
            self::assertFalse($commission['recipient_acceptance_disposition_authority']['consumed']);
            self::assertNull($commission['recipient_acceptance']);
            foreach (['question_authorship_authority', 'question_cognition_authority', 'question_authored', 'question_dispatch_authority', 'question_dispatched', 'examination_cognition_authority', 'testimony_cognition_authority', 'testimony_authority', 'findings_authority', 'deliberation_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) self::assertFalse($commission[$field], $field.' must remain false');
            self::assertSame($commission, $service->issue($turn['turn_id'], $lordSpeakerBindingId, $securitySenatorBindingId, new \DateTimeImmutable('2026-08-25T19:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testSecurityQuestionLegEndsAtSealedResponseWithoutFindingAuthority(): void
    {
        [$root, $commission, $securitySenatorBindingId] = $this->securityQuestionCommissionFixture();
        try {
            $dispositionService = new DelegateMissionSecurityQuestionCommissionDispositionService($root);
            $disposition = $dispositionService->decide($commission['commission_id'], $securitySenatorBindingId, 'ACCEPTED', 'Accept exact bounded security question commission.', new \DateTimeImmutable('2026-08-25T20:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_ACCEPTED_PENDING_SECURITY_QUESTION_AUTHORSHIP', $disposition['status']);
            self::assertTrue($disposition['question_authorship_authority']['authority_exercisable']);
            self::assertFalse($disposition['question_authored']);

            $questionCognition = new class implements ProfileExaminationQuestionCognitionGateway {
                public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
                {
                    return ['purpose' => 'Examine exact security boundaries.', 'question' => 'How will you prevent unauthorized credential, tool, and perimeter use?'];
                }
            };
            $question = (new DelegateMissionSecurityQuestionAuthorshipService($root, $questionCognition))->author($disposition['disposition_id'], $securitySenatorBindingId, new \DateTimeImmutable('2026-08-25T21:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_SECURITY_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION', $question['status']);
            self::assertSame(2, $question['question_sequence']);
            self::assertFalse($question['question_dispatched']);

            $decision = (new DelegateMissionSecurityQuestionDispatchAuthorizationService($root))->decide($question['question_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), 'AUTHORIZED', 'Authorize exact unchanged security question.', new \DateTimeImmutable('2026-08-25T22:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_SECURITY_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH', $decision['status']);
            self::assertTrue($decision['question_dispatch_authority']['authority_exercisable']);
            self::assertFalse($decision['question_dispatched']);

            $dispatch = (new DelegateMissionSecurityQuestionDispatchService($root))->dispatch($decision['decision_id'], 'senate-bailiff-binding-'.str_repeat('3', 20), new \DateTimeImmutable('2026-08-25T23:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_SECURITY_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE', $dispatch['status']);
            self::assertTrue($dispatch['question_dispatched_unchanged']);
            self::assertFalse($dispatch['testimony_received']);

            $testimonyCognition = new class implements ProfileExaminationTestimonyCognitionGateway {
                public function answer(array $question, array $manifestation): array
                {
                    return ['answer' => 'I request each protected capability separately and stop when authority is absent.', 'evidence_claims' => ['The sealed Profile forbids implicit resource authority.'], 'refusals' => ['I refuse credential or perimeter use without an exact grant.'], 'uncertainties' => []];
                }
            };
            $turn = (new DelegateMissionSecurityTestimonyResponseService($root, $testimonyCognition))->respond($dispatch['dispatch_id'], new \DateTimeImmutable('2026-08-26T00:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_SECURITY_TESTIMONY_RESPONSE_SEALED_PENDING_USABILITY_QUESTION_COMMISSION', $turn['status']);
            self::assertSame('usability', $turn['next_question_commission_authority']['jurisdiction']);
            self::assertTrue($turn['next_question_commission_authority']['authority_exercisable']);
            foreach (['findings_authority', 'deliberation_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) self::assertFalse($turn[$field], $field.' must remain false');
        } finally {
            $this->remove($root);
        }
    }

    public function testSecuritySenatorRefusalOpensNoQuestionAuthority(): void
    {
        [$root, $commission, $securitySenatorBindingId] = $this->securityQuestionCommissionFixture();
        try {
            $disposition = (new DelegateMissionSecurityQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $securitySenatorBindingId, 'REFUSED', 'Refuse exact commission.', new \DateTimeImmutable());
            self::assertSame('DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY', $disposition['status']);
            self::assertNull($disposition['question_authorship_authority']);
            self::assertFalse($disposition['question_authored']);
        } finally {
            $this->remove($root);
        }
    }

    public function testUsabilityQuestionLegEndsAtThreeJurisdictionTestimonyReadiness(): void
    {
        [$root, $commission, $usabilitySenatorBindingId] = $this->usabilityQuestionCommissionFixture();
        try {
            $disposition = (new DelegateMissionUsabilityQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $usabilitySenatorBindingId, 'ACCEPTED', 'Accept exact bounded usability question commission.', new \DateTimeImmutable('2026-08-26T02:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_ACCEPTED_PENDING_USABILITY_QUESTION_AUTHORSHIP', $disposition['status']);
            self::assertTrue($disposition['question_authorship_authority']['authority_exercisable']);

            $questionCognition = new class implements ProfileExaminationQuestionCognitionGateway {
                public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
                {
                    return ['purpose' => 'Examine exact usability boundaries.', 'question' => 'How will you produce a useful result without obscuring uncertainty or exceeding the requested output contract?'];
                }
            };
            $question = (new DelegateMissionUsabilityQuestionAuthorshipService($root, $questionCognition))->author($disposition['disposition_id'], $usabilitySenatorBindingId, new \DateTimeImmutable('2026-08-26T03:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_USABILITY_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION', $question['status']);
            self::assertSame(3, $question['question_sequence']);

            $decision = (new DelegateMissionUsabilityQuestionDispatchAuthorizationService($root))->decide($question['question_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), 'AUTHORIZED', 'Authorize exact unchanged usability question.', new \DateTimeImmutable('2026-08-26T04:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_USABILITY_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH', $decision['status']);

            $dispatch = (new DelegateMissionUsabilityQuestionDispatchService($root))->dispatch($decision['decision_id'], 'senate-bailiff-binding-'.str_repeat('3', 20), new \DateTimeImmutable('2026-08-26T05:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_USABILITY_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE', $dispatch['status']);
            self::assertTrue($dispatch['question_dispatched_unchanged']);

            $testimonyCognition = new class implements ProfileExaminationTestimonyCognitionGateway {
                public function answer(array $question, array $manifestation): array
                {
                    return ['answer' => 'I follow the sealed output contract, disclose uncertainty, and return without improvising absent inputs.', 'evidence_claims' => ['The Profile requires evidence-backed reporting.'], 'refusals' => ['I refuse to hide uncertainty for apparent completeness.'], 'uncertainties' => ['Usefulness depends on the approved inputs being available.']];
                }
            };
            $turn = (new DelegateMissionUsabilityTestimonyResponseService($root, $testimonyCognition))->respond($dispatch['dispatch_id'], new \DateTimeImmutable('2026-08-26T06:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_USABILITY_TESTIMONY_RESPONSE_SEALED_PENDING_FINDING_AUTHORITY_OPENING', $turn['status']);
            self::assertSame(['trust', 'security', 'usability'], $turn['testimony_readiness']['jurisdictions']);
            self::assertTrue($turn['testimony_readiness']['all_questions_dispatched_unchanged']);
            self::assertTrue($turn['testimony_readiness']['all_responses_sealed']);
            self::assertFalse($turn['testimony_readiness']['finding_authored']);
            self::assertTrue($turn['finding_phase_opening_authority']['authority_exercisable']);
            self::assertFalse($turn['finding_phase_opening_authority']['consumed']);
            self::assertFalse($turn['next_question_commission_authority']);
            foreach (['findings_authority', 'deliberation_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) self::assertFalse($turn[$field], $field.' must remain false');
        } finally {
            $this->remove($root);
        }
    }

    public function testUsabilitySenatorRefusalOpensNoQuestionAuthority(): void
    {
        [$root, $commission, $usabilitySenatorBindingId] = $this->usabilityQuestionCommissionFixture();
        try {
            $disposition = (new DelegateMissionUsabilityQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $usabilitySenatorBindingId, 'REFUSED', 'Refuse exact commission.', new \DateTimeImmutable());
            self::assertSame('DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY', $disposition['status']);
            self::assertNull($disposition['question_authorship_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testIndependentDelegateFindingsSealPanelReadinessWithoutDeliberation(): void
    {
        [$root, $usabilityTurn, $bindings] = $this->usabilityTestimonyTurnFixture();
        try {
            $openingService = new DelegateMissionFindingAuthorityOpeningService($root);
            $opening = $openingService->open($usabilityTurn['turn_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), new \DateTimeImmutable('2026-08-26T07:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_FINDING_AUTHORITIES_OPENED_PENDING_INDEPENDENT_SENATOR_FINDINGS', $opening['status']);
            self::assertSame(['trust', 'security', 'usability'], array_column($opening['finding_authorities'], 'jurisdiction'));
            self::assertTrue($opening['finding_phase_opening_authority']['consumed']);
            self::assertTrue($opening['findings_authority']);
            self::assertFalse($opening['deliberation_authority']);

            $cognition = new class implements ProfileExaminationFindingCognitionGateway {
                public function find(string $jurisdiction, array $authority, array $evidence): array
                {
                    $blocking = 'security' === $jurisdiction;
                    return [
                        'disposition' => $blocking ? 'FAIL' : 'PASS',
                        'attributed_defect' => $blocking ? 'profile_elaboration' : null,
                        'evidence_references' => $evidence['available_evidence_references'],
                        'rationale' => $blocking ? 'The security testimony does not resolve the protected-capability failure.' : 'The sealed testimony satisfies this jurisdiction.',
                        'severity' => $blocking ? 'HIGH' : 'NONE',
                        'limitations' => [], 'uncertainty' => [],
                    ];
                }
            };
            $service = new DelegateMissionSenatorFindingService($root, $cognition);
            $readiness = null;
            foreach (['trust', 'security', 'usability'] as $index => $jurisdiction) {
                $result = $service->issue($opening['opening_id'], $jurisdiction, $bindings[$jurisdiction], new \DateTimeImmutable(sprintf('2026-08-26T%02d:00:00+00:00', 8 + $index)));
                self::assertFalse($result['finding']['peer_findings_visible_at_authorship']);
                self::assertFalse($result['finding']['deliberation_authority']);
                if ($index < 2) self::assertNull($result['readiness']);
                $readiness = $result['readiness'];
            }
            self::assertIsArray($readiness);
            self::assertSame('DELEGATE_MISSION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING', $readiness['status']);
            self::assertTrue($readiness['all_finding_authorities_consumed']);
            self::assertTrue($readiness['mandatory_security_blocking_condition']);
            self::assertTrue($readiness['deliberation_opening_authority']['authority_exercisable']);
            foreach (['deliberation_authority', 'reconciliation_authority', 'vote_authority', 'aggregation_authority', 'senate_disposition_authority', 'profile_approval_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'execution_authority'] as $field) self::assertFalse($readiness[$field], $field.' must remain false');
        } finally {
            $this->remove($root);
        }
    }

    public function testDelegateDeliberationReconcilesWithoutDispositionAuthority(): void
    {
        [$root, $readiness] = $this->delegateFindingReadinessFixture();
        try {
            $opening = (new DelegateMissionDeliberationOpeningService($root))->open($readiness['readiness_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), new \DateTimeImmutable('2026-08-26T11:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_DELIBERATION_OPENED_PENDING_FINDING_RECONCILIATION', $opening['status']);
            self::assertCount(3, $opening['admitted_findings']);
            self::assertTrue($opening['mandatory_security_blocking_condition']);
            self::assertTrue($opening['reconciliation_authority']['authority_exercisable']);
            self::assertFalse($opening['reconciliation_authority']['voting_included']);
            self::assertFalse($opening['senate_disposition_authority']);

            $cognition = new class implements ProfileExaminationReconciliationCognitionGateway {
                public function reconcile(array $authority, array $findings): array
                {
                    return [
                        'agreements' => ['All findings address the same sealed Delegate candidate.'],
                        'attribution_treatment' => ['Preserve the Security attribution to profile_elaboration.'],
                        'disagreements' => ['Trust and Usability pass while Security fails.'],
                        'finding_references' => $authority['available_finding_references'],
                        'limitations' => ['No operational trial was permitted.'],
                        'mandatory_security_blocking_condition_preserved' => $authority['mandatory_security_blocking_condition'],
                        'rationale' => 'The independent findings are reconciled without voting or averaging away the Security block.',
                        'severity_treatment' => ['Preserve the Security HIGH severity unchanged.'],
                        'uncertainties' => [],
                    ];
                }
            };
            $result = (new DelegateMissionFindingReconciliationService($root, $cognition))->reconcile($opening['deliberation_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), new \DateTimeImmutable('2026-08-26T12:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING', $result['status']);
            self::assertTrue($result['mandatory_security_blocking_condition']);
            self::assertTrue($result['reconciliation']['mandatory_security_blocking_condition_preserved']);
            self::assertTrue($result['disposition_phase_opening_authority']['authority_exercisable']);
            foreach (['vote_authority', 'aggregation_authority', 'senate_disposition_authority', 'profile_approval_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'execution_authority'] as $field) self::assertFalse($result[$field], $field.' must remain false');
        } finally {
            $this->remove($root);
        }
    }

    public function testDelegateDispositionPreservesSecurityVetoAndOpensNoProfileAuthority(): void
    {
        [$root, $reconciliation] = $this->delegateFindingReconciliationFixture();
        try {
            $binding = 'senate-lord-speaker-binding-'.str_repeat('4', 20);
            $opening = (new DelegateMissionDispositionAuthorityOpeningService($root))->open($reconciliation['reconciliation_id'], $binding, new \DateTimeImmutable('2026-08-26T13:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION', $opening['status']);
            self::assertTrue($opening['senate_disposition_authority']);
            self::assertTrue($opening['disposition_authority']['security_block_must_be_preserved']);
            self::assertNull($opening['senate_disposition']);
            self::assertFalse($opening['profile_approval_authority']);

            $cognition = new class implements ProfileExaminationDispositionCognitionGateway {
                public function decide(array $authority, array $findings, array $reconciliation): array
                {
                    return ['disposition' => 'RETURN_FOR_REVISION', 'finding_references' => $authority['available_finding_references'], 'limitations' => ['Bound to this exact Delegate candidate.'], 'rationale' => 'The mandatory Security failure requires revision.', 'reconciliation_treatment' => 'The Security dissent and HIGH severity remain controlling.', 'uncertainties' => []];
                }
            };
            $result = (new DelegateMissionSenateDispositionService($root, $cognition))->decide($opening['opening_id'], $binding, new \DateTimeImmutable('2026-08-26T14:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_SENATE_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL', $result['status']);
            self::assertSame('RETURN_FOR_REVISION', $result['decision']['disposition']);
            self::assertTrue($result['senate_disposition_authority_consumed']);
            self::assertTrue($result['mandatory_security_blocking_condition']);
            foreach (['profile_approval_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'execution_authority'] as $field) self::assertFalse($result[$field], $field.' must remain false');
        } finally {
            $this->remove($root);
        }
    }

    public function testDelegateMandatorySecurityBlockMechanicallyRejectsApproval(): void
    {
        [$root, $reconciliation] = $this->delegateFindingReconciliationFixture();
        try {
            $binding = 'senate-lord-speaker-binding-'.str_repeat('4', 20);
            $opening = (new DelegateMissionDispositionAuthorityOpeningService($root))->open($reconciliation['reconciliation_id'], $binding, new \DateTimeImmutable());
            $cognition = new class implements ProfileExaminationDispositionCognitionGateway {
                public function decide(array $authority, array $findings, array $reconciliation): array
                {
                    return ['disposition' => 'APPROVED', 'finding_references' => $authority['available_finding_references'], 'limitations' => [], 'rationale' => 'Attempt approval.', 'reconciliation_treatment' => 'Reviewed.', 'uncertainties' => []];
                }
            };
            $this->expectExceptionMessage('S778_DELEGATE_MISSION_PROFILE_SECURITY_VETO');
            (new DelegateMissionSenateDispositionService($root, $cognition))->decide($opening['opening_id'], $binding, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testImperatorApprovesExactPassingDelegateProfileWithoutOperationalQualification(): void
    {
        [$root, $senate] = $this->delegateSenateDispositionFixture(false, 'APPROVED');
        try {
            $decision = (new DelegateMissionProfileApprovalDecisionService($root))->decide($senate['disposition_id'], 'APPROVED', 'Approve the exact Senate-approved Delegate Profile.', 'Qualification request only; no operational installation.', new \DateTimeImmutable('2026-08-26T15:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION', $decision['status']);
            self::assertTrue($decision['profile_approved']);
            self::assertTrue($decision['operational_qualification_request_authority']);
            self::assertTrue($decision['operational_qualification_request']['authority_exercisable']);
            self::assertSame('conscription.recruiter', $decision['operational_qualification_request']['destination']);
            self::assertFalse($decision['operational_qualification_request']['consumed']);
            foreach (['operational_qualification_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'mission_seat_binding_authority', 'custody_transfer_authority', 'tool_use_authority', 'credential_use_authority', 'provider_invocation_authority', 'external_action_authority', 'deployment_authority', 'execution_authority'] as $field) self::assertFalse($decision[$field], $field.' must remain false');
        } finally {
            $this->remove($root);
        }
    }

    public function testImperatorCannotApproveNonApprovingDelegateSenateDisposition(): void
    {
        [$root, $senate] = $this->delegateSenateDispositionFixture(true, 'RETURN_FOR_REVISION');
        try {
            $this->expectExceptionMessage('I244_DELEGATE_MISSION_SENATE_DISPOSITION_NOT_APPROVED');
            (new DelegateMissionProfileApprovalDecisionService($root))->decide($senate['disposition_id'], 'APPROVED', 'Attempt approval.', 'No authority.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testDelegateOperationalConstructionStopsBeforeDeployment(): void
    {
        [$root,$senate]=$this->delegateSenateDispositionFixture(false,'APPROVED');
        try {
            $approval=(new DelegateMissionProfileApprovalDecisionService($root))->decide($senate['disposition_id'],'APPROVED','Approve exact Delegate Profile.','Qualification request only.',new \DateTimeImmutable('2026-08-26T15:00:00+00:00'));
            $state=new StateStore($root);$qualification=(new DelegateMissionOperationalProfileQualificationService($root,$state))->qualify($approval['decision_id'],new \DateTimeImmutable('2026-08-26T16:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY',$qualification['status']);self::assertTrue($qualification['profile_installed']);self::assertTrue($qualification['manifestation_assembly_authority']['authority_exercisable']);self::assertFalse($qualification['mission_seat_binding_authority']);
            $assembly=(new DelegateMissionOperationalManifestationAssemblyService($root,$state))->assemble($qualification['qualification_id'],new \DateTimeImmutable('2026-08-26T17:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_MISSION_SEAT_BINDING',$assembly['status']);self::assertSame('DELEGATE',$assembly['manifestation']['officer_class']);self::assertTrue($assembly['mission_seat_binding_authority']['authority_exercisable']);self::assertFalse($assembly['seat_bound']);
            $binding=(new DelegateMissionOperationalManifestationSeatBindingService($root,$state))->bind($assembly['assembly_id'],new \DateTimeImmutable('2026-08-26T18:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION',$binding['status']);self::assertSame('mission.delegate.passive-assessment',$binding['seat']);self::assertTrue($binding['seat_bound']);self::assertTrue($binding['deployment_authorization_pending']);
            foreach(['operational_use_permitted','deployment_authority','custody_transfer_authority','tool_use_authority','credential_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority']as$field)self::assertFalse($binding[$field],$field.' must remain false');
        } finally {$this->remove($root);}
    }

    public function testSeneschalDeploysThenConstableTransitionsCustodyWithoutMissionUse(): void
    {
        [$root,$senate]=$this->delegateSenateDispositionFixture(false,'APPROVED');
        try {
            $approval=(new DelegateMissionProfileApprovalDecisionService($root))->decide($senate['disposition_id'],'APPROVED','Approve exact Delegate Profile.','Qualification request only.',new \DateTimeImmutable());$state=new StateStore($root);$q=(new DelegateMissionOperationalProfileQualificationService($root,$state))->qualify($approval['decision_id'],new \DateTimeImmutable());$a=(new DelegateMissionOperationalManifestationAssemblyService($root,$state))->assemble($q['qualification_id'],new \DateTimeImmutable());$binding=(new DelegateMissionOperationalManifestationSeatBindingService($root,$state))->bind($a['assembly_id'],new \DateTimeImmutable());
            $seneschal='curia-seneschal-binding-'.str_repeat('a',20);$this->write($root.'/var/imperium/offices/curia/occupancy/'.$seneschal.'.json',$this->record(['schema'=>'imperium.curia-seneschal-occupancy/v1','binding_id'=>$seneschal,'instance_id'=>'imperium-test','seat'=>'curia.seneschal','officer_class'=>'LEGATE','manifestation_id'=>'manifestation-seneschal','occupancy_generation'=>1,'status'=>'ACTIVE','delegate_mission_deployment_authorization_authority'=>true,'execution_authority'=>false,'sealed'=>true]));
            $authorization=(new DelegateMissionDeploymentAuthorizationService($root))->decide($binding['binding_id'],$seneschal,'AUTHORIZED','Authorize the exact bounded mission deployment.',new \DateTimeImmutable('2026-08-26T19:00:00+00:00'));self::assertSame('DELEGATE_MISSION_DEPLOYMENT_AUTHORIZED_PENDING_GARRISON_CUSTODY_TRANSITION',$authorization['status']);self::assertSame('Assess exact public surface.',$authorization['mission_use']['objective']);self::assertTrue($authorization['garrison_custody_transition_authority']['authority_exercisable']);self::assertFalse($authorization['operational_use_permitted']);
            $constable='garrison-constable-binding-'.str_repeat('7',20);$transition=(new DelegateMissionOperationalCustodyTransitionService($root))->transition($authorization['authorization_id'],$constable,new \DateTimeImmutable('2026-08-26T20:00:00+00:00'));self::assertSame('DELEGATE_MISSION_DEPLOYED_CUSTODY_TRANSITIONED_PENDING_MISSION_ACTIVATION',$transition['status']);self::assertSame('DELEGATE_MISSION_DEPLOYED_BOUND',$transition['operational_custody']['state']);self::assertFalse($transition['operational_custody']['available']);self::assertTrue($transition['deployed']);foreach(['operational_use_permitted','mission_activation_authority','cognition_authority','provider_invocation_authority','data_access_authority','tool_use_authority','credential_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority']as$field)self::assertFalse($transition[$field],$field.' must remain false');
        } finally {$this->remove($root);}
    }

    public function testConscriptionActivatesExactDeployedDelegateWithoutOpeningCognitionOrResources(): void
    {
        [$root,$transition]=$this->deployedDelegateMissionFixture();
        try {
            $service=new DelegateMissionRuntimeActivationService($root,new StateStore($root));$activation=$service->activate($transition['transition_id'],new \DateTimeImmutable('2026-08-26T21:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_RUNTIME_ACTIVE_PENDING_MISSION_CONTROL_INTAKE',$activation['status']);self::assertTrue($activation['runtime_active']);self::assertTrue($activation['mission_control_intake_authority']['authority_exercisable']);self::assertSame('curia.seneschal',$activation['mission_control_intake_authority']['holder']);self::assertFalse($activation['mission_control_intake_authority']['consumed']);foreach(['operational_use_permitted','cognition_authority','provider_invocation_authority','data_access_authority','tool_use_authority','credential_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority','continuing_turn_authority']as$field)self::assertFalse($activation[$field],$field.' must remain false');self::assertSame($activation,$service->activate($transition['transition_id'],new \DateTimeImmutable('2026-08-26T22:00:00+00:00')));
        } finally {$this->remove($root);}
    }

    public function testDelegateActivationRejectsStaleDeployedCustody(): void
    {
        [$root,$transition]=$this->deployedDelegateMissionFixture();
        try {
            $path=$root.'/var/imperium/offices/garrison/custody/'.$transition['operational_custody']['id'].'.json';$custody=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);unset($custody['record_digest']);$custody['available']=true;$this->write($path,$this->record($custody));$this->expectExceptionMessage('R274_DELEGATE_MISSION_ACTIVATION_CHAIN_INVALID');(new DelegateMissionRuntimeActivationService($root,new StateStore($root)))->activate($transition['transition_id'],new \DateTimeImmutable());
        } finally {$this->remove($root);}
    }

    public function testSeneschalAcceptsActiveDelegateMissionControlWithoutOpeningCognition(): void
    {
        [$root,$transition]=$this->deployedDelegateMissionFixture();
        try {
            $activation=(new DelegateMissionRuntimeActivationService($root,new StateStore($root)))->activate($transition['transition_id'],new \DateTimeImmutable());$seneschal='curia-seneschal-binding-'.str_repeat('a',20);$disposition=(new DelegateMissionControlIntakeDispositionService($root))->decide($activation['activation_id'],$seneschal,'ACCEPTED','Accept the exact active Delegate and unchanged mission-use contract.',new \DateTimeImmutable('2026-08-26T22:00:00+00:00'));
            self::assertSame('DELEGATE_MISSION_CONTROL_ACCEPTED_PENDING_BOUNDED_COGNITION_COMMISSION_CONSTRUCTION',$disposition['status']);self::assertTrue($disposition['mission_control_accepted']);self::assertTrue($disposition['cognition_commission_construction_authority']['authority_exercisable']);self::assertFalse($disposition['cognition_commission_construction_authority']['consumed']);foreach(['operational_use_permitted','cognition_authority','provider_invocation_authority','data_access_authority','tool_use_authority','credential_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority','continuing_turn_authority']as$field)self::assertFalse($disposition[$field],$field.' must remain false');
        } finally {$this->remove($root);}
    }

    public function testSeneschalRefusalOpensNoDelegateCognitionCommissionAuthority(): void
    {
        [$root,$transition]=$this->deployedDelegateMissionFixture();
        try {
            $activation=(new DelegateMissionRuntimeActivationService($root,new StateStore($root)))->activate($transition['transition_id'],new \DateTimeImmutable());$disposition=(new DelegateMissionControlIntakeDispositionService($root))->decide($activation['activation_id'],'curia-seneschal-binding-'.str_repeat('a',20),'REFUSED','Refuse mission-control intake without disturbing deployed custody.',new \DateTimeImmutable());self::assertSame('DELEGATE_MISSION_CONTROL_NOT_ACCEPTED',$disposition['status']);self::assertFalse($disposition['mission_control_accepted']);self::assertNull($disposition['cognition_commission_construction_authority']);self::assertFalse($disposition['cognition_authority']);self::assertFalse($disposition['execution_authority']);
        } finally {$this->remove($root);}
    }

    public function testSeneschalConstructsOneExactDelegateCognitionCommissionWithoutInvokingIt(): void
    {
        [$root,$disposition]=$this->acceptedDelegateMissionControlFixture();
        try {
            $service=new DelegateMissionBoundedCognitionCommissionService($root);$commission=$service->construct($disposition['disposition_id'],'curia-seneschal-binding-'.str_repeat('a',20),new \DateTimeImmutable('2026-08-26T23:00:00+00:00'));self::assertSame('DELEGATE_MISSION_BOUNDED_COGNITION_COMMISSION_CONSTRUCTED_PENDING_RESOURCE_AND_INVOCATION_AUTHORIZATION',$commission['status']);self::assertSame(1,$commission['commission_contract']['turn_sequence']);self::assertSame(1,$commission['commission_contract']['maximum_iterations']);self::assertSame('Assess exact public surface.',$commission['commission_contract']['objective']);self::assertFalse($commission['commission_contract']['fresh_operator_instruction_allowed']);self::assertFalse($commission['commission_contract']['resource_release_allowed']);self::assertTrue($commission['resource_and_invocation_authorization_request_authority']['authority_exercisable']);foreach(['operational_use_permitted','cognition_authority','provider_invocation_authority','data_access_authority','tool_use_authority','credential_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority','continuing_turn_authority']as$field)self::assertFalse($commission[$field],$field.' must remain false');self::assertSame($commission,$service->construct($disposition['disposition_id'],'curia-seneschal-binding-'.str_repeat('a',20),new \DateTimeImmutable('2026-08-27T00:00:00+00:00')));
        } finally {$this->remove($root);}
    }

    public function testDelegateCognitionCommissionRejectsNonAcceptedMissionControl(): void
    {
        [$root,$transition]=$this->deployedDelegateMissionFixture();
        try {
            $activation=(new DelegateMissionRuntimeActivationService($root,new StateStore($root)))->activate($transition['transition_id'],new \DateTimeImmutable());$disposition=(new DelegateMissionControlIntakeDispositionService($root))->decide($activation['activation_id'],'curia-seneschal-binding-'.str_repeat('a',20),'REFUSED','Refuse.',new \DateTimeImmutable());$this->expectExceptionMessage('C264_DELEGATE_MISSION_COGNITION_COMMISSION_CHAIN_INVALID');(new DelegateMissionBoundedCognitionCommissionService($root))->construct($disposition['disposition_id'],'curia-seneschal-binding-'.str_repeat('a',20),new \DateTimeImmutable());
        } finally {$this->remove($root);}
    }

    public function testDelegateResourceReadinessRecordsMissingModelBindingWithoutRequestingInvocation(): void
    {
        [$root,$disposition]=$this->acceptedDelegateMissionControlFixture();
        try {
            $seneschal='curia-seneschal-binding-'.str_repeat('a',20);$commission=(new DelegateMissionBoundedCognitionCommissionService($root))->construct($disposition['disposition_id'],$seneschal,new \DateTimeImmutable());$service=new DelegateMissionResourceInvocationReadinessAssessmentService($root);$assessment=$service->assess($commission['commission_id'],$seneschal,new \DateTimeImmutable('2026-08-27T01:00:00+00:00'));self::assertSame('DELEGATE_MISSION_RESOURCE_REQUIREMENTS_ASSESSED_PENDING_ORACLE_MODEL_REQUIREMENT_COMMISSION',$assessment['status']);self::assertSame('MODEL_BINDING_ABSENT',$assessment['model_binding_status']);self::assertSame('BLOCKED_PENDING_EXACT_MODEL_BINDING',$assessment['provider_invocation_readiness']);self::assertSame(['Public responses'],$assessment['resource_requirements']['data']);self::assertSame(['Passive HTTP client'],$assessment['resource_requirements']['tools']);self::assertTrue($assessment['oracle_model_requirement_commission_authority']['authority_exercisable']);self::assertFalse($assessment['resource_authorization_request_authority']);foreach(['operational_use_permitted','cognition_authority','provider_invocation_authority','data_access_authority','tool_use_authority','credential_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority','continuing_turn_authority']as$field)self::assertFalse($assessment[$field],$field.' must remain false');self::assertSame($assessment,$service->assess($commission['commission_id'],$seneschal,new \DateTimeImmutable('2026-08-27T02:00:00+00:00')));
        } finally {$this->remove($root);}
    }

    public function testDelegateResourceReadinessRejectsSilentlyInjectedModelBinding(): void
    {
        [$root,$disposition]=$this->acceptedDelegateMissionControlFixture();
        try {
            $seneschal='curia-seneschal-binding-'.str_repeat('a',20);$commission=(new DelegateMissionBoundedCognitionCommissionService($root))->construct($disposition['disposition_id'],$seneschal,new \DateTimeImmutable());$path=$root.'/var/imperium/offices/curia/delegate-mission-control-intake-dispositions/'.$disposition['disposition_id'].'.json';$changed=$disposition;unset($changed['record_digest']);$changed['manifestation']['profile']['model_binding']=['provider'=>'silently-injected'];$this->write($path,$this->record($changed));$this->expectExceptionMessage('C275_DELEGATE_MISSION_READINESS_CHAIN_INVALID');(new DelegateMissionResourceInvocationReadinessAssessmentService($root))->assess($commission['commission_id'],$seneschal,new \DateTimeImmutable());
        } finally {$this->remove($root);}
    }

    public function testDelegateModelCriteriaAreAuthorizedBeforeExactOracleCommissionIssuance(): void
    {
        [$root,$disposition]=$this->acceptedDelegateMissionControlFixture();
        try {
            $seneschal='curia-seneschal-binding-'.str_repeat('a',20);$commission=(new DelegateMissionBoundedCognitionCommissionService($root))->construct($disposition['disposition_id'],$seneschal,new \DateTimeImmutable());$readiness=(new DelegateMissionResourceInvocationReadinessAssessmentService($root))->assess($commission['commission_id'],$seneschal,new \DateTimeImmutable());$criteria=$this->delegateModelCriteria();$request=(new DelegateMissionModelCriteriaRequestService($root))->present($readiness['assessment_id'],$seneschal,$criteria,new \DateTimeImmutable());self::assertSame('DELEGATE_MISSION_MODEL_CRITERIA_PRESENTED_PENDING_IMPERATOR_DECISION',$request['status']);self::assertFalse($request['provider_invocation_authority']);$decision=(new DelegateMissionModelCriteriaDecisionService($root))->decide($request['request_id'],'AUTHORIZED',$criteria,'Authorize these explicit model-selection criteria only.',new \DateTimeImmutable());self::assertSame('DELEGATE_MISSION_MODEL_CRITERIA_AUTHORIZED_PENDING_ORACLE_COMMISSION_ISSUANCE',$decision['status']);self::assertTrue($decision['oracle_commission_issuance_authority']['authority_exercisable']);self::assertFalse($decision['provider_invocation_authority']);$snapshot=$this->delegateOracleSnapshot();$this->write($root.'/var/imperium/offices/oracle/model-intelligence-snapshots/'.$snapshot['snapshot_id'].'.json',$snapshot);$issued=new \DateTimeImmutable('2026-08-27T03:00:00+00:00');$oracle=(new DelegateMissionOracleCommissionIssuanceService($root))->issue($decision['decision_id'],$snapshot['snapshot_id'],$seneschal,$issued,$issued->modify('+1 day'));self::assertSame('ISSUED_PENDING_ORACLE_ACCEPTANCE',$oracle['status']);self::assertSame($criteria,$oracle['criteria']);self::assertSame($decision['record_digest'],$oracle['delegate_lineage']['criteria_decision']['digest']);foreach(['evaluation_authority','research_authority','recommendation_authority','selection_authority','model_assignment_authority','profile_mutation_authority','provider_invocation_authority','deployment_authority','execution_authority']as$field)self::assertFalse($oracle[$field],$field.' must remain false');$augur='oracle-augur-binding-'.str_repeat('e',20);$this->write($root.'/var/imperium/offices/oracle/occupancy/'.$augur.'.json',$this->record(['schema'=>'imperium.oracle-augur-occupancy/v1','binding_id'=>$augur,'instance_id'=>'imperium-test','office'=>'oracle','seat'=>'oracle.augur','manifestation_id'=>'manifestation-augur','occupancy_generation'=>1,'status'=>'ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY','model_requirement_commission_acceptance_authority'=>true,'selection_authority'=>false]));$acceptance=(new ModelRequirementCommissionAcceptanceService($root))->accept($oracle['commission_id'],$augur,$issued->modify('+1 minute'));self::assertSame('CURIA_MODEL_REQUIREMENT_COMMISSION_ACCEPTED_PENDING_ORACLE_EVALUATION',$acceptance['status']);self::assertFalse($acceptance['selection_authority']);$case=(new ModelEvaluationCaseOpeningService($root))->open($acceptance['acceptance_id'],$augur,$issued->modify('+2 minutes'));self::assertSame('ORACLE_MODEL_EVALUATION_CASE_OPENED_PENDING_AUGUR_ELIGIBILITY_FINDINGS',$case['status']);self::assertSame(['deepseek/delegate@2026-08-01'],$case['included_candidates']);self::assertTrue($case['candidate_universe_frozen']);self::assertSame($oracle['record_digest'],$case['source_commission']['digest']);foreach(['recommendation_authority','ranking_authority','selection_authority','model_assignment_authority','profile_mutation_authority','provider_invocation_authority','deployment_authority','execution_authority']as$field)self::assertFalse($case[$field],$field.' must remain false');$model='deepseek/delegate@2026-08-01';$finding=(new ModelEligibilityFindingService($root))->issue($case['case_id'],$case['eligibility_authorities'][$model]['authority_id'],$augur,'ELIGIBLE',$this->delegateCriterionFindings(),['delegate-source'],['delegate-claim'],[],$issued->modify('+3 minutes'));self::assertSame('ORACLE_MODEL_ELIGIBILITY_FINDING_SEALED_NO_SELECTION_AUTHORITY',$finding['status']);$phase=$this->onlyDelegateRecord($root.'/var/imperium/offices/oracle/model-eligibility-phases');self::assertSame('ORACLE_ELIGIBILITY_FINDINGS_COMPLETE_PENDING_COMPARATIVE_ASSESSMENT',$phase['status']);$comparison=(new ModelComparativeAssessmentService($root))->seal($phase['phase_id'],$augur,$this->delegateComparisonMatrix(),$issued->modify('+4 minutes'));self::assertSame('ORACLE_COMPARATIVE_ASSESSMENT_SEALED_PENDING_AUGUR_RECOMMENDATION',$comparison['status']);self::assertNull($comparison['winner']);self::assertNull($comparison['ordinal_ranking']);$recommendation=(new ModelRecommendationService($root))->issue($comparison['assessment_id'],$comparison['recommendation_authority']['authority_id'],$augur,'RECOMMEND_MODEL',$model,$this->delegateRecommendationRationale(),[$model=>['recommendation_role'=>'RECOMMENDED','rationale'=>'Only frozen eligible candidate satisfies every authorized criterion.','advantages'=>['Evidence supports the bounded mission fit.'],'disadvantages'=>[],'limitations'=>['Bound to the pinned snapshot.'],'contradictions'=>[]]],$issued->modify('+5 minutes'));self::assertSame('ORACLE_MODEL_RECOMMENDATION_SEALED_PENDING_CURIA_SELECTION_DECISION',$recommendation['status']);self::assertSame($model,$recommendation['recommended_model']);self::assertFalse($recommendation['selection_authority']);self::assertFalse($recommendation['provider_invocation_authority']);$selection=(new DelegateMissionModelSelectionDecisionService($root))->decide($recommendation['recommendation_id'],$recommendation['curia_selection_decision_authority']['authority_id'],$seneschal,'SELECT_ELIGIBLE_MODEL',$model,['temperature'=>0.2],'Select the exact evidence-bound recommendation for this Delegate turn.',$issued->modify('+6 minutes'));self::assertSame('DELEGATE_MISSION_MODEL_SELECTED_PENDING_CONSCRIPTION_BINDING_SEAL',$selection['status']);self::assertSame($model,$selection['selected_model']);self::assertTrue($selection['model_binding_sealing_authority']['authority_exercisable']);foreach(['model_assignment_authority','profile_mutation_authority','credential_release_authority','provider_invocation_authority','resource_authority','external_action_authority','execution_authority']as$field)self::assertFalse($selection[$field],$field.' must remain false');$binding=(new DelegateMissionModelBindingSealingService($root,new StateStore($root)))->seal($selection['decision_id'],$selection['model_binding_sealing_authority']['authority_id'],$issued->modify('+7 minutes'));self::assertSame('DELEGATE_MISSION_MODEL_BINDING_SEALED_PENDING_ACCESS_ATTESTATION',$binding['status']);self::assertSame($model,$binding['provider_model_version']);self::assertSame(1,$binding['target']['turn_sequence']);self::assertSame($commission['manifestation_id'],$binding['target']['manifestation_id']);self::assertTrue($binding['model_access_attestation_authority']['authority_exercisable']);foreach(['profile_mutated','model_assigned','access_attested','credential_released','provider_invoked','resource_available','external_action_authorized','execution_authority']as$field)self::assertFalse($binding[$field],$field.' must remain false');$assertion=['schema'=>'imperium.clavium-provider-access-assertion/v1','assertion_id'=>'clavium-provider-access-'.str_repeat('9',20),'issuer'=>['seat'=>'clavium.locksmith'],'provider'=>'deepseek','credential_ref'=>'clavium://providers/deepseek/default','scope'=>['model.invoke'],'observation'=>['method'=>'sterile-test-presence','observed_at'=>$issued->modify('+7 minutes')->format(DATE_ATOM),'evidence'=>['configured'=>true]],'status'=>'ACCESS_AVAILABLE','checkpoint'=>'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY','restrictions'=>['mission-bound'],'revalidation'=>['expires_at'=>$issued->modify('+2 hours')->format(DATE_ATOM)],'credential_possession_transferred'=>false,'credential_use_authority'=>false,'credential_disclosure_authority'=>false,'provider_invocation_authority'=>false,'execution_authority'=>false,'sealed'=>true];$assertion['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($assertion));$this->write($root.'/var/imperium/offices/clavium/provider-access-assertions/'.$assertion['assertion_id'].'.json',$assertion);$locksmith='clavium-locksmith-binding-'.str_repeat('8',20);$this->write($root.'/var/imperium/offices/clavium/occupancy/'.$locksmith.'.json',$this->record(['schema'=>'imperium.clavium-locksmith-occupancy/v1','binding_id'=>$locksmith,'instance_id'=>'imperium-test','seat'=>'clavium.locksmith','manifestation_id'=>'manifestation-locksmith','occupancy_generation'=>1,'status'=>'ACTIVE','delegate_mission_model_access_attestation_authority'=>true,'delegate_mission_provider_invocation_activation_authority'=>true,'credential_disclosure_authority'=>false,'execution_authority'=>false]));$attestation=(new DelegateMissionModelAccessAttestationService($root))->attest($binding['binding_id'],$binding['model_access_attestation_authority']['authority_id'],$assertion['assertion_id'],$locksmith,$issued->modify('+8 minutes'));self::assertSame('DELEGATE_MISSION_MODEL_ACCESS_ATTESTED_PENDING_RESOURCE_AND_INVOCATION_DECISION',$attestation['status']);self::assertFalse($attestation['credential_released']);$resourceDecision=(new DelegateMissionResourceInvocationDecisionService($root))->decide($attestation['attestation_id'],$attestation['imperator_resource_invocation_decision_authority']['authority_id'],'AUTHORIZED','Authorize only the exact attested model and frozen turn-one requirements.',$issued->modify('+9 minutes'));self::assertSame('DELEGATE_MISSION_RESOURCE_AND_INVOCATION_AUTHORIZED_PENDING_SCOPED_ACTIVATION',$resourceDecision['status']);self::assertTrue($resourceDecision['provider_invocation_activation_authority']['authority_exercisable']);foreach(['credential_released','provider_invocation_authority','resource_released','external_action_authority','execution_authority']as$field)self::assertFalse($resourceDecision[$field]);$activation=(new DelegateMissionProviderInvocationActivationService($root))->activate($resourceDecision['decision_id'],$resourceDecision['provider_invocation_activation_authority']['authority_id'],$locksmith,$issued->modify('+10 minutes'));self::assertSame('DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN',$activation['status']);self::assertFalse($activation['credential_lease']['credential_reference_disclosed']);self::assertFalse($activation['credential_lease']['credential_possession_transferred']);self::assertTrue($activation['bounded_cognition_turn_authority']['authority_exercisable']);foreach(['provider_invoked','cognition_performed','resource_consumed','external_action_authority','execution_authority','continuing_turn_authority']as$field)self::assertFalse($activation[$field]);
            $gateway=new class implements DelegateMissionCognitionGateway{public function invoke(array$activation,array$commission):array{\PHPUnit\Framework\Assert::assertSame('ai.platform.generic.deepseek',$activation['model']['runtime_binding']['platform_service']);\PHPUnit\Framework\Assert::assertSame('deepseek-v4-flash',$activation['model']['runtime_binding']['runtime_model']);\PHPUnit\Framework\Assert::assertSame(1,$commission['commission_contract']['turn_sequence']);return['disposition'=>'COMPLETED','output'=>'The bounded usability assessment is complete.','evidence_references'=>['delegate-source'],'uncertainties'=>[],'stop_condition_triggered'=>false,'stop_rationale'=>null];}};
            $turn=(new DelegateMissionBoundedCognitionTurnService($root,$gateway))->execute($activation['activation_id'],$activation['bounded_cognition_turn_authority']['authority_id'],$issued->modify('+11 minutes'));
            self::assertSame('DELEGATE_MISSION_BOUNDED_COGNITION_TURN_COMPLETE_PENDING_CURIA_DISPOSITION',$turn['status']);self::assertTrue($turn['provider_invoked']);self::assertTrue($turn['cognition_performed']);self::assertTrue($turn['maximum_turns_consumed']);self::assertTrue($turn['credential_lease']['consumed']);self::assertTrue($turn['curia_result_disposition_authority']['authority_exercisable']);foreach(['credential_use_authority','provider_invocation_authority','tool_use_authority','perimeter_crossing_authority','external_action_authority','execution_authority','continuing_turn_authority']as$field)self::assertFalse($turn[$field]);
        } finally {$this->remove($root);}
    }

    private function delegateModelCriteria(): array
    {
        return['cognitive_task'=>'Perform the exact sealed Delegate mission turn.','required_capabilities'=>['structured-output','evidence-grounding'],'prohibited_capabilities'=>['autonomous-tool-use'],'required_tools'=>[],'minimum_context_tokens'=>32000,'data_classification'=>'INTERNAL','data_residency'=>'ANY_APPROVED_REGION','permitted_providers'=>['deepseek'],'max_cost_per_million_tokens'=>25,'max_latency_ms'=>15000,'minimum_reliability'=>0.99,'fallback_policy'=>'Return to Curia if no candidate qualifies.','substitution_policy'=>'SILENT_SUBSTITUTION_PROHIBITED','evaluation_rubric'=>['capability-fit','reliability','cost','latency','risk'],'minimum_evidence_sources'=>1];
    }

    private function delegateOracleSnapshot(): array
    {
        return$this->record(['schema'=>'imperium.oracle-model-intelligence-snapshot/v1','snapshot_id'=>'oracle-model-intelligence-'.str_repeat('d',20),'snapshot_generation'=>1,'prior_snapshot'=>null,'instance_id'=>'imperium-test','steward'=>'oracle','actor'=>['office'=>'oracle','seat'=>'oracle.augur'],'models'=>['deepseek/delegate@2026-08-01'=>['provider'=>'deepseek','platform_service'=>'ai.platform.generic.deepseek','runtime_model'=>'deepseek-v4-flash','knowledge'=>['sources'=>['delegate-source'=>['source_id'=>'delegate-source']],'claims'=>['delegate-claim'=>['claim_id'=>'delegate-claim']]],'accessibility'=>['status'=>'ACCESSIBLE'],'admissibility'=>['status'=>'ADMISSIBLE']]],'classification_dimensions'=>['knowledge','accessibility','admissibility'],'status'=>'ORACLE_CANONICAL_CATALOGUE_SNAPSHOT_SEALED_NO_SELECTION_AUTHORITY','model_research_authority'=>false,'selection_authority'=>false]);
    }

    private function delegateCriterionFindings(): array
    {
        $out=[];foreach($this->delegateModelCriteria()['evaluation_rubric']as$criterion)$out[$criterion]=['disposition'=>'SATISFIED','rationale'=>'Pinned catalogue evidence satisfies the authorized criterion.'];return$out;
    }

    private function delegateComparisonMatrix(): array
    {
        $cell=['observation'=>'The pinned evidence satisfies the authorized criterion.','evidence_strength'=>'HIGH','source_ids'=>['delegate-source'],'claim_ids'=>['delegate-claim'],'limitations'=>['Bound to the pinned snapshot.'],'contradictions'=>[]];$out=[];foreach($this->delegateModelCriteria()['evaluation_rubric']as$criterion)$out[$criterion]=['deepseek/delegate@2026-08-01'=>$cell];return$out;
    }

    private function delegateRecommendationRationale(): array
    {
        $out=[];foreach($this->delegateModelCriteria()['evaluation_rubric']as$criterion)$out[$criterion]=['rationale'=>'Evidence supports recommendation without selection.','evidence_refs'=>['delegate-source','delegate-claim']];return$out;
    }

    private function onlyDelegateRecord(string$directory): array
    {
        $files=glob($directory.'/*.json')?:[];self::assertCount(1,$files);return json_decode((string)file_get_contents($files[0]),true,512,JSON_THROW_ON_ERROR);
    }

    private function acceptedDelegateMissionControlFixture(): array
    {
        [$root,$transition]=$this->deployedDelegateMissionFixture();$activation=(new DelegateMissionRuntimeActivationService($root,new StateStore($root)))->activate($transition['transition_id'],new \DateTimeImmutable());$disposition=(new DelegateMissionControlIntakeDispositionService($root))->decide($activation['activation_id'],'curia-seneschal-binding-'.str_repeat('a',20),'ACCEPTED','Accept exact active Delegate mission control.',new \DateTimeImmutable());return[$root,$disposition];
    }

    private function deployedDelegateMissionFixture(): array
    {
        [$root,$senate]=$this->delegateSenateDispositionFixture(false,'APPROVED');$approval=(new DelegateMissionProfileApprovalDecisionService($root))->decide($senate['disposition_id'],'APPROVED','Approve exact Delegate Profile.','Qualification request only.',new \DateTimeImmutable());$state=new StateStore($root);$q=(new DelegateMissionOperationalProfileQualificationService($root,$state))->qualify($approval['decision_id'],new \DateTimeImmutable());$a=(new DelegateMissionOperationalManifestationAssemblyService($root,$state))->assemble($q['qualification_id'],new \DateTimeImmutable());$binding=(new DelegateMissionOperationalManifestationSeatBindingService($root,$state))->bind($a['assembly_id'],new \DateTimeImmutable());$seneschal='curia-seneschal-binding-'.str_repeat('a',20);$this->write($root.'/var/imperium/offices/curia/occupancy/'.$seneschal.'.json',$this->record(['schema'=>'imperium.curia-seneschal-occupancy/v1','binding_id'=>$seneschal,'instance_id'=>'imperium-test','seat'=>'curia.seneschal','officer_class'=>'LEGATE','manifestation_id'=>'manifestation-seneschal','occupancy_generation'=>1,'status'=>'ACTIVE','delegate_mission_deployment_authorization_authority'=>true,'delegate_mission_control_intake_disposition_authority'=>true,'delegate_mission_cognition_commission_construction_authority'=>true,'delegate_mission_resource_invocation_readiness_assessment_authority'=>true,'delegate_mission_model_criteria_request_authority'=>true,'delegate_mission_oracle_commission_issuance_authority'=>true,'delegate_mission_model_selection_decision_authority'=>true,'execution_authority'=>false,'sealed'=>true]));$authorization=(new DelegateMissionDeploymentAuthorizationService($root))->decide($binding['binding_id'],$seneschal,'AUTHORIZED','Authorize the exact bounded mission deployment.',new \DateTimeImmutable());$transition=(new DelegateMissionOperationalCustodyTransitionService($root))->transition($authorization['authorization_id'],'garrison-constable-binding-'.str_repeat('7',20),new \DateTimeImmutable());return[$root,$transition];
    }

    private function delegateSenateDispositionFixture(bool $securityFails,string $disposition): array
    {
        [$root,$reconciliation]=$this->delegateFindingReconciliationFixture($securityFails);$binding='senate-lord-speaker-binding-'.str_repeat('4',20);$opening=(new DelegateMissionDispositionAuthorityOpeningService($root))->open($reconciliation['reconciliation_id'],$binding,new \DateTimeImmutable());$cognition=new class($disposition) implements ProfileExaminationDispositionCognitionGateway {public function __construct(private string$disposition){}public function decide(array$authority,array$findings,array$reconciliation):array{return['disposition'=>$this->disposition,'finding_references'=>$authority['available_finding_references'],'limitations'=>[],'rationale'=>'Issue the exact bounded Senate disposition.','reconciliation_treatment'=>'Preserve the complete reconciliation unchanged.','uncertainties'=>[]];}};

        return[$root,(new DelegateMissionSenateDispositionService($root,$cognition))->decide($opening['opening_id'],$binding,new \DateTimeImmutable())];
    }

    private function delegateFindingReconciliationFixture(bool $securityFails=true): array
    {
        [$root, $readiness] = $this->delegateFindingReadinessFixture($securityFails);
        $binding = 'senate-lord-speaker-binding-'.str_repeat('4', 20);
        $opening = (new DelegateMissionDeliberationOpeningService($root))->open($readiness['readiness_id'], $binding, new \DateTimeImmutable());
        $cognition = new class implements ProfileExaminationReconciliationCognitionGateway {
            public function reconcile(array $authority, array $findings): array
            {
                return ['agreements' => ['Same sealed candidate.'], 'attribution_treatment' => ['Preserve every attribution unchanged.'], 'disagreements' => $authority['mandatory_security_blocking_condition'] ? ['Security fails while peers pass.'] : [], 'finding_references' => $authority['available_finding_references'], 'limitations' => [], 'mandatory_security_blocking_condition_preserved' => $authority['mandatory_security_blocking_condition'], 'rationale' => 'Preserve the exact independent findings.', 'severity_treatment' => ['Preserve every severity unchanged.'], 'uncertainties' => []];
            }
        };

        return [$root, (new DelegateMissionFindingReconciliationService($root, $cognition))->reconcile($opening['deliberation_id'], $binding, new \DateTimeImmutable())];
    }

    private function delegateFindingReadinessFixture(bool $securityFails=true): array
    {
        [$root, $usabilityTurn, $bindings] = $this->usabilityTestimonyTurnFixture();
        $opening = (new DelegateMissionFindingAuthorityOpeningService($root))->open($usabilityTurn['turn_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), new \DateTimeImmutable());
        $cognition = new class($securityFails) implements ProfileExaminationFindingCognitionGateway {
            public function __construct(private bool $securityFails) {}
            public function find(string $jurisdiction, array $authority, array $evidence): array
            {
                $blocking = $this->securityFails && 'security' === $jurisdiction;
                return ['disposition' => $blocking ? 'FAIL' : 'PASS', 'attributed_defect' => $blocking ? 'profile_elaboration' : null, 'evidence_references' => $evidence['available_evidence_references'], 'rationale' => $blocking ? 'Security failure.' : 'Pass.', 'severity' => $blocking ? 'HIGH' : 'NONE', 'limitations' => [], 'uncertainty' => []];
            }
        };
        $service = new DelegateMissionSenatorFindingService($root, $cognition); $readiness = null;
        foreach (['trust', 'security', 'usability'] as $jurisdiction) $readiness = $service->issue($opening['opening_id'], $jurisdiction, $bindings[$jurisdiction], new \DateTimeImmutable())['readiness'] ?? $readiness;

        return [$root, $readiness];
    }

    private function usabilityTestimonyTurnFixture(): array
    {
        [$root, $commission, $usabilityBindingId] = $this->usabilityQuestionCommissionFixture();
        $disposition = (new DelegateMissionUsabilityQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $usabilityBindingId, 'ACCEPTED', 'Accept.', new \DateTimeImmutable());
        $questionCognition = new class implements ProfileExaminationQuestionCognitionGateway {
            public function authorQuestion(string $jurisdiction, array $commission, array $opening): array { return ['purpose' => 'Examine usability.', 'question' => 'How will you preserve useful bounded output?']; }
        };
        $question = (new DelegateMissionUsabilityQuestionAuthorshipService($root, $questionCognition))->author($disposition['disposition_id'], $usabilityBindingId, new \DateTimeImmutable());
        $decision = (new DelegateMissionUsabilityQuestionDispatchAuthorizationService($root))->decide($question['question_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), 'AUTHORIZED', 'Authorize.', new \DateTimeImmutable());
        $dispatch = (new DelegateMissionUsabilityQuestionDispatchService($root))->dispatch($decision['decision_id'], 'senate-bailiff-binding-'.str_repeat('3', 20), new \DateTimeImmutable());
        $testimony = new class implements ProfileExaminationTestimonyCognitionGateway {
            public function answer(array $question, array $manifestation): array { return ['answer' => 'I preserve the output contract.', 'evidence_claims' => [], 'refusals' => [], 'uncertainties' => []]; }
        };
        $turn = (new DelegateMissionUsabilityTestimonyResponseService($root, $testimony))->respond($dispatch['dispatch_id'], new \DateTimeImmutable());

        return [$root, $turn, [
            'trust' => 'senate-committee-trust-binding-'.str_repeat('5', 20),
            'security' => 'senate-committee-security-binding-'.str_repeat('8', 20),
            'usability' => $usabilityBindingId,
        ]];
    }

    private function usabilityQuestionCommissionFixture(): array
    {
        [$root, $securityTurn] = $this->securityTestimonyTurnFixture();
        $usabilitySenatorBindingId = 'senate-committee-usability-binding-'.str_repeat('9', 20);
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$usabilitySenatorBindingId.'.json', $this->record([
            'schema' => 'imperium.senate-committee-occupancy/v1', 'binding_id' => $usabilitySenatorBindingId,
            'instance_id' => 'imperium-test', 'office' => 'senate', 'seat' => 'senate.committee.usability',
            'officer_class' => 'LEGATE', 'manifestation_id' => 'manifestation-usability-senator', 'occupancy_generation' => 1,
            'status' => 'ACTIVE', 'binding_atomic' => true,
            'delegate_question_commission_acceptance_disposition_authority' => true,
            'senator_question_authority' => true, 'execution_authority' => false, 'sealed' => true,
            'senator_finding_authority' => true,
        ]));
        $commission = (new DelegateMissionUsabilityQuestionCommissionIssuanceService($root))->issue($securityTurn['turn_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), $usabilitySenatorBindingId, new \DateTimeImmutable());

        return [$root, $commission, $usabilitySenatorBindingId];
    }

    private function securityTestimonyTurnFixture(): array
    {
        [$root, $commission, $securitySenatorBindingId] = $this->securityQuestionCommissionFixture();
        $disposition = (new DelegateMissionSecurityQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $securitySenatorBindingId, 'ACCEPTED', 'Accept exact bounded security question commission.', new \DateTimeImmutable());
        $questionCognition = new class implements ProfileExaminationQuestionCognitionGateway {
            public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
            {
                return ['purpose' => 'Examine security.', 'question' => 'How will you preserve protected capability boundaries?'];
            }
        };
        $question = (new DelegateMissionSecurityQuestionAuthorshipService($root, $questionCognition))->author($disposition['disposition_id'], $securitySenatorBindingId, new \DateTimeImmutable());
        $decision = (new DelegateMissionSecurityQuestionDispatchAuthorizationService($root))->decide($question['question_id'], 'senate-lord-speaker-binding-'.str_repeat('4', 20), 'AUTHORIZED', 'Authorize.', new \DateTimeImmutable());
        $dispatch = (new DelegateMissionSecurityQuestionDispatchService($root))->dispatch($decision['decision_id'], 'senate-bailiff-binding-'.str_repeat('3', 20), new \DateTimeImmutable());
        $testimonyCognition = new class implements ProfileExaminationTestimonyCognitionGateway {
            public function answer(array $question, array $manifestation): array
            {
                return ['answer' => 'I stop without exact protected capability authority.', 'evidence_claims' => [], 'refusals' => [], 'uncertainties' => []];
            }
        };
        $turn = (new DelegateMissionSecurityTestimonyResponseService($root, $testimonyCognition))->respond($dispatch['dispatch_id'], new \DateTimeImmutable());

        return [$root, $turn];
    }

    private function securityQuestionCommissionFixture(): array
    {
        [$root, $turn, $lordSpeakerBindingId, $securitySenatorBindingId] = $this->trustTestimonyTurnFixture();
        $commission = (new DelegateMissionSecurityQuestionCommissionIssuanceService($root))->issue($turn['turn_id'], $lordSpeakerBindingId, $securitySenatorBindingId, new \DateTimeImmutable());

        return [$root, $commission, $securitySenatorBindingId];
    }

    private function trustTestimonyTurnFixture(): array
    {
        [$root, $dispatch] = $this->dispatchedTrustQuestionFixture();
        $cognition = new class implements ProfileExaminationTestimonyCognitionGateway {
            public function answer(array $question, array $manifestation): array
            {
                return ['answer' => 'I preserve scope.', 'evidence_claims' => [], 'refusals' => [], 'uncertainties' => []];
            }
        };
        $turn = (new DelegateMissionTrustTestimonyResponseService($root, $cognition))->respond($dispatch['dispatch_id'], new \DateTimeImmutable());
        $securitySenatorBindingId = 'senate-committee-security-binding-'.str_repeat('8', 20);
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$securitySenatorBindingId.'.json', $this->record([
            'schema' => 'imperium.senate-committee-occupancy/v1', 'binding_id' => $securitySenatorBindingId,
            'instance_id' => 'imperium-test', 'office' => 'senate', 'seat' => 'senate.committee.security',
            'officer_class' => 'LEGATE', 'manifestation_id' => 'manifestation-security-senator', 'occupancy_generation' => 1,
            'status' => 'ACTIVE', 'binding_atomic' => true,
            'delegate_question_commission_acceptance_disposition_authority' => true,
            'senator_question_authority' => true, 'execution_authority' => false, 'sealed' => true,
            'senator_finding_authority' => true,
        ]));

        return [$root, $turn, 'senate-lord-speaker-binding-'.str_repeat('4', 20), $securitySenatorBindingId];
    }

    private function dispatchedTrustQuestionFixture(): array
    {
        [$root, $decision, $bailiffBindingId] = $this->authorizedTrustQuestionDispatchFixture();
        $dispatch = (new DelegateMissionTrustQuestionDispatchService($root))->dispatch($decision['decision_id'], $bailiffBindingId, new \DateTimeImmutable());

        return [$root, $dispatch];
    }

    private function authorizedTrustQuestionDispatchFixture(): array
    {
        [$root, $question, $lordSpeakerBindingId] = $this->authoredTrustQuestionFixture();
        $decision = (new DelegateMissionTrustQuestionDispatchAuthorizationService($root))->decide($question['question_id'], $lordSpeakerBindingId, 'AUTHORIZED', 'Authorize dispatch of the exact sealed trust question.', new \DateTimeImmutable());

        return [$root, $decision, 'senate-bailiff-binding-'.str_repeat('3', 20)];
    }

    private function authoredTrustQuestionFixture(): array
    {
        [$root, $disposition, $trustSenatorBindingId] = $this->acceptedFirstQuestionCommissionFixture();
        $cognition = new class implements ProfileExaminationQuestionCognitionGateway {
            public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
            {
                return ['purpose' => 'Examine exact trust boundaries.', 'question' => 'How will you preserve the sealed authority boundary under incomplete evidence?'];
            }
        };
        $question = (new DelegateMissionTrustQuestionAuthorshipService($root, $cognition))->author($disposition['disposition_id'], $trustSenatorBindingId, new \DateTimeImmutable());

        return [$root, $question, 'senate-lord-speaker-binding-'.str_repeat('4', 20)];
    }

    private function acceptedFirstQuestionCommissionFixture(): array
    {
        [$root, $commission, $trustSenatorBindingId] = $this->firstQuestionCommissionFixture();
        $disposition = (new DelegateMissionFirstQuestionCommissionDispositionService($root))->decide($commission['commission_id'], $trustSenatorBindingId, 'ACCEPTED', 'Accept exact bounded trust question commission.', new \DateTimeImmutable());

        return [$root, $disposition, $trustSenatorBindingId];
    }

    private function firstQuestionCommissionFixture(): array
    {
        [$root, $opening, $lordSpeakerBindingId, $trustSenatorBindingId] = $this->examinationOpeningFixture();
        $commission = (new DelegateMissionFirstQuestionCommissionIssuanceService($root))->issue($opening['opening_id'], $lordSpeakerBindingId, $trustSenatorBindingId, new \DateTimeImmutable());

        return [$root, $commission, $trustSenatorBindingId];
    }

    private function examinationOpeningFixture(): array
    {
        [$root, $admission, $lordSpeakerBindingId] = $this->admittedExaminationManifestationFixture();
        $opening = (new DelegateMissionProfileExaminationOpeningService($root))->open($admission['disposition_id'], $lordSpeakerBindingId, new \DateTimeImmutable());
        $trustSenatorBindingId = 'senate-committee-trust-binding-'.str_repeat('5', 20);
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$trustSenatorBindingId.'.json', $this->record([
            'schema' => 'imperium.senate-committee-occupancy/v1',
            'binding_id' => $trustSenatorBindingId,
            'instance_id' => 'imperium-test',
            'office' => 'senate',
            'seat' => 'senate.committee.trust',
            'officer_class' => 'LEGATE',
            'manifestation_id' => 'manifestation-trust-senator',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'binding_atomic' => true,
            'delegate_question_commission_acceptance_disposition_authority' => true,
            'senator_question_authority' => true,
            'senator_finding_authority' => true,
            'execution_authority' => false,
            'sealed' => true,
        ]));

        return [$root, $opening, $lordSpeakerBindingId, $trustSenatorBindingId];
    }

    private function admittedExaminationManifestationFixture(): array
    {
        [$root, $delivery, $bailiffBindingId] = $this->examinationManifestationDeliveryFixture();
        $admission = (new DelegateMissionExaminationStandAdmissionDispositionService($root))->decide($delivery['delivery_id'], $bailiffBindingId, 'ADMITTED', 'Admit exact secured examination Manifestation.', new \DateTimeImmutable());

        return [$root, $admission, 'senate-lord-speaker-binding-'.str_repeat('4', 20)];
    }

    private function examinationManifestationDeliveryFixture(): array
    {
        [$root, $authorization] = $this->acceptedExaminationPreparationFixture();
        $delivery = (new DelegateMissionExaminationManifestationAssemblyService($root, new StateStore($root)))->assemble($authorization['disposition_id'], new \DateTimeImmutable());
        $bindingId = 'senate-bailiff-binding-'.str_repeat('3', 20);
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$bindingId.'.json', $this->record([
            'schema' => 'imperium.senate-bailiff-occupancy/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'office' => 'senate',
            'seat' => 'senate.bailiff',
            'officer_class' => 'LEGATE',
            'manifestation_id' => 'manifestation-bailiff',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'binding_atomic' => true,
            'delegate_examination_stand_intake_disposition_authority' => true,
            'proceeding_security_authority' => true,
            'delegate_examination_question_dispatch_authority' => true,
            'execution_authority' => false,
            'sealed' => true,
        ]));

        return [$root, $delivery, $bindingId];
    }

    private function acceptedExaminationPreparationFixture(): array
    {
        [$root, $handoff, $bindingId] = $this->examinationPreparationHandoffFixture();
        $authorization = (new DelegateMissionExaminationPreparationIntakeDispositionService($root))->decide($handoff['handoff_id'], $bindingId, 'ACCEPTED', 'Accept exact examination preparation.', new \DateTimeImmutable());

        return [$root, $authorization];
    }

    private function examinationPreparationHandoffFixture(): array
    {
        [$root, $intake] = $this->acceptedProfileCandidateIntakeFixture();
        $handoff = (new DelegateMissionExaminationPreparationHandoffService($root, new StateStore($root)))->prepare($intake['disposition_id'], new \DateTimeImmutable());
        $bindingId = 'senate-lord-speaker-binding-'.str_repeat('4', 20);
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$bindingId.'.json', $this->record([
            'schema' => 'imperium.senate-lord-speaker-occupancy/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'office' => 'senate',
            'seat' => 'senate.lord-speaker',
            'officer_class' => 'LEGATE',
            'manifestation_id' => 'manifestation-lord-speaker',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'binding_atomic' => true,
            'delegate_examination_preparation_intake_disposition_authority' => true,
            'delegate_profile_examination_opening_authority' => true,
            'delegate_first_question_commission_issuance_authority' => true,
            'delegate_subsequent_question_commission_issuance_authority' => true,
            'delegate_finding_phase_opening_authority' => true,
            'delegate_deliberation_opening_authority' => true,
            'delegate_disposition_phase_opening_authority' => true,
            'delegate_question_dispatch_authorization_disposition_authority' => true,
            'execution_authority' => false,
            'sealed' => true,
        ]));

        return [$root, $handoff, $bindingId];
    }

    private function acceptedProfileCandidateIntakeFixture(): array
    {
        [$root, $result] = $this->derivedProfileCandidateFixture();
        $intake = (new DelegateMissionProfileCandidateIntakeDispositionService($root, new StateStore($root)))->decide($result['return']['return_id'], 'ACCEPTED', 'Accept exact sealed candidate.', new \DateTimeImmutable());

        return [$root, $intake];
    }

    private function derivedProfileCandidateFixture(): array
    {
        [$root, $disposition] = $this->acceptedLaboratoriumCommissionFixture();
        $result = (new DelegateMissionProfileCandidateDerivationReturnService($root, $this->profileElaboration()))->deriveAndReturn($disposition['disposition_id'], new \DateTimeImmutable());

        return [$root, $result];
    }

    private function profileElaboration(): ProfileElaborationCognitionGateway
    {
        return new class implements ProfileElaborationCognitionGateway {
            public function elaborate(array $acceptance, array $authorization): array
            {
                return [
                    'disposition' => 'PROFILE_ELABORATION_COMPLETE',
                    'operating_posture' => 'Operate only within the exact passive assessment scope.',
                    'responsibilities' => ['Perform the exact bounded assessment.'],
                    'non_responsibilities' => ['Do not execute external changes.'],
                    'reasoning_priorities' => ['Preserve evidence and scope.'],
                    'evidence_discipline' => ['Cite every finding.'],
                    'tool_use_directives' => ['Request separately authorized tools only.'],
                    'input_handling' => ['Reject inputs outside the approved perimeter.'],
                    'output_contract' => ['Return one evidence-backed report.'],
                    'escalation_conditions' => ['Escalate on authentication boundaries.'],
                    'uncertainty_behavior' => ['State uncertainty explicitly.'],
                    'failure_behavior' => ['Stop and return without improvisation.'],
                    'persona_adaptations' => ['Apply passive assessment discipline.'],
                ];
            }
        };
    }

    private function acceptedLaboratoriumCommissionFixture(): array
    {
        [$root, $commission, $bindingId] = $this->laboratoriumCommissionFixture();
        $disposition = (new DelegateMissionProfileDerivationCommissionDispositionService($root))->decide($commission['request_id'], $bindingId, 'ACCEPTED', 'Accept exact custody-bound derivation commission.', new \DateTimeImmutable());

        return [$root, $disposition];
    }

    private function laboratoriumCommissionFixture(): array
    {
        [$root, $decision, $bootstrap] = $this->authorizedProfileScopeDecisionFixture();
        $commission = (new DelegateMissionProfileDerivationCommissionRequestService($root, $bootstrap))->decide($decision['decision_id'], 'ACCEPTED', 'Accept exact authorized scope.', new \DateTimeImmutable())['commission_request'];
        $bindingId = 'laboratorium-alchemist-binding-'.str_repeat('6', 20);
        $this->write($root.'/var/imperium/offices/laboratorium/occupancy/'.$bindingId.'.json', $this->record([
            'schema' => 'imperium.laboratorium-alchemist-occupancy/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'office' => 'laboratorium',
            'seat' => 'laboratorium.alchemist',
            'officer_class' => 'LEGATE',
            'manifestation_id' => 'manifestation-alchemist',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'binding_atomic' => true,
            'profile_derivation_commission_acceptance_authority' => true,
            'execution_authority' => false,
            'sealed' => true,
        ]));

        return [$root, $commission, $bindingId];
    }

    private function authorizedProfileScopeDecisionFixture(): array
    {
        [$root, $request] = $this->profileScopeRequestFixture();
        $decision = (new DelegateMissionProfileScopeDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize.', 'No scope expansion.', new \DateTimeImmutable());
        $bootstrap = new StateStore($root);
        $bootstrap->write([
            'state' => 'CURIA_READY',
            'binding' => ['instance_id' => 'imperium-test'],
            'events' => [[
                'transition' => 'T04',
                'result' => 'SUCCESS',
                'output' => ['successor' => ['manifestation_id' => 'manifestation-recruiter', 'seat' => 'conscription.recruiter', 'occupancy_generation' => 2, 'authority' => 'ordinary-recruiter']],
            ]],
        ]);

        return [$root, $decision, $bootstrap];
    }

    private function profileScopeRequestFixture(): array
    {
        [$root, $reservationRequest, $constableBindingId] = $this->reservationRequestFixture();
        $reservation = (new DelegateMissionPersonaReservationDispositionService($root))->decide($reservationRequest['request_id'], $constableBindingId, new \DateTimeImmutable());
        $request = (new DelegateMissionProfileScopeAuthorizationRequestService($root))->construct($reservation['disposition_id'], new \DateTimeImmutable());

        return [$root, $request];
    }

    private function personnelUseRequestFixture(): array
    {
        [$root, $demandId, $bindingId, $responseId, $custodyId] = $this->fixtures();
        $intake = (new DelegateMissionCapabilityDemandIntakeService($root))->decide($demandId, $bindingId, 'ACCEPTED', 'Accepted.', new \DateTimeImmutable());
        $resolution = (new DelegateMissionPersonnelResolutionService($root))->resolve($intake['disposition_id'], $bindingId, $responseId, 'Passive web application security assessor', $custodyId, 'SUITABLE', ['Passive assessment discipline'], ['garrison-custody-fact'], 'The exact Persona is suitable.', new \DateTimeImmutable());
        $request = (new DelegateMissionPersonnelUseRequestService($root))->present($resolution['resolution_id'], new \DateTimeImmutable());

        return [$root, $request, $bindingId];
    }

    private function reservationRequestFixture(bool $available = true): array
    {
        [$root, $request, $guildmasterBindingId] = $this->personnelUseRequestFixture();
        $decision = (new DelegateMissionPersonnelUseDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize.', 'Exact disclosed mission bounds.', new \DateTimeImmutable());
        $reservationRequest = (new DelegateMissionPersonnelUseAcceptanceService($root))->accept($decision['decision_id'], $guildmasterBindingId, new \DateTimeImmutable())['reservation_request'];
        $custodyId = $reservationRequest['personnel_commitment']['persona']['custody_id'];
        if (!$available) {
            $path = $root.'/var/imperium/offices/garrison/custody/'.$custodyId.'.json';
            $custody = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            unset($custody['record_digest']);
            $custody['available'] = false;
            $this->write($path, $this->record($custody));
        }
        $constableBindingId = 'garrison-constable-binding-'.str_repeat('7', 20);
        $this->write($root.'/var/imperium/offices/garrison/occupancy/'.$constableBindingId.'.json', $this->record([
            'schema' => 'imperium.garrison-constable-occupancy/v1',
            'binding_id' => $constableBindingId,
            'instance_id' => 'imperium-test',
            'seat' => 'garrison.constable',
            'officer_class' => 'LEGATE',
            'manifestation_id' => 'manifestation-constable',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'persona_reservation_disposition_authority' => true,
            'delegate_mission_operational_custody_transition_authority' => true,
            'selection_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]));

        return [$root, $reservationRequest, $constableBindingId, $custodyId];
    }

    private function fixtures(): array
    {
        $root = sys_get_temp_dir().'/imperium-delegate-guildhall-'.bin2hex(random_bytes(5));
        $demandId = 'delegate-mission-capability-demand-'.str_repeat('a', 20);
        $bindingId = 'guildhall-binding-'.str_repeat('b', 20);
        $responseId = 'garrison-response-'.str_repeat('c', 20);
        $custodyId = 'garrison-custody-passive-assessor';
        $demand = $this->record([
            'schema' => 'imperium.delegate-mission-capability-demand/v1',
            'demand_id' => $demandId,
            'instance_id' => 'imperium-test',
            'officer_class' => 'DELEGATE',
            'authority_source' => ['mission_authorization' => ['id' => 'mission-authorization-'.str_repeat('d', 20), 'digest' => str_repeat('1', 64)]],
            'mission_plan' => ['proceeding_id' => 'proceeding-delegate', 'turn_sequence' => 1, 'turn_digest' => str_repeat('2', 64), 'dossier_id' => 'curia-planning-dossier-'.str_repeat('e', 20), 'dossier_version' => 1, 'dossier_digest' => str_repeat('3', 64), 'plan_digest' => str_repeat('4', 64)],
            'demand' => [
                'objective' => 'Assess exact public surface.',
                'scope' => ['Public application behavior only'],
                'deliverables' => ['Sealed assessment report'],
                'constraints' => ['Passive observation only'],
                'required_inputs' => ['Approved public endpoint list'],
                'capability_requirements' => ['Analyze public behavior'],
                'expected_outcomes' => ['Evidence-backed findings'],
                'mission_seat' => 'mission.delegate.passive-assessment',
                'bounded_duration' => ['maximum' => 4, 'unit' => 'hours', 'starts_when' => 'Delegate deployment is authorized', 'expires_when' => 'Four hours elapse'],
                'data_requirements' => ['Public responses'],
                'tool_requirements' => ['Passive HTTP client'],
                'credential_requirements' => ['None'],
                'perimeter_requirements' => ['Approved public endpoints only'],
                'stop_conditions' => ['Unexpected authentication boundary'],
                'return_conditions' => ['Mission disposition or interruption'],
                'unbinding_conditions' => ['Return accepted'],
                'custody_restoration_conditions' => ['Restore Persona to Garrison custody'],
                'retirement_conditions' => ['Delegate unbound and manifestation terminated'],
            ],
            'consumer' => ['office' => 'guildhall', 'seat' => 'guildhall.guildmaster', 'intake_pending' => true, 'delivered' => false],
            'status' => 'DELEGATE_MISSION_CAPABILITY_DEMAND_SEALED_PENDING_GUILDHALL_INTAKE_NO_PERSONNEL_AUTHORITY',
            'guildhall_intake_authority' => false,
            'profession_determination_authority' => false,
            'persona_selection_authority' => false,
            'personnel_use_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $bindings = [];
        foreach (['guildhall.guildmaster', 'guildhall.committee.disciplinary-fit', 'guildhall.committee.composition', 'guildhall.committee.boundary-challenge'] as $seat) {
            $bindings[$seat] = ['seat' => $seat, 'officer_class' => 'LEGATE', 'manifestation_id' => 'guildhall.guildmaster' === $seat ? 'manifestation-guildmaster' : 'manifestation-'.substr(hash('sha256', $seat), 0, 12), 'occupancy_generation' => 1, 'status' => 'ACTIVE'];
        }
        $binding = $this->record([
            'schema' => 'imperium.guildhall-seat-binding-cohort/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'office' => 'guildhall',
            'bindings' => $bindings,
            'office_status' => 'ACTIVE',
            'binding_atomic' => true,
            'execution_authority' => false,
        ]);
        $custody = $this->record([
            'schema' => 'imperium.garrison-persona-custody/v1',
            'custody_id' => $custodyId,
            'instance_id' => 'imperium-test',
            'persona_id' => 'persona-passive-assessor',
            'persona_version' => '1.0.0',
            'persona_digest' => 'sha256:'.str_repeat('5', 64),
            'custody_state' => 'ADMITTED_HELD',
            'available' => true,
            'sealed' => true,
        ]);
        $response = $this->record([
            'schema' => 'imperium.garrison-inventory-response/v1',
            'response_id' => $responseId,
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-delegate',
            'source_inquiry_id' => 'garrison-inquiry-'.str_repeat('f', 20),
            'source_inquiry_digest' => str_repeat('6', 64),
            'responder' => ['office' => 'garrison', 'seat' => 'garrison.constable', 'manifestation_id' => 'manifestation-constable', 'occupancy_generation' => 1, 'occupancy_digest' => str_repeat('7', 64)],
            'recipient' => ['office' => 'guildhall', 'seat' => 'guildhall.guildmaster', 'manifestation_id' => 'manifestation-guildmaster', 'occupancy_generation' => 1],
            'inventory_records' => [$custody],
            'ledger_finding' => 'EXACT_ADMITTED_PERSONA_CUSTODY_RECORDS_ATTACHED',
            'status' => 'AUTHORITATIVE_INVENTORY_FACTS_DELIVERED',
            'authoritative_inventory_response' => true,
            'ranking_authority' => false,
            'selection_authority' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'spawning_authority' => false,
            'execution_authority' => false,
        ]);
        $this->write($root.'/var/imperium/offices/curia/delegate-mission-capability-demands/'.$demandId.'.json', $demand);
        $this->write($root.'/var/imperium/offices/guildhall/occupancy/'.$bindingId.'.json', $binding);
        $this->write($root.'/var/imperium/offices/guildhall/inventory-responses/'.$responseId.'.json', $response);
        $this->write($root.'/var/imperium/offices/garrison/custody/'.$custodyId.'.json', $custody);

        return [$root, $demandId, $bindingId, $responseId, $custodyId];
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
