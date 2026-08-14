<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\BlackquillProductionAuthorizationService;
use PHPUnit\Framework\TestCase;
final class BlackquillProductionAuthorizationServiceTest extends TestCase
{
    public function testAuthorizesExactProductionButRequiresFoundryAcceptance(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-auth-" .
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
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/external-reviewer-production-cases",
            $caseId,
            $c,
        );
        $id =
            "blackquill-production-authorization-request-" .
            str_repeat("b", 20);
        $r = [
            "schema" =>
                "imperium.curia-blackquill-production-authorization-request/v1",
            "request_id" => $id,
            "instance_id" => "imperium-test",
            "recipient" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "source_case_id" => $caseId,
            "source_case_digest" => $c["record_digest"],
            "lineage" => ["source_intake_id" => "intake"],
            "persona" => $persona,
            "requested_scope" => ["seal candidate"],
            "requested_authority" =>
                "EXACT_BLACKQUILL_PERSONA_PRODUCTION_PROCESSING_ONLY",
            "status" => "PENDING_IMPERATOR_DECISION",
            "approval_recorded" => false,
            "production_authority" => false,
            "review_findings_authority" => false,
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/curia/blackquill-production-authorization-requests",
            $id,
            $r,
        );
        try {
            $s = new BlackquillProductionAuthorizationService($root);
            $d = $s->authorize($id);
            self::assertSame($d, $s->authorize($id));
            self::assertSame(
                "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
                $d["status"],
            );
            self::assertTrue($d["production_authority"]);
            self::assertFalse($d["production_authority_exercisable"]);
            self::assertTrue($d["authorization_act"]["production_authority"]);
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
                self::assertFalse($d[$k]);
            }
        } finally {
            $this->removeTree($root);
        }
    }
    private function write(string $d, string $id, array &$r): void
    {
        mkdir($d, 0770, true);
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
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
