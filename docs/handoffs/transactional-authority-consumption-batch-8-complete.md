# Transactional Authority Consumption Adoption Batch 8 complete

## Result

Batch 8 inventories the complete operational-adoption consumer cluster and adopts exactly two
single-result Seneschal consumers:

| Requirement | Reconciliation | Final disposition |
| --- | --- | --- |
| Existing authority | `reconciliation_authority.authority_id` from `OperationalAdoptionReconciliationOpeningService` | `adoption_decision_authority.authority_id` from `OperationalAdoptionDecisionOpeningService` |
| Authoritative inputs | exact opening ID/digest, admitted assessment references, authored reconciliation, current Seneschal, `reconciled_at` | exact decision-opening ID/digest, unchanged reconciliation/assessment chain, disposition, rationale, limitations, current Seneschal, `decided_at` |
| Competent consumer | `OperationalAdoptionReconciliationService` | `OperationalAdoptionDispositionService` |
| Lock scope/order | order 1: `operational-adoption-authority:<sha256 authorityId>` | order 1: `operational-adoption-authority:<sha256 authorityId>` |
| Competing paths | divergent reconciliation/result identity for the same authority | divergent disposition, rationale, limitations or result identity for the same authority |
| Replay identity | fingerprint of the exact unchanged lifecycle-result surface | fingerprint of the exact unchanged lifecycle-result surface |
| Consumption/result | embedded consumed authority plus one immutable reconciliation | embedded consumed authority plus one immutable terminal disposition |
| Partial-write exposure | one immutable write; no external effect | one immutable write; no external effect |
| Recovery | exact committed result/envelope or exact historical result | exact committed result/envelope or exact historical result |
| Proof | two-process competing-result convergence and fault after immutable commit | same shared transition proof plus service-specific reconstruction |

`OperationalAdoptionAuthorityTransition` acquires the exact authority lock before source reread,
validation and result selection. It seals one
`imperium.runtime-transactional-authority-consumption/v1` envelope and commits logical consumption
with the immutable result through `ImmutableRecordStore`. Historical results without the envelope
remain valid and are not rewritten. Adopted envelope divergence fails stopped.

Both requirements are `EXISTS_CANONICALLY`; both consumers are now
`TRANSACTIONAL_CANONICAL`. `NO_EXPIRY_DECLARED` records only the existing absence of expiry.

## Exact exclusions

The governing-intake requirement is `ABSENT`: the presentation exposes `intake_pending`, not a
canonical single-use authority identity, and the disposition explicitly records
`evaluation_opening_authority=false`. `OperationalAdoptionIntakeDispositionService` remains
`RACE_EXPOSED`; migration would require authority redesign.

Independent assessment authority is `EXISTS_FRAGMENTED`. Each jurisdiction has a canonical
authority and exact Curialis, but `OperationalAdoptionIndependentAssessmentService` can write the
assessment and then separately write the all-assessments completion. A crash between those writes
cannot reconstruct the original `completed_at`. It remains `RECOVERY_INCOMPLETE` pending a
separately authorized checkpoint/recovery contract. The three Curialis actors are not merged.

No other operational-adoption presentation, evaluation-opening, commission issuance, commission
acceptance, reconciliation-opening, or decision-opening record consumes a canonical authority.

## Preserved boundaries

No authority schema, ID, issuer, holder, competent actor, source identity, result schema, result ID,
timestamp, public method, disposition or downstream authority changed. No provider, model, network,
credential, tool, operational-use, external-action or execution effect enters the transition.

This migration opens no authority redesign, cognition recovery, Oracle/model governance,
construction/admission, older recovery, generalized revocation, propagation, telemetry,
reassessment, containment, incident, Iron Gate, Lazaretto, sortie, external-receipt,
provider-journal or credential-platform boundary.

## Next separately bounded batch

Only Batch 9 may next be considered: migrate the Oracle and model-governance consumer cluster if a
truthful shared lock, replay, recovery and proof boundary exists without merging competent actors.

Five estimated batches remain: Batches 9–13. This is a planning forecast, not authorization.
Batch 9 is not authorized by this handoff; it requires an explicit continuation instruction.
