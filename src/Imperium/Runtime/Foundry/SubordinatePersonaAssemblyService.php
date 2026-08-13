<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class SubordinatePersonaAssemblyService
{
    private string $officeRoot;
    private string $specificationDirectory;
    private string $caseDirectory;
    private string $candidateDirectory;
    private SubordinatePersonaSpecificationLineageGuard $lineageGuard;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->officeRoot = $projectDir . "/var/imperium/offices";
        $this->specificationDirectory =
            $this->officeRoot . "/foundry/subordinate-persona-specifications";
        $this->caseDirectory =
            $this->officeRoot . "/foundry/subordinate-construction-cases";
        $this->candidateDirectory =
            $this->officeRoot . "/foundry/subordinate-persona-candidates";
        $this->lineageGuard = new SubordinatePersonaSpecificationLineageGuard(
            $this->specificationDirectory,
        );
    }
    public function assemble(string $specificationId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-specification-[a-f0-9]{20}$/',
                $specificationId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F121_SUBORDINATE_SPECIFICATION_ID_INVALID",
            );
        }
        $s = $this->read(
            $this->specificationDirectory . "/" . $specificationId . ".json",
            "F122_SUBORDINATE_SPECIFICATION_ABSENT",
        );
        $caseId = $s["case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "F123_SUBORDINATE_ASSEMBLY_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($s) ||
            !$this->digestMatches($case) ||
            "imperium.foundry-subordinate-persona-specification/v1" !==
                ($s["schema"] ?? null) ||
            $specificationId !== ($s["specification_id"] ?? null) ||
            "SEALED_PENDING_PERSONA_CONSTRUCTION" !== ($s["status"] ?? null) ||
            true !== ($s["persona_specification_complete"] ?? null) ||
            true !== ($s["sealed"] ?? null) ||
            ($s["case_digest"] ?? null) !== ($case["record_digest"] ?? null)
        ) {
            throw new \RuntimeException(
                "F123_SUBORDINATE_ASSEMBLY_CHAIN_INVALID",
            );
        }
        $this->lineageGuard->assertCurrent($s);
        $products = [];
        foreach (
            [
                "hagiography" => "EVIDENCE_DERIVED_PERSONA_SECTIONS",
                "studium" => "PERSONA_GOVERNANCE_DOCTRINE_SECTIONS",
            ]
            as $office => $class
        ) {
            $matches = [];
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
                $r = $this->read($p, "F124_SUBORDINATE_PRODUCT_INVALID");
                if (
                    $specificationId ===
                    ($r["persona_specification_id"] ?? null)
                ) {
                    $matches[] = $r;
                }
            }
            if (1 !== count($matches)) {
                throw new \RuntimeException(
                    "F125_SUBORDINATE_PRODUCT_SET_INCOMPLETE",
                );
            }
            $r = $matches[0];
            if (
                !$this->digestMatches($r) ||
                "imperium.subordinate-persona-section-packet/v1" !==
                    ($r["schema"] ?? null) ||
                $office !== ($r["office"] ?? null) ||
                $class !== ($r["authorship_class"] ?? null) ||
                "SEALED_PENDING_FOUNDRY_ASSEMBLY" !== ($r["status"] ?? null) ||
                true !== ($r["sealed"] ?? null) ||
                true !== ($r["authorship_complete"] ?? null) ||
                ($r["persona_specification_digest"] ?? null) !==
                    ($s["record_digest"] ?? null) ||
                ($r["subordinate_construction_case_id"] ?? null) !== $caseId ||
                ($r["subordinate_construction_case_digest"] ?? null) !==
                    ($case["record_digest"] ?? null) ||
                ($r["source_resolution_id"] ?? null) !==
                    ($s["source_resolution_id"] ?? null) ||
                ($r["source_resolution_digest"] ?? null) !==
                    ($s["source_resolution_digest"] ?? null) ||
                !is_array($r["authored_sections"] ?? null) ||
                [] === $r["authored_sections"] ||
                true === ($r["persona_assembly_authority"] ?? null) ||
                true === ($r["persona_approval_authority"] ?? null) ||
                true === ($r["profile_approval_authority"] ?? null) ||
                true === ($r["spawning_authority"] ?? null) ||
                true === ($r["admission_authority"] ?? null) ||
                true === ($r["seat_binding_authority"] ?? null) ||
                true === ($r["execution_authority"] ?? null)
            ) {
                throw new \RuntimeException("F124_SUBORDINATE_PRODUCT_INVALID");
            }
            $products[$office] = $r;
        }
        foreach (
            glob(
                $this->candidateDirectory .
                    "/subordinate-persona-candidate-*.json",
            ) ?:
            []
            as $p
        ) {
            $old = $this->read($p, "F127_SUBORDINATE_ASSEMBLY_REPLAY_CONFLICT");
            if (
                $specificationId ===
                    ($old["persona_specification_id"] ?? null) &&
                $this->digestMatches($old)
            ) {
                return $old;
            }
        }
        $refs = [];
        foreach ($products as $office => $r) {
            $refs[$office] = [
                "product_id" => $r["product_id"],
                "record_digest" => $r["record_digest"],
                "acceptance_id" => $r["acceptance_id"],
                "commission_id" => $r["commission_id"],
                "author" => $r["author"],
                "authorship_class" => $r["authorship_class"],
            ];
        }
        $id =
            "subordinate-persona-candidate-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $specificationId,
                        $s["record_digest"],
                        $refs,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-subordinate-persona-candidate/v1",
            "candidate_id" => $id,
            "instance_id" => $s["instance_id"],
            "queue_position" => $s["queue_position"],
            "subordinate_staff_class" => $s["subordinate_staff_class"],
            "persona_name" => $s["specification"]["persona_name"],
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "persona_specification_id" => $specificationId,
            "persona_specification_digest" => $s["record_digest"],
            "source_resolution_id" => $s["source_resolution_id"],
            "source_resolution_digest" => $s["source_resolution_digest"],
            "artificer" => $s["artificer"],
            "section_products" => $refs,
            "persona" => [
                "identity_and_requirements" => $s["specification"],
                "evidence_derived_sections" =>
                    $products["hagiography"]["authored_sections"],
                "governance_doctrine_sections" =>
                    $products["studium"]["authored_sections"],
            ],
            "source_citations" => array_values(
                array_unique(
                    array_merge(
                        $products["hagiography"]["source_citations"],
                        $products["studium"]["source_citations"],
                    ),
                ),
            ),
            "unresolved_questions" => array_values(
                array_unique(
                    array_merge(
                        $products["hagiography"]["unresolved_questions"],
                        $products["studium"]["unresolved_questions"],
                    ),
                ),
            ),
            "status" => "ASSEMBLED_PENDING_FOUNDRY_REVIEW",
            "assembly_complete" => true,
            "persona_approval_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
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
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->candidateDirectory) &&
            !mkdir($this->candidateDirectory, 0770, true) &&
            !is_dir($this->candidateDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry subordinate candidate directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->candidateDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read($p, "F127_SUBORDINATE_ASSEMBLY_REPLAY_CONFLICT");
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F127_SUBORDINATE_ASSEMBLY_REPLAY_CONFLICT",
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
                "Subordinate Persona candidate cannot be committed atomically.",
            );
        }
        return $r;
    }
}
