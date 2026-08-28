# Iron Gate Execution Authority and Receipt Binding — Preparation Inventory

## Scope and method

This is the canonical documentation-only output of Preparation Batch 0. Runtime behavior is
unchanged. The inventory covers all 39 PHP files under `Runtime/LaCortine` and `Runtime/Sortie`, the
Oracle research issuer/admission pair, every runtime `OutboundRequest` constructor and Iron Gate
consumer, and the associated scope, credential, provider, process, Lazaretto, persistence,
reconstruction, tamper and secret-exclusion tests.

The classifications are:

- `EXISTS_CANONICALLY`: one explicit contract enforces the complete property at the exact named
  boundary; durability is claimed only where the evidence row says so;
- `EXISTS_FRAGMENTED`: exact pieces exist, but no single durable execution/receipt contract joins
  them;
- `ABSENT`: the property is neither implemented nor truthfully proved; and
- `DEFERRED_BOUNDARY`: the property belongs to an expressly closed adjacent campaign.

The consumer postures are:

- `DURABLE_RECEIPT_BOUND`: one durable claim establishes the winner before effect start, records a
  truthful provider outcome, binds the exact raw receipt through admission, and supports read-only
  reconstruction. No current perimeter consumer has this posture.
- `PRE_IO_UNCLAIMED`: exact request validation exists, but no durable single-winner claim precedes
  credential consumption or effect start.
- `UNKNOWN_OUTCOME_UNSAFE`: an external effect may begin without a durable provider-idempotency and
  unknown-outcome rule that prohibits unsafe automatic replay.
- `RECEIPT_RECOVERY_INCOMPLETE`: a raw or admitted return exists only in memory, a temporary file,
  or a multi-write downstream chain with no complete forward-recovery rule.
- `JOURNALED_EFFECT_FRAGMENTED`: one effect has a durable pre-I/O journal and fail-stopped unknown-
  outcome rule, but claim, provider response bytes, raw return, admission and terminal lifecycle are
  not one reconstructable contract.
- `DEFERRED_COMPETING_BOUNDARY`: the consumer belongs to the separately closed sortie, inbound,
  credential-platform or downstream evidence boundary and is not eligible for the deterministic
  first slice.

One consumer can carry multiple postures because validation, effect and return are distinct
failure surfaces. A posture is not a claim that an unopened boundary may be implemented.

## Authoritative issuers and holders

| Issuer | Actor and authoritative input | Exact power and limit | Classification |
| --- | --- | --- | --- |
| `AgentMailEmailSendCommand` | CLI operator supplies recipient, subject, body, optional PDF and inbox; the command generates commission, authorization, request and capability identities in process. | It can construct and immediately execute `email.send`, but no persisted competent institutional decision or sealed authorization is read. The authorization digest is the payload digest rather than a digest-validated authorization record. | `ABSENT` |
| `DeterministicHttpPostSmokeCommand` | Local smoke command creates its own secret, commission, fixed authorization and exact payload. | Deliberately non-network proof harness; it is not a production authority issuer. | `DEFERRED_BOUNDARY` |
| `SortieSmokeCommand` | CLI objective plus generated request/commission identity and fixed authorization label. | Creates cognition-only sortie scope without a persisted source authorization. | `ABSENT` |
| `SortieHttpGetSmokeCommand` | CLI destination/objective plus generated capability and request identities. | Creates tool-bearing sortie scope without a persisted capability issuer or source authorization. | `ABSENT` |
| `OracleResearchCommissionService` | Digest-validated Imperator research authorization, exact occupied Augur, instance, governed scope, issue/expiry times. | Persists `imperium.oracle-research-commission/v1`, then derives a sortie `OutboundRequest`. The commission has a deterministic identity and explicit single-use research authority, but issuance is direct-write/file-exists replay and exercise is not claimed at dispatch. | `EXISTS_FRAGMENTED` |
| `OutboundRequest` | Request, authorization ID/digest, commission, operation, purpose, mode, destinations, tools, capabilities, payload digest, return contract and expiry. | Complete in-memory scope carrier and immediate holder of outbound power; it does not validate a source record, actor, instance, issuance time, single-use identity or persistence. | `EXISTS_FRAGMENTED` |
| `CredentialBroker::issue()` | Credential reference, commission, operation, expiry and maximum uses. | Issues an opaque capability with no secret material. `EnvironmentCredentialBroker` is process-local and accepts no source decision, destination, payload, request or execution identity. | `EXISTS_FRAGMENTED` |

The five source files constructing `OutboundRequest` are the four commands above and
`OracleResearchCommissionService`. No other persistent authoritative issuer was found.

## Requirement inventory

