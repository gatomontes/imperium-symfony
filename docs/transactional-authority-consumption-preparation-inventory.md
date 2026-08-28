# Transactional Authority Consumption Adoption — Preparation Inventory

## Scope and method

This is the canonical documentation-only output of Preparation Batch 0. It inventories runtime
records that are explicitly single-use, exercisable once, consumed by a succeeding act, or used as
an at-most-once lease or claim. Standing Office competencies, descriptive `*_authority=true`
capability flags, negative adjacent-authority fields, and immutable evidence that opens no act are
not consumable authorities and are not counted as such.

The inventory was produced from every issuer and consumer under `src/Imperium/Runtime`, the
Delegate Mission authority matrix and flow, the shared persistence primitives, and the associated
unit, contention, crash, tamper, and documentation tests. Grouping mechanically identical
jurisdictional consumers does not merge their actors, schemas, authorities, or results.

The requirement classifications are:

- `EXISTS_CANONICALLY`: one shared primitive or explicit contract enforces the property;
- `EXISTS_FRAGMENTED`: lifecycle-specific code enforces some or all of it without one contract;
- `ABSENT`: the property is not implemented or proved for the inventoried scope; and
- `DEFERRED_BOUNDARY`: the property belongs to an expressly closed successor boundary.

The consumer postures are:

- `TRANSACTIONAL_CANONICAL`: the complete decision/consume/result or recoverable transition uses
  shared locking, replay, immutable-record, and/or compare-and-swap primitives;
- `LOCKED_FRAGMENTED`: the complete decision is natively locked, but consumption and result use a
  lifecycle-specific representation rather than the canonical consumption contract;
- `RACE_EXPOSED`: validation or replay selection occurs outside the lock that establishes the
  winner, even if the eventual immutable write is locked;
- `RECOVERY_INCOMPLETE`: a claim or write may survive without a deterministic completion rule for
  every later boundary; and
- `DEFERRED_EXTERNAL_BOUNDARY`: the consumer crosses an expressly unopened external boundary.

## Canonical substrate and its limits

| Requirement | Classification | Evidence and exact limit |
| --- | --- | --- |
| Cross-process scope lock with release on exception | `EXISTS_CANONICALLY` | `AtomicTransition::run()` hashes one validated scope to a native `flock(LOCK_EX)` lock; `TransactionalPersistencePrimitivesTest::testAtomicTransitionReleasesLockAfterException()` proves release, not ordering across multiple scopes. |
| Immutable record uniqueness | `EXISTS_CANONICALLY` | `ImmutableRecordStore::put()` locks the complete directory, seals canonical JSON, returns exact replay, and rejects a different record at the same ID. It cannot prevent two semantically competing results with different IDs. |
| Mutable compare-and-swap | `EXISTS_CANONICALLY` | `MutableStateStore::compareAndSwapGuarded()` locks the exact relative path, validates the current digest, runs a guard, and atomically renames the next state. |
| Complete authoritative-input replay identity | `EXISTS_CANONICALLY` | `ReplayFingerprint` canonicalizes the supplied complete input set and rejects mismatch. Adoption is limited to the services enumerated below. |
| Generic single-authority consumption | `EXISTS_CANONICALLY` | `AuthorityConsumptionStore` locks `authority:<sha256 authorityId>`, emits one `imperium.runtime-authority-consumption/v1`, returns exact source/consumer replay, and rejects conflict. Only Delegate turn recovery currently calls it. |
| Atomic multi-authority consumption | `EXISTS_FRAGMENTED` | The operational claim now adopts the shared transaction/recovery contracts for its lease plus cognition authority. Governance and Delegate provider claims remain lifecycle-specific, and the generic store has no separately migrated multi-authority execution path. |
| Canonical lock order declaration and enforcement | `ABSENT` | Individual services hard-code order. No registry or contract prevents inverse acquisition by a competing path. Current proved orders are listed below. |
| Canonical duplicate/conflict/expired/stale/missing/superseded vocabulary | `ABSENT` | Services fail stopped with lifecycle-specific errors. Expiry exists only where the schema carries it; generalized supersession and revocation remain unopened. |
| Commit-boundary fault injection for every consumer | `ABSENT` | Construction, deployment custody, provider unknown outcome, and terminal retirement have bounded crash proofs; the remaining authority consumers do not. |
| Read-only reconstruction of every consumption | `EXISTS_FRAGMENTED` | Operational/governance interruption, provider journal, four crash corridors, and the fourteen-record terminal audit reconstruct bounded lineages. There is no runtime-wide consumption ledger. |
| Generalized revocation, propagation, telemetry, reassessment, containment, or incident handling | `DEFERRED_BOUNDARY` | These campaigns remain closed. Absence is not authority to implement them here. |
| Iron Gate, Lazaretto, sortie, external-receipt, and new credential-platform transactional semantics | `DEFERRED_BOUNDARY` | These are external/perimeter successors and receive `DEFERRED_EXTERNAL_BOUNDARY`, not an internal migration posture. |

