<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\AdversarialReviewerConstructionAuthorizationService;
use PHPUnit\Framework\TestCase;

final class AdversarialReviewerConstructionAuthorizationServiceTest extends
    TestCase
{
    public function testDeliversExactConstructionAuthorityWithoutDownstreamAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-authorization-" .
            bin2hex(random_bytes(6));
        $caseId = "adversarial-reviewer-provisioning-" . str_repeat("a", 20);
        $case = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-provisioning-case/v1",
            "case_id" => $caseId,
            "source_demand_id" =>
                "foundry-adversarial-reviewer-demand-" . str_repeat("b", 20),
            "source_demand_digest" => "demand-digest",
            "instance_id" => "imperium-test",
            "target_seat" => ["seat" => "foundry.reviewer.adversarial"],
            "candidate_id" => "candidate",
            "candidate_digest" => "candidate-digest",
            "profile_source" =>
                "offices/foundry/profile-reviewer-adversarial.md",
            "status" => "BLOCKED_PENDING_ADVERSARIAL_REVIEWER_PERSONA",
            "persona_construction_required" => true,
            "mission_persona_selection_required" => false,
            "construction_authority" => false,
            "commission_authority" => false,
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
        try {
            $service = new AdversarialReviewerConstructionAuthorizationService(
                $root,
            );
            $delivery = $service->authorize($caseId);
            self::assertSame($delivery, $service->authorize($caseId));
            self::assertSame(
                "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
                $delivery["status"],
            );
            self::assertTrue($delivery["construction_authority"]);
            self::assertFalse($delivery["construction_authority_exercisable"]);
            self::assertSame($caseId, $delivery["source_case_id"]);
            self::assertSame(
                $case["record_digest"],
                $delivery["source_case_digest"],
            );
            self::assertSame(
                $case["candidate_id"],
                $delivery["authorization_act"]["candidate_id"],
            );
            self::assertTrue(
                $delivery["authorization_act"]["construction_authority"],
            );
            foreach (
                [
                    "commission_authority",
                    "review_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "admission_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $authority
            ) {
                self::assertFalse($delivery[$authority]);
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
