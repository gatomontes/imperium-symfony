# Recruiter Disaster-Recovery Contract

## Status

Current deterministic contract for restoring ordinary Conscription authority after the incumbent ordinary Recruiter is unexpectedly and irrecoverably lost.

## Jurisdiction

This procedure is not initial bootstrap, orderly Recruiter succession, or ordinary spawning. It may begin only after Imperium has reached `READY` and MasterMason has mechanically established that the resident Recruiter Seat has no recoverable incumbent.

The retired provisional Recruiter is never reactivated. Recovery creates a fresh recovery Recruiter with a new manifestation identity and explicit recovery provenance.

## Required authorization

Recovery requires one Charter-declared recovery authorization naming:

- Imperium instance and pinned Charter generation
- vacant resident Recruiter Seat and its expected generation
- last valid ordinary Recruiter occupancy record
- evidence establishing loss, termination, or irrecoverability
- exact recovery Recruiter Profile and authorized substrate digests
- exact ordinary Recruiter Profile to be used for the successor
- recovery transaction, idempotency, and expiry identifiers
- authenticated authorizing principal required by the Charter

The Charter may require Imperator, as exceptional SuperAdmin, to authorize this operation. Imperator authorization is neither construction nor execution: MasterMason alone validates and performs the declared recovery transitions.

Absent, ambiguous, stale, replayed, mismatched, or non-mechanically-decidable authorization fails closed.

## State machine

```text
RECOVERY_UNREQUESTED
→ LOSS_CONFIRMED
→ RECOVERY_SEAT_RESERVED
→ RECOVERY_RECRUITER_BOUND
→ SUCCESSOR_QUALIFIED
→ SUCCESSOR_BOUND
→ RECOVERY_COMPLETE
```

1. `LOSS_CONFIRMED`: verify `READY`, authoritative vacancy, incumbent irrecoverability, last occupancy lineage, Charter generation, and recovery authorization.
2. `RECOVERY_SEAT_RESERVED`: atomically reserve the Recruiter Seat at its expected generation and bar all ordinary Recruiter succession or competing recovery.
3. `RECOVERY_RECRUITER_BOUND`: mechanically instantiate a fresh recovery Recruiter from the exact authorized composition and bind it inactive except for one successor qualification commission.
4. `SUCCESSOR_QUALIFIED`: the recovery Recruiter assembles and qualifies one distinct ordinary Recruiter; MasterMason verifies the single-use commission and returned succession packet.
5. `SUCCESSOR_BOUND`: MasterMason atomically retires the recovery Recruiter, advances the Seat generation, and binds the qualified ordinary successor.
6. `RECOVERY_COMPLETE`: verify ordinary Conscription readiness, close the recovery authority, and record the complete lineage and receipts.

Every transition uses the shared [Runtime Concurrency and Replay Primitive Contract](runtime-concurrency-replay.md). Completed effects recover forward from durable checkpoints. Failed candidates are quarantined and retired; their identities and commissions are never reused.

## Recovery Recruiter authority

The recovery Recruiter may:

- consume exactly one recovery-bound commission
- assemble and qualify exactly one distinct ordinary Recruiter successor
- return exactly one attributable succession packet or bounded failure

It may not:

- qualify Secretary, Rector, another Officer, or an operative
- accept ordinary Conscription work
- occupy another Seat
- survive successful succession
- be promoted, converted, reused, resurrected, or treated as the ordinary Recruiter
- alter its commission, either Profile, or the governing Charter generation

## Failure and halt rules

If incumbent status is uncertain, the Seat cannot be reserved. If the presumed-lost incumbent reappears, MasterMason fences it from authority and halts for state reconciliation; it never permits dual Recruiter authority.

Any unreconciled partial effect, conflicting Seat claim, changed Charter generation, invalid successor packet, or expired authorization halts recovery. Retry requires a fresh authorization where the Charter requires one, a fresh commission, fresh candidate identities, and an admissible recovery receipt from the last durable checkpoint.

Imperium may continue only within Charter-declared degraded-operation boundaries while the Recruiter Seat is vacant. No new manifestation may be assembled or qualified until `RECOVERY_COMPLETE`.

## Provenance

The ordinary successor's lineage is:

```text
Charter-declared recovery authority
→ authenticated recovery authorization
→ MasterMason recovery transaction
→ fresh recovery Recruiter
→ successor qualification packet
→ MasterMason binding
```

Recovery provenance remains permanently distinguishable from initial bootstrap and orderly succession.
