<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinateConstructionCaseService
{
    private string $acceptances;
    private string $officeRoot;
    private string $cases;

    public function __construct(#[Autowire("%kernel.project_dir%")] string $projectDir)
    {
        $this->acceptances = $projectDir . "/var/imperium/offices/foundry/subordinate-construction-acceptances";
        $this->officeRoot = $projectDir . "/var/imperium/offices";
        $this->cases = $projectDir . "/var/imperium/offices/foundry/subordinate-construction-cases";
    }

    public function open(string $acceptanceId): array
    {
        if (!preg_match('/^foundry-subordinate-acceptance-[a-f0-9]{20}$/', $acceptanceId)) throw new \InvalidArgumentException("F100_SUBORDINATE_ACCEPTANCE_ID_INVALID");
        $acceptance = $this->read($this->acceptances . "/" . $acceptanceId . ".json", "F101_SUBORDINATE_ACCEPTANCE_ABSENT");
        $references = $acceptance["authorized_resolutions"] ?? null;
        $commissionId = $acceptance["guildhall_commission_id"] ?? null;
        $commissionDigest = $acceptance["guildhall_commission_digest"] ?? null;
        if (!$this->digestMatches($acceptance)
            || "imperium.foundry-subordinate-construction-authorization-acceptance/v1" !== ($acceptance["schema"] ?? null)
            || $acceptanceId !== ($acceptance["acceptance_id"] ?? null)
            || "foundry.artificer" !== ($acceptance["actor"]["seat"] ?? null)
            || "ACCEPTED_FOR_EXACT_SUBORDINATE_CONSTRUCTION" !== ($acceptance["disposition"] ?? null)
            || true !== ($acceptance["recipient_acceptance"] ?? null)
            || true !== ($acceptance["construction_authority"] ?? null)
            || true !== ($acceptance["construction_authority_exercisable"] ?? null)
            || false !== ($acceptance["persona_selection_authority"] ?? null)
            || false !== ($acceptance["profile_approval_authority"] ?? null)
            || false !== ($acceptance["spawning_authority"] ?? null)
            || false !== ($acceptance["seat_binding_authority"] ?? null)
            || false !== ($acceptance["execution_authority"] ?? null)
            || !is_array($references) || [] === $references
            || !is_string($commissionId) || !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $commissionId)
            || !is_string($commissionDigest) || '' === $commissionDigest) throw new \RuntimeException("F102_SUBORDINATE_ACCEPTANCE_INVALID");

        $cases = []; $seen = [];
        foreach ($references as $index => $reference) {
            $resolutionId = $reference["resolution_id"] ?? null; $office = $reference["office"] ?? null;
            if (!is_string($resolutionId) || !in_array($office, ["hagiography", "studium"], true) || isset($seen[$resolutionId])) throw new \RuntimeException("F103_SUBORDINATE_RESOLUTION_SET_INVALID");
            $resolution = $this->read($this->officeRoot . "/" . $office . "/subordinate-resolutions/" . $resolutionId . ".json", "F104_SUBORDINATE_RESOLUTION_ABSENT");
            if (!$this->digestMatches($resolution)
                || "imperium.authorship-subordinate-resolution/v1" !== ($resolution["schema"] ?? null)
                || $resolutionId !== ($resolution["resolution_id"] ?? null) || $office !== ($resolution["office"] ?? null)
                || ($reference["record_digest"] ?? null) !== ($resolution["record_digest"] ?? null)
                || ($acceptance["instance_id"] ?? null) !== ($resolution["instance_id"] ?? null)
                || ($reference["acceptance_id"] ?? null) !== ($resolution["acceptance_id"] ?? null)
                || ($reference["commission_id"] ?? null) !== ($resolution["commission_id"] ?? null)
                || ($reference["subordinate_staff_class"] ?? null) !== ($resolution["subordinate_staff_class"] ?? null)
                || CanonicalJson::encode($reference["required_specializations"] ?? null) !== CanonicalJson::encode($resolution["decision"]["required_specializations"] ?? null)
                || "PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION" !== ($resolution["status"] ?? null)
                || true !== ($resolution["sealed"] ?? null)
                || true === ($resolution["construction_authority"] ?? null) || true === ($resolution["persona_selection_authority"] ?? null)
                || true === ($resolution["profile_approval_authority"] ?? null) || true === ($resolution["spawning_authority"] ?? null)
                || true === ($resolution["seat_binding_authority"] ?? null) || true === ($resolution["execution_authority"] ?? null)) throw new \RuntimeException("F105_SUBORDINATE_RESOLUTION_CHANGED");
            $caseId = "subordinate-construction-case-" . substr(hash("sha256", CanonicalJson::encode([$acceptanceId, $acceptance["record_digest"], $index, $resolutionId, $resolution["record_digest"], $commissionId, $commissionDigest])), 0, 20);
            $cases[] = $this->persist($caseId, [
                "schema" => "imperium.foundry-subordinate-construction-case/v1", "case_id" => $caseId,
                "instance_id" => $acceptance["instance_id"], "queue_position" => $index + 1, "office" => $office,
                "subordinate_staff_class" => $resolution["subordinate_staff_class"], "source_resolution_id" => $resolutionId,
                "source_resolution_digest" => $resolution["record_digest"], "source_acceptance_id" => $resolution["acceptance_id"],
                "source_commission_id" => $resolution["commission_id"], "originating_guildhall_commission_id" => $commissionId,
                "originating_guildhall_commission_digest" => $commissionDigest, "authorization_acceptance_id" => $acceptanceId,
                "authorization_acceptance_digest" => $acceptance["record_digest"], "artificer" => $acceptance["actor"],
                "subordinate_requirements" => $resolution["decision"], "status" => "OPEN_PENDING_PERSONA_SPECIFICATION",
                "construction_authority" => true, "persona_selection_authority" => false, "profile_approval_authority" => false,
                "spawning_authority" => false, "admission_authority" => false, "seat_binding_authority" => false, "execution_authority" => false,
            ]);
            $seen[$resolutionId] = true;
        }
        return ["acceptance_id" => $acceptanceId, "artificer" => $acceptance["actor"], "cases" => $cases];
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->cases) && !mkdir($this->cases, 0770, true) && !is_dir($this->cases)) throw new \RuntimeException("F106_SUBORDINATE_CASE_FAILED");
        $record["record_digest"] = hash("sha256", CanonicalJson::encode($record)); $path = $this->cases . "/" . $id . ".json";
        if (is_file($path)) { $existing = $this->read($path, "F106_SUBORDINATE_CASE_REPLAY_CONFLICT"); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException("F106_SUBORDINATE_CASE_REPLAY_CONFLICT"); return $existing; }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException("F106_SUBORDINATE_CASE_FAILED"); }
        return $record;
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record["record_digest"] ?? null; unset($record["record_digest"]); return is_string($digest) && hash_equals($digest, hash("sha256", CanonicalJson::encode($record))); }
}
