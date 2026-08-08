---
title: Bootstrap Forward-Recovery Contract
status: current-theoretical-contract
scope: initial-bootstrap-failure-and-retry
inherits:
  - /imperium-doctrine.md
  - /contracts/bootstrap-manifest.md
  - /contracts/bootstrap-state-machine.md
  - /contracts/runtime-concurrency-replay.md
---

# Bootstrap Forward-Recovery Contract

## Purpose

This contract governs cleanup and retry after a failed initial-bootstrap transition. Bootstrap recovers forward from the last durably completed state. It never pretends that created identities, records, commissions, reservations, or runtime effects did not exist.

The exact serialized recovery table implementing this contract must be pinned as `primordial.bootstrap_recovery_machine` in the Bootstrap Manifest. Prose is explanatory; MasterMason may execute only pinned machine-readable recovery actions. Recovery uses the shared primitives without resetting, transferring, or weakening their replay history.

## Durable checkpoint rule

Each successful bootstrap transition atomically commits:

- predecessor and successor state
- transition identifier and attempt number
- expected and observed resource generations
- all created identities, commissions, reservations, packets, bindings, and runtime effects
- the transition receipt and integrity digest

A successor state exists only after that checkpoint is durable. A crash before commit leaves the machine at the preceding checkpoint and requires reconciliation of the incomplete attempt before retry. A crash after commit resumes from the committed successor state. Event order alone may not infer state.

## Failure record

Every failed attempt must durably record:

- transaction, instance, Manifest, Charter generation, transition, and attempt identifiers
- last valid checkpoint
- exact failure code and observed resource generations
- every resource created or touched by the attempt
- whether each effect is absent, active, inactive, quarantined, retired, released, expired, or unresolved
- the sole admitted cleanup or recovery action
- whether retry is blocked or admitted

An unresolved effect blocks retry. MasterMason may not convert, suppress, delete, or relabel a failure record as success.

## Resource disposition

Forward recovery uses these exact dispositions:

- `QUARANTINED`: isolated, non-addressable, unable to receive work, bind, qualify, or be reused
- `RETIRED_FAILED`: permanently barred from activation or reuse; identity and provenance retained
- `RELEASED`: reservation relinquished by the owning transaction after expected-generation verification
- `PRESERVED`: valid resource from a completed checkpoint retained unchanged
- `UNRESOLVED`: observed effect lacks a verified final disposition and blocks retry

A failed manifestation candidate or successor is quarantined and then retired as `RETIRED_FAILED`. Its manifestation identity, packet, qualification record, and commission may never be reused. A failed Office runtime is made non-addressable and retired or removed only as the pinned recovery action declares; its runtime identity may never masquerade as a fresh creation.

Records are append-only. Cleanup changes resource state; it does not erase provenance.

## Commissions and identities

Each retry uses:

- a fresh transition-attempt identifier
- fresh single-use commission identifiers
- fresh candidate manifestation and runtime identities
- the same immutable bootstrap transaction, instance, Manifest, and Charter-generation bindings
- newly observed expected-state generations

Consumed, expired, failed, quarantined, or retired commissions and identities are never reset. Identical retry inputs do not imply identity reuse.

## Reservations

Every Seat or runtime reservation has:

- reservation identifier
- owning bootstrap transaction and transition attempt
- exact target identity
- expected occupancy or runtime generation
- issued and expiry times
- status: `HELD`, `CONSUMED`, `RELEASED`, or `EXPIRED`

MasterMason may consume, renew, release, or recognize expiry only through a pinned transition. Release requires proof that no admitted binding or activation consumed the reservation. A stale or ambiguously consumed reservation becomes `UNRESOLVED` and blocks retry.

## Reconciliation before retry

Before retrying a failed transition, MasterMason must:

1. load the last durable checkpoint and failure record
2. compare every recorded expected generation with observed persistent and runtime state
3. classify every partial effect under this contract
4. quarantine and retire failed candidates
5. close and disposition failed routes or runtimes as declared
6. consume, release, or expire reservations and commissions
7. prove that all preserved resources still match the checkpoint
8. issue a recovery receipt
9. admit retry only when no `UNRESOLVED` effect remains

If observed state cannot be reconciled mechanically, bootstrap halts and enters the separately governed disaster-recovery path. MasterMason may not guess.

## Transition-specific recovery

- T01 failure creates no instance state unless the bootstrap-transaction checkpoint commits.
- T02 failure must leave no addressable Conscription runtime. Any created runtime identity is dispositioned before T02 retry.
- T03 failure retires the provisional candidate, releases the Recruiter Seat reservation if unconsumed, and retries with a fresh candidate identity.
- T04 failure preserves the valid provisional Recruiter, retires the failed successor, proves the Recruiter Seat occupancy generation unchanged, and retries with a fresh succession commission and successor identity. A partial swap is `UNRESOLVED` until the pinned reconciliation proves exactly one occupant.
- T05 failure preserves the ordinary Recruiter, retires both Secretary and Rector candidates from that attempt even if one qualified, releases both target reservations, and retries the pair with fresh commissions and identities.
- T06 failure preserves the verified Secretary and Rector packets only while valid and unexpired. Any partial Office runtime is non-addressable and dispositioned before retry; stale packets force return to the separately declared reassembly edge.
- T07 failure keeps both occupants inactive. Any partial binding is reconciled and vacated; both delivery candidates from that attempt are retired unless the pinned table explicitly proves neither binding occurred and both packets remain reusable. The default is fresh reassembly.
- T08 failure closes every primordial route touched by the attempt. Bound occupants remain inactive and preserved only if endpoint and occupancy generations are unchanged.
- T09 failure leaves Secretariat unexposed and all occupants inactive. Retry is admitted only after complete fresh revalidation; any partial exposure or activation is `UNRESOLVED` until mechanically contained.

No recovery action may retreat the durable state label. When a valid earlier product must be replaced, the recovery receipt invalidates that product and follows an explicit forward reassembly edge declared by the pinned machine.

## Retry admission

Retry is admitted only when:

- the preceding durable checkpoint remains valid
- all partial effects have final dispositions
- all required reservations are freshly held
- every commission and candidate identity is fresh where required
- Charter generation, Manifest, and pinned artifacts remain unchanged
- no suspension, revocation, expiry, or conflicting mutation is unresolved
- the recovery receipt authorizes the exact next transition attempt

Otherwise retry is refused.

## Boundaries

This contract does not govern ordinary spawning, orderly succession after bootstrap, upgrades, Charter replacement, or disaster recovery. It does not authorize rollback, record deletion, identity reuse, artifact substitution, or interpretation.

## Invariant

> Preserve valid checkpoints. Quarantine and retire failed effects. Retry forward with fresh authority and identity. Never erase history to simulate rollback.
