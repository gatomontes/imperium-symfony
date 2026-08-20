<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewCorrectionReturnService
{
    private string $root;
    private string $returnDirectory;
    private SubordinatePersonaSpecificationLineageGuard $lineage;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/offices/foundry";
        $this->returnDirectory =
            $this->root . "/subordinate-clarification-returns";
        $this->lineage = new SubordinatePersonaSpecificationLineageGuard(
            $this->root . "/subordinate-persona-specifications",
        );
    }

    public function returnForRevision(string $resultId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-review-result-[a-f0-9]{20}$/',
                $resultId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F173_ADVERSARIAL_RESULT_ID_INVALID",
            );
        }
        $result = $this->read(
            $this->root . "/adversarial-review-results/" . $resultId . ".json",
            "F174_ADVERSARIAL_CORRECTION_CHAIN_INVALID",
        );
        $candidateId = $result["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-candidates/" .
                    $candidateId .
                    ".json",
                "F174_ADVERSARIAL_CORRECTION_CHAIN_INVALID",
            )
            : [];
        $specificationId =
            $result["review_target_lineage"]["persona_specification_id"] ??
            null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-specifications/" .
                    $specificationId .
                    ".json",
                "F174_ADVERSARIAL_CORRECTION_CHAIN_INVALID",
            )
            : [];

        if (
            !$this->digestMatches($result) ||
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($specification) ||
            "imperium.foundry-adversarial-review-result/v1" !==
                ($result["schema"] ?? null) ||
            "RETURNED_TO_FOUNDRY_FOR_VERSIONED_CORRECTION" !==
                ($result["status"] ?? null) ||
            "RETURN_TO_FOUNDRY" !==
                ($result["decision"]["disposition"] ?? null) ||
            [] === ($result["decision"]["required_corrections"] ?? []) ||
            true !== ($result["return_to_foundry"] ?? null) ||
            true !== ($result["versioned_correction_required"] ?? null) ||
            true !==
                ($result["supersession_required_for_material_correction"] ??
                    null) ||
            ($result["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($result["review_target_lineage"]["persona_specification_digest"] ??
                null) !==
                ($specification["record_digest"] ?? null) ||
            ($candidate["persona_specification_digest"] ?? null) !==
                ($specification["record_digest"] ?? null)
        ) {
            throw new \RuntimeException(
                "F174_ADVERSARIAL_CORRECTION_CHAIN_INVALID",
            );
        }

        $id =
            "subordinate-adversarial-correction-return-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $resultId,
                        $result["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        $specificationId,
                        $specification["record_digest"],
                    ]),
                ),
                0,
                20,
            );

        $record = [
            "schema" =>
                "imperium.foundry-adversarial-review-correction-return/v1",
            "return_id" => $id,
            "instance_id" => $result["instance_id"],
            "adversarial_review_result_id" => $resultId,
            "adversarial_review_result_digest" => $result["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "persona_specification_id" => $specificationId,
            "persona_specification_digest" => $specification["record_digest"],
            "subordinate_construction_case_id" =>
                $candidate["subordinate_construction_case_id"],
            "subordinate_construction_case_digest" =>
                $candidate["subordinate_construction_case_digest"],
            "review_target_lineage" => $result["review_target_lineage"],
            "prior_revision_basis" => $specification["revision_basis"] ?? null,
            "adversarial_findings" => $result["decision"]["findings"],
            "required_corrections" =>
                $result["decision"]["required_corrections"],
            "rationale" => $result["decision"]["rationale"],
            "disposition" =>
                "RETURNED_TO_FOUNDRY_FOR_VERSIONED_SPECIFICATION_REVISION",
            "status" => "PENDING_FOUNDRY_SPECIFICATION_REVISION",
            "specification_revision_authority" => true,
            "re_dispatch_required" => true,
            "persona_approval_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        if (is_file($this->returnDirectory . "/" . $id . ".json")) {
            return $this->persist($id, $record);
        }
        $this->lineage->assertCurrent($specification);
        return $this->persist($id, $record);
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->returnDirectory) &&
            !mkdir($this->returnDirectory, 0770, true) &&
            !is_dir($this->returnDirectory)
        ) {
            throw new \RuntimeException(
                "F175_ADVERSARIAL_CORRECTION_RETURN_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->returnDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F176_ADVERSARIAL_CORRECTION_RETURN_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F176_ADVERSARIAL_CORRECTION_RETURN_CONFLICT",
                );
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $record,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($temporary, $path)
        ) {
            @unlink($temporary);
            throw new \RuntimeException(
                "F175_ADVERSARIAL_CORRECTION_RETURN_FAILED",
            );
        }
        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record["record_digest"] ?? null;
        unset($record["record_digest"]);
        return is_string($digest) &&
            hash_equals(
                $digest,
                hash("sha256", CanonicalJson::encode($record)),
            );
    }
}
