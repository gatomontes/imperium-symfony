<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerPersonaReviewInitiationService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerPersonaReviewInitiationServiceTest extends
    TestCase
{
    public function testInitiatesReviewAndFailsClosedOnBootstrapConstraint(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-review-initiation-" .
            bin2hex(random_bytes(6));
        $candidateId =
            "adversarial-reviewer-persona-candidate-" . str_repeat("a", 20);
        $candidate = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-candidate/v1",
            "persona_candidate_id" => $candidateId,
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "instance_id" => "imperium-test",
            "construction_acceptance_id" => "acceptance",
            "construction_acceptance_digest" => "acceptance-digest",
            "artificer" => ["seat" => "foundry.artificer"],
            "status" => "SEALED_PENDING_FOUNDRY_REVIEW",
            "construction_complete" => true,
            "sealed" => true,
            "production_approval" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates",
            $candidateId,
            $candidate,
        );
        try {
            $service = new AdversarialReviewerPersonaReviewInitiationService(
                $root,
            );
            $case = $service->initiate($candidateId);
            self::assertSame($case, $service->initiate($candidateId));
            self::assertSame(
                "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION",
                $case["status"],
            );
            self::assertTrue($case["review_initiated"]);
            self::assertFalse($case["review_complete"]);
            self::assertFalse($case["clean_review"]);
            self::assertSame(
                $candidate["record_digest"],
                $case["persona_candidate_digest"],
            );
            self::assertTrue(
                in_array(
                    "candidate_cannot_review_itself",
                    $case["bootstrap_constraint"],
                    true,
                ),
            );
            foreach (
                [
                    "exception_authority",
                    "review_authority",
                    "production_approval",
                    "spawning_authority",
                    "seat_binding_authority",
                    "admission_authority",
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
