<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperatorRootOperationalizationService
{
    private string $root;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/operator-root";
    }

    public function seal(string $instanceId): array
    {
        if ("" === trim($instanceId)) {
            throw new \InvalidArgumentException("B220_INSTANCE_ID_INVALID");
        }
        $path = $this->root . "/operationalization-seal.json";
        if (is_file($path)) {
            $existing = $this->read($path);
            if ($instanceId !== ($existing["instance_id"] ?? null)) {
                throw new \RuntimeException(
                    "B221_OPERATIONALIZATION_REPLAY_CONFLICT",
                );
            }
            return $existing;
        }
        $installations = [];
        foreach (
            glob($this->root . "/installations/*.json") ?: []
            as $installationPath
        ) {
            $installation = $this->read($installationPath);
            if ($instanceId === ($installation["instance_id"] ?? null)) {
                $installations[] = $installation;
            }
        }
        if ([] === $installations) {
            throw new \RuntimeException("B222_FOUNDING_PERSONNEL_ABSENT");
        }
        $installedSeats = array_filter(array_column($installations, "seat"));
        $requiredSeats = array_column(RequiredV0SeatRegistry::all(), "seat");
        $missingSeats = array_values(
            array_diff($requiredSeats, $installedSeats),
        );
        if ([] !== $missingSeats) {
            throw new \RuntimeException(
                "B224_REQUIRED_FOUNDING_SEATS_VACANT:" .
                    implode(",", $missingSeats),
            );
        }
        usort(
            $installations,
            static fn(array $a, array $b): int => $a["installation_id"] <=>
                $b["installation_id"],
        );
        $docket = [];
        foreach ($installations as $installation) {
            $personnelType = $installation["personnel_type"] ?? "OFFICER";
            $docket[] = [
                "installation_id" => $installation["installation_id"],
                "installation_digest" => $installation["record_digest"],
                "personnel_type" => $personnelType,
                "office" => $installation["office"],
                "role" => $installation["role"],
                "seat" => $installation["seat"] ?? null,
                "assignment_id" => $installation["assignment_id"] ?? null,
                "founding_class" =>
                    $installation["founding_class"] ?? "ARTIFACT_BACKED",
                "placeholder_version" => $installation["version"] ?? null,
                "persona_id" => $installation["persona"]["id"] ?? null,
                "persona_version" =>
                    $installation["persona"]["version"] ?? null,
                "profile_id" => $installation["profile"]["id"] ?? null,
                "profile_version" =>
                    $installation["profile"]["version"] ?? null,
                "officer_id" => $installation["officer"]["id"] ?? null,
                "officer_version" =>
                    $installation["officer"]["version"] ?? null,
                "required_disposition" => "GOVERNED_REASSESSMENT_AND_UPGRADE",
                "priority" => "FIRST_ORDER_OF_BUSINESS",
                "root_installation_may_remain_active_during_reassessment" => true,
                "replacement_requires_governed_cutover" => true,
            ];
        }
        $record = [
            "schema" => "imperium.operator-root-operationalization-seal/v1",
            "seal_id" =>
                "operator-root-operationalization-" .
                substr(
                    hash(
                        "sha256",
                        CanonicalJson::encode([$instanceId, $docket]),
                    ),
                    0,
                    20,
                ),
            "instance_id" => $instanceId,
            "status" => "IMPERIUM_OPERATIONAL",
            "operator_root_installation_window" => "PERMANENTLY_CLOSED",
            "founding_installation_count" => count($docket),
            "required_seat_count" => count($requiredSeats),
            "required_seats_complete" => true,
            "first_order_of_business" =>
                "GOVERNED_REASSESSMENT_AND_UPGRADE_OF_ALL_ROOT_INSTALLED_PERSONNEL",
            "upgrade_docket" => $docket,
            "root_provenance_preserved" => true,
            "future_operator_root_installation_allowed" => false,
        ];
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        if (
            !is_dir($this->root) &&
            !mkdir($this->root, 0770, true) &&
            !is_dir($this->root)
        ) {
            throw new \RuntimeException("B223_OPERATIONALIZATION_SEAL_FAILED");
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
            throw new \RuntimeException("B223_OPERATIONALIZATION_SEAL_FAILED");
        }
        return $record;
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
