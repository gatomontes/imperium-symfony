# Handoff: runtime-integrity hardening Step 3 complete

## Completed transition

Hardening Step 3 makes the Delegate brokered provider boundary directly testable and records explicit pre-I/O failure evidence.

Additional checkpoint: `INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED`

## Enforced invariants

- Symfony Generic platform construction is isolated behind `DelegateSymfonyPlatformAdapter`;
- the production DeepSeek adapter receives credential material only while executing inside the credential-broker callback;
- adapter tests prove the secret is absent from journal records and returned exceptions;
- a credential issuance or resolution failure before callback entry creates an explicit pre-I/O journal record;
- an adapter exception after durable start creates an unknown-outcome record;
- provider diagnostics and exception ancestry are suppressed at the cognition boundary;
- the same stable idempotency key reaches the provider adapter;
- response content is never persisted in the invocation journal; and
- an injected runtime clock controls all brokered invocation journal timestamps.

## Verification

Focused adapter tests cover successful broker callback execution, idempotency-key propagation, response-identity sealing, credential failure before callback entry, explicit pre-I/O evidence, post-start provider failure, unknown-outcome classification, diagnostic suppression, and absence of credential material.

PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation remains required.

## Scope limitation

This checkpoint proves the Delegate adapter boundary. It does not yet remove credential-bearing shared Symfony platforms used by resident or Legate cognition gateways.

## Next bounded transition

Hardening Step 4 should begin shared transactional persistence primitives. Invocation claiming and journaling now have local locked transitions, but the lifecycle still lacks a reusable `ImmutableRecordStore`, `MutableStateStore`, `AuthorityConsumptionStore`, and compare-and-swap coordinator.
