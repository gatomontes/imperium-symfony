<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PersonaConfirmationCaseIntakeService
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
                '/^adversarial-reviewer-bootstrap-seed-confirmation-request-[a-f0-9]{20}$/',
                $requestId,
            )
        ) {
            throw new \InvalidArgumentException(
                "S100_PERSONA_CONFIRMATION_REQUEST_ID_INVALID",
            );
        }
        $request = $this->read(
            $this->inbox . "/" . $requestId . ".json",
            "S101_PERSONA_CONFIRMATION_REQUEST_ABSENT",
        );
        $contract = $request["examination_contract"] ?? null;
        if (
            !$this->digestMatches($request) ||
            !is_array($contract) ||
            "imperium.senate-persona-confirmation-request/v1" !==
                ($request["schema"] ?? null) ||
            $requestId !== ($request["confirmation_request_id"] ?? null) ||
            "PENDING_ADMISSION_PERSONA_QUALIFICATION" !==
                ($request["proceeding_class"] ?? null) ||
            "foundry.artificer" !== ($request["requester"]["seat"] ?? null) ||
            "senate" !== ($request["recipient"]["office"] ?? null) ||
            "senate.lord-speaker" !== ($request["recipient"]["seat"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($request["persona_id"] ?? null) ||
            "1.0.0" !== ($request["persona_version"] ?? null) ||
            "OPEN_EXACT_MANIFESTATION_BOUND_CONFIRMATION_CASE" !==
                ($request["requested_disposition"] ?? null) ||
            "DELIVERED_PENDING_SENATE_ACCEPTANCE" !==
                ($request["status"] ?? null) ||
            null !== ($request["recipient_acceptance"] ?? null) ||
            null !== ($request["senate_finding"] ?? null) ||
            true !== ($request["sealed"] ?? null) ||
            !$this->validContract($contract) ||
            $this->hasAuthority($request)
        ) {
            throw new \RuntimeException(
                "S102_PERSONA_CONFIRMATION_REQUEST_INVALID",
            );
        }

        $lordSpeaker = $this->occupant(
            "senate-lord-speaker-binding-*.json",
            "senate.lord-speaker",
            $request["instance_id"] ?? null,
        );
        $bailiff = $this->occupant(
            "senate-bailiff-binding-*.json",
            "senate.bailiff",
            $request["instance_id"] ?? null,
        );
        $activationRequired = [];
        if (null === $lordSpeaker) {
            $activationRequired[] = "senate.lord-speaker";
        }
        if (null === $bailiff) {
            $activationRequired[] = "senate.bailiff";
        }

        $id =
            "senate-confirmation-case-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $requestId,
                        $request["record_digest"],
                        $request["persona_candidate_id"],
                        $request["persona_candidate_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.senate-persona-confirmation-case/v1",
            "confirmation_case_id" => $id,
            "instance_id" => $request["instance_id"],
            "source_request_id" => $requestId,
            "source_request_digest" => $request["record_digest"],
            "source_admission_return_id" =>
                $request["source_admission_return_id"],
            "source_admission_return_digest" =>
                $request["source_admission_return_digest"],
            "production_approval_id" => $request["production_approval_id"],
            "production_approval_digest" =>
                $request["production_approval_digest"],
            "authorization_act_id" => $request["authorization_act_id"],
            "authorization_act_digest" => $request["authorization_act_digest"],
            "persona_candidate_id" => $request["persona_candidate_id"],
            "persona_candidate_digest" => $request["persona_candidate_digest"],
            "persona_id" => $request["persona_id"],
            "persona_version" => $request["persona_version"],
            "persona" => $request["persona"],
            "design_basis" => $request["design_basis"],
            "bootstrap_seed_boundary" => $request["bootstrap_seed_boundary"],
            "proceeding_class" => $request["proceeding_class"],
            "examination_contract" => $contract,
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

    private function validContract(array $contract): bool
    {
        return "production-approved-pending-admission" ===
            ($contract["subject_state"] ?? null) &&
            true === ($contract["manifestation_required"] ?? null) &&
            "examination_only" === ($contract["profile_class"] ?? null) &&
            true === ($contract["sterile_witness_required"] ?? null) &&
            true === ($contract["exact_candidate_only"] ?? null) &&
            true ===
                ($contract["independent_senate_disposition_required"] ??
                    null) &&
            true === ($contract["self_review_prohibited"] ?? null) &&
            true === ($contract["ordinary_operational_use_prohibited"] ?? null);
    }
    private function occupant(
        string $pattern,
        string $seat,
        mixed $instanceId,
    ): ?array {
        $paths = glob($this->occupancy . "/" . $pattern) ?: [];
        if ([] === $paths) {
            return null;
        }
        if (1 !== count($paths)) {
            throw new \RuntimeException("S103_SENATE_OCCUPANCY_AMBIGUOUS");
        }
        $record = $this->read($paths[0], "S104_SENATE_OCCUPANCY_INVALID");
        if (
            !$this->digestMatches($record) ||
            $seat !== ($record["seat"] ?? null) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null)
        ) {
            throw new \RuntimeException("S104_SENATE_OCCUPANCY_INVALID");
        }
        return [
            "binding_id" => $record["binding_id"],
            "binding_digest" => $record["record_digest"],
            "manifestation_id" => $record["manifestation_id"],
            "occupancy_generation" => $record["occupancy_generation"],
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
                "candidate_approval_authority",
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
                "Senate confirmation-case directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->cases . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S105_CONFIRMATION_CASE_ABSENT");
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "S106_CONFIRMATION_CASE_REPLAY_CONFLICT",
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
                "Senate confirmation case cannot be committed atomically.",
            );
        }
        return $record;
    }
}
