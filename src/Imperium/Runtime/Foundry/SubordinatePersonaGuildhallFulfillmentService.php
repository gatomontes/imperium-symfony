<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaGuildhallFulfillmentService
{
    private string $acceptances;
    private string $records;
    private string $candidates;
    private string $outbox;

    public function __construct(#[Autowire("%kernel.project_dir%")] string $projectDir)
    {
        $this->acceptances = $projectDir . "/var/imperium/offices/foundry/senate-confirmation-acceptances";
        $this->records = $projectDir . "/var/imperium/offices/senate/outbox/confirmation-records";
        $this->candidates = $projectDir . "/var/imperium/offices/foundry/subordinate-persona-candidates";
        $this->outbox = $projectDir . "/var/imperium/offices/guildhall/inbox/subordinate-persona-fulfillments";
    }

    public function fulfill(string $acceptanceId): array
    {
        if (!preg_match('/^foundry-senate-confirmation-acceptance-[a-f0-9]{20}$/', $acceptanceId)) throw new \InvalidArgumentException("F200_CONFIRMATION_ACCEPTANCE_ID_INVALID");
        $acceptance = $this->read($this->acceptances . "/" . $acceptanceId . ".json", "F201_GUILDHALL_FULFILLMENT_CHAIN_INVALID");
        $record = $this->read($this->records . "/" . ($acceptance["confirmation_record_id"] ?? "") . ".json", "F201_GUILDHALL_FULFILLMENT_CHAIN_INVALID");
        $candidate = $this->read($this->candidates . "/" . ($acceptance["candidate_id"] ?? "") . ".json", "F201_GUILDHALL_FULFILLMENT_CHAIN_INVALID");
        $commissionId = $acceptance["originating_guildhall_commission_id"] ?? null;
        $commissionDigest = $acceptance["originating_guildhall_commission_digest"] ?? null;
        if (!$this->ok($acceptance) || !$this->ok($record) || !$this->ok($candidate)
            || "SENATE_CONFIRMATION_RECORD_ACCEPTED_PENDING_GUILDHALL_FULFILLMENT" !== ($acceptance["status"] ?? null)
            || true !== ($acceptance["guildhall_fulfillment_ready"] ?? null) || "CONFIRMED" !== ($acceptance["senate_disposition"] ?? null)
            || !is_string($commissionId) || !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $commissionId) || !is_string($commissionDigest) || "" === trim($commissionDigest)
            || $commissionId !== ($record["originating_guildhall_commission_id"] ?? null) || $commissionDigest !== ($record["originating_guildhall_commission_digest"] ?? null)
            || $commissionId !== ($candidate["originating_guildhall_commission_id"] ?? null) || $commissionDigest !== ($candidate["originating_guildhall_commission_digest"] ?? null)
            || ($acceptance["confirmation_record_digest"] ?? null) !== ($record["record_digest"] ?? null)
            || ($acceptance["candidate_digest"] ?? null) !== ($candidate["record_digest"] ?? null)
            || true === ($acceptance["admission_authority"] ?? null) || true === ($acceptance["execution_authority"] ?? null)) throw new \RuntimeException("F201_GUILDHALL_FULFILLMENT_CHAIN_INVALID");
        $id = "foundry-guildhall-persona-fulfillment-" . substr(hash("sha256", CanonicalJson::encode([$acceptanceId, $acceptance["record_digest"], $commissionId, $candidate["record_digest"]])), 0, 20);
        return $this->save($id, [
            "schema" => "imperium.foundry-guildhall-persona-fulfillment/v1", "fulfillment_id" => $id, "instance_id" => $acceptance["instance_id"],
            "sender" => $acceptance["artificer"], "recipient" => ["office" => "guildhall", "seat" => "guildhall.guildmaster"],
            "foundry_confirmation_acceptance_id" => $acceptanceId, "foundry_confirmation_acceptance_digest" => $acceptance["record_digest"],
            "senate_confirmation_record_id" => $record["confirmation_record_id"], "senate_confirmation_record_digest" => $record["record_digest"],
            "candidate_id" => $candidate["candidate_id"], "candidate_digest" => $candidate["record_digest"], "persona_name" => $candidate["persona_name"], "persona_specification_version" => $candidate["persona_specification_version"], "persona" => $candidate["persona"],
            "originating_guildhall_commission_id" => $commissionId, "originating_guildhall_commission_digest" => $commissionDigest, "review_target_lineage" => $acceptance["review_target_lineage"],
            "commission_fulfilled" => true, "candidate_substituted" => false, "status" => "FULFILLED_PENDING_GUILDHALL_ACCEPTANCE", "recipient_acceptance" => null,
            "admission_authority" => false, "execution_authority" => false, "sealed" => true,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function ok(array $record): bool { $digest = $record["record_digest"] ?? null; unset($record["record_digest"]); return is_string($digest) && hash_equals($digest, hash("sha256", CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array { if (!is_dir($this->outbox) && !mkdir($this->outbox, 0770, true) && !is_dir($this->outbox)) throw new \RuntimeException("F202_GUILDHALL_FULFILLMENT_FAILED"); $record["record_digest"] = hash("sha256", CanonicalJson::encode($record)); $path = $this->outbox . "/" . $id . ".json"; if (is_file($path)) { $old=$this->read($path,"F203_GUILDHALL_FULFILLMENT_CONFLICT"); if (CanonicalJson::encode($old)!==CanonicalJson::encode($record)) throw new \RuntimeException("F203_GUILDHALL_FULFILLMENT_CONFLICT"); return $old; } $tmp=$path.".tmp.".bin2hex(random_bytes(6)); if(false===file_put_contents($tmp,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)||!rename($tmp,$path)){@unlink($tmp);throw new \RuntimeException("F202_GUILDHALL_FULFILLMENT_FAILED");} return $record; }
}
