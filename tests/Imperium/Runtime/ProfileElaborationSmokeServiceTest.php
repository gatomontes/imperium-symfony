<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

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
            self::assertFileExists($root.'/var/imperium/offices/laboratorium/profile-candidates/'.$result['candidate']['candidate_id'].'.json');
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); }
        rmdir($path);
    }
}
