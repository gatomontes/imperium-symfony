# Runtime replay and idempotency contract

## Rule

A durable operation may return an existing result only when the complete authoritative-input fingerprint matches the fingerprint recorded by the first successful attempt.

The authoritative input set must include every source identity and digest, consumed authority identity, target identity, and state payload that can change the operation's meaning. Incidental retry time is excluded unless time itself changes authorization validity or output.

## Outcomes

- **Exact replay:** the fingerprint matches; return the existing immutable result without repeating side effects or consuming another authority.
- **Conflicting reuse:** the source identifier exists but the fingerprint differs; fail stopped with the operation's explicit conflict error.
- **Missing evidence:** fail stopped with the operation's explicit absent or invalid error.
- **Consumed authority:** exact replay may return its existing result; a new or conflicting operation may not reuse it.
- **Expired authority:** a new operation fails stopped. Expiry does not invalidate an already completed exact replay unless the operation's contract explicitly says otherwise.

## Initial enforcement

Hardening Step 12 applies this contract to:

1. provider-invocation claims;
2. provider-independent turn recovery; and
3. recoverable Delegate terminal retirement.

Remaining lifecycle services must migrate to the same primitive before the system-wide replay backlog item is complete.