| Requirement | Classification | Evidence and exact limit |
| --- | --- | --- |
| Exact authorization and commission lineage | `EXISTS_FRAGMENTED` | `OutboundRequest`, `BoundaryDispatch`, `RawExternalPayload` and Lazaretto carry authorization and commission IDs. Only Oracle reads a digest-sealed source authorization; deterministic commands synthesize theirs. Lazaretto never rereads the authorization digest. |
| Competent actor and runtime principal | `EXISTS_FRAGMENTED` | Oracle binds Imperator and occupied Augur. Deterministic execution binds no instance, Office, Seat, Manifestation, accountable owner or decision owner. Sortie binds disposable identities only after dispatch. |
| Exact operation, destination and payload | `EXISTS_CANONICALLY` | For the in-memory deterministic call, `DeterministicBoundaryExecutor` enforces deterministic mode, exact payload SHA-256, one destination, credential commission/operation/capability membership and transport support before credential use. This does not make the source authorization durable. |
| Tool and capability scope | `EXISTS_FRAGMENTED` | Request, dispatch, manifest, raw payload and Lazaretto carry lists; governed sortie registry refuses unavailable/ambiguous tools. List membership does not prove a competent capability issuer or durable consumption. |
| Credential-reference scope and secret exclusion | `EXISTS_FRAGMENTED` | Opaque `CredentialCapability` binds credential reference, commission, operation, expiry and max uses; tests prove forged, cross-broker and expired capabilities fail and metadata/admitted provenance exclude the secret. Destination, request, execution and payload are not capability fields; use state is process-local. |
| Expiry and freshness | `EXISTS_FRAGMENTED` | Request, capability, manifest and sortie cognition authority have expiry checks. There is no persisted issued-at/freshness/revalidation record at Iron Gate, and no generalized supersession or revocation check. |
| Single-use execution identity | `ABSENT` | `IronGate::dispatch()` creates a fresh random `requestId.attempt.*` on every call. It is neither persisted nor a winner for request, authorization or capability. Process-local capability use is not a durable execution claim. |
| Replay identity | `ABSENT` | No complete deterministic fingerprint binds source authorization digest, request, payload, destination, operation, credential capability, return contract and provider idempotency key. Re-dispatch creates a new execution ID. |
| Lock scope and order | `ABSENT` | Deterministic dispatch, capability consumption, transport, raw construction and Lazaretto admission acquire no cross-process lock. Consequently there is no canonical lock order or competing-path winner. |
| Pre-I/O claim | `ABSENT` | The deterministic lane increments process-local capability use before invoking the transport, but writes no durable claim. The sortie cognition journal reserves by authority digest; the sortie tool and outer execution do not. |
| Exact effect-start point | `EXISTS_FRAGMENTED` | Deterministic effect may begin at the transport `file_get_contents()` call after capability use increments. Sortie tool effect begins at its GET `file_get_contents()`; sortie provider effect begins after journal transition to `INVOCATION_IN_FLIGHT`. No shared execution checkpoint names these points. |
| Provider idempotency | `ABSENT` | AgentMail and generic bearer transports send no runtime idempotency key and store no provider contract proving duplicate suppression. A sortie provider receives stable `sortie:<authority digest>`, but this does not govern deterministic execution or tool I/O. |
| Deterministic unknown-outcome rule | `ABSENT` | A timeout, process death or thrown transport error can follow provider acceptance. Current errors describe absence/rejection of a response, but cannot prove the effect did not occur. There is no durable `UNKNOWN_REPLAY_PROHIBITED` state. |
| Sortie cognition unknown-outcome rule | `EXISTS_CANONICALLY` | `BrokeredSortieCognitionProviderInvoker` reserves a CAS journal before provider I/O, sets `automatic_replay_permitted=false`, transitions to in-flight, and seals response identity or `PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED`. The rule is exact for provider cognition only. |
| Raw provider receipt | `EXISTS_FRAGMENTED` | `TransportResult` and `RawExternalPayload` preserve content digest, sources and observed/received times; AgentMail requires message/thread IDs. The raw shape omits request ID, authorization digest, operation, destination, payload digest, credential reference, provider idempotency key and explicit attempted/accepted/unknown disposition. |
| Expected-return validation | `ABSENT` | Lazaretto copies `BoundaryDispatch::expectedReturnContract` into provenance but does not validate response bytes against it. AgentMail transport performs one provider-specific shape check before Lazaretto. |
| Lazaretto lineage and scope admission | `EXISTS_CANONICALLY` | For one in-memory dispatch/payload pair, Lazaretto validates execution, commission, authorization, declared tool/capability use and exact sortie identities; tests prove mismatch refusal. This is admission validation, not durable receipt binding. |
| Outbound raw/admitted persistence | `ABSENT` | `RawExternalPayload` and `AdmittedExternalArtifact` are value objects only. Unlike inbound webhook artifacts, outbound returns have no store. Sortie output is a temporary file deleted by the parent launcher. |
| Partial-write recovery | `ABSENT` | Crashes after capability consumption, after provider acceptance, after response receipt, after raw construction or after admission leave no deterministic forward-recovery checkpoint. Random payload/artifact timestamps and IDs prevent exact reconstruction. |
| Downstream Oracle persistence | `EXISTS_FRAGMENTED` | Oracle writes admitted evidence and then a separate authority-consumption receipt. A crash can leave evidence without receipt; receipt discovery and writes share no authority lock, and existing-file replay does not compare conflicting content. |
| Read-only reconstruction | `ABSENT` | No durable chain reconstructs source decision → execution winner → credential capability use → provider outcome → raw receipt → Lazaretto admission. Console output and application logs are not native authority evidence. |
| Concurrency proof | `ABSENT` | No deterministic or outer-sortie two-process execution/receipt contention test exists. Existing tests are single-process scope and replay checks. |
| Fault proof | `ABSENT` | No injected crash proof covers any deterministic perimeter commit boundary. Sortie cognition encodes a journal rule but has no associated boundary fault matrix proving complete raw-return recovery. |
| Tamper proof | `EXISTS_FRAGMENTED` | Payload digests, raw-content digests, sealed sortie manifest digests, webhook signatures and Oracle record digests have tests. `BoundaryDispatch`, admitted outbound artifacts and deterministic receipts are not sealed durable records. |
| Inbound webhook admission and persistence | `DEFERRED_BOUNDARY` | Svix verification, five-minute replay window, hostile-content `authority=none`, and exclusive-create inbound persistence exist. This is a competing inbound boundary, not an outbound receipt store or permission to expand Lazaretto. |
| Revocation, propagation, telemetry, reassessment, containment and incidents | `DEFERRED_BOUNDARY` | These remain separately closed. Preparation records their absence without implementing them. |

