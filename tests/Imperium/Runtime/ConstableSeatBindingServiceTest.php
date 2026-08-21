<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Garrison\CanonicalConstableRegistry;
use App\Imperium\Runtime\Garrison\ConstableSeatBindingService;
use PHPUnit\Framework\TestCase;

final class ConstableSeatBindingServiceTest extends TestCase
{
    public function testAtomicallyBindsQualifiedConstableAndActivatesExactGarrisonJurisdiction(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-constable-binding-" .
            bin2hex(random_bytes(6));
        $project = dirname(__DIR__, 3);
        $bootstrap = new StateStore($root);
        $bootstrap->locked(static function () use ($bootstrap): void {
            $bootstrap->write([
                "state" => "CURIA_READY",
                "binding" => ["instance_id" => "imperium-test"],
                "events" => [],
            ]);
        });
        mkdir($root . "/runtime/artifacts", 0770, true);
        copy(
            $project . "/runtime/artifacts/generic-officer-substrate.json",
            $root . "/runtime/artifacts/generic-officer-substrate.json",
        );
        $registry = new CanonicalConstableRegistry($project);
        $substrate = new GenericOfficerSubstrateRegistry($root);
        $member = $registry->member();
        $deliveryId = "qualified-delivery-1234567890abcdef1234";
        $manifestationId =
            "imperium-test.officer.garrison.constable.123456789abc";
        $qualification = [
            "candidate_id" => $manifestationId,
            "qualification_contract" => $member["qualification_contract"],
            "disposition" => "QUALIFIED",
        ];
        $packet = [
            "schema" => "imperium.qualified-manifestation-packet/v1",
            "delivery_id" => $deliveryId,
            "commission" => ["consumed" => true],
            "candidate" => [
                "manifestation_id" => $manifestationId,
                "instance_id" => "imperium-test",
                "persona" => $member["persona"],
                "profile" => $member["profile"],
                "substrate_instance" => [
                    "substrate" => $substrate->current(),
                    "status" => "PROFILE_INSTALLED",
                ],
                "target_seat" => "garrison.constable",
                "target_occupancy_generation" => 1,
                "status" => "QUALIFIED_UNBOUND",
            ],
            "qualification" => $qualification,
            "qualification_digest" => hash(
                "sha256",
                CanonicalJson::encode($qualification),
            ),
            "sealed" => true,
            "seat_binding_authority" => false,
            "inventory_response_authority" => false,
            "execution_authority" => false,
        ];
        $packet["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($packet),
        );
        mkdir(
            $root . "/var/imperium/mastermason/qualified-manifestations",
            0770,
            true,
        );
        file_put_contents(
            $root .
                "/var/imperium/mastermason/qualified-manifestations/" .
                $deliveryId .
                ".json",
            json_encode($packet, JSON_THROW_ON_ERROR),
        );
        try {
            $service = new ConstableSeatBindingService(
                $root,
                $bootstrap,
                $registry,
                $substrate,
            );
            $occupancy = $service->bind($deliveryId);
            self::assertSame($occupancy, $service->bind($deliveryId));
            self::assertSame(
                "imperium.garrison-constable-occupancy/v1",
                $occupancy["schema"],
            );
            self::assertSame("garrison.constable", $occupancy["seat"]);
            self::assertSame($manifestationId, $occupancy["manifestation_id"]);
            self::assertSame("ACTIVE", $occupancy["status"]);
            self::assertSame(0, $occupancy["prior_occupancy_generation"]);
            self::assertSame(1, $occupancy["occupancy_generation"]);
            self::assertTrue($occupancy["binding_atomic"]);
            self::assertTrue($occupancy["seat_binding_authority"]);
            self::assertSame(
                "CONSUMED_BY_ATOMIC_BINDING",
                $occupancy["seat_binding_disposition"],
            );
            self::assertTrue($occupancy["inventory_response_authority"]);
            self::assertTrue(
                $occupancy["persona_admission_disposition_authority"],
            );
            self::assertTrue($occupancy["custody_registration_authority"]);
            self::assertTrue(
                $occupancy["persona_reservation_disposition_authority"],
            );
            self::assertFalse($occupancy["selection_authority"]);
            self::assertFalse($occupancy["execution_authority"]);
            self::assertFileExists(
                $root .
                    "/var/imperium/offices/garrison/occupancy/" .
                    $occupancy["binding_id"] .
                    ".json",
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
        foreach (array_diff(scandir($path) ?: [], [".", ".."]) as $entry) {
            $child = $path . "/" . $entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
