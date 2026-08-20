<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaAdmissionIntakeService
{
    private string $inbox;
    private string $occupancy;
    private string $returns;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->inbox =
            $projectDir .
            "/var/imperium/offices/garrison/inbox/subordinate-persona-admissions";
        $this->occupancy =
            $projectDir . "/var/imperium/offices/garrison/occupancy";
        $this->returns =
            $projectDir .
            "/var/imperium/offices/foundry/inbox/subordinate-persona-admission-returns";
    }

    public function inspect(string $deliveryId): array
    {
        if (
            !preg_match(
                '/^subordinate-persona-admission-delivery-[a-f0-9]{20}$/',
                $deliveryId,
            )
        ) {
            throw new \InvalidArgumentException(
                "GA78_SUBORDINATE_ADMISSION_DELIVERY_ID_INVALID",
            );
        }
        $delivery = $this->read(
            $this->inbox . "/" . $deliveryId . ".json",
            "GA79_SUBORDINATE_ADMISSION_DELIVERY_ABSENT",
        );
        if (
            !$this->digestMatches($delivery) ||
            "imperium.garrison-subordinate-persona-admission-delivery/v1" !==
                ($delivery["schema"] ?? null) ||
            "garrison.constable" !== ($delivery["recipient"]["seat"] ?? null) ||
            "foundry.artificer" !== ($delivery["sender"]["seat"] ?? null) ||
            "CONSIDER_EXACT_PERSONA_FOR_GARRISON_ADMISSION" !==
                ($delivery["requested_disposition"] ?? null) ||
            "DELIVERED_PENDING_GARRISON_ACCEPTANCE" !==
                ($delivery["status"] ?? null) ||
            "RECOVERY_ONLY_PREMATURE_GARRISON_DELIVERY" !==
                ($delivery["route_class"] ?? null) ||
            true !== ($delivery["canonical_flow_violation"] ?? null) ||
            null !== ($delivery["recipient_acceptance"] ?? null) ||
            true !== ($delivery["production_approval"] ?? null) ||
            false !== ($delivery["admission_authority"] ?? null) ||
            null !== ($delivery["admission_decision"] ?? null) ||
            true !== ($delivery["sealed"] ?? null) ||
            $this->hasProhibitedAuthority($delivery)
        ) {
            throw new \RuntimeException(
                "GA80_SUBORDINATE_ADMISSION_DELIVERY_INVALID",
            );
        }

        $constable = $this->currentConstable($delivery["instance_id"] ?? null);
        $defects = [];
        foreach (
            [
                "senate_confirmation_id" =>
                    "MISSING_EXACT_SENATE_CONFIRMATION_ID",
                "senate_confirmation_digest" =>
                    "MISSING_EXACT_SENATE_CONFIRMATION_DIGEST",
                "tested_manifestation_id" =>
                    "MISSING_EXACT_TESTED_MANIFESTATION_ID",
                "tested_manifestation_digest" =>
                    "MISSING_EXACT_TESTED_MANIFESTATION_DIGEST",
            ]
            as $field => $defect
        ) {
            if (
                !is_string($delivery[$field] ?? null) ||
                "" === trim($delivery[$field])
            ) {
                $defects[] = $defect;
            }
        }
        if ([] === $defects) {
            throw new \RuntimeException(
                "GA81_SUBORDINATE_ADMISSION_REQUIRES_CONFIRMATION_VALIDATION",
            );
        }

        $id =
            "subordinate-persona-admission-return-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $deliveryId,
                        $delivery["record_digest"],
                        $constable["binding_id"],
                        $constable["record_digest"],
                        $defects,
                    ]),
                ),
                0,
                20,
            );
        return $this->persist($id, [
            "schema" =>
                "imperium.garrison-subordinate-persona-admission-return/v1",
            "return_id" => $id,
            "instance_id" => $delivery["instance_id"],
            "source_delivery_id" => $deliveryId,
            "source_delivery_digest" => $delivery["record_digest"],
            "production_approval_id" => $delivery["production_approval_id"],
            "production_approval_digest" =>
                $delivery["production_approval_digest"],
            "candidate_id" => $delivery["candidate_id"],
            "candidate_digest" => $delivery["candidate_digest"],
            "persona_name" => $delivery["persona_name"],
            "review_target_lineage" => $delivery["review_target_lineage"],
            "route_class" => "RECOVERY_AFTER_PREMATURE_GARRISON_DELIVERY",
            "constable" => [
                "seat" => "garrison.constable",
                "manifestation_id" => $constable["manifestation_id"],
                "occupancy_generation" => $constable["occupancy_generation"],
                "binding_id" => $constable["binding_id"],
                "binding_digest" => $constable["record_digest"],
            ],
            "recipient" => [
                "office" => "foundry",
                "seat" => "foundry.artificer",
            ],
            "disposition" => "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE",
            "defects" => $defects,
            "required_correction" =>
                "send the exact candidate through the competent pre-admission examination and supply a passing Senate confirmation for the exact tested manifestation",
            "recipient_acceptance" => false,
            "admission_decision" => "REFUSED",
            "custody_created" => false,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "selection_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ]);
    }

    private function currentConstable(mixed $instanceId): array
    {
        $paths =
            glob($this->occupancy . "/garrison-constable-binding-*.json") ?: [];
        if (1 !== count($paths)) {
            throw new \RuntimeException("GA82_EXACT_ACTIVE_CONSTABLE_REQUIRED");
        }
        $record = $this->read(
            $paths[0],
            "GA82_EXACT_ACTIVE_CONSTABLE_REQUIRED",
        );
        if (
            !$this->digestMatches($record) ||
            "imperium.garrison-constable-occupancy/v1" !==
                ($record["schema"] ?? null) ||
            $instanceId !== ($record["instance_id"] ?? null) ||
            "garrison.constable" !== ($record["seat"] ?? null) ||
            "ACTIVE" !== ($record["status"] ?? null) ||
            true !== ($record["binding_atomic"] ?? null) ||
            true !==
                ($record["persona_admission_disposition_authority"] ?? null) ||
            true !== ($record["custody_registration_authority"] ?? null) ||
            true === ($record["selection_authority"] ?? null) ||
            true === ($record["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "GA83_CONSTABLE_ADMISSION_JURISDICTION_INVALID",
            );
        }
        return $record;
    }

    private function hasProhibitedAuthority(array $record): bool
    {
        foreach (
            [
                "profile_approval_authority",
                "spawning_authority",
                "seat_binding_authority",
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
            !is_dir($this->returns) &&
            !mkdir($this->returns, 0770, true) &&
            !is_dir($this->returns)
        ) {
            throw new \RuntimeException(
                "GA84_SUBORDINATE_ADMISSION_RETURN_FAILED",
            );
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $path = $this->returns . "/" . $id . ".json";
        if (is_file($path)) {
            $existing = $this->read(
                $path,
                "GA85_SUBORDINATE_ADMISSION_RETURN_CONFLICT",
            );
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException(
                    "GA85_SUBORDINATE_ADMISSION_RETURN_CONFLICT",
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
                "GA84_SUBORDINATE_ADMISSION_RETURN_FAILED",
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