## Consumer and competing-path inventory

| Consumer/path | Effect, partial writes and recovery | Proof actually present | Posture |
| --- | --- | --- | --- |
| `DeterministicBoundaryExecutor` + `BearerJsonPostTransport` | Validates exact in-memory scope, consumes capability in process, then performs POST. Any death from effect start through admission loses the outcome. Generic HTTP carries no provider acceptance identity beyond status/source strings. | Payload tamper, credential forgery/cross-broker/expiry/one-use, HTTPS refusal and secret exclusion; no concurrency or crash proof. | `PRE_IO_UNCLAIMED`, `UNKNOWN_OUTCOME_UNSAFE`, `RECEIPT_RECOVERY_INCOMPLETE` |
| `DeterministicBoundaryExecutor` + `AgentMailEmailTransport` | Same lane; requires AgentMail message/thread IDs after 2xx, but sends no idempotency key and persists neither attempt nor receipt. | Provider destination, payload-recipient and credential preflight tests; live command is explicitly side-effecting and is not a fault proof. | `PRE_IO_UNCLAIMED`, `UNKNOWN_OUTCOME_UNSAFE`, `RECEIPT_RECOVERY_INCOMPLETE` |
| `IronGate::dispatch()` | Revalidates only request expiry and randomly creates execution/sortie identities. Repeated callers are competing winners with unrelated IDs. | Mode separation and disposable-manifest scope tests only. | `PRE_IO_UNCLAIMED` |
| `Lazaretto::admit()` | Correctly validates one live dispatch and payload, then returns a time-derived in-memory artifact. It copies rather than validates the return contract. | Exact lineage, undeclared scope and sortie/non-sortie checks; no persistence, contention or reconstruction proof. | `RECEIPT_RECOVERY_INCOMPLETE` |
| `SortieBoundaryExecutor` / `SortieProcessLauncher` / `OneShotSortieRunner` | Random outer identities, temporary manifest/output, child-local lifecycle and `finally` retirement. Process death can erase lifecycle and raw output; parent cleanup removes staging. | Manifest tamper, process isolation, one-shot retirement and exact returned lineage tests. | `DEFERRED_COMPETING_BOUNDARY`, `RECEIPT_RECOVERY_INCOMPLETE` |
| `BrokeredSortieCognitionProviderInvoker` | Durable CAS reservation precedes provider cognition; stable provider identity and fail-stopped unknown outcome exist. Response bytes are not stored in the journal, and outer raw/admission recovery remains absent. | Configuration and authority binding tests; no complete provider-journal concurrency/fault/reconstruction suite in this boundary. | `JOURNALED_EFFECT_FRAGMENTED`, `DEFERRED_COMPETING_BOUNDARY` |
| `HttpGetSortieToolExecutor` | Boolean process-local one-use flag is set immediately before GET. No journal or idempotency rule exists; returned evidence is lost on child failure. | Exact one-tool/one-capability/one-destination validation and registry tests; no fault/concurrency proof. | `PRE_IO_UNCLAIMED`, `RECEIPT_RECOVERY_INCOMPLETE`, `DEFERRED_COMPETING_BOUNDARY` |
| `OracleResearchEvidenceAdmissionService` | Downstream sortie evidence admission can consume the research authority, but evidence and receipt are separate direct writes after ephemeral Lazaretto admission. | Exact lineage, return shape, claim scope, replay of same artifact and no-selection-authority tests; no race/fault proof. | `RECEIPT_RECOVERY_INCOMPLETE`, `DEFERRED_COMPETING_BOUNDARY` |
| `InboundLazaretto` / `InboundArtifactStore` | Verified inbound event handling has exclusive-create provider-message idempotency and `authority=none`; it does not correspond to an outbound execution or provider receipt. | Signature tamper/staleness, hostile content, malformed body and persist-once tests. | `DEFERRED_COMPETING_BOUNDARY` |

