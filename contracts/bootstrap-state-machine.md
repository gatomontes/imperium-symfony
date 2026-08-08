---
title: Bootstrap State Machine Contract
status: current-theoretical-contract
scope: initial-bootstrap-only
inherits:
  - /imperium-doctrine.md
  - /contracts/bootstrap-manifest.md
  - /contracts/runtime-concurrency-replay.md
---

# Bootstrap State Machine Contract

## Purpose

This contract defines the deterministic state machine by which MasterMason converts one Launcher-verified Bootstrap Manifest into one ready primordial Imperium instance.

It governs initial bootstrap only. Ordinary spawning, Office reactivation, succession, upgrade, and disaster recovery require separate state machines and may not enter through this contract.

The exact serialized transition table implementing this contract must be pinned as `primordial.bootstrap_machine` in the Bootstrap Manifest. Its failure cleanup and retry actions must be pinned separately as `primordial.bootstrap_recovery_machine` under the [Bootstrap Forward-Recovery Contract](bootstrap-forward-recovery.md). Prose is explanatory; only those pinned machine-readable predicates and transitions are executable. Reservation, commission, idempotency, generation, replay-ledger, and Charter-pinning semantics are inherited unchanged from the shared [Runtime Concurrency and Replay Primitive Contract](runtime-concurrency-replay.md).

## Fixed bindings

Before the machine begins, the Launcher must supply:

- one verified `manifest_id`
- its exact `charter_generation`
- one new `instance_id`
- the complete verified artifact set pinned by that Manifest
- the authenticated launch-time and revocation snapshot

These values are immutable for the transaction. A missing or changed binding refuses the transition.

## States

```text
UNINITIALIZED
→ MANIFEST_BOUND
→ CONSCRIPTION_ACTIVE
→ PROVISIONAL_RECRUITER_BOUND
→ ORDINARY_RECRUITER_BOUND
→ TRIAD_ASSEMBLED
→ OFFICES_ACTIVE
→ TRIAD_BOUND_INACTIVE
→ ROUTES_VERIFIED
→ READY
```

No state may be skipped, inferred, or entered from an undeclared predecessor. `READY` is the sole state in which Secretariat may be exposed to the Operator.

A failed transition does not create another success state. It produces a durable failure event while the machine remains at the last durably completed checkpoint. All cleanup and retry are governed by the pinned forward-recovery machine; no transition may improvise rollback.

## Transition table

### T01 — Bind manifest

- From: `UNINITIALIZED`
- Input: Launcher verification receipt, Manifest, immutable instance identifier
- Predicates:
  - receipt authenticates the exact `manifest_id`, `charter_generation`, artifact-set digest, Launcher digest, and MasterMason digest
  - no persistent state exists for `instance_id`
  - MasterMason is the exact implementation pinned by the Manifest
- Action: create the bootstrap transaction and bind all fixed values
- Output: immutable bootstrap-transaction record
- To: `MANIFEST_BOUND`
- Failure codes: `B01_INVALID_LAUNCH_RECEIPT`, `B02_INSTANCE_EXISTS`, `B03_IMPLEMENTATION_MISMATCH`
- Retry: permitted only with the identical inputs after an external defect is corrected; changed inputs constitute a new launch attempt and new `instance_id`

### T02 — Activate Conscription

- From: `MANIFEST_BOUND`
- Input: pinned Conscription definition
- Predicates:
  - definition digest and version match the Manifest
  - definition belongs to the bound Charter generation
  - no Conscription runtime exists for the instance
- Action: create Conscription runtime in inactive-unoccupied mode, then activate only its mechanical interface
- Output: Conscription runtime identity and activation record
- To: `CONSCRIPTION_ACTIVE`
- Failure codes: `B10_CONSCRIPTION_MISMATCH`, `B11_CONSCRIPTION_EXISTS`, `B12_CONSCRIPTION_ACTIVATION_FAILED`
- Retry: same transition from `MANIFEST_BOUND`; the failed attempt must not leave an addressable Conscription runtime

### T03 — Mechanically bind provisional Recruiter

