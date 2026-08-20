<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenateResidentProvisioningCaseService
{
    private string $demands;
    private string $cases;
    private string $projectDir;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->projectDir = $projectDir;
        $this->demands =
            $projectDir . "/var/imperium/mastermason/spawning-requests";
        $this->cases =
            $projectDir . "/var/imperium/mastermason/activation-cases";
    }

    public function open(string $demandId): array
    {
        if (!preg_match('/^senate-activation-[a-f0-9]{20}$/', $demandId)) {
            throw new \InvalidArgumentException(
                "M130_SENATE_ACTIVATION_DEMAND_ID_INVALID",
            );
        }
        $demand = $this->read(
            $this->demands . "/" . $demandId . ".json",
            "M131_SENATE_ACTIVATION_DEMAND_ABSENT",
        );
        if (
            !$this->digestMatches($demand) ||
            "imperium.senate-activation-demand/v1" !==
                ($demand["schema"] ?? null) ||
            $demandId !== ($demand["demand_id"] ?? null) ||
            "mastermason" !== ($demand["recipient"]["id"] ?? null) ||
            "SENATE_RESIDENT_STAFF_CONSTRUCTION_REQUIRED" !==
                ($demand["status"] ?? null) ||
            true !== ($demand["sealed"] ?? null) ||
            false !== ($demand["occupancy_claimed"] ?? null) ||
            $this->hasAuthority($demand)
        ) {
            throw new \RuntimeException(
                "M132_SENATE_ACTIVATION_DEMAND_INVALID",
            );
        }

        $required = $demand["required_seats"] ?? null;
        if (!is_array($required) || 2 !== count($required)) {
            throw new \RuntimeException("M133_SENATE_REQUIRED_SEATS_INVALID");
        }
        $expected = ["senate.lord-speaker", "senate.bailiff"];
        $seen = [];
        $cases = [];
        foreach ($required as $index => $seat) {
            if (
                !is_array($seat) ||
                ($expected[$index] ?? null) !== ($seat["seat"] ?? null) ||
                isset($seen[$seat["seat"]])
            ) {
                throw new \RuntimeException(
                    "M133_SENATE_REQUIRED_SEATS_INVALID",
                );
            }
            $this->validateSeatSources($seat);
            if (
                "CONSTRUCTION_REQUIRED" !== ($seat["persona_state"] ?? null) ||
                "SOURCE_CONTRACT_ONLY" !== ($seat["profile_state"] ?? null) ||
                null !== ($seat["canonical_staff_package"] ?? null) ||
                null !== ($seat["occupancy"] ?? null)
            ) {
                throw new \RuntimeException(
                    "M134_SENATE_RESIDENT_STATE_INVALID",
                );
            }

            $role =
                "senate.lord-speaker" === $seat["seat"]
                    ? "lord-speaker"
                    : "bailiff";
            $caseId =
                "senate-" .
                $role .
                "-provisioning-" .
                substr(
                    hash(
                        "sha256",
                        CanonicalJson::encode([
                            $demandId,
                            $demand["record_digest"],
                            $index,
                            $seat,
                        ]),
                    ),
                    0,
                    20,
                );
            $cases[] = $this->persist($caseId, [
                "schema" => "imperium.senate-resident-provisioning-case/v1",
                "case_id" => $caseId,
                "instance_id" => $demand["instance_id"],
                "office" => "senate",
                "target_seat" => $seat["seat"],
                "resident_role" => $role,
                "source_activation_demand_id" => $demandId,
                "source_activation_demand_digest" => $demand["record_digest"],
                "source_confirmation_case_id" =>
                    $demand["source_confirmation_case_id"],
                "source_confirmation_case_digest" =>
                    $demand["source_confirmation_case_digest"],
                "source_confirmation_request_id" =>
                    $demand["source_confirmation_request_id"],
                "source_confirmation_request_digest" =>
                    $demand["source_confirmation_request_digest"],
                "persona_candidate_id" => $demand["persona_candidate_id"],
                "persona_candidate_digest" =>
                    $demand["persona_candidate_digest"],
                "authorized_review_target" =>
                    $demand["authorized_review_target"] ?? null,
                "review_target_lineage" =>
                    $demand["review_target_lineage"] ?? null,
                "profile_source" => $seat["profile_source"],
                "profile_source_digest" => $seat["profile_source_digest"],
                "seat_contract_source" => $seat["seat_contract_source"],
                "seat_contract_source_digest" =>
                    $seat["seat_contract_source_digest"],
                "persona_state" => "CONSTRUCTION_REQUIRED",
                "profile_state" => "SOURCE_CONTRACT_ONLY",
                "status" =>
                    "BLOCKED_PENDING_EXPLICIT_CONSTRUCTION_AUTHORIZATION",
                "construction_authority" => false,
                "construction_authority_exercisable" => false,
                "persona_approval_authority" => false,
                "profile_approval_authority" => false,
                "spawning_authority" => false,
                "seat_binding_authority" => false,
                "confirmation_acceptance_authority" => false,
                "senate_disposition_authority" => false,
                "assembly_request_authority" => false,
                "witness_instantiation_authority" => false,
                "senate_finding_authority" => false,
                "admission_authority" => false,
                "execution_authority" => false,
                "sealed" => true,
            ]);
            $seen[$seat["seat"]] = true;
        }
        return [
            "activation_demand_id" => $demandId,
            "activation_demand_digest" => $demand["record_digest"],
            "confirmation_case_id" => $demand["source_confirmation_case_id"],
            "cases" => $cases,
        ];
    }

    private function validateSeatSources(array $seat): void
    {
        foreach (
            [
                ["profile_source", "profile_source_digest"],
                ["seat_contract_source", "seat_contract_source_digest"],
            ]
            as [$pathKey, $digestKey]
        ) {
            $relative = $seat[$pathKey] ?? null;
            $digest = $seat[$digestKey] ?? null;
            if (
                !is_string($relative) ||
                !str_starts_with($relative, "offices/senate/") ||
                !is_string($digest)
            ) {
                throw new \RuntimeException(
                    "M135_SENATE_SOURCE_CONTRACT_INVALID",
                );
            }
            $path = $this->projectDir . "/" . $relative;
            if (
                !is_file($path) ||
                !hash_equals(
                    "sha256:" .
                        hash("sha256", (string) file_get_contents($path)),
                    $digest,
                )
            ) {
                throw new \RuntimeException(
                    "M135_SENATE_SOURCE_CONTRACT_INVALID",
                );
            }
        }
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "construction_authority",
                "spawning_authority",
                "seat_binding_authority",
                "confirmation_acceptance_authority",
                "senate_disposition_authority",
                "assembly_request_authority",
                "witness_instantiation_authority",
                "senate_finding_authority",
                "admission_authority",
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
            !is_dir($this->cases) &&
            !mkdir($this->cases, 0770, true) &&
            !is_dir($this->cases)
        ) {
            throw new \RuntimeException(
                "MasterMason activation-case directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->cases . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "M136_SENATE_RESIDENT_CASE_ABSENT");
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "M137_SENATE_RESIDENT_CASE_REPLAY_CONFLICT",
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
                "Senate resident provisioning case cannot be committed atomically.",
            );
        }
        return $record;
    }
}
