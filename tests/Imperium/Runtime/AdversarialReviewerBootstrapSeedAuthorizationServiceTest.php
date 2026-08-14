<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\AdversarialReviewerBootstrapSeedAuthorizationService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerBootstrapSeedAuthorizationServiceTest extends
    TestCase
{
    public function testAuthorizesOnlyExactInitialSeedWithoutDownstreamAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-seed-auth-" .
            bin2hex(random_bytes(6));
        $id = "adversarial-reviewer-persona-candidate-" . str_repeat("a", 20);
        $candidate = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-candidate/v1",
            "persona_candidate_id" => $id,
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "supersedes" => null,
            "instance_id" => "imperium-test",
            "construction_acceptance_id" => "acceptance",
            "construction_acceptance_digest" => "acceptance-digest",
            "authorization_act_id" => "construction-authorization",
            "authorization_act_digest" => "construction-authorization-digest",
            "source_case_id" => "source-case",
            "source_case_digest" => "source-case-digest",
            "authorized_review_target" => [
                "candidate_id" => "target",
                "candidate_digest" => "target-digest",
            ],
            "artificer" => ["seat" => "foundry.artificer"],
            "template" => [
                "schema" => "imperium.persona/v1",
                "version" => "1.0.0",
            ],
            "sources" => [
                "design_basis" => [
                    "name" => "Blackquill",
                    "kind" => "persona-design-basis",
                    "derivation_basis" =>
                        "user-designated Blackquill critical-analysis contract",
                    "method" => ["attack the strongest plausible reading"],
                    "identity_imported" => false,
                    "institution_imported" => false,
                    "authority_imported" => false,
                ],
            ],
            "persona" => ["identity" => "Independent Adversarial Reviewer"],
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
            $id,
            $candidate,
        );
        try {
            $service = new AdversarialReviewerBootstrapSeedAuthorizationService(
                $root,
            );
            $delivery = $service->authorize($id);
            self::assertSame($delivery, $service->authorize($id));
            self::assertSame(
                "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
                $delivery["status"],
            );
            self::assertSame(
                $candidate["record_digest"],
                $delivery["persona_candidate_digest"],
            );
            self::assertTrue($delivery["bootstrap_seed_authority"]);
            self::assertFalse(
                $delivery["bootstrap_seed_authority_exercisable"],
            );
            $boundary =
                $delivery["authorization_act"]["bootstrap_seed_boundary"];
            self::assertTrue($boundary["initial_version_only"]);
            self::assertTrue($boundary["exact_candidate_only"]);
            self::assertFalse($boundary["predecessor_review_required"]);
            self::assertTrue($boundary["self_review_prohibited"]);
            self::assertFalse($boundary["general_review_waiver"]);
            self::assertFalse($boundary["general_precedent"]);
            self::assertTrue(
                $boundary[
                    "successor_versions_require_ordinary_independent_review"
                ],
            );
            self::assertNull($delivery["recipient_acceptance"]);
            foreach (
                [
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
                self::assertFalse($delivery[$key]);
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
