<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\SubordinateAuthorshipCommissionAcceptanceService;
use App\Imperium\Runtime\Authorship\SubordinatePersonaSectionAuthorshipGateway;
use App\Imperium\Runtime\Authorship\SubordinatePersonaSectionAuthorshipService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaAuthorshipCommissionService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerDemandService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerProvisioningCaseService;
use App\Imperium\Runtime\Curia\AdversarialReviewerConstructionAuthorizationService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerConstructionAuthorizationAcceptanceService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerPersonaConstructionService;
use App\Imperium\Runtime\Curia\AdversarialReviewerBootstrapSeedAuthorizationService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedAuthorizationAcceptanceService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedProductionApprovalService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedAdmissionDeliveryService;
use App\Imperium\Runtime\Garrison\AdversarialReviewerBootstrapSeedAdmissionIntakeService;
use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedSenateConfirmationRequestService;
use App\Imperium\Runtime\Senate\PersonaConfirmationCaseIntakeService;
use App\Imperium\Runtime\Senate\SenateActivationDemandService;
use App\Imperium\Runtime\Senate\SenateResidentProvisioningCaseService;
use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService;
use App\Imperium\Runtime\Foundry\AdversarialReviewReadinessService;
use App\Imperium\Runtime\Foundry\AdversarialReviewAcceptanceService;
use App\Imperium\Runtime\Foundry\AdversarialPersonaReviewCognitionGateway;
use App\Imperium\Runtime\Foundry\AdversarialPersonaReviewService;
use App\Imperium\Runtime\Foundry\AdversarialReviewCorrectionReturnService;
use App\Imperium\Runtime\Foundry\AdversarialReviewProductionApprovalService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaAssemblyService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewCognitionGateway;
use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationRevisionCognitionGateway;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationRevisionService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaAdmissionDeliveryService;
use App\Imperium\Runtime\Garrison\SubordinatePersonaAdmissionIntakeService;
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
            $provisioning = new AdversarialReviewerProvisioningCaseService(
                $root,
            );
            $reviewerCase = $provisioning->open($demand["demand_id"]);
            self::assertSame(
                $reviewerCase,
                $provisioning->open($demand["demand_id"]),
            );
            self::assertSame(
                $demand["superseded_commissions"],
                $reviewerCase["superseded_commissions"],
            );
            $authorization = new AdversarialReviewerConstructionAuthorizationService(
                $root,
            );
            $delivery = $authorization->authorize($reviewerCase["case_id"]);
            self::assertSame(
                $delivery,
                $authorization->authorize($reviewerCase["case_id"]),
            );
            $bindingId = "foundry-artificer-binding-" . str_repeat("f", 20);
            $binding = [
                "schema" => "imperium.foundry-artificer-occupancy/v1",
                "binding_id" => $bindingId,
                "instance_id" => "imperium-test",
                "office" => "foundry",
                "seat" => "foundry.artificer",
                "manifestation_id" => "artificer-manifestation",
                "occupancy_generation" => 1,
                "status" => "ACTIVE",
                "binding_atomic" => true,
                "execution_authority" => false,
            ];
            $this->write(
                $root . "/var/imperium/offices/foundry/occupancy",
                $bindingId,
                $binding,
            );
            $acceptanceService = new AdversarialReviewerConstructionAuthorizationAcceptanceService(
                $root,
            );
            $reviewerAcceptance = $acceptanceService->accept(
                $delivery["delivery_id"],
                $bindingId,
            );
            self::assertSame(
                $reviewerAcceptance,
                $acceptanceService->accept(
                    $delivery["delivery_id"],
                    $bindingId,
                ),
            );
            $this->copySource(
                $root,
                "offices/foundry/profile-reviewer-adversarial.md",
            );
            $this->copySource(
                $root,
                "offices/foundry/seat-demand-reviewer-adversarial.md",
            );
            $construction = new AdversarialReviewerPersonaConstructionService(
                $root,
            );
            $reviewer = $construction->construct(
                $reviewerAcceptance["acceptance_id"],
            );
            self::assertSame(
                $reviewer,
                $construction->construct($reviewerAcceptance["acceptance_id"]),
            );
            self::assertSame(
                $candidate["candidate_id"],
                $reviewer["authorized_review_target"]["candidate_id"],
            );
            self::assertSame(
                "Blackquill",
                $reviewer["sources"]["design_basis"]["name"],
            );
            self::assertFalse(
                $reviewer["sources"]["design_basis"]["identity_imported"],
            );
            self::assertFalse(
                $reviewer["sources"]["design_basis"]["institution_imported"],
            );
            self::assertSame(
                $demand["superseded_commissions"],
                $reviewer["superseded_commissions"],
            );
            self::assertSame(
                "SEALED_PENDING_FOUNDRY_REVIEW",
                $reviewer["status"],
            );
            self::assertFalse($reviewer["review_authority"]);
            self::assertFalse($reviewer["admission_authority"]);
            $seedAuthorization = new AdversarialReviewerBootstrapSeedAuthorizationService(
                $root,
            );
            $seedDelivery = $seedAuthorization->authorize(
                $reviewer["persona_candidate_id"],
            );
            $expectedLineage = [
                "persona_specification_id" =>
                    $reviewer["persona_specification_id"],
                "persona_specification_digest" =>
                    $reviewer["persona_specification_digest"],
                "persona_specification_version" => 2,
                "specification_supersedes" =>
                    $reviewer["specification_supersedes"],
                "specification_revision_basis" =>
                    $reviewer["specification_revision_basis"],
                "dispatch_kind" => "SPECIFICATION_REVISION_REISSUE",
                "superseded_commissions" => $reviewer["superseded_commissions"],
            ];
            self::assertSame(
                $expectedLineage,
                $seedDelivery["authorization_act"]["review_target_lineage"],
            );
            $seedAcceptanceService = new AdversarialReviewerBootstrapSeedAuthorizationAcceptanceService(
                $root,
            );
            $seedAcceptance = $seedAcceptanceService->accept(
                $seedDelivery["delivery_id"],
                $bindingId,
            );
            $approvalService = new AdversarialReviewerBootstrapSeedProductionApprovalService(
                $root,
            );
            $approval = $approvalService->approve(
                $seedAcceptance["acceptance_id"],
            );
            $admissionDeliveryService = new AdversarialReviewerBootstrapSeedAdmissionDeliveryService(
                $root,
            );
            $admissionDelivery = $admissionDeliveryService->deliver(
                $approval["production_approval_id"],
            );
            $constableBindingId =
                "garrison-constable-binding-" . str_repeat("1", 20);
            $constableBinding = [
                "schema" => "imperium.garrison-constable-occupancy/v1",
                "binding_id" => $constableBindingId,
                "instance_id" => "imperium-test",
                "seat" => "garrison.constable",
                "manifestation_id" => "constable-manifestation",
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
                $constableBindingId,
                $constableBinding,
            );
            $admissionIntake = new AdversarialReviewerBootstrapSeedAdmissionIntakeService(
                $root,
            );
            $admissionReturn = $admissionIntake->inspect(
                $admissionDelivery["delivery_id"],
            );
            self::assertSame(
                "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE",
                $admissionReturn["disposition"],
            );
            self::assertFalse($admissionReturn["custody_created"]);
            $confirmationRequestService = new AdversarialReviewerBootstrapSeedSenateConfirmationRequestService(
                $root,
            );
            $confirmationRequest = $confirmationRequestService->request(
                $admissionReturn["return_id"],
            );
            self::assertSame(
                $expectedLineage,
                $confirmationRequest["review_target_lineage"],
            );
            $confirmationIntake = new PersonaConfirmationCaseIntakeService(
                $root,
            );
            $confirmationCase = $confirmationIntake->preserve(
                $confirmationRequest["confirmation_request_id"],
            );
            self::assertSame(
                "BLOCKED_PENDING_SENATE_OCCUPANCY",
                $confirmationCase["status"],
            );
            foreach (
                [
                    "offices/senate/profile-lord-speaker.md",
                    "offices/senate/seat-resident-lord-speaker.md",
                    "offices/senate/profile-bailiff.md",
                    "offices/senate/seat-resident-bailiff.md",
                ]
                as $source
            ) {
                $this->copySource($root, $source);
            }
            $senateDemandService = new SenateActivationDemandService($root);
            $senateDemand = $senateDemandService->demand(
                $confirmationCase["confirmation_case_id"],
            );
            $residentCasesService = new SenateResidentProvisioningCaseService(
                $root,
            );
            $residentCases = $residentCasesService->open(
                $senateDemand["demand_id"],
            );
            self::assertCount(2, $residentCases["cases"]);
            self::assertSame(
                ["senate.lord-speaker", "senate.bailiff"],
                array_column($residentCases["cases"], "target_seat"),
            );
            foreach ($residentCases["cases"] as $residentCase) {
                self::assertSame(
                    "BLOCKED_PENDING_EXPLICIT_CONSTRUCTION_AUTHORIZATION",
                    $residentCase["status"],
                );
                self::assertSame(
                    $expectedLineage,
                    $residentCase["review_target_lineage"],
                );
                self::assertFalse($residentCase["construction_authority"]);
                self::assertFalse($residentCase["spawning_authority"]);
                self::assertFalse($residentCase["senate_finding_authority"]);
            }
            $foundingPackage = [
                "schema" => "imperium.operator-root-personnel-package/v2",
                "instance_id" => "imperium-test",
                "personnel" => [],
            ];
            foreach (
                [
                    [
                        "foundry.reviewer.adversarial",
                        "foundry",
                        "adversarial-reviewer",
                    ],
                    ["senate.lord-speaker", "senate", "lord-speaker"],
                    ["senate.bailiff", "senate", "bailiff"],
                    ["armory.warden", "armory", "warden"],
                    ["curia.seneschal", "curia", "seneschal"],
                ]
                as [$seat, $office, $role]
            ) {
                $slug = str_replace(".", "-", $seat);
                $foundingPackage["personnel"][] = [
                    "personnel_type" => "OFFICER",
                    "office" => $office,
                    "role" => $role,
                    "seat" => $seat,
                    "persona" => [
                        "id" => $slug . "-persona",
                        "version" => "1.0.0",
                        "definition" => ["operator-authored founding Persona"],
                    ],
                    "profile" => [
                        "id" => $slug . "-profile",
                        "version" => "1.0.0",
                        "definition" => ["operator-authored founding Profile"],
                    ],
                    "officer" => [
                        "id" => $slug . "-officer",
                        "version" => "1.0.0",
                        "definition" => ["operator-supplied founding Officer"],
                    ],
                ];
            }
            $foundingPackage["personnel"][] = [
                "personnel_type" => "OPERATIVE",
                "office" => "hagiography",
                "role" => "research-operative",
                "assignment_id" => "founding-research-operative-1",
                "persona" => ["id" => "research-persona", "version" => "1.0.0"],
                "profile" => ["id" => "research-profile", "version" => "1.0.0"],
                "officer" => [
                    "id" => "research-operative",
                    "version" => "1.0.0",
                ],
            ];
            $foundingPackage["personnel"][0] = [
                "personnel_type" => "OFFICER",
                "founding_class" => "GENERIC_V0_PLACEHOLDER",
                "version" => "0",
                "office" => "foundry",
                "role" => "adversarial-reviewer",
                "seat" => "foundry.reviewer.adversarial",
            ];
            $rootInstallation = new OperatorRootPersonnelInstallationService(
                $root,
            );
            $installed = $rootInstallation->install($foundingPackage);
            self::assertSame(
                $installed,
                $rootInstallation->install($foundingPackage),
            );
            self::assertSame(
                "OPERATOR_ROOT_INSTALLATION",
                $installed["provenance"],
            );
            self::assertCount(6, $installed["installations"]);
            self::assertFalse($installed["internal_authorization_required"]);
            self::assertFalse($installed["internal_construction_required"]);
            self::assertFalse($installed["internal_admission_required"]);
            self::assertFalse($installed["internal_qualification_required"]);
            self::assertFalse($installed["internal_confirmation_required"]);
            self::assertFalse(
                $installed["installation_grants_execution_authority"],
            );
            self::assertTrue($installed["post_operational_upgrades_governed"]);
            $operative = $installed["installations"][5];
            self::assertSame("OPERATIVE", $operative["personnel_type"]);
            self::assertSame(
                "INSTALLED_INACTIVE_PRE_OPERATIONAL",
                $operative["status"],
            );
            self::assertFalse($operative["roster"]["deployment_authority"]);
            $readinessService = new AdversarialReviewReadinessService($root);
            $readiness = $readinessService->resume($demand["demand_id"]);
            self::assertSame(
                $readiness,
                $readinessService->resume($demand["demand_id"]),
            );
            self::assertSame(
                "PENDING_ADVERSARIAL_REVIEWER_ACCEPTANCE",
                $readiness["status"],
            );
            self::assertSame(
                "BLOCKED_PENDING_SENATE_OCCUPANCY",
                $confirmationCase["status"],
            );
            self::assertSame(
                $expectedLineage,
                $readiness["review_target_lineage"],
            );
            self::assertSame(
                $candidate["candidate_id"],
                $readiness["candidate_id"],
            );
            self::assertSame(
                "OPERATOR_ROOT_INSTALLATION",
                $readiness["reviewer_occupancy"]["provenance"],
            );
            self::assertFalse($readiness["review_authority_exercisable"]);
            self::assertFalse($readiness["persona_approval_authority"]);
            $reviewAcceptanceService = new AdversarialReviewAcceptanceService(
                $root,
            );
            $reviewAcceptance = $reviewAcceptanceService->accept(
                $readiness["readiness_id"],
            );
            self::assertSame(
                $reviewAcceptance,
                $reviewAcceptanceService->accept($readiness["readiness_id"]),
            );
            self::assertSame(
                "GENERIC_V0_PLACEHOLDER",
                $reviewAcceptance["reviewer"]["founding_class"],
            );
            self::assertSame(
                "0",
                $reviewAcceptance["reviewer"]["placeholder_version"],
            );
            self::assertTrue($reviewAcceptance["review_authority_exercisable"]);
            self::assertFalse($reviewAcceptance["persona_approval_authority"]);
            $adversarialCognition = new class implements
                AdversarialPersonaReviewCognitionGateway
            {
                public function review(
                    array $candidate,
                    array $specification,
                    array $case,
                    array $acceptance,
                ): array {
                    return [
                        "disposition" => "PASSED",
                        "findings" => [
                            "The corrected v2 candidate preserves the clarification, exact supersession lineage, and resident authorship boundaries.",
                        ],
                        "required_corrections" => [],
                        "rationale" =>
                            "No unresolved contradiction, unsupported authority claim, or material specification defect remains in the exact reviewed candidate.",
                    ];
                }
            };
            $adversarialReviewService = new AdversarialPersonaReviewService(
                $root,
                $adversarialCognition,
            );
            $adversarialResult = $adversarialReviewService->review(
                $reviewAcceptance["acceptance_id"],
            );
            self::assertSame(
                $adversarialResult,
                $adversarialReviewService->review(
                    $reviewAcceptance["acceptance_id"],
                ),
            );
            self::assertSame(
                "PASSED_PENDING_FOUNDRY_PRODUCTION_APPROVAL",
                $adversarialResult["status"],
            );
            self::assertSame(
                $expectedLineage,
                $adversarialResult["review_target_lineage"],
            );
            self::assertTrue(
                $adversarialResult["foundry_production_approval_eligible"],
            );
            self::assertFalse($adversarialResult["persona_approval_authority"]);
            self::assertFalse($adversarialResult["admission_authority"]);
            self::assertFalse($adversarialResult["execution_authority"]);
            $productionApprovalService = new AdversarialReviewProductionApprovalService(
                $root,
            );
            $productionApproval = $productionApprovalService->approve(
                $adversarialResult["result_id"],
                $bindingId,
            );
            self::assertSame(
                $productionApproval,
                $productionApprovalService->approve(
                    $adversarialResult["result_id"],
                    $bindingId,
                ),
            );
            self::assertSame(
                "APPROVED_PENDING_GARRISON_ADMISSION_DELIVERY",
                $productionApproval["status"],
            );
            self::assertSame(
                $expectedLineage,
                $productionApproval["review_target_lineage"],
            );
            self::assertTrue($productionApproval["production_approval"]);
            self::assertFalse($productionApproval["admission_authority"]);
            self::assertFalse($productionApproval["execution_authority"]);
            $personaAdmissionDeliveryService = new SubordinatePersonaAdmissionDeliveryService(
                $root,
            );
            $personaAdmissionDelivery = $personaAdmissionDeliveryService->deliver(
                $productionApproval["production_approval_id"],
            );
            self::assertSame(
                $personaAdmissionDelivery,
                $personaAdmissionDeliveryService->deliver(
                    $productionApproval["production_approval_id"],
                ),
            );
            self::assertSame(
                "DELIVERED_PENDING_GARRISON_ACCEPTANCE",
                $personaAdmissionDelivery["status"],
            );
            self::assertSame(
                $candidate["persona"],
                $personaAdmissionDelivery["persona"],
            );
            self::assertSame(
                $expectedLineage,
                $personaAdmissionDelivery["review_target_lineage"],
            );
            self::assertFalse($personaAdmissionDelivery["admission_authority"]);
            $personaAdmissionIntakeService = new SubordinatePersonaAdmissionIntakeService(
                $root,
            );
            $personaAdmissionReturn = $personaAdmissionIntakeService->inspect(
                $personaAdmissionDelivery["delivery_id"],
            );
            self::assertSame(
                $personaAdmissionReturn,
                $personaAdmissionIntakeService->inspect(
                    $personaAdmissionDelivery["delivery_id"],
                ),
            );
            self::assertSame(
                "REFUSED_INCOMPLETE_PERSONA_ADMISSION_PACKAGE",
                $personaAdmissionReturn["disposition"],
            );
            self::assertSame(
                $expectedLineage,
                $personaAdmissionReturn["review_target_lineage"],
            );
            self::assertCount(4, $personaAdmissionReturn["defects"]);
            self::assertFalse($personaAdmissionReturn["custody_created"]);
            self::assertFalse($personaAdmissionReturn["admission_authority"]);
            self::assertFalse($personaAdmissionReturn["execution_authority"]);

            unlink(
                $root .
                    "/var/imperium/offices/foundry/adversarial-review-results/" .
                    $adversarialResult["result_id"] .
                    ".json",
            );
            $returningCognition = new class implements
                AdversarialPersonaReviewCognitionGateway
            {
                public function review(
                    array $candidate,
                    array $specification,
                    array $case,
                    array $acceptance,
                ): array {
                    return [
                        "disposition" => "RETURN_TO_FOUNDRY",
                        "findings" => [
                            "One stop condition remains ambiguous under conflicting source evidence.",
                        ],
                        "required_corrections" => [
                            "Make the conflicting-evidence stop condition explicit without requesting Garrison facts for the unfinished Persona.",
                        ],
                        "rationale" =>
                            "The exact candidate cannot pass until its evidence conflict behavior is bounded.",
                    ];
                }
            };
            $returnedResult = new AdversarialPersonaReviewService(
                $root,
                $returningCognition,
            )->review($reviewAcceptance["acceptance_id"]);
            self::assertSame(
                "RETURNED_TO_FOUNDRY_FOR_VERSIONED_CORRECTION",
                $returnedResult["status"],
            );
            $correctionReturnService = new AdversarialReviewCorrectionReturnService(
                $root,
            );
            $correctionReturn = $correctionReturnService->returnForRevision(
                $returnedResult["result_id"],
            );
            self::assertSame(
                $correctionReturn,
                $correctionReturnService->returnForRevision(
                    $returnedResult["result_id"],
                ),
            );
            self::assertSame(
                $expectedLineage,
                $correctionReturn["review_target_lineage"],
            );
            self::assertSame(
                $revisionBasis,
                $correctionReturn["prior_revision_basis"],
            );
            self::assertTrue($correctionReturn["re_dispatch_required"]);

            $revisionCognition = new class implements
                SubordinatePersonaSpecificationRevisionCognitionGateway
            {
                public function revise(
                    array $case,
                    array $priorSpecification,
                    array $revisionReturn,
                ): array {
                    return [
                        "disposition" => "PERSONA_SPECIFICATION_COMPLETE",
                        "persona_name" => "Bounded Chronicler",
                        "purpose" =>
                            "Produce evidence-bounded Hagiography sections.",
                        "identity_constraints" => [
                            "Remain a subordinate Chronicler.",
                        ],
                        "competencies" => [
                            "Trace conflicting source evidence.",
                        ],
                        "behavioral_directives" => [
                            "Stop and return conflicting evidence explicitly.",
                        ],
                        "evidence_obligations" => [
                            "Preserve source identity and uncertainty.",
                        ],
                        "explicit_exclusions" => [
                            "Do not request Garrison facts for an unfinished Persona.",
                        ],
                        "source_requirements" => [
                            "Use only supplied evidence.",
                        ],
                        "return_contracts" => [
                            "Return unresolved conflicts to Hagiography.",
                        ],
                        "stop_conditions" => [
                            "Stop when source evidence materially conflicts.",
                        ],
                    ];
                }
            };
            $revisionService = new SubordinatePersonaSpecificationRevisionService(
                $root,
                $revisionCognition,
            );
            $correctedSpecification = $revisionService->revise(
                $correctionReturn["return_id"],
            );
            self::assertSame(
                $correctedSpecification,
                $revisionService->revise($correctionReturn["return_id"]),
            );
            self::assertSame(
                3,
                $correctedSpecification["specification_version"],
            );
            self::assertSame(
                $revisedId,
                $correctedSpecification["supersedes"]["specification_id"],
            );
            self::assertSame(
                "ADVERSARIAL_CORRECTION",
                $correctedSpecification["revision_basis"]["return_kind"],
            );
            self::assertSame(
                $revisionBasis,
                $correctedSpecification["revision_basis"][
                    "prior_revision_basis"
                ],
            );
            $correctedDispatch = $dispatch->dispatch(
                $correctedSpecification["specification_id"],
            );
            self::assertSame(
                "SPECIFICATION_REVISION_REISSUE",
                $correctedDispatch["dispatch_kind"],
            );
            foreach (
                $correctedDispatch["commissions"]
                as $correctedCommission
            ) {
                self::assertSame(
                    3,
                    $correctedCommission["persona_specification_version"],
                );
                self::assertSame(
                    $correctedSpecification["revision_basis"],
                    $correctedCommission["specification_revision_basis"],
                );
            }
            $operationalization = new OperatorRootOperationalizationService(
                $root,
            );
            try {
                $operationalization->seal("imperium-test");
                self::fail(
                    "Partial founding personnel were declared operational.",
                );
            } catch (\RuntimeException $e) {
                self::assertTrue(
                    str_starts_with(
                        $e->getMessage(),
                        "B224_REQUIRED_FOUNDING_SEATS_VACANT:",
                    ),
                );
            }
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

    private function copySource(string $root, string $relative): void
    {
        $destination = $root . "/" . $relative;
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0770, true);
        }
        file_put_contents(
            $destination,
            (string) file_get_contents(dirname(__DIR__, 3) . "/" . $relative),
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
