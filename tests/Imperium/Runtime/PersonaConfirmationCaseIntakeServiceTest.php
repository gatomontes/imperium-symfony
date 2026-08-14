<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Senate\PersonaConfirmationCaseIntakeService;
use PHPUnit\Framework\TestCase;

final class PersonaConfirmationCaseIntakeServiceTest extends TestCase
{
    public function testPreservesExactCaseWithoutImpersonatingVacantSenateSeats(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-senate-case-" .
            bin2hex(random_bytes(6));
        $requestId =
            "adversarial-reviewer-bootstrap-seed-confirmation-request-" .
            str_repeat("a", 20);
        $contract = [
            "subject_state" => "production-approved-pending-admission",
            "manifestation_required" => true,
            "profile_class" => "examination_only",
            "sterile_witness_required" => true,
            "exact_candidate_only" => true,
            "independent_senate_disposition_required" => true,
            "self_review_prohibited" => true,
            "ordinary_operational_use_prohibited" => true,
        ];
        $request = [
            "schema" => "imperium.senate-persona-confirmation-request/v1",
            "confirmation_request_id" => $requestId,
            "instance_id" => "imperium-test",
            "proceeding_class" => "PENDING_ADMISSION_PERSONA_QUALIFICATION",
            "requester" => ["seat" => "foundry.artificer"],
            "recipient" => [
                "office" => "senate",
                "seat" => "senate.lord-speaker",
            ],
            "source_admission_return_id" => "return",
            "source_admission_return_digest" => "return-digest",
            "production_approval_id" => "approval",
            "production_approval_digest" => "approval-digest",
            "authorization_act_id" => "act",
            "authorization_act_digest" => "act-digest",
            "persona_candidate_id" => "candidate",
            "persona_candidate_digest" => "candidate-digest",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "persona" => ["role" => "Adversarial Reviewer"],
            "design_basis" => ["name" => "Blackquill"],
            "bootstrap_seed_boundary" => ["self_review_prohibited" => true],
            "examination_contract" => $contract,
            "requested_disposition" =>
                "OPEN_EXACT_MANIFESTATION_BOUND_CONFIRMATION_CASE",
            "status" => "DELIVERED_PENDING_SENATE_ACCEPTANCE",
            "recipient_acceptance" => null,
            "senate_finding" => null,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/senate/inbox/persona-confirmation-requests",
            $requestId,
            $request,
        );
        try {
            $service = new PersonaConfirmationCaseIntakeService($root);
            $case = $service->preserve($requestId);
            self::assertSame($case, $service->preserve($requestId));
            self::assertSame(
                "BLOCKED_PENDING_SENATE_OCCUPANCY",
                $case["status"],
            );
            self::assertSame(
                ["senate.lord-speaker", "senate.bailiff"],
                $case["activation_required"],
            );
            self::assertNull($case["lord_speaker_occupancy"]);
            self::assertNull($case["bailiff_occupancy"]);
            self::assertTrue($case["request_preserved"]);
            self::assertNull($case["recipient_acceptance"]);
            self::assertNull($case["confirmation_plan"]);
            self::assertFalse($case["assembly_request_authority"]);
            self::assertFalse($case["witness_instantiation_authority"]);
            self::assertNull($case["senate_finding"]);
            foreach (
                [
                    "admission_authority",
                    "profile_approval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($case[$key]);
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
