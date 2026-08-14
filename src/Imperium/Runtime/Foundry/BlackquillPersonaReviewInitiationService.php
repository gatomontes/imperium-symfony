<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class BlackquillPersonaReviewInitiationService
{
    private string $candidates;
    private string $reviews;
    public function __construct(#[Autowire("%kernel.project_dir%")] string $p)
    {
        $root = $p . "/var/imperium/offices/foundry";
        $this->candidates = $root . "/blackquill-persona-candidates";
        $this->reviews = $root . "/blackquill-persona-review-cases";
    }
    public function initiate(string $candidateId): array
    {
        if (
            !preg_match(
                '/^blackquill-persona-candidate-[a-f0-9]{20}$/',
                $candidateId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F144_BLACKQUILL_CANDIDATE_ID_INVALID",
            );
        }
        $candidate = $this->read(
            $this->candidates . "/" . $candidateId . ".json",
            "F145_BLACKQUILL_CANDIDATE_ABSENT",
        );
        if (
            !$this->ok($candidate) ||
            "imperium.foundry-blackquill-persona-candidate/v1" !==
                ($candidate["schema"] ?? null) ||
            $candidateId !== ($candidate["persona_candidate_id"] ?? null) ||
            "foundry.external.blackquill-adversarial-reviewer" !==
                ($candidate["persona_id"] ?? null) ||
            "1.0.0" !== ($candidate["persona_version"] ?? null) ||
            "SEALED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true !== ($candidate["production_processing_complete"] ?? null) ||
            true !== ($candidate["sealed"] ?? null) ||
            true === ($candidate["production_approval"] ?? null) ||
            "imperium.persona/v1" !==
                ($candidate["template"]["schema"] ?? null) ||
            "1.0.0" !== ($candidate["template"]["version"] ?? null) ||
            !is_string($candidate["source"]["content_digest"] ?? null) ||
            "foundry.artificer" !== ($candidate["artificer"]["seat"] ?? null) ||
            !$this->hasLineage($candidate) ||
            $this->downstream($candidate)
        ) {
            throw new \RuntimeException("F146_BLACKQUILL_CANDIDATE_INVALID");
        }
        foreach (
            glob($this->reviews . "/blackquill-persona-review-case-*.json") ?:
            []
            as $path
        ) {
            $old = $this->read(
                $path,
                "F147_BLACKQUILL_REVIEW_INITIATION_REPLAY_CONFLICT",
            );
            if (
                $candidateId === ($old["persona_candidate_id"] ?? null) &&
                $this->ok($old)
            ) {
                return $old;
            }
        }
        $id =
            "blackquill-persona-review-case-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $candidateId,
                        $candidate["record_digest"],
                        "independent-reviewer-distinct-from-blackquill-required",
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-blackquill-persona-review-case/v1",
            "review_case_id" => $id,
            "instance_id" => $candidate["instance_id"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "persona_id" => $candidate["persona_id"],
            "persona_version" => $candidate["persona_version"],
            "production_acceptance_id" =>
                $candidate["production_acceptance_id"],
            "production_acceptance_digest" =>
                $candidate["production_acceptance_digest"],
            "authorization_act_id" => $candidate["authorization_act_id"],
            "authorization_act_digest" =>
                $candidate["authorization_act_digest"],
            "source_case_id" => $candidate["source_case_id"],
            "source_case_digest" => $candidate["source_case_digest"],
            "template" => $candidate["template"],
            "source" => $candidate["source"],
            "artificer" => $candidate["artificer"],
            "requester" => "foundry.artificer",
            "escalation_recipient" => "curia.seneschal",
            "required_capability" => [
                "seat" => "foundry.reviewer.adversarial",
                "profile" => "offices/foundry/profile-reviewer-adversarial.md",
                "independence" =>
                    "reviewer must be admitted and must not be Blackquill, the candidate under review, or its author or repairer",
            ],
            "independence_constraint" => [
                "blackquill_cannot_review_itself",
                "unadmitted_candidate_cannot_occupy_the_reviewing_seat",
                "target_candidate_cannot_supply_its_own_review_authority",
                "production_authorization_does_not_waive_independent_review",
            ],
            "permitted_resolutions" => [
                "Curia supplies an already admitted independently qualified Reviewer Persona distinct from Blackquill through the normal occupation flow",
                "Imperator separately authorizes an explicit exceptional bootstrap-review protocol preserving independence and complete lineage",
            ],
            "review_scope" =>
                "Attack the exact immutable Blackquill Persona Candidate for contradictions, gaps, ambiguity, unreachable duties, authority leaks, self-exemption, source-boundary failures, and exploitable wording.",
            "status" => "BLOCKED_PENDING_DISTINCT_INDEPENDENT_REVIEWER",
            "review_initiated" => true,
            "review_complete" => false,
            "clean_review" => false,
            "exception_authority" => false,
            "review_findings_authority" => false,
            "production_approval" => false,
            "senate_confirmation_authority" => false,
            "release_authority" => false,
            "admission_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
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
                "production_acceptance_id",
                "production_acceptance_digest",
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
        return true;
    }
    private function downstream(array $r): bool
    {
        foreach (
            [
                "review_findings_authority",
                "review_authority",
                "senate_confirmation_authority",
                "release_authority",
                "admission_authority",
                "spawning_authority",
                "seat_binding_authority",
                "candidate_approval_authority",
                "execution_authority",
            ]
            as $key
        ) {
            if (true === ($r[$key] ?? false)) {
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
    private function ok(array $r): bool
    {
        $digest = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($digest) &&
            hash_equals($digest, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->reviews) &&
            !mkdir($this->reviews, 0770, true) &&
            !is_dir($this->reviews)
        ) {
            throw new \RuntimeException(
                "Foundry Blackquill Persona review-case directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $path = $this->reviews . "/" . $id . ".json";
        if (is_file($path)) {
            $old = $this->read(
                $path,
                "F147_BLACKQUILL_REVIEW_INITIATION_REPLAY_CONFLICT",
            );
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F147_BLACKQUILL_REVIEW_INITIATION_REPLAY_CONFLICT",
                );
            }
            return $old;
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $r,
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
                "Blackquill Persona review case cannot be committed atomically.",
            );
        }
        return $r;
    }
}