## Lock map and competing paths

| Scope and order | Current users | Competing path and proof |
| --- | --- | --- |
| `authority:<sha256 authorityId>` then immutable consumption-directory lock | `AuthorityConsumptionStore`; Delegate turn recovery | Same authority with changed source or consumer loses with `PST131`; unit proof exists, but no multi-process consumer proof. |
| `oca-cognition-authority:<sha256 authorityId>` → `oca-lease:<sha256 leaseId>` → immutable operational-claim directory | `OperationalCognitionInvocationClaimService` | Operational interruption enforcement takes the same authority then lease order. Claim/claim and claim/interruption single-winner behavior are proved by `OperationalCognitionInvocationClaimServiceTest` and `InternalOperationalLeaseInterruptionEnforcementServiceTest`. |
| `gca-authority:<sha256 authorityId>` → `gca-lease:<sha256 leaseId>` → immutable governance-claim directory | `GovernanceCognitionInvocationClaimService` | Governance cognition and governance-lease interruption enforcement converge on the same order. Exact unit coverage exists; no separate multi-process governance contender proof exists. |
| provider turn authority → credential lease → immutable Delegate invocation-claim directory | `ProviderInvocationClaimService` | Duplicate activation claim and provider-journal start compete. Replay fingerprint and journal multi-process proof exist; the multi-lock policy remains service-local. |
| operational/governance interruption authority or lease identity → immutable result directory | three internal interruption enforcement services | Claim admission guards read exact immutable interruption results. Single-winner native locking exists; result reconstruction is lifecycle-specific. |
| `codex-imperii` plus nested mutable path lock | `CodexImperiiStore`; operational construction coordinator | Generation/checkpoint CAS rejects stale writers; `DelegateMissionOperationalTransitionConcurrencyTest` and Crash Demonstration 1 prove convergence. |
| `delegate-deployment-custody:<sha256 authorizationId>` plus transaction/custody path locks | deployment custody coordinator | Resume scans transaction state by authorization. Crash Demonstration 2 proves each checkpoint; lock ordering is implicit in the coordinator. |
| `delegate-terminal:<sha256 authorizationId>` plus transaction/custody/binding path locks | terminal transition coordinator | Resume scans by authorization. Crash Demonstration 4 proves forward completion; lock ordering is implicit in the coordinator. |
| immutable directory lock only | most lifecycle services below | `existing()`/`glob()`/validation occurs before `ImmutableRecordStore::put()`. Same-ID exact replay is safe, but two decisions that derive different result IDs can both commit. |
| exclusive file creation (`fopen(..., 'x')`) | legacy Legate provider invocation claim | One claim filename wins, but later response/turn/result writes are not one recoverable transition. |

## Consumable authority inventory

Every row identifies the authoritative inputs, competent consumer, consumption/result shape,
partial-write and recovery posture, and current proof. “Digest chain” means every named source ID and
digest plus the occupied actor identity carried by that service; it does not imply a shared replay
fingerprint.

