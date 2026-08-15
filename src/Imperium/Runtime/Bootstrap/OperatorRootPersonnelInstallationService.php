<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperatorRootPersonnelInstallationService
{
    private string $installationDirectory;
    private string $officeRoot;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->installationDirectory =
            $projectDir . "/var/imperium/operator-root/installations";
        $this->officeRoot = $projectDir . "/var/imperium/offices";
    }

    public function install(array $package): array
    {
        $expected = [
            "foundry.reviewer.adversarial",
            "senate.lord-speaker",
            "senate.bailiff",
        ];
        if (
            "imperium.operator-root-personnel-package/v1" !==
                ($package["schema"] ?? null) ||
            !is_string($package["instance_id"] ?? null) ||
            "" === trim($package["instance_id"]) ||
            !is_array($package["personnel"] ?? null) ||
            3 !== count($package["personnel"])
        ) {
            throw new \RuntimeException("B200_OPERATOR_ROOT_PACKAGE_INVALID");
        }
        $packageDigest = hash("sha256", CanonicalJson::encode($package));
        $records = [];
        foreach ($package["personnel"] as $index => $member) {
            $seat = is_array($member) ? $member["seat"] ?? null : null;
            if (($expected[$index] ?? null) !== $seat) {
                throw new \RuntimeException(
                    "B201_OPERATOR_ROOT_SEAT_SET_INVALID",
                );
            }
            foreach (["persona", "profile", "officer"] as $artifact) {
                $value = is_array($member) ? $member[$artifact] ?? null : null;
                if (
                    !is_array($value) ||
                    [] === $value ||
                    !is_string($value["id"] ?? null) ||
                    "" === trim($value["id"]) ||
                    !is_string($value["version"] ?? null) ||
                    "" === trim($value["version"])
                ) {
                    throw new \RuntimeException(
                        "B202_OPERATOR_ROOT_ARTIFACT_INVALID",
                    );
                }
            }
            $records[] = $this->record(
                $package["instance_id"],
                $packageDigest,
                $member,
            );
        }
        foreach ($records as $record) {
            $this->assertSeatAvailable($record);
        }
        $installed = [];
        foreach ($records as $record) {
            $installed[] = $this->persist($record);
        }
        return [
            "schema" => "imperium.operator-root-personnel-installation/v1",
            "instance_id" => $package["instance_id"],
            "source_package_digest" => $packageDigest,
            "provenance" => "OPERATOR_ROOT_INSTALLATION",
            "installations" => $installed,
            "status" => "FOUNDING_PERSONNEL_INSTALLED",
            "internal_authorization_required" => false,
            "internal_construction_required" => false,
            "internal_admission_required" => false,
            "post_bootstrap_changes_governed" => true,
        ];
    }

    private function record(
        string $instanceId,
        string $packageDigest,
        array $member,
    ): array {
        $seat = $member["seat"];
        [$office, $role] = match ($seat) {
            "foundry.reviewer.adversarial" => [
                "foundry",
                "adversarial-reviewer",
            ],
            "senate.lord-speaker" => ["senate", "lord-speaker"],
            "senate.bailiff" => ["senate", "bailiff"],
        };
        $artifactDigests = [];
        foreach (["persona", "profile", "officer"] as $artifact) {
            $artifactDigests[$artifact] = hash(
                "sha256",
                CanonicalJson::encode($member[$artifact]),
            );
        }
        $id =
            "operator-root-installation-" .
            substr(
                hash(
                    "sha256",
                    CanonicalJson::encode([
                        $instanceId,
                        $packageDigest,
                        $seat,
                        $artifactDigests,
                    ]),
                ),
                0,
                20,
            );
        $bindingId =
            $office .
            "-" .
            $role .
            "-binding-" .
            substr(hash("sha256", $id . $seat), 0, 20);
        $manifestationId =
            "operator-root-manifestation-" .
            substr(
                hash("sha256", CanonicalJson::encode($artifactDigests)),
                0,
                20,
            );
        return [
            "schema" =>
                "imperium.operator-root-personnel-installation-record/v1",
            "installation_id" => $id,
            "instance_id" => $instanceId,
            "office" => $office,
            "seat" => $seat,
            "role" => $role,
            "source_package_digest" => $packageDigest,
            "provenance" => "OPERATOR_ROOT_INSTALLATION",
            "persona" => $member["persona"],
            "profile" => $member["profile"],
            "officer" => $member["officer"],
            "artifact_digests" => $artifactDigests,
            "binding" => [
                "schema" => "imperium.operator-root-seat-occupancy/v1",
                "binding_id" => $bindingId,
                "instance_id" => $instanceId,
                "office" => $office,
                "seat" => $seat,
                "manifestation_id" => $manifestationId,
                "occupancy_generation" => 1,
                "source_installation_id" => $id,
                "source_package_digest" => $packageDigest,
                "provenance" => "OPERATOR_ROOT_INSTALLATION",
                "status" => "ACTIVE",
                "binding_atomic" => true,
                "review_authority" => "foundry.reviewer.adversarial" === $seat,
                "confirmation_acceptance_authority" =>
                    "senate.lord-speaker" === $seat,
                "proceeding_security_authority" => "senate.bailiff" === $seat,
                "execution_authority" => false,
            ],
            "status" => "INSTALLED_AND_BOUND",
            "internal_authorization_required" => false,
            "post_bootstrap_changes_governed" => true,
        ];
    }

    private function assertSeatAvailable(array $record): void
    {
        $directory = $this->officeRoot . "/" . $record["office"] . "/occupancy";
        $path = $directory . "/" . $record["binding"]["binding_id"] . ".json";
        if (is_file($path)) {
            return;
        }
        foreach (glob($directory . "/*.json") ?: [] as $existingPath) {
            $existing = $this->read($existingPath);
            if (
                $record["seat"] === ($existing["seat"] ?? null) &&
                "ACTIVE" === ($existing["status"] ?? null)
            ) {
                throw new \RuntimeException("B203_OPERATOR_ROOT_SEAT_OCCUPIED");
            }
        }
    }

    private function persist(array $record): array
    {
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        $installationPath =
            $this->installationDirectory .
            "/" .
            $record["installation_id"] .
            ".json";
        $binding = $record["binding"];
        $binding["source_installation_digest"] = $record["record_digest"];
        $binding["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($binding),
        );
        $occupancyDirectory =
            $this->officeRoot . "/" . $record["office"] . "/occupancy";
        $occupancyPath =
            $occupancyDirectory . "/" . $binding["binding_id"] . ".json";
        if (is_file($installationPath) && is_file($occupancyPath)) {
            return $record;
        }
        foreach (
            [$this->installationDirectory, $occupancyDirectory]
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
        $this->write($occupancyPath, $binding);
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
