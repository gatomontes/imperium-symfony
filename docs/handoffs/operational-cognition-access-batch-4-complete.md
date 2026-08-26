# Operational Cognition Access Batch 4 complete

Batch 4 removes the operational Manifestation's directly configured Symfony AI agent and replaces it with the exact Batch 3 claim-bound credential path.

## Closed boundary

- One intact durable operational invocation claim must resolve through the exact bounded-execution authorization identity and digest.
- The claim's lease and cognition authority must both be consumed, non-continuing, and the lease still live.
- Provider and model are fixed to the strict DeepSeek adapter contract; the stable claim idempotency identity is preserved.
- Credential capability issuance and secret resolution occur only inside `OperationalClaimBoundCredentialBroker`; the secret reaches only the per-call adapter callback.
- Pre-I/O failure, unknown provider outcome, response identity, sealed response envelope, and automatic-replay prohibition reuse the existing governed journal contracts.
- The operational agent definition and direct `AgentInterface` injection no longer exist.

## Verification

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/SymfonyAiOperationalExecutionCognitionGatewayTest.php
```

The implementation environment did not provide PHP, so the focused PHP suite remains a local verification command. Static diff hygiene must pass before merge.

## Next boundary

Batch 5 is proof only: exhaust refusal, malformed/missing/mismatched/expired/consumed/superseded authority, replay and concurrency, interruption around claim/provider I/O, unknown outcome, and secret exclusion. Do not migrate other governance clusters in Batch 5 and do not claim the system-wide credential gate closed while direct agents and the global environment-backed platform remain.
