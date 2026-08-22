<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\LaboratoriumProfileDerivationCommissionService;
use App\Imperium\Runtime\Conscription\ProfileCandidateReturnAcceptanceService;
use App\Imperium\Runtime\Conscription\ExaminationAssemblyAuthorizationRequestService;
use App\Imperium\Runtime\Conscription\ExaminationManifestationAssemblyService;
use App\Imperium\Runtime\Conscription\ProfileDerivationAuthorizationAcceptanceService;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Curia\ProfileDerivationAuthorizationDecisionService;
use App\Imperium\Runtime\Curia\ProfileDerivationAuthorizationRequestService;
use App\Imperium\Runtime\Garrison\ProfileDerivationHandoffDispositionService;
use App\Imperium\Runtime\Senate\ExaminationAssemblyAuthorizationDispositionService;
use App\Imperium\Runtime\Senate\ExaminationManifestationStandAdmissionService;
use App\Imperium\Runtime\Senate\ProfileExaminationOpeningService;

final readonly class ProfileElaborationSmokeService
{
    public function __construct(private ProfileElaborationCognitionGateway $cognition) {}

    public function run(string $root, string $senateDisposition = 'ACCEPTED'): array
    {
        if (is_dir($root) || is_file($root)) throw new \RuntimeException('DEV01_SMOKE_ROOT_ALREADY_EXISTS');

        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-profile-elaboration-smoke', 'instance_id' => 'imperium-profile-elaboration-smoke']);
        $store->appendTurn('proceeding-profile-elaboration-smoke', 'response-profile-elaboration-smoke-plan', 1, [
            'schema' => 'imperium.curian-turn/v1',
            'proceeding_id' => 'proceeding-profile-elaboration-smoke',
            'response_id' => 'response-profile-elaboration-smoke-plan',
            'seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED', 'mission_plan' => $this->missionPlan()],
            'resource_demands' => ['Guildhall personnel disposition'],
        ]);

        $custody = $this->seal([
            'schema' => 'imperium.garrison-persona-custody/v1',
            'custody_id' => 'persona-custody-profile-elaboration-smoke',
            'instance_id' => 'imperium-profile-elaboration-smoke',
            'persona_id' => 'persona-profile-elaboration-smoke',
            'persona_version' => '1',
            'persona_name' => 'Evidence-Bound Web Application Security Assessor',
            'persona_digest' => str_repeat('a', 64),
            'custody_state' => 'ADMITTED_HELD',
            'available' => true,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/offices/garrison/custody/'.$custody['custody_id'].'.json', $custody);

        $reservationId = 'persona-reservation-disposition-'.str_repeat('b', 20);
        $reservation = $this->seal([
            'schema' => 'imperium.garrison-persona-reservation-disposition/v1',
            'disposition_id' => $reservationId,
            'instance_id' => 'imperium-profile-elaboration-smoke',
            'proceeding_id' => 'proceeding-profile-elaboration-smoke',
            'personnel_commitment' => [
                'capability_slot_id' => 'slot-passive-web-assessment',
                'capability_requirements' => ['Analyze publicly observable application behavior', 'Produce evidence-bound findings'],
                'profession' => 'Web application security assessor',
                'persona' => ['custody_id' => $custody['custody_id'], 'persona_id' => $custody['persona_id']],
                'suitability_determination' => 'The exact admitted Persona satisfies the bounded passive-assessment requirements.',
                'guildhall_resolution_digest' => str_repeat('c', 64),
            ],
            'custody_id' => $custody['custody_id'],
            'custody_digest' => $custody['record_digest'],
            'disposition' => 'RESERVED',
            'status' => 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION',
            'persona_reserved' => true,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/offices/garrison/persona-reservation-dispositions/'.$reservationId.'.json', $reservation);

        $bootstrap = new StateStore($root);
        $bootstrap->locked(static function () use ($bootstrap): void {
            $bootstrap->write([
                'state' => 'CURIA_READY',
                'binding' => ['instance_id' => 'imperium-profile-elaboration-smoke'],
                'events' => [[
                    'transition' => 'T04', 'result' => 'SUCCESS',
                    'output' => ['successor' => [
                        'manifestation_id' => 'imperium-profile-elaboration-smoke.officer.ordinary-recruiter.1',
                        'seat' => 'conscription.recruiter', 'occupancy_generation' => 2, 'authority' => 'ordinary-recruiter',
                    ]],
                ]],
            ]);
        });
        $constable = $this->constable();
        $this->write($root.'/var/imperium/offices/garrison/occupancy/'.$constable['binding_id'].'.json', $constable);
        $alchemist = $this->alchemist();
        $this->write($root.'/var/imperium/offices/laboratorium/occupancy/'.$alchemist['binding_id'].'.json', $alchemist);
        $lordSpeaker = $this->lordSpeaker();
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$lordSpeaker['binding_id'].'.json', $lordSpeaker);
        $bailiff = $this->bailiff();
        $this->write($root.'/var/imperium/offices/senate/occupancy/'.$bailiff['binding_id'].'.json', $bailiff);

        $request = (new ProfileDerivationAuthorizationRequestService($root, $store))->request($reservationId, 1);
        $act = (new ProfileDerivationAuthorizationDecisionService($root))->decide(
            $request['request_id'], 'AUTHORIZED', 'Authorize the exact development smoke-test Profile derivation.', 'Passive public assessment only; no authentication or active scanning.',
        );
        $handoff = (new ProfileDerivationAuthorizationAcceptanceService($root, $bootstrap))->accept($act['act_id'])['handoff_request'];
        $disposition = (new ProfileDerivationHandoffDispositionService($root))->decide($handoff['request_id'], $constable['binding_id'], 'APPROVED', 'Approve the exact custody-bound smoke-test lease.');
        $commission = (new LaboratoriumProfileDerivationCommissionService($root, $bootstrap))->commission($disposition['disposition_id']);
        $acceptance = (new ProfileDerivationCommissionAcceptanceService($root))->accept($commission['commission_id'], $alchemist['binding_id']);
        $candidate = (new ProfileCandidateDerivationService($root, $this->cognition))->derive($acceptance['acceptance_id']);
        $return = (new ProfileCandidateReturnService($root))->returnCandidate($candidate['candidate_id']);
        $returnAcceptance = (new ProfileCandidateReturnAcceptanceService($root, $bootstrap))->accept($return['return_id']);
        $assemblyRequest = (new ExaminationAssemblyAuthorizationRequestService($root, $bootstrap))->request($returnAcceptance['acceptance_id']);
        $assemblyAuthorization = (new ExaminationAssemblyAuthorizationDispositionService($root))->decide(
            $assemblyRequest['request_id'], $lordSpeaker['binding_id'], $senateDisposition,
            'ACCEPTED' === strtoupper(trim($senateDisposition)) ? 'Accept the exact examination-only assembly contract for Senate intake.' : 'Refuse the exact examination-only assembly contract without granting authority.',
        );
        $examinationManifestation = 'ACCEPTED' === $assemblyAuthorization['disposition'] ? (new ExaminationManifestationAssemblyService($root, $bootstrap))->assemble($assemblyAuthorization['disposition_id']) : null;
        $standAdmission = is_array($examinationManifestation) ? (new ExaminationManifestationStandAdmissionService($root))->admit($examinationManifestation['delivery_id'], $bailiff['binding_id']) : null;
        $examinationOpening = is_array($standAdmission) ? (new ProfileExaminationOpeningService($root))->open($standAdmission['admission_id'], $lordSpeaker['binding_id']) : null;

        return ['state_root' => $root, 'acceptance' => $acceptance, 'candidate' => $candidate, 'return' => $return, 'return_acceptance' => $returnAcceptance, 'examination_assembly_request' => $assemblyRequest, 'examination_assembly_authorization' => $assemblyAuthorization, 'examination_manifestation' => $examinationManifestation, 'stand_admission' => $standAdmission, 'examination_opening' => $examinationOpening];
    }

    private function missionPlan(): array
    {
        return [
            'objective' => 'Assess a supplied public web application without active interaction.',
            'scope' => ['Explicitly supplied public URLs'],
            'deliverables' => ['Evidence-bound risk report'],
            'constraints' => ['Passive and non-invasive only'],
            'required_inputs' => ['Target URL'],
            'capability_requirements' => ['Analyze publicly observable application behavior', 'Produce evidence-bound findings'],
            'tool_requirements' => ['Approved passive review checklist'],
            'data_requirements' => ['Publicly observable application responses'],
            'office_participation' => ['Guildhall', 'Laboratorium', 'Conscription', 'Senate'],
            'stop_conditions' => ['Authentication or active scanning would be required'],
        ];
    }

    private function constable(): array
    {
        return $this->seal([
            'schema' => 'imperium.garrison-constable-occupancy/v1',
            'binding_id' => 'garrison-constable-binding-'.str_repeat('d', 20),
            'instance_id' => 'imperium-profile-elaboration-smoke', 'seat' => 'garrison.constable',
            'manifestation_id' => 'imperium-profile-elaboration-smoke.officer.garrison.constable.1',
            'occupancy_generation' => 1, 'status' => 'ACTIVE',
            'profile_derivation_handoff_disposition_authority' => true,
            'selection_authority' => false, 'execution_authority' => false,
        ]);
    }

    private function alchemist(): array
    {
        return $this->seal([
            'schema' => 'imperium.operator-root-seat-occupancy/v1',
            'binding_id' => 'operator-root-binding-'.str_repeat('e', 20),
            'instance_id' => 'imperium-profile-elaboration-smoke', 'office' => 'laboratorium', 'seat' => 'laboratorium.alchemist',
            'manifestation_id' => 'imperium-profile-elaboration-smoke.officer.laboratorium.alchemist.1',
            'occupancy_generation' => 1, 'status' => 'ACTIVE', 'binding_atomic' => true,
            'profile_derivation_commission_acceptance_authority' => true, 'execution_authority' => false,
        ]);
    }

    private function lordSpeaker(): array
    {
        return $this->seal([
            'schema' => 'imperium.senate-lord-speaker-occupancy/v1',
            'binding_id' => 'senate-lord-speaker-binding-'.str_repeat('f', 20),
            'instance_id' => 'imperium-profile-elaboration-smoke', 'office' => 'senate', 'seat' => 'senate.lord-speaker',
            'manifestation_id' => 'imperium-profile-elaboration-smoke.officer.senate.lord-speaker.1',
            'occupancy_generation' => 1, 'status' => 'ACTIVE', 'binding_atomic' => true,
            'examination_assembly_authorization_disposition_authority' => true, 'execution_authority' => false,
        ]);
    }

    private function bailiff(): array
    {
        return $this->seal(['schema'=>'imperium.senate-bailiff-occupancy/v1','binding_id'=>'senate-bailiff-binding-'.str_repeat('a',20),'instance_id'=>'imperium-profile-elaboration-smoke','office'=>'senate','seat'=>'senate.bailiff','manifestation_id'=>'imperium-profile-elaboration-smoke.officer.senate.bailiff.1','occupancy_generation'=>1,'status'=>'ACTIVE','binding_atomic'=>true,'proceeding_security_authority'=>true,'execution_authority'=>false]);
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record;
    }

    private function write(string $path, array $record): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('DEV02_SMOKE_DIRECTORY_FAILED');
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('DEV03_SMOKE_RECORD_FAILED');
    }
}
