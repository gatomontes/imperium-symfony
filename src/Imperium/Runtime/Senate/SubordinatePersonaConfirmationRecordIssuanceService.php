<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaConfirmationRecordIssuanceService
{
    private string $senate;
    private string $requestDirectory;
    private string $recordDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->senate = $projectDir . "/var/imperium/offices/senate";
        $this->requestDirectory = $this->senate . "/inbox/persona-confirmation-requests";
        $this->recordDirectory = $this->senate . "/outbox/confirmation-records";
    }

    public function issue(string $retirementSetId): array
    {
        if (!preg_match('/^senate-witness-retirement-set-[a-f0-9]{20}$/', $retirementSetId)) {
            throw new \InvalidArgumentException("S196_WITNESS_RETIREMENT_SET_ID_INVALID");
        }
        foreach (glob($this->recordDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S203_CONFIRMATION_RECORD_CONFLICT");
            if ($retirementSetId === ($existing["retirement_set_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $retirement = $this->readRecord("witness-retirement-sets", $retirementSetId, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $disposition = $this->readRecord("dispositions", $retirement["disposition_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $findingSet = $this->readRecord("senator-finding-sets", $disposition["finding_set_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $ledger = $this->readRecord("required-trial-ledgers", $findingSet["required_trial_ledger_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $baseline = $this->readRecord("jurisdiction-baselines", $ledger["baseline_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $consistency = $this->readRecord("fresh-consistency-trials", $ledger["fresh_consistency_trial_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $deposition = $this->readRecord("depositions", $baseline["deposition_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $witness = $this->readRecord("persona-witnesses", $deposition["manifestation_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $acceptance = $this->readRecord("confirmation-case-acceptances", $witness["confirmation_acceptance_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $case = $this->readRecord("confirmation-cases", $acceptance["confirmation_case_id"] ?? null, "S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        $requestId = $case["source_request_id"] ?? null;
        $request = is_string($requestId)
            ? $this->read($this->requestDirectory . "/" . $requestId . ".json", "S197_CONFIRMATION_RECORD_CHAIN_INVALID")
            : [];
        $records = [$retirement, $disposition, $findingSet, $ledger, $baseline, $consistency, $deposition, $witness, $acceptance, $case, $request];
        foreach ($records as $record) {
            if (!$this->digestMatches($record)) {
                throw new \RuntimeException("S197_CONFIRMATION_RECORD_CHAIN_INVALID");
            }
        }
        if (
            $retirementSetId !== ($retirement["retirement_set_id"] ?? null) ||
            "ALL_WITNESSES_RETIRED_PENDING_CONFIRMATION_RECORD_ISSUANCE" !== ($retirement["status"] ?? null) ||
            true !== ($retirement["all_witnesses_accounted_for"] ?? null) ||
            true !== ($retirement["all_witnesses_retired"] ?? null) ||
            true !== ($retirement["evidentiary_artifacts_preserved"] ?? null) ||
            4 !== ($retirement["retired_witness_count"] ?? null) ||
            ($retirement["disposition_digest"] ?? null) !== ($disposition["record_digest"] ?? null) ||
            ($disposition["finding_set_digest"] ?? null) !== ($findingSet["record_digest"] ?? null) ||
            ($findingSet["required_trial_ledger_digest"] ?? null) !== ($ledger["record_digest"] ?? null) ||
            ($ledger["baseline_digest"] ?? null) !== ($baseline["record_digest"] ?? null) ||
            ($ledger["fresh_consistency_trial_digest"] ?? null) !== ($consistency["record_digest"] ?? null) ||
            ($baseline["deposition_digest"] ?? null) !== ($deposition["record_digest"] ?? null) ||
            ($deposition["manifestation_digest"] ?? null) !== ($witness["record_digest"] ?? null) ||
            ($witness["confirmation_acceptance_digest"] ?? null) !== ($acceptance["record_digest"] ?? null) ||
            ($acceptance["confirmation_case_digest"] ?? null) !== ($case["record_digest"] ?? null) ||
            ($case["source_request_digest"] ?? null) !== ($request["record_digest"] ?? null)
        ) {
            throw new \RuntimeException("S197_CONFIRMATION_RECORD_CHAIN_INVALID");
        }
        $candidateDigests = array_map(
            static fn (array $record): mixed => $record["candidate_digest"] ?? $record["persona_candidate_digest"] ?? null,
            $records,
        );
        $candidateDigests = array_values(array_filter($candidateDigests, static fn (mixed $digest): bool => null !== $digest));
        if ([] === $candidateDigests || 1 !== count(array_unique($candidateDigests))) {
            throw new \RuntimeException("S198_CONFIRMATION_RECORD_CANDIDATE_MISMATCH");
        }
        $allGuildhallCommissionIds = array_column($records, "originating_guildhall_commission_id");
        $allGuildhallCommissionDigests = array_column($records, "originating_guildhall_commission_digest");
        $guildhallCommissionIds = array_values(array_unique($allGuildhallCommissionIds));
        $guildhallCommissionDigests = array_values(array_unique($allGuildhallCommissionDigests));
        if (count($records) !== count($allGuildhallCommissionIds) || count($records) !== count($allGuildhallCommissionDigests) || 1 !== count($guildhallCommissionIds) || 1 !== count($guildhallCommissionDigests) || !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', (string) $guildhallCommissionIds[0])) {
            throw new \RuntimeException("S198_CONFIRMATION_RECORD_GUILDHALL_PROVENANCE_MISMATCH");
        }
        $findingDigests = array_column($findingSet["findings"] ?? [], "finding_digest");
        $dispositionReferences = $disposition["decision"]["finding_references"] ?? null;
        if (
            4 !== count($findingDigests) ||
            $findingDigests !== $dispositionReferences ||
            4 !== count($retirement["retirement_events"] ?? []) ||
            false === ($disposition["disagreement_preserved"] ?? null)
        ) {
            throw new \RuntimeException("S199_CONFIRMATION_RECORD_INCOMPLETE");
        }
        $decision = $disposition["decision"]["disposition"] ?? null;
        $routing = match ($decision) {
            "CONFIRMED" => "CONFIRMED_CANDIDATE_FOR_GUILDHALL_FULFILLMENT",
            "RETURN_TO_FOUNDRY" => "VERSIONED_CORRECTION_REQUIRED",
            "REFUSED" => "CANONICAL_PROGRESSION_HALTED",
            "UNRESOLVED" => "HELD_PENDING_EXPLICIT_RESOLUTION",
            default => throw new \RuntimeException("S200_CONFIRMATION_RECORD_DISPOSITION_INVALID"),
        };
        $bundle = [
            "confirmation_request" => $request,
            "confirmation_case" => $case,
            "lord_speaker_acceptance" => $acceptance,
            "baseline_witness" => $witness,
            "secured_deposition" => $deposition,
            "jurisdiction_baseline" => $baseline,
            "fresh_consistency_trial" => $consistency,
            "required_trial_ledger" => $ledger,
            "senator_finding_set" => $findingSet,
            "senate_disposition" => $disposition,
            "witness_retirement_set" => $retirement,
        ];
        $id = "senate-subordinate-persona-confirmation-record-" . substr(hash("sha256", CanonicalJson::encode([
            $retirementSetId,
            $retirement["record_digest"],
            $decision,
            hash("sha256", CanonicalJson::encode($bundle)),
        ])), 0, 20);
        return $this->persist($id, [
            "schema" => "imperium.senate-subordinate-persona-confirmation-record/v1",
            "confirmation_record_id" => $id,
            "instance_id" => $retirement["instance_id"],
            "candidate_id" => $retirement["candidate_id"],
            "candidate_digest" => $retirement["candidate_digest"],
            "originating_guildhall_commission_id" => $retirement["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $retirement["originating_guildhall_commission_digest"],
            "review_target_lineage" => $retirement["review_target_lineage"],
            "retirement_set_id" => $retirementSetId,
            "retirement_set_digest" => $retirement["record_digest"],
            "record_bundle" => $bundle,
            "record_bundle_digest" => hash("sha256", CanonicalJson::encode($bundle)),
            "senate_disposition" => $decision,
            "foundry_routing" => $routing,
            "issuer" => ["office" => "senate", "lord_speaker" => $disposition["lord_speaker"]],
            "recipient" => ["office" => "foundry", "seat" => "foundry.artificer"],
            "evidence_omitted" => false,
            "findings_omitted" => false,
            "disagreement_preserved" => true,
            "all_witnesses_retired" => true,
            "recipient_acceptance" => null,
            "status" => "CONFIRMATION_RECORD_ISSUED_PENDING_FOUNDRY_ACCEPTANCE",
            "senate_confirmation_granted" => "CONFIRMED" === $decision,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function readRecord(string $directory, mixed $id, string $error): array
    {
        return is_string($id)
            ? $this->read($this->senate . "/" . $directory . "/" . $id . ".json", $error)
            : [];
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->recordDirectory) && !mkdir($this->recordDirectory, 0770, true) && !is_dir($this->recordDirectory)) {
            throw new \RuntimeException("S202_CONFIRMATION_RECORD_ISSUANCE_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->recordDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S203_CONFIRMATION_RECORD_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S203_CONFIRMATION_RECORD_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S202_CONFIRMATION_RECORD_ISSUANCE_FAILED");
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
