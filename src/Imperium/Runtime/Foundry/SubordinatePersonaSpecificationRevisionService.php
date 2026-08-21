<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSpecificationRevisionService
{
    private string $caseDirectory;
    private string $specificationDirectory;
    private string $returnDirectory;
    private SubordinatePersonaSpecificationLineageGuard $lineage;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private SubordinatePersonaSpecificationRevisionCognitionGateway $cognition,
    ) {
        $root = $projectDir . "/var/imperium/offices/foundry";
        $this->caseDirectory = $root . "/subordinate-construction-cases";
        $this->specificationDirectory =
            $root . "/subordinate-persona-specifications";
        $this->returnDirectory = $root . "/subordinate-clarification-returns";
        $this->lineage = new SubordinatePersonaSpecificationLineageGuard(
            $this->specificationDirectory,
        );
    }

    public function revise(string $returnId): array
    {
        if (
            !preg_match(
                '/^subordinate-(?:clarification|adversarial-correction)-return-[a-f0-9]{20}$/',
                $returnId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F131_CLARIFICATION_RETURN_ID_INVALID",
            );
        }
        $return = $this->read(
            $this->returnDirectory . "/" . $returnId . ".json",
            "F132_CLARIFICATION_RETURN_ABSENT",
        );
        $specificationId = $return["persona_specification_id"] ?? null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->specificationDirectory .
                    "/" .
                    $specificationId .
                    ".json",
                "F133_SPECIFICATION_REVISION_CHAIN_INVALID",
            )
            : [];
        $caseId = $return["subordinate_construction_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "F133_SPECIFICATION_REVISION_CHAIN_INVALID",
            )
            : [];
        $returnKind = $this->returnKind($return);

        if (
            null === $returnKind ||
            !$this->digestMatches($return) ||
            !$this->digestMatches($specification) ||
            !$this->digestMatches($case) ||
            !is_string($case["originating_guildhall_commission_id"] ?? null) ||
            !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $case["originating_guildhall_commission_id"]) ||
            !is_string($case["originating_guildhall_commission_digest"] ?? null) ||
            "PENDING_FOUNDRY_SPECIFICATION_REVISION" !==
                ($return["status"] ?? null) ||
            true !== ($return["specification_revision_authority"] ?? null) ||
            ($return["persona_specification_digest"] ?? null) !==
                ($specification["record_digest"] ?? null) ||
            ($return["subordinate_construction_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($specification["case_id"] ?? null) !== $caseId ||
            ($specification["case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($return["originating_guildhall_commission_id"] ?? null) !== ($specification["originating_guildhall_commission_id"] ?? null) ||
            ($return["originating_guildhall_commission_digest"] ?? null) !== ($specification["originating_guildhall_commission_digest"] ?? null) ||
            ($specification["originating_guildhall_commission_id"] ?? null) !== ($case["originating_guildhall_commission_id"] ?? null) ||
            ($specification["originating_guildhall_commission_digest"] ?? null) !== ($case["originating_guildhall_commission_digest"] ?? null) ||
            !$this->validReturnPayload($returnKind, $return)
        ) {
            throw new \RuntimeException(
                "F133_SPECIFICATION_REVISION_CHAIN_INVALID",
            );
        }
        foreach (
            glob(
                $this->specificationDirectory .
                    "/subordinate-persona-specification-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read(
                $path,
                "F136_SPECIFICATION_REVISION_REPLAY_CONFLICT",
            );
            if (
                $returnId ===
                    ($existing["revision_basis"]["return_id"] ??
                        ($existing["revision_basis"][
                            "clarification_return_id"
                        ] ??
                            null)) &&
                $this->digestMatches($existing)
            ) {
                return $existing;
            }
        }
        $this->lineage->assertCurrent($specification);

        $version = ($specification["specification_version"] ?? 1) + 1;
        $decision = $this->cognition->revise($case, $specification, $return);
        $complete =
            "PERSONA_SPECIFICATION_COMPLETE" ===
            ($decision["disposition"] ?? null);
        $id =
            "subordinate-persona-specification-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $specificationId,
                        $specification["record_digest"],
                        $returnId,
                        $return["record_digest"],
                        $version,
                        $decision,
                    ]),
                ),
                0,
                20,
            );

        return $this->persist($id, [
            "schema" => "imperium.foundry-subordinate-persona-specification/v1",
            "specification_id" => $id,
            "specification_version" => $version,
            "instance_id" => $specification["instance_id"],
            "case_id" => $caseId,
            "case_digest" => $case["record_digest"],
            "originating_guildhall_commission_id" => $case["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $case["originating_guildhall_commission_digest"],
            "queue_position" => $specification["queue_position"],
            "office" => $specification["office"],
            "subordinate_staff_class" =>
                $specification["subordinate_staff_class"],
            "source_resolution_id" => $specification["source_resolution_id"],
            "source_resolution_digest" =>
                $specification["source_resolution_digest"],
            "artificer" => $specification["artificer"],
            "inherited_requirements" =>
                $specification["inherited_requirements"],
            "specification" => $decision,
            "supersedes" => [
                "specification_id" => $specificationId,
                "specification_digest" => $specification["record_digest"],
                "specification_version" => $version - 1,
            ],
            "revision_basis" => $this->revisionBasis(
                $returnKind,
                $returnId,
                $return,
            ),
            "status" => $complete
                ? "SEALED_PENDING_PERSONA_CONSTRUCTION"
                : "CLARIFICATION_REQUIRED",
            "persona_specification_complete" => $complete,
            "construction_authority" => $complete,
            "persona_selection_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function returnKind(array $return): ?string
    {
        return match ($return["schema"] ?? null) {
            "imperium.subordinate-specification-clarification-return/v1"
                => "CLARIFICATION",
            "imperium.foundry-adversarial-review-correction-return/v1"
                => "ADVERSARIAL_CORRECTION",
            default => null,
        };
    }

    private function validReturnPayload(string $kind, array $return): bool
    {
        return "CLARIFICATION" === $kind
            ? [] !==
                    ($return["original_clarification"][
                        "unresolved_questions"
                    ] ??
                        [])
            : [] !== ($return["required_corrections"] ?? []) &&
                    true === ($return["re_dispatch_required"] ?? null) &&
                    is_array($return["review_target_lineage"] ?? null);
    }

    private function revisionBasis(
        string $kind,
        string $returnId,
        array $return,
    ): array {
        if ("CLARIFICATION" === $kind) {
            return [
                "return_kind" => $kind,
                "return_id" => $returnId,
                "return_digest" => $return["record_digest"],
                "clarification_return_id" => $returnId,
                "clarification_return_digest" => $return["record_digest"],
                "original_clarification_product_id" =>
                    $return["clarification_product_id"],
                "original_clarification_product_digest" =>
                    $return["clarification_product_digest"],
                "original_clarification" => $return["original_clarification"],
            ];
        }
        return [
            "return_kind" => $kind,
            "return_id" => $returnId,
            "return_digest" => $return["record_digest"],
            "adversarial_review_result_id" =>
                $return["adversarial_review_result_id"],
            "adversarial_review_result_digest" =>
                $return["adversarial_review_result_digest"],
            "review_target_lineage" => $return["review_target_lineage"],
            "prior_revision_basis" => $return["prior_revision_basis"],
            "adversarial_findings" => $return["adversarial_findings"],
            "required_corrections" => $return["required_corrections"],
            "rationale" => $return["rationale"],
        ];
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->specificationDirectory) &&
            !mkdir($this->specificationDirectory, 0770, true) &&
            !is_dir($this->specificationDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry specification directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->specificationDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F136_SPECIFICATION_REVISION_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F136_SPECIFICATION_REVISION_REPLAY_CONFLICT",
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
                "Revised specification cannot be committed atomically.",
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
