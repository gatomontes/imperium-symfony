# Handoff: runtime-integrity hardening Step 4 complete

## Completed transition

Hardening Step 4 establishes the shared transactional persistence foundation required by the Blackquill backlog.

Introduced primitives:

- `AtomicTransition`
- `ImmutableRecordStore`
- `MutableStateStore`
- `AuthorityConsumptionStore`

## Enforced invariants

- complete primitive-level read, validation, decision, and commit sequences share one exclusive named lock;
- immutable identities permit exact replay and reject conflicting content;
- mutable state changes require an exact current digest;
- stale or tampered state cannot win compare-and-swap;
- authority consumption has one deterministic identity and one exact winning source lineage;
- filesystem paths are bounded and traversal-resistant; and
- locks are released after both success and failure.

## Verification

Contract tests cover immutable exact replay, conflicting replay, one-record uniqueness, mutable creation, compare-and-swap, stale writers, tamper refusal, single-winner authority consumption, conflicting consumers, and lock recovery after injected exceptions.

PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation remains required.

## Scope limitation

This step introduces the primitives but does not claim that existing lifecycle services are automatically transactional. Migration must occur service by service, preserving every office, authority, emitted record, and cross-office handoff.

## Next bounded transition

Hardening Step 5 should migrate the Delegate provider invocation claim and journal onto the shared primitives, eliminating their local duplicated locking, digest validation, scanning, and commit code while preserving their current record schemas and recovery semantics.
