<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\SubordinateAuthorshipCommissionAcceptanceService;
use App\Imperium\Runtime\Authorship\SubordinatePersonaSectionAuthorshipGateway;
use App\Imperium\Runtime\Authorship\SubordinatePersonaSectionAuthorshipService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaAuthorshipCommissionService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerDemandService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaAssemblyService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewCognitionGateway;
use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewService;
use PHPUnit\Framework\TestCase;

final class SubordinateAuthorshipRevisionReissueAcceptanceTest extends TestCase
{
    public function testBothOfficesAcceptFreshRevisionReissuesWithFullLineage(): void
    {
        $root =
            sys_get_temp_dir() .
            "/imperium-subordinate-reissue-accept-" .
            bin2hex(random_bytes(6));
        $caseId = "subordinate-construction-case-" . str_repeat("a", 20);
        $case = [
            "schema" => "imperium.foundry-subordinate-construction-case/v1",
            "case_id" => $caseId,
            "instance_id" => "imperium-test",
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "resolution-digest",
            "subordinate_requirements" => [
                "required_specializations" => ["work"],
            ],
            "status" => "OPEN_PENDING_PERSONA_SPECIFICATION",
            "construction_authority" => true,
        ];
        $this->write(
            $root .
                "/var/imperium/offices/foundry/subordinate-construction-cases",
            $caseId,
            $case,
        );
        $priorId = "subordinate-persona-specification-" . str_repeat("b", 20);
        $prior = $this->specification($priorId, $case, 1, null, null);
        $specificationDirectory =
            $root .
            "/var/imperium/offices/foundry/subordinate-persona-specifications";
        $this->write($specificationDirectory, $priorId, $prior);
        $dispatch = new SubordinatePersonaAuthorshipCommissionService($root);
        $priorDispatch = $dispatch->dispatch($priorId);
        $revisedId = "subordinate-persona-specification-" . str_repeat("c", 20);
        $revisionBasis = [
            "clarification_return_id" =>
                "subordinate-clarification-return-" . str_repeat("d", 20),
            "original_clarification" => [
                "unresolved_questions" => [
                    "Garrison has no facts for an unfinished Persona.",
                ],
            ],
        ];
        $revised = $this->specification(
            $revisedId,
            $case,
            2,
            [
                "specification_id" => $priorId,
                "specification_digest" => $prior["record_digest"],
                "specification_version" => 1,
            ],
            $revisionBasis,
        );
        $this->write($specificationDirectory, $revisedId, $revised);
        try {
            $reissue = $dispatch->dispatch($revisedId);
            self::assertSame(
                "SPECIFICATION_REVISION_REISSUE",
                $reissue["dispatch_kind"],
            );
            $acceptance = new SubordinateAuthorshipCommissionAcceptanceService(
                $root,
            );
            $authorship = new SubordinatePersonaSectionAuthorshipService(
                $root,
                new class implements SubordinatePersonaSectionAuthorshipGateway
                {
                    public function author(
                        string $office,
                        array $acceptance,
                        array $commission,
                        array $specification,
                        array $case,
                    ): array {
                        return [
                            "disposition" => "SECTION_PACKET_COMPLETE",
                            "authored_sections" => [
                                $office . "_replacement" => [
                                    "Complete content derived only for specification v2.",
                                ],
                            ],
                            "source_citations" => [
                                "resolution:resolution-digest",
                            ],
                            "unresolved_questions" => [],
                        ];
                    }
                },
            );
            $products = [];
            foreach ($reissue["commissions"] as $commission) {
                $office = $commission["office"];
                $role =
                    "hagiography" === $office ? "sanctographer" : "chancellor";
                $seat = $office . "." . $role;
                $bindingId =
                    $office . "-" . $role . "-binding-" . str_repeat("e", 20);
                $binding = [
                    "schema" => "imperium.authorship-resident-occupancy/v1",
                    "binding_id" => $bindingId,
                    "instance_id" => "imperium-test",
                    "office" => $office,
                    "seat" => $seat,
                    "manifestation_id" => "manifestation-" . $role,
                    "occupancy_generation" => 1,
                    "status" => "ACTIVE",
                    "binding_atomic" => true,
                    "authorship_authority" => true,
                    "execution_authority" => false,
                ];
                $this->write(
                    $root . "/var/imperium/offices/" . $office . "/occupancy",
                    $bindingId,
                    $binding,
                );
                $accepted = $acceptance->accept(
                    $office,
                    $commission["commission_id"],
                    $bindingId,
                );
                self::assertSame(
                    "SPECIFICATION_REVISION_REISSUE",
                    $accepted["dispatch_kind"],
                );
                self::assertCount(2, $accepted["superseded_commissions"]);
                foreach ($accepted["superseded_commissions"] as $reference) {
                    $matches = array_values(
                        array_filter(
                            $priorDispatch["commissions"],
                            static fn(
                                array $priorCommission,
                            ): bool => $reference["commission_id"] ===
                                $priorCommission["commission_id"],
                        ),
                    );
                    self::assertCount(1, $matches);
                    self::assertSame(
                        $matches[0]["record_digest"],
                        $reference["commission_digest"],
                    );
                    self::assertSame(
                        "SUPERSEDED_BY_SPECIFICATION_REVISION",
                        $reference["disposition"],
                    );
                }
                self::assertSame(2, $accepted["persona_specification_version"]);
                self::assertSame(
                    $revised["supersedes"],
                    $accepted["specification_supersedes"],
                );
                self::assertSame(
                    $revisionBasis,
                    $accepted["specification_revision_basis"],
                );
                self::assertTrue($accepted["authorship_authority_exercisable"]);
                self::assertFalse($accepted["persona_assembly_authority"]);
                $product = $authorship->author(
                    $office,
                    $accepted["acceptance_id"],
                );
                self::assertSame(
                    $product,
                    $authorship->author($office, $accepted["acceptance_id"]),
                );
                self::assertSame(
                    "SEALED_PENDING_FOUNDRY_ASSEMBLY",
                    $product["status"],
                );
                self::assertSame(
                    "SPECIFICATION_REVISION_REISSUE",
                    $product["dispatch_kind"],
                );
                self::assertSame(
                    $accepted["superseded_commissions"],
                    $product["superseded_commissions"],
                );
                self::assertSame([], $product["unresolved_questions"]);
                self::assertSame(2, $product["persona_specification_version"]);
                self::assertSame(
                    $revisionBasis,
                    $product["specification_revision_basis"],
                );
                self::assertTrue($product["authorship_complete"]);
                self::assertTrue($product["sealed"]);
                self::assertFalse($product["persona_assembly_authority"]);
                $products[$office] = $product;
            }
            self::assertSame(["hagiography", "studium"], array_keys($products));
            $assembly = new SubordinatePersonaAssemblyService($root);
            $candidate = $assembly->assemble($revisedId);
            self::assertSame($candidate, $assembly->assemble($revisedId));
            self::assertSame(
                "ASSEMBLED_PENDING_FOUNDRY_REVIEW",
                $candidate["status"],
            );
            self::assertSame(
                "SPECIFICATION_REVISION_REISSUE",
                $candidate["dispatch_kind"],
            );
            self::assertSame(
                $reissue["superseded_commissions"],
                $candidate["superseded_commissions"],
            );
            self::assertCount(2, $candidate["section_products"]);
            self::assertSame([], $candidate["unresolved_questions"]);
            self::assertTrue($candidate["assembly_complete"]);
            self::assertFalse($candidate["persona_approval_authority"]);
            $reviewService = new SubordinatePersonaReviewService(
                $root,
                new class implements SubordinatePersonaReviewCognitionGateway {
                    public function review(
                        array $candidate,
                        array $specification,
                        array $case,
                    ): array {
                        return [
                            "disposition" => "READY_FOR_ADVERSARIAL_REVIEW",
                            "findings" => [
                                "Both replacement packets match specification v2.",
                                "The original clarification remains traceable.",
                            ],
                            "unresolved_blockers" => [],
                            "adversarial_review_brief" =>
                                "Challenge the assembled v2 Persona without reopening superseded v1 requirements.",
                        ];
                    }
                },
            );
            $review = $reviewService->review($candidate["candidate_id"]);
            self::assertSame(
                $review,
                $reviewService->review($candidate["candidate_id"]),
            );
            self::assertSame(
                "SEALED_PENDING_FOUNDRY_ADVERSARIAL_REVIEW",
                $review["status"],
            );
            self::assertSame(
                $candidate["superseded_commissions"],
                $review["superseded_commissions"],
            );
            self::assertTrue($review["adversarial_review_authority"]);
            self::assertFalse($review["persona_approval_authority"]);
            $demandService = new AdversarialReviewerDemandService($root);
            $demand = $demandService->demand($review["review_id"]);
            self::assertSame(
                $demand,
                $demandService->demand($review["review_id"]),
            );
            self::assertSame(
                "PENDING_EXACT_ADVERSARIAL_REVIEWER_OCCUPATION",
                $demand["status"],
            );
            self::assertSame(
                $review["superseded_commissions"],
                $demand["superseded_commissions"],
            );
            self::assertSame(
                $candidate["candidate_id"],
                $demand["required_seat"]["candidate_id"],
            );
            self::assertFalse($demand["review_authority"]);
            self::assertFalse($demand["spawning_authority"]);
        } finally {
            $this->removeTree($root);
        }
    }

