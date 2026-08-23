<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Laboratorium\ProfileCandidateReturnService;
use App\Imperium\Runtime\Conscription\ProfileCandidateReturnAcceptanceService;
use App\Imperium\Runtime\Conscription\ExaminationAssemblyAuthorizationRequestService;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationCognitionGateway;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationSmokeService;
use App\Imperium\Runtime\Senate\ProfileExaminationQuestionCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationTestimonyCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationFindingCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationReconciliationCognitionGateway;
use App\Imperium\Runtime\Senate\ProfileExaminationDispositionCognitionGateway;
use PHPUnit\Framework\TestCase;

final class ProfileElaborationSmokeServiceTest extends TestCase
{
    public function testSenateRefusalGrantsNoAssemblyAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-profile-elaboration-smoke-refusal-'.bin2hex(random_bytes(6));
        $cognition = new class implements ProfileElaborationCognitionGateway {
            public function elaborate(array $acceptance, array $authorization): array
            {
                return [
                    'disposition' => 'PROFILE_ELABORATION_COMPLETE', 'operating_posture' => 'Passive and evidence-bound.',
                    'responsibilities' => ['Assess the supplied public target.'], 'non_responsibilities' => ['Do not scan actively.'],
                    'reasoning_priorities' => ['Separate evidence from inference.'], 'evidence_discipline' => ['Attribute every finding.'],
                    'tool_use_directives' => ['Use only the passive checklist.'], 'input_handling' => ['Accept the supplied URL only.'],
                    'output_contract' => ['Return an evidence-bound report.'], 'escalation_conditions' => ['Escalate authentication requirements.'],
                    'uncertainty_behavior' => ['State uncertainty.'], 'failure_behavior' => ['Stop safely.'],
                    'persona_adaptations' => ['Apply the admitted identity without mutation.'],
                ];
            }
        };
        try {
            $result = (new ProfileElaborationSmokeService($cognition, $this->questionCognition(), $this->testimonyCognition(), $this->findingCognition(), $this->reconciliationCognition(), $this->dispositionCognition()))->run($root, 'REFUSED');
            self::assertSame('REFUSED', $result['examination_assembly_authorization']['disposition']);
            self::assertSame('EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY', $result['examination_assembly_authorization']['status']);
            self::assertFalse($result['examination_assembly_authorization']['recipient_acceptance']);
            self::assertFalse($result['examination_assembly_authorization']['examination_profile_installation_authority']);
            self::assertFalse($result['examination_assembly_authorization']['examination_assembly_authority']);
            self::assertFalse($result['examination_assembly_authorization']['examination_assembly_authority_exercisable']);
            self::assertNull($result['examination_manifestation']);
            self::assertNull($result['stand_admission']);
            self::assertNull($result['examination_opening']);
            self::assertSame([], $result['panel_acceptances']);self::assertNull($result['panel_readiness']);self::assertNull($result['testimony_opening']);self::assertSame([], $result['examination_questions']);self::assertSame([], $result['profile_testimony_turns']);self::assertNull($result['profile_testimony_readiness']);self::assertNull($result['finding_authority_opening']);self::assertSame([],$result['senator_findings']);self::assertNull($result['finding_readiness']);self::assertNull($result['deliberation_opening']);self::assertNull($result['reconciliation']);self::assertNull($result['disposition_authority_opening']);self::assertNull($result['profile_disposition']);self::assertNull($result['profile_approval']);
        } finally { $this->removeTree($root); }
    }

    public function testIsolatedDriverPersistsTheCompleteGovernedChainAndCandidate(): void
    {
        $root = sys_get_temp_dir().'/imperium-profile-elaboration-smoke-'.bin2hex(random_bytes(6));
        $cognition = new class implements ProfileElaborationCognitionGateway {
            public function elaborate(array $acceptance, array $authorization): array
            {
                return [
                    'disposition' => 'PROFILE_ELABORATION_COMPLETE', 'operating_posture' => 'Passive and evidence-bound.',
                    'responsibilities' => ['Assess the supplied public target.'], 'non_responsibilities' => ['Do not scan actively.'],
                    'reasoning_priorities' => ['Separate evidence from inference.'], 'evidence_discipline' => ['Attribute every finding.'],
                    'tool_use_directives' => ['Use only the passive checklist.'], 'input_handling' => ['Accept the supplied URL only.'],
                    'output_contract' => ['Return an evidence-bound report.'], 'escalation_conditions' => ['Escalate authentication requirements.'],
                    'uncertainty_behavior' => ['State uncertainty.'], 'failure_behavior' => ['Stop safely.'],
                    'persona_adaptations' => ['Apply the admitted identity without mutation.'],
                ];
            }
        };
        try {
            $result = (new ProfileElaborationSmokeService($cognition, $this->questionCognition(), $this->testimonyCognition(), $this->findingCognition(), $this->reconciliationCognition(), $this->dispositionCognition()))->run($root);
            self::assertSame('PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION', $result['candidate']['status']);
            self::assertSame('PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION', $result['acceptance']['status']);
            self::assertTrue($result['candidate']['profile_elaboration_complete']);
            self::assertTrue($result['candidate']['profile_candidate_return_authority']);
            self::assertSame('PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_ACCEPTANCE', $result['return']['status']);
            self::assertTrue($result['return']['profile_candidate_return_authority_consumed']);
            self::assertTrue($result['return']['profile_candidate_returned']);
            self::assertFalse($result['return']['recipient_acceptance']);
            self::assertFalse($result['return']['profile_approval_authority']);
            self::assertFalse($result['return']['profile_installation_authority']);
            self::assertFalse($result['return']['examination_assembly_authority']);
            self::assertFalse($result['return']['senate_examination_authority']);
            self::assertFalse($result['return']['custody_release_authority']);
            self::assertFalse($result['return']['deployment_authority']);
            self::assertFalse($result['return']['execution_authority']);
            self::assertSame('ACCEPTED_EXACT_RETURNED_PROFILE_CANDIDATE', $result['return_acceptance']['disposition']);
            self::assertSame('PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_AUTHORIZATION', $result['return_acceptance']['status']);
            self::assertTrue($result['return_acceptance']['recipient_acceptance']);
            self::assertTrue($result['return_acceptance']['profile_candidate_acceptance_authority_consumed']);
            self::assertFalse($result['return_acceptance']['profile_approval_authority']);
            self::assertFalse($result['return_acceptance']['profile_installation_authority']);
            self::assertFalse($result['return_acceptance']['examination_assembly_authority']);
            self::assertFalse($result['return_acceptance']['senate_examination_authority']);
            self::assertFalse($result['return_acceptance']['custody_release_authority']);
            self::assertFalse($result['return_acceptance']['deployment_authority']);
            self::assertFalse($result['return_acceptance']['execution_authority']);
            self::assertSame('EXAMINATION_ASSEMBLY_AUTHORIZATION_REQUESTED_PENDING_SENATE_INTAKE', $result['examination_assembly_request']['status']);
            self::assertSame('ASSEMBLE_ONE_EXAMINATION_ONLY_MANIFESTATION', $result['examination_assembly_request']['requested_authority']);
            self::assertSame('senate.lord-speaker', $result['examination_assembly_request']['recipient']['seat']);
            self::assertNull($result['examination_assembly_request']['recipient_acceptance']);
            self::assertTrue($result['examination_assembly_request']['examination_assembly_request_authority_consumed']);
            self::assertFalse($result['examination_assembly_request']['profile_installation_authority']);
            self::assertFalse($result['examination_assembly_request']['examination_assembly_authority']);
            self::assertFalse($result['examination_assembly_request']['senate_examination_authority']);
            self::assertFalse($result['examination_assembly_request']['deployment_authority']);
            self::assertFalse($result['examination_assembly_request']['execution_authority']);
            self::assertSame('ACCEPTED', $result['examination_assembly_authorization']['disposition']);
            self::assertSame('EXAMINATION_ASSEMBLY_AUTHORIZED_PENDING_CONSCRIPTION_ASSEMBLY', $result['examination_assembly_authorization']['status']);
            self::assertTrue($result['examination_assembly_authorization']['recipient_acceptance']);
            self::assertTrue($result['examination_assembly_authorization']['examination_profile_installation_authority']);
            self::assertTrue($result['examination_assembly_authorization']['examination_assembly_authority']);
            self::assertTrue($result['examination_assembly_authorization']['examination_assembly_authority_exercisable']);
            self::assertFalse($result['examination_assembly_authorization']['profile_installation_authority']);
            self::assertFalse($result['examination_assembly_authorization']['profile_approval_authority']);
            self::assertFalse($result['examination_assembly_authorization']['senate_examination_authority']);
            self::assertFalse($result['examination_assembly_authorization']['deployment_authority']);
            self::assertFalse($result['examination_assembly_authorization']['execution_authority']);
            self::assertSame('EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE', $result['examination_manifestation']['status']);
            self::assertTrue($result['examination_manifestation']['examination_profile_installed']);
            self::assertTrue($result['examination_manifestation']['examination_manifestation_assembled']);
            self::assertTrue($result['examination_manifestation']['examination_profile_installation_authority_consumed']);
            self::assertTrue($result['examination_manifestation']['examination_assembly_authority_consumed']);
            self::assertSame(0, $result['examination_manifestation']['manifestation']['substrate']['version']);
            self::assertFalse($result['examination_manifestation']['manifestation']['operational_use_permitted']);
            self::assertNull($result['examination_manifestation']['recipient_acceptance']);
            self::assertFalse($result['examination_manifestation']['senate_examination_authority']);
            self::assertFalse($result['examination_manifestation']['deployment_authority']);
            self::assertFalse($result['examination_manifestation']['execution_authority']);
            self::assertSame('EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_SENATE_EXAMINATION_OPENING', $result['stand_admission']['status']);
            self::assertTrue($result['stand_admission']['stand_admission']);
            self::assertTrue($result['stand_admission']['proceeding_security_active']);
            self::assertTrue($result['stand_admission']['recipient_acceptance']);
            self::assertFalse($result['stand_admission']['senate_examination_authority']);
            self::assertFalse($result['stand_admission']['deployment_authority']);
            self::assertFalse($result['stand_admission']['execution_authority']);
            self::assertSame('PROFILE_EXAMINATION_OPENED_PENDING_SENATOR_ACCEPTANCE',$result['examination_opening']['case']['status']);
            self::assertFalse($result['examination_opening']['case']['testimony_open']);
            self::assertCount(3,$result['examination_opening']['commissions']);
            foreach($result['examination_opening']['commissions'] as $commission){self::assertNull($commission['recipient_acceptance']);self::assertFalse($commission['senator_question_authority_exercisable']);self::assertFalse($commission['senator_finding_authority_exercisable']);}
            self::assertCount(3,$result['panel_acceptances']);foreach($result['panel_acceptances']as$a){self::assertTrue($a['recipient_acceptance']);self::assertFalse($a['senator_question_authority_exercisable']);self::assertFalse($a['senator_finding_authority_exercisable']);}
            self::assertSame('PROFILE_EXAMINATION_PANEL_ACCEPTED_PENDING_TESTIMONY_OPENING',$result['panel_readiness']['status']);self::assertTrue($result['panel_readiness']['panel_ready']);self::assertFalse($result['panel_readiness']['testimony_open']);
            self::assertSame('PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING',$result['testimony_opening']['status']);
            self::assertTrue($result['testimony_opening']['profile_examination_testimony_opening_authority_consumed']);
            self::assertTrue($result['testimony_opening']['testimony_open']);
            self::assertFalse($result['testimony_opening']['deliberation_open']);
            self::assertTrue($result['testimony_opening']['senator_question_authority_exercisable']);
            self::assertFalse($result['testimony_opening']['senator_finding_authority_exercisable']);
            self::assertFalse($result['testimony_opening']['profile_approval_authority']);
            self::assertFalse($result['testimony_opening']['profile_installation_authority']);
            self::assertFalse($result['testimony_opening']['deployment_authority']);
            self::assertFalse($result['testimony_opening']['execution_authority']);
            self::assertCount(3,$result['testimony_opening']['question_authorities']);
            foreach($result['testimony_opening']['question_authorities']as$authority){self::assertTrue($authority['senator_question_authority_exercisable']);self::assertFalse($authority['senator_finding_authority_exercisable']);}
            self::assertCount(3,$result['examination_questions']);
            self::assertSame(['trust','security','usability'],array_values(array_unique(array_map(static fn(array $question):string=>$question['jurisdiction'],$result['examination_questions']))));
            foreach($result['examination_questions'] as $question){
                self::assertSame('PROFILE_EXAMINATION_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH',$question['status']);
                self::assertTrue($question['senator_question_authority_consumed']);self::assertFalse($question['question_dispatched']);self::assertNull($question['testimony_answer']);
                self::assertTrue($question['testimony_open']);self::assertFalse($question['deliberation_open']);self::assertFalse($question['senator_finding_authority_exercisable']);
                self::assertFalse($question['senate_disposition_authority']);self::assertFalse($question['profile_approval_authority']);self::assertFalse($question['profile_installation_authority']);
                self::assertFalse($question['seat_binding_authority']);self::assertFalse($question['deployment_authority']);self::assertFalse($question['execution_authority']);
                self::assertSame($result['testimony_opening']['manifestation'],$question['manifestation']);self::assertSame($result['testimony_opening']['custody_lease'],$question['custody_lease']);
                self::assertSame($result['testimony_opening']['manifestation']['profile'],$question['profile_candidate']);self::assertSame($result['testimony_opening']['manifestation']['persona'],$question['persona_identity']);
                self::assertSame('conscription.recruiter',$question['return_destination']);
                self::assertSame($result['testimony_opening']['defect_attribution_rubric'],$question['defect_attribution_rubric']);
                self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-questions/'.$question['question_id'].'.json');
            }
            self::assertCount(3,$result['profile_testimony_turns']);
            foreach($result['profile_testimony_turns'] as $turn){
                self::assertSame('PROFILE_EXAMINATION_TESTIMONY_ANSWER_SEALED_PENDING_PANEL_COMPLETION',$turn['status']);
                self::assertTrue($turn['question_dispatched_unchanged']);self::assertTrue($turn['testimony_answer_sealed']);self::assertNull($turn['senator_finding']);
                self::assertFalse($turn['deliberation_open']);self::assertFalse($turn['senator_finding_authority_exercisable']);self::assertFalse($turn['senate_disposition_authority']);
                self::assertFalse($turn['profile_approval_authority']);self::assertFalse($turn['profile_installation_authority']);self::assertFalse($turn['seat_binding_authority']);self::assertFalse($turn['deployment_authority']);self::assertFalse($turn['execution_authority']);
                self::assertSame($result['testimony_opening']['manifestation'],$turn['manifestation']);self::assertSame($result['testimony_opening']['custody_lease'],$turn['custody_lease']);
                self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-testimony-turns/'.$turn['turn_id'].'.json');
            }
            self::assertSame('PROFILE_EXAMINATION_TESTIMONY_ANSWERS_SEALED_PENDING_FINDING_AUTHORITY_OPENING',$result['profile_testimony_readiness']['status']);
            self::assertTrue($result['profile_testimony_readiness']['all_questions_dispatched_unchanged']);self::assertTrue($result['profile_testimony_readiness']['all_testimony_answers_sealed']);
            self::assertFalse($result['profile_testimony_readiness']['deliberation_open']);self::assertFalse($result['profile_testimony_readiness']['senator_finding_authority_exercisable']);
            self::assertFalse($result['profile_testimony_readiness']['senate_disposition_authority']);self::assertFalse($result['profile_testimony_readiness']['profile_approval_authority']);self::assertFalse($result['profile_testimony_readiness']['profile_installation_authority']);
            self::assertFalse($result['profile_testimony_readiness']['seat_binding_authority']);self::assertFalse($result['profile_testimony_readiness']['deployment_authority']);self::assertFalse($result['profile_testimony_readiness']['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-testimony-readiness/'.$result['profile_testimony_readiness']['readiness_id'].'.json');
            self::assertSame('PROFILE_EXAMINATION_FINDING_AUTHORITIES_OPENED_PENDING_SENATOR_FINDINGS',$result['finding_authority_opening']['status']);
            self::assertTrue($result['finding_authority_opening']['finding_phase_opening_authority_consumed']);self::assertTrue($result['finding_authority_opening']['senator_finding_authority_exercisable']);
            self::assertSame([],$result['finding_authority_opening']['senator_findings']);self::assertFalse($result['finding_authority_opening']['deliberation_open']);self::assertFalse($result['finding_authority_opening']['senate_disposition_authority']);
            self::assertFalse($result['finding_authority_opening']['profile_approval_authority']);self::assertFalse($result['finding_authority_opening']['profile_installation_authority']);self::assertFalse($result['finding_authority_opening']['seat_binding_authority']);
            self::assertFalse($result['finding_authority_opening']['deployment_authority']);self::assertFalse($result['finding_authority_opening']['execution_authority']);
            self::assertSame($result['profile_testimony_turns'][0]['manifestation'],$result['finding_authority_opening']['manifestation']);self::assertSame($result['profile_testimony_turns'][0]['profile_candidate'],$result['finding_authority_opening']['profile_candidate']);
            self::assertSame($result['profile_testimony_turns'][0]['persona_identity'],$result['finding_authority_opening']['persona_identity']);self::assertSame($result['profile_testimony_turns'][0]['custody_lease'],$result['finding_authority_opening']['custody_lease']);
            self::assertSame($result['profile_testimony_turns'][0]['return_destination'],$result['finding_authority_opening']['return_destination']);self::assertSame($result['profile_testimony_turns'][0]['defect_attribution_rubric'],$result['finding_authority_opening']['defect_attribution_rubric']);
            self::assertCount(3,$result['finding_authority_opening']['finding_authorities']);
            self::assertSame(['security','trust','usability'],array_column($result['finding_authority_opening']['finding_authorities'],'jurisdiction'));
            foreach($result['finding_authority_opening']['finding_authorities'] as $authority){self::assertTrue($authority['senator_finding_authority_exercisable']);self::assertNull($authority['senator_finding']);}
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-finding-authority-openings/'.$result['finding_authority_opening']['opening_id'].'.json');
            self::assertCount(3,$result['senator_findings']);self::assertSame(['trust','security','usability'],array_column($result['senator_findings'],'jurisdiction'));
            foreach($result['senator_findings'] as $finding){
                self::assertSame('PROFILE_EXAMINATION_SENATOR_FINDING_AUTHORED_SEALED_PENDING_PANEL_COMPLETION',$finding['status']);self::assertTrue($finding['senator_finding_authority_consumed']);self::assertTrue($finding['attributable']);
                self::assertFalse($finding['deliberation_open']);self::assertFalse($finding['senate_disposition_authority']);self::assertFalse($finding['profile_approval_authority']);self::assertFalse($finding['profile_installation_authority']);
                self::assertFalse($finding['seat_binding_authority']);self::assertFalse($finding['deployment_authority']);self::assertFalse($finding['execution_authority']);
                self::assertSame($result['finding_authority_opening']['manifestation'],$finding['manifestation']);self::assertSame($result['finding_authority_opening']['profile_candidate'],$finding['profile_candidate']);self::assertSame($result['finding_authority_opening']['persona_identity'],$finding['persona_identity']);
                self::assertSame($result['finding_authority_opening']['custody_lease'],$finding['custody_lease']);self::assertSame($result['finding_authority_opening']['return_destination'],$finding['return_destination']);self::assertSame($result['finding_authority_opening']['defect_attribution_rubric'],$finding['defect_attribution_rubric']);
                self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-senator-findings/'.$finding['finding_id'].'.json');
            }
            self::assertSame('PROFILE_EXAMINATION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING',$result['finding_readiness']['status']);self::assertTrue($result['finding_readiness']['all_finding_authorities_consumed']);
            self::assertCount(3,$result['finding_readiness']['senator_findings']);self::assertFalse($result['finding_readiness']['deliberation_open']);self::assertFalse($result['finding_readiness']['senate_disposition_authority']);
            self::assertFalse($result['finding_readiness']['profile_approval_authority']);self::assertFalse($result['finding_readiness']['profile_installation_authority']);self::assertFalse($result['finding_readiness']['seat_binding_authority']);self::assertFalse($result['finding_readiness']['deployment_authority']);self::assertFalse($result['finding_readiness']['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-finding-readiness/'.$result['finding_readiness']['readiness_id'].'.json');
            self::assertSame('PROFILE_EXAMINATION_DELIBERATION_OPENED_PENDING_RECONCILIATION',$result['deliberation_opening']['status']);
            self::assertTrue($result['deliberation_opening']['deliberation_opening_authority_consumed']);self::assertTrue($result['deliberation_opening']['deliberation_open']);self::assertTrue($result['deliberation_opening']['reconciliation_authority_exercisable']);
            self::assertNull($result['deliberation_opening']['reconciliation']);self::assertFalse($result['deliberation_opening']['vote_authority']);self::assertFalse($result['deliberation_opening']['aggregation_authority']);self::assertFalse($result['deliberation_opening']['senate_disposition_authority']);
            self::assertFalse($result['deliberation_opening']['profile_approval_authority']);self::assertFalse($result['deliberation_opening']['profile_installation_authority']);self::assertFalse($result['deliberation_opening']['seat_binding_authority']);self::assertFalse($result['deliberation_opening']['deployment_authority']);self::assertFalse($result['deliberation_opening']['execution_authority']);
            self::assertCount(3,$result['deliberation_opening']['admitted_findings']);self::assertSame(['security','trust','usability'],array_column($result['deliberation_opening']['admitted_findings'],'jurisdiction'));
            foreach($result['deliberation_opening']['admitted_findings'] as $admitted){$matching=array_values(array_filter($result['senator_findings'],static fn(array $finding):bool=>$finding['finding_id']===$admitted['finding_id']));self::assertCount(1,$matching);self::assertSame($matching[0],$admitted);}
            self::assertSame($result['finding_authority_opening']['manifestation'],$result['deliberation_opening']['manifestation']);self::assertSame($result['finding_authority_opening']['profile_candidate'],$result['deliberation_opening']['profile_candidate']);self::assertSame($result['finding_authority_opening']['persona_identity'],$result['deliberation_opening']['persona_identity']);self::assertSame($result['finding_authority_opening']['custody_lease'],$result['deliberation_opening']['custody_lease']);
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-deliberation-openings/'.$result['deliberation_opening']['deliberation_id'].'.json');
            self::assertSame('PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING',$result['reconciliation']['status']);self::assertTrue($result['reconciliation']['reconciliation_authority_consumed']);self::assertTrue($result['reconciliation']['deliberation_open']);
            self::assertFalse($result['reconciliation']['vote_authority']);self::assertFalse($result['reconciliation']['aggregation_authority']);self::assertFalse($result['reconciliation']['senate_disposition_authority']);
            self::assertFalse($result['reconciliation']['profile_approval_authority']);self::assertFalse($result['reconciliation']['profile_installation_authority']);self::assertFalse($result['reconciliation']['seat_binding_authority']);self::assertFalse($result['reconciliation']['deployment_authority']);self::assertFalse($result['reconciliation']['execution_authority']);
            self::assertSame($result['deliberation_opening']['admitted_findings'],$result['reconciliation']['admitted_findings']);self::assertSame($result['deliberation_opening']['manifestation'],$result['reconciliation']['manifestation']);self::assertSame($result['deliberation_opening']['profile_candidate'],$result['reconciliation']['profile_candidate']);self::assertSame($result['deliberation_opening']['persona_identity'],$result['reconciliation']['persona_identity']);self::assertSame($result['deliberation_opening']['custody_lease'],$result['reconciliation']['custody_lease']);
            self::assertSame(array_map(static fn(array $finding):string=>'finding:'.$finding['jurisdiction'].':'.$finding['record_digest'],$result['reconciliation']['admitted_findings']),$result['reconciliation']['reconciliation']['finding_references']);
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-reconciliations/'.$result['reconciliation']['reconciliation_id'].'.json');
            self::assertSame('PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION',$result['disposition_authority_opening']['status']);self::assertTrue($result['disposition_authority_opening']['disposition_phase_opening_authority_consumed']);self::assertTrue($result['disposition_authority_opening']['deliberation_open']);self::assertTrue($result['disposition_authority_opening']['senate_disposition_authority']);self::assertNull($result['disposition_authority_opening']['senate_disposition']);
            self::assertFalse($result['disposition_authority_opening']['reconciliation_authority_exercisable']);self::assertFalse($result['disposition_authority_opening']['vote_authority']);self::assertFalse($result['disposition_authority_opening']['aggregation_authority']);self::assertFalse($result['disposition_authority_opening']['profile_approval_authority']);self::assertFalse($result['disposition_authority_opening']['profile_installation_authority']);self::assertFalse($result['disposition_authority_opening']['seat_binding_authority']);self::assertFalse($result['disposition_authority_opening']['deployment_authority']);self::assertFalse($result['disposition_authority_opening']['execution_authority']);
            self::assertSame($result['reconciliation']['admitted_findings'],$result['disposition_authority_opening']['admitted_findings']);self::assertSame($result['reconciliation']['reconciliation'],$result['disposition_authority_opening']['reconciliation']);self::assertSame($result['reconciliation']['manifestation'],$result['disposition_authority_opening']['manifestation']);self::assertSame($result['reconciliation']['profile_candidate'],$result['disposition_authority_opening']['profile_candidate']);self::assertSame($result['reconciliation']['persona_identity'],$result['disposition_authority_opening']['persona_identity']);self::assertSame($result['reconciliation']['custody_lease'],$result['disposition_authority_opening']['custody_lease']);
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-disposition-authority-openings/'.$result['disposition_authority_opening']['opening_id'].'.json');
            self::assertSame('PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL',$result['profile_disposition']['status']);self::assertTrue($result['profile_disposition']['senate_disposition_authority_consumed']);self::assertTrue($result['profile_disposition']['imperator_profile_approval_pending']);self::assertSame('APPROVED',$result['profile_disposition']['decision']['disposition']);
            self::assertFalse($result['profile_disposition']['profile_approval_authority']);self::assertFalse($result['profile_disposition']['profile_installation_authority']);self::assertFalse($result['profile_disposition']['seat_binding_authority']);self::assertFalse($result['profile_disposition']['deployment_authority']);self::assertFalse($result['profile_disposition']['execution_authority']);self::assertSame($result['disposition_authority_opening']['admitted_findings'],$result['profile_disposition']['admitted_findings']);self::assertSame($result['disposition_authority_opening']['reconciliation'],$result['profile_disposition']['reconciliation']);
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-dispositions/'.$result['profile_disposition']['disposition_id'].'.json');
            self::assertSame('IMPERATOR_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION',$result['profile_approval']['status']);self::assertTrue($result['profile_approval']['imperator_profile_approval_consumed']);self::assertTrue($result['profile_approval']['profile_approved']);self::assertTrue($result['profile_approval']['operational_qualification_request_authority']);
            self::assertFalse($result['profile_approval']['profile_installation_authority']);self::assertFalse($result['profile_approval']['manifestation_assembly_authority']);self::assertFalse($result['profile_approval']['seat_binding_authority']);self::assertFalse($result['profile_approval']['deployment_authority']);self::assertFalse($result['profile_approval']['execution_authority']);
            self::assertSame($result['profile_disposition']['record_digest'],$result['profile_approval']['source_senate_disposition']['digest']);self::assertSame($result['profile_disposition']['profile_candidate'],$result['profile_approval']['profile_candidate']);self::assertSame($result['profile_disposition']['persona_identity'],$result['profile_approval']['persona_identity']);self::assertSame($result['profile_disposition']['custody_lease'],$result['profile_approval']['custody_lease']);self::assertSame($result['profile_disposition']['manifestation'],$result['profile_approval']['manifestation']);self::assertSame($result['profile_disposition']['admitted_findings'],$result['profile_approval']['admitted_findings']);self::assertSame($result['profile_disposition']['reconciliation'],$result['profile_approval']['reconciliation']);
            self::assertFileExists($root.'/var/imperium/imperator/profile-approval-decisions/'.$result['profile_approval']['decision_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/laboratorium/profile-candidates/'.$result['candidate']['candidate_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/profile-candidate-return-inbox/'.$result['return']['return_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/profile-candidate-return-acceptances/'.$result['return_acceptance']['acceptance_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/senate/examination-assembly-authorization-inbox/'.$result['examination_assembly_request']['request_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/examination-assembly-authorization-dispositions/'.$result['examination_assembly_authorization']['disposition_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/senate/examination-manifestation-intake/'.$result['examination_manifestation']['delivery_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/senate/examination-stand-admissions/'.$result['stand_admission']['admission_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/senate/profile-examination-testimony-openings/'.$result['testimony_opening']['opening_id'].'.json');

            $custodyPath = $root.'/var/imperium/offices/garrison/custody/'.$result['candidate']['custody_lease']['custody_id'].'.json';
            $custody = json_decode((string) file_get_contents($custodyPath), true, 512, JSON_THROW_ON_ERROR);
            $custody['available'] = false; unset($custody['record_digest']);
            $custody['record_digest'] = hash('sha256', CanonicalJson::encode($custody));
            file_put_contents($custodyPath, json_encode($custody, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('R96_EXAMINATION_ASSEMBLY_REQUEST_CHAIN_INVALID');
            (new ExaminationAssemblyAuthorizationRequestService($root, new \App\Bootstrap\StateStore($root)))->request($result['return_acceptance']['acceptance_id']);
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); }
        rmdir($path);
    }

    private function questionCognition(): ProfileExaminationQuestionCognitionGateway
    {
        return new class implements ProfileExaminationQuestionCognitionGateway {
            public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
            {
                return ['purpose'=>'Test only the exact '.$jurisdiction.' jurisdiction under the shared defect-attribution rubric.','question'=>'Under the exact '.$jurisdiction.' commission, how does this Manifestation preserve its bounded Profile and identify the rubric category responsible for any inability to comply?'];
            }
        };
    }

    private function testimonyCognition(): ProfileExaminationTestimonyCognitionGateway
    {
        return new class implements ProfileExaminationTestimonyCognitionGateway {
            public function answer(array $question, array $manifestation): array
            {
                return ['answer'=>'I preserve the exact examination-only Profile, Persona identity, authority boundaries, and Conscription return destination.','uncertainties'=>[],'refusals'=>['I refuse operational use, tools, credentials, external action, deployment, and execution.'],'evidence_claims'=>['The supplied Manifestation is examination-only and secured on senate.stand.']];
            }
        };
    }

    private function findingCognition(): ProfileExaminationFindingCognitionGateway
    {
        return new class implements ProfileExaminationFindingCognitionGateway {
            public function find(string $jurisdiction, array $authority, array $evidence): array
            {
                return ['disposition'=>'PASS','attributed_defect'=>null,'evidence_references'=>$evidence['available_evidence_references'],'rationale'=>'The exact '.$jurisdiction.' testimony preserves its bounded examination contract.','severity'=>'NONE','limitations'=>[],'uncertainty'=>[]];
            }
        };
    }

    private function reconciliationCognition(): ProfileExaminationReconciliationCognitionGateway
    {
        return new class implements ProfileExaminationReconciliationCognitionGateway {
            public function reconcile(array $authority,array $findings):array
            {
                return ['finding_references'=>$authority['available_finding_references'],'agreements'=>['All three findings preserve the examination-only boundary.'],'disagreements'=>[],'attribution_treatment'=>['No defect is attributed by any finding.'],'severity_treatment'=>['All findings report NONE.'],'limitations'=>['Reconciliation is limited to the admitted sealed findings.'],'uncertainties'=>[],'rationale'=>'The exact findings agree without voting, aggregation, modification, or suppressed dissent.'];
            }
        };
    }

    private function dispositionCognition(): ProfileExaminationDispositionCognitionGateway
    {
        return new class implements ProfileExaminationDispositionCognitionGateway {public function decide(array $authority,array $findings,array $reconciliation):array{return ['disposition'=>'APPROVED','finding_references'=>$authority['available_finding_references'],'rationale'=>'All exact findings pass within their stated limitations.','reconciliation_treatment'=>'The sealed reconciliation reports agreement and no suppressed dissent.','limitations'=>['Bound to this exact examination record.'],'uncertainties'=>[]];}};
    }
}
