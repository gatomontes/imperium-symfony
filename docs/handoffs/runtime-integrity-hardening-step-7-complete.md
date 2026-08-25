# Handoff: runtime-integrity hardening Step 7 complete

## Completed transition

Hardening Step 7 adds process-level contention proof and deterministic provider-invocation crash assessment.

## Recovery classifications

- no durable claim: no recoverable invocation exists;
- claim without journal: governed resolution is required and automatic replay is prohibited;
- `INVOCATION_IN_FLIGHT`: provider outcome may be unknown and automatic replay is prohibited;
- response identity sealed without a turn: turn-persistence recovery is required without provider replay;
- explicitly unknown outcome: governed resolution is required and automatic replay is prohibited;
- pre-I/O failure: fresh authorization is required; and
- durable turn linked to the exact claim digest: no provider recovery is required.

The assessor is read-only. It grants no mission, provider, credential, turn, continuation, or replay authority.

## Concurrency proof

The journal contention test launches two independent PHP subprocesses behind one start gate. Both contend for the same durable claim and journal identity through the real filesystem transition locks. Exactly one process creates `INVOCATION_IN_FLIGHT`; the other receives `CLV412_PROVIDER_INVOCATION_ALREADY_STARTED`. The test uses `PHP_BINARY` and `proc_open` so it runs on Windows and Unix-like PHP environments.

Shared persistence directory creation tolerates a simultaneous creator without emitting diagnostics. A competing process must lose only through the governed transition result; filesystem race warnings are neither evidence nor part of the process contract.

## Verification

Static diff validation passes. PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation remains required.

## Next bounded transition

Hardening Step 8 should make downstream bounded-turn persistence atomic and recoverable after response identity sealing, then add fault injection around the response-sealed-to-turn-persisted boundary.
