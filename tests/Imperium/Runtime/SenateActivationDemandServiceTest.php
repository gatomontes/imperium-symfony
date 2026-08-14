<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Senate\SenateActivationDemandService;
use PHPUnit\Framework\TestCase;

final class SenateActivationDemandServiceTest extends TestCase
{
    public function testDemandsMissingResidentConstructionWithoutInventingOccupantsOrAuthority(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-senate-activation-" .
            bin2hex(random_bytes(6));
        mkdir($root . "/offices/senate", 0770, true);
        foreach (
            [
                "profile-lord-speaker.md",
                "seat-resident-lord-speaker.md",
                "profile-bailiff.md",
                "seat-resident-bailiff.md",
            ]
            as $file
        ) {
            file_put_contents($root . "/offices/senate/" . $file, $file . "\n");
        }
        $caseId = "senate-confirmation-case-" . str_repeat("a", 20);
        $case = [
            "schema" => "imperium.senate-persona-confirmation-case/v1",
            "confirmation_case_id" => $caseId,
            "instance_id" => "imperium-test",
            "source_request_id" => "request",
            "source_request_digest" => "request-digest",
            "persona_candidate_id" => "candidate",
            "persona_candidate_digest" => "candidate-digest",
            "proceeding_class" => "PENDING_ADMISSION_PERSONA_QUALIFICATION",
            "status" => "BLOCKED_PENDING_SENATE_OCCUPANCY",
            "request_preserved" => true,
            "lord_speaker_occupancy" => null,
            "bailiff_occupancy" => null,
            "activation_required" => ["senate.lord-speaker", "senate.bailiff"],
            "assembly_request_authority" => false,
            "witness_instantiation_authority" => false,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/offices/senate/confirmation-cases",
            $caseId,
            $case,
        );
        try {
            $service = new SenateActivationDemandService($root);
            $demand = $service->demand($caseId);
            self::assertSame($demand, $service->demand($caseId));
            self::assertSame(
                "SENATE_RESIDENT_STAFF_CONSTRUCTION_REQUIRED",
                $demand["status"],
            );
            self::assertSame(
                ["senate.lord-speaker", "senate.bailiff"],
                array_column($demand["required_seats"], "seat"),
            );
            foreach ($demand["required_seats"] as $seat) {
                self::assertSame(
                    "CONSTRUCTION_REQUIRED",
                    $seat["persona_state"],
                );
                self::assertSame(
                    "SOURCE_CONTRACT_ONLY",
                    $seat["profile_state"],
                );
                self::assertNull($seat["canonical_staff_package"]);
                self::assertNull($seat["occupancy"]);
            }
            self::assertFalse($demand["occupancy_claimed"]);
            foreach (
                [
                    "construction_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "confirmation_acceptance_authority",
                    "assembly_request_authority",
                    "witness_instantiation_authority",
                    "senate_finding_authority",
                    "admission_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($demand[$key]);
            }
        } finally {
            $this->removeTree($root);
        }
    }
    private function digest(array &$record): void
    {
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
    }
    private function write(string $directory, string $id, array &$record): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0770, true);
        }
        $this->digest($record);
        file_put_contents(
            $directory . "/" . $id . ".json",
            json_encode($record, JSON_THROW_ON_ERROR),
        );
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
