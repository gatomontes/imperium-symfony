<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewerDemandService
{
    private string $reviewDirectory;
    private string $candidateDirectory;
    private string $demandDirectory;
    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->reviewDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/subordinate-persona-reviews";
        $this->candidateDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/subordinate-persona-candidates";
        $this->demandDirectory =
            $projectDir . "/var/imperium/mastermason/spawning-requests";
    }
    public function demand(string $reviewId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-review-[a-f0-9]{20}$/',
                $reviewId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F147_PERSONA_REVIEW_ID_INVALID",
            );
        }
        $review = $this->read(
            $this->reviewDirectory . "/" . $reviewId . ".json",
            "F148_PERSONA_REVIEW_ABSENT",
        );
        $candidateId = $review["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->candidateDirectory . "/" . $candidateId . ".json",
                "F149_ADVERSARIAL_DEMAND_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($review) ||
            !$this->digestMatches($candidate) ||
            "imperium.foundry-subordinate-persona-review/v1" !==
                ($review["schema"] ?? null) ||
            "SEALED_PENDING_FOUNDRY_ADVERSARIAL_REVIEW" !==
                ($review["status"] ?? null) ||
            true !== ($review["completeness_review_complete"] ?? null) ||
            true !== ($review["adversarial_review_authority"] ?? null) ||
            true !== ($review["sealed"] ?? null) ||
            ($review["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            !is_string($candidate["originating_guildhall_commission_id"] ?? null) ||
            !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $candidate["originating_guildhall_commission_id"]) ||
            ($review["originating_guildhall_commission_id"] ?? null) !== ($candidate["originating_guildhall_commission_id"] ?? null) ||
            ($review["originating_guildhall_commission_digest"] ?? null) !== ($candidate["originating_guildhall_commission_digest"] ?? null) ||
            ($review["dispatch_kind"] ?? null) !==
                ($candidate["dispatch_kind"] ?? null) ||
            CanonicalJson::encode($review["superseded_commissions"] ?? null) !==
                CanonicalJson::encode(
                    $candidate["superseded_commissions"] ?? null,
                ) ||
            "ASSEMBLED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            true === ($review["persona_approval_authority"] ?? null) ||
            true === ($review["admission_authority"] ?? null) ||
            true === ($review["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "F149_ADVERSARIAL_DEMAND_CHAIN_INVALID",
            );
        }
        $seat = [
            "seat" => "foundry.reviewer.adversarial",
            "profile" => "offices/foundry/profile-reviewer-adversarial.md",
            "activation_policy" => "demand",
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "originating_guildhall_commission_id" => $candidate["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $candidate["originating_guildhall_commission_digest"],
            "status" => "BLOCKED_PENDING_EXACT_REVIEWER_OCCUPATION",
        ];
        $id =
            "foundry-adversarial-reviewer-demand-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $reviewId,
                        $review["record_digest"],
                        $seat,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-adversarial-reviewer-demand/v1",
            "demand_id" => $id,
            "requester" => "foundry.artificer",
            "recipient" => "mastermason",
            "instance_id" => $review["instance_id"],
            "foundry_review_id" => $reviewId,
            "foundry_review_digest" => $review["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "originating_guildhall_commission_id" =>
                $candidate["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" =>
                $candidate["originating_guildhall_commission_digest"],
            "persona_specification_id" => $review["persona_specification_id"],
            "persona_specification_digest" =>
                $review["persona_specification_digest"],
            "persona_specification_version" =>
                $review["persona_specification_version"],
            "specification_supersedes" => $review["specification_supersedes"],
            "specification_revision_basis" =>
                $review["specification_revision_basis"],
            "dispatch_kind" => $review["dispatch_kind"],
            "superseded_commissions" => $review["superseded_commissions"],
            "review_scope" => $review["decision"]["adversarial_review_brief"],
            "required_seat" => $seat,
            "independence_requirements" => [
                "occupant did not author candidate",
                "occupant did not repair candidate",
                "Artificer may not direct, suppress, reinterpret, or negotiate findings",
            ],
            "status" => "PENDING_EXACT_ADVERSARIAL_REVIEWER_OCCUPATION",
            "review_authority" => false,
            "persona_approval_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
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
        $d = $r["record_digest"] ?? null;
        unset($r["record_digest"]);
        return is_string($d) &&
            hash_equals($d, hash("sha256", CanonicalJson::encode($r)));
    }
    private function persist(string $id, array $r): array
    {
        if (
            !is_dir($this->demandDirectory) &&
            !mkdir($this->demandDirectory, 0770, true) &&
            !is_dir($this->demandDirectory)
        ) {
            throw new \RuntimeException(
                "MasterMason spawning-request directory cannot be created.",
            );
        }
        $r["record_digest"] = hash("sha256", CanonicalJson::encode($r));
        $p = $this->demandDirectory . "/" . $id . ".json";
        if (is_file($p)) {
            $old = $this->read($p, "F150_ADVERSARIAL_DEMAND_REPLAY_CONFLICT");
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($r)) {
                throw new \RuntimeException(
                    "F150_ADVERSARIAL_DEMAND_REPLAY_CONFLICT",
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
                "Adversarial Reviewer demand cannot be committed atomically.",
            );
        }
        return $r;
    }
}
