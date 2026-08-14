<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\BlackquillProductionRemediationCaseService;
use PHPUnit\Framework\TestCase;
final class BlackquillProductionRemediationCaseServiceTest extends TestCase
{
    public function testOpensExactNonAuthorizingProductionRoute(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-remediation-" .
            bin2hex(random_bytes(6));
        $intakeId =
            "blackquill-external-reviewer-intake-" . str_repeat("a", 20);
        $persona = [
            "persona_id" => "foundry.external.blackquill-adversarial-reviewer",
            "persona_version" => "1.0.0",
        ];
        $i = [
            "schema" => "imperium.foundry-external-reviewer-persona-intake/v1",
            "intake_id" => $intakeId,
            "instance_id" => "imperium-test",
            "external_persona" => $persona,
            "target_review_case_id" => "review-case",
            "target_review_case_digest" => "review-digest",
            "source_validated" => true,
            "eligible_for_review_occupation" => false,
        ];
        $this->write(
            $root . "/var/imperium/offices/foundry/external-reviewer-intakes",
            $intakeId,
            $i,
        );
        $id =
            "garrison-external-reviewer-admission-disposition-" .
            str_repeat("b", 20);
        $missing = [
            "production-approved immutable Persona artifact",
            "Foundry release packet for the exact Persona version",
            "passing Senate confirmation record for the exact manifested candidate",
            "authorized admission handoff correlation identity",
        ];
        $d = [
            "schema" =>
                "imperium.garrison-external-reviewer-admission-disposition/v1",
            "disposition_id" => $id,
            "instance_id" => "imperium-test",
            "source_intake_id" => $intakeId,
            "source_intake_digest" => $i["record_digest"],
            "persona" => $persona,
            "evaluation" => [
                "identity_and_source_integrity" => "VALID",
                "admission_chain" => "INCOMPLETE",
                "missing_evidence" => $missing,
            ],
            "disposition" => "REFUSED_INCOMPLETE_ADMISSION_CHAIN",
            "status" => "SEALED_RETURN_TO_FOUNDRY",
            "return_recipient" => "foundry.artificer",
            "admitted" => false,
            "custody_record_created" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/offices/garrison/admission-dispositions",
            $id,
            $d,
        );
        try {
            $s = new BlackquillProductionRemediationCaseService($root);
            $r = $s->open($id);
            self::assertSame($r, $s->open($id));
            self::assertSame(
                "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION",
                $r["status"],
            );
            self::assertTrue($r["remediation_open"]);
            self::assertSame($i["record_digest"], $r["source_intake_digest"]);
            self::assertSame(6, count($r["required_production_path"]));
            foreach (
                [
                    "production_authority",
                    "review_authority",
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
