<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSenateDispositionService
{
    private string $findingDirectory;
    private string $ledgerDirectory;
    private string $baselineDirectory;
    private string $witnessDirectory;
    private string $occupancyDirectory;
    private string $dispositionDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private LordSpeakerDispositionCognitionGateway $cognition,
    ) {
        $senate = rtrim($projectDir) . "/var/imperium/offices/senate";
        $this->findingDirectory = $senate . "/senator-finding-sets";
        $this->ledgerDirectory = $senate . "/required-trial-ledgers";
        $this->baselineDirectory = $senate . "/jurisdiction-baselines";
        $this->witnessDirectory = $senate . "/persona-witnesses";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->dispositionDirectory = $senate . "/dispositions";
    }

    public function issue(string $findingSetId): array
    {
        if (!preg_match('/^senate-persona-senator-finding-set-[a-f0-9]{20}$/', $findingSetId)) {
            throw new \InvalidArgumentException("S179_SENATOR_FINDING_SET_ID_INVALID");
        }
        foreach (glob($this->dispositionDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S186_SENATE_DISPOSITION_CONFLICT");
            if ($findingSetId === ($existing["finding_set_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $findingSet = $this->read(
            $this->findingDirectory . "/" . $findingSetId . ".json",
            "S180_SENATOR_FINDING_SET_ABSENT",
        );
        $ledgerId = $findingSet["required_trial_ledger_id"] ?? null;
        $ledger = is_string($ledgerId)
            ? $this->read($this->ledgerDirectory . "/" . $ledgerId . ".json", "S181_SENATE_DISPOSITION_CHAIN_INVALID")
            : [];
        $baselineId = $ledger["baseline_id"] ?? null;
        $baseline = is_string($baselineId)
            ? $this->read($this->baselineDirectory . "/" . $baselineId . ".json", "S181_SENATE_DISPOSITION_CHAIN_INVALID")
            : [];
        $manifestationId = $baseline["manifestation_id"] ?? null;
        $witness = is_string($manifestationId)
            ? $this->read($this->witnessDirectory . "/" . $manifestationId . ".json", "S181_SENATE_DISPOSITION_CHAIN_INVALID")
            : [];
        if (
            !$this->digestMatches($findingSet) ||
            !is_string($findingSet["originating_guildhall_commission_id"] ?? null) ||
            !$this->digestMatches($ledger) ||
            !$this->digestMatches($baseline) ||
            !$this->digestMatches($witness) ||
            $findingSetId !== ($findingSet["finding_set_id"] ?? null) ||
            "SENATOR_FINDINGS_SEALED_PENDING_LORD_SPEAKER_DISPOSITION" !== ($findingSet["status"] ?? null) ||
            true !== ($findingSet["sealed"] ?? null) ||
            !is_bool($findingSet["mandatory_failure_present"] ?? null) ||
            4 !== count($findingSet["findings"] ?? []) ||
            ($findingSet["required_trial_ledger_digest"] ?? null) !== ($ledger["record_digest"] ?? null) ||
            ($ledger["baseline_digest"] ?? null) !== ($baseline["record_digest"] ?? null) ||
            ($baseline["manifestation_digest"] ?? null) !== ($witness["record_digest"] ?? null) ||
            ($findingSet["candidate_digest"] ?? null) !== ($witness["candidate_digest"] ?? null) ||
            true === ($findingSet["admission_authority"] ?? null) ||
            true === ($findingSet["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S181_SENATE_DISPOSITION_CHAIN_INVALID");
        }
        $lordSpeaker = $this->lordSpeaker(
            $findingSet["instance_id"],
            $witness["lord_speaker"] ?? null,
        );
        $references = array_column($findingSet["findings"], "finding_digest");
        if (4 !== count(array_unique($references)) || in_array(null, $references, true)) {
            throw new \RuntimeException("S181_SENATE_DISPOSITION_CHAIN_INVALID");
        }
        $authority = [
            "lord_speaker" => $this->actor($lordSpeaker),
            "finding_set_id" => $findingSetId,
            "finding_set_digest" => $findingSet["record_digest"],
            "candidate_id" => $findingSet["candidate_id"],
            "candidate_digest" => $findingSet["candidate_digest"],
            "originating_guildhall_commission_id" => $findingSet["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $findingSet["originating_guildhall_commission_digest"],
            "available_finding_references" => $references,
            "mandatory_failure_present" => $findingSet["mandatory_failure_present"],
            "senate_disposition_authority" => true,
            "admission_authority" => false,
            "execution_authority" => false,
        ];
        $decision = $this->cognition->decide($authority, $findingSet);
        $this->validateDecision($decision, $references, $findingSet);
        $disagreementPresent = 1 < count(array_unique(array_map(
            static fn (array $finding): mixed => $finding["decision"]["disposition"] ?? null,
            $findingSet["findings"],
        )));
        $id = "senate-subordinate-persona-disposition-" . substr(hash("sha256", CanonicalJson::encode([
            $findingSetId,
            $findingSet["record_digest"],
            $lordSpeaker["binding_id"],
            $lordSpeaker["record_digest"],
            $decision,
        ])), 0, 20);
        return $this->persist($id, [
            "schema" => "imperium.senate-subordinate-persona-disposition/v1",
            "disposition_id" => $id,
            "instance_id" => $findingSet["instance_id"],
            "candidate_id" => $findingSet["candidate_id"],
            "candidate_digest" => $findingSet["candidate_digest"],
            "originating_guildhall_commission_id" =>
                $findingSet["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" =>
                $findingSet["originating_guildhall_commission_digest"],
            "review_target_lineage" => $findingSet["review_target_lineage"],
            "finding_set_id" => $findingSetId,
            "finding_set_digest" => $findingSet["record_digest"],
            "lord_speaker" => $this->actor($lordSpeaker),
            "decision" => $decision,
            "mandatory_failure_present" => $findingSet["mandatory_failure_present"],
            "mandatory_failure_bar_enforced" => true,
            "disagreement_present" => $disagreementPresent,
            "disagreement_preserved" => true,
            "aggregate_score" => null,
            "vote" => null,
            "majority_calculation" => null,
            "authorized_destination" => "foundry.artificer",
            "status" => "SENATE_DISPOSITION_SEALED_PENDING_WITNESS_RETIREMENT",
            "witness_retirement_required" => true,
            "senate_confirmation_granted" => "CONFIRMED" === $decision["disposition"],
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function lordSpeaker(string $instanceId, mixed $reference): array
    {
        $bindingId = is_array($reference) ? $reference["binding_id"] ?? null : null;
        $record = is_string($bindingId)
            ? $this->read($this->occupancyDirectory . "/" . $bindingId . ".json", "S183_LORD_SPEAKER_DISPOSITION_AUTHORITY_INVALID")
            : [];
        if (
            !$this->digestMatches($record) ||
            "senate.lord-speaker" !== ($record["seat"] ?? null) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record["senate_disposition_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null) ||
            !is_array($reference) ||
            ($reference["binding_digest"] ?? null) !== ($record["record_digest"] ?? null)
        ) {
            throw new \RuntimeException("S183_LORD_SPEAKER_DISPOSITION_AUTHORITY_INVALID");
        }
        return $record;
    }

    private function validateDecision(array $decision, array $references, array $findingSet): void
    {
        $keys = array_keys($decision);
        sort($keys, SORT_STRING);
        $actualReferences = $decision["finding_references"] ?? null;
        $limitations = $decision["limitations"] ?? null;
        if (
            ["conflicting_findings_treatment", "disposition", "finding_references", "limitations", "rationale"] !== $keys ||
            !in_array($decision["disposition"] ?? null, ["CONFIRMED", "RETURN_TO_FOUNDRY", "REFUSED", "UNRESOLVED"], true) ||
            !is_string($decision["rationale"] ?? null) ||
            "" === trim($decision["rationale"]) ||
            !is_string($decision["conflicting_findings_treatment"] ?? null) ||
            "" === trim($decision["conflicting_findings_treatment"]) ||
            !is_array($actualReferences) ||
            $references !== $actualReferences ||
            !is_array($limitations) ||
            (true === ($findingSet["mandatory_failure_present"] ?? null) && "CONFIRMED" === $decision["disposition"])
        ) {
            throw new \RuntimeException("S184_SENATE_DISPOSITION_INVALID");
        }
        foreach ($limitations as $limitation) {
            if (!is_string($limitation) || "" === trim($limitation)) {
                throw new \RuntimeException("S184_SENATE_DISPOSITION_INVALID");
            }
        }
    }

    private function actor(array $binding): array
    {
        return [
            "seat" => $binding["seat"],
            "binding_id" => $binding["binding_id"],
            "binding_digest" => $binding["record_digest"],
            "manifestation_id" => $binding["manifestation_id"],
            "occupancy_generation" => $binding["occupancy_generation"],
            "founding_class" => $binding["founding_class"] ?? "ARTIFACT_BACKED",
            "placeholder_version" => $binding["placeholder_version"] ?? null,
        ];
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->dispositionDirectory) && !mkdir($this->dispositionDirectory, 0770, true) && !is_dir($this->dispositionDirectory)) {
            throw new \RuntimeException("S185_SENATE_DISPOSITION_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->dispositionDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S186_SENATE_DISPOSITION_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S186_SENATE_DISPOSITION_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S185_SENATE_DISPOSITION_FAILED");
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
