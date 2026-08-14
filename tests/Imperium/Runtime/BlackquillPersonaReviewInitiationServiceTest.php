<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\BlackquillPersonaReviewInitiationService;
use PHPUnit\Framework\TestCase;
final class BlackquillPersonaReviewInitiationServiceTest extends TestCase
{
    public function testInitiatesReviewAndFailsClosedWithoutDistinctReviewer(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-review-" .
            bin2hex(random_bytes(6));
        $id = "blackquill-persona-candidate-" . str_repeat("a", 20);
        $candidate = [
            "schema" => "imperium.foundry-blackquill-persona-candidate/v1",
            "persona_candidate_id" => $id,
            "persona_id" => "foundry.external.blackquill-adversarial-reviewer",
            "persona_version" => "1.0.0",
            "instance_id" => "imperium-test",
            "production_acceptance_id" => "acceptance",
            "production_acceptance_digest" => "acceptance-digest",
            "authorization_act_id" => "authorization",
            "authorization_act_digest" => "authorization-digest",
            "source_case_id" => "source-case",
            "source_case_digest" => "source-case-digest",
            "template" => [
                "schema" => "imperium.persona/v1",
                "version" => "1.0.0",
            ],
            "source" => ["content_digest" => "sha256:source"],
            "artificer" => ["seat" => "foundry.artificer"],
            "status" => "SEALED_PENDING_FOUNDRY_REVIEW",
            "production_processing_complete" => true,
            "sealed" => true,
            "production_approval" => false,
            "review_findings_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/blackquill-persona-candidates",
            $id,
            $candidate,
        );
        try {
            $service = new BlackquillPersonaReviewInitiationService($root);
            $case = $service->initiate($id);
            self::assertSame($case, $service->initiate($id));
            self::assertSame(
                "BLOCKED_PENDING_DISTINCT_INDEPENDENT_REVIEWER",
                $case["status"],
            );
            self::assertSame(
                $candidate["record_digest"],
                $case["persona_candidate_digest"],
            );
            self::assertSame(
                $candidate["production_acceptance_digest"],
                $case["production_acceptance_digest"],
            );
            self::assertSame(
                $candidate["authorization_act_digest"],
                $case["authorization_act_digest"],
            );
            self::assertSame(
                $candidate["source_case_digest"],
                $case["source_case_digest"],
            );
            self::assertSame($candidate["template"], $case["template"]);
            self::assertTrue($case["review_initiated"]);
            self::assertFalse($case["review_complete"]);
            self::assertFalse($case["clean_review"]);
            self::assertContains(
                "blackquill_cannot_review_itself",
                $case["independence_constraint"],
            );
            foreach (
                [
                    "exception_authority",
                    "review_findings_authority",
                    "production_approval",
                    "senate_confirmation_authority",
                    "release_authority",
                    "admission_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($case[$key]);
            }
        } finally {
            $this->removeTree($root);
        }
    }
    private function write(string $directory, string $id, array &$record): void
    {
        mkdir($directory, 0770, true);
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        file_put_contents(
            $directory . "/" . $id . ".json",
            json_encode($record, JSON_THROW_ON_ERROR),
        );
    }
    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], [".", ".."]) as $entry) {
            $child = $path . "/" . $entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
