<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSenatorFindingService
{
    private string $ledgerDirectory;
    private string $baselineDirectory;
    private string $consistencyDirectory;
    private string $occupancyDirectory;
    private string $findingDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
        private SenatorFindingCognitionGateway $cognition,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->ledgerDirectory = $senate . "/required-trial-ledgers";
        $this->baselineDirectory = $senate . "/jurisdiction-baselines";
        $this->consistencyDirectory = $senate . "/fresh-consistency-trials";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->findingDirectory = $senate . "/senator-finding-sets";
    }

    public function issue(string $ledgerId): array
    {
        if (!preg_match('/^senate-persona-required-trial-ledger-[a-f0-9]{20}$/', $ledgerId)) {
            throw new \InvalidArgumentException("S171_REQUIRED_TRIAL_LEDGER_ID_INVALID");
        }
        foreach (glob($this->findingDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S178_SENATOR_FINDING_SET_CONFLICT");
            if ($ledgerId === ($existing["required_trial_ledger_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $ledger = $this->read(
            $this->ledgerDirectory . "/" . $ledgerId . ".json",
            "S172_REQUIRED_TRIAL_LEDGER_ABSENT",
        );
        $baselineId = $ledger["baseline_id"] ?? null;
        $baseline = is_string($baselineId)
            ? $this->read($this->baselineDirectory . "/" . $baselineId . ".json", "S173_SENATOR_FINDING_CHAIN_INVALID")
            : [];
        $consistencyId = $ledger["fresh_consistency_trial_id"] ?? null;
        $consistency = is_string($consistencyId)
            ? $this->read($this->consistencyDirectory . "/" . $consistencyId . ".json", "S173_SENATOR_FINDING_CHAIN_INVALID")
            : [];
        if (
            !$this->digestMatches($ledger) ||
            !$this->digestMatches($baseline) ||
            !$this->digestMatches($consistency) ||
            "REQUIRED_TRIALS_SEALED_PENDING_SENATOR_FINDINGS" !== ($ledger["status"] ?? null) ||
            true !== ($ledger["evidentiary_phase_complete"] ?? null) ||
            [] !== ($ledger["senator_findings"] ?? null) ||
            ($ledger["baseline_digest"] ?? null) !== ($baseline["record_digest"] ?? null) ||
            ($ledger["fresh_consistency_trial_digest"] ?? null) !== ($consistency["record_digest"] ?? null) ||
            true === ($ledger["admission_authority"] ?? null) ||
            true === ($ledger["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S173_SENATOR_FINDING_CHAIN_INVALID");
        }

        $findings = [];
        foreach (["practice", "governance", "consistency", "security"] as $jurisdiction) {
            $baselineTurn = $this->turn($baseline, $jurisdiction);
            $senator = $this->senator(
                $jurisdiction,
                $ledger["instance_id"],
                $baselineTurn["assignment"]["senator"] ?? null,
            );
            $evidence = $this->evidence(
                $jurisdiction,
                $baselineTurn,
                $consistency,
                $ledger,
            );
            $assignment = [
                "jurisdiction" => $jurisdiction,
                "senator" => $this->actor($senator),
                "required_trial_ledger_id" => $ledgerId,
                "required_trial_ledger_digest" => $ledger["record_digest"],
                "available_evidence_references" =>
                    $evidence["available_evidence_references"],
                "finding_authority" => true,
                "vote_authority" => false,
                "senate_disposition_authority" => false,
            ];
            $decision = $this->cognition->find(
                $jurisdiction,
                $assignment,
                $evidence,
            );
            $this->validate($jurisdiction, $decision, $evidence);
            $finding = [
                "jurisdiction" => $jurisdiction,
                "senator" => $this->actor($senator),
                "assignment" => $assignment,
                "decision" => $decision,
                "evidence_package_digest" =>
                    hash("sha256", CanonicalJson::encode($evidence)),
                "attributable" => true,
                "sealed" => true,
            ];
            $finding["finding_digest"] = hash(
                "sha256",
                CanonicalJson::encode($finding),
            );
            $findings[] = $finding;
        }
        $mandatoryFailures = array_values(array_map(
            static fn (array $finding): bool =>
                true === ($finding["decision"]["mandatory_failure"] ?? false),
            $findings,
        ));
        $id = "senate-persona-senator-finding-set-" . substr(hash("sha256", CanonicalJson::encode([
            $ledgerId,
            $ledger["record_digest"],
            array_column($findings, "finding_digest"),
        ])), 0, 20);
        return $this->persist($id, [
            "schema" => "imperium.senate-persona-senator-finding-set/v1",
            "finding_set_id" => $id,
            "instance_id" => $ledger["instance_id"],
            "candidate_id" => $ledger["candidate_id"],
            "candidate_digest" => $ledger["candidate_digest"],
            "review_target_lineage" => $ledger["review_target_lineage"],
            "required_trial_ledger_id" => $ledgerId,
            "required_trial_ledger_digest" => $ledger["record_digest"],
            "findings" => $findings,
            "jurisdictions" => ["practice", "governance", "consistency", "security"],
            "mandatory_failure_present" => in_array(true, $mandatoryFailures, true),
            "status" => "SENATOR_FINDINGS_SEALED_PENDING_LORD_SPEAKER_DISPOSITION",
            "aggregate_score" => null,
            "vote" => null,
            "majority_calculation" => null,
            "disagreement_suppressed" => false,
            "senate_disposition" => null,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function evidence(
        string $jurisdiction,
        array $baselineTurn,
        array $consistency,
        array $ledger,
    ): array {
        $references = [
            "baseline:" . $jurisdiction . ":" . $baselineTurn["turn_digest"],
        ];
        $package = ["baseline_turn" => $baselineTurn];
        if ("consistency" === $jurisdiction) {
            $references[] = "fresh-consistency:" . $consistency["record_digest"];
            $package["fresh_consistency_trial"] = $consistency;
        }
        if (in_array($jurisdiction, ["governance", "security"], true)) {
            $matches = array_values(array_filter(
                $ledger["pressure_trials"] ?? [],
                static fn (mixed $trial): bool =>
                    is_array($trial) &&
                    $jurisdiction === ($trial["jurisdiction"] ?? null),
            ));
            if (1 !== count($matches)) {
                throw new \RuntimeException("S173_SENATOR_FINDING_CHAIN_INVALID");
            }
            $references[] = "pressure:" . $jurisdiction . ":" . $matches[0]["trial_digest"];
            $package["pressure_trial"] = $matches[0];
        }
        $package["available_evidence_references"] = $references;
        return $package;
    }

    private function turn(array $baseline, string $jurisdiction): array
    {
        $matches = array_values(array_filter(
            $baseline["turns"] ?? [],
            static fn (mixed $turn): bool =>
                is_array($turn) &&
                $jurisdiction === ($turn["jurisdiction"] ?? null),
        ));
        if (1 !== count($matches)) {
            throw new \RuntimeException("S173_SENATOR_FINDING_CHAIN_INVALID");
        }
        return $matches[0];
    }

    private function senator(
        string $jurisdiction,
        string $instanceId,
        mixed $reference,
    ): array {
        $seat = "senate.committee." . $jurisdiction;
        $matches = [];
        foreach (glob($this->occupancyDirectory . "/*.json") ?: [] as $path) {
            $record = $this->read($path, "S174_SENATOR_FINDING_AUTHORITY_INVALID");
            if ($seat === ($record["seat"] ?? null)) {
                $matches[] = $record;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException("S174_SENATOR_FINDING_AUTHORITY_INVALID");
        }
        $record = $matches[0];
        if (
            !$this->digestMatches($record) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record["senator_finding_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null) ||
            !is_array($reference) ||
            ($reference["binding_id"] ?? null) !== ($record["binding_id"] ?? null) ||
            ($reference["binding_digest"] ?? null) !== ($record["record_digest"] ?? null)
        ) {
            throw new \RuntimeException("S174_SENATOR_FINDING_AUTHORITY_INVALID");
        }
        return $record;
    }

    private function validate(
        string $jurisdiction,
        array $decision,
        array $evidence,
    ): void {
        $keys = array_keys($decision);
        sort($keys, SORT_STRING);
        $references = $decision["evidence_references"] ?? null;
        if (
            ["disposition", "evidence_references", "limitations", "mandatory_failure", "rationale", "severity"] !== $keys ||
            !in_array($decision["disposition"] ?? null, ["PASS", "CONCERN", "FAIL", "UNRESOLVED"], true) ||
            !in_array($decision["severity"] ?? null, ["NONE", "LOW", "MEDIUM", "HIGH", "CRITICAL"], true) ||
            !is_string($decision["rationale"] ?? null) ||
            "" === trim($decision["rationale"]) ||
            !is_array($references) ||
            [] === $references ||
            !is_array($decision["limitations"] ?? null) ||
            !is_bool($decision["mandatory_failure"] ?? null) ||
            ("security" !== $jurisdiction && true === $decision["mandatory_failure"]) ||
            (true === $decision["mandatory_failure"] && (
                "FAIL" !== $decision["disposition"] ||
                "CRITICAL" !== $decision["severity"]
            ))
        ) {
            throw new \RuntimeException("S176_SENATOR_FINDING_INVALID");
        }
        foreach ($references as $reference) {
            if (!is_string($reference) || !in_array($reference, $evidence["available_evidence_references"], true)) {
                throw new \RuntimeException("S176_SENATOR_FINDING_INVALID");
            }
        }
        foreach ($decision["limitations"] as $limitation) {
            if (!is_string($limitation) || "" === trim($limitation)) {
                throw new \RuntimeException("S176_SENATOR_FINDING_INVALID");
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
        if (!is_dir($this->findingDirectory) && !mkdir($this->findingDirectory, 0770, true) && !is_dir($this->findingDirectory)) {
            throw new \RuntimeException("S177_SENATOR_FINDING_SET_FAILED");
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->findingDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "S178_SENATOR_FINDING_SET_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException("S178_SENATOR_FINDING_SET_CONFLICT");
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("S177_SENATOR_FINDING_SET_FAILED");
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
