<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerConstructionAuthorizationAcceptanceService
{
    private string $inboxDirectory;
    private string $caseDirectory;
    private string $occupancyDirectory;
    private string $acceptanceDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->inboxDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/inbox/adversarial-reviewer-construction-authorizations";
        $this->caseDirectory =
            $projectDir . "/var/imperium/mastermason/activation-cases";
        $this->occupancyDirectory =
            $projectDir . "/var/imperium/offices/foundry/occupancy";
        $this->acceptanceDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-construction-acceptances";
    }

    public function accept(string $deliveryId, string $bindingId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-construction-delivery-[a-f0-9]{20}$/',
                $deliveryId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F100_ADVERSARIAL_REVIEWER_DELIVERY_ID_INVALID",
            );
        }
        if (
            !preg_match(
                '/^foundry-artificer-binding-[a-f0-9]{20}$/',
                $bindingId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F101_ARTIFICER_BINDING_ID_INVALID",
            );
        }
        $delivery = $this->read(
            $this->inboxDirectory . "/" . $deliveryId . ".json",
            "F102_ADVERSARIAL_REVIEWER_DELIVERY_ABSENT",
        );
        $act = $delivery["authorization_act"] ?? null;
        if (
            !is_array($act) ||
            !$this->digestMatches($delivery) ||
            !$this->digestMatches($act) ||
            "imperium.foundry-adversarial-reviewer-construction-authorization-delivery/v1" !==
                ($delivery["schema"] ?? null) ||
            $deliveryId !== ($delivery["delivery_id"] ?? null) ||
            "foundry" !== ($delivery["office"] ?? null) ||
            "foundry.artificer" !== ($delivery["target"] ?? null) ||
            "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE" !==
                ($delivery["status"] ?? null) ||
            null !== ($delivery["recipient_acceptance"] ?? null) ||
            true !== ($delivery["construction_authority"] ?? null) ||
            false !==
                ($delivery["construction_authority_exercisable"] ?? null) ||
            ($delivery["authorization_act_id"] ?? null) !==
                ($act["act_id"] ?? null) ||
            ($delivery["authorization_act_digest"] ?? null) !==
                ($act["record_digest"] ?? null) ||
            "imperium.imperator-adversarial-reviewer-construction-authorization/v1" !==
                ($act["schema"] ?? null) ||
            "EXACT_ADVERSARIAL_REVIEWER_PERSONA_CONSTRUCTION_AUTHORIZATION" !==
                ($act["kind"] ?? null) ||
            "AUTHORIZED_FOR_EXACT_REVIEWER_PERSONA_CONSTRUCTION" !==
                ($act["disposition"] ?? null) ||
            "foundry.reviewer.adversarial" !==
                ($act["target_seat"]["seat"] ?? null) ||
            true !== ($act["construction_authority"] ?? null) ||
            false !== ($act["construction_authority_exercisable"] ?? null) ||
            $this->hasDownstreamAuthority($delivery) ||
            $this->hasDownstreamAuthority($act)
        ) {
            throw new \RuntimeException(
                "F103_ADVERSARIAL_REVIEWER_DELIVERY_INVALID",
            );
        }

        $caseId = $delivery["source_case_id"] ?? null;
        $case = is_string($caseId)
            ? $this->read(
                $this->caseDirectory . "/" . $caseId . ".json",
                "F104_ADVERSARIAL_REVIEWER_CASE_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($case) ||
            ($delivery["source_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($act["source_case_id"] ?? null) !== $caseId ||
            ($act["source_case_digest"] ?? null) !==
                ($case["record_digest"] ?? null) ||
            ($delivery["instance_id"] ?? null) !==
                ($case["instance_id"] ?? null) ||
            ($act["candidate_id"] ?? null) !==
                ($case["candidate_id"] ?? null) ||
            ($act["candidate_digest"] ?? null) !==
                ($case["candidate_digest"] ?? null) ||
            CanonicalJson::encode($act["target_seat"] ?? null) !==
                CanonicalJson::encode($case["target_seat"] ?? null) ||
            "BLOCKED_PENDING_ADVERSARIAL_REVIEWER_PERSONA" !==
                ($case["status"] ?? null) ||
            true !== ($case["sealed"] ?? null) ||
            false !== ($case["construction_authority"] ?? null) ||
            $this->hasDownstreamAuthority($case)
        ) {
            throw new \RuntimeException(
                "F104_ADVERSARIAL_REVIEWER_CASE_CHANGED",
            );
        }

        $binding = $this->read(
            $this->occupancyDirectory . "/" . $bindingId . ".json",
            "F105_ARTIFICER_BINDING_ABSENT",
        );
        if (
            !$this->digestMatches($binding) ||
            "imperium.foundry-artificer-occupancy/v1" !==
                ($binding["schema"] ?? null) ||
            "foundry" !== ($binding["office"] ?? null) ||
            "foundry.artificer" !== ($binding["seat"] ?? null) ||
            "ACTIVE" !== ($binding["status"] ?? null) ||
            true !== ($binding["binding_atomic"] ?? null) ||
            ($delivery["instance_id"] ?? null) !==
                ($binding["instance_id"] ?? null) ||
            true === ($binding["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("F106_ARTIFICER_BINDING_INVALID");
        }

        $id =
            "foundry-adversarial-reviewer-construction-acceptance-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $deliveryId,
                        $delivery["record_digest"],
                        $bindingId,
                        $binding["record_digest"],
                        $caseId,
                        $case["record_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-construction-authorization-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => $delivery["instance_id"],
            "delivery_id" => $deliveryId,
            "delivery_digest" => $delivery["record_digest"],
            "authorization_act_id" => $act["act_id"],
            "authorization_act_digest" => $act["record_digest"],
            "source_case_id" => $caseId,
            "source_case_digest" => $case["record_digest"],
            "candidate_id" => $case["candidate_id"],
            "candidate_digest" => $case["candidate_digest"],
            "target_seat" => $case["target_seat"],
            "profile_source" => $case["profile_source"],
            "binding_id" => $bindingId,
            "binding_digest" => $binding["record_digest"],
            "actor" => [
                "seat" => "foundry.artificer",
                "manifestation_id" => $binding["manifestation_id"],
                "occupancy_generation" => $binding["occupancy_generation"],
            ],
            "disposition" =>
                "ACCEPTED_FOR_EXACT_ADVERSARIAL_REVIEWER_PERSONA_CONSTRUCTION",
            "recipient_acceptance" => true,
            "construction_authority" => true,
            "construction_authority_exercisable" => true,
            "commission_authority" => false,
            "persona_selection_authority" => false,
            "profile_approval_authority" => false,
            "review_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ]);
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (
            [
                "commission_authority",
                "persona_selection_authority",
                "profile_approval_authority",
                "review_authority",
                "spawning_authority",
                "seat_binding_authority",
                "admission_authority",
                "candidate_approval_authority",
                "execution_authority",
            ]
            as $key
        ) {
            if (true === ($record[$key] ?? false)) {
                return true;
            }
        }
        return false;
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
    private function persist(string $id, array $acceptance): array
    {
        if (
            !is_dir($this->acceptanceDirectory) &&
            !mkdir($this->acceptanceDirectory, 0770, true) &&
            !is_dir($this->acceptanceDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry Adversarial Reviewer construction-acceptance directory cannot be created.",
            );
        }
        $acceptance["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($acceptance),
        );
        $path = $this->acceptanceDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F107_ADVERSARIAL_REVIEWER_ACCEPTANCE_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($acceptance)
            ) {
                throw new \RuntimeException(
                    "F108_ADVERSARIAL_REVIEWER_ACCEPTANCE_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        foreach (
            glob(
                $this->acceptanceDirectory .
                    "/foundry-adversarial-reviewer-construction-acceptance-*.json",
            ) ?:
            []
            as $existingPath
        ) {
            $existing = $this->read(
                $existingPath,
                "F109_ADVERSARIAL_REVIEWER_DELIVERY_ALREADY_DISPOSED",
            );
            if (
                ($acceptance["delivery_id"] ?? null) ===
                ($existing["delivery_id"] ?? null)
            ) {
                throw new \RuntimeException(
                    "F109_ADVERSARIAL_REVIEWER_DELIVERY_ALREADY_DISPOSED",
                );
            }
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $acceptance,
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
                "Adversarial Reviewer construction authorization acceptance cannot be committed atomically.",
            );
        }
        return $acceptance;
    }
}
