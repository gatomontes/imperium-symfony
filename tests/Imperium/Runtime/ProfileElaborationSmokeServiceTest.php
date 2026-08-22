<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Laboratorium\ProfileCandidateReturnService;
use App\Imperium\Runtime\Conscription\ProfileCandidateReturnAcceptanceService;
use App\Imperium\Runtime\Conscription\ExaminationAssemblyAuthorizationRequestService;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationCognitionGateway;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationSmokeService;
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
            $result = (new ProfileElaborationSmokeService($cognition))->run($root, 'REFUSED');
            self::assertSame('REFUSED', $result['examination_assembly_authorization']['disposition']);
            self::assertSame('EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY', $result['examination_assembly_authorization']['status']);
            self::assertFalse($result['examination_assembly_authorization']['recipient_acceptance']);
            self::assertFalse($result['examination_assembly_authorization']['examination_profile_installation_authority']);
            self::assertFalse($result['examination_assembly_authorization']['examination_assembly_authority']);
            self::assertFalse($result['examination_assembly_authorization']['examination_assembly_authority_exercisable']);
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
            $result = (new ProfileElaborationSmokeService($cognition))->run($root);
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
            self::assertFileExists($root.'/var/imperium/offices/laboratorium/profile-candidates/'.$result['candidate']['candidate_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/profile-candidate-return-inbox/'.$result['return']['return_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/profile-candidate-return-acceptances/'.$result['return_acceptance']['acceptance_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/senate/examination-assembly-authorization-inbox/'.$result['examination_assembly_request']['request_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/examination-assembly-authorization-dispositions/'.$result['examination_assembly_authorization']['disposition_id'].'.json');

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
}
