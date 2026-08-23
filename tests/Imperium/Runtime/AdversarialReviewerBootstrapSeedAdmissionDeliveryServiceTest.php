<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedAdmissionDeliveryService;
use PHPUnit\Framework\TestCase;

final class AdversarialReviewerBootstrapSeedAdmissionDeliveryServiceTest extends
    TestCase
{
    public function testFoundryDeliversExactApprovedPersonaWithoutAdmissionAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-seed-delivery-" .
            bin2hex(random_bytes(6));
        $candidateId =
            "adversarial-reviewer-persona-candidate-" . str_repeat("a", 20);
        $basis = [
            "name" => "Blackquill",
            "kind" => "persona-design-basis",
            "method" => ["pressure-test"],
        ];
        $candidate = [
            "persona_candidate_id" => $candidateId,
            "instance_id" => "imperium-test",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "construction_acceptance_id" => "construction",
            "construction_acceptance_digest" => "construction-digest",
            "authorized_review_target" => ["candidate_id" => "target"],
            "sources" => ["design_basis" => $basis],
            "persona" => ["role" => "Adversarial Reviewer"],
            "construction_complete" => true,
            "sealed" => true,
            "production_approval" => false,
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
            "seat" => "foundry.artificer",
            "manifestation_id" => "artificer",
            "occupancy_generation" => 1,
            "status" => "ACTIVE",
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
        $approvalId =
            "adversarial-reviewer-bootstrap-seed-production-approval-" .
            str_repeat("c", 20);
        $approval = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-production-approval/v1",
            "production_approval_id" => $approvalId,
            "instance_id" => "imperium-test",
            "bootstrap_seed_acceptance_id" => "acceptance",
            "bootstrap_seed_acceptance_digest" => "acceptance-digest",
            "authorization_act_id" => "act",
            "authorization_act_digest" => "act-digest",
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "construction_acceptance_id" => "construction",
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
                "APPROVED_AS_EXACT_INITIAL_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED",
            "status" => "APPROVED_PENDING_GARRISON_ADMISSION",
            "bootstrap_seed_authority_consumed" => true,
            "production_approval" => true,
            "review_findings_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-production-approvals",
            $approvalId,
            $approval,
        );
        try {
            $service = new AdversarialReviewerBootstrapSeedAdmissionDeliveryService(
                $root,
            );
            $delivery = $service->deliver($approvalId);
            self::assertSame($delivery, $service->deliver($approvalId));
            self::assertSame(
                "DELIVERED_PENDING_GARRISON_ACCEPTANCE",
                $delivery["status"],
            );
            self::assertSame($candidate["persona"], $delivery["persona"]);
            self::assertTrue($delivery["production_approval"]);
            self::assertFalse($delivery["admission_authority"]);
            self::assertNull($delivery["admission_decision"]);
            foreach (
                [
                    "profile_approval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "review_findings_authority",
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
