<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\ProfileDerivationAuthorizationAcceptanceService;
use App\Imperium\Runtime\Conscription\LaboratoriumProfileDerivationCommissionService;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Curia\ProfileDerivationAuthorizationDecisionService;
use App\Imperium\Runtime\Curia\ProfileDerivationAuthorizationRequestService;
use App\Imperium\Runtime\Garrison\ProfileDerivationHandoffDispositionService;
use App\Imperium\Runtime\Laboratorium\ProfileDerivationCommissionAcceptanceService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProfileDerivationAuthorizationFlowTest extends TestCase
{
    public function testOccupiedAlchemistAcceptsExactCommissionAndOnlyThenMakesDerivationExercisable(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $constable = $this->constableOccupancy($root);
            $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $constable['binding_id'], 'APPROVED', 'Approve exact lease.');
            $commission = (new LaboratoriumProfileDerivationCommissionService($root, $bootstrap))->commission($disposition['disposition_id']);
            $alchemist = $this->alchemistOccupancy($root);
            $service = new ProfileDerivationCommissionAcceptanceService($root);
            $acceptance = $service->accept($commission['commission_id'], $alchemist['binding_id']);
            self::assertSame($acceptance, $service->accept($commission['commission_id'], $alchemist['binding_id']));
            self::assertSame('PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION', $acceptance['status']);
            self::assertSame('ACCEPTED_FOR_EXACT_PROFILE_DERIVATION', $acceptance['disposition']);
            self::assertSame($act['profile_scope'], $acceptance['profile_scope']);
            self::assertSame('ADMITTED_HELD', $acceptance['custody_lease']['custody_state']);
            self::assertTrue($acceptance['recipient_acceptance']);
            self::assertTrue($acceptance['profile_derivation_authority']);
            self::assertTrue($acceptance['profile_derivation_authority_exercisable']);
            self::assertTrue($acceptance['profile_candidate_creation_authority']);
            self::assertFalse($acceptance['profile_artifact_created']);
            self::assertFalse($acceptance['profile_approval_authority']);
            self::assertFalse($acceptance['profile_installation_authority']);
            self::assertFalse($acceptance['custody_release_authority']);
            self::assertFalse($acceptance['spawning_authority']);
            self::assertFalse($acceptance['deployment_authority']);
            self::assertFalse($acceptance['execution_authority']);
        } finally { $this->removeTree($root); }
    }

    public function testAlchemistAcceptanceRequiresActiveAuthorizedOccupancy(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $constable = $this->constableOccupancy($root);
            $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $constable['binding_id'], 'APPROVED', 'Approve exact lease.');
            $commission = (new LaboratoriumProfileDerivationCommissionService($root, $bootstrap))->commission($disposition['disposition_id']);
            $alchemist = $this->alchemistOccupancy($root, false);
            $this->expectExceptionMessage('L25_PROFILE_DERIVATION_COMMISSION_INVALID');
            (new ProfileDerivationCommissionAcceptanceService($root))->accept($commission['commission_id'], $alchemist['binding_id']);
        } finally { $this->removeTree($root); }
    }

    public function testAlchemistRefusesCommissionDriftAfterConscriptionIssuance(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $constable = $this->constableOccupancy($root);
            $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $constable['binding_id'], 'APPROVED', 'Approve exact lease.');
            $commission = (new LaboratoriumProfileDerivationCommissionService($root, $bootstrap))->commission($disposition['disposition_id']);
            $alchemist = $this->alchemistOccupancy($root);
            $path = $root.'/var/imperium/offices/laboratorium/profile-derivation-commission-inbox/'.$commission['commission_id'].'.json';
            $changed = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $changed['profile_scope']['constraints'][] = 'Injected constraint'; unset($changed['record_digest']);
            $changed['record_digest'] = hash('sha256', CanonicalJson::encode($changed));
            file_put_contents($path, json_encode($changed, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('L25_PROFILE_DERIVATION_COMMISSION_INVALID');
            (new ProfileDerivationCommissionAcceptanceService($root))->accept($commission['commission_id'], $alchemist['binding_id']);
        } finally { $this->removeTree($root); }
    }

    public function testRecruiterIssuesExactNonExercisableLaboratoriumCommissionFromApprovedLease(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $binding = $this->constableOccupancy($root);
            $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $binding['binding_id'], 'APPROVED', 'Approve the exact custody-bound derivation lease.');
            $service = new LaboratoriumProfileDerivationCommissionService($root, $bootstrap);
            $commission = $service->commission($disposition['disposition_id']);
            self::assertSame($commission, $service->commission($disposition['disposition_id']));
            self::assertSame('PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE', $commission['status']);
            self::assertSame('laboratorium.alchemist', $commission['recipient']['seat']);
            self::assertSame('DERIVE_ONE_EXACT_MISSION_PROFILE', $commission['commission_scope']);
            self::assertSame($act['profile_scope'], $commission['profile_scope']);
            self::assertSame('ADMITTED_HELD', $commission['custody_lease']['custody_state']);
            self::assertSame('garrison', $commission['custody_lease']['custodian']);
            self::assertFalse($commission['recipient_acceptance']);
            self::assertTrue($commission['profile_derivation_authority']);
            self::assertFalse($commission['profile_derivation_authority_exercisable']);
            self::assertFalse($commission['profile_artifact_authority']);
            self::assertFalse($commission['profile_approval_authority']);
            self::assertFalse($commission['profile_installation_authority']);
            self::assertFalse($commission['custody_release_authority']);
            self::assertFalse($commission['persona_substitution_authority']);
            self::assertFalse($commission['spawning_authority']);
            self::assertFalse($commission['deployment_authority']);
            self::assertFalse($commission['execution_authority']);
        } finally { $this->removeTree($root); }
    }

    public function testRecruiterCannotCommissionLaboratoriumFromRefusedLease(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $binding = $this->constableOccupancy($root);
            $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $binding['binding_id'], 'REFUSED', 'Refuse the lease.');
            $this->expectExceptionMessage('R89_PROFILE_DERIVATION_COMMISSION_CHAIN_INVALID');
            (new LaboratoriumProfileDerivationCommissionService($root, $bootstrap))->commission($disposition['disposition_id']);
        } finally { $this->removeTree($root); }
    }

    public function testRecruiterRefusesProfileScopeDriftAfterConstableApproval(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $binding = $this->constableOccupancy($root);
            $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $binding['binding_id'], 'APPROVED', 'Approve the exact custody-bound derivation lease.');
            $path = $root.'/var/imperium/offices/garrison/profile-derivation-handoff-inbox/'.$handoff['request_id'].'.json';
            $changed = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $changed['profile_scope']['objective'] = 'Changed objective'; unset($changed['record_digest']);
            $changed['record_digest'] = hash('sha256', CanonicalJson::encode($changed));
            file_put_contents($path, json_encode($changed, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('R89_PROFILE_DERIVATION_COMMISSION_CHAIN_INVALID');
            (new LaboratoriumProfileDerivationCommissionService($root, $bootstrap))->commission($disposition['disposition_id']);
        } finally { $this->removeTree($root); }
    }

    public function testConstableApprovesExactCustodyBoundDerivationHandoffWithoutReleasingCustody(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $binding = $this->constableOccupancy($root);
            $service = new ProfileDerivationHandoffDispositionService($root);
            $record = $service->decide($handoff['request_id'], $binding['binding_id'], 'APPROVED', 'Approve the exact custody-bound derivation lease.');
            self::assertSame($record, $service->decide($handoff['request_id'], $binding['binding_id'], 'APPROVED', 'Approve the exact custody-bound derivation lease.'));
            self::assertSame('PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION', $record['status']);
            self::assertSame('ADMITTED_HELD', $record['custody']['state']);
            self::assertSame('garrison', $record['custody']['retained_by']);
            self::assertSame('CUSTODY_BOUND_PROFILE_DERIVATION_ONLY', $record['lease_scope']);
            self::assertSame($act['profile_scope'], $record['profile_scope']);
            self::assertTrue($record['handoff_authority']);
            self::assertTrue($record['conscription_laboratorium_commission_request_authority']);
            self::assertFalse($record['custody_release_authority']);
            self::assertFalse($record['persona_substitution_authority']);
            self::assertFalse($record['profile_artifact_authority']);
            self::assertFalse($record['spawning_authority']);
            self::assertFalse($record['deployment_authority']);
            self::assertFalse($record['execution_authority']);
        } finally { $this->removeTree($root); }
    }

    public function testConstableRefusalCreatesNoDownstreamAuthority(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $binding = $this->constableOccupancy($root);
            $record = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $binding['binding_id'], 'REFUSED', 'Custody lease refused.');
            self::assertSame('PROFILE_DERIVATION_HANDOFF_REFUSED', $record['status']);
            self::assertFalse($record['handoff_authority']);
            self::assertFalse($record['conscription_laboratorium_commission_request_authority']);
            self::assertFalse($record['custody_release_authority']);
            self::assertFalse($record['execution_authority']);
        } finally { $this->removeTree($root); }
    }

    public function testConstableRefusesHandoffWhenCustodyDriftsAfterRequest(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
            $binding = $this->constableOccupancy($root);
            $path = $root.'/var/imperium/offices/garrison/custody/persona-custody-test.json';
            $custody = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $custody['available'] = false; unset($custody['record_digest']);
            $custody['record_digest'] = hash('sha256', CanonicalJson::encode($custody));
            file_put_contents($path, json_encode($custody, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('GA105_PROFILE_DERIVATION_HANDOFF_CHAIN_INVALID');
            (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $binding['binding_id'], 'APPROVED', 'Attempt approval after drift.');
        } finally { $this->removeTree($root); }
    }

    public function testOccupiedRecruiterAcceptsExactAuthorizedRouteAndRequestsOnlyConstableHandoff(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $service = new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap);
            $result = $service->accept($act['act_id']);
            self::assertSame($result, $service->accept($act['act_id']));

            $acceptance = $result['acceptance'];
            self::assertSame('PROFILE_DERIVATION_ACCEPTED_PENDING_CONSTABLE_HANDOFF_DISPOSITION', $acceptance['status']);
            self::assertSame('conscription.recruiter', $acceptance['actor']['seat']);
            self::assertSame($act['profile_scope'], $acceptance['profile_scope']);
            self::assertTrue($acceptance['garrison_handoff_request_authority']);
            self::assertFalse($acceptance['retrieval_authority']);
            self::assertFalse($acceptance['laboratorium_commission_authority']);

            $handoff = $result['handoff_request'];
            self::assertSame('PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION', $handoff['status']);
            self::assertSame('CUSTODY_BOUND_PROFILE_DERIVATION_ONLY', $handoff['requested_handoff']);
            self::assertSame(['custody_id' => 'persona-custody-test', 'persona_id' => 'persona-test'], $handoff['persona']);
            self::assertSame($act['profile_scope'], $handoff['profile_scope']);
            self::assertTrue($handoff['handoff_requested']);
            self::assertFalse($handoff['handoff_authority']);
            self::assertFalse($handoff['custody_release_authority']);
            self::assertFalse($handoff['laboratorium_commission_authority']);
            self::assertFalse($handoff['spawning_authority']);
            self::assertFalse($handoff['seat_binding_authority']);
            self::assertFalse($handoff['deployment_authority']);
            self::assertFalse($handoff['execution_authority']);
        } finally { $this->removeTree($root); }
    }

    public function testRecruiterRefusesNonAuthorizingDisposition(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'DEFERRED', 'Do not proceed.');
            $this->expectExceptionMessage('R76_PROFILE_DERIVATION_AUTHORIZATION_CHAIN_INVALID');
            (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id']);
        } finally { $this->removeTree($root); }
    }

    public function testRecruiterRefusesReservationDriftAfterAuthorization(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = $this->recruiterBootstrap($root);
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $path = $root.'/var/imperium/offices/garrison/persona-reservation-dispositions/'.$reservationId.'.json';
            $reservation = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $reservation['personnel_commitment']['profession'] = 'Substituted profession';
            unset($reservation['record_digest']);
            $reservation['record_digest'] = hash('sha256', CanonicalJson::encode($reservation));
            file_put_contents($path, json_encode($reservation, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('R76_PROFILE_DERIVATION_AUTHORIZATION_CHAIN_INVALID');
            (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id']);
        } finally { $this->removeTree($root); }
    }

    public function testRecruiterAcceptanceRequiresCurrentOccupiedRecruiter(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $bootstrap = new StateStore($root);
            $bootstrap->locked(static function () use ($bootstrap): void {
                $bootstrap->write(['state' => 'CURIA_READY', 'binding' => ['instance_id' => 'imperium-test'], 'events' => []]);
            });
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], 'AUTHORIZED', 'Authorize exact derivation.', 'Passive scope only.');
            $this->expectExceptionMessage('R82_RECRUITER_UNAVAILABLE');
            (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id']);
        } finally { $this->removeTree($root); }
    }

    public function testAuthorizedDecisionBindsExactReservationAndStructuredProfileScope(): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $requestService = new ProfileDerivationAuthorizationRequestService($root, $store);
            $request = $requestService->request($reservationId, 1);
            self::assertSame($request, $requestService->request($reservationId, 1));
            self::assertSame('persona-test', $request['profile_scope']['persona']['persona_id']);
            self::assertSame('Web application security assessor', $request['profile_scope']['profession']);
            self::assertSame('curia', $request['profile_scope']['profile_steward']);
            self::assertSame('conscription.recruiter', $request['profile_scope']['prospective_commissioner_and_installer']);
            self::assertSame('laboratorium.alchemist', $request['profile_scope']['prospective_transformer']);
            self::assertSame('senate', $request['profile_scope']['prospective_examiner']);
            self::assertSame('imperator', $request['profile_scope']['prospective_approver']);
            self::assertFalse($request['profile_derivation_authority']);
            self::assertFalse($request['retrieval_authority']);

            $decisionService = new ProfileDerivationAuthorizationDecisionService($root);
            $act = $decisionService->decide($request['request_id'], 'AUTHORIZED', 'Authorize the exact Profile derivation scope.', 'Passive mission scope only.');
            self::assertSame($act, $decisionService->decide($request['request_id'], 'AUTHORIZED', 'Authorize the exact Profile derivation scope.', 'Passive mission scope only.'));
            self::assertSame('PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE', $act['status']);
            self::assertSame($request['source_reservation_disposition'], $act['source_reservation_disposition']);
            self::assertSame($request['source_plan'], $act['source_plan']);
            self::assertSame($request['profile_scope'], $act['profile_scope']);
            self::assertTrue($act['profile_derivation_authority']);
            self::assertTrue($act['profile_derivation_authority_exercisable']);
            self::assertTrue($act['conscription_followup_required']);
            self::assertFalse($act['retrieval_authority']);
            self::assertFalse($act['conscription_acceptance_authority']);
            self::assertFalse($act['spawning_authority']);
            self::assertFalse($act['seat_binding_authority']);
            self::assertFalse($act['deployment_authority']);
            self::assertFalse($act['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    #[DataProvider('nonAuthorizingDispositions')]
    public function testEveryConversationalDispositionRemainsNonAuthorizing(string $disposition): void
    {
        [$root, $store, $reservationId] = $this->fixture();
        try {
            $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
            $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide($request['request_id'], $disposition, 'Exact non-authorizing Imperator response.');
            self::assertSame('NON_AUTHORIZING_IMPERATOR_PROFILE_DERIVATION_DISPOSITION_RECORDED', $act['status']);
            self::assertFalse($act['profile_derivation_authority']);
            self::assertFalse($act['profile_derivation_authority_exercisable']);
            self::assertFalse($act['conscription_followup_required']);
            self::assertFalse($act['retrieval_authority']);
            self::assertFalse($act['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public static function nonAuthorizingDispositions(): iterable
    {
        foreach (['REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'] as $disposition) yield $disposition => [$disposition];
    }

    public function testRequestRefusesAReservationThatDidNotSucceed(): void
    {
        [$root, $store, $reservationId] = $this->fixture('REFUSED_PERSONA_UNAVAILABLE', false);
        try {
            $this->expectExceptionMessage('C139_RESERVATION_DISPOSITION_INVALID');
            (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testRequestRefusesPlanDriftFromTheReservedCapabilityCommitment(): void
    {
        [$root, $store, $reservationId] = $this->fixture('RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION', true, ['Different capability']);
        try {
            $this->expectExceptionMessage('C141_PROFILE_SCOPE_MISMATCH');
            (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
        } finally {
            $this->removeTree($root);
        }
    }

    private function fixture(string $status = 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION', bool $reserved = true, ?array $planCapabilities = null): array
    {
        $root = sys_get_temp_dir().'/imperium-profile-derivation-auth-'.bin2hex(random_bytes(6));
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-test', 'instance_id' => 'imperium-test']);
        $plan = $this->missionPlan();
        if (null !== $planCapabilities) $plan['capability_requirements'] = $planCapabilities;
        $store->appendTurn('proceeding-test', 'response-profile-plan', 1, [
            'schema' => 'imperium.curian-turn/v1',
            'proceeding_id' => 'proceeding-test',
            'response_id' => 'response-profile-plan',
            'seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED', 'mission_plan' => $plan],
            'resource_demands' => ['Guildhall personnel disposition'],
        ]);
        $custody = [
            'schema' => 'imperium.garrison-persona-custody/v1',
            'custody_id' => 'persona-custody-test',
            'instance_id' => 'imperium-test',
            'persona_id' => 'persona-test',
            'custody_state' => 'ADMITTED_HELD',
            'available' => true,
        ];
        $custody['record_digest'] = hash('sha256', CanonicalJson::encode($custody));
        $custodyDirectory = $root.'/var/imperium/offices/garrison/custody';
        mkdir($custodyDirectory, 0770, true);
        file_put_contents($custodyDirectory.'/persona-custody-test.json', json_encode($custody, JSON_THROW_ON_ERROR));
        $reservationId = 'persona-reservation-disposition-'.str_repeat('a', 20);
        $record = [
            'schema' => 'imperium.garrison-persona-reservation-disposition/v1',
            'disposition_id' => $reservationId,
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test',
            'personnel_commitment' => [
                'capability_slot_id' => 'slot-passive-assessment',
                'capability_requirements' => ['Analyze public application behavior', 'Produce evidence-bound findings'],
                'profession' => 'Web application security assessor',
                'persona' => ['custody_id' => 'persona-custody-test', 'persona_id' => 'persona-test'],
                'suitability_determination' => 'Exact Persona satisfies the bounded mission requirements.',
                'guildhall_resolution_digest' => str_repeat('b', 64),
            ],
            'custody_id' => 'persona-custody-test',
            'custody_digest' => $custody['record_digest'],
            'disposition' => $reserved ? 'RESERVED' : 'PERSONA_UNAVAILABLE',
            'status' => $status,
            'persona_reserved' => $reserved,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ];
        $directory = $root.'/var/imperium/offices/garrison/persona-reservation-dispositions';
        mkdir($directory, 0770, true);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($directory.'/'.$reservationId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
        return [$root, $store, $reservationId];
    }

    private function missionPlan(): array
    {
        return [
            'objective' => 'Assess a public web application.',
            'scope' => ['Supplied public URLs'],
            'deliverables' => ['Evidence-bound risk report'],
            'constraints' => ['Passive only'],
            'required_inputs' => ['Target URL'],
            'capability_requirements' => ['Analyze public application behavior', 'Produce evidence-bound findings'],
            'tool_requirements' => ['Passive checklist'],
            'data_requirements' => ['Public responses'],
            'office_participation' => ['Guildhall', 'Laboratorium', 'Conscription', 'Senate'],
            'stop_conditions' => ['Authentication or active scanning required'],
        ];
    }

    private function recruiterBootstrap(string $root): StateStore
    {
        $bootstrap = new StateStore($root);
        $bootstrap->locked(static function () use ($bootstrap): void {
            $bootstrap->write([
                'state' => 'CURIA_READY',
                'binding' => ['instance_id' => 'imperium-test'],
                'events' => [[
                    'transition' => 'T04',
                    'result' => 'SUCCESS',
                    'output' => ['successor' => [
                        'manifestation_id' => 'imperium-test.officer.ordinary-recruiter.1',
                        'seat' => 'conscription.recruiter',
                        'occupancy_generation' => 2,
                        'authority' => 'ordinary-recruiter',
                    ]],
                ]],
            ]);
        });
        return $bootstrap;
    }

    private function constableOccupancy(string $root): array
    {
        $binding = [
            'schema' => 'imperium.garrison-constable-occupancy/v1',
            'binding_id' => 'garrison-constable-binding-'.str_repeat('d', 20),
            'instance_id' => 'imperium-test',
            'seat' => 'garrison.constable',
            'manifestation_id' => 'imperium-test.officer.garrison.constable.test',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'profile_derivation_handoff_disposition_authority' => true,
            'selection_authority' => false,
            'execution_authority' => false,
        ];
        $binding['record_digest'] = hash('sha256', CanonicalJson::encode($binding));
        $directory = $root.'/var/imperium/offices/garrison/occupancy';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$binding['binding_id'].'.json', json_encode($binding, JSON_THROW_ON_ERROR));
        return $binding;
    }

    private function alchemistOccupancy(string $root, bool $authorized = true): array
    {
        $binding = [
            'schema' => 'imperium.operator-root-seat-occupancy/v1',
            'binding_id' => 'operator-root-binding-'.str_repeat('e', 20),
            'instance_id' => 'imperium-test',
            'office' => 'laboratorium',
            'seat' => 'laboratorium.alchemist',
            'manifestation_id' => 'imperium-test.officer.laboratorium.alchemist.test',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'binding_atomic' => true,
            'profile_derivation_commission_acceptance_authority' => $authorized,
            'execution_authority' => false,
        ];
        $binding['record_digest'] = hash('sha256', CanonicalJson::encode($binding));
        $directory = $root.'/var/imperium/offices/laboratorium/occupancy';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$binding['binding_id'].'.json', json_encode($binding, JSON_THROW_ON_ERROR));
        return $binding;
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
