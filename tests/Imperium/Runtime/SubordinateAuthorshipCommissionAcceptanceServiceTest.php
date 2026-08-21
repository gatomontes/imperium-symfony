<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\SubordinateAuthorshipCommissionAcceptanceService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubordinateAuthorshipCommissionAcceptanceServiceTest extends
    TestCase
{
    #[DataProvider("offices")]
    public function testResidentAcceptsOnlyExactAddressedCommission(
        string $office,
        string $role,
        string $seat,
    ): void {
        $root =
            sys_get_temp_dir() .
            "/imperium-subordinate-accept-" .
            $office .
            "-" .
            bin2hex(random_bytes(6));
        $caseId = "subordinate-construction-case-" . str_repeat("a", 20);
        $case = [
            "schema" => "imperium.foundry-subordinate-construction-case/v1",
            "case_id" => $caseId,
            "originating_guildhall_commission_id" => "guildhall-subordinate-construction-commission-cccccccccccccccccccc",
            "originating_guildhall_commission_digest" => "guildhall-commission-digest",
            "status" => "OPEN_PENDING_PERSONA_SPECIFICATION",
            "construction_authority" => true,
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
        $specId = "subordinate-persona-specification-" . str_repeat("b", 20);
        $spec = [
            "schema" => "imperium.foundry-subordinate-persona-specification/v1",
            "specification_id" => $specId,
            "specification_version" => 1,
            "supersedes" => null,
            "case_id" => $caseId,
            "case_digest" => $case["record_digest"],
            "originating_guildhall_commission_id" => $case["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $case["originating_guildhall_commission_digest"],
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "resolution-digest",
            "specification" => ["persona_name" => "Candidate"],
            "inherited_requirements" => [
                "required_specializations" => ["work"],
            ],
            "status" => "SEALED_PENDING_PERSONA_CONSTRUCTION",
            "sealed" => true,
        ];
        $spec["record_digest"] = hash("sha256", CanonicalJson::encode($spec));
        $dir =
            $root .
            "/var/imperium/offices/foundry/subordinate-persona-specifications";
        mkdir($dir, 0770, true);
        file_put_contents(
            $dir . "/" . $specId . ".json",
            json_encode($spec, JSON_THROW_ON_ERROR),
        );
        $commissionId =
            "subordinate-authorship-" . $office . "-" . str_repeat("c", 20);
        $c = [
            "schema" => "imperium.subordinate-persona-authorship-commission/v1",
            "commission_id" => $commissionId,
            "instance_id" => "imperium-test",
            "originating_guildhall_commission_id" => "guildhall-subordinate-construction-commission-cccccccccccccccccccc",
            "originating_guildhall_commission_digest" => "guildhall-commission-digest",
            "office" => $office,
            "target_seat" => $seat,
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "persona_specification_id" => $specId,
            "persona_specification_digest" => $spec["record_digest"],
            "persona_specification_version" => 1,
            "specification_supersedes" => null,
            "specification_revision_basis" => null,
            "dispatch_kind" => "INITIAL_SPECIFICATION_DISPATCH",
            "superseded_commissions" => [],
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "resolution-digest",
            "persona_specification" => $spec["specification"],
            "inherited_requirements" => $spec["inherited_requirements"],
            "authorship_class" => "BOUNDED",
            "required_product" => "Exact section",
            "forbidden_authorship" => ["complete Persona"],
            "status" => "ISSUED_PENDING_RECIPIENT",
            "authorship_authority" => true,
            "recipient_acceptance" => null,
            "persona_assembly_authority" => false,
            "persona_approval_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
        ];
        $c["record_digest"] = hash("sha256", CanonicalJson::encode($c));
        $dir = $root . "/var/imperium/offices/" . $office . "/inbox";
        mkdir($dir, 0770, true);
        file_put_contents(
            $dir . "/" . $commissionId . ".json",
            json_encode($c, JSON_THROW_ON_ERROR),
        );
        $bindingId = $office . "-" . $role . "-binding-" . str_repeat("d", 20);
        $b = [
            "schema" => "imperium.authorship-resident-occupancy/v1",
            "binding_id" => $bindingId,
            "instance_id" => "imperium-test",
            "originating_guildhall_commission_id" => "guildhall-subordinate-construction-commission-cccccccccccccccccccc",
            "originating_guildhall_commission_digest" => "guildhall-commission-digest",
            "office" => $office,
            "seat" => $seat,
            "manifestation_id" => "manifestation-" . $role,
            "occupancy_generation" => 1,
            "status" => "ACTIVE",
            "binding_atomic" => true,
            "authorship_authority" => true,
            "execution_authority" => false,
        ];
        $b["record_digest"] = hash("sha256", CanonicalJson::encode($b));
        $dir = $root . "/var/imperium/offices/" . $office . "/occupancy";
        mkdir($dir, 0770, true);
        file_put_contents(
            $dir . "/" . $bindingId . ".json",
            json_encode($b, JSON_THROW_ON_ERROR),
        );
        try {
            $service = new SubordinateAuthorshipCommissionAcceptanceService(
                $root,
            );
            $a = $service->accept($office, $commissionId, $bindingId);
            self::assertSame(
                $a,
                $service->accept($office, $commissionId, $bindingId),
            );
            self::assertSame(
                "ACCEPTED_FOR_EXACT_SUBORDINATE_AUTHORSHIP",
                $a["disposition"],
            );
            self::assertTrue($a["recipient_acceptance"]);
            self::assertTrue($a["authorship_authority_exercisable"]);
            self::assertSame($case["originating_guildhall_commission_id"], $a["originating_guildhall_commission_id"]);
            self::assertSame($case["originating_guildhall_commission_digest"], $a["originating_guildhall_commission_digest"]);
            self::assertSame(
                "INITIAL_SPECIFICATION_DISPATCH",
                $a["dispatch_kind"],
            );
            self::assertSame([], $a["superseded_commissions"]);
            self::assertFalse($a["persona_assembly_authority"]);
            self::assertFalse($a["persona_approval_authority"]);
            self::assertFalse($a["profile_approval_authority"]);
            self::assertFalse($a["spawning_authority"]);
            self::assertFalse($a["execution_authority"]);
            $successor = [
                "specification_id" =>
                    "subordinate-persona-specification-" . str_repeat("e", 20),
                "specification_version" => 2,
                "supersedes" => [
                    "specification_id" => $specId,
                    "specification_digest" => $spec["record_digest"],
                    "specification_version" => 1,
                ],
            ];
            $successor["record_digest"] = hash(
                "sha256",
                CanonicalJson::encode($successor),
            );
            file_put_contents(
                $root .
                    "/var/imperium/offices/foundry/subordinate-persona-specifications/" .
                    $successor["specification_id"] .
                    ".json",
                json_encode($successor, JSON_THROW_ON_ERROR),
            );
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage(
                "F138_SUBORDINATE_SPECIFICATION_SUPERSEDED",
            );
            $service->accept($office, $commissionId, $bindingId);
        } finally {
            $this->removeTree($root);
        }
    }
    public static function offices(): array
    {
        return [
            ["hagiography", "sanctographer", "hagiography.sanctographer"],
            ["studium", "chancellor", "studium.chancellor"],
        ];
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
