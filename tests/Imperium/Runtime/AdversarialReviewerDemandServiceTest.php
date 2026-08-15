<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerDemandService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerDemandServiceTest extends TestCase
{
    public function testDemandsIndependentExactCandidateOccupation(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-adversarial-demand-" .
            bin2hex(random_bytes(6));
        $candidateId = "subordinate-persona-candidate-" . str_repeat("a", 20);
        $candidate = [
            "schema" => "imperium.foundry-subordinate-persona-candidate/v1",
            "candidate_id" => $candidateId,
            "status" => "ASSEMBLED_PENDING_FOUNDRY_REVIEW",
            "dispatch_kind" => "SPECIFICATION_REVISION_REISSUE",
            "superseded_commissions" => [
                ["office" => "hagiography", "commission_id" => "prior-h"],
                ["office" => "studium", "commission_id" => "prior-s"],
            ],
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-persona-candidates",
            $candidateId,
            $candidate,
        );
        $reviewId = "subordinate-persona-review-" . str_repeat("b", 20);
        $review = [
            "schema" => "imperium.foundry-subordinate-persona-review/v1",
            "review_id" => $reviewId,
            "instance_id" => "imperium-test",
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "persona_specification_id" => "specification",
            "persona_specification_digest" => "specification-digest",
            "persona_specification_version" => 2,
            "specification_supersedes" => ["specification_id" => "prior"],
            "specification_revision_basis" => [
                "clarification_return_id" => "clarification",
            ],
            "dispatch_kind" => "SPECIFICATION_REVISION_REISSUE",
            "superseded_commissions" => $candidate["superseded_commissions"],
            "decision" => [
                "adversarial_review_brief" =>
                    "Attack authority boundaries and contradictions.",
            ],
            "status" => "SEALED_PENDING_FOUNDRY_ADVERSARIAL_REVIEW",
            "completeness_review_complete" => true,
            "adversarial_review_authority" => true,
            "persona_approval_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/offices/foundry/subordinate-persona-reviews",
            $reviewId,
            $review,
        );
        try {
            $service = new AdversarialReviewerDemandService($root);
            $d = $service->demand($reviewId);
            self::assertSame($d, $service->demand($reviewId));
            self::assertSame(
                "foundry.reviewer.adversarial",
                $d["required_seat"]["seat"],
            );
            self::assertSame($candidateId, $d["required_seat"]["candidate_id"]);
            self::assertSame(
                "demand",
                $d["required_seat"]["activation_policy"],
            );
            self::assertSame(
                "PENDING_EXACT_ADVERSARIAL_REVIEWER_OCCUPATION",
                $d["status"],
            );
            self::assertSame(
                "SPECIFICATION_REVISION_REISSUE",
                $d["dispatch_kind"],
            );
            self::assertSame(
                $candidate["superseded_commissions"],
                $d["superseded_commissions"],
            );
            self::assertFalse($d["review_authority"]);
            self::assertFalse($d["persona_approval_authority"]);
            self::assertFalse($d["spawning_authority"]);
            self::assertFalse($d["admission_authority"]);
            self::assertFalse($d["execution_authority"]);
        } finally {
            $this->removeTree($root);
        }
    }
    private function write(string $d, string $id, array &$r): void
    {
        if (!is_dir($d)) {
            mkdir($d, 0770, true);
        }
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