No inventoried consumer qualifies as `DURABLE_RECEIPT_BOUND`.

## Failure and reconstruction matrix

| Failure point | Current observable state | Safe automatic retry? | Exact limit |
| --- | --- | --- | --- |
| Before dispatch | No durable record | Only by caller policy, not proven | No winner or replay identity exists. |
| After dispatch, before capability use | No durable record | Not provable under concurrency | A second dispatch receives a different execution ID. |
| After capability use, before transport call | Process-local use increment only | Same object refuses; restart forgets | Pre-I/O failure is indistinguishable after process death. |
| During/after provider effect, before response | No deterministic journal | **No** | Provider may have accepted the effect; outcome is unknown. |
| After response, before raw payload | Provider response only in stack memory | **No** | Receipt and provider acceptance identity can be lost. |
| After raw payload, before Lazaretto | In-memory raw object only | **No** | No store or recovery scan exists. |
| After Lazaretto admission | In-memory admitted artifact only | **No** | Admission ID depends on admission time and is not persisted. |
| Sortie cognition in flight | Durable provider journal | **No** | Automatic replay is prohibited, but response bytes/raw/admission cannot be reconstructed from the journal. |
| Oracle evidence written, receipt absent | Evidence file may survive | Not automatically | No checkpoint/authority lock deterministically completes the receipt. |

## Smallest safe migration sequence

No step is authorized by this inventory.

1. Define separately versioned **deterministic execution-claim** and **deterministic receipt-binding**
   contracts. Preserve `OutboundRequest` compatibility, but require native source authorization,
   competent actor/holder, complete replay fingerprint, one execution identity, exact effect-start
   state, and an immutable relation to the existing raw/admitted shapes. Do not include network I/O
   inside an internal rollback transaction.
2. Prove the provider prerequisite before adopting a consumer: one exact deterministic operation
   must have a stable provider idempotency key or an explicit non-replayable unknown-outcome rule.
   AgentMail `email.send` is the narrowest candidate only after that contract is verified; current
   code does not prove it.
3. Adopt one deterministic operation only: claim the exact source authority and credential-
   capability identity durably before credential resolution, mark effect start before the transport
   call, and prohibit automatic replay from any in-flight/unknown state. Credential secret material
   and credential-platform redesign remain outside the record.
4. Persist the raw provider response and a separately sealed receipt-binding result, then admit it
   through the existing Lazaretto validation without expanding sanitization or trust policy.
   Recovery must complete forward from a sealed response and must never reinvoke the provider.
5. Prove two competing executions, crash-before-I/O, crash-at-effect-start, provider-unknown,
   crash-after-response, crash-after-raw-persist, admission conflict, receipt tamper, exact replay,
   changed authoritative input and secret exclusion. Every proof must distinguish
   `attempted`, `accepted`, `rejected` and `unknown` without inventing provider knowledge.
6. Add read-only reconstruction from the source authorization through the execution claim,
   capability-use reference, provider outcome, raw receipt and admitted artifact.
7. Assess the sortie lane separately. Preserve its provider journal and stable idempotency identity,
   but do not merge tool I/O, cognition, child lifecycle, Oracle admission or deterministic receipt
   semantics merely because they share Iron Gate and Lazaretto class names.

## Preserved boundary

Preparation changed no runtime class, schema, issuer, consumer, command, request, dispatch,
credential, transport, provider journal, raw payload, Lazaretto admission, persistence, Oracle
evidence, sortie, tool, lifecycle or external-I/O behavior. It opened no Iron Gate, Lazaretto,
sortie, credential-platform, revocation, propagation, telemetry, reassessment, containment or
incident boundary. Delegate Mission remains terminal at Step 69.
