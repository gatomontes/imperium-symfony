<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\BlackquillReviewResolutionRequestService;
use PHPUnit\Framework\TestCase;
final class BlackquillReviewResolutionRequestServiceTest extends TestCase
{
    public function testPresentsExactConstraintWithoutGrantingResolutionAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-resolution-" .
            bin2hex(random_bytes(6));
        $id = "blackquill-persona-review-case-" . str_repeat("a", 20);
        $case = [
            "schema" => "imperium.foundry-blackquill-persona-review-case/v1",
            "review_case_id" => $id,
            "instance_id" => "imperium-test",
            "persona_candidate_id" => "candidate",
            "persona_candidate_digest" => "candidate-digest",
            "persona_id" => "foundry.external.blackquill-adversarial-reviewer",
            "persona_version" => "1.0.0",
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
            "requester" => "foundry.artificer",
            "escalation_recipient" => "curia.seneschal",
            "review_scope" => "Attack exact candidate.",
            "independence_constraint" => ["blackquill_cannot_review_itself"],
            "permitted_resolutions" => [
                "distinct admitted reviewer",
                "explicit exceptional protocol",
            ],
            "status" => "BLOCKED_PENDING_DISTINCT_INDEPENDENT_REVIEWER",
            "review_initiated" => true,
            "review_complete" => false,
            "clean_review" => false,
            "exception_authority" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/blackquill-persona-review-cases",
            $id,
            $case,
        );
        try {
            $service = new BlackquillReviewResolutionRequestService($root);
            $request = $service->request($id);
            self::assertSame($request, $service->request($id));
            self::assertSame("PENDING_IMPERATOR_DECISION", $request["status"]);
            self::assertSame(
                $case["record_digest"],
                $request["source_review_case_digest"],
            );
            self::assertSame(
                $case["persona_candidate_digest"],
                $request["persona_candidate_digest"],
            );
            self::assertSame(
                "INDEPENDENCE_CONSTRAINT_AUTHENTICATED",
                $request["curia_disposition"]["finding"],
            );
            self::assertSame(
                "PROHIBITED",
                $request["curia_disposition"]["self_review"],
            );
            self::assertFalse($request["decision_recorded"]);
            foreach (
                [
                    "distinct_reviewer_authority",
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
