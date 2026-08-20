<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSenateConfirmationRequestService
{
    private string $root;
    private string $returnDirectory;
    private string $deliveryDirectory;
    private string $senateInbox;
    private SubordinatePersonaSpecificationLineageGuard $lineage;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/offices/foundry";
        $this->returnDirectory =
            $this->root . "/inbox/subordinate-persona-admission-returns";
        $this->deliveryDirectory =
            $projectDir .
            "/var/imperium/offices/garrison/inbox/subordinate-persona-admissions";
        $this->senateInbox =
            $projectDir .
            "/var/imperium/offices/senate/inbox/persona-confirmation-requests";
        $this->lineage = new SubordinatePersonaSpecificationLineageGuard(
            $this->root . "/subordinate-persona-specifications",
        );
    }

    public function request(string $returnId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-admission-return-[a-f0-9]{20}$/',
                $returnId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F181_SUBORDINATE_ADMISSION_RETURN_ID_INVALID",
            );
        }
        $return = $this->read(
            $this->returnDirectory . "/" . $returnId . ".json",
            "F182_SUBORDINATE_CONFIRMATION_REQUEST_CHAIN_INVALID",
        );
        $deliveryId = $return["source_delivery_id"] ?? null;
        $delivery = is_string($deliveryId)
            ? $this->read(
                $this->deliveryDirectory . "/" . $deliveryId . ".json",
                "F182_SUBORDINATE_CONFIRMATION_REQUEST_CHAIN_INVALID",
            )
            : [];
        $approvalId = $return["production_approval_id"] ?? null;
        $approval = is_string($approvalId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-production-approvals/" .
                    $approvalId .
                    ".json",
                "F182_SUBORDINATE_CONFIRMATION_REQUEST_CHAIN_INVALID",
            )
            : [];
        $candidateId = $return["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-candidates/" .
                    $candidateId .
                    ".json",
                "F182_SUBORDINATE_CONFIRMATION_REQUEST_CHAIN_INVALID",
            )
            : [];
        $specificationId =
            $return["review_target_lineage"]["persona_specification_id"] ??
            null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-specifications/" .
                    $specificationId .
                    ".json",
                "F182_SUBORDINATE_CONFIRMATION_REQUEST_CHAIN_INVALID",
            )
            : [];

        if (
            !$this->digestMatches($return) ||
            !$this->digestMatches($delivery) ||
            !$this->digestMatches($approval) ||
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($specification) ||
            "imperium.garrison-subordinate-persona-admission-return/v1" !==
                ($return["schema"] ?? null) ||
            "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE" !==
                ($return["disposition"] ?? null) ||
            "REFUSED" !== ($return["admission_decision"] ?? null) ||
            false !== ($return["custody_created"] ?? null) ||
            $this->requiredDefects() !== ($return["defects"] ?? null) ||
            true !== ($return["sealed"] ?? null) ||
            $this->hasAuthority($return) ||
            ($return["source_delivery_digest"] ?? null) !==
                ($delivery["record_digest"] ?? null) ||
            ($return["production_approval_digest"] ?? null) !==
                ($approval["record_digest"] ?? null) ||
            ($return["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($delivery["production_approval_digest"] ?? null) !==
                ($approval["record_digest"] ?? null) ||
            ($delivery["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            "APPROVED_PENDING_GARRISON_ADMISSION_DELIVERY" !==
                ($approval["status"] ?? null) ||
            ($return["review_target_lineage"]["persona_specification_digest"] ??
                null) !==
                ($specification["record_digest"] ?? null)
        ) {
            throw new \RuntimeException(
                "F182_SUBORDINATE_CONFIRMATION_REQUEST_CHAIN_INVALID",
            );
        }

        $id =
            "subordinate-persona-confirmation-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $returnId,
                        $return["record_digest"],
                        $deliveryId,
                        $delivery["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        "senate.qualification",
                    ]),
                ),
                0,
                20,
            );
        $record = [
            "schema" => "imperium.senate-persona-confirmation-request/v1",
            "confirmation_request_id" => $id,
            "instance_id" => $return["instance_id"],
            "proceeding_class" => "PENDING_ADMISSION_PERSONA_QUALIFICATION",
            "requester" => $approval["actor"],
            "recipient" => [
                "office" => "senate",
                "seat" => "senate.lord-speaker",
            ],
            "source_admission_return_id" => $returnId,
            "source_admission_return_digest" => $return["record_digest"],
            "source_admission_delivery_id" => $deliveryId,
            "source_admission_delivery_digest" => $delivery["record_digest"],
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "persona_name" => $candidate["persona_name"],
            "persona_specification_version" =>
                $candidate["persona_specification_version"],
            "persona" => $candidate["persona"],
            "review_target_lineage" => $return["review_target_lineage"],
            "examination_contract" => [
                "subject_state" => "production-approved-pending-admission",
                "manifestation_required" => true,
                "profile_class" => "examination_only",
                "sterile_witness_required" => true,
                "exact_candidate_only" => true,
                "independent_senate_disposition_required" => true,
                "self_review_prohibited" => true,
                "ordinary_operational_use_prohibited" => true,
            ],
            "requested_disposition" =>
                "OPEN_EXACT_MANIFESTATION_BOUND_CONFIRMATION_CASE",
            "status" => "DELIVERED_PENDING_SENATE_ACCEPTANCE",
            "recipient_acceptance" => null,
            "senate_finding" => null,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        if (is_file($this->senateInbox . "/" . $id . ".json")) {
            return $this->persist($id, $record);
        }
        $this->lineage->assertCurrent($specification);
        return $this->persist($id, $record);
    }

    private function requiredDefects(): array
    {
        return [
            "MISSING_EXACT_SENATE_CONFIRMATION_ID",
            "MISSING_EXACT_SENATE_CONFIRMATION_DIGEST",
            "MISSING_EXACT_TESTED_MANIFESTATION_ID",
            "MISSING_EXACT_TESTED_MANIFESTATION_DIGEST",
        ];
    }

    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "admission_authority",
                "profile_approval_authority",
                "spawning_authority",
                "seat_binding_authority",
                "selection_authority",
                "execution_authority",
            ]
            as $key
        ) {
            if (true === ($record[$key] ?? false)) {
                return true;
            }
        }
        return false;
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->senateInbox) &&
            !mkdir($this->senateInbox, 0770, true) &&
            !is_dir($this->senateInbox)
        ) {
            throw new \RuntimeException(
                "F183_SUBORDINATE_CONFIRMATION_REQUEST_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->senateInbox . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F184_SUBORDINATE_CONFIRMATION_REQUEST_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F184_SUBORDINATE_CONFIRMATION_REQUEST_CONFLICT",
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
                "F183_SUBORDINATE_CONFIRMATION_REQUEST_FAILED",
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
