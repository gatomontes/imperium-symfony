<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Senate\SenateResidentProvisioningCaseService;
use PHPUnit\Framework\TestCase;

final class SenateResidentProvisioningCaseServiceTest extends TestCase
{
    public function testOpensSeparateNonAuthorizingResidentCases(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-senate-resident-cases-" .
            bin2hex(random_bytes(6));
        mkdir($root . "/offices/senate", 0770, true);
        $required = [];
        foreach (
            [
                [
                    "senate.lord-speaker",
                    "profile-lord-speaker.md",
                    "seat-resident-lord-speaker.md",
                ],
                [
                    "senate.bailiff",
                    "profile-bailiff.md",
                    "seat-resident-bailiff.md",
                ],
            ]
            as [$seat, $profile, $contract]
        ) {
            file_put_contents(
                $root . "/offices/senate/" . $profile,
                $profile . "\n",
            );
            file_put_contents(
                $root . "/offices/senate/" . $contract,
                $contract . "\n",
            );
            $required[] = [
                "seat" => $seat,
                "persona_state" => "CONSTRUCTION_REQUIRED",
                "profile_state" => "SOURCE_CONTRACT_ONLY",
                "profile_source" => "offices/senate/" . $profile,
                "profile_source_digest" =>
                    "sha256:" . hash("sha256", $profile . "\n"),
                "seat_contract_source" => "offices/senate/" . $contract,
                "seat_contract_source_digest" =>
                    "sha256:" . hash("sha256", $contract . "\n"),
                "canonical_staff_package" => null,
                "occupancy" => null,
            ];
        }
        $demandId = "senate-activation-" . str_repeat("a", 20);
        $demand = [
            "schema" => "imperium.senate-activation-demand/v1",
            "demand_id" => $demandId,
            "instance_id" => "imperium-test",
            "recipient" => ["id" => "mastermason"],
            "source_confirmation_case_id" => "confirmation-case",
            "source_confirmation_case_digest" => "confirmation-case-digest",
            "source_confirmation_request_id" => "confirmation-request",
            "source_confirmation_request_digest" =>
                "confirmation-request-digest",
            "persona_candidate_id" => "candidate",
            "persona_candidate_digest" => "candidate-digest",
            "required_seats" => $required,
            "status" => "SENATE_RESIDENT_STAFF_CONSTRUCTION_REQUIRED",
            "occupancy_claimed" => false,
            "construction_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "confirmation_acceptance_authority" => false,
            "senate_disposition_authority" => false,
            "assembly_request_authority" => false,
            "witness_instantiation_authority" => false,
            "senate_finding_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root . "/var/imperium/mastermason/spawning-requests",
            $demandId,
            $demand,
        );
        try {
            $service = new SenateResidentProvisioningCaseService($root);
            $result = $service->open($demandId);
            self::assertSame($result, $service->open($demandId));
            self::assertCount(2, $result["cases"]);
            self::assertSame(
                ["senate.lord-speaker", "senate.bailiff"],
                array_column($result["cases"], "target_seat"),
            );
            self::assertNotSame(
                $result["cases"][0]["case_id"],
                $result["cases"][1]["case_id"],
            );
            foreach ($result["cases"] as $case) {
                self::assertSame(
                    "BLOCKED_PENDING_EXPLICIT_CONSTRUCTION_AUTHORIZATION",
                    $case["status"],
                );
                self::assertSame(
                    "CONSTRUCTION_REQUIRED",
                    $case["persona_state"],
                );
                self::assertSame(
                    "SOURCE_CONTRACT_ONLY",
                    $case["profile_state"],
                );
                foreach (
                    [
                        "construction_authority",
                        "construction_authority_exercisable",
                        "persona_approval_authority",
                        "profile_approval_authority",
                        "spawning_authority",
                        "seat_binding_authority",
                        "confirmation_acceptance_authority",
                        "senate_disposition_authority",
                        "assembly_request_authority",
                        "witness_instantiation_authority",
                        "senate_finding_authority",
                        "admission_authority",
                        "execution_authority",
                    ]
                    as $key
                ) {
                    self::assertFalse($case[$key]);
                }
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
