# Canonical Native Effect Corridor Activation — Batch 5 adversarial/application proof v1

`BATCH_5_SEPARATE_PROCESS_CONTENTION_INTERRUPTION_BYPASS_AND_CONTAINER_PROOF_COMPLETE_NO_EXTERNAL_IO`

Two independent PHP processes competing for the same sealed effect authority
produce exactly one atomic-admission winner. The loser cannot substitute its
separately issued same-process capability and receives
`CNE302_EFFECT_AUTHORITY_ALREADY_USED`.

Process termination before admission publishes no effect record. Termination
after the atomic cut leaves exactly one immutable admission and a new process
cannot infer continuation. Termination inside the provider-double callback
leaves the durable callback-start record; a later process receives
`UNKNOWN_REPLAY_PROHIBITED` without entering its callback.

Expiry, revocation and cancellation refuse before admission. Production
container discovery exposes one auto-discovered `CanonicalNativeEffectCorridor`
construction boundary for the inert validator, secret-free issuer, atomic
admission service and provider-double recovery service, while no command,
generic executor, transport or legacy reader consumes the new cut. The facade
has no credential-broker dependency.

All process workers are local fixtures. They contain no credential resolver,
AgentMail implementation, HTTP client or network operation. Batch 6 remains a
non-executing live-trial package preparation boundary.
