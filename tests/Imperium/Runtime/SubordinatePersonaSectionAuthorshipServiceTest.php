<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\SubordinatePersonaSectionAuthorshipGateway;
use App\Imperium\Runtime\Authorship\SubordinatePersonaSectionAuthorshipService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class SubordinatePersonaSectionAuthorshipServiceTest extends TestCase
{
    #[DataProvider("offices")]
    public function testAuthorsOnlyExactBoundedPacket(
        string $office,
        string $class,
        string $forbidden,
    ): void {
        $root =
            sys_get_temp_dir() .
            "/imperium-subordinate-product-" .
            $office .
            "-" .
            bin2hex(random_bytes(6));
        $caseId = "subordinate-construction-case-" . str_repeat("a", 20);
        $case = [
            "schema" => "imperium.foundry-subordinate-construction-case/v1",
            "case_id" => $caseId,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-construction-cases",
            $caseId,
            $case,
        );
        $specId = "subordinate-persona-specification-" . str_repeat("b", 20);
        $spec = [
            "schema" => "imperium.foundry-subordinate-persona-specification/v1",
            "specification_id" => $specId,
            "specification_version" => 1,
            "supersedes" => null,
            "specification_id" => $specId,
            "case_id" => $caseId,
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "digest",
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-persona-specifications",
            $specId,
            $spec,
        );
        $commissionId =
            "subordinate-authorship-" . $office . "-" . str_repeat("c", 20);
        $commission = [
            "schema" => "imperium.subordinate-persona-authorship-commission/v1",
            "commission_id" => $commissionId,
            "authorship_class" => $class,
            "forbidden_authorship" => [$forbidden],
            "dispatch_kind" => "INITIAL_SPECIFICATION_DISPATCH",
            "superseded_commissions" => [],
        ];
        $this->write(
            $root . "/var/imperium/offices/" . $office . "/inbox",
            $commissionId,
            $commission,
        );
        $acceptanceId =
            $office . "-subordinate-acceptance-" . str_repeat("d", 20);
        $acceptance = [
            "schema" =>
                "imperium.subordinate-authorship-commission-acceptance/v1",
            "acceptance_id" => $acceptanceId,
            "instance_id" => "imperium-test",
            "originating_guildhall_commission_id" => "guildhall-subordinate-construction-commission-cccccccccccccccccccc",
            "originating_guildhall_commission_digest" => "guildhall-commission-digest",
            "office" => $office,
            "commission_id" => $commissionId,
            "commission_digest" => $commission["record_digest"],
            "persona_specification_id" => $specId,
            "persona_specification_digest" => $spec["record_digest"],
            "persona_specification_version" => 1,
            "specification_supersedes" => null,
            "specification_revision_basis" => null,
            "dispatch_kind" => "INITIAL_SPECIFICATION_DISPATCH",
            "superseded_commissions" => [],
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "digest",
            "actor" => ["seat" => $office . ".resident"],
            "authorship_class" => $class,
            "disposition" => "ACCEPTED_FOR_EXACT_SUBORDINATE_AUTHORSHIP",
            "recipient_acceptance" => true,
            "authorship_authority_exercisable" => true,
            "persona_assembly_authority" => false,
            "persona_approval_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/" .
                $office .
                "/subordinate-acceptances",
            $acceptanceId,
            $acceptance,
        );
        $gateway = new class implements
            SubordinatePersonaSectionAuthorshipGateway
        {
            public function author(
                string $office,
                array $acceptance,
                array $commission,
                array $specification,
                array $case,
            ): array {
                return [
                    "disposition" => "SECTION_PACKET_COMPLETE",
                    "authored_sections" => [
                        "bounded_section" => ["Exact authored content"],
                    ],
                    "source_citations" => ["resolution:digest"],
                    "unresolved_questions" => [],
                ];
            }
        };
        try {
            $service = new SubordinatePersonaSectionAuthorshipService(
                $root,
                $gateway,
            );
            $r = $service->author($office, $acceptanceId);
            self::assertSame($r, $service->author($office, $acceptanceId));
            self::assertSame("SEALED_PENDING_FOUNDRY_ASSEMBLY", $r["status"]);
            self::assertTrue($r["authorship_complete"]);
            self::assertTrue($r["sealed"]);
            self::assertSame($case["originating_guildhall_commission_id"], $r["originating_guildhall_commission_id"]);
            self::assertSame($case["originating_guildhall_commission_digest"], $r["originating_guildhall_commission_digest"]);
            self::assertSame(
                "INITIAL_SPECIFICATION_DISPATCH",
                $r["dispatch_kind"],
            );
            self::assertSame([], $r["superseded_commissions"]);
            self::assertFalse($r["persona_assembly_authority"]);
            self::assertFalse($r["persona_approval_authority"]);
            self::assertFalse($r["profile_approval_authority"]);
            self::assertFalse($r["spawning_authority"]);
            self::assertFalse($r["admission_authority"]);
            self::assertFalse($r["execution_authority"]);
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
            $this->write(
                $root .
                    "/var/imperium/offices/foundry/subordinate-persona-specifications",
                $successor["specification_id"],
                $successor,
            );
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage(
                "F138_SUBORDINATE_SPECIFICATION_SUPERSEDED",
            );
            $service->author($office, $acceptanceId);
        } finally {
            $this->removeTree($root);
        }
    }
    public static function offices(): array
    {
        return [
            [
                "hagiography",
                "EVIDENCE_DERIVED_PERSONA_SECTIONS",
                "governance doctrine",
            ],
            [
                "studium",
                "PERSONA_GOVERNANCE_DOCTRINE_SECTIONS",
                "evidence-derived traits",
            ],
        ];
    }
    private function write(string $dir, string $id, array &$record): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        file_put_contents(
            $dir . "/" . $id . ".json",
            json_encode($record, JSON_THROW_ON_ERROR),
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