- From: `CONSCRIPTION_ACTIVE`
- Input: pinned Recruiter Seat, provisional Recruiter Profile, provisional substrate, and mechanical-bootstrap declaration
- Predicates:
  - all versions and digests match the Manifest
  - Recruiter Seat is vacant and reserved by this bootstrap transaction
  - provisional Profile approval/current-active attestations are valid for the bound Charter generation
  - substrate and installation procedure match the pinned compatibility declaration
  - the bootstrap declaration targets only this Recruiter Seat and provisional Profile
- Action: instantiate the substrate, install the exact provisional Profile, run the pinned mechanical conformance checks, seal the manifestation, bind it, and activate it with succession-only authority
- Output: provisional Recruiter manifestation identity, conformance record, binding record, occupancy generation, and authority-limitation record
- To: `PROVISIONAL_RECRUITER_BOUND`
- Failure codes: `B20_RECRUITER_ARTIFACT_MISMATCH`, `B21_RECRUITER_SEAT_UNAVAILABLE`, `B22_RECRUITER_ATTESTATION_INVALID`, `B23_RECRUITER_CONFORMANCE_FAILED`, `B24_RECRUITER_BINDING_FAILED`
- Retry: same transition from `CONSCRIPTION_ACTIVE`; no failed candidate identity or Seat reservation may be reused unless the pinned machine explicitly marks it reusable

### T04 — Install ordinary Recruiter successor

- From: `PROVISIONAL_RECRUITER_BOUND`
- Input: one single-use succession commission containing the pinned ordinary Recruiter Profile, substrate, resident Seat, and qualification contract
- Predicates:
  - provisional Recruiter occupancy remains identical to T03 output
  - its authority is limited to this exact succession commission
  - the commission, ordinary Profile, and substrate match the Manifest and bound Charter generation
  - the commission has not been consumed
  - no Secretary or Rector commission exists
- Action: provisional Recruiter constructs and qualifies one distinct ordinary Recruiter; MasterMason verifies the succession packet, atomically retires and vacates the provisional manifestation, and binds the successor to the same resident Recruiter Seat
- Output: retired provisional identity and provenance record; ordinary Recruiter identity, qualification packet, binding record, and new occupancy generation
- To: `ORDINARY_RECRUITER_BOUND`
- Failure codes: `B30_PROVISIONAL_RECRUITER_CHANGED`, `B31_SUCCESSION_COMMISSION_MISMATCH`, `B32_SUCCESSOR_QUALIFICATION_FAILED`, `B33_SUCCESSION_PACKET_INVALID`, `B34_SUCCESSION_SWAP_FAILED`
- Retry: same transition from `PROVISIONAL_RECRUITER_BOUND` with a new single-use commission; a failed successor identity may not be reused

### T05 — Assemble Secretary and Rector

- From: `ORDINARY_RECRUITER_BOUND`
- Input: two single-use commissions containing the pinned Secretary and Rector Seats, Profiles, substrates, and qualification contracts
- Predicates:
  - ordinary Recruiter occupancy remains identical to T04 output
  - both commissions match the Manifest and bound Charter generation
  - both target Seats are vacant and reserved by this transaction
  - neither commission has been consumed
- Action: Conscription constructs and qualifies both manifestations; MasterMason verifies both delivery packets
- Output: sealed Secretary and Rector delivery packets plus qualification records
- To: `TRIAD_ASSEMBLED`
- Failure codes: `B40_RECRUITER_CHANGED`, `B41_COMMISSION_MISMATCH`, `B42_TARGET_UNAVAILABLE`, `B43_ASSEMBLY_FAILED`, `B44_QUALIFICATION_FAILED`, `B45_DELIVERY_PACKET_INVALID`
- Retry: same transition from `ORDINARY_RECRUITER_BOUND` with new single-use commission identifiers; success requires both valid packets from the same attempt

### T06 — Activate Secretariat and Castellan

- From: `TRIAD_ASSEMBLED`
- Input: pinned Secretariat and Castellan definitions
- Predicates:
  - both definitions match the Manifest and bound Charter generation
  - verified Secretary and Rector packets remain valid and unexpired
  - neither Office runtime exists for the instance
- Action: create both Office runtimes in active-but-unavailable mode
- Output: Secretariat and Castellan runtime identities and activation records
- To: `OFFICES_ACTIVE`
- Failure codes: `B50_OFFICE_MISMATCH`, `B51_PACKET_STALE`, `B52_OFFICE_EXISTS`, `B53_OFFICE_ACTIVATION_FAILED`
- Retry: same transition from `TRIAD_ASSEMBLED`; failure must not leave either Office addressable

