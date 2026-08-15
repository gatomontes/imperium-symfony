<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class AdversarialReviewerBootstrapSeedAuthorizationService
{
    private string $candidates;
    private string $inbox;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $this->candidates =
            $p .
            "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates";
        $this->inbox =
            $p .
            "/var/imperium/offices/foundry/inbox/adversarial-reviewer-bootstrap-seed-authorizations";
    }
    public function authorize(string $candidateId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-persona-candidate-[a-f0-9]{20}$/',
                $candidateId,
            )
        ) {
            throw new \InvalidArgumentException(
                "C125_ADVERSARIAL_REVIEWER_CANDIDATE_ID_INVALID",
            );
        }
        $candidate = $this->read(
            $this->candidates . "/" . $candidateId . ".json",
            "C126_ADVERSARIAL_REVIEWER_CANDIDATE_ABSENT",
        );
        $basis = $candidate["sources"]["design_basis"] ?? null;
        if (
            !$this->ok($candidate) ||
            "imperium.foundry-adversarial-reviewer-persona-candidate/v1" !==
                ($candidate["schema"] ?? null) ||
            $candidateId !== ($candidate["persona_candidate_id"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($candidate["persona_id"] ?? null) ||
            "1.0.0" !== ($candidate["persona_version"] ?? null) ||
            null !== ($candidate["supersedes"] ?? null) ||
            "SEALED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true !== ($candidate["construction_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null) ||
            !is_array($basis) ||
            "Blackquill" !== ($basis["name"] ?? null) ||
            "persona-design-basis" !== ($basis["kind"] ?? null) ||
            "user-designated Blackquill critical-analysis contract" !==
                ($basis["derivation_basis"] ?? null) ||
            !is_array($basis["method"] ?? null) ||
            [] === $basis["method"] ||
            false !== ($basis["identity_imported"] ?? null) ||
            false !== ($basis["institution_imported"] ?? null) ||
            false !== ($basis["authority_imported"] ?? null) ||
            $this->hasAuthority($candidate) ||
            !$this->hasLineage($candidate)
        ) {
            throw new \RuntimeException(
                "C127_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_CANDIDATE_INVALID",
            );
        }
        $candidatePaths =
            glob(
                $this->candidates .
                    "/adversarial-reviewer-persona-candidate-*.json",
            ) ?:
            [];
        if (1 !== count($candidatePaths)) {
            throw new \RuntimeException(
                "C128_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_NOT_INITIAL",
            );
        }
        foreach (
            glob(
                $this->inbox .
                    "/adversarial-reviewer-bootstrap-seed-authorization-delivery-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read(
                $path,
                "C129_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_REPLAY_CONFLICT",
            );
            if (
                $candidateId === ($existing["persona_candidate_id"] ?? null) &&
                $this->ok($existing)
            ) {
                return $existing;
            }
            throw new \RuntimeException(
                "C128_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_NOT_INITIAL",
            );
        }
        $actId =
            "adversarial-reviewer-bootstrap-seed-authorization-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $candidateId,
                        $candidate["record_digest"],
                        "imperator-development-root",
                        "initial-reviewer-bootstrap-seed-only",
                    ]),
                ),
                0,
                20,
            );
        $act = [
            "schema" =>
                "imperium.imperator-adversarial-reviewer-bootstrap-seed-authorization/v1",
            "kind" =>
                "EXACT_INITIAL_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_AUTHORIZATION",
            "act_id" => $actId,
            "instance_id" => $candidate["instance_id"],
            "actor" => [
                "kind" => "imperator",
                "id" => "imperator-development-root",
            ],
            "authority_basis" => "explicit-imperator-directive",
            "directive" =>
                "establish the Blackquill-derived Adversarial Reviewer as the initial bootstrap seed without predecessor self-review",
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
            "review_target_lineage" => [
                "persona_specification_id" =>
                    $candidate["persona_specification_id"] ?? null,
                "persona_specification_digest" =>
                    $candidate["persona_specification_digest"] ?? null,
                "persona_specification_version" =>
                    $candidate["persona_specification_version"] ?? null,
                "specification_supersedes" =>
                    $candidate["specification_supersedes"] ?? null,
                "specification_revision_basis" =>
                    $candidate["specification_revision_basis"] ?? null,
                "dispatch_kind" => $candidate["dispatch_kind"] ?? null,
                "superseded_commissions" =>
                    $candidate["superseded_commissions"] ?? null,
            ],
            "design_basis" => $basis,
            "bootstrap_seed_boundary" => [
                "initial_version_only" => true,
                "exact_candidate_only" => true,
                "predecessor_review_required" => false,
                "self_review_prohibited" => true,
                "general_review_waiver" => false,
                "general_precedent" => false,
                "successor_versions_require_ordinary_independent_review" => true,
                "candidate_revision_terminates_authority" => true,
                "identity_or_digest_change_terminates_authority" => true,
            ],
            "disposition" =>
                "AUTHORIZED_FOR_EXACT_INITIAL_REVIEWER_BOOTSTRAP_SEED_PROCESSING",
            "bootstrap_seed_authority" => true,
            "bootstrap_seed_authority_exercisable" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
        ];
        $act["record_digest"] = hash("sha256", CanonicalJson::encode($act));
        $deliveryId =
            "adversarial-reviewer-bootstrap-seed-authorization-delivery-" .
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
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-authorization-delivery/v1",
            "delivery_id" => $deliveryId,
            "instance_id" => $candidate["instance_id"],
            "office" => "foundry",
            "target" => "foundry.artificer",
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "authorization_act_id" => $actId,
            "authorization_act_digest" => $act["record_digest"],
            "authorization_act" => $act,
            "status" => "DELIVERED_PENDING_FOUNDRY_ACCEPTANCE",
            "recipient_acceptance" => null,
            "bootstrap_seed_authority" => true,
            "bootstrap_seed_authority_exercisable" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "admission_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }
    private function hasLineage(array $candidate): bool
    {
        foreach (
            [
                "instance_id",
                "construction_acceptance_id",
                "construction_acceptance_digest",
                "authorization_act_id",
                "authorization_act_digest",
                "source_case_id",
                "source_case_digest",
            ]
            as $key
        ) {
            if (
                !is_string($candidate[$key] ?? null) ||
                "" === $candidate[$key]
            ) {
                return false;
            }
        }
        return is_array($candidate["authorized_review_target"] ?? null) &&
            is_array($candidate["artificer"] ?? null) &&
            is_array($candidate["template"] ?? null) &&
            is_array($candidate["persona"] ?? null);
    }
    private function hasAuthority(array $record): bool
    {
        foreach (
            [
                "bootstrap_seed_authority",
                "review_authority",
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
    private function ok(array $record): bool
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
                "Foundry bootstrap-seed authorization inbox cannot be created.",
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
                "C129_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "C129_ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_REPLAY_CONFLICT",
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
                "Bootstrap-seed authorization delivery cannot be committed atomically.",
            );
        }
        return $record;
    }
}
