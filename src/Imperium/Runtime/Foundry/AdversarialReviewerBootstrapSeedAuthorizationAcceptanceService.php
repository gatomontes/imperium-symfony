<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerBootstrapSeedAuthorizationAcceptanceService
{
    private string $inbox;
    private string $candidates;
    private string $occupancy;
    private string $acceptances;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->inbox =
            $projectDir .
            "/var/imperium/offices/foundry/inbox/adversarial-reviewer-bootstrap-seed-authorizations";
        $this->candidates =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates";
        $this->occupancy =
            $projectDir . "/var/imperium/offices/foundry/occupancy";
        $this->acceptances =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-acceptances";
    }

    public function accept(string $deliveryId, string $bindingId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-bootstrap-seed-authorization-delivery-[a-f0-9]{20}$/',
                $deliveryId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F125_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_DELIVERY_ID_INVALID",
            );
        }
        if (
            !preg_match(
                '/^foundry-artificer-binding-[a-f0-9]{20}$/',
                $bindingId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F126_ARTIFICER_BINDING_ID_INVALID",
            );
        }

        $delivery = $this->read(
            $this->inbox . "/" . $deliveryId . ".json",
            "F127_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_DELIVERY_ABSENT",
        );
        $act = $delivery["authorization_act"] ?? null;
        $boundary = is_array($act)
            ? $act["bootstrap_seed_boundary"] ?? null
            : null;
        if (
            !is_array($act) ||
            !is_array($boundary) ||
            !$this->digestMatches($delivery) ||
            !$this->digestMatches($act) ||
            "imperium.foundry-adversarial-reviewer-bootstrap-seed-authorization-delivery/v1" !==
                ($delivery["schema"] ?? null) ||
            $deliveryId !== ($delivery["delivery_id"] ?? null) ||
            "foundry" !== ($delivery["office"] ?? null) ||
            "foundry.artificer" !== ($delivery["target"] ?? null) ||
            "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE" !==
                ($delivery["status"] ?? null) ||
            null !== ($delivery["recipient_acceptance"] ?? null) ||
            true !== ($delivery["sealed"] ?? null) ||
            true !== ($delivery["bootstrap_seed_authority"] ?? null) ||
            false !==
                ($delivery["bootstrap_seed_authority_exercisable"] ?? null) ||
            ($delivery["authorization_act_id"] ?? null) !==
                ($act["act_id"] ?? null) ||
            ($delivery["authorization_act_digest"] ?? null) !==
                ($act["record_digest"] ?? null) ||
            "imperium.imperator-adversarial-reviewer-bootstrap-seed-authorization/v1" !==
                ($act["schema"] ?? null) ||
            "EXACT_INITIAL_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_AUTHORIZATION" !==
                ($act["kind"] ?? null) ||
            "imperator" !== ($act["actor"]["kind"] ?? null) ||
            "imperator-development-root" !== ($act["actor"]["id"] ?? null) ||
            "explicit-imperator-directive" !==
                ($act["authority_basis"] ?? null) ||
            "foundry.adversarial-reviewer" !== ($act["persona_id"] ?? null) ||
            "1.0.0" !== ($act["persona_version"] ?? null) ||
            "AUTHORIZED_FOR_EXACT_INITIAL_REVIEWER_BOOTSTRAP_SEED_PROCESSING" !==
                ($act["disposition"] ?? null) ||
            true !== ($act["bootstrap_seed_authority"] ?? null) ||
            false !== ($act["bootstrap_seed_authority_exercisable"] ?? null) ||
            !$this->validBoundary($boundary) ||
            $this->hasDownstreamAuthority($delivery) ||
            $this->hasDownstreamAuthority($act)
        ) {
            throw new \RuntimeException(
                "F128_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_DELIVERY_INVALID",
            );
        }

        $candidateId = $delivery["persona_candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->candidates . "/" . $candidateId . ".json",
                "F129_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($candidate) ||
            $candidateId !== ($candidate["persona_candidate_id"] ?? null) ||
            ($delivery["persona_candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($act["persona_candidate_id"] ?? null) !== $candidateId ||
            ($act["persona_candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($act["instance_id"] ?? null) !==
                ($candidate["instance_id"] ?? null) ||
            ($act["construction_acceptance_id"] ?? null) !==
                ($candidate["construction_acceptance_id"] ?? null) ||
            ($act["construction_acceptance_digest"] ?? null) !==
                ($candidate["construction_acceptance_digest"] ?? null) ||
            CanonicalJson::encode($act["authorized_review_target"] ?? null) !==
                CanonicalJson::encode(
                    $candidate["authorized_review_target"] ?? null,
                ) ||
            CanonicalJson::encode($act["design_basis"] ?? null) !==
                CanonicalJson::encode(
                    $candidate["sources"]["design_basis"] ?? null,
                ) ||
            "SEALED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null)
        ) {
            throw new \RuntimeException(
                "F129_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            );
        }

        $binding = $this->read(
            $this->occupancy . "/" . $bindingId . ".json",
            "F130_ARTIFICER_BINDING_ABSENT",
        );
        if (
            !$this->digestMatches($binding) ||
            "imperium.foundry-artificer-occupancy/v1" !==
                ($binding["schema"] ?? null) ||
            "foundry" !== ($binding["office"] ?? null) ||
            "foundry.artificer" !== ($binding["seat"] ?? null) ||
            "ACTIVE" !== ($binding["status"] ?? null) ||
            true !== ($binding["binding_atomic"] ?? null) ||
            ($candidate["instance_id"] ?? null) !==
                ($binding["instance_id"] ?? null) ||
            true === ($binding["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("F131_ARTIFICER_BINDING_INVALID");
        }

        $id =
            "foundry-adversarial-reviewer-bootstrap-seed-acceptance-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $deliveryId,
                        $delivery["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        $bindingId,
                        $binding["record_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-authorization-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => $candidate["instance_id"],
            "delivery_id" => $deliveryId,
            "delivery_digest" => $delivery["record_digest"],
            "authorization_act_id" => $act["act_id"],
            "authorization_act_digest" => $act["record_digest"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "construction_acceptance_id" =>
                $candidate["construction_acceptance_id"],
            "construction_acceptance_digest" =>
                $candidate["construction_acceptance_digest"],
            "authorized_review_target" =>
                $candidate["authorized_review_target"],
            "design_basis" => $candidate["sources"]["design_basis"],
            "bootstrap_seed_boundary" => $boundary,
            "binding_id" => $bindingId,
            "binding_digest" => $binding["record_digest"],
            "actor" => [
                "seat" => "foundry.artificer",
                "manifestation_id" => $binding["manifestation_id"],
                "occupancy_generation" => $binding["occupancy_generation"],
            ],
            "disposition" =>
                "ACCEPTED_FOR_EXACT_INITIAL_REVIEWER_BOOTSTRAP_SEED_PROCESSING",
            "recipient_acceptance" => true,
            "bootstrap_seed_authority" => true,
            "bootstrap_seed_authority_exercisable" => true,
            "review_findings_authority" => false,
            "production_approval" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ]);
    }

    private function validBoundary(array $boundary): bool
    {
        foreach (
            [
                "initial_version_only",
                "exact_candidate_only",
                "self_review_prohibited",
                "successor_versions_require_ordinary_independent_review",
                "candidate_revision_terminates_authority",
                "identity_or_digest_change_terminates_authority",
            ]
            as $key
        ) {
            if (true !== ($boundary[$key] ?? null)) {
                return false;
            }
        }
        foreach (
            [
                "predecessor_review_required",
                "general_review_waiver",
                "general_precedent",
            ]
            as $key
        ) {
            if (false !== ($boundary[$key] ?? null)) {
                return false;
            }
        }
        return true;
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (
            [
                "review_findings_authority",
                "production_approval",
                "profile_approval_authority",
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

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->acceptances) &&
            !mkdir($this->acceptances, 0770, true) &&
            !is_dir($this->acceptances)
        ) {
            throw new \RuntimeException(
                "Foundry bootstrap-seed acceptance directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->acceptances . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F132_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ACCEPTANCE_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F133_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ACCEPTANCE_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        foreach (
            glob(
                $this->acceptances .
                    "/foundry-adversarial-reviewer-bootstrap-seed-acceptance-*.json",
            ) ?:
            []
            as $existingPath
        ) {
            $existing = $this->read(
                $existingPath,
                "F134_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_DELIVERY_ALREADY_DISPOSED",
            );
            if (
                ($record["delivery_id"] ?? null) ===
                ($existing["delivery_id"] ?? null)
            ) {
                throw new \RuntimeException(
                    "F134_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_DELIVERY_ALREADY_DISPOSED",
                );
            }
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $record,
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
                "Bootstrap-seed authorization acceptance cannot be committed atomically.",
            );
        }
        return $record;
    }
}
