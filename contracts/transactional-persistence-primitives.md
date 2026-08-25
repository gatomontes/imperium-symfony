# Transactional persistence primitives contract

## Purpose

Replace ad hoc scan, validate, decide, and write mechanics with shared primitives that protect the complete transition decision under one named lock.

## `AtomicTransition`

- serializes every operation sharing the same bounded scope;
- holds the exclusive lock across read, validation, decision, and commit;
- releases the lock after success or exception; and
- grants no business authority by itself.

## `ImmutableRecordStore`

- validates bounded relative paths and rejects traversal;
- seals every record with canonical SHA-256 identity;
- commits through a temporary file and atomic rename while locked;
- returns an exact authoritative replay; and
- rejects changed content under an existing immutable identity.

## `MutableStateStore`

- validates the current record digest while holding the transition lock;
- permits initial creation only when the expected digest is `null`;
- commits a next generation only when the supplied expected digest matches;
- rejects stale writers and tampered current state; and
- atomically replaces the state file.

## `AuthorityConsumptionStore`

- derives one deterministic consumption identity from the exact authority ID;
- binds the source identity and digest, consumer, and timestamp;
- returns the original consumption for a replay with the same authoritative source and consumer, regardless of the retry clock; and
- rejects a second source or consumer for an already-consumed authority.

## Scope boundary

These primitives establish infrastructure, not lifecycle migration. A service is not concurrency-safe merely because these classes exist; its complete authoritative transition must be moved onto them and covered by contention and fault-injection tests.
