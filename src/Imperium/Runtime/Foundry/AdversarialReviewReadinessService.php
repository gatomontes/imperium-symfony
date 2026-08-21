<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewReadinessService
{
    private string $demandDirectory;
    private string $candidateDirectory;
    private string $occupancyDirectory;
    private string $readinessDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->demandDirectory =
            $projectDir . "/var/imperium/mastermason/spawning-requests";
        $this->candidateDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/subordinate-persona-candidates";
        $this->occupancyDirectory =
            $projectDir . "/var/imperium/offices/foundry/occupancy";
        $this->readinessDirectory =
            $projectDir .
            "/var/imperium/offices/foundry/adversarial-review-readiness";
    }

    public function resume(string $demandId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-reviewer-demand-[a-f0-9]{20}$/',
                $demandId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F151_ADVERSARIAL_DEMAND_ID_INVALID",
            );
        }
        $demand = $this->read(
            $this->demandDirectory . "/" . $demandId . ".json",
            "F152_ADVERSARIAL_DEMAND_ABSENT",
        );
        $candidateId = $demand["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->candidateDirectory . "/" . $candidateId . ".json",
                "F153_ADVERSARIAL_READINESS_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($demand) ||
            !$this->digestMatches($candidate) ||
            "imperium.foundry-adversarial-reviewer-demand/v1" !==
                ($demand["schema"] ?? null) ||
            "PENDING_EXACT_ADVERSARIAL_REVIEWER_OCCUPATION" !==
                ($demand["status"] ?? null) ||
            ($demand["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            !is_string($candidate["originating_guildhall_commission_id"] ?? null) ||
            ($demand["originating_guildhall_commission_id"] ?? null) !== ($candidate["originating_guildhall_commission_id"] ?? null) ||
            ($demand["originating_guildhall_commission_digest"] ?? null) !== ($candidate["originating_guildhall_commission_digest"] ?? null) ||
            "ASSEMBLED_PENDING_FOUNDRY_REVIEW" !==
                ($candidate["status"] ?? null)
        ) {
            throw new \RuntimeException(
                "F153_ADVERSARIAL_READINESS_CHAIN_INVALID",
            );
        }
        $matches = [];
        foreach (glob($this->occupancyDirectory . "/*.json") ?: [] as $path) {
            $record = $this->read(
                $path,
                "F154_FOUNDING_REVIEWER_OCCUPANCY_INVALID",
            );
            if ("foundry.reviewer.adversarial" === ($record["seat"] ?? null)) {
                $matches[] = $record;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException(
                "F154_FOUNDING_REVIEWER_OCCUPANCY_INVALID",
            );
        }
        $reviewer = $matches[0];
        if (
            !$this->digestMatches($reviewer) ||
            "imperium.operator-root-seat-occupancy/v1" !==
                ($reviewer["schema"] ?? null) ||
            "OPERATOR_ROOT_INSTALLATION" !==
                ($reviewer["provenance"] ?? null) ||
            "ACTIVE" !== ($reviewer["status"] ?? null) ||
            true !== ($reviewer["review_authority"] ?? null) ||
            ($demand["instance_id"] ?? null) !==
                ($reviewer["instance_id"] ?? null)
        ) {
            throw new \RuntimeException(
                "F154_FOUNDING_REVIEWER_OCCUPANCY_INVALID",
            );
        }
        $id =
            "foundry-adversarial-review-readiness-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $demandId,
                        $demand["record_digest"],
                        $candidateId,
                        $candidate["record_digest"],
                        $reviewer["binding_id"],
                        $reviewer["record_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-adversarial-review-readiness/v1",
            "readiness_id" => $id,
            "instance_id" => $demand["instance_id"],
            "source_demand_id" => $demandId,
            "source_demand_digest" => $demand["record_digest"],
            "foundry_review_id" => $demand["foundry_review_id"],
            "foundry_review_digest" => $demand["foundry_review_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "originating_guildhall_commission_id" => $candidate["originating_guildhall_commission_id"],
            "originating_guildhall_commission_digest" => $candidate["originating_guildhall_commission_digest"],
            "review_target_lineage" => [
                "persona_specification_id" =>
                    $demand["persona_specification_id"],
                "persona_specification_digest" =>
                    $demand["persona_specification_digest"],
                "persona_specification_version" =>
                    $demand["persona_specification_version"],
                "specification_supersedes" =>
                    $demand["specification_supersedes"],
                "specification_revision_basis" =>
                    $demand["specification_revision_basis"],
                "dispatch_kind" => $demand["dispatch_kind"],
                "superseded_commissions" => $demand["superseded_commissions"],
            ],
            "reviewer_occupancy" => [
                "binding_id" => $reviewer["binding_id"],
                "binding_digest" => $reviewer["record_digest"],
                "manifestation_id" => $reviewer["manifestation_id"],
                "occupancy_generation" => $reviewer["occupancy_generation"],
                "provenance" => $reviewer["provenance"],
            ],
            "bootstrap_governance_chain_disposition" =>
                "SUPERSEDED_FOR_INITIAL_INSTALLATION_ONLY",
            "status" => "PENDING_ADVERSARIAL_REVIEWER_ACCEPTANCE",
            "recipient_acceptance" => null,
            "review_authority_exercisable" => false,
            "persona_approval_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->readinessDirectory) &&
            !mkdir($this->readinessDirectory, 0770, true) &&
            !is_dir($this->readinessDirectory)
        ) {
            throw new \RuntimeException("F155_ADVERSARIAL_READINESS_FAILED");
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->readinessDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F156_ADVERSARIAL_READINESS_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F156_ADVERSARIAL_READINESS_CONFLICT",
                );
            }
            return $existing;
        }
        $tmp = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $tmp,
                    json_encode(
                        $record,
                        JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_SLASHES |
                            JSON_THROW_ON_ERROR,
                    ) . "\n",
                    LOCK_EX,
                ) ||
            !rename($tmp, $path)
        ) {
            @unlink($tmp);
            throw new \RuntimeException("F155_ADVERSARIAL_READINESS_FAILED");
        }
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
}
