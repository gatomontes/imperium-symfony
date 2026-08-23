<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

final readonly class RequiredV0PersonnelInstallationService
{
    public function __construct(
        private OperatorRootPersonnelInstallationService $installer,
    ) {}

    public function install(string $instanceId): array
    {
        if ("" === trim($instanceId)) {
            throw new \InvalidArgumentException("B230_INSTANCE_ID_INVALID");
        }
        $personnel = [];
        foreach (RequiredV0SeatRegistry::all() as $seat) {
            $personnel[] = [
                "personnel_type" => "OFFICER",
                "founding_class" => "GENERIC_V0_PLACEHOLDER",
                "version" => "0",
                "office" => $seat["office"],
                "role" => $seat["role"],
                "seat" => $seat["seat"],
            ];
        }
        $result = $this->installer->install([
            "schema" => "imperium.operator-root-personnel-package/v3",
            "instance_id" => $instanceId,
            "personnel" => $personnel,
        ]);
        $result["required_seat_count"] = count($personnel);
        $result["required_seats"] = array_column($personnel, "seat");
        $result["founding_class"] = "GENERIC_V0_PLACEHOLDER";
        $result["placeholder_version"] = "0";
        $result["persona_required"] = false;
        $result["profile_required"] = false;
        $result["officer_artifact_required"] = false;
        $result["status"] = "ALL_REQUIRED_V0_SEATS_OCCUPIED_PRE_OPERATIONAL";
        return $result;
    }
}
