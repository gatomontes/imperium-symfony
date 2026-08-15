<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService;
use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService;
use App\Imperium\Runtime\Bootstrap\RequiredV0SeatRegistry;
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
                    "FIRST_ORDER_OF_BUSINESS",
                    $upgrade["priority"],
                );
                self::assertSame(
                    "GOVERNED_REASSESSMENT_AND_UPGRADE",
                    $upgrade["required_disposition"],
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
