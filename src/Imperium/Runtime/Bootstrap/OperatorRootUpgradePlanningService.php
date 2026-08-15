<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperatorRootUpgradePlanningService
{
    private string $root;

    public function __construct(
        #[Autowire("%kernel.project_dir%")] string $projectDir,
    ) {
        $this->root = $projectDir . "/var/imperium/operator-root";
    }

    public function prepare(string $instanceId): array
    {
        $seal = $this->read(
            $this->root . "/operationalization-seal.json",
            "B250_OPERATIONALIZATION_REQUIRED",
        );
        if (
            $instanceId !== ($seal["instance_id"] ?? null) ||
            "IMPERIUM_OPERATIONAL" !== ($seal["status"] ?? null)
        ) {
            throw new \RuntimeException("B251_OPERATIONALIZATION_INVALID");
        }
        $path = $this->root . "/upgrade-plan.json";
        $inventory = $seal["upgrade_docket"] ?? [];
        $first = [
            "foundry.reviewer.adversarial",
            "foundry.artificer",
            "garrison.constable",
            "laboratorium.alchemist",
            "conscription.recruiter",
        ];
        usort($inventory, static function (array $a, array $b) use (
            $first,
        ): int {
            $aRank = array_search($a["seat"] ?? null, $first, true);
            $bRank = array_search($b["seat"] ?? null, $first, true);
            $aRank = false === $aRank ? count($first) : $aRank;
            $bRank = false === $bRank ? count($first) : $bRank;
            return [$aRank, $a["seat"] ?? ""] <=> [$bRank, $b["seat"] ?? ""];
        });
        foreach ($inventory as $index => &$item) {
            $item["upgrade_sequence"] = $index + 1;
        }
        unset($item);
        $record = [
            "schema" => "imperium.operator-root-upgrade-plan/v1",
            "plan_id" =>
                "operator-root-upgrade-plan-" .
                substr(
                    hash(
                        "sha256",
                        CanonicalJson::encode([$seal["seal_id"], $inventory]),
                    ),
                    0,
                    20,
                ),
            "instance_id" => $instanceId,
            "operationalization_seal_id" => $seal["seal_id"],
            "operationalization_seal_digest" => $seal["record_digest"],
            "status" => "PREPARED_NOT_STARTED",
            "operator_may_test_drive_before_start" => true,
            "execution_authority" => false,
            "ordered_upgrades" => $inventory,
        ];
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        if (is_file($path)) {
            $existing = $this->read($path, "B252_UPGRADE_PLAN_CONFLICT");
            if (
                CanonicalJson::encode($existing) !==
                CanonicalJson::encode($record)
            ) {
                throw new \RuntimeException("B252_UPGRADE_PLAN_CONFLICT");
            }
            return $existing;
        }
        $this->write($path, $record);
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
            throw new \RuntimeException("B253_UPGRADE_PLAN_WRITE_FAILED");
        }
    }
}
