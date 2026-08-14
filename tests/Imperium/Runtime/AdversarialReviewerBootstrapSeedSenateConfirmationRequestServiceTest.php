<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedSenateConfirmationRequestService;
use PHPUnit\Framework\TestCase;

final class AdversarialReviewerBootstrapSeedSenateConfirmationRequestServiceTest
    extends TestCase
{
    public function testFoundrySubmitsReturnedExactPersonaForSterileSenateConfirmation(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-senate-request-" .
            bin2hex(random_bytes(6));
        $candidateId =
            "adversarial-reviewer-persona-candidate-" . str_repeat("a", 20);
        $basis = ["name" => "Blackquill", "kind" => "persona-design-basis"];
        $candidate = [
            "persona_candidate_id" => $candidateId,
            "instance_id" => "imperium-test",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "sources" => ["design_basis" => $basis],
            "persona" => ["role" => "Adversarial Reviewer"],
            "construction_complete" => true,
            "sealed" => true,
            "production_approval" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-persona-candidates",
            $candidateId,
            $candidate,
        );
        $approvalId =
            "adversarial-reviewer-bootstrap-seed-production-approval-" .
            str_repeat("b", 20);
        $approval = [
            "schema" =>
                "imperium.foundry-adversarial-reviewer-bootstrap-seed-production-approval/v1",
            "production_approval_id" => $approvalId,
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "bootstrap_seed_acceptance_id" => "acceptance",
            "bootstrap_seed_acceptance_digest" => "acceptance-digest",
            "authorization_act_id" => "act",
            "authorization_act_digest" => "act-digest",
            "actor" => ["seat" => "foundry.artificer"],
            "bootstrap_seed_boundary" => ["self_review_prohibited" => true],
            "status" => "APPROVED_PENDING_GARRISON_ADMISSION",
            "production_approval" => true,
            "bootstrap_seed_authority_consumed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/adversarial-reviewer-bootstrap-seed-production-approvals",
            $approvalId,
            $approval,
        );
        $deliveryId =
            "adversarial-reviewer-bootstrap-seed-admission-delivery-" .
            str_repeat("c", 20);
        $delivery = [
            "delivery_id" => $deliveryId,
            "instance_id" => "imperium-test",
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "status" => "DELIVERED_PENDING_GARRISON_ACCEPTANCE",
            "production_approval" => true,
            "admission_authority" => false,
            "admission_decision" => null,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/garrison/inbox/adversarial-reviewer-bootstrap-seed-admissions",
            $deliveryId,
            $delivery,
        );
        $returnId =
            "adversarial-reviewer-bootstrap-seed-admission-return-" .
            str_repeat("d", 20);
        $return = [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-bootstrap-seed-admission-return/v1",
            "return_id" => $returnId,
            "instance_id" => "imperium-test",
            "source_delivery_id" => $deliveryId,
            "source_delivery_digest" => $delivery["record_digest"],
            "production_approval_id" => $approvalId,
            "production_approval_digest" => $approval["record_digest"],
            "persona_candidate_id" => $candidateId,
            "persona_candidate_digest" => $candidate["record_digest"],
            "constable" => ["seat" => "garrison.constable"],
            "recipient" => [
                "office" => "foundry",
                "seat" => "foundry.artificer",
            ],
            "disposition" => "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE",
            "defects" => [
                "MISSING_EXACT_SENATE_CONFIRMATION_ID",
                "MISSING_EXACT_SENATE_CONFIRMATION_DIGEST",
                "MISSING_EXACT_TESTED_MANIFESTATION_ID",
            ],
            "bootstrap_exception_extended" => false,
            "admission_decision" => "REFUSED",
            "custody_created" => false,
            "admission_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "selection_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/inbox/adversarial-reviewer-bootstrap-seed-admission-returns",
            $returnId,
            $return,
        );
        try {
            $service = new AdversarialReviewerBootstrapSeedSenateConfirmationRequestService(
                $root,
            );
            $request = $service->request($returnId);
            self::assertSame($request, $service->request($returnId));
            self::assertSame(
                "PENDING_ADMISSION_PERSONA_QUALIFICATION",
                $request["proceeding_class"],
            );
            self::assertSame(
                "examination_only",
                $request["examination_contract"]["profile_class"],
            );
            self::assertTrue(
                $request["examination_contract"]["sterile_witness_required"],
            );
            self::assertTrue(
                $request["examination_contract"]["self_review_prohibited"],
            );
            self::assertSame(
                "DELIVERED_PENDING_SENATE_ACCEPTANCE",
                $request["status"],
            );
            self::assertNull($request["senate_finding"]);
            foreach (
                [
                    "admission_authority",
                    "profile_approval_authority",
                    "spawning_authority",
                    "seat_binding_authority",
                    "candidate_approval_authority",
                    "execution_authority",
                ]
                as $key
            ) {
                self::assertFalse($request[$key]);
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
