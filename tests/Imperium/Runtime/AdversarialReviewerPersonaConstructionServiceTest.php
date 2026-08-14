<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerPersonaConstructionService;
use PHPUnit\Framework\TestCase;
final class AdversarialReviewerPersonaConstructionServiceTest extends TestCase
{
    public function testConstructsSealedVersionedCandidateWithoutDownstreamAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-persona-" .
            bin2hex(random_bytes(6));
        $acceptanceId =
            "foundry-adversarial-reviewer-construction-acceptance-" .
            str_repeat("a", 20);
        $acceptance = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-construction-authorization-acceptance/v1",
            "acceptance_id" => $acceptanceId,
            "instance_id" => "imperium-test",
            "authorization_act_id" => "authorization",
            "authorization_act_digest" => "authorization-digest",
            "source_case_id" => "case",
            "source_case_digest" => "case-digest",
            "candidate_id" => "mission-candidate",
            "candidate_digest" => "mission-candidate-digest",
            "target_seat" => ["seat" => "foundry.reviewer.adversarial"],
            "profile_source" =>
                "offices/foundry/profile-reviewer-adversarial.md",
            "actor" => [
                "seat" => "foundry.artificer",
                "manifestation_id" => "artificer",
                "occupancy_generation" => 1,
            ],
            "disposition" =>
                "ACCEPTED_FOR_EXACT_ADVERSARIAL_REVIEWER_PERSONA_CONSTRUCTION",
            "recipient_acceptance" => true,
            "construction_authority" => true,
            "construction_authority_exercisable" => true,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
        ];
        $this->writeRecord(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-construction-acceptances",
            $acceptanceId,
            $acceptance,
        );
        $this->writeSource(
            $root . "/offices/foundry/profile-reviewer-adversarial.md",
            "reviewer profile",
        );
        $this->writeSource(
            $root . "/offices/foundry/seat-demand-reviewer-adversarial.md",
            "reviewer seat",
        );
        try {
            $service = new AdversarialReviewerPersonaConstructionService($root);
            $candidate = $service->construct($acceptanceId);
            self::assertSame($candidate, $service->construct($acceptanceId));
            self::assertSame(
                "foundry.adversarial-reviewer",
                $candidate["persona_id"],
            );
            self::assertSame("1.0.0", $candidate["persona_version"]);
            self::assertSame(
                "SEALED_PENDING_FOUNDRY_REVIEW",
                $candidate["status"],
            );
            self::assertTrue($candidate["construction_complete"]);
            self::assertTrue($candidate["sealed"]);
            self::assertFalse($candidate["production_approval"]);
            self::assertSame(
                "mission-candidate",
                $candidate["authorized_review_target"]["candidate_id"],
            );
            self::assertSame(
                $acceptance["record_digest"],
                $candidate["construction_acceptance_digest"],
            );
            self::assertSame(
                "Blackquill",
                $candidate["sources"]["design_basis"]["name"],
            );
            self::assertSame(
                "persona-design-basis",
                $candidate["sources"]["design_basis"]["kind"],
            );
            self::assertFalse(
                $candidate["sources"]["design_basis"]["identity_imported"],
            );
            self::assertFalse(
                $candidate["sources"]["design_basis"]["institution_imported"],
            );
            self::assertFalse(
                $candidate["sources"]["design_basis"]["authority_imported"],
            );
            foreach (
                [
                    "review_authority",
                    "profile_approval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "admission_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($candidate[$key]);
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
    private function writeSource(string $path, string $content): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        file_put_contents($path, $content);
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
