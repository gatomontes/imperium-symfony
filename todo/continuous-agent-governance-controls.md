# Continuous Agent Governance Controls

## Purpose

Imperium must produce governance evidence as a consequence of operating, not as a report reconstructed after the mission. External action increases the required control strength, but governance begins before an operative can act: recommendations, planning, personnel selection, evidence handling, and authorization can already affect consequential human decisions.

This work extends the canonical Decision Record TODO. It addresses runtime identity, continuous evidence, measurement, revocation, containment, and incident handling around the decision-to-execution chain.

## Governance proportional to consequence

- [ ] Define governance tiers for advisory cognition, internal mission decisions, resource use, delegated external action, and irreversible external effects.
- [ ] Require provenance, disclosure, evidence discipline, data controls, and accountability for advisory outputs even when no external tool is available.
- [ ] Increase authorization, isolation, telemetry, revalidation, and revocation requirements as consequence and autonomy increase.
- [ ] Prevent capability possession from being treated as authority to exercise that capability.
- [ ] Record the governance tier and consequence classification on every Profile, authority lease, sortie demand, and execution receipt.

## Runtime principal separation

Do not collapse accountability, institutional identity, cognitive identity, runtime execution, human representation, or authorization into one blended "agent identity."

- [ ] Define and bind the distinct principals involved in an operation:
  - accountable human or institutional owner;
  - competent Office and occupied Seat;
  - Persona identity;
  - mission Profile and its limitations;
  - Officer/runtime process identity;
  - assembled Manifestation identity;
  - delegating or represented human authority, when applicable;
  - disposable sortie identity for external execution.
- [ ] Require every runtime principal to have an independently attributable identifier and lifecycle state.
- [ ] Prohibit shared credentials or blended accounts across Manifestations, Offices, instances, or sorties.
- [ ] Bind credentials to the minimum runtime principal, tool, scope, destination, duration, and authority lease required.
- [ ] Make every external effect reconstructable from the sortie through the Manifestation, Seat, Profile, Persona, authority act, and accountable owner.

## Continuous evidence chain

- [ ] Define a canonical event envelope for governance-relevant transitions.
- [ ] Emit attributable, timestamped, digest-bound events during the work rather than generating an audit narrative afterward.
- [ ] Link mission input, Curian deliberation, evidence, decision, approval, authority, custody, assembly, deployment, execution, result, reassessment, and retirement.
- [ ] Distinguish observations, inferences, recommendations, decisions, authorizations, attempted actions, completed effects, and returned evidence.
- [ ] Preserve exact tool inputs, policy evaluations, credential lease identity, target, output digest, provider receipt, failure, and retry history for every sortie.
- [ ] Make missing evidence fail closed whenever the missing link prevents proof of authority, scope, target, or result.
- [ ] Provide a mechanical reconstruction view for one mission without requiring a reviewer to search unrelated application logs.

## Runtime authorization leases

- [ ] Define an execution-time authority lease binding:
  - source decision and approver;
  - exact permitted capability;
  - tool and credential scope;
  - target and data boundary;
  - policy and evidence versions;
  - limitations and stop conditions;
  - issuance, activation, expiry, and freshness;
  - revalidation and invalidation conditions;
  - revocation authority.
- [ ] Revalidate the lease at the Iron Gate immediately before external execution.
- [ ] Reject stale, superseded, expired, revoked, scope-expanded, target-changed, or evidence-invalidated leases.
- [ ] Consume or close single-use leases after one bounded execution attempt unless retry authority is explicit.
- [ ] Bind the execution receipt to the exact lease and decision lineage.

## Revocation and kill switch

- [ ] Implement an immediate runtime revocation path that does not depend on a software release or agent cooperation.
- [ ] Support at least `RESTRICT`, `INTERRUPT`, `REAUTHORIZE`, and `RETIRE` dispositions with attributable authority.
- [ ] Allow revocation at the instance, Manifestation, Profile, Seat, sortie, tool, credential, destination, and capability levels.
- [ ] Make revocation close active leases and prevent new ones from being exercised.
- [ ] Define how in-flight external operations are cancelled, quarantined, allowed to finish safely, or escalated when cancellation is impossible.
- [ ] Preserve the revocation reason, actor, authority basis, affected scope, effective time, acknowledgements, and any residual exposure.
- [ ] Test kill-switch propagation independently of cognitive components.

## Telemetry and continuous measurement

- [ ] Define control telemetry for authorization checks, policy denials, scope violations, evidence gaps, retries, tool failures, suspicious inputs, and revocations.
- [ ] Measure intended action versus attempted action versus actual external effect.
- [ ] Detect use of unauthorized tools, credentials, destinations, data classes, or execution paths.
- [ ] Detect abnormal identity, capability, credential, and sortie proliferation within one instance.
- [ ] Detect evidence-lineage breaks and version drift between decision, authorization, and execution.
- [ ] Feed material telemetry back into Curia for reassessment without granting telemetry any decision authority.
- [ ] Define thresholds that require restriction, interruption, Senate examination, incident response, or Imperator escalation.

## Data and execution containment

- [ ] Enforce least privilege mechanically at tool, credential, data, destination, and time boundaries.
- [ ] Keep internal cognition separated from external execution through the Iron Gate and disposable sorties.
- [ ] Keep all inbound external payloads behind Lazaretto admission, provenance, sanitization, and `authority=none` treatment.
- [ ] Add explicit egress controls and data masking for sensitive or unnecessary fields.
- [ ] Prevent memory or instruction contamination from external payloads from silently changing Persona, Profile, mission scope, or authority.
- [ ] Define detection and response for prompt injection, memory poisoning, credential misuse, and compromised providers.
- [ ] Preserve version lineage for policies, Profiles, tools, schemas, and enforcement code used during each effect.

## Incident management

- [ ] Define the incident record and competent response authority for agent-originated or sortie-originated events.
- [ ] Automatically preserve relevant proceedings, evidence, leases, credentials used, execution receipts, provider responses, and telemetry when an incident is opened.
- [ ] Support containment without destroying forensic evidence or mutating sealed records.
- [ ] Require residual-exposure assessment and a named competent owner before incident closure.
- [ ] Bind remediation, reauthorization, resumption, or retirement to the incident lineage.

## Verification

- [ ] Demonstrate one advisory-only mission with complete provenance and accountability despite having no external effect.
- [ ] Demonstrate one externally acting mission from deliberation through execution receipt and retirement.
- [ ] Prove that a valid decision without a valid execution-time lease cannot cause an external effect.
- [ ] Prove that a revoked lease cannot be exercised even if the operative continues requesting execution.
- [ ] Prove that credentials cannot be reused by another Manifestation, instance, or sortie.
- [ ] Prove that a target, tool, data-scope, or evidence-version change forces revalidation or refusal.
- [ ] Prove that inbound hostile content remains evidence with no authority and cannot alter mission control state.
- [ ] Produce a complete incident reconstruction from native Imperium artifacts rather than post-hoc application-log correlation.

## Suggested implementation order

1. Runtime principal and authority-lease contracts.
2. Canonical governance event envelope.
3. Iron Gate lease revalidation and execution receipt binding.
4. Immediate revocation and kill-switch propagation.
5. Telemetry, anomaly detection, and Curia reassessment intake.
6. Incident preservation, containment, and closure lineage.

The governing standard is continuous proof: Imperium must be able to show who or what acted, under whose authority, within which boundaries, from which evidence, through which runtime principal, with what external result, and whether that authority remained valid at the moment of consequence.
