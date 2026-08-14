<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\BlackquillProductionAuthorizationAcceptanceService;
use PHPUnit\Framework\TestCase;
final class BlackquillProductionAuthorizationAcceptanceServiceTest extends
    TestCase
{
    public function testArtificerAcceptsExactProductionOnly(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-accept-" .
            bin2hex(random_bytes(6));
        $caseId =
            "blackquill-production-remediation-case-" . str_repeat("a", 20);
        $persona = [
            "persona_id" => "foundry.external.blackquill-adversarial-reviewer",
            "persona_version" => "1.0.0",
        ];
        $c = [
            "schema" =>
                "imperium.foundry-blackquill-production-remediation-case/v1",
            "case_id" => $caseId,
            "instance_id" => "imperium-test",
            "persona" => $persona,
            "status" => "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION",
            "production_authority" => false,
            "review_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/external-reviewer-production-cases",
            $caseId,
            $c,
        );
        $act = [
            "schema" =>
                "imperium.imperator-blackquill-production-authorization/v1",
            "act_id" =>
                "blackquill-production-authorization-" . str_repeat("b", 20),
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "persona" => $persona,
            "disposition" =>
                "AUTHORIZED_FOR_EXACT_BLACKQUILL_PRODUCTION_PROCESSING",
            "production_authority" => true,
            "production_authority_exercisable" => false,
            "review_findings_authority" => false,
            "execution_authority" => false,
        ];
        $this->digest($act);
        $deliveryId =
            "blackquill-production-authorization-delivery-" .
            str_repeat("c", 20);
        $d = [
            "schema" =>
                "imperium.foundry-blackquill-production-authorization-delivery/v1",
            "delivery_id" => $deliveryId,
            "target" => "foundry.artificer",
            "instance_id" => "imperium-test",
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "authorization_act_id" => $act["act_id"],
            "authorization_act_digest" => $act["record_digest"],
            "status" => "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "production_authority" => true,
            "production_authority_exercisable" => false,
            "review_findings_authority" => false,
            "execution_authority" => false,
            "authorization_act" => $act,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/inbox/blackquill-production-authorizations",
            $deliveryId,
            $d,
        );
        $bindingId = "foundry-artificer-binding-" . str_repeat("d", 20);
        $b = [
            "schema" => "imperium.foundry-artificer-occupancy/v1",
            "binding_id" => $bindingId,
            "instance_id" => "imperium-test",
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
            $b,
        );
        try {
            $s = new BlackquillProductionAuthorizationAcceptanceService($root);
            $r = $s->accept($deliveryId, $bindingId);
            self::assertSame($r, $s->accept($deliveryId, $bindingId));
            self::assertTrue($r["recipient_acceptance"]);
            self::assertTrue($r["production_authority"]);
            self::assertTrue($r["production_authority_exercisable"]);
            self::assertSame($c["record_digest"], $r["source_case_digest"]);
            foreach (
                [
                    "review_findings_authority",
                    "senate_confirmation_authority",
                    "release_authority",
                    "admission_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $k
            ) {
                self::assertFalse($r[$k]);
            }
        } finally {
            $this->removeTree($root);
        }
    }
    private function digest(array &$r): void
    {
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
    }
    private function write(string $d, string $id, array &$r): void
    {
        mkdir($d, 0770, true);
        $this->digest($r);
        file_put_contents(
            $d . "/" . $id . ".json",
            json_encode($r, JSON_THROW_ON_ERROR),
        );
    }
    private function removeTree(string $p): void
    {
        if (!is_dir($p)) {
            return;
        }
        foreach (array_diff(scandir($p) ?: [], [".", ".."]) as $e) {
            $c = $p . "/" . $e;
            is_dir($c) ? $this->removeTree($c) : unlink($c);
        }
        rmdir($p);
    }
}
