<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedProductionApprovalService;
use PHPUnit\Framework\TestCase;

final class AdversarialReviewerBootstrapSeedProductionApprovalServiceTest
    extends TestCase
{
    public function testArtificerApprovesExactAcceptedSeedWithoutAdmissionAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-seed-production-" .
            bin2hex(random_bytes(6));
        $candidateId =
            "adversarial-reviewer-persona-candidate-" . str_repeat("a", 20);
        $basis = [
            "name" => "Blackquill",
            "kind" => "persona-design-basis",
            "derivation_basis" =>
                "user-designated Blackquill critical-analysis contract",
            "method" => ["pressure-test"],
            "identity_imported" => false,
            "institution_imported" => false,
            "authority_imported" => false,
        ];
        $candidate = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-candidate/v1",
            "persona_candidate_id" => $candidateId,
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "supersedes" => null,
            "instance_id" => "imperium-test",
            "construction_acceptance_id" => "construction-acceptance",
            "construction_acceptance_digest" => "construction-digest",
            "authorized_review_target" => [
                "candidate_id" => "target",
                "candidate_digest" => "target-digest",
            ],
            "sources" => ["design_basis" => $basis],
            "status" => "SEALED_PENDING_FOUNDRY_REVIEW",
            "construction_complete" => true,
            "sealed" => true,
            "production_approval" => false,
            "review_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates",
            $candidateId,
            $candidate,
        );
        $bindingId = "foundry-artificer-binding-" . str_repeat("b", 20);
        $binding = [
            "schema" => "imperium.foundry-artificer-occupancy/v1",
            "binding_id" => $bindingId,
            "instance_id" => "imperium-test",
            "office" => "foundry",
            "seat" => "foundry.artificer",
            "manifestation_id" => "artificer",
            "occupancy_generation" => 1,
            "status" => "ACTIVE",
            "binding_atomic" => true,
            "execution_authority" => false,
        ];
        $this->write(
            $root . "/var/imperium/offices/foundry/occupancy",
            $bindingId,
            $binding,
        );
        $boundary = [
            "initial_version_only" => true,
            "exact_candidate_only" => true,
            "predecessor_review_required" => false,
            "self_review_prohibited" => true,
            "general_review_waiver" => false,
            "general_precedent" => false,
            "successor_versions_require_ordinary_independent_review" => true,
            "candidate_revision_terminates_authority" => true,
            "identity_or_digest_change_terminates_authority" => true,
        ];
        $acceptanceId =
            "foundry-adversarial-reviewer-bootstrap-seed-acceptance-" .
            str_repeat("c", 20);
        $acceptance = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-authorization-acceptance/v1",
            "acceptance_id" => $acceptanceId,
            "instance_id" => "imperium-test",
            "delivery_id" => "delivery",
            "delivery_digest" => "delivery-digest",
            "authorization_act_id" => "act",
            "authorization_act_digest" => "act-digest",
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "construction_acceptance_id" => "construction-acceptance",
            "construction_acceptance_digest" => "construction-digest",
            "authorized_review_target" =>
                $candidate["authorized_review_target"],
            "design_basis" => $basis,
            "bootstrap_seed_boundary" => $boundary,
            "binding_id" => $bindingId,
            "binding_digest" => $binding["record_digest"],
            "actor" => [
                "seat" => "foundry.artificer",
                "manifestation_id" => "artificer",
                "occupancy_generation" => 1,
            ],
            "disposition" =>
                "ACCEPTED_FOR_EXACT_INITIAL_REVIEWER_BOOTSTRAP_SEED_PROCESSING",
            "recipient_acceptance" => true,
            "bootstrap_seed_authority" => true,
            "bootstrap_seed_authority_exercisable" => true,
            "review_findings_authority" => false,
            "production_approval" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-acceptances",
            $acceptanceId,
            $acceptance,
        );
        try {
            $service = new AdversarialReviewerBootstrapSeedProductionApprovalService(
                $root,
            );
            $approval = $service->approve($acceptanceId);
            self::assertSame($approval, $service->approve($acceptanceId));
            self::assertTrue($approval["production_approval"]);
            self::assertTrue($approval["bootstrap_seed_authority_consumed"]);
            self::assertSame(
                "APPROVED_PENDING_GARRISON_ADMISSION",
                $approval["status"],
            );
            self::assertSame(
                $candidate["record_digest"],
                $approval["persona_candidate_digest"],
            );
            foreach (
                [
                    "review_findings_authority",
                    "profile_approval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "admission_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($approval[$key]);
            }
        } finally {
            $this->removeTree($root);
        }
    }

    private function digest(array &$record): void
    {
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
    }
    private function write(string $directory, string $id, array &$record): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0770, true);
        }
        $this->digest($record);
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
