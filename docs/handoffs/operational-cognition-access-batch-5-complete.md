# Operational Cognition Access Batch 5 complete

> **Historical next-boundary note:** the migration requested below was completed by credential-boundary Batches 6–17. The system-wide gate is closed; see `docs/handoffs/credential-boundary-remediation-complete.md`.

The separately bounded Operational Cognition Access lifecycle is implemented and hostile-proofed through its broker boundary.

## Proof matrix

| Boundary | Proved disposition |
| --- | --- |
| Imperator refusal, expiry, or model mismatch | No lease activation authority |
| Refused, expired, mismatched, or unauthorized lease issuance | Fail stopped without credential resolution |
| Expired, consumed, substituted, divergent, or partial claim source | Fail stopped before provider I/O |
| Concurrent durable claimants | Converge on one claim |
| Missing, malformed, mismatched, expired, consumed, or superseded broker claim | Fail stopped before credential issuance |
| Exact invocation replay or interrupted pre-I/O reservation | Fail stopped before second credential resolution |
| Credential failure | Sealed pre-I/O failure; replay prohibited |
| Provider failure after invocation start | Unknown outcome; replay prohibited; diagnostic suppressed |
| Successful provider response | Envelope and response identity sealed; bounded result only |
| Persistence and serialized records | No credential material or secret-bearing diagnostics |

## Proof-driven correction

Batch 5 found that the Batch 4 journal began inside the credential callback. A replay could therefore resolve a second credential before the existing journal rejected adapter execution. The gateway now reserves the journal with an atomic compare-and-swap before broker consumption. Only the reservation winner can reach credential issuance; the callback then transitions that reservation to external-I/O-started immediately before adapter invocation.

## Local verification

```bash
php vendor/bin/phpunit \
  tests/Imperium/Runtime/OperationalCognitionAccessRequestDecisionTest.php \
  tests/Imperium/Runtime/OperationalCognitionLeaseServiceTest.php \
  tests/Imperium/Runtime/OperationalCognitionInvocationClaimServiceTest.php \
  tests/Imperium/Runtime/SymfonyAiOperationalExecutionCognitionGatewayTest.php
```

## Next boundary

Inventory the remaining directly configured agents, group them by governance cluster and authority contract, and establish their ordered migration batches. The shared environment-backed platform must remain until its last consumer is removed. The system-wide credential-boundary gate remains open.
