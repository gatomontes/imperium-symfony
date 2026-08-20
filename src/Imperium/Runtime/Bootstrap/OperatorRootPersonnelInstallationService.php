<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperatorRootPersonnelInstallationService
{
    private string $root;
    private string $officeRoot;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/operator-root";
        $this->officeRoot = $projectDir . "/var/imperium/offices";
    }

    public function install(array $package): array
    {
        if (is_file($this->root . "/operationalization-seal.json")) {
            throw new \RuntimeException("B212_OPERATOR_ROOT_WINDOW_CLOSED");
        }
        $package = $this->normalize($package);
        $packageDigest = hash("sha256", CanonicalJson::encode($package));
        $records = [];
        $placements = [];
        foreach ($package["personnel"] as $member) {
            $this->assertMember($member);
            $key = $this->placementKey($member);
            if (isset($placements[$key])) {
                throw new \RuntimeException(
                    "B201_OPERATOR_ROOT_PLACEMENT_SET_INVALID",
                );
            }
            $placements[$key] = true;
            $records[] = $this->record(
                $package["instance_id"],
                $packageDigest,
                $member,
            );
        }
        foreach ($records as $record) {
            $this->assertPlacementAvailable($record);
        }
        $installed = array_map($this->persist(...), $records);

        return [
            "schema" => "imperium.operator-root-personnel-installation/v3",
            "instance_id" => $package["instance_id"],
            "source_package_digest" => $packageDigest,
            "provenance" => "OPERATOR_ROOT_INSTALLATION",
            "installations" => $installed,
            "status" => "FOUNDING_PERSONNEL_INSTALLED_PRE_OPERATIONAL",
            "internal_authorization_required" => false,
            "internal_construction_required" => false,
            "internal_admission_required" => false,
            "internal_qualification_required" => false,
            "internal_confirmation_required" => false,
            "installation_grants_execution_authority" => false,
            "installation_grants_external_action_authority" => false,
            "installation_grants_credentials" => false,
            "operator_root_window_open" => true,
            "operationalization_closes_window_permanently" => true,
            "post_operational_upgrades_governed" => true,
        ];
    }

    private function normalize(array $package): array
    {
        if (
            "imperium.operator-root-personnel-package/v1" ===
            ($package["schema"] ?? null)
        ) {
            $map = [
                "foundry.reviewer.adversarial" => [
                    "foundry",
                    "adversarial-reviewer",
                ],
                "senate.lord-speaker" => ["senate", "lord-speaker"],
                "senate.bailiff" => ["senate", "bailiff"],
            ];
            foreach ($package["personnel"] ?? [] as $index => $member) {
                $seat = is_array($member) ? $member["seat"] ?? null : null;
                if (!is_string($seat) || !isset($map[$seat])) {
                    throw new \RuntimeException(
                        "B201_OPERATOR_ROOT_PLACEMENT_SET_INVALID",
                    );
                }
                [$office, $role] = $map[$seat];
                $package["personnel"][$index] = array_merge($member, [
                    "personnel_type" => "OFFICER",
                    "office" => $office,
                    "role" => $role,
                ]);
            }
            $package["schema"] = "imperium.operator-root-personnel-package/v2";
        }
        if (
            !in_array(
                $package["schema"] ?? null,
                [
                    "imperium.operator-root-personnel-package/v2",
                    "imperium.operator-root-personnel-package/v3",
                ],
                true,
            ) ||
            !is_string($package["instance_id"] ?? null) ||
            trim($package["instance_id"]) === "" ||
            !is_array($package["personnel"] ?? null) ||
            [] === $package["personnel"]
        ) {
            throw new \RuntimeException("B200_OPERATOR_ROOT_PACKAGE_INVALID");
        }
        return $package;
    }

    private function assertMember(mixed $member): void
    {
        if (
            !is_array($member) ||
            !in_array(
                $member["personnel_type"] ?? null,
                ["OFFICER", "OPERATIVE"],
                true,
            )
        ) {
            throw new \RuntimeException("B205_OPERATOR_ROOT_TYPE_INVALID");
        }
        $v0 = "GENERIC_V0_PLACEHOLDER" === ($member["founding_class"] ?? null);
        if (
            $v0 &&
            ("OFFICER" !== $member["personnel_type"] ||
                "0" !== ($member["version"] ?? null))
        ) {
            throw new \RuntimeException("B213_GENERIC_V0_INVALID");
        }
        foreach (["office", "role"] as $field) {
            if (
                !is_string($member[$field] ?? null) ||
                !preg_match('/^[a-z0-9][a-z0-9.-]*$/', $member[$field])
            ) {
                throw new \RuntimeException("B206_OPERATOR_ROOT_ID_INVALID");
            }
        }
        $placementField =
            "OFFICER" === $member["personnel_type"] ? "seat" : "assignment_id";
        if (
            !is_string($member[$placementField] ?? null) ||
            !preg_match('/^[a-z0-9][a-z0-9.-]*$/', $member[$placementField])
        ) {
            throw new \RuntimeException("B207_OPERATOR_ROOT_PLACEMENT_INVALID");
        }
        if ($v0) {
            foreach (["persona", "profile", "officer"] as $forbidden) {
                if (array_key_exists($forbidden, $member)) {
                    throw new \RuntimeException(
                        "B214_GENERIC_V0_ARTIFACT_FORBIDDEN",
                    );
                }
            }
            return;
        }
        foreach (["persona", "profile", "officer"] as $artifact) {
            $value = $member[$artifact] ?? null;
            if (
                !is_array($value) ||
                [] === $value ||
                !is_string($value["id"] ?? null) ||
                trim($value["id"]) === "" ||
                !is_string($value["version"] ?? null) ||
                trim($value["version"]) === ""
            ) {
                throw new \RuntimeException(
                    "B202_OPERATOR_ROOT_ARTIFACT_INVALID",
                );
            }
        }
    }

    private function placementKey(array $member): string
    {
        return "OFFICER" === $member["personnel_type"]
            ? "seat:" . $member["seat"]
            : "operative:" . $member["office"] . ":" . $member["assignment_id"];
    }

    private function record(
        string $instanceId,
        string $packageDigest,
        array $member,
    ): array {
        if ("GENERIC_V0_PLACEHOLDER" === ($member["founding_class"] ?? null)) {
            return $this->v0Record($instanceId, $packageDigest, $member);
        }
        $digests = [];
        foreach (["persona", "profile", "officer"] as $artifact) {
            $digests[$artifact] = hash(
                "sha256",
                CanonicalJson::encode($member[$artifact]),
            );
        }
        $placement = $this->placementKey($member);
        $id =
            "operator-root-installation-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $instanceId,
                        $packageDigest,
                        $placement,
                        $digests,
                    ]),
                ),
                0,
                20,
            );
        $manifestationId =
            "operator-root-manifestation-" .
            substr(hash("sha256", CanonicalJson::encode($digests)), 0, 20);
        $authoritySource = [
            "profile_id" => $member["profile"]["id"],
            "profile_version" => $member["profile"]["version"],
            "profile_digest" => $digests["profile"],
        ];
        $record = [
            "schema" =>
                "imperium.operator-root-personnel-installation-record/v2",
            "installation_id" => $id,
            "instance_id" => $instanceId,
            "personnel_type" => $member["personnel_type"],
            "office" => $member["office"],
            "role" => $member["role"],
            "source_package_digest" => $packageDigest,
            "provenance" => "OPERATOR_ROOT_INSTALLATION",
            "persona" => $member["persona"],
            "profile" => $member["profile"],
            "officer" => $member["officer"],
            "artifact_digests" => $digests,
            "manifestation_id" => $manifestationId,
            "authority_source" => $authoritySource,
            "internal_authorization_required" => false,
            "internal_construction_required" => false,
            "internal_admission_required" => false,
            "internal_qualification_required" => false,
            "internal_confirmation_required" => false,
            "execution_authority" => false,
            "external_action_authority" => false,
            "credentials_granted" => false,
            "post_operational_upgrades_governed" => true,
        ];
        if ("OFFICER" === $member["personnel_type"]) {
            $seat = $member["seat"];
            $bindingId =
                $member["office"] .
                "-" .
                $member["role"] .
                "-binding-" .
                substr(hash("sha256", $id . $seat), 0, 20);
            $record["seat"] = $seat;
            $record["binding"] = [
                "schema" => "imperium.operator-root-seat-occupancy/v1",
                "binding_id" => $bindingId,
                "instance_id" => $instanceId,
                "office" => $member["office"],
                "seat" => $seat,
                "role" => $member["role"],
                "manifestation_id" => $manifestationId,
                "occupancy_generation" => 1,
                "source_installation_id" => $id,
                "source_package_digest" => $packageDigest,
                "provenance" => "OPERATOR_ROOT_INSTALLATION",
                "status" => "ACTIVE",
                "binding_atomic" => true,
                "authority_source" => $authoritySource,
                "review_authority" => "foundry.reviewer.adversarial" === $seat,
                "confirmation_acceptance_authority" =>
                    "senate.lord-speaker" === $seat,
                "proceeding_security_authority" => "senate.bailiff" === $seat,
                "senator_question_authority" =>
                    str_starts_with($seat, "senate.committee."),
                "senator_finding_authority" =>
                    str_starts_with($seat, "senate.committee."),
                "execution_authority" => false,
                "external_action_authority" => false,
                "credentials_granted" => false,
            ];
            $record["status"] = "INSTALLED_AND_BOUND_PRE_OPERATIONAL";
        } else {
            $record["assignment_id"] = $member["assignment_id"];
            $record["roster"] = [
                "schema" => "imperium.operator-root-operative-roster/v1",
                "roster_id" =>
                    "operator-root-operative-" .
                    substr(hash("sha256", $id), 0, 20),
                "instance_id" => $instanceId,
                "office" => $member["office"],
                "role" => $member["role"],
                "assignment_id" => $member["assignment_id"],
                "manifestation_id" => $manifestationId,
                "source_installation_id" => $id,
                "source_package_digest" => $packageDigest,
                "provenance" => "OPERATOR_ROOT_INSTALLATION",
                "status" => "INSTALLED_INACTIVE",
                "authority_source" => $authoritySource,
                "deployment_authority" => false,
                "execution_authority" => false,
                "external_action_authority" => false,
                "credentials_granted" => false,
            ];
            $record["status"] = "INSTALLED_INACTIVE_PRE_OPERATIONAL";
        }
        return $record;
    }

    private function v0Record(
        string $instanceId,
        string $packageDigest,
        array $member,
    ): array {
        $seat = $member["seat"];
        $id =
            "operator-root-v0-installation-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $instanceId,
                        $packageDigest,
                        $member["office"],
                        $member["role"],
                        $seat,
                        "0",
                    ]),
                ),
                0,
                20,
            );
        $manifestationId =
            "generic-v0-placeholder-" .
            substr(hash("sha256", $instanceId . ":" . $seat), 0, 20);
        $bindingId =
            $member["office"] .
            "-" .
            $member["role"] .
            "-binding-" .
            substr(hash("sha256", $id . $seat), 0, 20);
        $authoritySource = [
            "kind" => "REQUIRED_SEAT_CONTRACT",
            "seat" => $seat,
            "placeholder_version" => "0",
        ];

        return [
            "schema" => "imperium.operator-root-v0-installation-record/v1",
            "installation_id" => $id,
            "instance_id" => $instanceId,
            "personnel_type" => "OFFICER",
            "founding_class" => "GENERIC_V0_PLACEHOLDER",
            "version" => "0",
            "office" => $member["office"],
            "role" => $member["role"],
            "seat" => $seat,
            "source_package_digest" => $packageDigest,
            "provenance" => "OPERATOR_ROOT_INSTALLATION",
            "manifestation_id" => $manifestationId,
            "authority_source" => $authoritySource,
            "binding" => [
                "schema" => "imperium.operator-root-seat-occupancy/v1",
                "binding_id" => $bindingId,
                "instance_id" => $instanceId,
                "office" => $member["office"],
                "seat" => $seat,
                "role" => $member["role"],
                "manifestation_id" => $manifestationId,
                "occupancy_generation" => 1,
                "placeholder_version" => "0",
                "founding_class" => "GENERIC_V0_PLACEHOLDER",
                "source_installation_id" => $id,
                "source_package_digest" => $packageDigest,
                "provenance" => "OPERATOR_ROOT_INSTALLATION",
                "status" => "ACTIVE",
                "binding_atomic" => true,
                "authority_source" => $authoritySource,
                "authorship_authority" => in_array(
                    $seat,
                    ["hagiography.sanctographer", "studium.chancellor"],
                    true,
                ),
                "subordinate_staff_resolution_authority" => in_array(
                    $seat,
                    ["hagiography.sanctographer", "studium.chancellor"],
                    true,
                ),
                "foundry_construction_authority" =>
                    "foundry.artificer" === $seat,
                "inventory_response_authority" =>
                    "garrison.constable" === $seat,
                "review_authority" => "foundry.reviewer.adversarial" === $seat,
                "confirmation_acceptance_authority" =>
                    "senate.lord-speaker" === $seat,
                "proceeding_security_authority" => "senate.bailiff" === $seat,
                "senator_question_authority" =>
                    str_starts_with($seat, "senate.committee."),
                "senator_finding_authority" =>
                    str_starts_with($seat, "senate.committee."),
                "recipient_acceptance" => false,
                "selection_authority" => false,
                "execution_authority" => false,
                "external_action_authority" => false,
                "credentials_granted" => false,
            ],
            "status" => "GENERIC_V0_INSTALLED_AND_BOUND_PRE_OPERATIONAL",
            "internal_authorization_required" => false,
            "internal_construction_required" => false,
            "internal_admission_required" => false,
            "internal_qualification_required" => false,
            "internal_confirmation_required" => false,
            "execution_authority" => false,
            "external_action_authority" => false,
            "credentials_granted" => false,
            "mandatory_first_order_upgrade" => true,
            "post_operational_upgrades_governed" => true,
        ];
    }

    private function assertPlacementAvailable(array $record): void
    {
        [$path, $directory] = $this->placementPath($record);
        if (is_file($path)) {
            return;
        }
        foreach (glob($directory . "/*.json") ?: [] as $existingPath) {
            $existing = $this->read($existingPath);
            if (
                "OFFICER" === $record["personnel_type"] &&
                $record["seat"] === ($existing["seat"] ?? null) &&
                in_array(
                    $existing["status"] ?? null,
                    ["ACTIVE", "ACTIVE_PLACEHOLDER"],
                    true,
                )
            ) {
                throw new \RuntimeException("B203_OPERATOR_ROOT_SEAT_OCCUPIED");
            }
            if (
                "OPERATIVE" === $record["personnel_type"] &&
                $record["assignment_id"] ===
                    ($existing["assignment_id"] ?? null)
            ) {
                throw new \RuntimeException(
                    "B209_OPERATOR_ROOT_ASSIGNMENT_OCCUPIED",
                );
            }
        }
    }

    private function placementPath(array $record): array
    {
        if ("OFFICER" === $record["personnel_type"]) {
            $directory =
                $this->officeRoot . "/" . $record["office"] . "/occupancy";
            return [
                $directory . "/" . $record["binding"]["binding_id"] . ".json",
                $directory,
            ];
        }
        $directory = $this->root . "/operatives/" . $record["office"];
        return [
            $directory . "/" . $record["roster"]["roster_id"] . ".json",
            $directory,
        ];
    }

    private function persist(array $record): array
    {
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $installationPath =
            $this->root .
            "/installations/" .
            $record["installation_id"] .
            ".json";
        [$placementPath, $placementDirectory] = $this->placementPath($record);
        $key = "OFFICER" === $record["personnel_type"] ? "binding" : "roster";
        $placement = $record[$key];
        $placement["source_installation_digest"] = $record["record_digest"];
        $placement["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($placement),
        );
        if (is_file($installationPath) || is_file($placementPath)) {
            if (!is_file($installationPath) || !is_file($placementPath)) {
                throw new \RuntimeException(
                    "B210_OPERATOR_ROOT_PARTIAL_INSTALLATION",
                );
            }
            if (
                CanonicalJson::encode($this->read($installationPath)) !==
                    CanonicalJson::encode($record) ||
                CanonicalJson::encode($this->read($placementPath)) !==
                    CanonicalJson::encode($placement)
            ) {
                throw new \RuntimeException(
                    "B211_OPERATOR_ROOT_REPLAY_CONFLICT",
                );
            }
            return $record;
        }
        foreach (
            [$this->root . "/installations", $placementDirectory]
            as $directory
        ) {
            if (
                !is_dir($directory) &&
                !mkdir($directory, 0770, true) &&
                !is_dir($directory)
            ) {
                throw new \RuntimeException(
                    "B204_OPERATOR_ROOT_INSTALLATION_FAILED",
                );
            }
        }
        $this->write($installationPath, $record);
        try {
            $this->write($placementPath, $placement);
        } catch (\Throwable $e) {
            @unlink($installationPath);
            throw $e;
        }
        return $record;
    }

    private function write(string $path, array $record): void
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
            throw new \RuntimeException(
                "B204_OPERATOR_ROOT_INSTALLATION_FAILED",
            );
        }
    }

    private function read(string $path): array
    {
        return json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
