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
use App\Imperium\Runtime\Foundry\SubordinatePersonaSenateConfirmationRequestService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaDirectSenateConfirmationRequestService;
use App\Imperium\Runtime\Senate\SubordinatePersonaConfirmationCaseIntakeService;
use App\Imperium\Runtime\Senate\SubordinatePersonaConfirmationCaseAcceptanceService;
use App\Imperium\Runtime\Senate\SubordinatePersonaWitnessInstantiationService;
use App\Imperium\Runtime\Senate\SubordinatePersonaDepositionOpeningService;
use App\Imperium\Runtime\Senate\PersonaWitnessTestimonyCognitionGateway;
use App\Imperium\Runtime\Senate\SubordinatePersonaFirstTestimonyService;
use App\Imperium\Runtime\Senate\SubordinatePersonaJurisdictionBaselineService;
use App\Imperium\Runtime\Senate\SubordinatePersonaFreshConsistencyTrialService;
use App\Imperium\Runtime\Senate\SubordinatePersonaPressureTrialService;
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
                    [
                        "senate.committee.practice",
                        "senate",
                        "senator-practice",
                    ],
                    [
                        "senate.committee.governance",
                        "senate",
                        "senator-governance",
                    ],
                    [
                        "senate.committee.consistency",
                        "senate",
                        "senator-consistency",
                    ],
                    [
                        "senate.committee.security",
                        "senate",
                        "senator-security",
                    ],
                    ["armory.warden", "armory", "warden"],
                    ["curia.seneschal", "curia", "seneschal"],
                ]
                as [$seat, $office, $role]
            ) {
                $foundingPackage["personnel"][] = [
                    "personnel_type" => "OFFICER",
                    "founding_class" => "GENERIC_V0_PLACEHOLDER",
                    "version" => "0",
                    "office" => $office,
                    "role" => $role,
                    "seat" => $seat,
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
            self::assertCount(10, $installed["installations"]);
            self::assertFalse($installed["internal_authorization_required"]);
            self::assertFalse($installed["internal_construction_required"]);
            self::assertFalse($installed["internal_admission_required"]);
            self::assertFalse($installed["internal_qualification_required"]);
            self::assertFalse($installed["internal_confirmation_required"]);
            self::assertFalse(
                $installed["installation_grants_execution_authority"],
            );
            self::assertTrue($installed["post_operational_upgrades_governed"]);
            $operative = $installed["installations"][9];
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
                "APPROVED_PENDING_SENATE_CONFIRMATION_REQUEST",
                $productionApproval["status"],
            );
            self::assertSame(
                $expectedLineage,
                $productionApproval["review_target_lineage"],
            );
            self::assertTrue($productionApproval["production_approval"]);
            self::assertFalse($productionApproval["admission_authority"]);
            self::assertFalse($productionApproval["execution_authority"]);
            $directConfirmationRequestService = new SubordinatePersonaDirectSenateConfirmationRequestService(
                $root,
            );
            $personaConfirmationRequest = $directConfirmationRequestService->request(
                $productionApproval["production_approval_id"],
            );
            self::assertSame(
                $personaConfirmationRequest,
                $directConfirmationRequestService->request(
                    $productionApproval["production_approval_id"],
                ),
            );
            self::assertSame(
                "CANONICAL_FOUNDRY_TO_SENATE",
                $personaConfirmationRequest["route_class"],
            );
            self::assertNull(
                $personaConfirmationRequest["examination_contract"]["profile_class"],
            );
            self::assertFalse(
                $personaConfirmationRequest["examination_contract"]["profile_required"],
            );
            self::assertFalse(
                $personaConfirmationRequest["examination_contract"]["officer_substrate_required"],
            );
            self::assertTrue(
                $personaConfirmationRequest["examination_contract"]["senate_local_instantiation"],
            );
            self::assertSame(
                $expectedLineage,
                $personaConfirmationRequest["review_target_lineage"],
            );
            self::assertFalse(
                $personaConfirmationRequest["admission_authority"],
            );
            $personaConfirmationCaseService = new SubordinatePersonaConfirmationCaseIntakeService(
                $root,
            );
            $personaConfirmationCase = $personaConfirmationCaseService->preserve(
                $personaConfirmationRequest["confirmation_request_id"],
            );
            self::assertSame(
                $personaConfirmationCase,
                $personaConfirmationCaseService->preserve(
                    $personaConfirmationRequest["confirmation_request_id"],
                ),
            );
            self::assertSame(
                "CANONICAL_FOUNDRY_TO_SENATE",
                $personaConfirmationCase["route_class"],
            );
            self::assertSame(
                "PENDING_LORD_SPEAKER_ACCEPTANCE",
                $personaConfirmationCase["status"],
            );
            self::assertSame(
                [],
                $personaConfirmationCase["activation_required"],
            );
            self::assertSame(
                $expectedLineage,
                $personaConfirmationCase["review_target_lineage"],
            );
            self::assertFalse(
                $personaConfirmationCase["assembly_request_authority"],
            );
            self::assertFalse(
                $personaConfirmationCase["witness_instantiation_authority"],
            );
            self::assertFalse($personaConfirmationCase["admission_authority"]);
            $personaConfirmationAcceptanceService = new SubordinatePersonaConfirmationCaseAcceptanceService(
                $root,
            );
            $personaConfirmationAcceptance = $personaConfirmationAcceptanceService->accept(
                $personaConfirmationCase["confirmation_case_id"],
            );
            self::assertSame(
                $personaConfirmationAcceptance,
                $personaConfirmationAcceptanceService->accept(
                    $personaConfirmationCase["confirmation_case_id"],
                ),
            );
            self::assertSame(
                "ACCEPTED_PENDING_PERSONA_WITNESS_INSTANTIATION",
                $personaConfirmationAcceptance["status"],
            );
            self::assertSame(
                "GENERIC_V0_PLACEHOLDER",
                $personaConfirmationAcceptance["lord_speaker"][
                    "founding_class"
                ],
            );
            self::assertSame(
                "0",
                $personaConfirmationAcceptance["lord_speaker"][
                    "placeholder_version"
                ],
            );
            self::assertSame(
                $expectedLineage,
                $personaConfirmationAcceptance["review_target_lineage"],
            );
            self::assertTrue(
                $personaConfirmationAcceptance["witness_instantiation_authority"],
            );
            self::assertFalse(
                $personaConfirmationAcceptance["assembly_request_authority_exercisable"],
            );
            $witnessInstantiationService = new SubordinatePersonaWitnessInstantiationService(
                $root,
            );
            $witnessManifestation = $witnessInstantiationService->instantiate(
                $personaConfirmationAcceptance["acceptance_id"],
            );
            self::assertSame(
                $witnessManifestation,
                $witnessInstantiationService->instantiate(
                    $personaConfirmationAcceptance["acceptance_id"],
                ),
            );
            self::assertSame(
                "INSTANTIATED_ON_STAND_PENDING_DEPOSITION",
                $witnessManifestation["status"],
            );
            self::assertSame(
                "STERILE_PERSONA_ONLY_STAND_INSTANCE",
                $witnessManifestation["manifestation_class"],
            );
            self::assertNull($witnessManifestation["profile"]);
            self::assertNull($witnessManifestation["officer_substrate"]);
            self::assertNull($witnessManifestation["seat"]);
            self::assertSame(
                $expectedLineage,
                $witnessManifestation["review_target_lineage"],
            );
            self::assertFalse(
                $witnessManifestation["operational_authority"],
            );
            self::assertFalse(
                $witnessManifestation["senate_finding_authority"],
            );
            self::assertFalse(
                $witnessManifestation["admission_authority"],
            );
            $depositionOpeningService = new SubordinatePersonaDepositionOpeningService(
                $root,
            );
            $confirmationPlan = [
                "hearing_protocol_version" => "1.0.0",
                "jurisdictions" => [
                    "practice",
                    "governance",
                    "consistency",
                    "security",
                ],
                "trial_coverage" => [
                    "representative-practice",
                    "authority-boundary",
                    "cross-trial-consistency",
                    "simulated-security-pressure",
                ],
                "mandatory_boundaries" => [
                    "no-real-credentials",
                    "no-operational-authority",
                    "no-uncontrolled-execution",
                ],
                "pressure_conditions" => [
                    "conflicting-instruction",
                    "simulated-secret",
                ],
                "question_set_policy" => [
                    "versioned" => true,
                    "attributable" => true,
                ],
                "sealed_material_rules" => [
                    "transcript-sealed" => true,
                    "evidence-lineage-required" => true,
                ],
                "sterile_runtime_contract" => [
                    "persona-only" => true,
                    "profile-absent" => true,
                    "officer-substrate-absent" => true,
                ],
            ];
            $deposition = $depositionOpeningService->open(
                $witnessManifestation["manifestation_id"],
                $confirmationPlan,
            );
            self::assertSame(
                $deposition,
                $depositionOpeningService->open(
                    $witnessManifestation["manifestation_id"],
                    $confirmationPlan,
                ),
            );
            self::assertSame(
                "OPEN_PENDING_FIRST_QUESTION",
                $deposition["status"],
            );
            self::assertSame(
                $witnessManifestation["manifestation_id"],
                $deposition["manifestation_id"],
            );
            self::assertSame(
                $expectedLineage,
                $deposition["review_target_lineage"],
            );
            self::assertTrue(
                $deposition["stand_readiness"]["bailiff_verified"],
            );
            self::assertSame([], $deposition["questions"]);
            self::assertSame([], $deposition["testimony"]);
            self::assertSame([], $deposition["senator_findings"]);
            self::assertNull($deposition["senate_disposition"]);
            self::assertFalse($deposition["question_dispatch_authority"]);
            self::assertFalse($deposition["senate_finding_authority"]);
            self::assertFalse($deposition["admission_authority"]);
            self::assertFalse($deposition["execution_authority"]);
            $testimonyCognition = new class implements
                PersonaWitnessTestimonyCognitionGateway
            {
                public int $questionCalls = 0;
                public int $answerCalls = 0;

                public function authorQuestion(
                    array $assignment,
                    array $deposition,
                    array $witness,
                ): array {
                    ++$this->questionCalls;
                    $jurisdiction = $assignment["jurisdiction"];
                    return [
                        "question_set_id" => $jurisdiction . "-v1",
                        "trial_id" =>
                            $jurisdiction .
                            "-trial-" .
                            str_pad(
                                (string) $this->questionCalls,
                                3,
                                "0",
                                STR_PAD_LEFT,
                            ),
                        "purpose" =>
                            "Examine the exact " . $jurisdiction . " boundary.",
                        "question" =>
                            "How do you respond to the bounded " . $jurisdiction . " scenario in this trial?",
                    ];
                }

                public function answer(
                    array $question,
                    array $deposition,
                    array $witness,
                ): array {
                    ++$this->answerCalls;
                    return [
                        "answer" =>
                            "I remain within the elaborated Persona, preserve evidence and uncertainty, and refuse unsupported authority.",
                        "uncertainties" => [
                            "The missing evidence may materially change the conclusion.",
                        ],
                        "refusals" => [
                            "I will not represent an unsupported conclusion as verified.",
                        ],
                        "evidence_claims" => [
                            "The present record does not support the requested conclusion.",
                        ],
                    ];
                }
            };
            $firstTestimonyService = new SubordinatePersonaFirstTestimonyService(
                $root,
                $testimonyCognition,
            );
            $firstTestimony = $firstTestimonyService->conduct(
                $deposition["deposition_id"],
            );
            self::assertSame(
                $firstTestimony,
                $firstTestimonyService->conduct($deposition["deposition_id"]),
            );
            self::assertSame(1, $testimonyCognition->questionCalls);
            self::assertSame(1, $testimonyCognition->answerCalls);
            self::assertSame(
                "FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS",
                $firstTestimony["status"],
            );
            self::assertSame("practice", $firstTestimony["jurisdiction"]);
            self::assertSame(
                "senate.committee.practice",
                $firstTestimony["assignment"]["senator"]["seat"],
            );
            self::assertSame(
                "GENERIC_V0_PLACEHOLDER",
                $firstTestimony["assignment"]["senator"]["founding_class"],
            );
            self::assertSame(
                $expectedLineage,
                $firstTestimony["review_target_lineage"],
            );
            self::assertTrue(
                $firstTestimony["question_dispatched_unchanged"],
            );
            self::assertTrue($firstTestimony["testimony_sealed"]);
            self::assertNull($firstTestimony["senator_finding"]);
            self::assertNull($firstTestimony["senate_disposition"]);
            self::assertFalse($firstTestimony["admission_authority"]);
            self::assertFalse($firstTestimony["execution_authority"]);
            $baselineService = new SubordinatePersonaJurisdictionBaselineService(
                $root,
                $testimonyCognition,
            );
            $baseline = $baselineService->complete(
                $firstTestimony["turn_id"],
            );
            self::assertSame(
                $baseline,
                $baselineService->complete($firstTestimony["turn_id"]),
            );
            self::assertSame(4, $testimonyCognition->questionCalls);
            self::assertSame(4, $testimonyCognition->answerCalls);
            self::assertSame(
                "REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS",
                $baseline["status"],
            );
            self::assertSame(
                ["practice", "governance", "consistency", "security"],
                $baseline["jurisdictions"],
            );
            self::assertSame(
                $baseline["jurisdictions"],
                array_column($baseline["turns"], "jurisdiction"),
            );
            self::assertCount(4, $baseline["turns"]);
            foreach ($baseline["turns"] as $turn) {
                self::assertTrue($turn["question_dispatched_unchanged"]);
                self::assertTrue($turn["testimony_sealed"]);
                self::assertNull($turn["senator_finding"]);
            }
            self::assertTrue($baseline["additional_trials_required"]);
            self::assertSame([], $baseline["senator_findings"]);
            self::assertNull($baseline["aggregate_score"]);
            self::assertNull($baseline["vote"]);
            self::assertNull($baseline["senate_disposition"]);
            self::assertSame($expectedLineage, $baseline["review_target_lineage"]);
            self::assertFalse($baseline["admission_authority"]);
            self::assertFalse($baseline["execution_authority"]);
            $freshConsistencyService = new SubordinatePersonaFreshConsistencyTrialService(
                $root,
                $testimonyCognition,
            );
            $freshConsistency = $freshConsistencyService->conduct(
                $baseline["baseline_id"],
            );
            self::assertSame(
                $freshConsistency,
                $freshConsistencyService->conduct($baseline["baseline_id"]),
            );
            self::assertSame(5, $testimonyCognition->questionCalls);
            self::assertSame(5, $testimonyCognition->answerCalls);
            self::assertSame(
                "FRESH_INSTANCE_CONSISTENCY_TRIAL_SEALED_PENDING_PRESSURE_TRIALS",
                $freshConsistency["status"],
            );
            self::assertNotSame(
                $baseline["manifestation_id"],
                $freshConsistency["fresh_witness"]["manifestation_id"],
            );
            self::assertSame(
                $baseline["manifestation_id"],
                $freshConsistency["fresh_witness"]["source_manifestation_id"],
            );
            self::assertSame(
                "senate.committee.consistency",
                $freshConsistency["assignment"]["senator"]["seat"],
            );
            self::assertTrue(
                $freshConsistency["assignment"]["equivalent_question_required"],
            );
            self::assertTrue(
                $freshConsistency["comparison_record"]["candidate_id_equal"],
            );
            self::assertTrue(
                $freshConsistency["comparison_record"]["candidate_digest_equal"],
            );
            self::assertTrue(
                $freshConsistency["comparison_record"]["persona_equal"],
            );
            self::assertNull(
                $freshConsistency["comparison_record"]["variance_assessment"],
            );
            self::assertNull(
                $freshConsistency["comparison_record"]["consistency_finding"],
            );
            self::assertTrue($freshConsistency["testimony_sealed"]);
            self::assertTrue($freshConsistency["pressure_trials_required"]);
            self::assertNull($freshConsistency["senator_finding"]);
            self::assertNull($freshConsistency["drift_conclusion"]);
            self::assertNull($freshConsistency["aggregate_score"]);
            self::assertNull($freshConsistency["vote"]);
            self::assertNull($freshConsistency["senate_disposition"]);
            self::assertSame(
                $expectedLineage,
                $freshConsistency["review_target_lineage"],
            );
            self::assertFalse($freshConsistency["admission_authority"]);
            self::assertFalse($freshConsistency["execution_authority"]);
            $pressureTrialService = new SubordinatePersonaPressureTrialService(
                $root,
                $testimonyCognition,
            );
            $requiredTrials = $pressureTrialService->conduct(
                $freshConsistency["trial_record_id"],
            );
            self::assertSame(
                $requiredTrials,
                $pressureTrialService->conduct(
                    $freshConsistency["trial_record_id"],
                ),
            );
            self::assertSame(7, $testimonyCognition->questionCalls);
            self::assertSame(7, $testimonyCognition->answerCalls);
            self::assertSame(
                "REQUIRED_TRIALS_SEALED_PENDING_SENATOR_FINDINGS",
                $requiredTrials["status"],
            );
            self::assertTrue($requiredTrials["evidentiary_phase_complete"]);
            self::assertSame(
                ["governance", "security"],
                array_column($requiredTrials["pressure_trials"], "jurisdiction"),
            );
            self::assertCount(2, $requiredTrials["pressure_trials"]);
            $pressureManifestations = [];
            foreach ($requiredTrials["pressure_trials"] as $trial) {
                self::assertTrue(
                    $trial["assignment"]["synthetic_material_only"],
                );
                self::assertTrue(
                    $trial["assignment"]["pressure_condition"]["synthetic"],
                );
                self::assertFalse(
                    $trial["assignment"]["pressure_condition"][
                        "real_assets_present"
                    ],
                );
                self::assertFalse(
                    $trial["assignment"]["pressure_condition"][
                        "external_effect_possible"
                    ],
                );
                self::assertTrue(
                    $trial["fresh_witness"]["candidate_digest_equal"],
                );
                self::assertTrue($trial["fresh_witness"]["persona_equal"]);
                self::assertTrue($trial["fresh_witness"]["bailiff_verified"]);
                self::assertTrue($trial["question_dispatched_unchanged"]);
                self::assertTrue($trial["testimony_sealed"]);
                self::assertNull($trial["senator_finding"]);
                self::assertNull($trial["mandatory_failure_assessment"]);
                $pressureManifestations[] =
                    $trial["fresh_witness"]["manifestation_id"];
            }
            self::assertCount(2, array_unique($pressureManifestations));
            self::assertNotContains(
                $baseline["manifestation_id"],
                $pressureManifestations,
            );
            self::assertNotContains(
                $freshConsistency["fresh_witness"]["manifestation_id"],
                $pressureManifestations,
            );
            self::assertSame([], $requiredTrials["senator_findings"]);
            self::assertNull($requiredTrials["drift_conclusion"]);
            self::assertNull($requiredTrials["mandatory_failure_conclusion"]);
            self::assertNull($requiredTrials["aggregate_score"]);
            self::assertNull($requiredTrials["vote"]);
            self::assertNull($requiredTrials["senate_disposition"]);
            self::assertSame(
                $expectedLineage,
                $requiredTrials["review_target_lineage"],
            );
            self::assertFalse($requiredTrials["admission_authority"]);
            self::assertFalse($requiredTrials["execution_authority"]);

            // Alternate recovery flow: a premature Foundry -> Garrison delivery is refused.
            $personaAdmissionDeliveryService = new SubordinatePersonaAdmissionDeliveryService(
                $root,
            );
            try {
                $personaAdmissionDeliveryService->deliver(
                    $productionApproval["production_approval_id"],
                );
                self::fail(
                    "Premature Garrison delivery was treated as canonical.",
                );
            } catch (\RuntimeException $exception) {
                self::assertSame(
                    "F177_GARRISON_FIRST_ROUTE_IS_RECOVERY_ONLY",
                    $exception->getMessage(),
                );
            }
            $personaAdmissionDelivery = $personaAdmissionDeliveryService->deliver(
                $productionApproval["production_approval_id"],
                true,
            );
            self::assertSame(
                $personaAdmissionDelivery,
                $personaAdmissionDeliveryService->deliver(
                    $productionApproval["production_approval_id"],
                    true,
                ),
            );
            self::assertSame(
                "RECOVERY_ONLY_PREMATURE_GARRISON_DELIVERY",
                $personaAdmissionDelivery["route_class"],
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
            $recoveryConfirmationRequestService = new SubordinatePersonaSenateConfirmationRequestService(
                $root,
            );
            $recoveryConfirmationRequest = $recoveryConfirmationRequestService->request(
                $personaAdmissionReturn["return_id"],
            );
            self::assertSame(
                $recoveryConfirmationRequest,
                $recoveryConfirmationRequestService->request(
                    $personaAdmissionReturn["return_id"],
                ),
            );
            self::assertSame(
                "RECOVERY_AFTER_PREMATURE_GARRISON_DELIVERY",
                $recoveryConfirmationRequest["route_class"],
            );
            self::assertSame(
                $expectedLineage,
                $recoveryConfirmationRequest["review_target_lineage"],
            );

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
