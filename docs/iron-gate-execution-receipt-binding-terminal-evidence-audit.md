# Iron Gate Execution Authority and Receipt Binding terminal evidence audit

## Status

`TERMINAL_THROUGH_BATCH_11`

This audit closes the bounded deterministic proof campaign. It records what the native evidence
corridor proves and, equally importantly, what it does not activate. The existing live Iron Gate,
`AgentMailEmailSendCommand`, `DeterministicBoundaryExecutor`, `AgentMailEmailTransport`, credential
platform and sortie paths are not migrated by this campaign and perform no new external I/O.

## Terminal requirement matrix

| Requirement | Classification | Exact consumer posture | Terminal evidence |
| --- | --- | --- | --- |
| Competent request, decision and issuance | `EXISTS_CANONICALLY` | `NATIVE_AUTHORITY_SOURCE` | Curia creates the sealed request; Imperator records the decision and separately issues the exact immutable authorization. |
| Actor, commission, operation, destination, payload, credential reference, return contract and expiry | `EXISTS_CANONICALLY` | `EXACT_SCOPE_REQUIRED` | Issuance preserves every authoritative digest and rejects widening or expired authority. |
| Single-use execution identity and competing callers | `EXISTS_CANONICALLY` | `DURABLE_SINGLE_WINNER` | The execution-claim transition permits one claim for one issuance; the transition lock serializes competitors. |
| Lock order and partial-write safety | `EXISTS_CANONICALLY` | `ATOMIC_RECORD_THEN_INDEX` | Each bounded service locks its source transition, validates before mutation and atomically seals its immutable record and winner index. |
| Effect-start point | `EXISTS_CANONICALLY` | `JOURNAL_BEFORE_PROVIDER` | A durable effect-start journal exists before credential resolution or the provider callback can be admitted. |
| Replay identity and provider idempotency | `EXISTS_CANONICALLY` | `EXACT_PROVIDER_REPLAY_IDENTITY` | The authorized provider key, request fingerprint and AgentMail header bind the admitted callback. A second admission is refused. |
| Crash before provider admission | `EXISTS_CANONICALLY` | `UNKNOWN_REPLAY_PROHIBITED` | The journal remains truthful and automatic replay is forbidden even when no callback admission exists. |
| Crash after provider admission and before response sealing | `EXISTS_CANONICALLY` | `UNKNOWN_REPLAY_PROHIBITED` | Admission records that the callback may have run; absence of a raw result is never rewritten as failure or success. |
| Observed accepted or rejected provider result | `EXISTS_CANONICALLY` | `RAW_EVIDENCE_ONLY` | One immutable raw result records HTTP status, exact bytes digest and observed/received times; rejected evidence is preserved without admission. |
| Accepted receipt admission | `EXISTS_CANONICALLY` | `EXACT_RETURN_CONTRACT_ADMISSION` | Deterministic Lazaretto admission accepts only the AgentMail message shape bound to the source result and authority. |
| Unknown and rejected receipt admission | `EXISTS_CANONICALLY` | `UNADMITTED_TRUTHFUL_OUTCOME` | Neither absence nor rejection can manufacture an accepted binding. |
| Persistence and read-only reconstruction | `EXISTS_CANONICALLY` | `DURABLE_RECEIPT_BOUND` | Reconstruction validates and returns issuance, claim, raw result and receipt binding without credential resolution, provider callback, external I/O or writes. |
| Tamper resistance | `EXISTS_CANONICALLY` | `FAIL_CLOSED_ON_DIGEST_MISMATCH` | Mutated terminal binding evidence is rejected before reconstruction. |
| Secret exclusion | `EXISTS_CANONICALLY` | `OPAQUE_CREDENTIAL_ONLY` | Adversarial proof scans every persisted corridor record and finds no callback credential bytes. |
| Existing deterministic command/transport adoption | `DEFERRED_BOUNDARY` | `UNMIGRATED_LIVE_CONSUMER` | No existing command, boundary executor or transport invokes the new corridor. A separately authorized adoption is required. |
| Sortie cognition, tools and lifecycle | `DEFERRED_BOUNDARY` | `SEPARATE_SORTIE_CAMPAIGN` | Sortie has different cognition, capability, process and retirement boundaries and was not merged into this lane. |
| Generalized Lazaretto policy, credential platform, revocation, propagation, telemetry, reassessment, containment and incidents | `DEFERRED_BOUNDARY` | `PERIMETER_CLOSED` | No policy or runtime change was authorized or made. |

No terminal requirement remains `ABSENT` inside the deliberately bounded deterministic evidence
corridor. The live-consumer and sortie boundaries remain deferred rather than silently promoted.

## Adversarial proof

`IronGateExecutionReceiptBindingBatch11Test` proves the entire accepted path from Curia request to
read-only receipt reconstruction. It also proves crash-stop truthfulness on both sides of callback
admission, one-shot callback admission, tamper failure and repository-record secret exclusion. The
earlier batch tests retain the rejected-result, malformed-return, concurrent-winner, replay and
fault-injection cases.

## Terminal disposition

The smallest safe migration sequence proposed in Preparation Batch 0 is complete as a native,
unused deterministic evidence corridor. It is eligible for a future, separately authorized live
consumer migration only after that migration names its exact command, provider operation and
operational evidence gate. This closeout does not itself open Iron Gate or Lazaretto and does not
authorize another batch.
