<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class SubordinatePersonaAuthorshipCommissionService
{
    private string $specificationDirectory;
    private string $caseDirectory;
    private string $hagiographyInbox;
    private string $studiumInbox;
    private SubordinatePersonaSpecificationLineageGuard $lineageGuard;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->specificationDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/subordinate-persona-specifications";
        $this->caseDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/subordinate-construction-cases";
        $this->hagiographyInbox =
            $projectDir . "/var/imperium/offices/hagiography/inbox";
        $this->studiumInbox =
            $projectDir . "/var/imperium/offices/studium/inbox";
        $this->lineageGuard = new SubordinatePersonaSpecificationLineageGuard(
            $this->specificationDirectory,
        );
    }
    public function dispatch(string $id): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-specification-[a-f0-9]{20}$/',
                $id,
            )
        ) {
            throw new \InvalidArgumentException(
                "F116_SUBORDINATE_SPECIFICATION_ID_INVALID",
            );
        }
        $s = $this->read(
            $this->specificationDirectory . "/" . $id . ".json",
            "F117_SUBORDINATE_SPECIFICATION_ABSENT",
        );
        if (
            !$this->digestMatches($s) ||
            "imperium.foundry-subordinate-persona-specification/v1" !==
                ($s["schema"] ?? null) ||
            $id !== ($s["specification_id"] ?? null) ||
            "SEALED_PENDING_PERSONA_CONSTRUCTION" !== ($s["status"] ?? null) ||
            true !== ($s["persona_specification_complete"] ?? null) ||
            true !== ($s["construction_authority"] ?? null) ||
            true !== ($s["sealed"] ?? null) ||
            true === ($s["persona_selection_authority"] ?? null) ||
            true === ($s["profile_approval_authority"] ?? null) ||
            true === ($s["spawning_authority"] ?? null) ||
            true === ($s["admission_authority"] ?? null) ||
            true === ($s["seat_binding_authority"] ?? null) ||
            true === ($s["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "F118_SUBORDINATE_SPECIFICATION_INVALID",
            );
        }
        $this->lineageGuard->assertCurrent($s);
        $caseId = $s["case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "F119_SUBORDINATE_SPECIFICATION_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($case) ||
            ($s["case_digest"] ?? null) !== ($case["record_digest"] ?? null) ||
            ($s["source_resolution_id"] ?? null) !==
                ($case["source_resolution_id"] ?? null) ||
            ($s["source_resolution_digest"] ?? null) !==
                ($case["source_resolution_digest"] ?? null) ||
            CanonicalJson::encode($s["inherited_requirements"] ?? null) !==
                CanonicalJson::encode(
                    $case["subordinate_requirements"] ?? null,
                ) ||
            ($s["instance_id"] ?? null) !== ($case["instance_id"] ?? null)
        ) {
            throw new \RuntimeException(
                "F119_SUBORDINATE_SPECIFICATION_CHAIN_INVALID",
            );
        }
        $supersededCommissions = $this->supersededCommissions($s);
        $revisionReissue = null !== ($s["supersedes"] ?? null);
        if ($revisionReissue && 2 !== count($supersededCommissions)) {
            throw new \RuntimeException(
                "F139_SUBORDINATE_REVISION_REISSUE_CHAIN_INCOMPLETE",
            );
        }
        $common = [
            "schema" => "imperium.subordinate-persona-authorship-commission/v1",
            "issuer" => $s["artificer"],
            "instance_id" => $s["instance_id"],
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "persona_specification_id" => $id,
            "persona_specification_digest" => $s["record_digest"],
            "persona_specification_version" => $s["specification_version"] ?? 1,
            "specification_supersedes" => $s["supersedes"] ?? null,
            "specification_revision_basis" => $s["revision_basis"] ?? null,
            "dispatch_kind" => $revisionReissue
                ? "SPECIFICATION_REVISION_REISSUE"
                : "INITIAL_SPECIFICATION_DISPATCH",
            "superseded_commissions" => $supersededCommissions,
            "source_resolution_id" => $s["source_resolution_id"],
            "source_resolution_digest" => $s["source_resolution_digest"],
            "candidate_class" => $s["subordinate_staff_class"],
            "candidate_name" => $s["specification"]["persona_name"],
            "persona_specification" => $s["specification"],
            "inherited_requirements" => $s["inherited_requirements"],
            "queue_position" => $s["queue_position"],
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
        $hid =
            "subordinate-authorship-hagiography-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $id,
                        $s["record_digest"],
                        "hagiography",
                    ]),
                ),
                0,
                20,
            );
        $sid =
            "subordinate-authorship-studium-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $id,
                        $s["record_digest"],
                        "studium",
                    ]),
                ),
                0,
                20,
            );
        $h = $this->persist(
            $this->hagiographyInbox,
            $hid,
            array_merge($common, [
                "commission_id" => $hid,
                "office" => "hagiography",
                "target_seat" => "hagiography.sanctographer",
                "authorship_class" => "EVIDENCE_DERIVED_PERSONA_SECTIONS",
                "required_product" =>
                    "Sanctographer-authenticated evidence-derived Persona section packet for the exact specification",
                "forbidden_authorship" => [
                    "governance doctrine",
                    "complete Persona assembly",
                    "Profile",
                    "admission decision",
                ],
            ]),
        );
        $d = $this->persist(
            $this->studiumInbox,
            $sid,
            array_merge($common, [
                "commission_id" => $sid,
                "office" => "studium",
                "target_seat" => "studium.chancellor",
                "authorship_class" => "PERSONA_GOVERNANCE_DOCTRINE_SECTIONS",
                "required_product" =>
                    "Chancellor-authenticated Persona Governance Doctrine section packet for the exact specification",
                "forbidden_authorship" => [
                    "evidence-derived traits",
                    "complete Persona assembly",
                    "Profile",
                    "admission decision",
                ],
            ]),
        );
        return [
            "specification_id" => $id,
            "dispatch_kind" => $common["dispatch_kind"],
            "superseded_commissions" => $supersededCommissions,
            "artificer" => $s["artificer"],
            "commissions" => [$h, $d],
        ];
    }
    private function supersededCommissions(array $specification): array
    {
        $supersedes = $specification["supersedes"] ?? null;
        if (!is_array($supersedes)) {
            return [];
        }
        $references = [];
        foreach (
            [
                "hagiography" => $this->hagiographyInbox,
                "studium" => $this->studiumInbox,
            ]
            as $office => $directory
        ) {
            $matches = [];
            foreach (
                glob(
                    $directory .
                        "/subordinate-authorship-" .
                        $office .
                        "-*.json",
                ) ?:
                []
                as $path
            ) {
                $commission = $this->read(
                    $path,
                    "F139_SUBORDINATE_REVISION_REISSUE_CHAIN_INCOMPLETE",
                );
                if (
                    !$this->digestMatches($commission) ||
                    "imperium.subordinate-persona-authorship-commission/v1" !==
                        ($commission["schema"] ?? null)
                ) {
                    throw new \RuntimeException(
                        "F139_SUBORDINATE_REVISION_REISSUE_CHAIN_INCOMPLETE",
                    );
                }
                if (
                    ($supersedes["specification_id"] ?? null) ===
                        ($commission["persona_specification_id"] ?? null) &&
                    ($supersedes["specification_digest"] ?? null) ===
                        ($commission["persona_specification_digest"] ?? null)
                ) {
                    $matches[] = $commission;
                }
            }
            if (1 !== count($matches)) {
                continue;
            }
            $references[] = [
                "office" => $office,
                "commission_id" => $matches[0]["commission_id"],
                "commission_digest" => $matches[0]["record_digest"],
                "persona_specification_id" =>
                    $matches[0]["persona_specification_id"],
                "persona_specification_digest" =>
                    $matches[0]["persona_specification_digest"],
                "disposition" => "SUPERSEDED_BY_SPECIFICATION_REVISION",
            ];
        }
        return $references;
    }
    private function read(string $p, string $e): array
    {
        if (!is_file($p)) {
            throw new \RuntimeException($e);
        }
        return json_decode(
            (string) file_get_contents($p),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
    private function digestMatches(array $r): bool
    {
        $d = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($d) &&
            hash_equals($d, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $dir, string $id, array $r): array
    {
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException(
                "Specialized Office inbox cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $dir . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read(
                $p,
                "F120_SUBORDINATE_AUTHORSHIP_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F120_SUBORDINATE_AUTHORSHIP_REPLAY_CONFLICT",
                );
            }
            return $old;
        }
        $tmp = $p . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $tmp,
                    json_encode(
                        $r,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($tmp, $p)
        ) {
            @unlink($tmp);
            throw new \RuntimeException(
                "Subordinate authorship commission cannot be committed atomically.",
            );
        }
        return $r;
    }
}
