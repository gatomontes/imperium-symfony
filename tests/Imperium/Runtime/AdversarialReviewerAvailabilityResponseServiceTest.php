<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\AdversarialReviewerAvailabilityResponseService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerAvailabilityResponseServiceTest extends TestCase
{
    public function testReturnsAuthoritativeExactAbsenceWithoutInterpretingIt(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-response-" .
            bin2hex(random_bytes(6));
        $inquiryId =
            "adversarial-reviewer-availability-inquiry-" . str_repeat("a", 20);
        $candidateId =
            "adversarial-reviewer-persona-candidate-" . str_repeat("b", 20);
        $inquiry = [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-availability-inquiry/v1",
            "inquiry_id" => $inquiryId,
            "instance_id" => "imperium-test",
            "requester" => ["office" => "curia", "seat" => "curia.seneschal"],
            "recipient" => [
                "office" => "garrison",
                "seat" => "garrison.constable",
            ],
            "source_review_case_id" => "review-case",
            "source_review_case_digest" => "review-case-digest",
            "blocked_candidate" => [
                "persona_candidate_id" => $candidateId,
                "persona_candidate_digest" => "candidate-digest",
                "persona_id" => "foundry.adversarial-reviewer",
                "persona_version" => "1.0.0",
            ],
            "inventory_question" =>
                "Does Garrison hold a distinct admitted Persona?",
            "requested_facts" => [
                "exact admitted Persona identity and version",
            ],
            "constable_occupancy" => null,
            "status" => "CONSTABLE_ACTIVATION_REQUIRED",
            "authoritative_inventory_response" => false,
            "selection_authority" => false,
            "review_authority" => false,
            "exception_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $occupancy = [
            "schema" => "imperium.garrison-constable-occupancy/v1",
            "binding_id" => "garrison-constable-binding-" . str_repeat("c", 20),
            "instance_id" => "imperium-test",
            "seat" => "garrison.constable",
            "manifestation_id" => "constable-test",
            "occupancy_generation" => 1,
            "status" => "ACTIVE",
            "inventory_response_authority" => true,
            "selection_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root . "/var/imperium/offices/garrison/inbox",
            $inquiryId,
            $inquiry,
        );
        $this->write(
            $root . "/var/imperium/offices/garrison/occupancy",
            $occupancy["binding_id"],
            $occupancy,
        );
        $unrelated = [
            "schema" => "imperium.garrison-persona-custody/v1",
            "persona_id" => "curia.secretary",
            "persona_version" => "1.0.0",
            "custody_state" => "ADMITTED_HELD",
            "availability" => "AVAILABLE",
        ];
        $this->write(
            $root . "/var/imperium/offices/garrison/custody",
            "unrelated-persona",
            $unrelated,
        );
        try {
            $service = new AdversarialReviewerAvailabilityResponseService(
                $root,
            );
            $response = $service->respond($inquiryId);
            self::assertSame($response, $service->respond($inquiryId));
            self::assertSame(
                "AUTHORITATIVE_INVENTORY_FACTS_DELIVERED_TO_CURIA",
                $response["status"],
            );
            self::assertSame(
                "NO_DISTINCT_ADMITTED_ADVERSARIAL_REVIEWER_PERSONA_HELD",
                $response["ledger_finding"],
            );
            self::assertSame(1, $response["custody_ledger_record_count"]);
            self::assertSame(0, $response["matching_identity_record_count"]);
            self::assertSame([], $response["matching_identity_records"]);
            self::assertTrue($response["authoritative_inventory_response"]);
            self::assertTrue($response["identity_match_only"]);
            self::assertFalse($response["suitability_determined"]);
            self::assertFalse($response["availability_interpreted"]);
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
                self::assertFalse($response[$key]);
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
