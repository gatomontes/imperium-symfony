<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\StateStore;
use App\Bootstrap\MasterMason;
use App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService;
use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use App\Imperium\Runtime\Bootstrap\OperatorRootUpgradePlanningService;
use App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService;
use App\Imperium\Runtime\Bootstrap\RequiredV0SeatRegistry;
use App\Imperium\Runtime\Bootstrap\V0ActivationService;
use PHPUnit\Framework\TestCase;

final class RequiredV0PersonnelInstallationTest extends TestCase
{
    public function testRequiredSeatsReceiveArtifactFreeV0OccupantsAndBecomeUpgradeDocket(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-required-v0-" .
            bin2hex(random_bytes(6));
        try {
            $installer = new OperatorRootPersonnelInstallationService($root);
            $service = new RequiredV0PersonnelInstallationService($installer);
            $result = $service->install("imperium-v0-test");
            self::assertSame($result, $service->install("imperium-v0-test"));
            self::assertSame(
                "ALL_REQUIRED_V0_SEATS_OCCUPIED_PRE_OPERATIONAL",
                $result["status"],
            );
            self::assertSame(
                count(RequiredV0SeatRegistry::all()),
                $result["required_seat_count"],
            );
            self::assertFalse($result["persona_required"]);
            self::assertFalse($result["profile_required"]);
            self::assertFalse($result["officer_artifact_required"]);
            foreach ($result["installations"] as $installation) {
                self::assertSame(
                    "GENERIC_V0_PLACEHOLDER",
                    $installation["founding_class"],
                );
                self::assertSame("0", $installation["version"]);
                self::assertFalse(array_key_exists("persona", $installation));
                self::assertFalse(array_key_exists("profile", $installation));
                self::assertFalse(array_key_exists("officer", $installation));
                self::assertSame("ACTIVE", $installation["binding"]["status"]);
                self::assertFalse($installation["execution_authority"]);
                self::assertTrue(
                    $installation["mandatory_first_order_upgrade"],
                );
                if ("foundry.reviewer.adversarial" === $installation["seat"]) {
                    self::assertTrue(
                        $installation["binding"]["review_authority"],
                    );
                }
                if ("hagiography.sanctographer" === $installation["seat"]) {
                    self::assertTrue(
                        $installation["binding"]["authorship_authority"],
                    );
                }
                if ("garrison.constable" === $installation["seat"]) {
                    self::assertTrue(
                        $installation["binding"][
                            "inventory_response_authority"
                        ],
                    );
                    self::assertTrue(
                        $installation["binding"][
                            "persona_reservation_disposition_authority"
                        ],
                    );
                    self::assertTrue(
                        $installation["binding"][
                            "profile_derivation_handoff_disposition_authority"
                        ],
                    );
                }
                if ("laboratorium.alchemist" === $installation["seat"]) {
                    self::assertTrue(
                        $installation["binding"][
                            "profile_derivation_commission_acceptance_authority"
                        ],
                    );
                }
            }
            $operationalization = new OperatorRootOperationalizationService(
                $root,
            );
            $seal = $operationalization->seal("imperium-v0-test");
            self::assertSame(
                $seal,
                $operationalization->seal("imperium-v0-test"),
            );
            self::assertSame("IMPERIUM_OPERATIONAL", $seal["status"]);
            self::assertTrue($seal["required_seats_complete"]);
            self::assertSame(
                count(RequiredV0SeatRegistry::all()),
                $seal["required_seat_count"],
            );
            self::assertCount(
                count(RequiredV0SeatRegistry::all()),
                $seal["upgrade_docket"],
            );
            foreach ($seal["upgrade_docket"] as $upgrade) {
                self::assertSame(
                    "GENERIC_V0_PLACEHOLDER",
                    $upgrade["founding_class"],
                );
                self::assertSame("0", $upgrade["placeholder_version"]);
                self::assertSame(
                    "DEFERRED_FOR_OPTIONAL_PREPARATION",
                    $upgrade["program_status"],
                );
            }
            try {
                $service->install("imperium-v0-test");
                self::fail(
                    "v0 installation remained open after operationalization.",
                );
            } catch (\RuntimeException $e) {
                self::assertSame(
                    "B212_OPERATOR_ROOT_WINDOW_CLOSED",
                    $e->getMessage(),
                );
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testPrimaryActivationAllowsTestDriveBeforeOptionalUpgradePlanning(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-v0-activation-" .
            bin2hex(random_bytes(6));
        try {
            $personnel = new OperatorRootPersonnelInstallationService($root);
            $required = new RequiredV0PersonnelInstallationService($personnel);
            $operationalization = new OperatorRootOperationalizationService(
                $root,
            );
            $upgrades = new OperatorRootUpgradePlanningService($root);
            $state = new StateStore($root);
            $activation = new V0ActivationService(
                $required,
                $operationalization,
                $upgrades,
                $state,
            );
            $masterMason = new MasterMason($state, $activation);
            $result = $masterMason->activate("imperium-activation-test");
            self::assertSame("CURIA_READY", $result["state"]["state"]);
            self::assertSame(
                "OPERATOR_ROOT_V0",
                $result["state"]["activation_mode"],
            );
            self::assertSame(
                "DEFERRED_FOR_TEST_DRIVE",
                $result["upgrade_program_status"],
            );
            self::assertSame(null, $result["upgrade_plan"]);
            self::assertTrue(
                $result["operationalization"][
                    "test_drive_permitted_before_upgrades"
                ],
            );
            self::assertFalse(
                $result["operationalization"][
                    "upgrade_program_required_for_activation"
                ],
            );
            $runtime = $result["state"]["events"][0]["output"]["runtime"];
            self::assertTrue($runtime["addressable"]);
            self::assertCount(3, $runtime["occupants"]);
            foreach ($runtime["occupants"] as $occupant) {
                self::assertSame("0", $occupant["placeholder_version"]);
                self::assertSame("active", $occupant["status"]);
            }
            $prepared = $masterMason->activate("imperium-activation-test", true);
            self::assertSame(
                "PREPARED_NOT_STARTED",
                $prepared["upgrade_program_status"],
            );
            self::assertSame(
                "PREPARED_NOT_STARTED",
                $prepared["upgrade_plan"]["status"],
            );
            self::assertTrue(
                $prepared["upgrade_plan"][
                    "operator_may_test_drive_before_start"
                ],
            );
            self::assertFalse($prepared["upgrade_plan"]["execution_authority"]);
            self::assertSame(
                "foundry.reviewer.adversarial",
                $prepared["upgrade_plan"]["ordered_upgrades"][0]["seat"],
            );
            self::assertSame(
                "foundry.artificer",
                $prepared["upgrade_plan"]["ordered_upgrades"][1]["seat"],
            );
            self::assertSame(
                "garrison.constable",
                $prepared["upgrade_plan"]["ordered_upgrades"][2]["seat"],
            );
            self::assertSame(
                "laboratorium.alchemist",
                $prepared["upgrade_plan"]["ordered_upgrades"][3]["seat"],
            );
            self::assertSame(
                "conscription.recruiter",
                $prepared["upgrade_plan"]["ordered_upgrades"][4]["seat"],
            );
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir()
                ? rmdir($item->getPathname())
                : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
