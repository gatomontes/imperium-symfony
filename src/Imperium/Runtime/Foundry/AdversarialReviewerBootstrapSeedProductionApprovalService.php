<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerBootstrapSeedProductionApprovalService
{
    private string $acceptances;
    private string $candidates;
    private string $occupancy;
    private string $approvals;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->acceptances =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-acceptances";
        $this->candidates =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates";
        $this->occupancy =
            $projectDir . "/var/imperium/offices/foundry/occupancy";
        $this->approvals =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-production-approvals";
    }

    public function approve(string $acceptanceId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-reviewer-bootstrap-seed-acceptance-[a-f0-9]{20}$/',
                $acceptanceId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F135_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ACCEPTANCE_ID_INVALID",
            );
        }
        $acceptance = $this->read(
            $this->acceptances . "/" . $acceptanceId . ".json",
            "F136_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ACCEPTANCE_ABSENT",
        );
        $boundary = $acceptance["bootstrap_seed_boundary"] ?? null;
        if (
            !$this->digestMatches($acceptance) ||
            !is_array($boundary) ||
            "imperium.foundry-adversarial-reviewer-bootstrap-seed-authorization-acceptance/v1" !==
                ($acceptance["schema"] ?? null) ||
            $acceptanceId !== ($acceptance["acceptance_id"] ?? null) ||
            "foundry.artificer" !== ($acceptance["actor"]["seat"] ?? null) ||
            "ACCEPTED_FOR_EXACT_INITIAL_REVIEWER_BOOTSTRAP_SEED_PROCESSING" !==
                ($acceptance["disposition"] ?? null) ||
            true !== ($acceptance["recipient_acceptance"] ?? null) ||
            true !== ($acceptance["bootstrap_seed_authority"] ?? null) ||
            true !==
                ($acceptance["bootstrap_seed_authority_exercisable"] ?? null) ||
            !$this->validBoundary($boundary) ||
            $this->hasDownstreamAuthority($acceptance)
        ) {
            throw new \RuntimeException(
                "F137_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ACCEPTANCE_INVALID",
            );
        }

        $candidateId = $acceptance["persona_candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->candidates . "/" . $candidateId . ".json",
                "F138_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($candidate) ||
            $candidateId !== ($candidate["persona_candidate_id"] ?? null) ||
            ($acceptance["persona_candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($acceptance["instance_id"] ?? null) !==
                ($candidate["instance_id"] ?? null) ||
            ($acceptance["construction_acceptance_id"] ?? null) !==
                ($candidate["construction_acceptance_id"] ?? null) ||
            ($acceptance["construction_acceptance_digest"] ?? null) !==
                ($candidate["construction_acceptance_digest"] ?? null) ||
            CanonicalJson::encode(
                $acceptance["authorized_review_target"] ?? null,
            ) !==
                CanonicalJson::encode(
                    $candidate["authorized_review_target"] ?? null,
                ) ||
            CanonicalJson::encode($acceptance["design_basis"] ?? null) !==
                CanonicalJson::encode(
                    $candidate["sources"]["design_basis"] ?? null,
                ) ||
            "imperium.foundry-adversarial-reviewer-persona-candidate/v1" !==
                ($candidate["schema"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($candidate["persona_id"] ?? null) ||
            "1.0.0" !== ($candidate["persona_version"] ?? null) ||
            null !== ($candidate["supersedes"] ?? null) ||
            "SEALED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true !== ($candidate["construction_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null) ||
            $this->hasCandidateAuthority($candidate)
        ) {
            throw new \RuntimeException(
                "F138_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_CHANGED",
            );
        }

        $bindingId = $acceptance["binding_id"] ?? null;
        $binding = is_string($bindingId)
            ? $this->read(
                $this->occupancy . "/" . $bindingId . ".json",
                "F139_ARTIFICER_BINDING_CHANGED",
            )
            : [];
        if (
            !$this->digestMatches($binding) ||
            ($acceptance["binding_digest"] ?? null) !==
                ($binding["record_digest"] ?? null) ||
            "imperium.foundry-artificer-occupancy/v1" !==
                ($binding["schema"] ?? null) ||
            "foundry" !== ($binding["office"] ?? null) ||
            "foundry.artificer" !== ($binding["seat"] ?? null) ||
            "ACTIVE" !== ($binding["status"] ?? null) ||
            true !== ($binding["binding_atomic"] ?? null) ||
            ($candidate["instance_id"] ?? null) !==
                ($binding["instance_id"] ?? null) ||
            ($acceptance["actor"]["manifestation_id"] ?? null) !==
                ($binding["manifestation_id"] ?? null) ||
            ($acceptance["actor"]["occupancy_generation"] ?? null) !==
                ($binding["occupancy_generation"] ?? null) ||
            true === ($binding["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException("F139_ARTIFICER_BINDING_CHANGED");
        }

        $id =
            "adversarial-reviewer-bootstrap-seed-production-approval-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $acceptanceId,
                        $acceptance["record_digest"],
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
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-production-approval/v1",
            "production_approval_id" => $id,
            "instance_id" => $candidate["instance_id"],
            "bootstrap_seed_acceptance_id" => $acceptanceId,
            "bootstrap_seed_acceptance_digest" => $acceptance["record_digest"],
            "delivery_id" => $acceptance["delivery_id"],
            "delivery_digest" => $acceptance["delivery_digest"],
            "authorization_act_id" => $acceptance["authorization_act_id"],
            "authorization_act_digest" =>
                $acceptance["authorization_act_digest"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => $candidate["persona_id"],
            "persona_version" => $candidate["persona_version"],
            "construction_acceptance_id" =>
                $candidate["construction_acceptance_id"],
            "construction_acceptance_digest" =>
                $candidate["construction_acceptance_digest"],
            "authorized_review_target" =>
                $candidate["authorized_review_target"],
            "review_target_lineage" =>
                $acceptance["review_target_lineage"] ?? null,
            "design_basis" => $candidate["sources"]["design_basis"],
            "bootstrap_seed_boundary" => $boundary,
            "binding_id" => $bindingId,
            "binding_digest" => $binding["record_digest"],
            "actor" => $acceptance["actor"],
            "disposition" =>
                "APPROVED_AS_EXACT_INITIAL_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED",
            "status" => "APPROVED_PENDING_GARRISON_ADMISSION",
            "bootstrap_seed_authority_consumed" => true,
            "production_approval" => true,
            "review_findings_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
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

    private function hasCandidateAuthority(array $record): bool
    {
        foreach (
            [
                "review_authority",
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
            !is_dir($this->approvals) &&
            !mkdir($this->approvals, 0770, true) &&
            !is_dir($this->approvals)
        ) {
            throw new \RuntimeException(
                "Foundry bootstrap-seed production-approval directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->approvals . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F140_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_PRODUCTION_APPROVAL_ABSENT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F141_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_PRODUCTION_APPROVAL_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        foreach (
            glob(
                $this->approvals .
                    "/adversarial-reviewer-bootstrap-seed-production-approval-*.json",
            ) ?:
            []
            as $existingPath
        ) {
            $existing = $this->read(
                $existingPath,
                "F142_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ALREADY_APPROVED",
            );
            if (
                ($record["persona_candidate_id"] ?? null) ===
                ($existing["persona_candidate_id"] ?? null)
            ) {
                throw new \RuntimeException(
                    "F142_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ALREADY_APPROVED",
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
                "Bootstrap-seed production approval cannot be committed atomically.",
            );
        }
        return $record;
    }
}
