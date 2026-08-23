<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerProvisioningCaseService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerProvisioningCaseServiceTest extends TestCase
{
    public function testOpensBlockedExactReviewerCaseWithoutAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-provisioning-" .
            bin2hex(random_bytes(6));
        $id = "foundry-adversarial-reviewer-demand-" . str_repeat("a", 20);
        $d = [
            "schema" => "imperium.foundry-adversarial-reviewer-demand/v1",
            "demand_id" => $id,
            "recipient" => "mastermason",
            "instance_id" => "imperium-test",
            "foundry_review_id" => "review",
            "foundry_review_digest" => "review-digest",
            "candidate_id" => "candidate",
            "candidate_digest" => "candidate-digest",
            "review_scope" => "Attack exact candidate.",
            "independence_requirements" => ["did not author candidate"],
            "required_seat" => [
                "seat" => "foundry.reviewer.adversarial",
                "profile" => "offices/foundry/profile-reviewer-adversarial.md",
            ],
            "status" => "PENDING_EXACT_ADVERSARIAL_REVIEWER_OCCUPATION",
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/mastermason/spawning-requests",
            $id,
            $d,
        );
        try {
            $s = new AdversarialReviewerProvisioningCaseService($root);
            $c = $s->open($id);
            self::assertSame($c, $s->open($id));
            self::assertSame(
                "BLOCKED_PENDING_ADVERSARIAL_REVIEWER_PERSONA",
                $c["status"],
            );
            self::assertTrue($c["persona_construction_required"]);
            self::assertFalse($c["mission_persona_selection_required"]);
            self::assertFalse($c["construction_authority"]);
            self::assertFalse($c["commission_authority"]);
            self::assertFalse($c["review_authority"]);
            self::assertFalse($c["spawning_authority"]);
            self::assertFalse($c["seat_binding_authority"]);
            self::assertFalse($c["execution_authority"]);
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
