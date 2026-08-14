<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\AdversarialReviewerAvailabilityInquiryService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerAvailabilityInquiryServiceTest extends TestCase
{
    public function testInquiresOnlyForDistinctAlreadyAdmittedReviewerFacts(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-availability-" .
            bin2hex(random_bytes(6));
        $id = "adversarial-reviewer-persona-review-case-" . str_repeat("a", 20);
        $case = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-review-case/v1",
            "review_case_id" => $id,
            "instance_id" => "imperium-test",
            "persona_candidate_id" =>
                "adversarial-reviewer-persona-candidate-" . str_repeat("b", 20),
            "persona_candidate_digest" => "candidate-digest",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "construction_acceptance_id" => "construction-acceptance",
            "construction_acceptance_digest" =>
                "construction-acceptance-digest",
            "requester" => "foundry.artificer",
            "escalation_recipient" => "curia.seneschal",
            "required_capability" => [
                "seat" => "foundry.reviewer.adversarial",
                "profile" => "offices/foundry/profile-reviewer-adversarial.md",
                "independence" => "distinct admitted reviewer",
            ],
            "review_scope" => "Attack the exact immutable candidate.",
            "status" =>
                "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION",
            "review_initiated" => true,
            "review_complete" => false,
            "clean_review" => false,
            "exception_authority" => false,
            "review_authority" => false,
            "production_approval" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-review-cases",
            $id,
            $case,
        );
        try {
            $service = new AdversarialReviewerAvailabilityInquiryService($root);
            $inquiry = $service->inquire($id);
            self::assertSame($inquiry, $service->inquire($id));
            self::assertSame(
                "CONSTABLE_ACTIVATION_REQUIRED",
                $inquiry["status"],
            );
            self::assertNull($inquiry["constable_occupancy"]);
            self::assertSame(
                $case["record_digest"],
                $inquiry["source_review_case_digest"],
            );
            self::assertSame(
                $case["persona_candidate_digest"],
                $inquiry["blocked_candidate"]["persona_candidate_digest"],
            );
            self::assertStringContainsString(
                "distinct admitted Persona",
                $inquiry["inventory_question"],
            );
            self::assertStringContainsString(
                "still under construction",
                $inquiry["exclusions"][0],
            );
            self::assertNotContains(
                "Persona content under construction",
                $inquiry["requested_facts"],
            );
            self::assertFalse($inquiry["authoritative_inventory_response"]);
            foreach (
                [
                    "ranking_authority",
                    "selection_authority",
                    "qualification_authority",
                    "review_authority",
                    "exception_authority",
                    "reservation_authority",
                    "retrieval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "admission_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($inquiry[$key]);
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
