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

The Seneschal may continue planning, ask exactly one question, request authorization, draft a Mission Plan, or refuse. An authorization request is not authorization, and a drafted Mission Plan is not approval.

## `register-curial-submission`

Bind a Curialis submission to its author, Seat, mandate, evidence, uncertainty, dossier version, and active deliberation.

## `record-seneschal-disposition`

Preserve an already-rendered Seneschal disposition, its evidence basis, dissent, mandate, affected objects, and required next institutional path.

## `track-dependency`

Maintain mechanical state for authorized commissions, Office dependencies, pending returns, deadlines, stop conditions, and unresolved blockers.

## `assemble-succession-packet`

Assemble the exact mission-state references, pending decisions, commitments, authorizations, unresolved disagreements, suitability demand, and transfer conditions selected by competent cognition. Assembly grants no appointment or transfer authority.

## `bind-seat-transfer`

Apply an already-authorized atomic Seneschal Seat transition only after exact identity, qualification, generation, suspension, and expected-state checks succeed.

## `package-authorized-delivery`

Package and route an already-authorized Curial artifact without revising its content or destination.
