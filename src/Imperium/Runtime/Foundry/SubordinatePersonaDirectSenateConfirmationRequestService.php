<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaDirectSenateConfirmationRequestService
{
    private string $root;
    private string $senateInbox;
    private SubordinatePersonaSpecificationLineageGuard $lineage;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/offices/foundry";
        $this->senateInbox =
            $projectDir .
            "/var/imperium/offices/senate/inbox/persona-confirmation-requests";
        $this->lineage = new SubordinatePersonaSpecificationLineageGuard(
            $this->root . "/subordinate-persona-specifications",
        );
    }

    public function request(string $approvalId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-production-approval-[a-f0-9]{20}$/',
                $approvalId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F185_SUBORDINATE_PRODUCTION_APPROVAL_ID_INVALID",
            );
        }
        $approval = $this->read(
            $this->root .
                "/subordinate-persona-production-approvals/" .
                $approvalId .
                ".json",
            "F186_DIRECT_SENATE_REQUEST_CHAIN_INVALID",
        );
        $candidateId = $approval["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-candidates/" .
                    $candidateId .
                    ".json",
                "F186_DIRECT_SENATE_REQUEST_CHAIN_INVALID",
            )
            : [];
        $resultId = $approval["adversarial_review_result_id"] ?? null;
        $result = is_string($resultId)
            ? $this->read(
                $this->root .
                    "/adversarial-review-results/" .
                    $resultId .
                    ".json",
                "F186_DIRECT_SENATE_REQUEST_CHAIN_INVALID",
            )
            : [];
        $specificationId =
            $approval["review_target_lineage"]["persona_specification_id"] ??
            null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-specifications/" .
                    $specificationId .
                    ".json",
                "F186_DIRECT_SENATE_REQUEST_CHAIN_INVALID",
            )
            : [];

        if (
            !$this->digestMatches($approval) ||
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($result) ||
            !$this->digestMatches($specification) ||
            "imperium.foundry-subordinate-persona-production-approval/v1" !==
                ($approval["schema"] ?? null) ||
            "APPROVED_AS_EXACT_REVIEWED_PERSONA_PRODUCTION" !==
                ($approval["disposition"] ?? null) ||
            "APPROVED_PENDING_SENATE_CONFIRMATION_REQUEST" !==
                ($approval["status"] ?? null) ||
            true !== ($approval["production_approval"] ?? null) ||
            true !== ($approval["sealed"] ?? null) ||
            ($approval["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($approval["adversarial_review_result_digest"] ?? null) !==
                ($result["record_digest"] ?? null) ||
            "PASSED_PENDING_FOUNDRY_PRODUCTION_APPROVAL" !==
                ($result["status"] ?? null) ||
            "PASSED" !== ($result["decision"]["disposition"] ?? null) ||
            ($approval["review_target_lineage"][
                "persona_specification_digest"
            ] ??
                null) !==
                ($specification["record_digest"] ?? null) ||
            ($candidate["persona_specification_digest"] ?? null) !==
                ($specification["record_digest"] ?? null) ||
            !is_string($candidate["originating_guildhall_commission_id"] ?? null) ||
            !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $candidate["originating_guildhall_commission_id"]) ||
            ($approval["originating_guildhall_commission_id"] ?? null) !== ($candidate["originating_guildhall_commission_id"] ?? null) ||
            ($approval["originating_guildhall_commission_digest"] ?? null) !== ($candidate["originating_guildhall_commission_digest"] ?? null) ||
            ($result["originating_guildhall_commission_id"] ?? null) !== ($candidate["originating_guildhall_commission_id"] ?? null) ||
            ($result["originating_guildhall_commission_digest"] ?? null) !== ($candidate["originating_guildhall_commission_digest"] ?? null) ||
            ($specification["originating_guildhall_commission_id"] ?? null) !== ($candidate["originating_guildhall_commission_id"] ?? null) ||
            ($specification["originating_guildhall_commission_digest"] ?? null) !== ($candidate["originating_guildhall_commission_digest"] ?? null) ||
            true === ($approval["admission_authority"] ?? null) ||
            true === ($approval["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "F186_DIRECT_SENATE_REQUEST_CHAIN_INVALID",
            );
        }

        $id =
            "subordinate-persona-confirmation-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $approvalId,
                        $approval["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        "CANONICAL_FOUNDRY_TO_SENATE",
                    ]),
                ),
                0,
                20,
            );
        $record = [
            "schema" => "imperium.senate-persona-confirmation-request/v1",
            "confirmation_request_id" => $id,
            "instance_id" => $approval["instance_id"],
            "route_class" => "CANONICAL_FOUNDRY_TO_SENATE",
            "proceeding_class" => "PENDING_ADMISSION_PERSONA_QUALIFICATION",
            "requester" => $approval["actor"],
            "recipient" => [
                "office" => "senate",
                "seat" => "senate.lord-speaker",
            ],
            "source_admission_return_id" => null,
            "source_admission_return_digest" => null,
            "source_admission_delivery_id" => null,
            "source_admission_delivery_digest" => null,
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "originating_guildhall_commission_id" => $candidate["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $candidate["originating_guildhall_commission_digest"],
            "persona_name" => $candidate["persona_name"],
            "persona_specification_version" =>
                $candidate["persona_specification_version"],
            "persona" => $candidate["persona"],
            "review_target_lineage" => $approval["review_target_lineage"],
            "examination_contract" => [
                "subject_state" =>
                    "production-approved-pending-senate-approval",
                "manifestation_required" => true,
                "profile_required" => false,
                "profile_class" => null,
                "officer_substrate_required" => false,
                "senate_local_instantiation" => true,
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

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->senateInbox) &&
            !mkdir($this->senateInbox, 0770, true) &&
            !is_dir($this->senateInbox)
        ) {
            throw new \RuntimeException("F187_DIRECT_SENATE_REQUEST_FAILED");
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->senateInbox . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F188_DIRECT_SENATE_REQUEST_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F188_DIRECT_SENATE_REQUEST_CONFLICT",
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
            throw new \RuntimeException("F187_DIRECT_SENATE_REQUEST_FAILED");
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