| Authority family and issuer | Authoritative inputs and replay identity | Consumer and result | Partial-write, recovery, and proof | Posture |
| --- | --- | --- | --- | --- |
| Governance cognition authority from the exact Office resolver; Imperator activation authority; Clavium governance lease | Authority identity/source, cluster, Seat/purpose, input digest, request, resource decision, provider/model/configuration, expiry. Claim ID is a lifecycle-specific canonical tuple, not `ReplayFingerprint`. | `GovernanceCognitionRequestService` → `GovernanceCognitionLeaseService` → `GovernanceCognitionInvocationClaimService`; one claim embeds both consumptions and pre-I/O idempotency identity. | Nested authority→lease lock prevents split consumption. Unknown outcome is non-replayable; provider journal/envelope governs later recovery. No direct multi-process claim proof. | `LOCKED_FRAGMENTED` |
| Operational bounded-execution cognition authority from Curia; Imperator resource decision activation authority; Clavium operational lease | Exact authorization/request/decision/lease digests, target, provider/model/configuration, input/profile requirement digest, iteration, transitive expiry. `ReplayFingerprint` produces the claim ID and is bound into the Batch 2 envelope. | `OperationalCognitionRequestService` → `OperationalCognitionLeaseService` → `OperationalCognitionInvocationClaimService`; one claim embeds the versioned transaction/recovery contracts plus both consumptions. | Authority→lease locks converge with interruption. Claim and opposing interruption races, envelope tamper, expiry, replay conflict, absent sources, and all four logical commit-boundary recovery observations are proved. The single immutable write exposes no torn consumption/result state. Later provider outcome remains journal-governed and outside the transition. | `TRANSACTIONAL_CANONICAL` |
| Delegate turn authority and credential lease issued by provider activation | Activation, exact turn authority, lease, binding, resource decision, model access/binding, input and configuration. `ReplayFingerprint` is stored on the claim. | `ProviderInvocationClaimService`; durable claim embeds both consumptions and provider idempotency identity. `DelegateMissionBoundedCognitionTurnService` completes the response/turn result. | Claim is pre-I/O and single-winner. Provider journal has multi-process start proof; crash demonstrations cover unknown outcome and response-forward recovery. Turn completion still uses lifecycle-specific consumption fields. | `LOCKED_FRAGMENTED` |
| Delegate missing-turn recovery authorization issued by provider recovery assessment | Recovery authorization ID/digest, sealed response envelope ID/digest, original claim, turn authority and lease identities, target and response identity. | `DelegateMissionTurnRecoveryService`; `AuthorityConsumptionStore` seals the recovery consumption and an immutable turn result. | Shared generic consumption is canonical; replay uses the same authoritative inputs and cannot invoke the provider. Crash Demonstration 3 proves provider-free forward recovery. | `TRANSACTIONAL_CANONICAL` |
| Governance interruption disposition and single-use Locksmith enforcement authority | Exact governance event/request/native authority, source authorizer, current Locksmith occupancy, disposition and expiry. | `InternalGovernanceInterruptionEnforcementService`; immutable denial result consumed by admission guard. | One native lock and immutable result; reconstruction validates the bounded lineage. It does not use the generic consumption store. | `LOCKED_FRAGMENTED` |
| Governance-lease interruption disposition and single-use Locksmith enforcement authority | Exact unclaimed governance lease, request/resource decision, source authorizer, current Locksmith occupancy, disposition and expiry. | `InternalGovernanceLeaseInterruptionEnforcementService`; immutable denial result consumed by governance claim admission. | Uses claim-compatible authority→lease order; claim existence is rechecked while locked. Lifecycle-specific reconstruction exists. | `LOCKED_FRAGMENTED` |
| Operational-lease interruption disposition and single-use Locksmith enforcement authority | Exact unclaimed operational lease, authorization/request/resource decision, source authorizer, current Locksmith occupancy, disposition and expiry. | `InternalOperationalLeaseInterruptionEnforcementService`; immutable denial result consumed by operational claim admission. | Uses exact operational authority→lease order; multi-process opposing-race proof and nine-artifact reconstruction exist. | `LOCKED_FRAGMENTED` |
| Delegate operational qualification, assembly, and Seat-binding authorities from Profile approval/Conscription checkpoints | Exact approved Profile, prior Folia, Manifestation, mission Seat, expected Codex generation/checkpoint. Replay is coordinator input plus Codex transition fingerprint. | Three Delegate operational construction services through `DelegateMissionOperationalTransitionCoordinator`; immutable Folia plus generation-1 Codex and inert binding. | Recoverable checkpoints and CAS; multi-process contention and every injected checkpoint are proved by Crash Demonstration 1. | `TRANSACTIONAL_CANONICAL` |
| Seneschal Delegate deployment authorization | Exact binding, Persona custody, deployment scope, authorizer actor, decision and digest. | `DelegateMissionOperationalCustodyTransitionService` through `DelegateMissionDeploymentCustodyTransitionCoordinator`; mutable custody plus immutable transition Folium. | PREPARED → CUSTODY_DEPLOYED → TRANSITION_RECORDED → COMPLETE, forward-resumable. Crash Demonstration 2 proves all boundaries. | `TRANSACTIONAL_CANONICAL` |
| Seneschal Delegate terminal return authorization | Exact return contract, result disposition, active binding, Persona custody, authorizer actor. | `DelegateMissionTerminalReturnService` through `DelegateMissionTerminalTransitionCoordinator`; custody restored, binding retired, terminal Folium sealed. | PREPARED → CUSTODY_RESTORED → BINDING_RETIRED → TERMINAL_RECORDED → COMPLETE. Crash Demonstration 4 proves convergence. | `TRANSACTIONAL_CANONICAL` |
| Legate governed commission acceptance, bounded cognition-turn, provider activation, result-review, and delivery authorities | Issuance/acceptance/turn decision/activation/binding digests, actor identity, provider/model/configuration and expiry. IDs are lifecycle-derived canonical hashes. | `LegateGovernedCommissionDispositionService`, `LegateProviderInvocationActivationService`, `LegateBoundedCognitionTurnService`, `LegateCognitionResultDeliveryService`, `CommissionerCognitionResultReviewService`; each result embeds consumed authority. | Claim uses exclusive file creation, but claim→provider response→turn→delivery/review is not checkpointed as one recoverable transition. Exact replay is service-specific. | `RECOVERY_INCOMPLETE` |
| Operational adoption intake/evaluation/assessment/reconciliation/decision authorities issued successively by Curia | Presentation/intake/evaluation, assessment commission per Seat, independent assessments, reconciliation and decision opening, with source digests and exact occupied actors. | `OperationalAdoptionIntakeDispositionService`, `OperationalAdoptionIndependentAssessmentService`, `OperationalAdoptionReconciliationService`, `OperationalAdoptionDispositionService`; immutable next-stage records embed consumption. | Each consumer scans for an existing result before an immutable write; no common winner scope, replay fingerprint, contention, or fault proof. | `RACE_EXPOSED` |
| Delegate Mission Steps 1–18 demand, personnel, Profile-scope, derivation, intake, examination-preparation, assembly and Stand-admission authorities | Prior Folium ID/digest, exact mission/Persona/Profile/Manifestation, competent occupied actor and disposition. Result IDs generally include source and decision. | Exact consumers: `DelegateMissionCapabilityDemandIntakeService`, `DelegateMissionPersonnelResolutionService`, `DelegateMissionPersonnelUseAcceptanceService`, `DelegateMissionPersonaReservationDispositionService`, `DelegateMissionProfileScopeDecisionService`, `DelegateMissionProfileDerivationCommissionRequestService`, `DelegateMissionProfileDerivationCommissionDispositionService`, `DelegateMissionProfileCandidateDerivationReturnService`, `DelegateMissionProfileCandidateIntakeDispositionService`, `DelegateMissionExaminationPreparationHandoffService`, `DelegateMissionExaminationPreparationIntakeDispositionService`, `DelegateMissionExaminationManifestationAssemblyService`, `DelegateMissionExaminationStandAdmissionDispositionService`, and `DelegateMissionProfileExaminationOpeningService`. | Immutable results record consumed fields, but validation/existing-result selection is outside the immutable directory lock. No family-wide contention/fault contract. | `RACE_EXPOSED` |
| Delegate Mission Steps 19–42 question commissions, authorship, dispatch, testimony, finding, reconciliation, and disposition authorities from Lord Speaker/Senators/Bailiff | Exact hearing/commission/question/testimony/finding records, jurisdiction, actor binding and source digests. | `DelegateMissionFirstQuestionCommissionDispositionService`, `DelegateMissionJurisdictionQuestionAuthorshipEngine`, `DelegateMissionQuestionDispatchAuthorizationEngine`, `DelegateMissionQuestionDispatchEngine`, `DelegateMissionTestimonyResponseEngine`, both subsequent commission issuance/disposition engines, `DelegateMissionFindingAuthorityOpeningService`, `DelegateMissionSenatorFindingService`, `DelegateMissionDeliberationOpeningService`, `DelegateMissionFindingReconciliationService`, `DelegateMissionDispositionAuthorityOpeningService`, and `DelegateMissionSenateDispositionService`. | Jurisdiction engines preserve separate authorities, but scan/validate/write replay is service-local. No simultaneous-writer or commit-fault proof. | `RACE_EXPOSED` |
| Delegate Mission Steps 43 and 47–65 Profile approval, mission control, model-governance, resource, attestation, lease and activation authorities | Exact Senate decision, binding/custody/activation, commission/readiness, criteria/catalogue/findings/recommendation/selection, model binding, access attestation, resource decision and actor digests. | `DelegateMissionProfileApprovalDecisionService`, `DelegateMissionRuntimeActivationService`, `DelegateMissionControlIntakeDispositionService`, `DelegateMissionBoundedCognitionCommissionService`, `DelegateMissionResourceInvocationReadinessAssessmentService`, `DelegateMissionModelCriteriaRequestService`, `DelegateMissionModelCriteriaDecisionService`, `DelegateMissionOracleCommissionIssuanceService`, `ModelRequirementCommissionAcceptanceService`, `ModelEvaluationCaseOpeningService`, `ModelEligibilityFindingService`, `ModelComparativeAssessmentService`, `ModelRecommendationService`, `DelegateMissionModelSelectionDecisionService`, `DelegateMissionModelBindingSealingService`, `DelegateMissionModelAccessAttestationService`, `DelegateMissionResourceInvocationDecisionService`, and `DelegateMissionProviderInvocationActivationService`. | Runtime activation and selected mechanics use shared immutable/replay primitives, but the authority chain as a whole has no common transaction. Lease/turn consumption becomes locked only at the later claim. | `RACE_EXPOSED` |
| Delegate Mission Steps 67–68 result-disposition and return-opening authorities | Exact turn/result, provider disposition, Seneschal actor, predeclared return contract. | `DelegateMissionCognitionResultDispositionService` and `DelegateMissionReturnAuthorizationService`; exact immutable results. | Shared mechanics validate references and immutable replay, but selection/consumption is not locked with a generic authority winner. Step 69 itself is canonical above. | `RACE_EXPOSED` |
| Legacy Profile examination question, testimony, finding, reconciliation, disposition and Imperator approval authorities | Exact Profile/examination records, jurisdiction, occupied Senate actor, evidence digests and decision. | `ProfileExaminationQuestionAuthorshipService`, `ProfileExaminationTestimonyOpeningService`, `ProfileExaminationSenatorFindingService`, `ProfileExaminationDeliberationOpeningService`, `ProfileExaminationReconciliationService`, `ProfileExaminationDispositionAuthorityOpeningService`, `ProfileExaminationDispositionService`, and `ProfileApprovalDecisionService`. | Immutable-result replay is based chiefly on source lookup; no complete shared fingerprint, winner scope, concurrency, or fault proof. | `RACE_EXPOSED` |
| Model-bound Profile examination question, testimony, finding, reconciliation, disposition and Imperator approval authorities | Model-bound Profile/examination sources, jurisdiction, actor and digest chain. | `ModelBoundProfileEvidenceQuestioningService`, `ModelBoundProfileExaminationTestimonyOpeningService`, `ModelBoundProfileFindingAuthorityOpeningService`, `ModelBoundProfileSenatorFindingService`, `ModelBoundProfileDeliberationOpeningService`, `ModelBoundProfileReconciliationService`, `ModelBoundProfileDispositionAuthorityOpeningService`, `ModelBoundProfileDispositionService`, and `ModelBoundProfileApprovalDecisionService`. | Same immutable-result/source-scan mechanics as the legacy Profile chain; no contention or fault proof. | `RACE_EXPOSED` |
| Subordinate Persona Senate question/testimony/finding/reconciliation/disposition authorities | Exact confirmation case, witness generation, required-trial ledger, jurisdiction, pressure condition, actor and evidence digests. | All `SubordinatePersona*QuestionService`, `*TestimonyService`, `*FindingService`, `SubordinatePersonaFindingAuthorityOpeningService`, `SubordinatePersonaFindingReconciliationService`, `SubordinatePersonaReconciliationOpeningService`, `SubordinatePersonaDispositionAuthorityOpeningService`, and `SubordinatePersonaSenateDispositionService`. | Shared immutable store and atomic primitive are widely used, but semantic existing-result selection and authority validation precede the write lock. No complete family contention/fault proof. | `RACE_EXPOSED` |
| Bootstrap/office construction, conscription, commissioning, activation, admission and Seat-binding authorities | Exact provisioning case/commission/authorization/persona/profile/custody/Seat and actor digests. | `ArtificerConscriptionService`, `AuthorshipResidentConscriptionService`, `ConstableConscriptionService`, `GuildhallConscriptionService`, construction/commission acceptance services in Authorship, Foundry, Guildhall and Garrison, admission services, and their Seat-binding/activation services. | These older consumers use mixed direct file writes and immutable stores. Multi-write construction and activation have no uniform checkpoint or replay fingerprint. | `RECOVERY_INCOMPLETE` |
| Legacy operational Profile qualification, Manifestation assembly, Seat binding, deployment, activation, execution and return authorities | Profile approval, Persona custody, mission target, binding generation, deployment/return decision and actor digests. | `OperationalProfileQualificationService`, `OperationalManifestationAssemblyService`, `OperationalManifestationSeatBindingService`, `OperationalDeploymentAuthorizationService`, `OperationalCustodyTransitionService`, `LegateRuntimeActivationService`, `BoundedOperationalExecutionService`, `OperationalReturnAuthorizationService`, and `OperationalReturnRetirementService`; model-bound variants are separate exact consumers. | Several dependent state and immutable writes lack the Delegate coordinators' PREPARED/checkpoint recovery. No complete contention/fault proof. | `RECOVERY_INCOMPLETE` |
| Oracle research commission/evidence authorities and non-Delegate model-selection planning authorities | Commission, catalogue snapshot, admitted evidence, selection plan and occupied Augur/Seneschal digests. | `OracleResearchCommissionService`, `OracleResearchEvidenceAdmissionService`, `ModelEvaluationCaseOpeningService`, `ModelEligibilityFindingService`, `ModelComparativeAssessmentService`, `ModelRecommendationService`, `ModelRequirementCommissionAcceptanceService`, `ModelSelectionPlanningDecisionService`, and `MissionAuthorizationDerivationService`. | Source-scan replay and immutable writes are fragmented; no shared consumption record or contention proof. | `RACE_EXPOSED` |
| Iron Gate dispatch, Lazaretto admission, sortie cognition/tool and external receipt authorities | External request/manifest/payload/tool/destination/credential and receipt identities. | `IronGate`, `BoundaryDispatch`, `InboundLazaretto`, `OneShotSortieRunner`, sortie cognition invokers and tool executors. | External effects and receipt binding require their own preparation. No migration or runtime claim is made here. | `DEFERRED_EXTERNAL_BOUNDARY` |

