<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\ExternalReviewerAdmissionDispositionService;
use PHPUnit\Framework\TestCase;
final class ExternalReviewerAdmissionDispositionServiceTest extends TestCase
{
    public function testConstableRefusesIncompleteExternalAdmissionChain(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-external-admission-" .
            bin2hex(random_bytes(6));
        $id = "blackquill-external-reviewer-intake-" . str_repeat("a", 20);
        $intake = [
            "schema" => "imperium.foundry-external-reviewer-persona-intake/v1",
            "intake_id" => $id,
            "instance_id" => "imperium-test",
            "target_review_case_id" => "review-case",
            "external_persona" => [
                "persona_id" =>
                    "foundry.external.blackquill-adversarial-reviewer",
                "persona_version" => "1.0.0",
            ],
            "status" => "SEALED_PENDING_GARRISON_ADMISSION_EVIDENCE",
            "source_validated" => true,
            "admission_claim_state" => "UNVERIFIED",
            "eligible_for_review_occupation" => false,
            "admission_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/offices/foundry/external-reviewer-intakes",
            $id,
            $intake,
        );
        $bindingId = "garrison-constable-binding-" . str_repeat("b", 20);
        $binding = [
            "schema" => "imperium.garrison-constable-occupancy/v1",
            "binding_id" => $bindingId,
            "instance_id" => "imperium-test",
            "seat" => "garrison.constable",
            "manifestation_id" => "constable",
            "occupancy_generation" => 1,
            "status" => "ACTIVE",
            "persona_admission_disposition_authority" => true,
            "selection_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root . "/var/imperium/offices/garrison/occupancy",
            $bindingId,
            $binding,
        );
        try {
            $s = new ExternalReviewerAdmissionDispositionService($root);
            $r = $s->evaluate($id);
            self::assertSame($r, $s->evaluate($id));
            self::assertSame(
                "REFUSED_INCOMPLETE_ADMISSION_CHAIN",
                $r["disposition"],
            );
            self::assertFalse($r["admitted"]);
            self::assertFalse($r["custody_record_created"]);
            self::assertFalse($r["eligible_for_review_occupation"]);
            self::assertTrue($r["admission_authority_consumed"]);
            self::assertSame(4, count($r["evaluation"]["missing_evidence"]));
            foreach (
                [
                    "review_authority",
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
