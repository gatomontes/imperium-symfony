---
title: Curia Mechanics
status: current-theoretical-contract
scope: office-mechanics
inherits:
  - /imperium-doctrine.md
  - ./doctrine.md
---

# Curia Mechanics

These mechanics preserve, correlate, version, and route Curia's work. They do not interpret intent, weigh evidence, determine sufficiency, choose personnel, render executive dispositions, approve authorization objects, or create authority.

## `open-planning-proceeding`

Create the immutable proceeding identity and bind the authenticated Imperator, instance, initial intent, supplied material, and provenance.

The development CLI authority surface is fixed to `imperator-development-root`; it does not accept a caller-selected Imperator identity. An identical instance, Manifest, Curian occupancy set, authority, and request deterministically replay the same proceeding identity. A Seneschal succession therefore cannot silently inherit or overwrite the predecessor's disposition for a replayed request.

Opening performs the following bounded sequence:

1. prove `CURIA_READY` and active Seneschal, Chamberlain, and Secretary occupancies;
2. have Chamberlain open the proceeding mechanically;
3. have Isolde preserve the exact Imperator request;
4. obtain one contract-valid Seneschal cognition result;
5. atomically persist the complete proceeding record.

The Seneschal result may admit planning, ask exactly one clarification question, or refuse. It must enumerate immediate resource demands and whether they require Imperator authorization. It cannot itself claim that authorization has been granted.

## `version-mission-dossier`

Append an attributable dossier version without mutating prior Operator statements, Curial findings, unknowns, decisions, or authorization objects.

## `register-active-question`

Bind one Seneschal-authorized question to the proceeding, dossier version, question cursor, intended recipient, and expected response contract.

## `record-operator-answer`

Preserve the exact answer, provenance, correlation, and response disposition without interpreting its substance.

The development command `imperium:curia:respond` restores an existing proceeding and appends one immutable turn. Each turn contains the exact Imperator response, Isolde's recording disposition, Chamberlain's restoration disposition, the Seneschal's next bounded disposition, declared resource demands, and authorization status.

Turns are append-only and monotonically sequenced. A stable `response_id` is an idempotency identity: replay returns the existing turn without invoking cognition again. A concurrent intervening turn invalidates a stale deliberation result rather than silently reordering the minutes.

The Seneschal may continue planning, ask exactly one question, request authorization, draft a Mission Plan, or refuse. The primary disposition and authorization flag are orthogonal: a draft or continuing-planning disposition may identify unresolved authorization demands. `AUTHORIZATION_REQUIRED` is used when the demand itself is the controlling disposition. An authorization request is not authorization, and a drafted Mission Plan is not approval.

A `MISSION_PLAN_DRAFTED` disposition must carry a structured Mission Plan containing objective, scope, deliverables, constraints, required inputs, personnel requirements, tool requirements, data requirements, Office participation, and stop conditions. Prose alone is not commissionable. Approval and authorization records against a legacy or incomplete prose draft remain preserved evidence but cannot produce commissioning readiness.

## `register-curial-submission`

Bind a Curialis submission to its author, Seat, mandate, evidence, uncertainty, dossier version, and active deliberation.

## `record-seneschal-disposition`

Preserve an already-rendered Seneschal disposition, its evidence basis, dissent, mandate, affected objects, and required next institutional path.

## `record-imperator-plan-approval`

Bind one explicit Imperator approval to the exact drafted-plan turn and record digest. Approval grants neither resource nor execution authority.

## `record-imperator-resource-authorization`

Bind one explicit Imperator authorization to an exact subset of the resource demands declared by the referenced drafted-plan turn. Undeclared resources are refused. Authorization does not approve the plan and does not itself authorize execution.

Curia becomes commissioning-ready only when the exact plan is approved and all its declared resource demands are covered by valid authorization records. Commissioning readiness permits Curia to prepare the next governed execution step; it is not execution authority.

## `issue-planning-commissions`

From one commissioning-ready structured Mission Plan, mechanically seal the exact planning-only commissions declared by the plan and covered by its approval and resource-authorization records. The current vertical slice issues:

