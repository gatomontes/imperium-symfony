<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaConfirmationCaseIntakeService
{
    private string $inbox;
    private string $occupancy;
    private string $cases;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->inbox =
            $projectDir .
            "/var/imperium/offices/senate/inbox/persona-confirmation-requests";
        $this->occupancy =
            $projectDir . "/var/imperium/offices/senate/occupancy";
        $this->cases =
            $projectDir . "/var/imperium/offices/senate/confirmation-cases";
    }

    public function preserve(string $requestId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-confirmation-request-[a-f0-9]{20}$/',
                $requestId,
            )
        ) {
            throw new \InvalidArgumentException(
                "S107_SUBORDINATE_CONFIRMATION_REQUEST_ID_INVALID",
            );
        }
        $request = $this->read(
            $this->inbox . "/" . $requestId . ".json",
            "S108_SUBORDINATE_CONFIRMATION_REQUEST_ABSENT",
        );
        if (
            !$this->digestMatches($request) ||
            !is_string($request["originating_guildhall_commission_id"] ?? null) ||
            !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $request["originating_guildhall_commission_id"]) ||
            !is_string($request["originating_guildhall_commission_digest"] ?? null) ||
            "imperium.senate-persona-confirmation-request/v1" !==
                ($request["schema"] ?? null) ||
            "PENDING_ADMISSION_PERSONA_QUALIFICATION" !==
                ($request["proceeding_class"] ?? null) ||
            "foundry.artificer" !== ($request["requester"]["seat"] ?? null) ||
            "senate.lord-speaker" !== ($request["recipient"]["seat"] ?? null) ||
            "OPEN_EXACT_MANIFESTATION_BOUND_CONFIRMATION_CASE" !==
                ($request["requested_disposition"] ?? null) ||
            "DELIVERED_PENDING_SENATE_ACCEPTANCE" !==
                ($request["status"] ?? null) ||
            null !== ($request["recipient_acceptance"] ?? null) ||
            null !== ($request["senate_finding"] ?? null) ||
            true !== ($request["sealed"] ?? null) ||
            !in_array(
                $request["route_class"] ?? null,
                [
                    "CANONICAL_FOUNDRY_TO_SENATE",
                    "RECOVERY_AFTER_PREMATURE_GARRISON_DELIVERY",
                ],
                true,
            ) ||
            !$this->validContract($request["examination_contract"] ?? null) ||
            !$this->routeMatchesContract(
                $request["route_class"] ?? null,
                $request["examination_contract"]["subject_state"] ?? null,
            ) ||
            $this->hasAuthority($request)
        ) {
            throw new \RuntimeException(
                "S109_SUBORDINATE_CONFIRMATION_REQUEST_INVALID",
            );
        }

        $lordSpeaker = $this->occupant(
            "senate.lord-speaker",
            $request["instance_id"],
            "confirmation_acceptance_authority",
        );
        $bailiff = $this->occupant(
            "senate.bailiff",
            $request["instance_id"],
            "proceeding_security_authority",
        );
        $activationRequired = [];
        if (null === $lordSpeaker) {
            $activationRequired[] = "senate.lord-speaker";
        }
        if (null === $bailiff) {
            $activationRequired[] = "senate.bailiff";
        }

        $id =
            "senate-subordinate-confirmation-case-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $requestId,
                        $request["record_digest"],
                        $request["candidate_id"],
                        $request["candidate_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.senate-subordinate-persona-confirmation-case/v1",
            "confirmation_case_id" => $id,
            "instance_id" => $request["instance_id"],
            "route_class" => $request["route_class"],
            "source_request_id" => $requestId,
            "source_request_digest" => $request["record_digest"],
            "source_admission_return_id" =>
                $request["source_admission_return_id"],
            "source_admission_return_digest" =>
                $request["source_admission_return_digest"],
            "production_approval_id" => $request["production_approval_id"],
            "production_approval_digest" =>
                $request["production_approval_digest"],
            "candidate_id" => $request["candidate_id"],
            "candidate_digest" => $request["candidate_digest"],
            "originating_guildhall_commission_id" => $request["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $request["originating_guildhall_commission_digest"],
            "persona_name" => $request["persona_name"],
            "persona_specification_version" =>
                $request["persona_specification_version"],
            "persona" => $request["persona"],
            "review_target_lineage" => $request["review_target_lineage"],
            "proceeding_class" => $request["proceeding_class"],
            "examination_contract" => $request["examination_contract"],
            "lord_speaker_occupancy" => $lordSpeaker,
            "bailiff_occupancy" => $bailiff,
            "activation_required" => $activationRequired,
            "status" =>
                [] === $activationRequired
                    ? "PENDING_LORD_SPEAKER_ACCEPTANCE"
                    : "BLOCKED_PENDING_SENATE_OCCUPANCY",
            "request_preserved" => true,
            "recipient_acceptance" => null,
            "confirmation_plan" => null,
            "assembly_request_authority" => false,
            "witness_instantiation_authority" => false,
            "senate_finding" => null,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function occupant(
        string $seat,
        string $instanceId,
        string $authority,
    ): ?array {
        $matches = [];
        foreach (glob($this->occupancy . "/*.json") ?: [] as $path) {
            $record = $this->read($path, "S110_SENATE_OCCUPANCY_INVALID");
            if ($seat === ($record["seat"] ?? null)) {
                $matches[] = $record;
            }
        }
        if ([] === $matches) {
            return null;
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException("S110_SENATE_OCCUPANCY_INVALID");
        }
        $record = $matches[0];
        if (
            !$this->digestMatches($record) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record[$authority] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S110_SENATE_OCCUPANCY_INVALID");
        }
        return [
            "binding_id" => $record["binding_id"],
            "binding_digest" => $record["record_digest"],
            "manifestation_id" => $record["manifestation_id"],
            "occupancy_generation" => $record["occupancy_generation"],
            "provenance" => $record["provenance"] ?? "GOVERNED_OCCUPANCY",
        ];
    }

    private function validContract(mixed $contract): bool
    {
        return is_array($contract) &&
            in_array(
                $contract["subject_state"] ?? null,
                [
                    "production-approved-pending-senate-approval",
                    "production-approved-pending-admission",
                ],
                true,
            ) &&
            true === ($contract["manifestation_required"] ?? null) &&
            false === ($contract["profile_required"] ?? null) &&
            null === ($contract["profile_class"] ?? null) &&
            false === ($contract["officer_substrate_required"] ?? null) &&
            true === ($contract["senate_local_instantiation"] ?? null) &&
            true === ($contract["sterile_witness_required"] ?? null) &&
            true === ($contract["exact_candidate_only"] ?? null) &&
            true ===
                ($contract["independent_senate_disposition_required"] ??
                    null) &&
            true === ($contract["self_review_prohibited"] ?? null) &&
            true === ($contract["ordinary_operational_use_prohibited"] ?? null);
    }

    private function routeMatchesContract(mixed $route, mixed $subject): bool
    {
        return match ($route) {
            "CANONICAL_FOUNDRY_TO_SENATE"
                => "production-approved-pending-senate-approval" === $subject,
            "RECOVERY_AFTER_PREMATURE_GARRISON_DELIVERY"
                => "production-approved-pending-admission" === $subject,
            default => false,
        };
    }

    private function hasAuthority(array $record): bool
    {
        foreach (
            [
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

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->cases) &&
            !mkdir($this->cases, 0770, true) &&
            !is_dir($this->cases)
        ) {
            throw new \RuntimeException(
                "S111_SUBORDINATE_CONFIRMATION_CASE_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->cases . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "S112_SUBORDINATE_CONFIRMATION_CASE_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "S112_SUBORDINATE_CONFIRMATION_CASE_CONFLICT",
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
                "S111_SUBORDINATE_CONFIRMATION_CASE_FAILED",
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
