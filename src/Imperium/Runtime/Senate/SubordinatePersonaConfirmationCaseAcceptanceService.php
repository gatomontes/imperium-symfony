<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaConfirmationCaseAcceptanceService
{
    private string $caseDirectory;
    private string $occupancyDirectory;
    private string $acceptanceDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $root = $projectDir . "/var/imperium/offices/senate";
        $this->caseDirectory = $root . "/confirmation-cases";
        $this->occupancyDirectory = $root . "/occupancy";
        $this->acceptanceDirectory = $root . "/confirmation-case-acceptances";
    }

    public function accept(string $caseId): array
    {
        if (
            !preg_match(
                '/^senate-subordinate-confirmation-case-[a-f0-9]{20}$/',
                $caseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "S113_SUBORDINATE_CONFIRMATION_CASE_ID_INVALID",
            );
        }
        $case = $this->read(
            $this->caseDirectory . "/" . $caseId . ".json",
            "S114_SUBORDINATE_CONFIRMATION_CASE_ABSENT",
        );
        $lordSpeaker = $this->binding(
            $case["lord_speaker_occupancy"] ?? null,
            "senate.lord-speaker",
            "confirmation_acceptance_authority",
        );
        $bailiff = $this->binding(
            $case["bailiff_occupancy"] ?? null,
            "senate.bailiff",
            "proceeding_security_authority",
        );
        if (
            !$this->digestMatches($case) ||
            "imperium.senate-subordinate-persona-confirmation-case/v1" !==
                ($case["schema"] ?? null) ||
            "CANONICAL_FOUNDRY_TO_SENATE" !== ($case["route_class"] ?? null) ||
            "PENDING_LORD_SPEAKER_ACCEPTANCE" !== ($case["status"] ?? null) ||
            [] !== ($case["activation_required"] ?? null) ||
            true !== ($case["request_preserved"] ?? null) ||
            null !== ($case["recipient_acceptance"] ?? null) ||
            null !== ($case["confirmation_plan"] ?? null) ||
            true === ($case["assembly_request_authority"] ?? null) ||
            true === ($case["witness_instantiation_authority"] ?? null) ||
            ($case["instance_id"] ?? null) !==
                ($lordSpeaker["instance_id"] ?? null) ||
            ($case["instance_id"] ?? null) !== ($bailiff["instance_id"] ?? null)
        ) {
            throw new \RuntimeException(
                "S115_SUBORDINATE_CONFIRMATION_CASE_INVALID",
            );
        }

        $id =
            "senate-subordinate-confirmation-acceptance-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $caseId,
                        $case["record_digest"],
                        $lordSpeaker["binding_id"],
                        $lordSpeaker["record_digest"],
                        $bailiff["binding_id"],
                        $bailiff["record_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.senate-subordinate-persona-confirmation-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => $case["instance_id"],
            "confirmation_case_id" => $caseId,
            "confirmation_case_digest" => $case["record_digest"],
            "candidate_id" => $case["candidate_id"],
            "candidate_digest" => $case["candidate_digest"],
            "persona_name" => $case["persona_name"],
            "persona_specification_version" =>
                $case["persona_specification_version"],
            "review_target_lineage" => $case["review_target_lineage"],
            "examination_contract" => $case["examination_contract"],
            "lord_speaker" => $this->actor($lordSpeaker),
            "bailiff" => $this->actor($bailiff),
            "disposition" => "ACCEPTED_FOR_EXACT_EXAMINATION_ASSEMBLY_REQUEST",
            "status" => "ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_REQUEST",
            "recipient_acceptance" => true,
            "assembly_request_authority" => true,
            "assembly_request_authority_exercisable" => true,
            "witness_instantiation_authority" => false,
            "senate_finding_authority" => false,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function binding(
        mixed $reference,
        string $seat,
        string $authority,
    ): array {
        $bindingId = is_array($reference)
            ? $reference["binding_id"] ?? null
            : null;
        $record = is_string($bindingId)
            ? $this->read(
                $this->occupancyDirectory . "/" . $bindingId . ".json",
                "S115_SUBORDINATE_CONFIRMATION_CASE_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($record) ||
            ($reference["binding_digest"] ?? null) !==
                ($record["record_digest"] ?? null) ||
            $seat !== ($record["seat"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record[$authority] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "S115_SUBORDINATE_CONFIRMATION_CASE_INVALID",
            );
        }
        return $record;
    }

    private function actor(array $binding): array
    {
        return [
            "seat" => $binding["seat"],
            "binding_id" => $binding["binding_id"],
            "binding_digest" => $binding["record_digest"],
            "manifestation_id" => $binding["manifestation_id"],
            "occupancy_generation" => $binding["occupancy_generation"],
            "provenance" => $binding["provenance"] ?? "GOVERNED_OCCUPANCY",
            "founding_class" => $binding["founding_class"] ?? "ARTIFACT_BACKED",
            "placeholder_version" => $binding["placeholder_version"] ?? null,
        ];
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->acceptanceDirectory) &&
            !mkdir($this->acceptanceDirectory, 0770, true) &&
            !is_dir($this->acceptanceDirectory)
        ) {
            throw new \RuntimeException(
                "S116_SUBORDINATE_CONFIRMATION_ACCEPTANCE_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->acceptanceDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "S117_SUBORDINATE_CONFIRMATION_ACCEPTANCE_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "S117_SUBORDINATE_CONFIRMATION_ACCEPTANCE_CONFLICT",
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
                "S116_SUBORDINATE_CONFIRMATION_ACCEPTANCE_FAILED",
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
