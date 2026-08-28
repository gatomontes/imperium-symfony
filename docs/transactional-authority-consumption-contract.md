# Transactional authority consumption and recovery contract

## Status

`BATCH_6_DETERMINISTIC_DELEGATE_SENATE_SUBCLUSTER_ADOPTED`

This document defines the shared version-1 mechanics represented by
`TransactionalAuthorityConsumptionContract` and `AuthorityConsumptionRecoveryContract`. The
contract classes remain declarative and do not themselves issue, consume, revoke, persist, lock,
recover, retry, or execute anything. The adopted operational, governance, and Delegate provider
claims plus the deterministic Delegate Senate results compose them through
`TransactionalAuthorityConsumptionEnvelope`; other consumers remain unmigrated.

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

## Batch 3 commit-boundary proof

The first adoption now has direct fault-injection proof at `PREPARED`,
`CONSUMPTION_COMMITTED`, `RESULT_COMMITTED`, and `COMPLETE`. The operational claim deliberately
does not create a second mutable transaction record. Its two consumptions and immutable lifecycle
result are one physical `ImmutableRecordStore::put()` commit:

- failure after `PREPARED` leaves no consumption or result artifact, and exact retry may perform the
  single commit;
- failure observed at any later logical checkpoint leaves the same complete immutable result with
  both exact consumptions;
- exact retry and replay return that result, while divergent transactional metadata fails stopped;
- the existing claim/claim and claim/interruption proofs retain one winner; and
- every recovery case retains `credential_resolved=false`, no provider journal, no provider
  invocation, no network access, and no external I/O.

No checkpoint grants rollback or authority unconsumption. No provider outcome exists inside this
transition.

## Batch 4 governance adoption

`GovernanceCognitionInvocationClaimService` is the second adopted consumer. It retains the exact
`imperium.clavium-governance-cognition-invocation-claim/v1` schema and its pre-existing claim-ID
derivation for compatibility. The complete `ReplayFingerprint` is separately sealed into each new
claim and transaction envelope.

The authoritative inputs include the complete normalized output reread through the exact
`GovernanceCognitionAuthorityRegistry` resolver, plus the request, resource decision, lease,
provider/model configuration, resource ceiling, target, and input digest. This prevents adoption
from weakening cluster-specific authority resolution.

Adoption preserves the exact lock order:

`gca-authority:<sha256 authorityId>` → `gca-lease:<sha256 leaseId>`

Historical immutable claims without the envelope remain replayable and are not rewritten. New
claims validate exact replay and reject divergent envelope metadata. The two-process claim proof
converges on one immutable result, while the existing governance-lease interruption path continues
to compete on the same lock scopes. Provider-journal creation and external I/O remain later,
separate boundaries.

## Batch 5 Delegate provider-claim adoption

`ProviderInvocationClaimService` is the third adopted consumer. It retains the exact
`imperium.clavium-provider-invocation-claim/v1` schema, deterministic claim ID, provider
idempotency key, lifecycle consumption fields, and one existing physical lock:

`provider-invocation-claim:<sha256 activationId>`

The envelope keeps the turn authority first and credential lease second as distinct authority
entries. Each lock-plan entry names the same composite scope because that one pre-existing lock
protects both; this declaration neither acquires the scope twice nor invents authority-specific
locks. The complete sealed activation is fingerprinted, including target, model binding and
configuration, credential-reference digest, lease scope/expiry, and both authority states.

Historical immutable claims without the envelope validate their original narrower fingerprint and
are returned without rewrite. Adopted claims validate the complete fingerprint and exact envelope.
The immutable claim commits both logical consumptions and the result together before any provider
journal, credential resolution, provider invocation, network access, or external effect. Existing
unknown-outcome and sealed-response forward-recovery semantics remain unchanged.

## Batch 6 Delegate Senate adoption

Eight deterministic authority consumers in Delegate Mission Steps 19–42 now compose the contract
through `DelegateMissionSenateAuthorityTransition`. The shared mechanism does not merge their authorities,
actors, jurisdictions, source records, cognition gateways, schemas, or immutable results.

Each consumer derives one lock from the exact authority it already consumes:

`delegate-senate-authority:<sha256 authorityId>`

The lock encloses the consumer's existing reread, chain validation, replay scan, and result commit.
The unchanged lifecycle result receives one embedded transaction
envelope whose authoritative inputs are the complete pre-envelope result surface, including the
exact immutable source digest and occupied actor. Authorities with no lifecycle expiry retain that
fact through the explicit `NO_EXPIRY_DECLARED` sentinel; no expiry or revocation behavior is added.

`ImmutableRecordStore` provides the one physical result commit. Historical results without an
envelope remain valid and are not rewritten. Adopted records are validated against the exact
consumer implied by their unchanged schema and jurisdiction. A fault immediately after result
commit leaves the complete transaction and exact retry recovers that one record; rollback,
authority unconsumption, and external effects remain prohibited.

Question authorship, testimony response, Senator finding, finding reconciliation, and final Senate
disposition are not adopted in Batch 6. Each crosses a Symfony AI gateway before its result exists.
A process death after that call has an unknown outcome; neither a lock nor a post-I/O envelope can
make replay truthful or effectively once-only. Those consumers remain `RECOVERY_INCOMPLETE` until a
separate pre-I/O claim/journal/recovery boundary is explicitly prepared and authorized.

## Closed boundaries

This contract opens no authority, revocation, propagation, telemetry, reassessment, containment,
incident, Iron Gate, Lazaretto, sortie, external-receipt, provider-journal, or credential-platform
boundary. It creates no Delegate Mission Step 70 or successor step in any closed campaign.
