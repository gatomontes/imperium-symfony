# Provider Effect Principal and Binding Activation — Batch 1

## Result

BATCH_1_ATOMIC_PRINCIPAL_ACTIVATION_PRODUCTION_COMPLETE

The existing competent decision, single-use activation authority and immutable
activation schema now have one production transition. It creates one immutable
combined consumption-and-activation winner keyed to the exact instance,
principal, generation and process boundary.

The principal generation is ACTIVE only when that combined record commits.
There is no separate authority-consumption record and therefore no
consumption-only durable state.

## Atomic transition

The service validates the complete decision, inert attestation, admitted
assurance and execution boundary before acquiring the generation lifecycle
lock. Inside that lock it deterministically constructs and validates the
activation record and commits it immutably.

The record contains:

- the exact source decision;
- the consumed single-use activation authority;
- the exact assurance, boundary and inert attestation;
- the exact principal generation and same-process scope;
- effective time, expiry and revocation posture;
- read-only exact reconstruction rules; and
- no continuing authority.

Exact replay returns the same winner. Changed valid evidence under the same
principal generation conflicts. Competing same-root service instances converge
under the generation lock.

## Crash and reconstruction

A cut before the combined commit leaves neither consumption nor activation. A
cut after commit leaves the exact combined winner. Recovery reads and validates
that winner without consuming authority again, reactivating, upgrading a
generation or creating a capability.

Expired, revoked, refused, wrong-generation or otherwise invalid decision
evidence refuses before commit.

The threat ceiling remains TRUSTED_WRITER_CANONICAL_INTEGRITY on
SINGLE_AUTHORITATIVE_ROOT_ONLY. Distributed uniqueness, hostile-writer
non-forgeability and split-brain resistance are not claimed.

## Preserved boundary

The exact principal generation may now become ACTIVE through this transition.
The provider binding remains BOUND_INACTIVE. Principal activation issues no
binding authority, execution authority, credential capability, retry authority
or continuing authority.

The transition has no dependency on credential bytes, credential references,
environment-variable names, process-local capability identity, provider SDKs,
transports or live commands. It defines no live-call contract, resolves no
credential, invokes no provider, performs no external I/O and sends no outbound
byte.

UNKNOWN_REPLAY_PROHIBITED remains binding after any possible provider effect.
Iron Gate and Lazaretto remain closed.

## Batch 2 gate

Only Batch 2 may next be considered: the principal-production lifecycle
terminal audit. It must adversarially verify the combined winner, exact
consumption, interruption cuts, replay, changed evidence, contention, expiry,
revocation, reconstruction, secret exclusion and non-authorities.

Binding activation is not authorized until that audit closes the principal
production sub-boundary.
