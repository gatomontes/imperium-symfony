---
title: Bootstrap Forward-Recovery Contract
status: current-theoretical-contract
scope: initial-bootstrap-only
inherits:
  - /contracts/bootstrap-state-machine.md
  - /contracts/runtime-concurrency-replay.md
---

# Bootstrap Forward-Recovery Contract

Bootstrap never rolls back a durable success. A failed transition records failure, preserves the last successful state, disposes only resources created by the failed attempt, and retries only through the recovery action pinned in `primordial.bootstrap_recovery_machine`.

## Recovery rules

- Fixed transaction, manifest, Charter, implementation, and artifact bindings never change during recovery.
- Failed candidate identities and consumed single-use commissions are not reused.
- Seat reservations are released or renewed only by their declared recovery action.
- A partial Seneschal–Chamberlain assembly or binding is never promoted; both members require a fresh same-attempt result.
- A failed Curia activation must leave no addressable runtime.
- Failure while attaching the Secretary cannot mutate or retire the already-bound governing pair.
- Route failure leaves every Curian route closed and every occupant inactive.
- Readiness failure leaves Curia unaddressable; recovery must freshly revalidate T01–T09 receipts before retrying T10.
- Any conflict with persisted runtime identity or occupancy generation refuses automated recovery and requires the separately governed disaster-recovery path.

## Checkpoints

The resumable checkpoints are exactly the successful states in the bootstrap machine. Recovery may retry only the next declared transition. It may not skip forward, synthesize a success receipt, alter a prior receipt, or reinterpret an invalid artifact.

The recovery machine is itself pinned and verified. If it is absent, stale, or invalid, the affected retry path remains unavailable.
