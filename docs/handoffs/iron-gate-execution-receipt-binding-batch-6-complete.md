# Iron Gate Execution Authority and Receipt Binding Batch 6 complete

## Result

Batch 6 implements one durable deterministic execution winner and consumes the exact Batch 5
outbound authorization into the same immutable claim aggregate. The canonical mechanics are in
`docs/iron-gate-deterministic-execution-claim.md`.

The claim binds exact authority, decision lineage, commission, operation, destination, payload,
return contract, holder, credential-capability metadata, provider idempotency identity, expiry and
lock order. Exact replay converges on the winner. A competing capability, expired or mismatched
scope, source tamper and secret-bearing input fail before persistence.

Every claim stops at `CLAIMED_PRE_IO / NOT_ATTEMPTED`; no credential was consumed or resolved and
no external I/O began. Runtime command, Iron Gate, transport, provider journal, raw receipt,
Lazaretto and sortie behavior are unchanged.

## Next separately bounded batch

Only Batch 7 may next be considered: bind the durable claim to one pre-I/O credential-use and
effect-start journal transition while preserving provider idempotency and truthful
`UNKNOWN_REPLAY_PROHIBITED` recovery. Provider invocation, receipt creation and Lazaretto admission
must remain separately bounded. Batch 7 is not authorized by this handoff.

Credential-platform redesign, revocation, propagation, telemetry, reassessment, containment and
incident boundaries remain closed. No Delegate Mission step or terminal campaign is reopened.

