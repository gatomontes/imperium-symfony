<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerProvisioningCaseService
{
    private string $demandDirectory;
    private string $caseDirectory;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->demandDirectory =
            $projectDir . "/var/imperium/mastermason/spawning-requests";
        $this->caseDirectory =
            $projectDir . "/var/imperium/mastermason/activation-cases";
    }
    public function open(string $demandId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-reviewer-demand-[a-f0-9]{20}$/',
                $demandId,
            )
        ) {
            throw new \InvalidArgumentException(
                "M75_ADVERSARIAL_DEMAND_ID_INVALID",
            );
        }
        $d = $this->read(
            $this->demandDirectory . "/" . $demandId . ".json",
            "M76_ADVERSARIAL_DEMAND_ABSENT",
        );
        if (
            !$this->digestMatches($d) ||
            "imperium.foundry-adversarial-reviewer-demand/v1" !==
                ($d["schema"] ?? null) ||
            $demandId !== ($d["demand_id"] ?? null) ||
            "mastermason" !== ($d["recipient"] ?? null) ||
            "foundry.reviewer.adversarial" !==
                ($d["required_seat"]["seat"] ?? null) ||
            "PENDING_EXACT_ADVERSARIAL_REVIEWER_OCCUPATION" !==
                ($d["status"] ?? null) ||
            true !== ($d["sealed"] ?? null) ||
            false !== ($d["review_authority"] ?? null) ||
            true === ($d["spawning_authority"] ?? null) ||
            true === ($d["seat_binding_authority"] ?? null) ||
            true === ($d["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("M77_ADVERSARIAL_DEMAND_INVALID");
        }
        $id =
            "adversarial-reviewer-provisioning-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $demandId,
                        $d["record_digest"],
                        $d["required_seat"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-provisioning-case/v1",
            "case_id" => $id,
            "source_demand_id" => $demandId,
            "source_demand_digest" => $d["record_digest"],
            "instance_id" => $d["instance_id"],
            "coordinator" => "mastermason",
            "target_seat" => $d["required_seat"],
            "candidate_id" => $d["candidate_id"],
            "candidate_digest" => $d["candidate_digest"],
            "foundry_review_id" => $d["foundry_review_id"],
            "foundry_review_digest" => $d["foundry_review_digest"],
            "persona_specification_id" =>
                $d["persona_specification_id"] ?? null,
            "persona_specification_digest" =>
                $d["persona_specification_digest"] ?? null,
            "persona_specification_version" =>
                $d["persona_specification_version"] ?? null,
            "specification_supersedes" =>
                $d["specification_supersedes"] ?? null,
            "specification_revision_basis" =>
                $d["specification_revision_basis"] ?? null,
            "dispatch_kind" => $d["dispatch_kind"] ?? null,
            "superseded_commissions" => $d["superseded_commissions"] ?? null,
            "review_scope" => $d["review_scope"],
            "independence_requirements" => $d["independence_requirements"],
            "required_artifacts" => [
                "admitted Adversarial Reviewer Persona",
                "versioned current/active Adversarial Reviewer Profile and lifecycle attestations",
                "generic Officer substrate qualification by Conscription",
                "candidate-bound demand Seat occupation",
            ],
            "profile_source" =>
                "offices/foundry/profile-reviewer-adversarial.md",
            "persona_source" => null,
            "status" => "BLOCKED_PENDING_ADVERSARIAL_REVIEWER_PERSONA",
            "mission_persona_selection_required" => false,
            "persona_construction_required" => true,
            "construction_authority" => false,
            "commission_authority" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function read(string $p, string $e): array
    {
        if (!is_file($p)) {
            throw new \RuntimeException($e);
        }
        return json_decode(
            (string) file_get_contents($p),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
    private function digestMatches(array $r): bool
    {
        $x = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($x) &&
            hash_equals($x, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->caseDirectory) &&
            !mkdir($this->caseDirectory, 0770, true) &&
            !is_dir($this->caseDirectory)
        ) {
            throw new \RuntimeException(
                "MasterMason activation-case directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->caseDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read(
                $p,
                "M78_ADVERSARIAL_PROVISIONING_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "M78_ADVERSARIAL_PROVISIONING_REPLAY_CONFLICT",
                );
            }
            return $old;
        }
        $tmp = $p . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $tmp,
                    json_encode(
                        $r,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($tmp, $p)
        ) {
            @unlink($tmp);
            throw new \RuntimeException(
                "Adversarial Reviewer provisioning case cannot be committed atomically.",
            );
        }
        return $r;
    }
}
