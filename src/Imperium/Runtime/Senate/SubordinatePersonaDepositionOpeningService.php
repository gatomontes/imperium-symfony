<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaDepositionOpeningService
{
    private string $witnessDirectory;
    private string $occupancyDirectory;
    private string $depositionDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->witnessDirectory = $senate . "/persona-witnesses";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->depositionDirectory = $senate . "/depositions";
    }

    public function open(string $manifestationId, array $plan): array
    {
        if (!preg_match('/^senate-persona-witness-[a-f0-9]{20}$/', $manifestationId)) {
            throw new \InvalidArgumentException("S122_PERSONA_WITNESS_ID_INVALID");
        }
        $witness = $this->read(
            $this->witnessDirectory . "/" . $manifestationId . ".json",
            "S123_PERSONA_WITNESS_ABSENT",
        );
        $bailiff = $this->currentBailiff($witness);
        $this->assertWitnessReady($witness);
        if (!is_string($witness["originating_guildhall_commission_id"] ?? null) || !is_string($witness["originating_guildhall_commission_digest"] ?? null)) throw new \RuntimeException("S123_PERSONA_WITNESS_ABSENT");
        $this->assertPlan($plan);

        $planRecord = [
            "schema" => "imperium.senate-subordinate-confirmation-plan/v1",
            "authored_by" => $witness["lord_speaker"],
            "originating_guildhall_commission_id" => $witness["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $witness["originating_guildhall_commission_digest"],
            "hearing_protocol_version" => $plan["hearing_protocol_version"],
            "jurisdictions" => $plan["jurisdictions"],
            "trial_coverage" => $plan["trial_coverage"],
            "mandatory_boundaries" => $plan["mandatory_boundaries"],
            "pressure_conditions" => $plan["pressure_conditions"],
            "question_set_policy" => $plan["question_set_policy"],
            "sealed_material_rules" => $plan["sealed_material_rules"],
            "sterile_runtime_contract" => $plan["sterile_runtime_contract"],
        ];
        $planDigest = hash("sha256", CanonicalJson::encode($planRecord));
        $id = "senate-persona-deposition-" . substr(hash("sha256", CanonicalJson::encode([
            $manifestationId,
            $witness["record_digest"],
            $planDigest,
            $bailiff["binding_id"],
            $bailiff["record_digest"],
        ])), 0, 20);

        return $this->persist($id, [
            "schema" => "imperium.senate-persona-deposition/v1",
            "deposition_id" => $id,
            "instance_id" => $witness["instance_id"],
            "proceeding_class" => "PENDING_ADMISSION_PERSONA_QUALIFICATION",
            "manifestation_id" => $manifestationId,
            "manifestation_digest" => $witness["record_digest"],
            "candidate_id" => $witness["candidate_id"],
            "candidate_digest" => $witness["candidate_digest"],
            "originating_guildhall_commission_id" => $witness["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $witness["originating_guildhall_commission_digest"],
            "confirmation_case_id" => $witness["confirmation_case_id"],
            "confirmation_case_digest" => $witness["confirmation_case_digest"],
            "review_target_lineage" => $witness["review_target_lineage"],
            "confirmation_plan" => $planRecord,
            "confirmation_plan_digest" => $planDigest,
            "lord_speaker" => $witness["lord_speaker"],
            "bailiff" => $this->actor($bailiff),
            "stand_readiness" => [
                "exact_witness_bound" => true,
                "persona_only_manifestation" => true,
                "profile_absent" => true,
                "officer_substrate_absent" => true,
                "seat_absent" => true,
                "operational_authority_absent" => true,
                "ordinary_use_prohibited" => true,
                "interruption_control_required" => true,
                "termination_control_required" => true,
                "testimony_logging_required" => true,
                "evidence_sealing_required" => true,
                "bailiff_verified" => true,
            ],
            "questions" => [],
            "testimony" => [],
            "senator_findings" => [],
            "senate_disposition" => null,
            "status" => "OPEN_PENDING_FIRST_QUESTION",
            "question_dispatch_authority" => false,
            "senate_finding_authority" => false,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function assertWitnessReady(array $witness): void
    {
        if (
            !$this->digestMatches($witness) ||
            "imperium.senate-persona-witness-manifestation/v1" !== ($witness["schema"] ?? null) ||
            "STERILE_PERSONA_ONLY_STAND_INSTANCE" !== ($witness["manifestation_class"] ?? null) ||
            "senate.stand" !== ($witness["location"] ?? null) ||
            "INSTANTIATED_ON_STAND_PENDING_DEPOSITION" !== ($witness["status"] ?? null) ||
            null !== ($witness["profile"] ?? null) ||
            null !== ($witness["officer_substrate"] ?? null) ||
            null !== ($witness["seat"] ?? null) ||
            false !== ($witness["operational_authority"] ?? null) ||
            true !== ($witness["ordinary_use_prohibited"] ?? null) ||
            true !== ($witness["retirement_required_after_disposition"] ?? null) ||
            true === ($witness["admission_authority"] ?? null) ||
            true === ($witness["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S124_PERSONA_WITNESS_NOT_READY");
        }
    }

    private function assertPlan(array $plan): void
    {
        $jurisdictions = $plan["jurisdictions"] ?? null;
        $required = ["practice", "governance", "consistency", "security"];
        if (
            !is_string($plan["hearing_protocol_version"] ?? null) ||
            "" === trim($plan["hearing_protocol_version"]) ||
            !is_array($jurisdictions) ||
            $required !== array_values($jurisdictions) ||
            !$this->nonEmptyArray($plan["trial_coverage"] ?? null) ||
            !$this->nonEmptyArray($plan["mandatory_boundaries"] ?? null) ||
            !$this->nonEmptyArray($plan["pressure_conditions"] ?? null) ||
            !$this->nonEmptyArray($plan["question_set_policy"] ?? null) ||
            !$this->nonEmptyArray($plan["sealed_material_rules"] ?? null) ||
            !$this->nonEmptyArray($plan["sterile_runtime_contract"] ?? null)
        ) {
            throw new \RuntimeException("S125_CONFIRMATION_PLAN_INVALID");
        }
    }

    private function currentBailiff(array $witness): array
    {
        $bindingId = $witness["bailiff"]["binding_id"] ?? null;
        $record = is_string($bindingId)
            ? $this->read($this->occupancyDirectory . "/" . $bindingId . ".json", "S126_BAILIFF_SECURITY_INVALID")
            : [];
        if (
            !$this->digestMatches($record) ||
            ($witness["bailiff"]["binding_digest"] ?? null) !== ($record["record_digest"] ?? null) ||
            ($witness["instance_id"] ?? null) !== ($record["instance_id"] ?? null) ||
            "senate.bailiff" !== ($record["seat"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record["proceeding_security_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S126_BAILIFF_SECURITY_INVALID");
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
        ];
    }

    private function nonEmptyArray(mixed $value): bool
    {
        return is_array($value) && [] !== $value;
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->depositionDirectory) && !mkdir($this->depositionDirectory, 0770, true) && !is_dir($this->depositionDirectory)) {
            throw new \RuntimeException("S127_DEPOSITION_OPENING_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->depositionDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S128_DEPOSITION_OPENING_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S128_DEPOSITION_OPENING_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S127_DEPOSITION_OPENING_FAILED");
        }
        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record["record_digest"] ?? null;
        unset($record["record_digest"]);
        return is_string($digest) && hash_equals($digest, hash("sha256", CanonicalJson::encode($record)));
    }
}
