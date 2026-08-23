<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerBootstrapSeedAdmissionDeliveryService
{
    private string $approvals;
    private string $candidates;
    private string $occupancy;
    private string $inbox;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->approvals =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-production-approvals";
        $this->candidates =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates";
        $this->occupancy =
            $projectDir . "/var/imperium/offices/foundry/occupancy";
        $this->inbox =
            $projectDir .
            "/var/imperium/offices/garrison/inbox/adversarial-reviewer-bootstrap-seed-admissions";
    }

    public function deliver(string $approvalId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-bootstrap-seed-production-approval-[a-f0-9]{20}$/',
                $approvalId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F143_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_PRODUCTION_APPROVAL_ID_INVALID",
            );
        }
        $approval = $this->read(
            $this->approvals . "/" . $approvalId . ".json",
            "F144_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_PRODUCTION_APPROVAL_ABSENT",
        );
        if (
            !$this->digestMatches($approval) ||
            "imperium.foundry-adversarial-reviewer-bootstrap-seed-production-approval/v1" !==
                ($approval["schema"] ?? null) ||
            $approvalId !== ($approval["production_approval_id"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($approval["persona_id"] ?? null) ||
            "1.0.0" !== ($approval["persona_version"] ?? null) ||
            "foundry.artificer" !== ($approval["actor"]["seat"] ?? null) ||
            "APPROVED_AS_EXACT_INITIAL_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED" !==
                ($approval["disposition"] ?? null) ||
            "APPROVED_PENDING_GARRISON_ADMISSION" !==
                ($approval["status"] ?? null) ||
            true !== ($approval["bootstrap_seed_authority_consumed"] ?? null) ||
            true !== ($approval["production_approval"] ?? null) ||
            true !== ($approval["sealed"] ?? null) ||
            !$this->validBoundary(
                $approval["bootstrap_seed_boundary"] ?? null,
            ) ||
            $this->hasDownstreamAuthority($approval)
        ) {
            throw new \RuntimeException(
                "F145_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_PRODUCTION_APPROVAL_INVALID",
            );
        }

        $candidateId = $approval["persona_candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->candidates . "/" . $candidateId . ".json",
                "F146_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($candidate) ||
            ($approval["persona_candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            $candidateId !== ($candidate["persona_candidate_id"] ?? null) ||
            ($approval["instance_id"] ?? null) !==
                ($candidate["instance_id"] ?? null) ||
            ($approval["persona_id"] ?? null) !==
                ($candidate["persona_id"] ?? null) ||
            ($approval["persona_version"] ?? null) !==
                ($candidate["persona_version"] ?? null) ||
            ($approval["construction_acceptance_id"] ?? null) !==
                ($candidate["construction_acceptance_id"] ?? null) ||
            ($approval["construction_acceptance_digest"] ?? null) !==
                ($candidate["construction_acceptance_digest"] ?? null) ||
            CanonicalJson::encode(
                $approval["authorized_review_target"] ?? null,
            ) !==
                CanonicalJson::encode(
                    $candidate["authorized_review_target"] ?? null,
                ) ||
            CanonicalJson::encode($approval["design_basis"] ?? null) !==
                CanonicalJson::encode(
                    $candidate["sources"]["design_basis"] ?? null,
                ) ||
            true !== ($candidate["construction_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null)
        ) {
            throw new \RuntimeException(
                "F146_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            );
        }

        $bindingId = $approval["binding_id"] ?? null;
        $binding = is_string($bindingId)
            ? $this->read(
                $this->occupancy . "/" . $bindingId . ".json",
                "F147_ARTIFICER_BINDING_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($binding) ||
            ($approval["binding_digest"] ?? null) !==
                ($binding["record_digest"] ?? null) ||
            "imperium.foundry-artificer-occupancy/v1" !==
                ($binding["schema"] ?? null) ||
            "foundry.artificer" !== ($binding["seat"] ?? null) ||
            "ACTIVE" !== ($binding["status"] ?? null) ||
            ($approval["instance_id"] ?? null) !==
                ($binding["instance_id"] ?? null) ||
            ($approval["actor"]["manifestation_id"] ?? null) !==
                ($binding["manifestation_id"] ?? null) ||
            ($approval["actor"]["occupancy_generation"] ?? null) !==
                ($binding["occupancy_generation"] ?? null)
        ) {
            throw new \RuntimeException("F147_ARTIFICER_BINDING_CHANGED");
        }

        $id =
            "adversarial-reviewer-bootstrap-seed-admission-delivery-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $approvalId,
                        $approval["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        "garrison.constable",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-bootstrap-seed-admission-delivery/v1",
            "delivery_id" => $id,
            "instance_id" => $approval["instance_id"],
            "sender" => $approval["actor"],
            "recipient" => [
                "office" => "garrison",
                "seat" => "garrison.constable",
            ],
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "bootstrap_seed_acceptance_id" =>
                $approval["bootstrap_seed_acceptance_id"],
            "bootstrap_seed_acceptance_digest" =>
                $approval["bootstrap_seed_acceptance_digest"],
            "authorization_act_id" => $approval["authorization_act_id"],
            "authorization_act_digest" => $approval["authorization_act_digest"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => $candidate["persona_id"],
            "persona_version" => $candidate["persona_version"],
            "persona" => $candidate["persona"],
            "design_basis" => $candidate["sources"]["design_basis"],
            "bootstrap_seed_boundary" => $approval["bootstrap_seed_boundary"],
            "authorized_review_target" =>
                $approval["authorized_review_target"] ?? null,
            "review_target_lineage" =>
                $approval["review_target_lineage"] ?? null,
            "requested_disposition" =>
                "CONSIDER_EXACT_PERSONA_FOR_GARRISON_ADMISSION",
            "status" => "DELIVERED_PENDING_GARRISON_ACCEPTANCE",
            "recipient_acceptance" => null,
            "production_approval" => true,
            "admission_authority" => false,
            "admission_decision" => null,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "review_findings_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function validBoundary(mixed $boundary): bool
    {
        if (!is_array($boundary)) {
            return false;
        }
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
            !is_dir($this->inbox) &&
            !mkdir($this->inbox, 0770, true) &&
            !is_dir($this->inbox)
        ) {
            throw new \RuntimeException(
                "Garrison bootstrap-seed admission inbox cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->inbox . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F148_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_DELIVERY_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F149_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_DELIVERY_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        foreach (
            glob(
                $this->inbox .
                    "/adversarial-reviewer-bootstrap-seed-admission-delivery-*.json",
            ) ?:
            []
            as $existingPath
        ) {
            $existing = $this->read(
                $existingPath,
                "F150_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ALREADY_DELIVERED",
            );
            if (
                ($record["production_approval_id"] ?? null) ===
                ($existing["production_approval_id"] ?? null)
            ) {
                throw new \RuntimeException(
                    "F150_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ALREADY_DELIVERED",
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
                "Garrison bootstrap-seed admission delivery cannot be committed atomically.",
            );
        }
        return $record;
    }
}
