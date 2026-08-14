<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerPersonaReviewInitiationService
{
    private string $candidateDirectory;
    private string $reviewDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $root = $projectDir . "/var/imperium/offices/foundry";
        $this->candidateDirectory =
            $root . "/adversarial-reviewer-persona-candidates";
        $this->reviewDirectory =
            $root . "/adversarial-reviewer-persona-review-cases";
    }

    public function initiate(string $candidateId): array
    {
        if (
            !preg_match(
                '/^adversarial-reviewer-persona-candidate-[a-f0-9]{20}$/',
                $candidateId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F117_ADVERSARIAL_REVIEWER_PERSONA_CANDIDATE_ID_INVALID",
            );
        }
        $candidate = $this->read(
            $this->candidateDirectory . "/" . $candidateId . ".json",
            "F118_ADVERSARIAL_REVIEWER_PERSONA_CANDIDATE_ABSENT",
        );
        if (
            !$this->digestMatches($candidate) ||
            "imperium.foundry-adversarial-reviewer-persona-candidate/v1" !==
                ($candidate["schema"] ?? null) ||
            $candidateId !== ($candidate["persona_candidate_id"] ?? null) ||
            "foundry.adversarial-reviewer" !==
                ($candidate["persona_id"] ?? null) ||
            !is_string($candidate["persona_version"] ?? null) ||
            "SEALED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true !== ($candidate["construction_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null) ||
            $this->hasDownstreamAuthority($candidate)
        ) {
            throw new \RuntimeException(
                "F119_ADVERSARIAL_REVIEWER_PERSONA_CANDIDATE_INVALID",
            );
        }

        foreach (
            glob(
                $this->reviewDirectory .
                    "/adversarial-reviewer-persona-review-case-*.json",
            ) ?:
            []
            as $path
        ) {
            $existing = $this->read(
                $path,
                "F120_ADVERSARIAL_REVIEWER_REVIEW_INITIATION_REPLAY_CONFLICT",
            );
            if (
                $candidateId === ($existing["persona_candidate_id"] ?? null) &&
                $this->digestMatches($existing)
            ) {
                return $existing;
            }
        }
        $id =
            "adversarial-reviewer-persona-review-case-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $candidateId,
                        $candidate["record_digest"],
                        "independent-bootstrap-review-required",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-persona-review-case/v1",
            "review_case_id" => $id,
            "instance_id" => $candidate["instance_id"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => $candidate["persona_id"],
            "persona_version" => $candidate["persona_version"],
            "construction_acceptance_id" =>
                $candidate["construction_acceptance_id"],
            "construction_acceptance_digest" =>
                $candidate["construction_acceptance_digest"],
            "artificer" => $candidate["artificer"],
            "requester" => "foundry.artificer",
            "escalation_recipient" => "curia.seneschal",
            "required_capability" => [
                "seat" => "foundry.reviewer.adversarial",
                "profile" => "offices/foundry/profile-reviewer-adversarial.md",
                "independence" =>
                    "reviewer must be admitted and must not be the candidate under review or its author or repairer",
            ],
            "bootstrap_constraint" => [
                "candidate_cannot_review_itself",
                "unadmitted candidate cannot occupy the reviewing Seat",
                "Artificer cannot impersonate the Adversarial Reviewer",
                "construction authorization does not waive independent review",
            ],
            "permitted_resolutions" => [
                "Curia supplies an already admitted independently qualified Reviewer Persona through the normal occupation flow",
                "Imperator separately authorizes an explicit exceptional bootstrap-review protocol preserving independence and full lineage",
            ],
            "review_scope" =>
                "Attack the exact immutable Adversarial Reviewer Persona Candidate for contradictions, gaps, ambiguity, unreachable duties, authority leaks, self-exemption, and exploitable wording.",
            "status" =>
                "BLOCKED_PENDING_INDEPENDENT_BOOTSTRAP_REVIEW_RESOLUTION",
            "review_initiated" => true,
            "review_complete" => false,
            "clean_review" => false,
            "exception_authority" => false,
            "review_authority" => false,
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

    private function hasDownstreamAuthority(array $record): bool
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
            !is_dir($this->reviewDirectory) &&
            !mkdir($this->reviewDirectory, 0770, true) &&
            !is_dir($this->reviewDirectory)
        ) {
            throw new \RuntimeException(
                "Foundry Adversarial Reviewer Persona review-case directory cannot be created.",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->reviewDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F120_ADVERSARIAL_REVIEWER_REVIEW_INITIATION_REPLAY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F120_ADVERSARIAL_REVIEWER_REVIEW_INITIATION_REPLAY_CONFLICT",
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
                "Adversarial Reviewer Persona review case cannot be committed atomically.",
            );
        }
        return $record;
    }
}
