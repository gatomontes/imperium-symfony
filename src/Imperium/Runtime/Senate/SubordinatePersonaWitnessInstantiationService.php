<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaWitnessInstantiationService
{
    private string $acceptanceDirectory;
    private string $caseDirectory;
    private string $witnessDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->acceptanceDirectory = $senate . "/confirmation-case-acceptances";
        $this->caseDirectory = $senate . "/confirmation-cases";
        $this->witnessDirectory = $senate . "/persona-witnesses";
    }

    public function instantiate(string $acceptanceId): array
    {
        if (!preg_match('/^senate-subordinate-confirmation-acceptance-[a-f0-9]{20}$/', $acceptanceId)) {
            throw new \InvalidArgumentException("S118_SUBORDINATE_CONFIRMATION_ACCEPTANCE_ID_INVALID");
        }
        $acceptance = $this->read(
            $this->acceptanceDirectory . "/" . $acceptanceId . ".json",
            "S119_PERSONA_WITNESS_INSTANTIATION_CHAIN_INVALID",
        );
        $caseId = $acceptance["confirmation_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read($this->caseDirectory . "/" . $caseId . ".json", "S119_PERSONA_WITNESS_INSTANTIATION_CHAIN_INVALID")
            : [];
        if (
            !$this->digestMatches($acceptance) ||
            !$this->digestMatches($case) ||
            !is_string($case["originating_guildhall_commission_id"] ?? null) ||
            ($acceptance["originating_guildhall_commission_id"] ?? null) !== ($case["originating_guildhall_commission_id"] ?? null) ||
            ($acceptance["originating_guildhall_commission_digest"] ?? null) !== ($case["originating_guildhall_commission_digest"] ?? null) ||
            "imperium.senate-subordinate-persona-confirmation-acceptance/v1" !== ($acceptance["schema"] ?? null) ||
            "ACCEPTED_FOR_EXACT_PERSONA_WITNESS_INSTANTIATION" !== ($acceptance["disposition"] ?? null) ||
            "ACCEPTED_PENDING_PERSONA_WITNESS_INSTANTIATION" !== ($acceptance["status"] ?? null) ||
            true !== ($acceptance["recipient_acceptance"] ?? null) ||
            true !== ($acceptance["witness_instantiation_authority"] ?? null) ||
            true === ($acceptance["assembly_request_authority_exercisable"] ?? null) ||
            ($acceptance["confirmation_case_digest"] ?? null) !== ($case["record_digest"] ?? null) ||
            ($acceptance["candidate_digest"] ?? null) !== ($case["candidate_digest"] ?? null) ||
            ($acceptance["candidate_id"] ?? null) !== ($case["candidate_id"] ?? null) ||
            !is_array($case["persona"] ?? null) ||
            true !== ($acceptance["examination_contract"]["sterile_witness_required"] ?? null) ||
            true === ($acceptance["admission_authority"] ?? null) ||
            true === ($acceptance["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S119_PERSONA_WITNESS_INSTANTIATION_CHAIN_INVALID");
        }

        $id = "senate-persona-witness-" . substr(hash("sha256", CanonicalJson::encode([
            $acceptanceId,
            $acceptance["record_digest"],
            $caseId,
            $case["record_digest"],
            $case["candidate_digest"],
            "STERILE_PERSONA_ONLY_STAND_INSTANCE",
        ])), 0, 20);

        return $this->persist($id, [
            "schema" => "imperium.senate-persona-witness-manifestation/v1",
            "manifestation_id" => $id,
            "instance_id" => $acceptance["instance_id"],
            "confirmation_acceptance_id" => $acceptanceId,
            "confirmation_acceptance_digest" => $acceptance["record_digest"],
            "confirmation_case_id" => $caseId,
            "confirmation_case_digest" => $case["record_digest"],
            "candidate_id" => $case["candidate_id"],
            "candidate_digest" => $case["candidate_digest"],
            "originating_guildhall_commission_id" => $case["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $case["originating_guildhall_commission_digest"],
            "persona_name" => $case["persona_name"],
            "persona_specification_version" => $case["persona_specification_version"],
            "persona" => $case["persona"],
            "review_target_lineage" => $case["review_target_lineage"],
            "lord_speaker" => $acceptance["lord_speaker"],
            "bailiff" => $acceptance["bailiff"],
            "manifestation_class" => "STERILE_PERSONA_ONLY_STAND_INSTANCE",
            "location" => "senate.stand",
            "profile" => null,
            "officer_substrate" => null,
            "seat" => null,
            "operational_authority" => false,
            "ordinary_use_prohibited" => true,
            "retirement_required_after_disposition" => true,
            "status" => "INSTANTIATED_ON_STAND_PENDING_DEPOSITION",
            "senate_finding_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->witnessDirectory) && !mkdir($this->witnessDirectory, 0770, true) && !is_dir($this->witnessDirectory)) {
            throw new \RuntimeException("S120_PERSONA_WITNESS_INSTANTIATION_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->witnessDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S121_PERSONA_WITNESS_INSTANTIATION_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S121_PERSONA_WITNESS_INSTANTIATION_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S120_PERSONA_WITNESS_INSTANTIATION_FAILED");
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