## Failure and recovery matrix

| Condition | Current system-wide result | Classification |
| --- | --- | --- |
| Exact duplicate | Canonical stores return exact record; many lifecycle services find and return a source-linked record; behavior is not one contract. | `EXISTS_FRAGMENTED` |
| Conflicting reuse | Canonical stores/fingerprints reject it; lifecycle services vary between conflict errors and permitting a different output-derived ID. | `EXISTS_FRAGMENTED` |
| Missing or tampered source | Canonical validators fail stopped in migrated corridors; older direct readers and scans vary. | `EXISTS_FRAGMENTED` |
| Expired authority/lease | Cognition and interruption corridors reject at the supplied clock boundary; many lifecycle authorities have no expiry. | `EXISTS_FRAGMENTED` |
| Stale or superseded authority | CAS rejects stale mutable state; source-digest checks reject some stale evidence. General supersession semantics are absent. | `EXISTS_FRAGMENTED` |
| Already consumed authority | Claims and interruption paths discover the exact competing immutable result under shared locks. Most immutable lifecycle results do not expose one canonical consumption index. | `EXISTS_FRAGMENTED` |
| Process death before immutable result | The adopted operational claim now proves zero artifact after `PREPARED` and deterministic exact retry. Other consumers remain fragmented, and a runtime-wide unknown-outcome contract is absent. | `EXISTS_FRAGMENTED` |
| Process death between dependent writes | Four checkpointed corridors resume forward; older construction, Legate, and operational paths may tear. | `EXISTS_FRAGMENTED` |
| Process death after provider I/O | Provider journal marks unknown outcome non-replayable; sealed-response recovery cannot invoke provider. | `EXISTS_CANONICALLY` |
| Lock acquisition in inverse order | No central declaration or detector exists. | `ABSENT` |
| External effect without bound receipt | Explicitly outside this campaign. | `DEFERRED_BOUNDARY` |

