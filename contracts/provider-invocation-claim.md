# Provider invocation claim contract

## Purpose

Create the durable boundary that must exist before any external provider I/O. The claim consumes one exact Delegate cognition-turn authority and one exact Clavium credential lease as a single-winner transition.

## Preconditions

- the source activation is sealed and digest-valid;
- the activation remains at `DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN`;
- the exact bounded cognition-turn authority is exercisable and unconsumed;
- the exact credential lease is single-use, unconsumed, unexpired, undisclosed, and possession has not transferred; and
- the activation grants both provider-invocation and credential-use authority.

## Atomic outcome

Under one exclusive transition lock, the runtime:

1. re-reads and validates the authoritative activation;
2. rejects mismatched, expired, consumed, malformed, or tampered authority;
3. derives one deterministic invocation identity and authoritative-input fingerprint;
4. records consumption of both the lease and turn authority;
5. persists a stable provider idempotency key; and
6. atomically commits `INVOCATION_CLAIMED_PENDING_EXTERNAL_IO` before any provider adapter may run.

An exact replay returns the existing claim. Conflicting reuse fails stopped. Concurrent claimants can produce only the same single committed invocation record.

## Recovery boundary

The claim records that external I/O has not started. Automatic replay is prohibited from the moment the claim is committed. Later transitions must distinguish proven pre-I/O failure from an unknown provider outcome; they may not infer either from the absence of a cognition result.

## Credential boundary

The claim contains no credential reference or credential material. It binds only the opaque lease identity. Credential resolution remains reserved to the future broker-mediated provider adapter.

## Authority exclusions

The claim grants no tool use, perimeter crossing, external action, mission continuation, redeployment, or execution authority. It does not itself invoke a provider.