- a Guildhall commission for profession determination, personnel disposition, and its bounded Garrison inventory inquiry; and
- an Armory commission for passive-methodology, checklist, and tooling disposition.

Storage and ordinary drafting support remain mechanical allocations rather than cognitive Office commissions. Issuance records each packet as `ISSUED_PENDING_RECIPIENT` while the target Office runtime is unavailable. Issuance grants no construction, recruitment, deployment, tool activation, target access, credential use, assessment work, or execution authority.

### `deliver-planning-commissions`

Route each exact sealed planning commission into its declared Office inbox. Delivery records `DELIVERED_PENDING_RECIPIENT`; it neither speaks for the recipient nor records acceptance. Curia cannot accept its own commission on Guildhall's or Armory's behalf. Recipient acceptance requires the exact target Office runtime and an attributable authorized occupant or service. Delivery remains planning-only and grants no execution authority.

## `track-dependency`

Maintain mechanical state for authorized commissions, Office dependencies, pending returns, deadlines, stop conditions, and unresolved blockers.

When Guildhall returns a final Personnel Disposition proving exact unresolved personnel gaps and routes matching non-authorizing Foundry demands, Curia validates the complete Garrison-response and demand lineage and presents one bounded construction-authorization request to Imperator. The request identifies every exact demand and asks only for Persona construction. Recording or presenting the request grants no construction, Persona selection, spawning, Seat binding, or execution authority.

## `record-imperator-construction-authorization`

Bind one explicit Imperator decision to the exact construction request, request digest, and complete set of Foundry demand digests. Authorization grants Foundry permission to construct one Persona candidate for each exact demand. It grants no Persona selection, spawning, Seat binding, or mission-execution authority. The immutable authorization act does not mutate the request or its demands; subsequent Foundry work must cite and validate the act.

## `request-subordinate-construction`

When occupied authorship Offices return sealed subordinate-requirement resolutions, Curia may present them together to Imperator as one authorization request. Each resolution remains an independent, digest-bound Office act: Curia may neither merge its specializations nor reinterpret its rationale. The request asks only for construction against the exact included resolutions and grants no construction, Persona selection, Profile approval, spawning, Seat binding, or execution authority.

## `authorize-subordinate-construction`

Bind one explicit Imperator decision to the exact Curial request digest and its complete ordered set of independently sealed subordinate resolutions. Authorization grants construction authority only against those exact resolutions. It grants no Persona selection, Profile approval, spawning, Seat binding, or execution authority, and it does not mutate the request or any Office resolution.

## `deliver-subordinate-construction-authorization`

Route the immutable Imperator act, its exact Curial request, and the complete digest-bound subordinate-resolution set into Foundry's authorization inbox. Delivery records authority as present but not exercisable until attributable Foundry acceptance. It neither mutates the source acts nor grants Persona selection, Profile approval, spawning, Seat binding, or execution authority.

## `deliver-construction-authorization`

Route the exact immutable construction-authorization act and its complete demand references to Foundry's authorization inbox. Delivery revalidates the act and every demand but does not mutate the original demands or claim Foundry acceptance. Only the exact construction authority crosses the boundary; Persona selection, spawning, Seat binding, and execution authority remain withheld.

## `assemble-succession-packet`

Assemble the exact mission-state references, pending decisions, commitments, authorizations, unresolved disagreements, suitability demand, and transfer conditions selected by competent cognition. Assembly grants no appointment or transfer authority.

## `bind-seat-transfer`

Apply an already-authorized atomic Seneschal Seat transition only after exact identity, qualification, generation, suspension, and expected-state checks succeed.

## `package-authorized-delivery`

Package and route an already-authorized Curial artifact without revising its content or destination.

## Resource-demand normalization

Mission-plan prose is explanatory, not a routing protocol. Before issuing planning commissions, Curia resolves explicit office names and resource categories case-insensitively from the authorized demands. A destination remains required, but its position or prose prefix is not authoritative. This prevents cognitively equivalent replay wording from changing the mechanical disposition.
