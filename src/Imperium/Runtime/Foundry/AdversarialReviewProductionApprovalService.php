<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewProductionApprovalService
{
    private string $root;
    private string $approvalDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/offices/foundry";
        $this->approvalDirectory =
            $this->root . "/subordinate-persona-production-approvals";
    }

    public function approve(string $resultId, string $bindingId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-review-result-[a-f0-9]{20}$/',
                $resultId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F168_ADVERSARIAL_RESULT_ID_INVALID",
            );
        }
        if (
            !preg_match(
                '/^foundry-artificer-binding-[a-f0-9]{20}$/',
                $bindingId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F169_ARTIFICER_BINDING_ID_INVALID",
            );
        }

        $result = $this->read(
            $this->root . "/adversarial-review-results/" . $resultId . ".json",
            "F170_ADVERSARIAL_PRODUCTION_APPROVAL_CHAIN_INVALID",
        );
        $candidateId = $result["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-candidates/" .
                    $candidateId .
                    ".json",
                "F170_ADVERSARIAL_PRODUCTION_APPROVAL_CHAIN_INVALID",
            )
            : [];
        $binding = $this->read(
            $this->root . "/occupancy/" . $bindingId . ".json",
            "F170_ADVERSARIAL_PRODUCTION_APPROVAL_CHAIN_INVALID",
        );

        if (
            !$this->digestMatches($result) ||
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($binding) ||
            "imperium.foundry-adversarial-review-result/v1" !==
                ($result["schema"] ?? null) ||
            "PASSED_PENDING_FOUNDRY_PRODUCTION_APPROVAL" !==
                ($result["status"] ?? null) ||
            "PASSED" !== ($result["decision"]["disposition"] ?? null) ||
            true !==
                ($result["foundry_production_approval_eligible"] ?? null) ||
            ($result["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            "ASSEMBLED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null) ||
            "imperium.foundry-artificer-occupancy/v1" !==
                ($binding["schema"] ?? null) ||
            "foundry.artificer" !== ($binding["seat"] ?? null) ||
            "ACTIVE" !== ($binding["status"] ?? null) ||
            true !== ($binding["binding_atomic"] ?? null) ||
            ($result["instance_id"] ?? null) !==
                ($binding["instance_id"] ?? null) ||
            true === ($binding["execution_authority"] ?? null) ||
            $this->hasDownstreamAuthority($result)
        ) {
            throw new \RuntimeException(
                "F170_ADVERSARIAL_PRODUCTION_APPROVAL_CHAIN_INVALID",
            );
        }

        $id =
            "subordinate-persona-production-approval-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $resultId,
                        $result["record_digest"],
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
                "imperium.foundry-subordinate-persona-production-approval/v1",
            "production_approval_id" => $id,
            "instance_id" => $result["instance_id"],
            "adversarial_review_result_id" => $resultId,
            "adversarial_review_result_digest" => $result["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "review_target_lineage" => $result["review_target_lineage"],
            "review_decision" => $result["decision"],
            "actor" => [
                "seat" => "foundry.artificer",
                "binding_id" => $bindingId,
                "binding_digest" => $binding["record_digest"],
                "manifestation_id" => $binding["manifestation_id"],
                "occupancy_generation" => $binding["occupancy_generation"],
            ],
            "disposition" => "APPROVED_AS_EXACT_REVIEWED_PERSONA_PRODUCTION",
            "status" => "APPROVED_PENDING_GARRISON_ADMISSION_DELIVERY",
            "production_approval" => true,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (
            [
                "persona_approval_authority",
                "admission_authority",
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

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->approvalDirectory) &&
            !mkdir($this->approvalDirectory, 0770, true) &&
            !is_dir($this->approvalDirectory)
        ) {
            throw new \RuntimeException(
                "F171_ADVERSARIAL_PRODUCTION_APPROVAL_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->approvalDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F172_ADVERSARIAL_PRODUCTION_APPROVAL_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F172_ADVERSARIAL_PRODUCTION_APPROVAL_CONFLICT",
                );
            }
            return $existing;
        }
        $this->write(
            $path,
            $record,
            "F171_ADVERSARIAL_PRODUCTION_APPROVAL_FAILED",
        );
        return $record;
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

    private function write(string $path, array $record, string $error): void
    {
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
            throw new \RuntimeException($error);
        }
    }
}
