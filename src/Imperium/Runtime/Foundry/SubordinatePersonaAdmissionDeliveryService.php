<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaAdmissionDeliveryService
{
    private string $root;
    private string $inbox;
    private SubordinatePersonaSpecificationLineageGuard $lineage;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/offices/foundry";
        $this->inbox =
            $projectDir .
            "/var/imperium/offices/garrison/inbox/subordinate-persona-admissions";
        $this->lineage = new SubordinatePersonaSpecificationLineageGuard(
            $this->root . "/subordinate-persona-specifications",
        );
    }

    public function deliver(string $approvalId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-production-approval-[a-f0-9]{20}$/',
                $approvalId,
            )
        ) {
            throw new \InvalidArgumentException(
                "F177_SUBORDINATE_PRODUCTION_APPROVAL_ID_INVALID",
            );
        }
        $approval = $this->read(
            $this->root .
                "/subordinate-persona-production-approvals/" .
                $approvalId .
                ".json",
            "F178_SUBORDINATE_ADMISSION_DELIVERY_CHAIN_INVALID",
        );
        $candidateId = $approval["candidate_id"] ?? null;
        $candidate = is_string($candidateId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-candidates/" .
                    $candidateId .
                    ".json",
                "F178_SUBORDINATE_ADMISSION_DELIVERY_CHAIN_INVALID",
            )
            : [];
        $resultId = $approval["adversarial_review_result_id"] ?? null;
        $result = is_string($resultId)
            ? $this->read(
                $this->root .
                    "/adversarial-review-results/" .
                    $resultId .
                    ".json",
                "F178_SUBORDINATE_ADMISSION_DELIVERY_CHAIN_INVALID",
            )
            : [];
        $specificationId =
            $approval["review_target_lineage"]["persona_specification_id"] ??
            null;
        $specification = is_string($specificationId)
            ? $this->read(
                $this->root .
                    "/subordinate-persona-specifications/" .
                    $specificationId .
                    ".json",
                "F178_SUBORDINATE_ADMISSION_DELIVERY_CHAIN_INVALID",
            )
            : [];

        if (
            !$this->digestMatches($approval) ||
            !$this->digestMatches($candidate) ||
            !$this->digestMatches($result) ||
            !$this->digestMatches($specification) ||
            "imperium.foundry-subordinate-persona-production-approval/v1" !==
                ($approval["schema"] ?? null) ||
            "APPROVED_AS_EXACT_REVIEWED_PERSONA_PRODUCTION" !==
                ($approval["disposition"] ?? null) ||
            "APPROVED_PENDING_GARRISON_ADMISSION_DELIVERY" !==
                ($approval["status"] ?? null) ||
            true !== ($approval["production_approval"] ?? null) ||
            true !== ($approval["sealed"] ?? null) ||
            ($approval["candidate_digest"] ?? null) !==
                ($candidate["record_digest"] ?? null) ||
            ($approval["adversarial_review_result_digest"] ?? null) !==
                ($result["record_digest"] ?? null) ||
            "PASSED_PENDING_FOUNDRY_PRODUCTION_APPROVAL" !==
                ($result["status"] ?? null) ||
            "PASSED" !== ($result["decision"]["disposition"] ?? null) ||
            ($approval["review_target_lineage"][
                "persona_specification_digest"
            ] ??
                null) !==
                ($specification["record_digest"] ?? null) ||
            ($candidate["persona_specification_digest"] ?? null) !==
                ($specification["record_digest"] ?? null) ||
            true === ($approval["admission_authority"] ?? null) ||
            true === ($approval["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "F178_SUBORDINATE_ADMISSION_DELIVERY_CHAIN_INVALID",
            );
        }

        $id =
            "subordinate-persona-admission-delivery-" .
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
        $record = [
            "schema" =>
                "imperium.garrison-subordinate-persona-admission-delivery/v1",
            "delivery_id" => $id,
            "instance_id" => $approval["instance_id"],
            "sender" => $approval["actor"],
            "recipient" => [
                "office" => "garrison",
                "seat" => "garrison.constable",
            ],
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "adversarial_review_result_id" => $resultId,
            "adversarial_review_result_digest" => $result["record_digest"],
            "candidate_id" => $candidateId,
            "candidate_digest" => $candidate["record_digest"],
            "persona_name" => $candidate["persona_name"],
            "persona_specification_version" =>
                $candidate["persona_specification_version"],
            "persona" => $candidate["persona"],
            "source_citations" => $candidate["source_citations"],
            "section_products" => $candidate["section_products"],
            "review_target_lineage" => $approval["review_target_lineage"],
            "review_decision" => $approval["review_decision"],
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
            "execution_authority" => false,
            "sealed" => true,
        ];
        if (is_file($this->inbox . "/" . $id . ".json")) {
            return $this->persist($id, $record);
        }
        $this->lineage->assertCurrent($specification);
        return $this->persist($id, $record);
    }

    private function persist(string $id, array $record): array
    {
        if (
            !is_dir($this->inbox) &&
            !mkdir($this->inbox, 0770, true) &&
            !is_dir($this->inbox)
        ) {
            throw new \RuntimeException(
                "F179_SUBORDINATE_ADMISSION_DELIVERY_FAILED",
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
                "F180_SUBORDINATE_ADMISSION_DELIVERY_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "F180_SUBORDINATE_ADMISSION_DELIVERY_CONFLICT",
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
                "F179_SUBORDINATE_ADMISSION_DELIVERY_FAILED",
            );
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