    private function specification(
        string $id,
        array $case,
        int $version,
        ?array $supersedes,
        ?array $revisionBasis,
    ): array {
        return [
            "schema" => "imperium.foundry-subordinate-persona-specification/v1",
            "specification_id" => $id,
            "specification_version" => $version,
            "supersedes" => $supersedes,
            "revision_basis" => $revisionBasis,
            "instance_id" => "imperium-test",
            "case_id" => $case["case_id"],
            "case_digest" => $case["record_digest"],
            "queue_position" => 1,
            "office" => "hagiography",
            "subordinate_staff_class" => "Chronicler",
            "source_resolution_id" => "resolution",
            "source_resolution_digest" => "resolution-digest",
            "artificer" => ["seat" => "foundry.artificer"],
            "inherited_requirements" => $case["subordinate_requirements"],
            "specification" => ["persona_name" => "Bounded Chronicler"],
            "status" => "SEALED_PENDING_PERSONA_CONSTRUCTION",
            "persona_specification_complete" => true,
            "construction_authority" => true,
            "persona_selection_authority" => false,
            "profile_approval_authority" => false,
            "spawning_authority" => false,
            "admission_authority" => false,
            "seat_binding_authority" => false,
            "execution_authority" => false,
            "sealed" => true,
        ];
    }

    private function write(string $directory, string $id, array &$record): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0770, true);
        }
        $record["record_digest"] = hash(
            "sha256",
            CanonicalJson::encode($record),
        );
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
