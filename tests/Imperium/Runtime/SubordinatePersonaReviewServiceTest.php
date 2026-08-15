<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewCognitionGateway;
use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewService;
use PHPUnit\Framework\TestCase;

final class SubordinatePersonaReviewServiceTest extends TestCase
{
    public function testSealsBoundedReviewWithoutApprovingPersona(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-persona-review-" .
            bin2hex(random_bytes(6));
        $caseId = "subordinate-construction-case-" . str_repeat("a", 20);
        $case = ["case_id" => $caseId];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-construction-cases",
            $caseId,
            $case,
        );
        $specId = "subordinate-persona-specification-" . str_repeat("b", 20);
        $basis = [
            "clarification_return_id" =>
                "subordinate-clarification-return-" . str_repeat("d", 20),
            "original_clarification" => [
                "unresolved_questions" => [
                    "No Garrison facts exist for an unfinished Persona.",
                ],
            ],
        ];
        $supersedes = [
            "specification_id" =>
                "subordinate-persona-specification-" . str_repeat("e", 20),
            "specification_digest" => "prior-digest",
            "specification_version" => 1,
        ];
        $spec = [
            "schema" => "imperium.foundry-subordinate-persona-specification/v1",
            "specification_id" => $specId,
            "specification_version" => 2,
            "supersedes" => $supersedes,
            "revision_basis" => $basis,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-persona-specifications",
            $specId,
            $spec,
        );
        $candidateId = "subordinate-persona-candidate-" . str_repeat("c", 20);
        $candidate = [
            "schema" => "imperium.foundry-subordinate-persona-candidate/v1",
            "candidate_id" => $candidateId,
            "instance_id" => "imperium-test",
            "persona_specification_id" => $specId,
            "persona_specification_digest" => $spec["record_digest"],
            "persona_specification_version" => 2,
            "specification_supersedes" => $supersedes,
            "specification_revision_basis" => $basis,
            "dispatch_kind" => "SPECIFICATION_REVISION_REISSUE",
            "superseded_commissions" => [
                ["office" => "hagiography", "commission_id" => "prior-h"],
                ["office" => "studium", "commission_id" => "prior-s"],
            ],
            "subordinate_construction_case_id" => $caseId,
            "subordinate_construction_case_digest" => $case["record_digest"],
            "artificer" => ["seat" => "foundry.artificer"],
            "status" => "ASSEMBLED_PENDING_FOUNDRY_REVIEW",
            "assembly_complete" => true,
            "sealed" => true,
            "persona_approval_authority" => false,
            "admission_authority" => false,
            "execution_authority" => false,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-persona-candidates",
            $candidateId,
            $candidate,
        );
        $gateway = new class implements SubordinatePersonaReviewCognitionGateway
        {
            public function review(array $c, array $s, array $case): array
            {
                return [
                    "disposition" => "READY_FOR_ADVERSARIAL_REVIEW",
                    "findings" => [
                        "Specification and Office boundaries are preserved.",
                    ],
                    "unresolved_blockers" => [],
                    "adversarial_review_brief" =>
                        "Challenge evidence transferability and doctrine consistency.",
                ];
            }
        };
        try {
            $service = new SubordinatePersonaReviewService($root, $gateway);
            $review = $service->review($candidateId);
            self::assertSame($review, $service->review($candidateId));
            self::assertSame(
                "SEALED_PENDING_FOUNDRY_ADVERSARIAL_REVIEW",
                $review["status"],
            );
            self::assertSame(2, $review["persona_specification_version"]);
            self::assertSame($basis, $review["specification_revision_basis"]);
            self::assertSame(
                "SPECIFICATION_REVISION_REISSUE",
                $review["dispatch_kind"],
            );
            self::assertCount(2, $review["superseded_commissions"]);
            self::assertTrue($review["adversarial_review_authority"]);
            self::assertFalse($review["persona_approval_authority"]);
            self::assertFalse($review["admission_authority"]);
            self::assertFalse($review["execution_authority"]);
        } finally {
            $this->removeTree($root);
        }
    }
    private function write(string $dir, string $id, array &$record): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
        file_put_contents(
            $dir . "/" . $id . ".json",
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
