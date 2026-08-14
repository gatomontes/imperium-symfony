<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\SubordinatePersonaAuthorshipCommissionService;
use PHPUnit\Framework\TestCase;
final class SubordinatePersonaAuthorshipCommissionServiceTest extends TestCase
{
    public function testDispatchesTwoBoundedCommissionsPerSpecification(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-subordinate-authorship-" .
            bin2hex(random_bytes(6));
        $caseId = "subordinate-construction-case-" . str_repeat("a", 20);
        $requirements = ["required_specializations" => ["research"]];
        $case = [
            "case_id" => $caseId,
            "instance_id" => "imperium-test",
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "resolution-digest",
            "subordinate_requirements" => $requirements,
        ];
        $case["record_digest"] = hash("sha256", CanonicalJson::encode($case));
        $dir =
            $root .
            "/var/imperium/offices/foundry/subordinate-construction-cases";
        mkdir($dir, 0770, true);
        file_put_contents(
            $dir . "/" . $caseId . ".json",
            json_encode($case, JSON_THROW_ON_ERROR),
        );
        $priorId = "subordinate-persona-specification-" . str_repeat("a", 20);
        $id = "subordinate-persona-specification-" . str_repeat("b", 20);
        $spec = [
            "schema" => "imperium.foundry-subordinate-persona-specification/v1",
            "specification_id" => $id,
            "specification_version" => 2,
            "supersedes" => [
                "specification_id" => $priorId,
                "specification_digest" => "prior-specification-digest",
                "specification_version" => 1,
            ],
            "revision_basis" => [
                "clarification_return_id" =>
                    "subordinate-clarification-return-" . str_repeat("c", 20),
                "original_clarification" => [
                    "unresolved_questions" => [
                        "Garrison has no facts for an unfinished Persona.",
                    ],
                ],
            ],
            "instance_id" => "imperium-test",
            "case_id" => $caseId,
            "case_digest" => $case["record_digest"],
            "queue_position" => 1,
            "subordinate_staff_class" => "Chronicler",
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "resolution-digest",
            "artificer" => ["seat" => "foundry.artificer"],
            "inherited_requirements" => $requirements,
            "specification" => ["persona_name" => "Evidence Chronicler"],
            "status" => "SEALED_PENDING_PERSONA_CONSTRUCTION",
            "persona_specification_complete" => true,
            "construction_authority" => true,
            "persona_selection_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $spec["record_digest"] = hash("sha256", CanonicalJson::encode($spec));
        $dir =
            $root .
            "/var/imperium/offices/foundry/subordinate-persona-specifications";
        mkdir($dir, 0770, true);
        $prior = $spec;
        $prior["specification_id"] = $priorId;
        $prior["specification_version"] = 1;
        $prior["supersedes"] = null;
        $prior["revision_basis"] = null;
        unset($prior["record_digest"]);
        $prior["record_digest"] = hash("sha256", CanonicalJson::encode($prior));
        $spec["supersedes"]["specification_digest"] = $prior["record_digest"];
        unset($spec["record_digest"]);
        $spec["record_digest"] = hash("sha256", CanonicalJson::encode($spec));
        file_put_contents(
            $dir . "/" . $priorId . ".json",
            json_encode($prior, JSON_THROW_ON_ERROR),
        );
        $service = new SubordinatePersonaAuthorshipCommissionService($root);
        $initial = $service->dispatch($priorId);
        self::assertSame(
            "INITIAL_SPECIFICATION_DISPATCH",
            $initial["dispatch_kind"],
        );
        file_put_contents(
            $dir . "/" . $id . ".json",
            json_encode($spec, JSON_THROW_ON_ERROR),
        );
        try {
            $r = $service->dispatch($id);
            self::assertSame($r, $service->dispatch($id));
            self::assertSame(
                "SPECIFICATION_REVISION_REISSUE",
                $r["dispatch_kind"],
            );
            self::assertCount(2, $r["superseded_commissions"]);
            self::assertCount(2, $r["commissions"]);
            self::assertSame(
                ["hagiography", "studium"],
                array_column($r["commissions"], "office"),
            );
            self::assertSame(
                [
                    "EVIDENCE_DERIVED_PERSONA_SECTIONS",
                    "PERSONA_GOVERNANCE_DOCTRINE_SECTIONS",
                ],
                array_column($r["commissions"], "authorship_class"),
            );
            foreach ($r["commissions"] as $c) {
                self::assertSame("ISSUED_PENDING_RECIPIENT", $c["status"]);
                self::assertSame(
                    "SPECIFICATION_REVISION_REISSUE",
                    $c["dispatch_kind"],
                );
                self::assertSame(
                    $r["superseded_commissions"],
                    $c["superseded_commissions"],
                );
                self::assertTrue($c["authorship_authority"]);
                self::assertSame(2, $c["persona_specification_version"]);
                self::assertSame(
                    $spec["supersedes"],
                    $c["specification_supersedes"],
                );
                self::assertSame(
                    $spec["revision_basis"],
                    $c["specification_revision_basis"],
                );
                self::assertFalse($c["persona_assembly_authority"]);
                self::assertFalse($c["persona_approval_authority"]);
                self::assertFalse($c["profile_approval_authority"]);
                self::assertFalse($c["spawning_authority"]);
                self::assertFalse($c["execution_authority"]);
            }
        } finally {
            $this->removeTree($root);
        }
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
