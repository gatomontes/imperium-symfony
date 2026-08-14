<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerConstructionAuthorizationAcceptanceService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerConstructionAuthorizationAcceptanceServiceTest
    extends TestCase
{
    public function testOccupiedArtificerAcceptsOnlyExactConstructionAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-acceptance-" .
            bin2hex(random_bytes(6));
        $caseId = "adversarial-reviewer-provisioning-" . str_repeat("a", 20);
        $case = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-provisioning-case/v1",
            "case_id" => $caseId,
            "instance_id" => "imperium-test",
            "candidate_id" => "candidate",
            "candidate_digest" => "candidate-digest",
            "target_seat" => ["seat" => "foundry.reviewer.adversarial"],
            "profile_source" =>
                "offices/foundry/profile-reviewer-adversarial.md",
            "status" => "BLOCKED_PENDING_ADVERSARIAL_REVIEWER_PERSONA",
            "construction_authority" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/mastermason/activation-cases",
            $caseId,
            $case,
        );
        $actId =
            "adversarial-reviewer-construction-authorization-" .
            str_repeat("b", 20);
        $act = [
            "schema" =>
                "imperium.imperator-adversarial-reviewer-construction-authorization/v1",
            "kind" =>
                "EXACT_ADVERSARIAL_REVIEWER_PERSONA_CONSTRUCTION_AUTHORIZATION",
            "act_id" => $actId,
            "source_case_id" => $caseId,
            "source_case_digest" => $case["record_digest"],
            "candidate_id" => $case["candidate_id"],
            "candidate_digest" => $case["candidate_digest"],
            "target_seat" => $case["target_seat"],
            "disposition" =>
                "AUTHORIZED_FOR_EXACT_REVIEWER_PERSONA_CONSTRUCTION",
            "construction_authority" => true,
            "construction_authority_exercisable" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
        ];
        $this->digest($act);
        $deliveryId =
            "adversarial-reviewer-construction-delivery-" . str_repeat("c", 20);
        $delivery = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-construction-authorization-delivery/v1",
            "delivery_id" => $deliveryId,
            "office" => "foundry",
            "target" => "foundry.artificer",
            "instance_id" => "imperium-test",
            "source_case_id" => $caseId,
            "source_case_digest" => $case["record_digest"],
            "authorization_act_id" => $actId,
            "authorization_act_digest" => $act["record_digest"],
            "status" => "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "construction_authority" => true,
            "construction_authority_exercisable" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "authorization_act" => $act,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/inbox/adversarial-reviewer-construction-authorizations",
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
            $service = new AdversarialReviewerConstructionAuthorizationAcceptanceService(
                $root,
            );
            $acceptance = $service->accept($deliveryId, $bindingId);
            self::assertSame(
                $acceptance,
                $service->accept($deliveryId, $bindingId),
            );
            self::assertTrue($acceptance["recipient_acceptance"]);
            self::assertTrue($acceptance["construction_authority"]);
            self::assertTrue($acceptance["construction_authority_exercisable"]);
            self::assertSame($caseId, $acceptance["source_case_id"]);
            self::assertSame("candidate", $acceptance["candidate_id"]);
            foreach (
                [
                    "review_authority",
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
