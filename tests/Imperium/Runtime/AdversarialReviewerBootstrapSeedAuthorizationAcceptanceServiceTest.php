<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedAuthorizationAcceptanceService;
use PHPUnit\Framework\TestCase;

final class AdversarialReviewerBootstrapSeedAuthorizationAcceptanceServiceTest
    extends TestCase
{
    public function testOccupiedArtificerAcceptsOnlyExactInitialBootstrapSeedAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-seed-acceptance-" .
            bin2hex(random_bytes(6));
        $candidateId =
            "adversarial-reviewer-persona-candidate-" . str_repeat("a", 20);
        $designBasis = [
            "name" => "Blackquill",
            "kind" => "persona-design-basis",
            "derivation_basis" =>
                "user-designated Blackquill critical-analysis contract",
            "method" => ["pressure-test unsupported assumptions"],
            "identity_imported" => false,
            "institution_imported" => false,
            "authority_imported" => false,
        ];
        $candidate = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-candidate/v1",
            "persona_candidate_id" => $candidateId,
            "instance_id" => "imperium-test",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "construction_acceptance_id" => "construction-acceptance",
            "construction_acceptance_digest" =>
                "construction-acceptance-digest",
            "authorized_review_target" => [
                "seat" => "foundry.reviewer.adversarial",
            ],
            "sources" => ["design_basis" => $designBasis],
            "status" => "SEALED_PENDING_FOUNDRY_REVIEW",
            "sealed" => true,
            "production_approval" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates",
            $candidateId,
            $candidate,
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
        $actId =
            "adversarial-reviewer-bootstrap-seed-authorization-" .
            str_repeat("b", 20);
        $act = [
            "schema" =>
                "imperium.imperator-adversarial-reviewer-bootstrap-seed-authorization/v1",
            "kind" =>
                "EXACT_INITIAL_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_AUTHORIZATION",
            "act_id" => $actId,
            "instance_id" => "imperium-test",
            "actor" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "authority_basis" => "explicit-imperator-directive",
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "construction_acceptance_id" => "construction-acceptance",
            "construction_acceptance_digest" =>
                "construction-acceptance-digest",
            "authorized_review_target" =>
                $candidate["authorized_review_target"],
            "design_basis" => $designBasis,
            "bootstrap_seed_boundary" => $boundary,
            "disposition" =>
                "AUTHORIZED_FOR_EXACT_INITIAL_REVIEWER_BOOTSTRAP_SEED_PROCESSING",
            "bootstrap_seed_authority" => true,
            "bootstrap_seed_authority_exercisable" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $this->digest($act);
        $deliveryId =
            "adversarial-reviewer-bootstrap-seed-authorization-delivery-" .
            str_repeat("c", 20);
        $delivery = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-authorization-delivery/v1",
            "delivery_id" => $deliveryId,
            "instance_id" => "imperium-test",
            "office" => "foundry",
            "target" => "foundry.artificer",
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "authorization_act_id" => $actId,
            "authorization_act_digest" => $act["record_digest"],
            "authorization_act" => $act,
            "status" => "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "bootstrap_seed_authority" => true,
            "bootstrap_seed_authority_exercisable" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
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
                "/var/imperium/offices/foundry/inbox/adversarial-reviewer-bootstrap-seed-authorizations",
            $deliveryId,
            $delivery,
        );
        $bindingId = "foundry-artificer-binding-" . str_repeat("d", 20);
        $binding = [
            "schema" => "imperium.foundry-artificer-occupancy/v1",
            "binding_id" => $bindingId,
            "instance_id" => "imperium-test",
            "office" => "foundry",
            "seat" => "foundry.artificer",
            "manifestation_id" => "artificer-manifestation",
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

        try {
            $service = new AdversarialReviewerBootstrapSeedAuthorizationAcceptanceService(
                $root,
            );
            $acceptance = $service->accept($deliveryId, $bindingId);
            self::assertSame(
                $acceptance,
                $service->accept($deliveryId, $bindingId),
            );
            self::assertTrue($acceptance["recipient_acceptance"]);
            self::assertTrue($acceptance["bootstrap_seed_authority"]);
            self::assertTrue(
                $acceptance["bootstrap_seed_authority_exercisable"],
            );
            self::assertSame($candidateId, $acceptance["persona_candidate_id"]);
            self::assertSame(
                $candidate["record_digest"],
                $acceptance["persona_candidate_digest"],
            );
            self::assertSame($boundary, $acceptance["bootstrap_seed_boundary"]);
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
                self::assertFalse($acceptance[$key]);
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
