<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\BlackquillProductionAuthorizationRequestService;
use PHPUnit\Framework\TestCase;
final class BlackquillProductionAuthorizationRequestServiceTest extends TestCase
{
    public function testPresentsExactNonAuthorizingRequest(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-request-" .
            bin2hex(random_bytes(6));
        $id = "blackquill-production-remediation-case-" . str_repeat("a", 20);
        $c = [
            "schema" =>
                "imperium.foundry-blackquill-production-remediation-case/v1",
            "case_id" => $id,
            "instance_id" => "imperium-test",
            "source_disposition_id" => "disposition",
            "source_disposition_digest" => "disposition-digest",
            "source_intake_id" => "intake",
            "source_intake_digest" => "intake-digest",
            "target_review_case_id" => "review",
            "target_review_case_digest" => "review-digest",
            "persona" => [
                "persona_id" =>
                    "foundry.external.blackquill-adversarial-reviewer",
                "persona_version" => "1.0.0",
            ],
            "status" => "BLOCKED_PENDING_CURIA_PRODUCTION_AUTHORIZATION",
            "remediation_open" => true,
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
            $id,
            $c,
        );
        try {
            $s = new BlackquillProductionAuthorizationRequestService($root);
            $r = $s->request($id);
            self::assertSame($r, $s->request($id));
            self::assertSame("PENDING_IMPERATOR_DECISION", $r["status"]);
            self::assertSame($c["record_digest"], $r["source_case_digest"]);
            self::assertSame(
                "EXACT_BLACKQUILL_PERSONA_PRODUCTION_PROCESSING_ONLY",
                $r["requested_authority"],
            );
            self::assertFalse($r["approval_recorded"]);
            foreach (
                [
                    "production_authority",
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
