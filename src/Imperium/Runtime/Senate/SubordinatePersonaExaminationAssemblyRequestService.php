<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaExaminationAssemblyRequestService
{
    private string $acceptanceDirectory;
    private string $caseDirectory;
    private string $inbox;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->acceptanceDirectory = $senate . "/confirmation-case-acceptances";
        $this->caseDirectory = $senate . "/confirmation-cases";
        $this->inbox =
            $projectDir .
            "/var/imperium/offices/conscription/inbox/persona-examination-assembly-requests";
    }

    public function request(string $acceptanceId): array
    {
        if (
            !preg_match(
                '/^senate-subordinate-confirmation-acceptance-[a-f0-9]{20}$/',
                $acceptanceId,
            )
        ) {
            throw new \InvalidArgumentException(
                "S118_SUBORDINATE_CONFIRMATION_ACCEPTANCE_ID_INVALID",
            );
        }
        $acceptance = $this->read(
            $this->acceptanceDirectory . "/" . $acceptanceId . ".json",
            "S119_EXAMINATION_ASSEMBLY_REQUEST_CHAIN_INVALID",
        );
        $caseId = $acceptance["confirmation_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "S119_EXAMINATION_ASSEMBLY_REQUEST_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($acceptance) ||
            !$this->digestMatches($case) ||
            "imperium.senate-subordinate-persona-confirmation-acceptance/v1" !==
                ($acceptance["schema"] ?? null) ||
            "ACCEPTED_FOR_EXACT_EXAMINATION_ASSEMBLY_REQUEST" !==
                ($acceptance["disposition"] ?? null) ||
            "ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_REQUEST" !==
                ($acceptance["status"] ?? null) ||
            true !== ($acceptance["recipient_acceptance"] ?? null) ||
            true !== ($acceptance["assembly_request_authority"] ?? null) ||
            true !==
                ($acceptance["assembly_request_authority_exercisable"] ??
                    null) ||
            true === ($acceptance["witness_instantiation_authority"] ?? null) ||
            ($acceptance["confirmation_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($acceptance["candidate_digest"] ?? null) !==
                ($case["candidate_digest"] ?? null) ||
            "CANONICAL_FOUNDRY_TO_SENATE" !== ($case["route_class"] ?? null) ||
            "examination_only" !==
                ($acceptance["examination_contract"]["profile_class"] ??
                    null) ||
            true !==
                ($acceptance["examination_contract"][
                    "sterile_witness_required"
                ] ??
                    null) ||
            true === ($acceptance["admission_authority"] ?? null) ||
            true === ($acceptance["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "S119_EXAMINATION_ASSEMBLY_REQUEST_CHAIN_INVALID",
            );
        }

        $id =
            "senate-subordinate-examination-assembly-request-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $acceptanceId,
                        $acceptance["record_digest"],
                        $caseId,
                        $case["record_digest"],
                        $acceptance["candidate_id"],
                        $acceptance["candidate_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.conscription-subordinate-persona-examination-assembly-request/v1",
            "assembly_request_id" => $id,
            "instance_id" => $acceptance["instance_id"],
            "route_class" => "CANONICAL_FOUNDRY_TO_SENATE",
            "source_confirmation_acceptance_id" => $acceptanceId,
            "source_confirmation_acceptance_digest" =>
                $acceptance["record_digest"],
            "source_confirmation_case_id" => $caseId,
            "source_confirmation_case_digest" => $case["record_digest"],
            "requester" => $acceptance["lord_speaker"],
            "security_officer" => $acceptance["bailiff"],
            "recipient" => [
                "office" => "conscription",
                "seat" => "conscription.recruiter",
            ],
            "candidate_id" => $acceptance["candidate_id"],
            "candidate_digest" => $acceptance["candidate_digest"],
            "persona_name" => $acceptance["persona_name"],
            "persona_specification_version" =>
                $acceptance["persona_specification_version"],
            "review_target_lineage" => $acceptance["review_target_lineage"],
            "examination_contract" => $acceptance["examination_contract"],
            "required_assembly" => [
                "quantity" => 1,
                "profile_class" => "examination_only",
                "generic_officer_substrate_required" => true,
                "sterile_witness" => true,
                "exact_candidate_only" => true,
                "senate_chamber_only" => true,
                "ordinary_operational_use_prohibited" => true,
                "retire_after_disposition" => true,
            ],
            "requested_disposition" =>
                "ACCEPT_EXACT_EXAMINATION_ASSEMBLY_COMMISSION",
            "status" => "DELIVERED_PENDING_CONSCRIPTION_ACCEPTANCE",
            "recipient_acceptance" => null,
            "assembly_request_authority_consumed" => true,
            "profile_commission_authority_exercisable" => false,
            "profile_approval_authority" => false,
            "witness_instantiation_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "senate_finding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->inbox) &&
            !mkdir($this->inbox, 0770, true) &&
            !is_dir($this->inbox)
        ) {
            throw new \RuntimeException(
                "S120_EXAMINATION_ASSEMBLY_REQUEST_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->inbox . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "S121_EXAMINATION_ASSEMBLY_REQUEST_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "S121_EXAMINATION_ASSEMBLY_REQUEST_CONFLICT",
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
                "S120_EXAMINATION_ASSEMBLY_REQUEST_FAILED",
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
