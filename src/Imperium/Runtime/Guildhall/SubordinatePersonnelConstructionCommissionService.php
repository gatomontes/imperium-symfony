<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonnelConstructionCommissionService
{
    private string $inbox;
    private string $foundryInbox;

    public function __construct(#[Autowire("%kernel.project_dir%")] string $projectDir)
    {
        $this->inbox = $projectDir . "/var/imperium/offices/guildhall/inbox/subordinate-personnel-authorizations";
        $this->foundryInbox = $projectDir . "/var/imperium/offices/foundry/inbox/subordinate-construction-commissions";
    }

    public function commission(string $deliveryId): array
    {
        if (!preg_match('/^guildhall-subordinate-personnel-authorization-[a-f0-9]{20}$/', $deliveryId)) throw new \InvalidArgumentException("G70_PERSONNEL_AUTHORIZATION_ID_INVALID");
        $delivery = $this->read($this->inbox . "/" . $deliveryId . ".json", "G71_PERSONNEL_AUTHORIZATION_ABSENT");
        $act = $delivery["authorization_act"] ?? null;
        if (!is_array($act) || !$this->digestMatches($delivery) || !$this->digestMatches($act)
            || "imperium.guildhall-subordinate-personnel-authorization-delivery/v1" !== ($delivery["schema"] ?? null)
            || "guildhall" !== ($delivery["office"] ?? null) || "guildhall" !== ($delivery["target"] ?? null)
            || "DELIVERED_PENDING_GUILDHALL_COMMISSION" !== ($delivery["status"] ?? null)
            || true !== ($delivery["personnel_commission_authority"] ?? null)
            || true === ($delivery["construction_authority"] ?? null)
            || ($delivery["authorization_act_digest"] ?? null) !== ($act["record_digest"] ?? null)
            || CanonicalJson::encode($delivery["authorized_resolutions"] ?? null) !== CanonicalJson::encode($act["resolutions"] ?? null)) {
            throw new \RuntimeException("G72_PERSONNEL_AUTHORIZATION_INVALID");
        }
        $id = "guildhall-subordinate-construction-commission-" . substr(hash("sha256", CanonicalJson::encode([$deliveryId, $delivery["record_digest"], $delivery["authorized_resolutions"]])), 0, 20);
        return $this->persist($id, [
            "schema" => "imperium.guildhall-subordinate-construction-commission/v1",
            "commission_id" => $id,
            "instance_id" => $delivery["instance_id"],
            "source_authorization_delivery_id" => $deliveryId,
            "source_authorization_delivery_digest" => $delivery["record_digest"],
            "authorization_act_id" => $act["act_id"],
            "authorization_act_digest" => $act["record_digest"],
            "authorized_resolutions" => $delivery["authorized_resolutions"],
            "requester" => ["office" => "guildhall"],
            "recipient" => ["office" => "foundry", "seat" => "foundry.artificer"],
            "status" => "COMMISSIONED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "construction_authority" => true,
            "construction_authority_exercisable" => false,
            "persona_selection_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->foundryInbox) && !mkdir($this->foundryInbox, 0770, true) && !is_dir($this->foundryInbox)) throw new \RuntimeException("G73_CONSTRUCTION_COMMISSION_FAILED");
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record)); $path = $this->foundryInbox . "/" . $id . ".json";
        if (is_file($path)) { $existing = $this->read($path, "G74_CONSTRUCTION_COMMISSION_CONFLICT"); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException("G74_CONSTRUCTION_COMMISSION_CONFLICT"); return $existing; }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException("G73_CONSTRUCTION_COMMISSION_FAILED"); }
        return $record;
    }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record["record_digest"] ?? null; unset($record["record_digest"]); return is_string($digest) && hash_equals($digest, hash("sha256", CanonicalJson::encode($record))); }
}
