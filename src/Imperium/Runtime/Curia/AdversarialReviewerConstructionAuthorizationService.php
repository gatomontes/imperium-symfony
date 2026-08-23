<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerConstructionAuthorizationService
{
    private string $caseDirectory;
    private string $inboxDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->caseDirectory =
            $projectDir . "/var/imperium/mastermason/activation-cases";
        $this->inboxDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/inbox/adversarial-reviewer-construction-authorizations";
    }

    public function authorize(string $caseId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-provisioning-[a-f0-9]{20}$/',
                $caseId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C112_ADVERSARIAL_REVIEWER_CASE_ID_INVALID",
            );
        }
        $case = $this->read(
            $this->caseDirectory . "/" . $caseId . ".json",
            "C113_ADVERSARIAL_REVIEWER_CASE_ABSENT",
        );
        if (
            !$this->digestMatches($case) ||
            "imperium.foundry-adversarial-reviewer-provisioning-case/v1" !==
                ($case["schema"] ?? null) ||
            $caseId !== ($case["case_id"] ?? null) ||
            "BLOCKED_PENDING_ADVERSARIAL_REVIEWER_PERSONA" !==
                ($case["status"] ?? null) ||
            "foundry.reviewer.adversarial" !==
                ($case["target_seat"]["seat"] ?? null) ||
            true !== ($case["persona_construction_required"] ?? null) ||
            false !== ($case["mission_persona_selection_required"] ?? null) ||
            false !== ($case["construction_authority"] ?? null) ||
            false !== ($case["commission_authority"] ?? null) ||
            false !== ($case["review_authority"] ?? null) ||
            false !== ($case["spawning_authority"] ?? null) ||
            false !== ($case["seat_binding_authority"] ?? null) ||
            false !== ($case["admission_authority"] ?? null) ||
            false !== ($case["execution_authority"] ?? null) ||
            true !== ($case["sealed"] ?? null)
        ) {
            throw new \RuntimeException(
                "C114_ADVERSARIAL_REVIEWER_CASE_INVALID",
            );
        }

        $actId =
            "adversarial-reviewer-construction-authorization-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $caseId,
                        $case["record_digest"],
                        "imperator-development-root",
                    ]),
                ),
                0,
                20,
            );
        $act = [
            "schema" =>
                "imperium.imperator-adversarial-reviewer-construction-authorization/v1",
            "kind" =>
                "EXACT_ADVERSARIAL_REVIEWER_PERSONA_CONSTRUCTION_AUTHORIZATION",
            "act_id" => $actId,
            "instance_id" => $case["instance_id"],
            "actor" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "authority_basis" => "explicit-imperator-directive",
            "source_case_id" => $caseId,
            "source_case_digest" => $case["record_digest"],
            "source_demand_id" => $case["source_demand_id"],
            "source_demand_digest" => $case["source_demand_digest"],
            "candidate_id" => $case["candidate_id"],
            "candidate_digest" => $case["candidate_digest"],
            "persona_specification_id" =>
                $case["persona_specification_id"] ?? null,
            "persona_specification_digest" =>
                $case["persona_specification_digest"] ?? null,
            "persona_specification_version" =>
                $case["persona_specification_version"] ?? null,
            "specification_supersedes" =>
                $case["specification_supersedes"] ?? null,
            "specification_revision_basis" =>
                $case["specification_revision_basis"] ?? null,
            "dispatch_kind" => $case["dispatch_kind"] ?? null,
            "superseded_commissions" => $case["superseded_commissions"] ?? null,
            "target_seat" => $case["target_seat"],
            "profile_source" => $case["profile_source"],
            "disposition" =>
                "AUTHORIZED_FOR_EXACT_REVIEWER_PERSONA_CONSTRUCTION",
            "construction_authority" => true,
            "construction_authority_exercisable" => false,
            "commission_authority" => false,
            "persona_selection_authority" => false,
            "profile_approval_authority" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $act["record_digest"] = hash("sha256", CanonicalJson::encode($act));
        $deliveryId =
            "adversarial-reviewer-construction-delivery-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $actId,
                        $act["record_digest"],
                        "foundry.artificer",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($deliveryId, [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-construction-authorization-delivery/v1",
            "delivery_id" => $deliveryId,
            "office" => "foundry",
            "target" => "foundry.artificer",
            "instance_id" => $case["instance_id"],
            "source_case_id" => $caseId,
            "source_case_digest" => $case["record_digest"],
            "authorization_act_id" => $actId,
            "authorization_act_digest" => $act["record_digest"],
            "status" => "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "construction_authority" => true,
            "construction_authority_exercisable" => false,
            "commission_authority" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "authorization_act" => $act,
        ]);
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record["record_digest"] ?? null;
        unset($record["record_digest"]);
        return is_string($digest) &&
            hash_equals(
                $digest,
                hash("sha256", CanonicalJson::encode($record)),
            );
    }

    private function persist(string $id, array $delivery): array
    {
        if (
            !is_dir($this->inboxDirectory) &&
            !mkdir($this->inboxDirectory, 0770, true) &&
            !is_dir($this->inboxDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry Adversarial Reviewer construction-authorization inbox cannot be created.",
            );
        }
        $delivery["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($delivery),
        );
        $path = $this->inboxDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "C115_ADVERSARIAL_REVIEWER_DELIVERY_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($delivery)
            ) {
                throw new \RuntimeException(
                    "C116_ADVERSARIAL_REVIEWER_DELIVERY_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $delivery,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($temporary, $path)
        ) {
            @unlink($temporary);
            throw new \RuntimeException(
                "Adversarial Reviewer construction authorization cannot be delivered atomically.",
            );
        }
        return $delivery;
    }
}
