<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\AdversarialReviewerBootstrapSeedAdmissionIntakeService;
use PHPUnit\Framework\TestCase;

final class AdversarialReviewerBootstrapSeedAdmissionIntakeServiceTest extends
    TestCase
{
    public function testConstableReturnsPackageMissingSenateConfirmationWithoutCustody(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-reviewer-seed-intake-" .
            bin2hex(random_bytes(6));
        $deliveryId =
            "adversarial-reviewer-bootstrap-seed-admission-delivery-" .
            str_repeat("a", 20);
        $delivery = [
            "schema" =>
                "imperium.garrison-adversarial-reviewer-bootstrap-seed-admission-delivery/v1",
            "delivery_id" => $deliveryId,
            "instance_id" => "imperium-test",
            "sender" => ["seat" => "foundry.artificer"],
            "recipient" => [
                "office" => "garrison",
                "seat" => "garrison.constable",
            ],
            "production_approval_id" => "approval",
            "production_approval_digest" => "approval-digest",
            "persona_candidate_id" => "candidate",
            "persona_candidate_digest" => "candidate-digest",
            "persona_id" => "foundry.adversarial-reviewer",
            "persona_version" => "1.0.0",
            "requested_disposition" =>
                "CONSIDER_EXACT_PERSONA_FOR_GARRISON_ADMISSION",
            "status" => "DELIVERED_PENDING_GARRISON_ACCEPTANCE",
            "recipient_acceptance" => null,
            "production_approval" => true,
            "admission_authority" => false,
            "admission_decision" => null,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "seat_binding_authority" => false,
            "review_findings_authority" => false,
            "candidate_approval_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/garrison/inbox/adversarial-reviewer-bootstrap-seed-admissions",
            $deliveryId,
            $delivery,
        );
        $bindingId = "garrison-constable-binding-" . str_repeat("b", 20);
        $binding = [
            "schema" => "imperium.garrison-constable-occupancy/v1",
            "binding_id" => $bindingId,
            "instance_id" => "imperium-test",
            "seat" => "garrison.constable",
            "manifestation_id" => "constable",
            "occupancy_generation" => 1,
            "status" => "ACTIVE",
            "binding_atomic" => true,
            "persona_admission_disposition_authority" => true,
            "custody_registration_authority" => true,
            "selection_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root . "/var/imperium/offices/garrison/occupancy",
            $bindingId,
            $binding,
        );
        try {
            $service = new AdversarialReviewerBootstrapSeedAdmissionIntakeService(
                $root,
            );
            $return = $service->inspect($deliveryId);
            self::assertSame($return, $service->inspect($deliveryId));
            self::assertSame(
                "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE",
                $return["disposition"],
            );
            self::assertSame(
                [
                    "MISSING_EXACT_SENATE_CONFIRMATION_ID",
                    "MISSING_EXACT_SENATE_CONFIRMATION_DIGEST",
                    "MISSING_EXACT_TESTED_MANIFESTATION_ID",
                ],
                $return["defects"],
            );
            self::assertFalse($return["bootstrap_exception_extended"]);
            self::assertFalse($return["custody_created"]);
            self::assertFalse($return["admission_authority"]);
            self::assertFileDoesNotExist(
                $root . "/var/imperium/offices/garrison/custody/candidate.json",
            );
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
