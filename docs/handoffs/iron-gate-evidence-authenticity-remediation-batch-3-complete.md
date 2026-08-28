# Iron Gate Evidence Authenticity Remediation Batch 3 complete

## Result

Batch 3 migrates `DeterministicRawProviderResultService` to version 2 envelope consumption. Its only
input is one response-envelope ID. The service resolves and validates the envelope, invocation
admission, execution claim and sealed content, then derives HTTP outcome and raw receipt from those
records.

Callers can no longer supply provider status, response bytes, observation time or receipt time to
the raw-result sealer. Accepted and rejected evidence remains immutable and one-winner. Absence of
an envelope remains `UNKNOWN_REPLAY_PROHIBITED` and cannot call the sealer.

Existing deterministic Lazaretto and reconstruction continue to consume the resulting raw-result
shape and are not otherwise migrated in this batch. No command, transport or live provider is
connected, and no external I/O occurs.

## Next separately bounded batch

Only Batch 4 may next be considered: separate admission, credential-attempt,
callback-may-have-run and response-observed state semantics without weakening replay refusal. Batch
4 is not authorized by this handoff and requires explicit continuation.

Live AgentMail, Iron Gate, existing command/transport, sortie, credential-platform work, generalized
Lazaretto, revocation, propagation, telemetry, reassessment, containment and incidents remain closed.