### T07 — Bind triad inactive

- From: `OFFICES_ACTIVE`
- Input: verified Secretary and Rector delivery packets and exact resident Seats
- Predicates:
  - Office runtime identities match T06 output
  - delivery packets and Seat reservations remain valid
  - both Seats are vacant
  - Profile and Charter generations remain pinned and unchanged
- Action: bind Secretary and Rector as inactive occupants in one bootstrap transaction
- Output: both binding records and occupancy generations
- To: `TRIAD_BOUND_INACTIVE`
- Failure codes: `B60_RUNTIME_CHANGED`, `B61_PACKET_OR_RESERVATION_INVALID`, `B62_SEAT_NOT_VACANT`, `B63_BINDING_FAILED`
- Retry: same transition from `OFFICES_ACTIVE`; partial binding is not a completed state and neither occupant may become active

### T08 — Verify routes

- From: `TRIAD_BOUND_INACTIVE`
- Input: pinned primordial route artifact and both occupancy generations
- Predicates:
  - route version and digest match the Manifest
  - endpoints resolve only to the bound Secretary and Rector occupants
  - no undeclared route is open
  - bidirectional synthetic route probes satisfy the pinned checks without delivering Office work
- Action: configure the pinned routes while keeping both occupants inactive
- Output: route-configuration digest and probe record
- To: `ROUTES_VERIFIED`
- Failure codes: `B70_ROUTE_MISMATCH`, `B71_ENDPOINT_MISMATCH`, `B72_UNDECLARED_ROUTE`, `B73_ROUTE_PROBE_FAILED`
- Retry: same transition from `TRIAD_BOUND_INACTIVE`; failed route configuration must remain closed

### T09 — Commit readiness

- From: `ROUTES_VERIFIED`
- Input: complete transition receipts T01–T08
- Predicates:
  - every receipt belongs to the same transaction, Manifest, Charter generation, and instance
  - Recruiter, Secretary, Rector, all three Offices, bindings, and routes still match their recorded generations
  - no failure, suspension, expiry, revocation, or intervening mutation is unresolved
- Action: atomically mark the primordial structure ready, activate Secretary and Rector, enable the verified routes, and expose Secretariat
- Output: signed or integrity-protected readiness record and ready-generation identifier
- To: `READY`
- Failure codes: `B80_RECEIPT_CHAIN_INVALID`, `B81_PRIMORDIAL_STATE_CHANGED`, `B82_UNRESOLVED_FAILURE`, `B83_READINESS_COMMIT_FAILED`
- Retry: same transition from `ROUTES_VERIFIED` only if all predicates are freshly revalidated; otherwise refuse and require the separately governed recovery path

## Global invariants

Every transition must:

- accept only the exact declared predecessor state
- compare expected and observed state generations before mutation
- carry `transaction_id`, `instance_id`, `manifest_id`, and `charter_generation`
- emit one durable attributable success or failure event
- be idempotent for the same transition identifier and identical inputs
- reject reused single-use commissions and conflicting transition identifiers
- fail closed before exposing any partially created constituent
- refuse any artifact, route, Profile, substrate, or implementation not pinned by the Manifest

MasterMason may execute only the action named by the admitted transition. It may not choose a later state, repair an input, substitute an artifact, reinterpret a predicate, or convert a failure into success.

## Failure boundary

Before `READY`, no Operator work may enter Secretariat, Castellan, or Conscription. Failed resources remain governed by the exact cleanup or forward-recovery rule attached to the pinned machine; they may not be silently reused.

Rollback is forbidden. Cleanup, crash reconciliation, resource disposition, reservation release, fresh-identity requirements, and retry admission are governed by the pinned [Bootstrap Forward-Recovery Contract](bootstrap-forward-recovery.md). Disaster recovery remains a separate required contract. Absence or failure of the pinned recovery machine keeps the affected retry path unavailable; it does not authorize MasterMason to improvise.

## Completion

Bootstrap succeeds only when T09 durably commits `READY`. Process liveness, individual Office activation, Seat binding, or successful route probes are not bootstrap completion.

> No READY record, no operational Imperium.
