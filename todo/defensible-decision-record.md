# Defensible Decision Record

## Purpose

Imperium already preserves attributable actors, authority boundaries, source artifacts, digests, limitations, state transitions, and proceeding lineage. It does not yet require every consequential decision to consolidate the seven fields of a defensible decision record into one canonical sealed artifact.

This work must preserve the complete underlying proceeding. The canonical record summarizes and binds the decision; it does not replace the Curian transcript or its source artifacts.

## Canonical contract

- [ ] Define `imperium.decision-record/v1` as the canonical envelope for consequential Curia, Imperator, Senate, Garrison, deployment, and execution decisions.
- [ ] Require an explicit `decision` containing the disposition, decided scope, granted authority, denied authority, and resulting state.
- [ ] Require `decision_owner` containing the actor ID, Office or Seat, authority basis, and accountability boundary.
- [ ] Require `options_considered[]` containing the actual alternatives examined and their dispositions, not merely the choices the decision-maker was permitted to select.
- [ ] Require `risks[]`, with each entry containing:
  - `identified_risk`;
  - `proposed_treatment`;
  - `applied_treatment`;
  - `residual_risk`;
  - `residual_risk_owner`;
  - `acceptance_disposition`.
- [ ] Require `evidence_relied_on[]` containing artifact IDs, record digests, provenance, versions, and relevance to the decision.
- [ ] Require a substantive `rationale` explaining why the decision was reasonable from the evidence available at that time.
- [ ] Add a runtime-generated `decided_at` in UTC, distinct from proceeding, request, approval, acceptance, deployment, and execution timestamps.
- [ ] Bind the record to its `instance_id`, `proceeding_id`, source requests, prior decisions, limitations, and downstream authority lineage.
- [ ] Seal the complete canonical record with its digest.

## Enforcement

- [ ] Reject consequential decisions missing any required canonical field.
- [ ] Reject `AUTHORIZED` and `APPROVED` decisions whose rationale is empty or merely repeats the disposition.
- [ ] Reject decisions that rely on nonexistent, unsealed, modified, or out-of-scope evidence.
- [ ] Require explicit residual-risk acceptance whenever identified risk remains after treatment.
- [ ] Prevent an actor from accepting residual risk outside that actor's competent authority.
- [ ] Preserve non-authorizing alternatives, objections, clarification requests, refusals, revision returns, and deferrals through the same contract.
- [ ] Preserve idempotency for identical submissions and fail closed on conflicting decisions.
- [ ] Preserve supersession lineage; never mutate a sealed historical decision record.

## Proceeding consolidation

- [ ] Implement a mechanical `DecisionRecordAssembler` that derives a candidate record from the exact proceeding and referenced artifacts.
- [ ] Keep cognition responsible for identifying alternatives, risks, evidence relevance, and rationale.
- [ ] Keep machinery responsible for attribution, timestamps, lineage, digest validation, completeness, sealing, and persistence.
- [ ] Preserve the full Curian transcript as underlying evidence.
- [ ] Make every referenced transcript turn and source artifact reconstructable from the canonical record.

## Adoption order

- [ ] Apply the contract to Imperator personnel-use authorization decisions.
- [ ] Apply the contract to Imperator construction authorization decisions.
- [ ] Apply the contract to Imperator Profile-derivation authorization decisions.
- [ ] Apply the contract to Garrison custody and handoff decisions.
- [ ] Apply the contract to Senate findings and final dispositions.
- [ ] Apply the contract to mission-plan approval and deployment authorization.
- [ ] Apply the contract to Iron Gate execution authorization and execution receipts.
- [ ] Version or adapt existing record readers without rewriting historical sealed artifacts.

## Verification

- [ ] Add contract tests proving all seven fields are mandatory.
- [ ] Test multi-option deliberation with explicit rejection reasons.
- [ ] Test treated risk with accepted residual risk and a named competent owner.
- [ ] Test refusal when residual-risk ownership is absent or incompetent.
- [ ] Test exact evidence, provenance, and digest reconstruction.
- [ ] Test that `decided_at` is distinct from request and proceeding timestamps.
- [ ] Test tampering, supersession, idempotency, and conflicting submissions.
- [ ] Produce one end-to-end decision record that a reviewer who was not present can reconstruct without searching unrelated logs.

## Implementation sequence

1. Canonical schema.
2. Mechanical assembler and validator.
3. Imperator authorization decisions.
4. Risk and evidence enforcement.
5. Remaining institutional decisions.
6. Execution receipt linkage.

The governing division remains: cognition explains the decision; machinery proves who made it, under what authority, from which evidence, at what time, and what the decision was permitted to cause.