## Smallest safe migration sequence

No step is authorized by this inventory.

1. Define one shared transactional-consumption contract that composes `AtomicTransition`, complete
   `ReplayFingerprint`, one-or-many authority identities, exact source digests, competent consumer,
   immutable result identity, and explicit unknown-outcome/recovery state. Do not replace any
   lifecycle authority schema or actor.
2. Adopt the contract first in the operational cognition lease + cognition-authority claim. It is
   the smallest representative two-authority consumer with exact lock order, full authoritative
   inputs, claim/claim and claim/interruption contention proof, no external I/O inside the
   transition, and read-only reconstruction. Preserve
   `oca-cognition-authority:<sha256 authorityId>` → `oca-lease:<sha256 leaseId>` exactly.
3. **Completed in Batch 3.** Prove at every internal commit boundary: one winner, exact replay equivalence, conflicting replay
   refusal, immutable-result uniqueness, interruption convergence, and recovery with
   `credential_resolved=false`, `provider_journal_created=false`, and
   `network_access_performed=false`.
4. Migrate the structurally parallel governance claim only after the operational contract proves
   that authority resolution through the registry does not weaken complete replay identity.
5. Migrate the Delegate provider invocation claim while preserving provider-journal and unknown-
   outcome semantics. Do not absorb external I/O into the consumption transaction.
6. Migrate internal immutable-result consumers by bounded competent-actor clusters: Delegate Senate
   engines; Profile/model-bound Senate engines; operational adoption; Oracle/model governance;
   construction/admission. Each cluster requires its own contention and fault proof.
7. Migrate older multi-write operational, bootstrap, and Legate paths only through explicit
   recoverable checkpoints; a consumption wrapper alone cannot repair torn state.
8. Mechanically reconstruct coverage, retain explicit external exclusions, run adversarial review,
   and close the campaign documentation-only.

## Preserved boundary

Preparation changed no authority, schema, issuer, consumer, scope, expiry, lock scope/order, replay
behavior, custody, deployment, cognition, journal, retirement, external action, or credential
boundary. Generalized revocation, propagation, telemetry, reassessment, containment, incidents,
Iron Gate, Lazaretto, sorties, external receipts, and credential-platform work remain closed.
