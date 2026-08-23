<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSenateConfirmationRecordAcceptanceService
{
    private string $inbox;
    private string $occupancy;
    private string $acceptances;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->inbox = $projectDir . "/var/imperium/offices/senate/outbox/confirmation-records";
        $this->occupancy = $projectDir . "/var/imperium/offices/foundry/occupancy";
        $this->acceptances = $projectDir . "/var/imperium/offices/foundry/senate-confirmation-acceptances";
    }

    public function accept(string $recordId, string $bindingId): array
    {
        if (!preg_match('/^senate-subordinate-persona-confirmation-record-[a-f0-9]{20}$/', $recordId)) {
            throw new \InvalidArgumentException("F192_SENATE_CONFIRMATION_RECORD_ID_INVALID");
        }
        if (!preg_match('/^foundry-artificer-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException("F193_ARTIFICER_BINDING_ID_INVALID");
        }
        foreach (glob($this->acceptances . "/*.json") ?: [] as $path) {
            $existing = $this->read($path, "F199_SENATE_CONFIRMATION_ACCEPTANCE_CONFLICT");
            if ($recordId === ($existing["confirmation_record_id"] ?? null) && $this->digestMatches($existing)) {
                return $existing;
            }
        }
        $record = $this->read($this->inbox . "/" . $recordId . ".json", "F194_SENATE_CONFIRMATION_RECORD_ABSENT");
        $artificer = $this->read($this->occupancy . "/" . $bindingId . ".json", "F195_ARTIFICER_CONFIRMATION_ACCEPTANCE_AUTHORITY_INVALID");
        if (
            !$this->digestMatches($record) ||
            !is_string($record["originating_guildhall_commission_id"] ?? null) ||
            !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $record["originating_guildhall_commission_id"]) ||
            !is_string($record["originating_guildhall_commission_digest"] ?? null) ||
            !$this->digestMatches($artificer) ||
            $recordId !== ($record["confirmation_record_id"] ?? null) ||
            "CONFIRMATION_RECORD_ISSUED_PENDING_FOUNDRY_ACCEPTANCE" !== ($record["status"] ?? null) ||
            "foundry" !== ($record["recipient"]["office"] ?? null) ||
            "foundry.artificer" !== ($record["recipient"]["seat"] ?? null) ||
            null !== ($record["recipient_acceptance"] ?? null) ||
            true !== ($record["all_witnesses_retired"] ?? null) ||
            false !== ($record["evidence_omitted"] ?? null) ||
            false !== ($record["findings_omitted"] ?? null) ||
            true !== ($record["disagreement_preserved"] ?? null) ||
            "foundry.artificer" !== ($artificer["seat"] ?? null) ||
            ($record["instance_id"] ?? null) !== ($artificer["instance_id"] ?? null) ||
            "ACTIVE" !== ($artificer["status"] ?? null) ||
            true !== ($artificer["senate_confirmation_record_acceptance_authority"] ?? null) ||
            true === ($artificer["execution_authority"] ?? null) ||
            true === ($record["admission_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("F196_SENATE_CONFIRMATION_RECORD_INVALID");
        }
        $disposition = $record["senate_disposition"] ?? null;
        $expectedRouting = match ($disposition) {
            "CONFIRMED" => ["CONFIRMED_CANDIDATE_FOR_GUILDHALL_FULFILLMENT", "SENATE_CONFIRMATION_RECORD_ACCEPTED_PENDING_GUILDHALL_FULFILLMENT"],
            "RETURN_TO_FOUNDRY" => ["VERSIONED_CORRECTION_REQUIRED", "SENATE_CONFIRMATION_RECORD_ACCEPTED_PENDING_VERSIONED_CORRECTION"],
            "REFUSED" => ["CANONICAL_PROGRESSION_HALTED", "SENATE_CONFIRMATION_REFUSAL_ACCEPTED_PROGRESSION_HALTED"],
            "UNRESOLVED" => ["HELD_PENDING_EXPLICIT_RESOLUTION", "SENATE_CONFIRMATION_RECORD_ACCEPTED_PENDING_EXPLICIT_RESOLUTION"],
            default => throw new \RuntimeException("F197_SENATE_CONFIRMATION_ROUTING_INVALID"),
        };
        if ($expectedRouting[0] !== ($record["foundry_routing"] ?? null)) {
            throw new \RuntimeException("F197_SENATE_CONFIRMATION_ROUTING_INVALID");
        }
        $id = "foundry-senate-confirmation-acceptance-" . substr(hash("sha256", CanonicalJson::encode([
            $recordId, $record["record_digest"], $bindingId, $artificer["record_digest"], $disposition,
        ])), 0, 20);
        return $this->persist($id, [
            "schema" => "imperium.foundry-senate-confirmation-record-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => $record["instance_id"],
            "confirmation_record_id" => $recordId,
            "confirmation_record_digest" => $record["record_digest"],
            "candidate_id" => $record["candidate_id"],
            "candidate_digest" => $record["candidate_digest"],
            "originating_guildhall_commission_id" => $record["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $record["originating_guildhall_commission_digest"],
            "review_target_lineage" => $record["review_target_lineage"],
            "artificer" => $this->actor($artificer),
            "record_receipt_accepted" => true,
            "persona_reinterpreted" => false,
            "candidate_substituted" => false,
            "senate_disposition" => $disposition,
            "foundry_routing" => $expectedRouting[0],
            "status" => $expectedRouting[1],
            "guildhall_fulfillment_ready" => "CONFIRMED" === $disposition,
            "versioned_correction_required" => "RETURN_TO_FOUNDRY" === $disposition,
            "canonical_progression_halted" => "REFUSED" === $disposition,
            "explicit_resolution_required" => "UNRESOLVED" === $disposition,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function actor(array $binding): array
    {
        return ["seat" => $binding["seat"], "binding_id" => $binding["binding_id"], "binding_digest" => $binding["record_digest"], "manifestation_id" => $binding["manifestation_id"], "occupancy_generation" => $binding["occupancy_generation"]];
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->acceptances) && !mkdir($this->acceptances, 0770, true) && !is_dir($this->acceptances)) throw new \RuntimeException("F198_SENATE_CONFIRMATION_ACCEPTANCE_FAILED");
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record));
        $path = $this->acceptances . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "F199_SENATE_CONFIRMATION_ACCEPTANCE_CONFLICT");
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException("F199_SENATE_CONFIRMATION_ACCEPTANCE_CONFLICT");
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException("F198_SENATE_CONFIRMATION_ACCEPTANCE_FAILED");
        }
        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record["record_digest"] ?? null;
        unset($record["record_digest"]);
        return is_string($digest) && hash_equals($digest, hash("sha256", CanonicalJson::encode($record)));
    }
}
