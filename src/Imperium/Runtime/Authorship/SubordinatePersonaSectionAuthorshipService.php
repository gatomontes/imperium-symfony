<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Authorship;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationLineageGuard;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class SubordinatePersonaSectionAuthorshipService
{
    private string $officeRoot;
    private string $specificationDirectory;
    private string $caseDirectory;
    private SubordinatePersonaSpecificationLineageGuard $lineageGuard;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private SubordinatePersonaSectionAuthorshipGateway $cognition,
    ) {
        $this->officeRoot = $projectDir . "/var/imperium/offices";
        $this->specificationDirectory =
            $this->officeRoot . "/foundry/subordinate-persona-specifications";
        $this->caseDirectory =
            $this->officeRoot . "/foundry/subordinate-construction-cases";
        $this->lineageGuard = new SubordinatePersonaSpecificationLineageGuard(
            $this->specificationDirectory,
        );
    }
    public function author(string $office, string $acceptanceId): array
    {
        [$class, $forbidden] = match ($office) {
            "hagiography" => [
                "EVIDENCE_DERIVED_PERSONA_SECTIONS",
                "governance doctrine",
            ],
            "studium" => [
                "PERSONA_GOVERNANCE_DOCTRINE_SECTIONS",
                "evidence-derived traits",
            ],
            default => throw new \InvalidArgumentException(
                "A105_AUTHORSHIP_OFFICE_INVALID",
            ),
        };
        if (
            !preg_match(
                "/^" . $office . '-subordinate-acceptance-[a-f0-9]{20}$/',
                $acceptanceId,
            )
        ) {
            throw new \InvalidArgumentException(
                "A106_SUBORDINATE_ACCEPTANCE_ID_INVALID",
            );
        }
        $a = $this->read(
            $this->officeRoot .
                "/" .
                $office .
                "/subordinate-acceptances/" .
                $acceptanceId .
                ".json",
            "A107_SUBORDINATE_ACCEPTANCE_ABSENT",
        );
        if (
            !$this->digestMatches($a) ||
            "imperium.subordinate-authorship-commission-acceptance/v1" !==
                ($a["schema"] ?? null) ||
            $acceptanceId !== ($a["acceptance_id"] ?? null) ||
            $office !== ($a["office"] ?? null) ||
            "ACCEPTED_FOR_EXACT_SUBORDINATE_AUTHORSHIP" !==
                ($a["disposition"] ?? null) ||
            true !== ($a["recipient_acceptance"] ?? null) ||
            true !== ($a["authorship_authority_exercisable"] ?? null) ||
            $class !== ($a["authorship_class"] ?? null) ||
            true === ($a["persona_assembly_authority"] ?? null) ||
            true === ($a["persona_approval_authority"] ?? null) ||
            true === ($a["profile_approval_authority"] ?? null) ||
            true === ($a["spawning_authority"] ?? null) ||
            true === ($a["admission_authority"] ?? null) ||
            true === ($a["seat_binding_authority"] ?? null) ||
            true === ($a["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("A108_SUBORDINATE_ACCEPTANCE_INVALID");
        }
        $commissionId = $a["commission_id"] ?? null;
        $c = is_string($commissionId)
            ? $this->read(
                $this->officeRoot .
                    "/" .
                    $office .
                    "/inbox/" .
                    $commissionId .
                    ".json",
                "A109_SUBORDINATE_AUTHORSHIP_CHAIN_INVALID",
            )
            : [];
        $specId = $a["persona_specification_id"] ?? null;
        $s = is_string($specId)
            ? $this->read(
                $this->specificationDirectory . "/" . $specId . ".json",
                "A109_SUBORDINATE_AUTHORSHIP_CHAIN_INVALID",
            )
            : [];
        $caseId = $a["subordinate_construction_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "A109_SUBORDINATE_AUTHORSHIP_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($c) ||
            !$this->digestMatches($s) ||
            !$this->digestMatches($case) ||
            ($a["commission_digest"] ?? null) !==
                ($c["record_digest"] ?? null) ||
            ($a["persona_specification_digest"] ?? null) !==
                ($s["record_digest"] ?? null) ||
            ($a["persona_specification_version"] ?? 1) !==
                ($s["specification_version"] ?? 1) ||
            CanonicalJson::encode($a["specification_supersedes"] ?? null) !==
                CanonicalJson::encode($s["supersedes"] ?? null) ||
            CanonicalJson::encode($a["specification_revision_basis"] ?? null) !==
                CanonicalJson::encode($s["revision_basis"] ?? null) ||
            ($a["subordinate_construction_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($a["source_resolution_id"] ?? null) !==
                ($s["source_resolution_id"] ?? null) ||
            ($a["source_resolution_digest"] ?? null) !==
                ($s["source_resolution_digest"] ?? null) ||
            $class !== ($c["authorship_class"] ?? null) ||
            !in_array($forbidden, $c["forbidden_authorship"] ?? [], true)
        ) {
            throw new \RuntimeException(
                "A109_SUBORDINATE_AUTHORSHIP_CHAIN_INVALID",
            );
        }
        $this->lineageGuard->assertCurrent($s);
        foreach (
            glob(
                $this->officeRoot .
                    "/" .
                    $office .
                    "/subordinate-products/" .
                    $office .
                    "-subordinate-product-*.json",
            ) ?:
            []
            as $p
        ) {
            $old = $this->read($p, "A112_SUBORDINATE_PRODUCT_REPLAY_CONFLICT");
            if (
                $acceptanceId === ($old["acceptance_id"] ?? null) &&
                $this->digestMatches($old)
            ) {
                return $old;
            }
        }
        $decision = $this->cognition->author($office, $a, $c, $s, $case);
        $this->validateDecision($decision);
        $complete =
            "SECTION_PACKET_COMPLETE" === ($decision["disposition"] ?? null);
        $id =
            $office .
            "-subordinate-product-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $acceptanceId,
                        $a["record_digest"],
                        $decision,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($office, $id, [
            "schema" => "imperium.subordinate-persona-section-packet/v1",
            "product_id" => $id,
            "instance_id" => $a["instance_id"],
            "office" => $office,
            "authorship_class" => $class,
            "acceptance_id" => $acceptanceId,
            "acceptance_digest" => $a["record_digest"],
            "commission_id" => $commissionId,
            "commission_digest" => $c["record_digest"],
            "persona_specification_id" => $specId,
            "persona_specification_digest" => $s["record_digest"],
            "persona_specification_version" =>
                $s["specification_version"] ?? 1,
            "specification_supersedes" => $s["supersedes"] ?? null,
            "specification_revision_basis" => $s["revision_basis"] ?? null,
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "source_resolution_id" => $a["source_resolution_id"],
            "source_resolution_digest" => $a["source_resolution_digest"],
            "author" => $a["actor"],
            "authored_sections" => $decision["authored_sections"],
            "source_citations" => $decision["source_citations"],
            "unresolved_questions" => $decision["unresolved_questions"],
            "status" => $complete
                ? "SEALED_PENDING_FOUNDRY_ASSEMBLY"
                : "CLARIFICATION_REQUIRED",
            "sealed" => $complete,
            "authorship_complete" => $complete,
            "persona_assembly_authority" => false,
            "persona_approval_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
        ]);
    }
    private function validateDecision(array $r): void
    {
        $keys = array_keys($r);
        sort($keys, SORT_STRING);
        if (
            [
                "authored_sections",
                "disposition",
                "source_citations",
                "unresolved_questions",
            ] !== $keys ||
            !in_array(
                $r["disposition"] ?? null,
                ["SECTION_PACKET_COMPLETE", "CLARIFICATION_REQUIRED"],
                true,
            ) ||
            !is_array($r["authored_sections"] ?? null) ||
            [] === $r["authored_sections"]
        ) {
            throw new \RuntimeException(
                "A110_SUBORDINATE_AUTHORSHIP_CONTRACT_INVALID",
            );
        }
        foreach ($r["authored_sections"] as $v) {
            if (is_string($v) && "" !== trim($v)) {
                continue;
            }
            if (!is_array($v) || [] === $v) {
                throw new \RuntimeException(
                    "A110_SUBORDINATE_AUTHORSHIP_CONTRACT_INVALID",
                );
            }
            $this->stringArray($v);
        }
        foreach (["source_citations", "unresolved_questions"] as $f) {
            $this->stringArray($r[$f] ?? null);
        }
    }
    private function stringArray(mixed $v): void
    {
        if (!is_array($v)) {
            throw new \RuntimeException(
                "A110_SUBORDINATE_AUTHORSHIP_CONTRACT_INVALID",
            );
        }
        foreach ($v as $i) {
            if (!is_string($i) || "" === trim($i)) {
                throw new \RuntimeException(
                    "A110_SUBORDINATE_AUTHORSHIP_CONTRACT_INVALID",
                );
            }
        }
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
    private function persist(string $office, string $id, array $r): array
    {
        $dir = $this->officeRoot . "/" . $office . "/subordinate-products";
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException(
                "Subordinate authorship product directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $dir . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read($p, "A112_SUBORDINATE_PRODUCT_REPLAY_CONFLICT");
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "A112_SUBORDINATE_PRODUCT_REPLAY_CONFLICT",
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
                "Subordinate authorship product cannot be committed atomically.",
            );
        }
        return $r;
    }
}
