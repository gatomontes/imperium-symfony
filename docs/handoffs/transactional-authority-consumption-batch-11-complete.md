# Transactional Authority Consumption Adoption Batch 11 complete

## Result

Batch 11 audits the recovery-incomplete cluster and adopts exactly one complete corridor:

| Requirement | Oracle eligibility finding and phase reconciliation |
| --- | --- |
| Existing authority | per-model `eligibility_authorities.*.authority_id` from the exact evaluation case |
| Authoritative inputs | case instance/digest, frozen model/criteria/catalogue scope, evidence, Augur, disposition and native `issued_at` |
| Competent consumer | `ModelEligibilityFindingService` |
| Lock scope/order | order 1: `oracle-eligibility-case:<sha256 caseId>`; the case lock serializes every contained authority |
| Competing paths | opposing findings for one authority and concurrent final findings for distinct authorities in the same case |
| Replay identity | fingerprint of the separate immutable transition record bound to the exact native finding |
| Consumption/result | unchanged immutable finding plus separate immutable consumption transition; optional phase is a deterministic dependent projection |
| Partial-write exposure | finding may exist without phase/transition after a crash; no external effect |
| Recovery | retry treats the finding as checkpoint, reconciles phase from all findings, then commits the exact transition |
| Proof | faults after finding, phase and transition commits; two-process opposing-finding convergence |

The authority requirement is `EXISTS_CANONICALLY`; its former dependent-write recovery was
`EXISTS_FRAGMENTED`. The consumer is now `TRANSACTIONAL_CANONICAL`.

The native finding and phase schemas remain unchanged. `OracleEligibilityAuthorityTransition`
stores instance identity from the immutable case, seals the exact finding as the non-embedded result,
and records `NO_EXPIRY_DECLARED` without inventing expiry. Phase `closed_at` is the latest native
`issued_at` among the complete finding set, which preserves the original authored acts and cannot be
replaced by a retry clock.

## Exact exclusions

| Requirement | Classification | Consumer posture and reason |
| --- | --- | --- |
| Operational-adoption independent assessment completion | `EXISTS_FRAGMENTED` | `OperationalAdoptionIndependentAssessmentService` remains `RECOVERY_INCOMPLETE`; cross-jurisdiction completion is not absorbed into the Oracle case contract. |
| Model-requirement and Delegate Oracle commission issuance | `EXISTS_FRAGMENTED` | `ModelRequirementCommissionService` and `DelegateMissionOracleCommissionIssuanceService` remain `RECOVERY_INCOMPLETE`; commission/inbox dual writes have no selected checkpoint contract. |
| Canonical subordinate Persona admission | `EXISTS_FRAGMENTED` | `SubordinatePersonaCanonicalAdmissionService` remains `RECOVERY_INCOMPLETE`; custody and disposition remain separate under boolean Constable powers and omit native decision time. |
| Guildhall cohort and subordinate construction/activation | `EXISTS_FRAGMENTED` | Their consumers remain `RECOVERY_INCOMPLETE`; a single act can fan out over several records without a selected recovery journal. |
| Older operational, bootstrap and Seat activation | `EXISTS_FRAGMENTED` | Their consumers remain `RECOVERY_INCOMPLETE`; mixed immutable/mutable writes do not share this case lock or reconstruction rule. |
| Legate claim→provider→turn→delivery/review chain | `EXISTS_FRAGMENTED` | Legate consumers remain `RECOVERY_INCOMPLETE`; provider unknown-outcome recovery cannot be inferred from an internal finding checkpoint. |
| Legacy Oracle recommendation and Curia planning selection | `EXISTS_FRAGMENTED` | Their consumers remain `RACE_EXPOSED`; native results still omit `instance_id`. |
| Legacy Oracle boolean powers | `ABSENT` | Acceptance, evaluation opening and comparative assessment remain `RACE_EXPOSED`; no single-use authority ID exists. |
| Cognition-bearing consumers | `EXISTS_FRAGMENTED` | They remain `RECOVERY_INCOMPLETE`; unknown model outcomes require separately authorized pre-I/O claims and journals. |
| Credential, provider and external-effect paths | `DEFERRED_BOUNDARY` | Consumers remain `DEFERRED_EXTERNAL_BOUNDARY`; no credential-platform, provider-journal, Iron Gate, Lazaretto, sortie or external-receipt boundary opens. |

No absent authority, missing instance, missing timestamp, provider outcome or rollback is invented.
No other multi-write path is generalized from the Oracle case-specific proof.

## Preserved boundaries

No authority schema, ID, issuer, holder, source finding, result schema, result ID, disposition,
evidence, timestamp, model eligibility rule, downstream assessment authority or public issue method
changed. The optional constructor fault seam is test-only and inert by default.

This migration opens no authority redesign, revocation, propagation, telemetry, reassessment,
containment, incident, credential resolution/release/use, provider activation/invocation,
provider-journal, network, external action, Iron Gate, Lazaretto, sortie, external-receipt or
credential-platform boundary.

## Next separately bounded batch

Only Batch 12 may next be considered: mechanically reconstruct adoption coverage, verify every
explicit exclusion and perform adversarial review. It is not authorization to migrate a remaining
consumer or repair a deferred boundary.

Two estimated batches remain: Batches 12–13. This is a planning forecast, not authorization.
Batch 12 is not authorized by this handoff; it requires an explicit continuation instruction.
