<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Laboratorium\ProfileCandidateReturnService;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationCognitionGateway;
use App\Imperium\Runtime\Laboratorium\ProfileElaborationSmokeService;
use PHPUnit\Framework\TestCase;

final class ProfileElaborationSmokeServiceTest extends TestCase
{
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
            self::assertFileExists($root.'/var/imperium/offices/laboratorium/profile-candidates/'.$result['candidate']['candidate_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/conscription/profile-candidate-return-inbox/'.$result['return']['return_id'].'.json');

            $custodyPath = $root.'/var/imperium/offices/garrison/custody/'.$result['candidate']['custody_lease']['custody_id'].'.json';
            $custody = json_decode((string) file_get_contents($custodyPath), true, 512, JSON_THROW_ON_ERROR);
            $custody['available'] = false; unset($custody['record_digest']);
            $custody['record_digest'] = hash('sha256', CanonicalJson::encode($custody));
            file_put_contents($custodyPath, json_encode($custody, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('L50_PROFILE_CANDIDATE_RETURN_CHAIN_INVALID');
            (new ProfileCandidateReturnService($root))->returnCandidate($result['candidate']['candidate_id']);
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); }
        rmdir($path);
    }
}
