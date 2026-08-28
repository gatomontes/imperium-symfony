# Iron Gate Evidence Authenticity Remediation Batch 4 complete

## Result

Batch 4 replaces preemptive outcome claims with four exact immutable states:

1. invocation admission — admitted, credential not yet attempted, callback not admitted, outcome
   `NOT_ATTEMPTED`;
2. credential-consumption attempt — consumption may fail, callback has not run, outcome
   `UNKNOWN_REPLAY_PROHIBITED`;
3. callback start — the provider callback may have run, no response is claimed, outcome
   `UNKNOWN_REPLAY_PROHIBITED`; and
4. response envelope — an exact response was observed and sealed.

Credential failure now leaves admission plus attempt but no callback-start or response evidence.
Callback failure leaves callback-start but no response envelope. Both remain non-replayable. Version
2 provider admissions no longer claim that credential consumption or callback execution happened
before their respective boundaries.

No live command, transport or provider is connected, and no external I/O occurs.

## Next separately bounded batch

Only Batch 5 may next be considered: reconstruct the complete occupancy/request/decision/issuance/
claim/journal/admission/attempt/callback/response/result/binding chain read only. Batch 5 is not
authorized by this handoff and requires explicit continuation.

Actor enforcement, integrity threat model, provider idempotency operational proof, live adoption,
sortie, credential-platform work, generalized Lazaretto, revocation, propagation, telemetry,
reassessment, containment and incidents remain closed.
