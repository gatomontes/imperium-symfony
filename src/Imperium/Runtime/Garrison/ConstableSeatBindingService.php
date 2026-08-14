<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;

final readonly class ConstableSeatBindingService
{
    private string $deliveryDirectory;
    private string $occupancyDirectory;

    public function __construct(
        string $projectDir,
        private StateStore $bootstrap,
        private CanonicalConstableRegistry $constable,
        private GenericOfficerSubstrateRegistry $substrate,
    ) {
        $this->deliveryDirectory =
            $projectDir . "/var/imperium/mastermason/qualified-manifestations";
        $this->occupancyDirectory =
            $projectDir . "/var/imperium/offices/garrison/occupancy";
    }

    public function bind(string $deliveryId): array
    {
        if (!preg_match('/^qualified-delivery-[a-f0-9]{20}$/', $deliveryId)) {
            throw new \InvalidArgumentException(
                "GA50_DELIVERY_INVALID: exact qualified Constable delivery identity is required.",
            );
        }
        $bootstrap = $this->bootstrap->read();
        if (
            !is_array($bootstrap) ||
            BootstrapState::CuriaReady->value !== ($bootstrap["state"] ?? null)
        ) {
            throw new \RuntimeException(
                "GA51_IMPERIUM_NOT_READY: Constable binding requires CURIA_READY.",
            );
        }
        $instanceId = $bootstrap["binding"]["instance_id"] ?? null;
        if (!is_string($instanceId) || "" === $instanceId) {
            throw new \RuntimeException(
                "GA51_IMPERIUM_NOT_READY: exact Imperium instance binding is absent.",
            );
        }
        $packet = $this->read(
            $this->deliveryDirectory . "/" . $deliveryId . ".json",
            "GA52_DELIVERY_ABSENT",
        );
        $member = $this->constable->member();
        $qualification = $packet["qualification"] ?? null;
        if (
            !$this->digestMatches($packet) ||
            $deliveryId !== ($packet["delivery_id"] ?? null) ||
            "imperium.qualified-manifestation-packet/v1" !==
                ($packet["schema"] ?? null) ||
            true !== ($packet["commission"]["consumed"] ?? null) ||
            $instanceId !== ($packet["candidate"]["instance_id"] ?? null) ||
            "garrison.constable" !==
                ($packet["candidate"]["target_seat"] ?? null) ||
            "QUALIFIED_UNBOUND" !== ($packet["candidate"]["status"] ?? null) ||
            1 !==
                ($packet["candidate"]["target_occupancy_generation"] ?? null) ||
            "PROFILE_INSTALLED" !==
                ($packet["candidate"]["substrate_instance"]["status"] ??
                    null) ||
            CanonicalJson::encode($this->substrate->current()) !==
                CanonicalJson::encode(
                    $packet["candidate"]["substrate_instance"]["substrate"] ??
                        null,
                ) ||
            CanonicalJson::encode($member["persona"]) !==
                CanonicalJson::encode(
                    $packet["candidate"]["persona"] ?? null,
                ) ||
            CanonicalJson::encode($member["profile"]) !==
                CanonicalJson::encode(
                    $packet["candidate"]["profile"] ?? null,
                ) ||
            !is_array($qualification) ||
            "QUALIFIED" !== ($qualification["disposition"] ?? null) ||
            ($packet["candidate"]["manifestation_id"] ?? null) !==
                ($qualification["candidate_id"] ?? null) ||
            CanonicalJson::encode($member["qualification_contract"]) !==
                CanonicalJson::encode(
                    $qualification["qualification_contract"] ?? null,
                ) ||
            !hash_equals(
                (string) ($packet["qualification_digest"] ?? ""),
                hash("sha256", CanonicalJson::encode($qualification)),
            ) ||
            true !== ($packet["sealed"] ?? null) ||
            true === ($packet["seat_binding_authority"] ?? null) ||
            true === ($packet["inventory_response_authority"] ?? null) ||
            true === ($packet["execution_authority"] ?? null)
        ) {
            throw new \RuntimeException(
                "GA53_QUALIFIED_PACKET_INVALID: exact sealed qualified-unbound Constable packet is required.",
            );
        }
        return $this->persist([
            "schema" => "imperium.garrison-constable-occupancy/v1",
            "binding_id" =>
                "garrison-constable-binding-" .
                substr(
                    hash(
                        "sha256",
                        CanonicalJson::encode([
                            $instanceId,
                            $deliveryId,
                            $packet["record_digest"],
                            $member,
                        ]),
                    ),
                    0,
                    20,
                ),
            "instance_id" => $instanceId,
            "office" => "garrison",
            "seat" => "garrison.constable",
            "manifestation_id" => $packet["candidate"]["manifestation_id"],
            "prior_occupancy_generation" => 0,
            "occupancy_generation" => 1,
            "source_delivery_id" => $deliveryId,
            "source_packet_digest" => $packet["record_digest"],
            "canonical_constable_package" => $this->constable->current(),
            "status" => "ACTIVE",
            "binding_atomic" => true,
            "seat_binding_authority" => true,
            "seat_binding_disposition" => "CONSUMED_BY_ATOMIC_BINDING",
            "inventory_response_authority" => true,
            "inventory_response_scope" =>
                "authorized exact Garrison inventory and availability facts only",
            "selection_authority" => false,
            "execution_authority" => false,
        ]);
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
    private function persist(array $occupancy): array
    {
        if (
            !is_dir($this->occupancyDirectory) &&
            !mkdir($this->occupancyDirectory, 0770, true) &&
            !is_dir($this->occupancyDirectory)
        ) {
            throw new \RuntimeException(
                "Garrison occupancy directory cannot be created.",
            );
        }
        $occupancy["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($occupancy),
        );
        $path =
            $this->occupancyDirectory .
            "/" .
            $occupancy["binding_id"] .
            ".json";
        if (is_file($path)) {
            $existing = $this->read($path, "GA54_BINDING_ABSENT");
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($occupancy)
            ) {
                throw new \RuntimeException("GA55_BINDING_REPLAY_CONFLICT");
            }
            return $existing;
        }
        if (
            [] !==
            (glob(
                $this->occupancyDirectory .
                    "/garrison-constable-binding-*.json",
            ) ?:
                [])
        ) {
            throw new \RuntimeException(
                "GA56_CONSTABLE_ALREADY_BOUND: Constable Seat is already occupied.",
            );
        }
        $temporary = $path . ".tmp." . bin2hex(random_bytes(6));
        if (
            false ===
                file_put_contents(
                    $temporary,
                    json_encode(
                        $occupancy,
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
                "Constable occupancy cannot be committed atomically.",
            );
        }
        return $occupancy;
    }
}
