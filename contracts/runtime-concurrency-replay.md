---
title: Runtime Concurrency and Replay Primitive Contract
status: current-theoretical-contract
scope: shared-runtime-transition-primitives
inherits:
  - /imperium-doctrine.md
---

# Runtime Concurrency and Replay Primitive Contract

## Purpose

This contract defines the common mechanical primitives that prevent concurrent, stale, duplicated, or replayed runtime transitions from producing conflicting Imperium state.

Bootstrap and ordinary spawning use these same primitives through separate state machines. This contract does not merge their workflows, authorize a transition, or supply recovery policy. Each governing state machine must declare when it acquires, validates, consumes, releases, or expires the shared primitives.

The exact canonical encoding and validation implementation must be pinned by the applicable authority artifact. Prose is explanatory; MasterMason may not infer missing semantics.

## Immutable transaction binding

Every governed transaction binds its transaction kind, instance, Charter generation, governing-machine identifier and digest, authority artifact, requester identity and occupancy generation or bootstrap declaration, target Office and Seat generations, and exact Profile identity and lifecycle generation. Those bindings cannot change in flight. Any change makes the transaction stale; it cannot be silently rebased.

## Seat reservation token

A Seat reservation contains an unguessable identifier; owning transaction and attempt; exact instance, Office, and Seat; expected occupancy generation; pinned Charter and Profile generations; issue and expiry times; and state (`HELD`, `CONSUMED`, `RELEASED`, or `EXPIRED`).

Acquisition is an atomic compare-and-set against the expected occupancy generation. At most one unexpired `HELD` reservation may exist for a Seat. It is non-transferable and consumable only by its owning transition while the Seat remains vacant at the expected generation. Reservation prevents a competing claim; it does not authorize spawning or binding.

## Single-use commission

Every Conscription commission contains an unguessable identifier; owning transaction, attempt, and reservation; exact requester or bootstrap authority; exact target Seat, Profile, substrate, and qualification contract; pinned Charter generation and machine digest; issue and expiry times; and state (`ISSUED`, `ACCEPTED`, `CONSUMED`, `FAILED`, `EXPIRED`, or `REVOKED`).

Acceptance and consumption are atomic. One commission may produce at most one admissible delivery packet. Timeout, retry, or duplicate delivery never resets it. Retry requires a fresh commission and, unless the governing recovery contract explicitly preserves the candidate, a fresh manifestation identity.

## Idempotency record

Every mutating request carries an `idempotency_key` unique within the instance and governing machine. MasterMason durably records the canonical request digest, first admitted attempt, resulting receipt, current or final disposition, and repeated observations.

The same key and request digest returns the existing receipt without mutation. The same key with a different digest fails as a conflict. A new key cannot make a consumed commission, reservation, packet, or transition reusable.

## Expected-state checks

Immediately before commit, every mutation atomically compares declared expectations with observed Charter and machine generations; requester occupancy; target Office and Seat generations; Profile lifecycle/current-active generation; reservation and commission states; and candidate packet identity and validity.

Earlier validation is insufficient. Any mismatch rejects the mutation as stale and invokes only the failure or recovery edge declared by the governing machine.

## Replay ledger

MasterMason maintains an append-only ledger for transition identifiers, idempotency keys, reservations, commissions, delivery packets, and manifestation identities. It distinguishes first admission, exact duplicate observation, conflicting reuse, consumption, expiry, failure, revocation, quarantine, and retirement.

Replay history must remain available as long as any referenced instance record can authorize or prove runtime state. Missing or ambiguous required history fails closed and enters separately governed recovery.

## Charter-generation pinning

A transaction executes entirely under one Charter generation. Supersession blocks each uncommitted transition pinned to the former generation. No in-flight transaction may adopt a newer generation. It must receive a declared terminal or recovery disposition; continued work requires a new request admitted under the new generation.

## Commit order

For every transition attempt, MasterMason must:

1. admit one canonical request and idempotency key;
2. verify the pinned Charter and machine generation;
3. acquire the target reservation by expected-generation compare-and-set;
4. issue or validate the single-use commission when construction is required;
5. record intermediate receipts without exposing the target;
6. revalidate every expected generation immediately before mutation;
7. atomically commit the declared mutation and consume its reservation and commission; and
8. append the final receipt and replay dispositions.

Failure follows only the governing machine's declared edge. This contract supplies no generic rollback.

## Separate machines

The bootstrap machine may use these primitives only for the Manifest-pinned primordial transaction. The ordinary-spawning machine may use them only after `CURIA_READY` and for an attributable request from an authorized occupied Office.

Neither machine may enter the other's states, reuse its authority artifact, consume its reservations or commissions, or treat shared primitive semantics as shared workflow authority.

## Invariants

- one Seat, at most one admitted claimant and occupant at a generation;
- one commission, at most one admissible delivery packet;
- one idempotency key, one canonical request and result lineage;
- one transaction, one Charter generation and governing machine;
- stale state never becomes current through retry;
- duplicate observation never produces duplicate mutation; and
- conflicting reuse is evidence, not a fresh request.

> Shared primitives make concurrency and replay mechanically uniform. Separate state machines preserve distinct authority, readiness, and recovery semantics.
