<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;

final readonly class V0ActivationService
{
    public function __construct(
        private RequiredV0PersonnelInstallationService $installer,
        private OperatorRootOperationalizationService $operationalization,
        private OperatorRootUpgradePlanningService $upgrades,
        private StateStore $state,
    ) {}

    public function activate(
        string $instanceId,
        bool $prepareUpgrades = false,
    ): array {
        if ("" === trim($instanceId)) {
            throw new \InvalidArgumentException("B240_INSTANCE_ID_INVALID");
        }
        return $this->state->locked(function () use (
            $instanceId,
            $prepareUpgrades,
        ): array {
            $existing = $this->state->read();
            if (is_array($existing)) {
                if (
                    BootstrapState::CuriaReady->value !==
                        ($existing["state"] ?? null) ||
                    "OPERATOR_ROOT_V0" !==
                        ($existing["activation_mode"] ?? null) ||
                    $instanceId !==
                        ($existing["binding"]["instance_id"] ?? null)
                ) {
                    throw new \RuntimeException(
                        "B241_INCOMPATIBLE_BOOTSTRAP_STATE_PRESENT",
                    );
                }
                return [
                    "state" => $existing,
                    "operationalization" => $existing["operationalization"],
                    "upgrade_plan" => $prepareUpgrades
                        ? $this->upgrades->prepare($instanceId)
                        : null,
                    "upgrade_program_status" => $prepareUpgrades
                        ? "PREPARED_NOT_STARTED"
                        : "DEFERRED_FOR_TEST_DRIVE",
                ];
            }
            $installation = $this->installer->install($instanceId);
            $seal = $this->operationalization->seal($instanceId);
            $occupants = [];
            foreach ($installation["installations"] as $record) {
                if (
                    in_array(
                        $record["seat"],
                        [
                            "curia.seneschal",
                            "curia.chamberlain",
                            "curia.secretary",
                        ],
                        true,
                    )
                ) {
                    $role = substr($record["seat"], strlen("curia."));
                    $occupants[$role] = [
                        "seat" => $record["seat"],
                        "manifestation_id" => $record["manifestation_id"],
                        "occupancy_generation" =>
                            $record["binding"]["occupancy_generation"],
                        "placeholder_version" => "0",
                        "status" => "active",
                    ];
                }
            }
            $manifestId =
                "operator-root-v0-manifest-" .
                substr(hash("sha256", $seal["record_digest"]), 0, 20);
            $state = [
                "schema" => "imperium.bootstrap-state/v0-root",
                "state" => BootstrapState::CuriaReady->value,
                "generation" => 1,
                "activation_mode" => "OPERATOR_ROOT_V0",
                "binding" => [
                    "instance_id" => $instanceId,
                    "manifest_id" => $manifestId,
                ],
                "operationalization" => [
                    "seal_id" => $seal["seal_id"],
                    "seal_digest" => $seal["record_digest"],
                ],
                "events" => [
                    [
                        "transition" => "T10",
                        "result" => "SUCCESS",
                        "output" => [
                            "activation_mode" => "OPERATOR_ROOT_V0",
                            "runtime" => [
                                "addressable" => true,
                                "occupants" => $occupants,
                            ],
                        ],
                    ],
                ],
            ];
            $state["record_digest"] = hash(
                "sha256",
                CanonicalJson::encode($state),
            );
            $this->state->write($state);
            return [
                "state" => $state,
                "installation" => $installation,
                "operationalization" => $seal,
                "upgrade_plan" => $prepareUpgrades
                    ? $this->upgrades->prepare($instanceId)
                    : null,
                "upgrade_program_status" => $prepareUpgrades
                    ? "PREPARED_NOT_STARTED"
                    : "DEFERRED_FOR_TEST_DRIVE",
            ];
        });
    }
}
