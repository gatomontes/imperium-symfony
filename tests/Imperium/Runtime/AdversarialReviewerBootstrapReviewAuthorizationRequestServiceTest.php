<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\AdversarialReviewerBootstrapReviewAuthorizationRequestService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerBootstrapReviewAuthorizationRequestServiceTest
    extends TestCase
{
    public function testRequestsExactOneTimeProtocolWithoutGrantingIt(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-bootstrap-review-request-" .
            bin2hex(random_bytes(6));
        $caseId =
            "adversarial-reviewer-persona-review-case-" . str_repeat("a", 20);
        $candidate = [
            "persona_candidate_id" =>
                "adversarial-reviewer-persona-candidate-" . str_repeat("b", 20),
            "persona_candidate_digest" => "candidate-digest",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "construction_acceptance_id" => "construction-acceptance",
            "construction_acceptance_digest" =>
                "construction-acceptance-digest",
        ];
        $case = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-review-case/v1",
            "review_case_id" => $caseId,
            "instance_id" => "imperium-test",
            ...$candidate,
            "artificer" => ["seat" => "foundry.artificer"],
            "required_capability" => ["seat" => "foundry.reviewer.adversarial"],
            "bootstrap_constraint" => ["candidate_cannot_review_itself"],
            "review_scope" => "Attack the exact immutable candidate.",
            "status" =>
                "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION",
            "review_initiated" => true,
            "review_complete" => false,
            "review_authority" => false,
            "production_approval" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-review-cases",
            $caseId,
            $case,
        );
        $responseId =
            "adversarial-reviewer-availability-response-" . str_repeat("c", 20);
        $response = [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-availability-response/v1",
            "response_id" => $responseId,
            "instance_id" => "imperium-test",
            "source_inquiry_id" => "inquiry",
            "source_inquiry_digest" => "inquiry-digest",
            "source_review_case_id" => $caseId,
            "source_review_case_digest" => $case["record_digest"],
            "blocked_candidate" => $candidate,
            "responder" => ["seat" => "garrison.constable"],
            "recipient" => ["office" => "curia", "seat" => "curia.seneschal"],
            "custody_ledger_record_count" => 3,
            "custody_ledger_digest" => "sha256:ledger",
            "matching_identity_record_count" => 0,
            "matching_identity_records" => [],
            "ledger_finding" =>
                "NO_DISTINCT_ADMITTED_ADVERSARIAL_REVIEWER_PERSONA_HELD",
            "status" => "AUTHORITATIVE_INVENTORY_FACTS_DELIVERED_TO_CURIA",
            "authoritative_inventory_response" => true,
            "identity_match_only" => true,
            "suitability_determined" => false,
            "availability_interpreted" => false,
            "selection_authority" => false,
            "review_authority" => false,
            "exception_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/curia/adversarial-reviewer-availability-responses",
            $responseId,
            $response,
        );
        try {
            $service = new AdversarialReviewerBootstrapReviewAuthorizationRequestService(
                $root,
            );
            $request = $service->request($responseId);
            self::assertSame($request, $service->request($responseId));
            self::assertSame("PENDING_IMPERATOR_DECISION", $request["status"]);
            self::assertSame(
                $case["record_digest"],
                $request["source_review_case_digest"],
            );
            self::assertSame(
                $response["record_digest"],
                $request["source_availability_response_digest"],
            );
            self::assertSame(
                "EXACT_ONE_TIME_ADVERSARIAL_REVIEWER_BOOTSTRAP_REVIEW_PROTOCOL_ONLY",
                $request["requested_authority"],
            );
            self::assertFalse($request["curia_finding"]["exception_implied"]);
            self::assertFalse(
                $request["requested_protocol"]["general_precedent"],
            );
            self::assertFalse(
                $request["requested_protocol"]["creates_persona"],
            );
            self::assertFalse(
                $request["requested_protocol"]["creates_institution"],
            );
            self::assertFalse($request["approval_recorded"]);
            foreach (
                [
                    "protocol_authority",
                    "reviewer_designation_authority",
                    "review_findings_authority",
                    "production_approval",
                    "profile_approval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "admission_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($request[$key]);
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
