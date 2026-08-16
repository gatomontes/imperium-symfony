<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialPersonaReviewService
{
    private string $root;
    private string $acceptanceDirectory;
    private string $resultDirectory;
    private SubordinatePersonaSpecificationLineageGuard $lineage;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private AdversarialPersonaReviewCognitionGateway $cognition,
    ) {
        $this->root = $projectDir . "/var/imperium/offices/foundry";
        $this->acceptanceDirectory =
            $this->root . "/adversarial-review-acceptances";
        $this->resultDirectory = $this->root . "/adversarial-review-results";
        $this->lineage = new SubordinatePersonaSpecificationLineageGuard(
            $this->root . "/subordinate-persona-specifications",
        );
    }

    public function review(string $acceptanceId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-review-acceptance-[a-f0-9]{20}$/',
                $acceptanceId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F162_ADVERSARIAL_ACCEPTANCE_ID_INVALID",
            );
        }
        $acceptance = $this->read(
            $this->acceptanceDirectory . "/" . $acceptanceId . ".json",
            "F163_ADVERSARIAL_ACCEPTANCE_ABSENT",
        );
        $candidateId = $acceptance["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-candidates/" .
                    $candidateId .
                    ".json",
                "F164_ADVERSARIAL_REVIEW_CHAIN_INVALID",
            )
            : [];
        $specificationId =
            $acceptance["review_target_lineage"]["persona_specification_id"] ??
            null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-specifications/" .
                    $specificationId .
                    ".json",
                "F164_ADVERSARIAL_REVIEW_CHAIN_INVALID",
            )
            : [];
        $caseId = $candidate["subordinate_construction_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->root .
                    "/subordinate-construction-cases/" .
                    $caseId .
                    ".json",
                "F164_ADVERSARIAL_REVIEW_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($acceptance) ||
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($specification) ||
            !$this->digestMatches($case) ||
            "imperium.foundry-adversarial-review-acceptance/v1" !==
                ($acceptance["schema"] ?? null) ||
            "ACCEPTED_FOR_EXACT_ADVERSARIAL_REVIEW" !==
                ($acceptance["disposition"] ?? null) ||
            true !== ($acceptance["recipient_acceptance"] ?? null) ||
            true !== ($acceptance["review_authority_exercisable"] ?? null) ||
            ($acceptance["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($acceptance["review_target_lineage"][
                "persona_specification_digest"
            ] ??
                null) !==
                ($specification["record_digest"] ?? null) ||
            ($candidate["persona_specification_digest"] ?? null) !==
                ($specification["record_digest"] ?? null) ||
            ($candidate["subordinate_construction_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            "ASSEMBLED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true === ($acceptance["persona_approval_authority"] ?? null) ||
            true === ($acceptance["admission_authority"] ?? null) ||
            true === ($acceptance["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "F164_ADVERSARIAL_REVIEW_CHAIN_INVALID",
            );
        }
        $this->lineage->assertCurrent($specification);
        foreach (
            glob(
                $this->resultDirectory .
                    "/foundry-adversarial-review-result-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read($path, "F167_ADVERSARIAL_REVIEW_CONFLICT");
            if (
                $acceptanceId === ($existing["acceptance_id"] ?? null) &&
                $this->digestMatches($existing)
            ) {
                return $existing;
            }
        }
        $decision = $this->cognition->review(
            $candidate,
            $specification,
            $case,
            $acceptance,
        );
        $this->validate($decision);
        $passed = "PASSED" === $decision["disposition"];
        $id =
            "foundry-adversarial-review-result-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $acceptanceId,
                        $acceptance["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        $decision,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-adversarial-review-result/v1",
            "result_id" => $id,
            "instance_id" => $acceptance["instance_id"],
            "acceptance_id" => $acceptanceId,
            "acceptance_digest" => $acceptance["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "review_target_lineage" => $acceptance["review_target_lineage"],
            "reviewer" => $acceptance["reviewer"],
            "decision" => $decision,
            "status" => $passed
                ? "PASSED_PENDING_FOUNDRY_PRODUCTION_APPROVAL"
                : "RETURNED_TO_FOUNDRY_FOR_VERSIONED_CORRECTION",
            "return_to_foundry" => !$passed,
            "versioned_correction_required" => !$passed,
            "supersession_required_for_material_correction" => !$passed,
            "foundry_production_approval_eligible" => $passed,
            "persona_approval_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function validate(array $decision): void
    {
        $keys = array_keys($decision);
        sort($keys, SORT_STRING);
        if (
            ["disposition", "findings", "rationale", "required_corrections"] !==
                $keys ||
            !in_array(
                $decision["disposition"] ?? null,
                ["PASSED", "RETURN_TO_FOUNDRY"],
                true,
            ) ||
            !is_string($decision["rationale"] ?? null) ||
            "" === trim($decision["rationale"]) ||
            !is_array($decision["findings"] ?? null) ||
            !is_array($decision["required_corrections"] ?? null) ||
            ("PASSED" === $decision["disposition"] &&
                [] !== $decision["required_corrections"]) ||
            ("RETURN_TO_FOUNDRY" === $decision["disposition"] &&
                [] === $decision["required_corrections"])
        ) {
            throw new \RuntimeException(
                "F165_ADVERSARIAL_REVIEW_CONTRACT_INVALID",
            );
        }
        foreach (["findings", "required_corrections"] as $field) {
            foreach ($decision[$field] as $item) {
                if (!is_string($item) || "" === trim($item)) {
                    throw new \RuntimeException(
                        "F165_ADVERSARIAL_REVIEW_CONTRACT_INVALID",
                    );
                }
            }
        }
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->resultDirectory) &&
            !mkdir($this->resultDirectory, 0770, true) &&
            !is_dir($this->resultDirectory)
        ) {
            throw new \RuntimeException("F166_ADVERSARIAL_REVIEW_FAILED");
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->resultDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "F167_ADVERSARIAL_REVIEW_CONFLICT");
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException("F167_ADVERSARIAL_REVIEW_CONFLICT");
            }
            return $existing;
        }
        $tmp = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $tmp,
                    json_encode(
                        $record,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($tmp, $path)
        ) {
            @unlink($tmp);
            throw new \RuntimeException("F166_ADVERSARIAL_REVIEW_FAILED");
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
