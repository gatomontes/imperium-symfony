# Next campaign: Iron Gate Evidence Authenticity Remediation

## Status

`BATCH_4_CHECKPOINT_STATES_SEPARATED`

Iron Gate Execution Authority and Receipt Binding remains terminal through Batch 11. Its adversarial
review found that the unused deterministic corridor is structurally coherent but not yet eligible
for live consumer adoption: a caller can separately nominate response bytes after the provider
callback, reconstruction skips effect-start and invocation-admission evidence, and institutional
actor and tamper claims exceed what the current enforcement proves.

Preparation Batch 0 and Batches 1–4 are complete. Only separately bounded Batch 5 may next be considered. Nothing below authorizes live consumer behavior,
external I/O or live consumer adoption.

## Preparation inventory

| Requirement | Classification | Required consumer posture | Evidence and exact gap |
| --- | --- | --- | --- |
| Callback-bound response capture | `ABSENT` | `PROVIDER_RESPONSE_ENVELOPE_REQUIRED` | `DeterministicJournalBoundCredentialBroker::invoke()` returns callback output, while `DeterministicRawProviderResultService::seal()` independently accepts arbitrary status and bytes. No durable evidence proves those bytes came from that invocation. |
| Complete request-to-receipt reconstruction | `EXISTS_FRAGMENTED` | `COMPLETE_CHAIN_RECONSTRUCTION_REQUIRED` | Reconstruction validates binding, raw result, claim and issuance but does not resolve provider admission, effect-start journal, decision, request or Curia occupancy. |
| Competent actor invocation | `EXISTS_FRAGMENTED` | `CALLER_AUTHORITY_REQUIRED` | Native occupancy and decision records exist, but service access is not bound to a consumed caller-specific invocation authority; Imperator identity is a development-root label. |
| Adversarial tamper protection | `EXISTS_FRAGMENTED` | `THREAT_MODEL_BOUNDED_INTEGRITY` | Canonical unkeyed digests detect accidental or unrecomputed mutation, not a writer able to alter content and recompute its digest. |
| Provider idempotency identity | `EXISTS_FRAGMENTED` | `REGISTERED_REQUEST_BOUND_IDEMPOTENCY` | The supplied key is checked against its supplied digest and forwarded, but global uniqueness, request derivation, provider retention and duplicate behavior are not operationally proved. |
| Credential/effect state truthfulness | `EXISTS_FRAGMENTED` | `SEPARATE_ADMISSION_ATTEMPT_OBSERVATION_STATES` | Admission declares credential use committed before broker consumption and conservatively declares that the callback may have run. These are safe for replay refusal but semantically collapse distinct checkpoints. |
| Receipt provenance and request correlation | `ABSENT` | `PROVIDER_PROVENANCE_REQUIRED` | Lazaretto validates JSON shape and internal agreement of values extracted from the same caller-supplied bytes; it does not prove provider origin or correlation to the exact authorized request. |
| Single-host persistence assumption | `DEFERRED_BOUNDARY` | `SINGLE_AUTHORITATIVE_ROOT_ONLY` | Current atomicity proves one authoritative filesystem root, not multi-host, split-brain or eventually consistent storage. Distributed persistence is outside this campaign. |
| Existing live command, transport and Iron Gate adoption | `DEFERRED_BOUNDARY` | `UNMIGRATED_LIVE_CONSUMER` | Live adoption remains prohibited until authenticity remediation closes and a separate campaign explicitly selects it. |
| Sortie, generalized Lazaretto policy and credential platform | `DEFERRED_BOUNDARY` | `PERIMETER_CLOSED` | These remain separate boundaries and may not be imported to make this remediation appear complete. |

## Smallest safe sequence

No batch is authorized merely because it is listed:

1. **Completed.** Preparation Batch 0 — preserve the adversarial findings, classifications,
   postures, deployment assumption and stop conditions;
2. **Completed.** Define a separately versioned callback-bound response-envelope contract without
   invoking a provider or migrating a consumer;
3. **Completed.** Bind one response envelope to one invocation admission and prohibit
   caller-nominated provider truth;
4. **Completed.** Make raw-result sealing consume the invocation-produced response envelope and
   prohibit caller-nominated status, bytes and times;
5. **Completed.** Separate admission, credential-attempt, callback-may-have-run and
   response-observed states;
6. reconstruct the entire occupancy/request/decision/issuance/claim/journal/admission/response/
   result/binding chain read only;
7. bind authority transitions to enforceable caller authority and state the exact integrity threat
   model;
8. prove idempotency registration, response provenance, fault, concurrency, tamper and secret
   exclusion without live external I/O; and
9. close remediation before selecting any live deterministic consumer campaign.

## Stop conditions

This campaign must stop rather than manufacture certainty if provider response bytes cannot be
captured by the exact admitted invocation, if actor authority cannot be enforced at the transition,
or if the integrity threat model cannot honestly distinguish checksum validation from hostile-writer
resistance.

Preparation Batch 0 may change only campaign inventory, handoffs, canonical flow and documentation
assertions. It may not change a runtime service, invoke AgentMail, migrate `AgentMailEmailSendCommand`,
`DeterministicBoundaryExecutor`, `AgentMailEmailTransport` or `IronGate`, expand Lazaretto, redesign
credentials, assess sortie, or open revocation, propagation, telemetry, reassessment, containment or
incident behavior.
