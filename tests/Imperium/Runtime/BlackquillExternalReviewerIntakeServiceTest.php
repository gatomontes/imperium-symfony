<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\BlackquillExternalReviewerIntakeService;
use PHPUnit\Framework\TestCase;
final class BlackquillExternalReviewerIntakeServiceTest extends TestCase
{
    public function testSealsAttributedExternalCandidateWithoutInventingAdmission(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-intake-" .
            bin2hex(random_bytes(6));
        $caseId =
            "adversarial-reviewer-persona-review-case-" . str_repeat("a", 20);
        $case = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-review-case/v1",
            "review_case_id" => $caseId,
            "instance_id" => "imperium-test",
            "persona_candidate_id" => "candidate",
            "persona_candidate_digest" => "candidate-digest",
            "status" =>
                "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION",
            "review_initiated" => true,
            "review_complete" => false,
            "review_authority" => false,
            "sealed" => true,
        ];
        $this->writeRecord(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-review-cases",
            $caseId,
            $case,
        );
        $this->writeSource(
            $root .
                "/offices/foundry/personas/blackquill-adversarial-reviewer.md",
        );
        try {
            $service = new BlackquillExternalReviewerIntakeService($root);
            $intake = $service->intake($caseId);
            self::assertSame($intake, $service->intake($caseId));
            self::assertSame(
                "foundry.external.blackquill-adversarial-reviewer",
                $intake["external_persona"]["persona_id"],
            );
            self::assertSame(
                "SEALED_PENDING_GARRISON_ADMISSION_EVIDENCE",
                $intake["status"],
            );
            self::assertSame("UNVERIFIED", $intake["admission_claim_state"]);
            self::assertTrue($intake["source_validated"]);
            self::assertFalse(
                $intake["external_persona"]["source"]["authority_imported"],
            );
            self::assertFalse($intake["eligible_for_review_occupation"]);
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
                self::assertFalse($intake[$key]);
            }
        } finally {
            $this->removeTree($root);
        }
    }
    private function writeRecord(
        string $directory,
        string $id,
        array &$record,
    ): void {
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
    private function writeSource(string $path): void
    {
        mkdir(dirname($path), 0770, true);
        file_put_contents($path, "# Blackquill external reviewer");
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
