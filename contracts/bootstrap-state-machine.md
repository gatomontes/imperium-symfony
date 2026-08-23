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

MasterMason converts one Launcher-verified Bootstrap Manifest into one Curia-ready Imperium instance. The serialized machine pinned as `primordial.bootstrap_machine` is executable; this prose defines its intent and invariants.

## Fixed bindings

The verified `manifest_id`, `charter_generation`, `instance_id`, artifact-set digest, Launcher digest, and MasterMason digest are immutable for the transaction.

## States

```text
UNINITIALIZED
→ MANIFEST_BOUND
→ CONSCRIPTION_ACTIVE
→ PROVISIONAL_RECRUITER_BOUND
→ ORDINARY_RECRUITER_BOUND
→ CURIAN_CORE_ASSEMBLED
→ CURIA_ACTIVE
→ CURIAN_CORE_BOUND_INACTIVE
→ SECRETARY_BOUND_INACTIVE
→ ROUTES_VERIFIED
→ CURIA_READY
```

No state may be skipped or inferred. Before `CURIA_READY`, Curia is neither addressable nor permitted to receive mission work.

## Transitions

| ID | Transition | Required result |
|---|---|---|
| T01 | Bind manifest | Immutable transaction binding |
| T02 | Activate Conscription mechanics | Non-operator-facing Conscription runtime |
| T03 | Bind provisional Recruiter | Generation 1, succession-only authority |
| T04 | Install ordinary Recruiter | Generation 2; provisional occupant retired |
| T05 | Assemble Curian core | Same-attempt, sealed Seneschal and Chamberlain packets |
| T06 | Activate Curia | One inactive-unavailable Curia runtime with three resident Seat slots |
| T07 | Bind Curian core inactive | Seneschal and Chamberlain bound atomically in the shared runtime |
| T08 | Attach Curial Secretary | Isolde independently qualified and bound inactive under her provisional Curial limitation |
| T09 | Verify Curian routes | Declared routes resolve within the shared runtime and remain disabled; no work is delivered |
| T10 | Commit readiness | Occupants activate, routes enable, Curia becomes addressable, and `curia.imperator` becomes the operator entrypoint |

The Seneschal–Chamberlain pair is the required governing core. The Secretary Seat is constitutionally optional and is not part of the pair’s atomic assembly; this development composition nevertheless attaches Isolde before readiness.

## Global invariants

Every transition must accept only its declared predecessor, compare generations, emit a durable attributable receipt, preserve transaction and Charter bindings, reject unpinned artifacts and reused commissions, and fail closed. A failed transition remains at the last durable checkpoint and follows the pinned forward-recovery machine; rollback and improvised repair are forbidden.

T05 and T07 are atomic across the governing pair. T06 creates exactly one Curia runtime. T08 cannot alter the pair. T09 must prove exact endpoint resolution, occupancy generation, shared-runtime residence, and absence of work delivery. T10 is the only transition that may activate occupants, enable routes, or expose Curia.

> No `CURIA_READY` record, no operational Imperium.
