# Iron Gate Execution Authority and Receipt Binding Batch 2 complete

## Result

Batch 2 assessed exactly AgentMail deterministic `email.send` and did not prove it eligible for
migration. The canonical assessment is
`docs/iron-gate-agentmail-provider-safety-assessment.md`.

AgentMail documents idempotency for create operations and recommends application message-ID
tracking or the draft workflow for duplicate-send prevention. That does not establish a
caller-supplied idempotency identity for direct send or close the unknown-outcome window before its
response is observed. The current lane has no durable pre-I/O provider journal.

The native source authorization is also absent. `AgentMailEmailSendCommand` fabricates identifiers
inside the executing process. Curia's existing bounded execution authorization cannot fill the gap:
it expressly denies tool, credential, external-action and network authority.

The exact consumer posture is therefore `BLOCKED_SOURCE_AUTHORITY_AND_PROVIDER_SAFETY`, with
`UNKNOWN_REPLAY_PROHIBITED` after an indeterminate provider attempt. No issuer or consumer was
migrated. Runtime behavior is unchanged and no external I/O occurred.

## Next separately bounded batch

No consumer-adoption batch is open. Only Batch 3 may next be considered: define, without issuing or
consuming it, the missing native outbound-email authorization contract and assess one explicitly
replay-safe provider workflow. Batch 3 is not authorized by this handoff and requires an explicit
continuation instruction.

Iron Gate execution, Lazaretto persistence, sortie, credential-platform, revocation, propagation,
telemetry, reassessment, containment and incident boundaries remain closed. No Delegate Mission
step or terminal campaign is reopened.
