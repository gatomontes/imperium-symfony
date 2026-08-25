# Handoff: runtime-integrity hardening Step 1 complete

## Completed transition

Hardening Step 1 establishes the durable provider-invocation claim foundation.

`ProviderInvocationClaimService` now performs one complete read, validation, replay check, authority-consumption decision, and atomic write under a shared exclusive transition lock. It persists one deterministic invocation identity, one authoritative-input fingerprint, and one stable provider idempotency key before external I/O.

Checkpoint: `INVOCATION_CLAIMED_PENDING_EXTERNAL_IO`

## Enforced invariants

- only a sealed, digest-valid Delegate provider activation is claimable;
- the exact cognition-turn authority and opaque credential lease must be single-use, unconsumed, exercisable, and unexpired;
- lease and turn-authority consumption are recorded in the same immutable claim;
- exact replay returns the existing claim;
- changed lineage under an already-claimed activation identity fails stopped;
- claim persistence uses a temporary file plus atomic rename while the transition lock is held;
- a stable persisted idempotency key exists before provider I/O;
- automatic replay is explicitly prohibited;
- the claim contains neither credential references nor credential material; and
- no provider invocation occurs in this step.

## Verification

Focused tests cover successful claiming, stable exact replay from two claimants, mismatched authority, expiry at the exact boundary, changed source lineage, one-record uniqueness, pre-I/O state, replay prohibition, and absence of credential material.

PHP is not installed in the current execution environment, so PHPUnit verification remains pending local execution.

## Authority boundary

This is a new technical-hardening lifecycle, not Delegate Mission Step 70. The completed and retired Delegate route remains closed. This transition grants no tool, perimeter, external-action, execution, continuation, redeployment, or reuse authority.

## Next bounded transition

Hardening Step 2 must introduce the broker-mediated provider adapter and remove direct `PlatformInterface` possession from `SymfonyAiDelegateMissionCognitionGateway`. The adapter must accept only a valid durable claim, resolve the credential inside the broker boundary, mark external I/O start durably, and preserve unknown-outcome fail-stop semantics.
