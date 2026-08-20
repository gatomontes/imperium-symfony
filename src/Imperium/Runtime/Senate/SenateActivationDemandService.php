<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenateActivationDemandService
{
    private string $cases;
    private string $requests;
    private string $profiles;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->cases =
            $projectDir . "/var/imperium/offices/senate/confirmation-cases";
        $this->requests =
            $projectDir . "/var/imperium/mastermason/spawning-requests";
        $this->profiles = $projectDir . "/offices/senate";
    }

    public function demand(string $caseId): array
    {
        if (!preg_match('/^senate-confirmation-case-[a-f0-9]{20}$/', $caseId)) {
            throw new \InvalidArgumentException(
                "S107_CONFIRMATION_CASE_ID_INVALID",
            );
        }
        $case = $this->read(
            $this->cases . "/" . $caseId . ".json",
            "S108_CONFIRMATION_CASE_ABSENT",
        );
        if (
            !$this->digestMatches($case) ||
            "imperium.senate-persona-confirmation-case/v1" !==
                ($case["schema"] ?? null) ||
            $caseId !== ($case["confirmation_case_id"] ?? null) ||
            "PENDING_ADMISSION_PERSONA_QUALIFICATION" !==
                ($case["proceeding_class"] ?? null) ||
            "BLOCKED_PENDING_SENATE_OCCUPANCY" !== ($case["status"] ?? null) ||
            true !== ($case["request_preserved"] ?? null) ||
            null !== ($case["lord_speaker_occupancy"] ?? null) ||
            null !== ($case["bailiff_occupancy"] ?? null) ||
            ["senate.lord-speaker", "senate.bailiff"] !==
                ($case["activation_required"] ?? null) ||
            true !== ($case["sealed"] ?? null) ||
            $this->hasAuthority($case)
        ) {
            throw new \RuntimeException(
                "S109_CONFIRMATION_CASE_NOT_ACTIVATION_BLOCKED",
            );
        }

        $requiredSeats = [
            $this->seat(
                "senate.lord-speaker",
                "profile-lord-speaker.md",
                "seat-resident-lord-speaker.md",
            ),
            $this->seat(
                "senate.bailiff",
                "profile-bailiff.md",
                "seat-resident-bailiff.md",
            ),
        ];
        $id =
            "senate-activation-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $caseId,
                        $case["record_digest"],
                        $requiredSeats,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.senate-activation-demand/v1",
            "demand_id" => $id,
            "instance_id" => $case["instance_id"],
            "requester" => [
                "kind" => "senate-mechanics",
                "basis" => "preserved-confirmation-case-vacancy",
            ],
            "recipient" => [
                "kind" => "bootstrap-coordinator",
                "id" => "mastermason",
            ],
            "source_confirmation_case_id" => $caseId,
            "source_confirmation_case_digest" => $case["record_digest"],
            "source_confirmation_request_id" => $case["source_request_id"],
            "source_confirmation_request_digest" =>
                $case["source_request_digest"],
            "persona_candidate_id" => $case["persona_candidate_id"],
            "persona_candidate_digest" => $case["persona_candidate_digest"],
            "authorized_review_target" =>
                $case["authorized_review_target"] ?? null,
            "review_target_lineage" => $case["review_target_lineage"] ?? null,
            "required_seats" => $requiredSeats,
            "activation_reason" =>
                "an exact preserved confirmation case cannot proceed while the resident Lord Speaker and Bailiff Seats are vacant",
            "status" => "SENATE_RESIDENT_STAFF_CONSTRUCTION_REQUIRED",
            "occupancy_claimed" => false,
            "construction_authority" => false,
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
    }

    private function seat(
        string $seat,
        string $profile,
        string $seatContract,
    ): array {
        $profilePath = $this->profiles . "/" . $profile;
        $seatPath = $this->profiles . "/" . $seatContract;
        if (!is_file($profilePath) || !is_file($seatPath)) {
            throw new \RuntimeException("S110_SENATE_STAFF_CONTRACT_ABSENT");
        }
        return [
            "seat" => $seat,
            "persona_state" => "CONSTRUCTION_REQUIRED",
            "profile_state" => "SOURCE_CONTRACT_ONLY",
            "profile_source" => "offices/senate/" . $profile,
            "profile_source_digest" =>
                "sha256:" .
                hash("sha256", (string) file_get_contents($profilePath)),
            "seat_contract_source" => "offices/senate/" . $seatContract,
            "seat_contract_source_digest" =>
                "sha256:" .
                hash("sha256", (string) file_get_contents($seatPath)),
            "canonical_staff_package" => null,
            "occupancy" => null,
        ];
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "assembly_request_authority",
                "witness_instantiation_authority",
                "confirmation_acceptance_authority",
                "senate_disposition_authority",
                "senate_finding_authority",
                "admission_authority",
                "profile_approval_authority",
                "spawning_authority",
                "seat_binding_authority",
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
            !is_dir($this->requests) &&
            !mkdir($this->requests, 0770, true) &&
            !is_dir($this->requests)
        ) {
            throw new \RuntimeException(
                "MasterMason spawning-request directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->requests . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "S111_SENATE_ACTIVATION_DEMAND_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "S112_SENATE_ACTIVATION_DEMAND_REPLAY_CONFLICT",
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
                "Senate activation demand cannot be committed atomically.",
            );
        }
        return $record;
    }
}
