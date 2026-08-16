<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AdversarialReviewAcceptanceService
{
    private string $readinessDirectory;
    private string $acceptanceDirectory;
    private string $occupancyDirectory;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $root = $projectDir . "/var/imperium/offices/foundry";
        $this->readinessDirectory = $root . "/adversarial-review-readiness";
        $this->acceptanceDirectory = $root . "/adversarial-review-acceptances";
        $this->occupancyDirectory = $root . "/occupancy";
    }

    public function accept(string $readinessId): array
    {
        if (
            !preg_match(
                '/^foundry-adversarial-review-readiness-[a-f0-9]{20}$/',
                $readinessId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F157_ADVERSARIAL_READINESS_ID_INVALID",
            );
        }
        $readiness = $this->read(
            $this->readinessDirectory . "/" . $readinessId . ".json",
            "F158_ADVERSARIAL_READINESS_ABSENT",
        );
        $bindingId = $readiness["reviewer_occupancy"]["binding_id"] ?? null;
        $occupancy = is_string($bindingId)
            ? $this->read(
                $this->occupancyDirectory . "/" . $bindingId . ".json",
                "F159_ADVERSARIAL_ACCEPTANCE_CHAIN_INVALID",
            )
            : [];
        if (
            !$this->digestMatches($readiness) ||
            !$this->digestMatches($occupancy) ||
            "imperium.foundry-adversarial-review-readiness/v1" !==
                ($readiness["schema"] ?? null) ||
            "PENDING_ADVERSARIAL_REVIEWER_ACCEPTANCE" !==
                ($readiness["status"] ?? null) ||
            null !== ($readiness["recipient_acceptance"] ?? null) ||
            true === ($readiness["review_authority_exercisable"] ?? null) ||
            ($readiness["reviewer_occupancy"]["binding_digest"] ?? null) !==
                ($occupancy["record_digest"] ?? null) ||
            "foundry.reviewer.adversarial" !== ($occupancy["seat"] ?? null) ||
            "ACTIVE" !== ($occupancy["status"] ?? null) ||
            true !== ($occupancy["review_authority"] ?? null) ||
            ($readiness["instance_id"] ?? null) !==
                ($occupancy["instance_id"] ?? null) ||
            true === ($occupancy["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "F159_ADVERSARIAL_ACCEPTANCE_CHAIN_INVALID",
            );
        }
        $id =
            "foundry-adversarial-review-acceptance-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $readinessId,
                        $readiness["record_digest"],
                        $bindingId,
                        $occupancy["record_digest"],
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" => "imperium.foundry-adversarial-review-acceptance/v1",
            "acceptance_id" => $id,
            "instance_id" => $readiness["instance_id"],
            "readiness_id" => $readinessId,
            "readiness_digest" => $readiness["record_digest"],
            "candidate_id" => $readiness["candidate_id"],
            "candidate_digest" => $readiness["candidate_digest"],
            "review_target_lineage" => $readiness["review_target_lineage"],
            "reviewer" => [
                "seat" => "foundry.reviewer.adversarial",
                "binding_id" => $bindingId,
                "binding_digest" => $occupancy["record_digest"],
                "manifestation_id" => $occupancy["manifestation_id"],
                "occupancy_generation" => $occupancy["occupancy_generation"],
                "founding_class" =>
                    $occupancy["founding_class"] ?? "ARTIFACT_BACKED",
                "placeholder_version" =>
                    $occupancy["placeholder_version"] ?? null,
                "provenance" => $occupancy["provenance"],
            ],
            "disposition" => "ACCEPTED_FOR_EXACT_ADVERSARIAL_REVIEW",
            "recipient_acceptance" => true,
            "review_authority" => true,
            "review_authority_exercisable" => true,
            "persona_approval_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->acceptanceDirectory) &&
            !mkdir($this->acceptanceDirectory, 0770, true) &&
            !is_dir($this->acceptanceDirectory)
        ) {
            throw new \RuntimeException("F160_ADVERSARIAL_ACCEPTANCE_FAILED");
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->acceptanceDirectory . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "F161_ADVERSARIAL_ACCEPTANCE_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F161_ADVERSARIAL_ACCEPTANCE_CONFLICT",
                );
            }
            return $existing;
        }
        $this->write($path, $record, "F160_ADVERSARIAL_ACCEPTANCE_FAILED");
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
            throw new \RuntimeException($error);
        }
    }
}
