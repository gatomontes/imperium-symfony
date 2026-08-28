# Iron Gate Execution Authority and Receipt Binding Batch 8 complete

## Result

Batch 8 implements the journal-bound credential and AgentMail adapter boundary documented in
`docs/iron-gate-journal-bound-agentmail-invocation.md`.

One durable provider-invocation admission is persisted before credential resolution or callback
entry. It binds the exact journal, claim, capability, operation, destination, payload, request
fingerprint and idempotency key. A second callback is durably prohibited. The AgentMail adapter
propagates the exact governed `Idempotency-Key`; authentication remains callback-local and is never
persisted.

Proof uses an in-memory provider callback only. No live external I/O occurred. No command,
existing transport, raw receipt, Lazaretto or sortie behavior changed.

## Next separately bounded batch

Only Batch 9 may next be considered: validate one governed provider result and durably bind its raw
receipt and truthful accepted/rejected outcome to the invocation admission. Missing responses stay
`UNKNOWN_REPLAY_PROHIBITED`. Lazaretto admission and reconstruction remain closed. Batch 9 is not
authorized by this handoff.

Credential-platform redesign, revocation, propagation, telemetry, reassessment, containment and
incident boundaries remain closed. No Delegate Mission step or terminal campaign is reopened.

