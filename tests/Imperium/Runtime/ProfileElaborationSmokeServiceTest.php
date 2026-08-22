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
            $result = (new ProfileElaborationSmokeService($cognition, $this->questionCognition(), $this->testimonyCognition()))->run($root, 'REFUSED');
            self::assertSame('REFUSED', $result['examination_assembly_authorization']['disposition']);
            self::assertSame('EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY', $result['examination_assembly_authorization']['status']);
            self::assertFalse($result['examination_assembly_authorization']['recipient_acceptance']);
            self::assertFalse($result['examination_assembly_authorization']['examination_profile_installation_authority']);
            self::assertFalse($result['examination_assembly_authorization']['examination_assembly_authority']);
            self::assertFalse($result['examination_assembly_authorization']['examination_assembly_authority_exercisable']);
            self::assertNull($result['examination_manifestation']);
            self::assertNull($result['stand_admission']);
            self::assertNull($result['examination_opening']);
            self::assertSame([], $result['panel_acceptances']);self::assertNull($result['panel_readiness']);self::assertNull($result['testimony_opening']);self::assertSame([], $result['examination_questions']);self::assertSame([], $result['profile_testimony_turns']);self::assertNull($result['profile_testimony_readiness']);
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
            $result = (new ProfileElaborationSmokeService($cognition, $this->questionCognition(), $this->testimonyCognition()))->run($root);
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
}
