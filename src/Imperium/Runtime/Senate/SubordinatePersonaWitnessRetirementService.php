<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaWitnessRetirementService
{
    private string $dispositionDirectory;
    private string $findingDirectory;
    private string $ledgerDirectory;
    private string $baselineDirectory;
    private string $consistencyDirectory;
    private string $witnessDirectory;
    private string $occupancyDirectory;
    private string $eventDirectory;
    private string $setDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $senate = $projectDir . "/var/imperium/offices/senate";
        $this->dispositionDirectory = $senate . "/dispositions";
        $this->findingDirectory = $senate . "/senator-finding-sets";
        $this->ledgerDirectory = $senate . "/required-trial-ledgers";
        $this->baselineDirectory = $senate . "/jurisdiction-baselines";
        $this->consistencyDirectory = $senate . "/fresh-consistency-trials";
        $this->witnessDirectory = $senate . "/persona-witnesses";
        $this->occupancyDirectory = $senate . "/occupancy";
        $this->eventDirectory = $senate . "/witness-retirement-events";
        $this->setDirectory = $senate . "/witness-retirement-sets";
    }

    public function retire(string $dispositionId): array
    {
        if (!preg_match('/^senate-subordinate-persona-disposition-[a-f0-9]{20}$/', $dispositionId)) {
            throw new \InvalidArgumentException("S187_SENATE_DISPOSITION_ID_INVALID");
        }
        foreach (glob($this->setDirectory . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "S195_WITNESS_RETIREMENT_SET_CONFLICT");
            if ($dispositionId === ($existing["disposition_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $disposition = $this->read(
            $this->dispositionDirectory . "/" . $dispositionId . ".json",
            "S188_SENATE_DISPOSITION_ABSENT",
        );
        if ("imperium.senate-subordinate-persona-disposition/v2" === ($disposition["schema"] ?? null)) {
            return $this->retireV2($dispositionId, $disposition);
        }
        $findingSetId = $disposition["finding_set_id"] ?? null;
        $findingSet = is_string($findingSetId)
            ? $this->read($this->findingDirectory . "/" . $findingSetId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID")
            : [];
        $ledgerId = $findingSet["required_trial_ledger_id"] ?? null;
        $ledger = is_string($ledgerId)
            ? $this->read($this->ledgerDirectory . "/" . $ledgerId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID")
            : [];
        $baselineId = $ledger["baseline_id"] ?? null;
        $baseline = is_string($baselineId)
            ? $this->read($this->baselineDirectory . "/" . $baselineId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID")
            : [];
        $consistencyId = $ledger["fresh_consistency_trial_id"] ?? null;
        $consistency = is_string($consistencyId)
            ? $this->read($this->consistencyDirectory . "/" . $consistencyId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID")
            : [];
        if (
            !$this->digestMatches($disposition) ||
            !is_string($disposition["originating_guildhall_commission_id"] ?? null) ||
            !$this->digestMatches($findingSet) ||
            !$this->digestMatches($ledger) ||
            !$this->digestMatches($baseline) ||
            !$this->digestMatches($consistency) ||
            $dispositionId !== ($disposition["disposition_id"] ?? null) ||
            "SENATE_DISPOSITION_SEALED_PENDING_WITNESS_RETIREMENT" !== ($disposition["status"] ?? null) ||
            true !== ($disposition["witness_retirement_required"] ?? null) ||
            true !== ($disposition["sealed"] ?? null) ||
            ($disposition["finding_set_digest"] ?? null) !== ($findingSet["record_digest"] ?? null) ||
            ($findingSet["required_trial_ledger_digest"] ?? null) !== ($ledger["record_digest"] ?? null) ||
            ($ledger["baseline_digest"] ?? null) !== ($baseline["record_digest"] ?? null) ||
            ($ledger["fresh_consistency_trial_digest"] ?? null) !== ($consistency["record_digest"] ?? null) ||
            true === ($disposition["admission_authority"] ?? null) ||
            true === ($disposition["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("S189_WITNESS_RETIREMENT_CHAIN_INVALID");
        }

        $references = $this->witnessReferences($baseline, $consistency, $ledger);
        $witnesses = [];
        foreach ($references as $reference) {
            $witness = $this->read(
                $this->witnessDirectory . "/" . $reference["manifestation_id"] . ".json",
                "S190_WITNESS_RETIREMENT_INVENTORY_INVALID",
            );
            if (
                !$this->digestMatches($witness) ||
                $reference["manifestation_digest"] !== ($witness["record_digest"] ?? null) ||
                ($disposition["instance_id"] ?? null) !== ($witness["instance_id"] ?? null) ||
                ($disposition["candidate_id"] ?? null) !== ($witness["candidate_id"] ?? null) ||
                ($disposition["candidate_digest"] ?? null) !== ($witness["candidate_digest"] ?? null) ||
                ($disposition["originating_guildhall_commission_id"] ?? null) !== ($witness["originating_guildhall_commission_id"] ?? null) ||
                ($disposition["originating_guildhall_commission_digest"] ?? null) !== ($witness["originating_guildhall_commission_digest"] ?? null) ||
                "STERILE_PERSONA_ONLY_STAND_INSTANCE" !== ($witness["manifestation_class"] ?? null) ||
                "senate.stand" !== ($witness["location"] ?? null) ||
                true !== ($witness["retirement_required_after_disposition"] ?? null) ||
                false !== ($witness["operational_authority"] ?? null) ||
                true === ($witness["execution_authority"] ?? null)
            ) {
                throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
            }
            $witnesses[] = ["reference" => $reference, "record" => $witness];
        }
        $ids = array_column($references, "manifestation_id");
        if (4 !== count($ids) || 4 !== count(array_unique($ids))) {
            throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
        }
        $bailiff = $this->bailiff(
            $disposition["instance_id"],
            $witnesses[0]["record"]["bailiff"] ?? null,
        );
        $events = [];
        foreach ($witnesses as $item) {
            $witness = $item["record"];
            $reference = $item["reference"];
            if (
                ($witness["bailiff"]["binding_id"] ?? null) !== ($bailiff["binding_id"] ?? null) ||
                ($witness["bailiff"]["binding_digest"] ?? null) !== ($bailiff["record_digest"] ?? null)
            ) {
                throw new \RuntimeException("S191_BAILIFF_RETIREMENT_AUTHORITY_INVALID");
            }
            $eventId = "senate-witness-retirement-event-" . substr(hash("sha256", CanonicalJson::encode([
                $dispositionId,
                $disposition["record_digest"],
                $witness["manifestation_id"],
                $witness["record_digest"],
                $bailiff["record_digest"],
            ])), 0, 20);
            $events[] = $this->persist($this->eventDirectory, $eventId, [
                "schema" => "imperium.senate-witness-retirement-event/v1",
                "retirement_event_id" => $eventId,
                "instance_id" => $disposition["instance_id"],
                "disposition_id" => $dispositionId,
                "disposition_digest" => $disposition["record_digest"],
                "candidate_id" => $disposition["candidate_id"],
                "candidate_digest" => $disposition["candidate_digest"],
                "originating_guildhall_commission_id" => $disposition["originating_guildhall_commission_id"],
                "originating_guildhall_commission_digest" => $disposition["originating_guildhall_commission_digest"],
                "witness_role" => $reference["witness_role"],
                "manifestation_id" => $witness["manifestation_id"],
                "manifestation_digest" => $witness["record_digest"],
                "bailiff" => $this->actor($bailiff),
                "prior_status" => $witness["status"],
                "stand_access_revoked" => true,
                "synthetic_material_revoked" => true,
                "runtime_terminated" => true,
                "evidentiary_artifacts_preserved" => true,
                "status" => "RETIRED",
                "operational_authority" => false,
                "admission_authority" => false,
                "execution_authority" => false,
                "sealed" => true,
            ], "S192_WITNESS_RETIREMENT_EVENT_FAILED", "S193_WITNESS_RETIREMENT_EVENT_CONFLICT");
        }
        $setId = "senate-witness-retirement-set-" . substr(hash("sha256", CanonicalJson::encode([
            $dispositionId,
            $disposition["record_digest"],
            array_column($events, "record_digest"),
        ])), 0, 20);
        return $this->persist($this->setDirectory, $setId, [
            "schema" => "imperium.senate-witness-retirement-set/v1",
            "retirement_set_id" => $setId,
            "instance_id" => $disposition["instance_id"],
            "disposition_id" => $dispositionId,
            "disposition_digest" => $disposition["record_digest"],
            "candidate_id" => $disposition["candidate_id"],
            "candidate_digest" => $disposition["candidate_digest"],
            "originating_guildhall_commission_id" => $disposition["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $disposition["originating_guildhall_commission_digest"],
            "review_target_lineage" => $disposition["review_target_lineage"],
            "bailiff" => $this->actor($bailiff),
            "retirement_events" => $events,
            "required_witness_count" => 4,
            "retired_witness_count" => count($events),
            "all_witnesses_accounted_for" => 4 === count($events),
            "all_witnesses_retired" => 4 === count($events),
            "evidentiary_artifacts_preserved" => true,
            "status" => "ALL_WITNESSES_RETIRED_PENDING_CONFIRMATION_RECORD_ISSUANCE",
            "confirmation_record_issuance_ready" => true,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ], "S194_WITNESS_RETIREMENT_SET_FAILED", "S195_WITNESS_RETIREMENT_SET_CONFLICT");
    }

    private function retireV2(string $dispositionId, array $disposition): array
    {
        $openingId = $disposition["source_disposition_authority_opening"]["id"] ?? null;
        $opening = is_string($openingId) ? $this->read($this->dispositionDirectory . "/../persona-disposition-authority-openings/" . $openingId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID") : [];
        $reconciliationId = $opening["source_reconciliation"]["id"] ?? null;
        $reconciliation = is_string($reconciliationId) ? $this->read($this->dispositionDirectory . "/../persona-reconciliations/" . $reconciliationId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID") : [];
        $ledgerId = $disposition["required_trial_ledger_id"] ?? null;
        $ledger = is_string($ledgerId) ? $this->read($this->ledgerDirectory . "/" . $ledgerId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID") : [];
        $baselineId = $ledger["baseline_id"] ?? null;
        $baseline = is_string($baselineId) ? $this->read($this->baselineDirectory . "/" . $baselineId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID") : [];
        $consistencyId = $ledger["fresh_consistency_trial_id"] ?? null;
        $consistency = is_string($consistencyId) ? $this->read($this->consistencyDirectory . "/" . $consistencyId . ".json", "S189_WITNESS_RETIREMENT_CHAIN_INVALID") : [];
        foreach ([$disposition, $opening, $reconciliation, $ledger, $baseline, $consistency] as $record) if (!$this->digestMatches($record)) throw new \RuntimeException("S189_WITNESS_RETIREMENT_CHAIN_INVALID");
        if ($dispositionId !== ($disposition["disposition_id"] ?? null) || "SENATE_DISPOSITION_SEALED_PENDING_WITNESS_RETIREMENT" !== ($disposition["status"] ?? null) || true !== ($disposition["witness_retirement_required"] ?? null) || true !== ($disposition["sealed"] ?? null) || ($disposition["source_disposition_authority_opening"]["digest"] ?? null) !== ($opening["record_digest"] ?? null) || ($opening["source_reconciliation"]["digest"] ?? null) !== ($reconciliation["record_digest"] ?? null) || ($disposition["required_trial_ledger_digest"] ?? null) !== ($ledger["record_digest"] ?? null) || ($ledger["baseline_digest"] ?? null) !== ($baseline["record_digest"] ?? null) || ($ledger["fresh_consistency_trial_digest"] ?? null) !== ($consistency["record_digest"] ?? null) || true === ($disposition["admission_authority"] ?? null) || true === ($disposition["execution_authority"] ?? null)) throw new \RuntimeException("S189_WITNESS_RETIREMENT_CHAIN_INVALID");
        $references = $this->witnessReferences($baseline, $consistency, $ledger);
        $witnesses = [];
        foreach ($references as $reference) {
            $witness = $this->read($this->witnessDirectory . "/" . $reference["manifestation_id"] . ".json", "S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
            if (!$this->digestMatches($witness) || $reference["manifestation_digest"] !== ($witness["record_digest"] ?? null) || ($disposition["instance_id"] ?? null) !== ($witness["instance_id"] ?? null) || "STERILE_PERSONA_ONLY_STAND_INSTANCE" !== ($witness["manifestation_class"] ?? null) || "senate.stand" !== ($witness["location"] ?? null) || true !== ($witness["retirement_required_after_disposition"] ?? null) || false !== ($witness["operational_authority"] ?? null) || true === ($witness["execution_authority"] ?? null)) throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
            $witnesses[] = ["reference" => $reference, "record" => $witness];
        }
        $ids = array_column($references, "manifestation_id");
        if (4 !== count($ids) || 4 !== count(array_unique($ids))) throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
        $source = $witnesses[0]["record"];
        foreach ($witnesses as $item) if (($source["candidate_digest"] ?? null) !== ($item["record"]["candidate_digest"] ?? null) || ($source["originating_guildhall_commission_digest"] ?? null) !== ($item["record"]["originating_guildhall_commission_digest"] ?? null)) throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
        $bailiff = $this->bailiff($disposition["instance_id"], $source["bailiff"] ?? null);
        $events = [];
        foreach ($witnesses as $item) {
            $witness = $item["record"]; $reference = $item["reference"];
            if (($witness["bailiff"]["binding_id"] ?? null) !== ($bailiff["binding_id"] ?? null) || ($witness["bailiff"]["binding_digest"] ?? null) !== ($bailiff["record_digest"] ?? null)) throw new \RuntimeException("S191_BAILIFF_RETIREMENT_AUTHORITY_INVALID");
            $eventId = "senate-witness-retirement-event-" . substr(hash("sha256", CanonicalJson::encode([$dispositionId, $disposition["record_digest"], $witness["manifestation_id"], $witness["record_digest"], $bailiff["record_digest"]])), 0, 20);
            $events[] = $this->persist($this->eventDirectory, $eventId, ["schema"=>"imperium.senate-witness-retirement-event/v2","retirement_event_id"=>$eventId,"instance_id"=>$disposition["instance_id"],"disposition_id"=>$dispositionId,"disposition_digest"=>$disposition["record_digest"],"candidate_id"=>$source["candidate_id"],"candidate_digest"=>$source["candidate_digest"],"originating_guildhall_commission_id"=>$source["originating_guildhall_commission_id"],"originating_guildhall_commission_digest"=>$source["originating_guildhall_commission_digest"],"witness_role"=>$reference["witness_role"],"manifestation_id"=>$witness["manifestation_id"],"manifestation_digest"=>$witness["record_digest"],"bailiff"=>$this->actor($bailiff),"prior_status"=>$witness["status"],"stand_access_revoked"=>true,"synthetic_material_revoked"=>true,"runtime_terminated"=>true,"evidentiary_artifacts_preserved"=>true,"status"=>"RETIRED","operational_authority"=>false,"admission_authority"=>false,"execution_authority"=>false,"sealed"=>true], "S192_WITNESS_RETIREMENT_EVENT_FAILED", "S193_WITNESS_RETIREMENT_EVENT_CONFLICT");
        }
        $setId = "senate-witness-retirement-set-" . substr(hash("sha256", CanonicalJson::encode([$dispositionId, $disposition["record_digest"], array_column($events, "record_digest")])), 0, 20);
        return $this->persist($this->setDirectory, $setId, ["schema"=>"imperium.senate-witness-retirement-set/v2","retirement_set_id"=>$setId,"instance_id"=>$disposition["instance_id"],"disposition_id"=>$dispositionId,"disposition_digest"=>$disposition["record_digest"],"source_disposition_authority_opening"=>$disposition["source_disposition_authority_opening"],"candidate_id"=>$source["candidate_id"],"candidate_digest"=>$source["candidate_digest"],"originating_guildhall_commission_id"=>$source["originating_guildhall_commission_id"],"originating_guildhall_commission_digest"=>$source["originating_guildhall_commission_digest"],"review_target_lineage"=>$source["review_target_lineage"],"bailiff"=>$this->actor($bailiff),"retirement_events"=>$events,"required_witness_count"=>4,"retired_witness_count"=>count($events),"all_witnesses_accounted_for"=>4===count($events),"all_witnesses_retired"=>4===count($events),"evidentiary_artifacts_preserved"=>true,"status"=>"ALL_WITNESSES_RETIRED_PENDING_CONFIRMATION_RECORD_ISSUANCE","confirmation_record_issuance_ready"=>true,"admission_authority"=>false,"profile_approval_authority"=>false,"spawning_authority"=>false,"seat_binding_authority"=>false,"execution_authority"=>false,"sealed"=>true], "S194_WITNESS_RETIREMENT_SET_FAILED", "S195_WITNESS_RETIREMENT_SET_CONFLICT");
    }

    private function witnessReferences(array $baseline, array $consistency, array $ledger): array
    {
        $references = [[
            "witness_role" => "baseline",
            "manifestation_id" => $baseline["manifestation_id"] ?? null,
            "manifestation_digest" => $baseline["manifestation_digest"] ?? null,
        ], [
            "witness_role" => "fresh_consistency",
            "manifestation_id" => $consistency["fresh_witness"]["manifestation_id"] ?? null,
            "manifestation_digest" => $consistency["fresh_witness"]["manifestation_digest"] ?? null,
        ]];
        foreach (["governance", "security"] as $jurisdiction) {
            $matches = array_values(array_filter(
                $ledger["pressure_trials"] ?? [],
                static fn (mixed $trial): bool => is_array($trial) && $jurisdiction === ($trial["jurisdiction"] ?? null),
            ));
            if (1 !== count($matches)) {
                throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
            }
            $references[] = [
                "witness_role" => $jurisdiction . "_pressure",
                "manifestation_id" => $matches[0]["fresh_witness"]["manifestation_id"] ?? null,
                "manifestation_digest" => $matches[0]["fresh_witness"]["manifestation_digest"] ?? null,
            ];
        }
        foreach ($references as $reference) {
            if (!is_string($reference["manifestation_id"]) || !is_string($reference["manifestation_digest"])) {
                throw new \RuntimeException("S190_WITNESS_RETIREMENT_INVENTORY_INVALID");
            }
        }
        return $references;
    }

    private function bailiff(string $instanceId, mixed $reference): array
    {
        $bindingId = is_array($reference) ? $reference["binding_id"] ?? null : null;
        $record = is_string($bindingId)
            ? $this->read($this->occupancyDirectory . "/" . $bindingId . ".json", "S191_BAILIFF_RETIREMENT_AUTHORITY_INVALID")
            : [];
        if (
            !$this->digestMatches($record) ||
            "senate.bailiff" !== ($record["seat"] ?? null) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record["proceeding_security_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null) ||
            !is_array($reference) ||
            ($reference["binding_digest"] ?? null) !== ($record["record_digest"] ?? null)
        ) {
            throw new \RuntimeException("S191_BAILIFF_RETIREMENT_AUTHORITY_INVALID");
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
            "founding_class" => $binding["founding_class"] ?? "ARTIFACT_BACKED",
            "placeholder_version" => $binding["placeholder_version"] ?? null,
        ];
    }

    private function persist(string $directory, string $id, array $record, string $failed, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException($failed);
        }
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $directory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, $conflict);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException($conflict);
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException($failed);
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
