<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaReviewService
{
    private string $candidateDirectory;
    private string $specificationDirectory;
    private string $caseDirectory;
    private string $reviewDirectory;
    private SubordinatePersonaSpecificationLineageGuard $lineageGuard;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private SubordinatePersonaReviewCognitionGateway $cognition,
    ) {
        $root = $projectDir . "/var/imperium/offices/foundry";
        $this->candidateDirectory = $root . "/subordinate-persona-candidates";
        $this->specificationDirectory =
            $root . "/subordinate-persona-specifications";
        $this->caseDirectory = $root . "/subordinate-construction-cases";
        $this->reviewDirectory = $root . "/subordinate-persona-reviews";
        $this->lineageGuard = new SubordinatePersonaSpecificationLineageGuard(
            $this->specificationDirectory,
        );
    }

    public function review(string $candidateId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-candidate-[a-f0-9]{20}$/',
                $candidateId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F141_SUBORDINATE_CANDIDATE_ID_INVALID",
            );
        }
        $candidate = $this->read(
            $this->candidateDirectory . "/" . $candidateId . ".json",
            "F142_SUBORDINATE_CANDIDATE_ABSENT",
        );
        $specificationId = $candidate["persona_specification_id"] ?? null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->specificationDirectory .
                    "/" .
                    $specificationId .
                    ".json",
                "F143_PERSONA_REVIEW_CHAIN_INVALID",
            )
            : [];
        $caseId = $candidate["subordinate_construction_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "F143_PERSONA_REVIEW_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($specification) ||
            !$this->digestMatches($case) ||
            "imperium.foundry-subordinate-persona-candidate/v1" !==
                ($candidate["schema"] ?? null) ||
            "ASSEMBLED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true !== ($candidate["assembly_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            ($candidate["persona_specification_digest"] ?? null) !==
                ($specification["record_digest"] ?? null) ||
            ($candidate["persona_specification_version"] ?? 1) !==
                ($specification["specification_version"] ?? 1) ||
            CanonicalJson::encode(
                $candidate["specification_supersedes"] ?? null,
            ) !== CanonicalJson::encode($specification["supersedes"] ?? null) ||
            CanonicalJson::encode(
                $candidate["specification_revision_basis"] ?? null,
            ) !==
                CanonicalJson::encode(
                    $specification["revision_basis"] ?? null,
                ) ||
            (null !== ($specification["supersedes"] ?? null)
                ? "SPECIFICATION_REVISION_REISSUE"
                : "INITIAL_SPECIFICATION_DISPATCH") !==
                ($candidate["dispatch_kind"] ?? null) ||
            (null !== ($specification["supersedes"] ?? null)
                ? !is_array($candidate["superseded_commissions"] ?? null) ||
                    2 !== count($candidate["superseded_commissions"])
                : [] !== ($candidate["superseded_commissions"] ?? null)) ||
            ($candidate["subordinate_construction_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            true === ($candidate["persona_approval_authority"] ?? null) ||
            true === ($candidate["admission_authority"] ?? null) ||
            true === ($candidate["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("F143_PERSONA_REVIEW_CHAIN_INVALID");
        }
        $this->lineageGuard->assertCurrent($specification);
        foreach (
            glob(
                $this->reviewDirectory . "/subordinate-persona-review-*.json",
            ) ?:
            []
            as $path
        ) {
            $old = $this->read($path, "F146_PERSONA_REVIEW_REPLAY_CONFLICT");
            if (
                $candidateId === ($old["candidate_id"] ?? null) &&
                $this->digestMatches($old)
            ) {
                return $old;
            }
        }
        $decision = $this->cognition->review($candidate, $specification, $case);
        $this->validateDecision($decision);
        $ready = "READY_FOR_ADVERSARIAL_REVIEW" === $decision["disposition"];
        $id =
            "subordinate-persona-review-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $candidateId,
                        $candidate["record_digest"],
                        $decision,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-subordinate-persona-review/v1",
            "review_id" => $id,
            "instance_id" => $candidate["instance_id"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "persona_specification_id" => $specificationId,
            "persona_specification_digest" => $specification["record_digest"],
            "persona_specification_version" =>
                $specification["specification_version"] ?? 1,
            "specification_supersedes" => $specification["supersedes"] ?? null,
            "specification_revision_basis" =>
                $specification["revision_basis"] ?? null,
            "dispatch_kind" => $candidate["dispatch_kind"],
            "superseded_commissions" => $candidate["superseded_commissions"],
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "artificer" => $candidate["artificer"],
            "decision" => $decision,
            "status" => $ready
                ? "SEALED_PENDING_FOUNDRY_ADVERSARIAL_REVIEW"
                : "SEALED_PENDING_FOUNDRY_REVISION",
            "completeness_review_complete" => true,
            "adversarial_review_authority" => $ready,
            "persona_approval_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function validateDecision(array $decision): void
    {
        $keys = array_keys($decision);
        sort($keys, SORT_STRING);
        if (
            [
                "adversarial_review_brief",
                "disposition",
                "findings",
                "unresolved_blockers",
            ] !== $keys ||
            !in_array(
                $decision["disposition"] ?? null,
                ["READY_FOR_ADVERSARIAL_REVIEW", "REVISION_REQUIRED"],
                true,
            ) ||
            !is_array($decision["findings"] ?? null) ||
            !is_array($decision["unresolved_blockers"] ?? null) ||
            !is_string($decision["adversarial_review_brief"] ?? null) ||
            "" === trim($decision["adversarial_review_brief"]) ||
            ("READY_FOR_ADVERSARIAL_REVIEW" === $decision["disposition"] &&
                [] !== $decision["unresolved_blockers"])
        ) {
            throw new \RuntimeException("F144_PERSONA_REVIEW_CONTRACT_INVALID");
        }
        foreach (["findings", "unresolved_blockers"] as $field) {
            foreach ($decision[$field] as $item) {
                if (!is_string($item) || "" === trim($item)) {
                    throw new \RuntimeException(
                        "F144_PERSONA_REVIEW_CONTRACT_INVALID",
                    );
                }
            }
        }
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
    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->reviewDirectory) &&
            !mkdir($this->reviewDirectory, 0770, true) &&
            !is_dir($this->reviewDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry review directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->reviewDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $old = $this->read($path, "F146_PERSONA_REVIEW_REPLAY_CONFLICT");
            if (
                CanonicalJson::encode($old) !== CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F146_PERSONA_REVIEW_REPLAY_CONFLICT",
                );
            }
            return $old;
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
            throw new \RuntimeException(
                "Persona review cannot be committed atomically.",
            );
        }
        return $record;
    }
}
