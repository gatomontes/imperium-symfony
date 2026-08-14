<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\BlackquillPersonaCandidateService;
use PHPUnit\Framework\TestCase;
final class BlackquillPersonaCandidateServiceTest extends TestCase
{
    public function testSealsFingerprintBoundImmutableCandidate(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-blackquill-candidate-" .
            bin2hex(random_bytes(6));
        $content = "# Blackquill Persona";
        $path =
            $root .
            "/offices/foundry/personas/blackquill-adversarial-reviewer.md";
        mkdir(dirname($path), 0770, true);
        file_put_contents($path, $content);
        $source = [
            "path" =>
                "offices/foundry/personas/blackquill-adversarial-reviewer.md",
            "content_digest" => "sha256:" . hash("sha256", $content),
            "derivation_basis" =>
                "user-designated Blackquill critical-analysis contract",
            "authority_imported" => false,
        ];
        $id = "foundry-blackquill-production-acceptance-" . str_repeat("a", 20);
        $a = [
            "schema" =>
                "imperium.foundry-blackquill-production-authorization-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => "imperium-test",
            "authorization_act_id" => "act",
            "authorization_act_digest" => "act-digest",
            "source_case_id" => "case",
            "source_case_digest" => "case-digest",
            "persona" => [
                "persona_id" =>
                    "foundry.external.blackquill-adversarial-reviewer",
                "persona_version" => "1.0.0",
                "source" => $source,
            ],
            "actor" => [
                "seat" => "foundry.artificer",
                "manifestation_id" => "artificer",
            ],
            "disposition" =>
                "ACCEPTED_FOR_EXACT_BLACKQUILL_PRODUCTION_PROCESSING",
            "recipient_acceptance" => true,
            "production_authority" => true,
            "production_authority_exercisable" => true,
            "review_findings_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/blackquill-production-acceptances",
            $id,
            $a,
        );
        try {
            $s = new BlackquillPersonaCandidateService($root);
            $r = $s->seal($id);
            self::assertSame($r, $s->seal($id));
            self::assertSame(
                "foundry.external.blackquill-adversarial-reviewer",
                $r["persona_id"],
            );
            self::assertSame("1.0.0", $r["persona_version"]);
            self::assertSame("SEALED_PENDING_FOUNDRY_REVIEW", $r["status"]);
            self::assertSame($content, $r["persona_content"]);
            self::assertSame($source, $r["source"]);
            self::assertTrue($r["production_processing_complete"]);
            self::assertTrue($r["sealed"]);
            self::assertFalse($r["production_approval"]);
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
