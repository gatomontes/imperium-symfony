# Transactional Authority Consumption Adoption Batch 9 complete

## Result

Batch 9 audits the Oracle/model-governance cluster and adopts exactly two Delegate Mission Curia
consumers:

| Requirement | Model-criteria presentation | Model-selection decision |
| --- | --- | --- |
| Existing authority | `oracle_model_requirement_commission_authority.authority_id` from resource-readiness assessment | `curia_selection_decision_authority.authority_id` from Oracle recommendation |
| Authoritative inputs | exact readiness digest, instance, Seneschal, normalized criteria and `presented_at` | exact recommendation digest, instance, Seneschal, disposition/model/configuration/rationale and `decided_at` |
| Competent consumer | `DelegateMissionModelCriteriaRequestService` | `DelegateMissionModelSelectionDecisionService` |
| Lock scope/order | order 1: `delegate-model-governance-authority:<sha256 authorityId>` | order 1: `delegate-model-governance-authority:<sha256 authorityId>` |
| Competing paths | divergent criteria/result identity for the same readiness authority | divergent disposition/model/configuration/result identity for the same selection authority |
| Replay identity | fingerprint of exact unchanged lifecycle-result surface | fingerprint of exact unchanged lifecycle-result surface |
| Consumption/result | embedded consumed criteria-proposal authority plus one immutable request | embedded consumed selection authority plus one immutable decision |
| Partial-write exposure | one immutable write; no external effect | one immutable write; no external effect |
| Recovery | exact committed result/envelope or exact historical result | exact committed result/envelope or exact historical result |
| Proof | two-process competing-result convergence and fault after immutable commit | same shared transition proof plus service-specific reconstruction |

Both requirements are `EXISTS_CANONICALLY`; both consumers are now
`TRANSACTIONAL_CANONICAL`. `DelegateMissionModelGovernanceAuthorityTransition` locks before the
authoritative reread and winner selection, fingerprints the unchanged record, and commits logical
consumption with the immutable result through `ImmutableRecordStore`. `NO_EXPIRY_DECLARED` records
only the existing absence of expiry.

## Exact exclusions

| Requirement | Classification | Consumer posture and reason |
| --- | --- | --- |
| Legacy model-requirement acceptance | `ABSENT` | `ModelRequirementCommissionAcceptanceService` remains `RACE_EXPOSED`; acceptance authority is an Augur occupancy boolean. |
| Legacy evaluation-case opening | `ABSENT` | `ModelEvaluationCaseOpeningService` remains `RACE_EXPOSED`; opening authority is a boolean. |
| Legacy comparative assessment | `ABSENT` | `ModelComparativeAssessmentService` remains `RACE_EXPOSED`; phase authority is a boolean without ID. |
| Oracle eligibility finding | `EXISTS_FRAGMENTED` | `ModelEligibilityFindingService` remains `RECOVERY_INCOMPLETE`; finding and optional phase closure are separate writes without a recovery checkpoint. |
| Legacy Oracle recommendation | `EXISTS_FRAGMENTED` | `ModelRecommendationService` remains `RACE_EXPOSED`; authority is explicit but the result omits native `instance_id`. |
| Legacy Curia planning selection | `EXISTS_FRAGMENTED` | `ModelSelectionPlanningDecisionService` remains `RACE_EXPOSED`; authority is explicit but the result omits native `instance_id`. |
| Model-requirement and Delegate Oracle commission issuance | `EXISTS_FRAGMENTED` | `ModelRequirementCommissionService` and `DelegateMissionOracleCommissionIssuanceService` remain `RECOVERY_INCOMPLETE`; each writes a commission and inbox copy. |
| Oracle research and returned evidence | `DEFERRED_BOUNDARY` | `OracleResearchCommissionService` and `OracleResearchEvidenceAdmissionService` remain `DEFERRED_EXTERNAL_BOUNDARY` because the route crosses sortie/external evidence. |
| Delegate criteria decision | `ABSENT` | `DelegateMissionModelCriteriaDecisionService` remains `RACE_EXPOSED`; its competent authority is an Imperator decision surface, not an explicit consumed single-use authority. |
| Delegate binding, access, resource decision and activation | `EXISTS_FRAGMENTED` | Their consumers remain `RACE_EXPOSED` inside separately bounded construction, credential and provider-admission surfaces. |

No missing authority ID, instance identity, checkpoint, timestamp or transaction is invented.

## Preserved boundaries

No authority schema, ID, issuer, holder, actor, source identity, result schema, result ID, timestamp,
public method, model criteria, model choice or downstream authority changed. No model binding,
credential release/use, provider invocation, network, external action or execution enters the
transition.

This migration opens no authority redesign, cognition recovery, construction/admission, older
recovery, generalized revocation, propagation, telemetry, reassessment, containment, incident,
Iron Gate, Lazaretto, sortie, external-receipt, provider-journal or credential-platform boundary.

## Next separately bounded batch

Only Batch 10 may next be considered: migrate the construction and admission consumer cluster if a
truthful shared lock, replay, recovery and proof boundary exists without merging competent actors.

Four estimated batches remain: Batches 10–13. This is a planning forecast, not authorization.
Batch 10 is not authorized by this handoff; it requires an explicit continuation instruction.
