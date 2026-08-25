# Handoff: runtime-integrity hardening Step 2 complete

## Completed transition

Hardening Step 2 moves the Delegate provider path behind a durable claim-aware credential broker.

Checkpoint sequence:

1. `INVOCATION_CLAIMED_PENDING_EXTERNAL_IO`
2. `INVOCATION_IN_FLIGHT`
3. `PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING`

An interrupted or exceptional provider attempt reaches `PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED`.

## Enforced boundary

- the bounded cognition-turn service creates the exact durable claim before calling its gateway;
- the completed cognition-turn record preserves the invocation-claim reference and digest;
- the Delegate gateway no longer receives or injects a credential-bearing `PlatformInterface`;
- the production provider invoker resolves `DEEPSEEK_API_KEY` only inside `CredentialBroker::consume()`;
- Symfony's Generic platform is constructed ephemerally inside that callback;
- `INVOCATION_IN_FLIGHT` is durably committed immediately before platform construction and provider I/O;
- the stable claim idempotency key is sent as `Idempotency-Key`;
- response content is represented in the journal only by a SHA-256 identity; and
- any post-start exception fails stopped as an unknown outcome that cannot automatically replay.

## Verification

Focused tests cover claim-aware gateway routing, unsupported-runtime refusal, durable pre-I/O journaling, response-identity sealing, duplicate-start refusal, unknown-outcome replay prohibition, and exact durable-claim matching. The full Delegate flow now proves that a durable claim exists before its cognition gateway is entered.

PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation is required before this checkpoint is treated as runtime-proven.

## Scope limitation

This step removes direct credential-bearing platform possession from the Delegate mission gateway only. Other resident and Legate Symfony AI gateways still use the shared configured platform. They require later migration before Clavium can honestly claim system-wide exclusive credential custody.

## Next bounded transition

Hardening Step 3 should prove the production brokered invoker with adapter contract tests, inject a clock into claim and journal expiry decisions, and introduce explicit pre-I/O failure evidence without weakening unknown-outcome handling.
