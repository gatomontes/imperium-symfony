# Transactional authority consumption and recovery contract

## Status

`BATCH_2_OPERATIONAL_CLAIM_ADOPTED`

This document defines the shared version-1 mechanics represented by
`TransactionalAuthorityConsumptionContract` and `AuthorityConsumptionRecoveryContract`. The
contracts describe a future transactional envelope. They do not issue, consume, revoke, persist,
lock, recover, retry, or execute anything, and no current consumer reads them.

## Consumption envelope

One transaction binds:

1. one or more separately typed lifecycle authorities, each with its unchanged schema, exact ID,
   source ID/digest, issuer, holder, scope, expiry, single-use state, and expected unconsumed state;
2. the complete authoritative inputs and their `ReplayFingerprint`;
3. the exact competent actor, service, and bounded act;
4. an ordered lock plan naming every existing lock scope and the authority protected by it;
5. every authority consumption and the one immutable lifecycle result; and
6. one separately versioned recovery reference.

Authority order is semantic and must be preserved. A set cannot be sorted or deduplicated in a way
that changes the existing acquisition order. For the first proposed migration, compatibility means:

`oca-cognition-authority:<sha256 authorityId>` → `oca-lease:<sha256 leaseId>`

The contract does not authorize changing either scope, reversing the order, or replacing the
operational cognition authority or lease schemas.

## Replay and winner rules

- Exact replay requires the same complete authoritative inputs, authority order, sources, consumer,
  bounded act, and lock plan.
- Exact replay returns the same immutable result and consumption identities.
- Changed authority, source ID/digest, actor, bounded act, input, expiry, scope, or lock plan is a
  conflict and fails stopped.
- A transaction may consume multiple authorities, but it does not merge them into one authority.
- `continuing_authority` is always false for the completed bounded act.
- Lifecycle-specific missing, stale, expired, superseded, interrupted, and already-consumed rules
  remain authoritative until separately migrated and proved.

## Recovery contract

Recovery observes one of six checkpoints:

1. `NOT_STARTED`;
2. `PREPARED`;
3. `CONSUMPTION_COMMITTED`;
4. `RESULT_COMMITTED`;
5. `COMPLETE`; or
6. `UNKNOWN`.

Only an exact replay fingerprint may participate in retry or forward recovery. Once consumption is
committed, automatic rollback and authority “unconsumption” are prohibited. An unknown external
outcome prohibits automatic retry and provider reinvocation, requires separately governed
resolution, and permits only sealed-response forward recovery where an existing lifecycle already
authorizes it.

The contract itself grants no recovery authority and cannot infer one from a checkpoint.

## Batch 2 adoption

`OperationalCognitionInvocationClaimService` is the first and only adopted consumer. New claims
embed a sealed transaction envelope while retaining the lifecycle claim schema and all existing
consumption fields. The envelope is complete before any provider journal, credential resolution,
network access, or external I/O. Existing pre-adoption immutable claims are neither rewritten nor
treated as proof of adoption.

Adoption preserves the exact lock order:

`oca-cognition-authority:<sha256 authorityId>` → `oca-lease:<sha256 leaseId>`

Exact replay validates the embedded envelope; changed transactional metadata is a conflict.

## First-adoption proof obligations

The separately authorized Batch 2 migration is complete. Its remaining Batch 3 proof obligations
are:

- one winner for claim/claim and claim/interruption competition;
- complete replay equivalence and conflicting replay refusal;
- one immutable result and both exact authority consumptions;
- recovery behavior after `PREPARED`, `CONSUMPTION_COMMITTED`, `RESULT_COMMITTED`, and `COMPLETE`;
- no credential resolution, provider journal, network access, or external I/O inside the
  consumption transition; and
- unchanged issuer, consumer, authority schemas, scope, expiry, lock scopes, and lock order.

## Closed boundaries

This contract opens no authority, revocation, propagation, telemetry, reassessment, containment,
incident, Iron Gate, Lazaretto, sortie, external-receipt, provider-journal, or credential-platform
boundary. It creates no Delegate Mission Step 70 or successor step in any closed campaign.
