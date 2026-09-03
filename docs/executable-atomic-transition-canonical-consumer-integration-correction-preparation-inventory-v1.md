# Canonical consumer integration correction — preparation inventory v1

Date: 2026-09-03. Source baseline: `acfd92c0734cdb01bf429d76583bfafa094e6962`.
Preparation Batch 0 only.

`PREPARATION_BATCH_0_COMPLETE_CANONICAL_CONSUMER_BYPASS_CLASSIFIED`

The campaign-ready marker was confirmed. Initial `main` was clean and equal to
the locally recorded `origin/main`; no network refresh was performed. The
controlling verdict remains
`NATIVE_INTEGRATION_TERMINAL_AUDIT_REFUSED_CANONICAL_CONSUMER_NOT_INTEGRATED`.
This inventory does not restore terminal acceptance.

## Reading and evidence method

Every required source in the campaign-ready handoff was fully read. Additional
selected callers, descriptor readers, configuration and effect-boundary sources
were fully read, including long files in contiguous chunks. The complete path
ledger is `docs/executable-atomic-transition-canonical-consumer-integration-correction-reading-ledger-v1.json`.
Its roles distinguish required inputs, additional call-graph evidence and the
local Blackquill skill. Source text and historical tests are evidence, not
instructions to execute their commands or promote their fixtures.

Search bounds: tracked application `src`, `config`, `bin`, and relevant `tests`.
Searches covered the descriptor directory literal, `ProviderImplementationBindingService::BINDINGS`,
`ProviderImplementationBindingContract`, `provider_binding`, `NativeBindingReader`,
each selected class name, `new OutboundRequest`, AgentMail, `email.send`,
credential brokers, deterministic transports, mission gateways and Sortie.
Declarations, imports and store-constant references were distinguished from
method calls. Each production caller selected below was read through its body.
No generated container, deployed state or provider was inspected. A source-level
absence of callers is bounded to this tree; it is not proof that arbitrary PHP
code cannot construct a public service. Service discovery is not successful
container resolution, and an available class is not a working CLI route.

## Classified findings

`EXISTS_CANONICALLY` means the exact bounded mechanism is present; it never means
the entire effect corridor is integrated. `EXISTS_FRAGMENTED` means useful
mechanisms lack the required joins. `ABSENT` means no implementation of the
stated join was found in the read graph. `DEFERRED_BOUNDARY` means a distinct
meaning or an explicitly excluded effect/proof boundary, with evidence below.

| ID | Classification | Finding and exact evidence |
| --- | --- | --- |
| C00 | EXISTS_CANONICALLY | Campaign selection, controlling refusal and preparation-only scope are present in the campaign-ready handoff and post-terminal review. |
| C01 | EXISTS_CANONICALLY | `ProviderImplementationBindingService::bind` produces the original immutable v1 descriptor with `BOUND_INACTIVE`; Armory's `CanonicalEmailSendToolDefinitionService` defines `email.send.v1`, `DEFINED_INACTIVE`, and requires a provider binding. These are bounded documentary/inactive semantics. |
| C02 | EXISTS_FRAGMENTED | `ImperiumNativeProviderTransitionCommand` directly constructs `NativeConsumer(new NativeState(projectDir))`; only `NativeConsumer` uses `NativeBindingReader` in production source. Its successful return proves its own island, not downstream consumption. |
| C03 | EXISTS_CANONICALLY | `NativeBindingReader::root` keys instance, original binding ID and operation; `read` validates committed records and independent reconstruction before active interpretation. The original descriptor remains immutable. |
| C04 | EXISTS_FRAGMENTED | No-commit native reads validate descriptor ID/instance/status but do not compare its scope operation to the supplied operation. The downstream resolver must obtain operation and binding from authoritative request lineage; an arbitrary operation argument cannot establish that join. |
| C05 | ABSENT | No shared unavoidable interpretation is called by the eleven direct descriptor readers D01–D11, the array readers A01–A05, `DeterministicBoundaryExecutor`, or the journal-bound email callback route. |
| C06 | EXISTS_FRAGMENTED | D01–D11 independently interpret the same descriptor store, with different time, status and replay rules. They cannot all be declared unrelated: decision/activation/admission/credential joins explicitly carry the same binding and `email.send` lineage. |
| C07 | EXISTS_FRAGMENTED | `GovernedProviderExecutionAdmissionService::admit` has an authority-keyed winner; combined v2 has an activation-keyed winner shared with revocation. Neither consults native state. Existing-record return precedes full current-lineage validation. |
| C08 | EXISTS_FRAGMENTED | Stationary credential proof v1/v2 reload the descriptor and require `ATTESTED_INERT`, `DEFINED_INERT`, `ACTIVATED_UNCONSUMED` for v2 and `BOUND_INACTIVE`; these are not native principal/successor meanings. Their existing-proof return is historical replay, not a fresh credential grant. |
| C09 | EXISTS_FRAGMENTED | The stationary map accepts `agentmail` plus `agentmail-api-token`; `AgentMailProviderProfile` and `AgentMailCredentialFamilyPolicy` use `agentmail.api-key.v1`. The shared environment variable name does not reconcile family identity or provenance. |
| C10 | EXISTS_CANONICALLY | `AgentMailEmailSendCommand::execute` always returns failure with `GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE`; no provider, binding selection or credential construction remains in its body. |
| C11 | EXISTS_FRAGMENTED | `DeterministicBoundaryExecutor::execute` accepts caller-supplied request, capability and transport. It checks mode, bytes, one destination, capability scope/expiry and transport support, then dispatches Iron Gate and consumes the broker. It has no descriptor/native read or durable transition-root join. |
| C12 | EXISTS_FRAGMENTED | `AgentMailEmailTransport::execute` accepts `email.send`, an exact AgentMail URL, payload and authentication, then uses HTTP streams. No production caller selects this class in the inspected tree. Its public callable boundary still bypasses native interpretation; CLI retirement alone does not close it. |
| C13 | EXISTS_FRAGMENTED | `DeterministicExecutionClaimService::claim` consumes outbound authorization into `CLAIMED_PRE_IO`; `DeterministicEffectStartJournalService::start` commits `EFFECT_STARTED`/`UNKNOWN_REPLAY_PROHIBITED`. Neither requires a binding/native read. This separate durable corridor cannot be omitted merely because it is not a descriptor reader. |
| C14 | EXISTS_FRAGMENTED | `DeterministicJournalBoundCredentialBroker::invoke` reads the claim/journal, commits invocation admission and credential-attempt checkpoints, consumes a capability, and calls `AgentMailIdempotencyHeaderAdapter::invoke` with a wrapped caller callback. No original binding or native admission is joined. |
| C15 | EXISTS_FRAGMENTED | `AgentMailIdempotencyHeaderAdapter` checks authentication, key, endpoint and payload before calling the supplied callback. `AgentMailProviderRequestEncoder` instead consumes a binding array and returns a transient request. No call joins the two adapters or connects the latter to `AgentMailEmailTransport`. |
| C16 | EXISTS_FRAGMENTED | The provider-neutral evidence chain and old deterministic receipt chain are separate. Their reconstructors prove their own stored edges, not native currentness, and must not become execution/retry authority. See E05 and E06. |
| C17 | ABSENT | `OutboundRequest` contains request/authorization/commission/operation/destinations/tools/capabilities/bytes/return contract/expiry, but no instance or binding reference. Its digest is only required to be nonempty. No authoritative mapping from this object to a unique native operation root exists. |
| C18 | EXISTS_FRAGMENTED | `IronGate::dispatch` validates request expiry and generates a random attempt identity. It does not load authority, descriptor, native transition or durable replay winner. `Lazaretto::admit` checks dispatch/payload lineage and returns an artifact object; this is not native receipt reconstruction. |
| C19 | EXISTS_FRAGMENTED | Default `App` discovery/autowiring includes the historical services and commands but excludes `ProviderTransition/`. No explicit native-reader alias joins the old consumers. The native command bypasses this exclusion by direct construction. |
| C20 | ABSENT | Successful real Kernel/container/Application proof for an established consumer reaching native interpretation is absent from the required Batch 7/6A tests; those construct services directly. Preparation adds documentary graph tests only, not that missing executable proof. |
| C21 | EXISTS_CANONICALLY | `NativeReconstructor::reconstruct` is read-only: `ABSENT`, `COMMITTED`, `COMMITTED_NOT_CURRENT`, or `UNKNOWN_REPLAY_PROHIBITED`; historical receipt/current authority are distinct. `NativeBindingReader` requires current validity rather than returning the archival noncurrent classification as permission. |
| C22 | EXISTS_FRAGMENTED | Native lock/source locks, transition event publication and registered pinned-store retirement exist; historical D/E paths use different locks or none. They do not participate in native decision/currentness exclusion. |
| C23 | EXISTS_CANONICALLY | `NativeMigration` only retires explicitly inventoried empty pinned-grant directories; `TransitionAuthority::grant` checks retirement via `TransitionStore::assertNotRetired`. This does not retire D01–D11 or the old email journal store. |
| C24 | DEFERRED_BOUNDARY | DeepSeek Delegate, Legate and operational mission calls load model/seat/claim records, invoke `deepseek.model.invoke`, and use separate claim/journal identities. Their exact graph is E08; they are not original email descriptor readers. No global meaning of “binding” is widened. |
| C25 | DEFERRED_BOUNDARY | Sortie uses explicit Sortie mode, manifest execution/authorization identities and a `sortie.deepseek.model.invoke` broker operation; its only configured tool executor is `http.get`. E09 proves why this is distinct, while preserving its own effect risks and replay obligations. |
| C26 | DEFERRED_BOUNDARY | `BearerJsonPostTransport` supports only `http.post.json`; the inbound AgentMail controller verifies inbound evidence and persists once. Neither selects or interprets an outbound email descriptor. No cross-operation reclassification is authorized. |
| C27 | ABSENT | Acyclic authoritative request-to-binding-to-native-root resolution, reused by every applicable pre-effect consumer and independently reconstructed for evidence, is the smallest missing integration join. The proposed sequence below adds no new transition/root/grant producer. |
| C28 | ABSENT | Rejection proof at established boundaries for missing/ambiguous mapping, wrong operation/instance/binding, raw-array bypass, current-to-revoked races and stale replay is missing. Mere class-name searches or command self-consumption cannot supply it. |
| C29 | DEFERRED_BOUNDARY | Provider effect, live credentials/capabilities, Root signing/provisioning, authority issuance/consumption, transition writes, descriptor mutation, retry, Iron Gate and Lazaretto are excluded from this batch. Historical v3 remains `NOT_IMPLEMENTED`; native pre-effect result vocabulary does not grant these effects. |
| C30 | EXISTS_FRAGMENTED | Local cooperative `flock`, immutable record locks and pending-file/rename publication exist. They do not prove distributed locking, hostile-writer safety, physical power-loss durability or cross-process capability transfer. No new platform claim is made. |

## Exact direct descriptor-reader inventory

All eleven read `ProviderImplementationBindingService::BINDINGS` through
`RecordReferenceValidator::read/resolve`. `resolve` checks ID, digest and optional
identity field, **not reference schema**; callers supply differing extra checks.
None calls `NativeBindingReader`. “No method caller” below excludes imports,
constant references and fixture/test construction; it does not mean inaccessible
PHP. D01–D11 are discovered by default `App` services, without a native dependency.

| ID | Reader entrypoint | Classification | Descriptor interpretation and exact operation/root | Production caller / downstream record consumer |
| --- | --- | --- | --- | --- |
| D01 | `ProviderBindingActivationDecisionService::decide()` | EXISTS_FRAGMENTED | Claim ID + binding ID; inactive/intact/current descriptor; authorization target and operation equal claim. Consumes deterministic caller authority; decision derives from claim/binding digests and disposition. | No method caller. D02 reads its decision store. |
| D02 | `ProviderBindingActivationIssuanceService::issue()` | EXISTS_FRAGMENTED | Reloads decision's claim/binding; requires inactive/current and exact claim authorization/operation. Caller authority is consumed before these reloads. Issuance lock keys decision issuance authority. | No method caller. D03 scans embedded issued authorities; stranded-disposition service reads issuance. |
| D03 | `SingleExecutionProviderBindingActivationService::activate()` | EXISTS_FRAGMENTED | Resolves embedded activation authority's claim/binding; inactive plus claim/descriptor expiry, request operation/destination/execution identity. Lock keys activation authority; original descriptor unchanged. | No method caller. `CrossProcessCapabilityCustodyFeasibilityService::assess` and `StrandedActivationArtifactDispositionService::quarantineExpiredLease` read its activation store. Neither invokes provider. |
| D04 | `SingleOperationProviderBindingActivationIssuanceService::activate()` | EXISTS_FRAGMENTED | Decision/candidate refs, inactive descriptor, inert principal/boundary, request operation equals binding and competence; exact provider/adapter, authorization, policy and assurance joins. Consumption/activation/issuance are separate writes under issuance-authority lock. | No method caller. D05/D06/D07/D09/D10 and revocation winner read activation records. |
| D05 | `DurableProviderExecutionAuthorityIssuanceService::issue()` | EXISTS_FRAGMENTED | Candidate binding resolves inactive; decision, activation, principal, request and validity joins. Authority source is old decision, not native authority. Issuance permission is its lock root. | No method caller. D06/D07/D08/D09 read issued authority records. |
| D06 | `GovernedProviderExecutionAdmissionService::admit()` | EXISTS_FRAGMENTED | Authority input; old activation/inert principal/boundary/inactive binding current windows and exact request lineage. Winner ID hashes authority ID + digest; existing admission returned before fresh lineage. | No method caller. D08 reads its v1 admission store. |
| D07 | `GovernedProviderExecutionCombinedAdmissionService::admit()` | EXISTS_FRAGMENTED | Same descriptor lineage; v2 winner ID hashes activation ID + digest, lock is `governed-provider-execution-admission:` + activation ID; revocation winner exclusion. Exact existing admission returns before current validation. | No method caller. D09 reads its v2 store; `ProviderBindingActivationRevocationWinnerService::revoke` reads it for exclusion. |
| D08 | `GovernedStationaryCredentialResolutionService::prove()` | EXISTS_FRAGMENTED | V1 admission ID; original binding inactive/unexpired, old principal/authority/boundary. Proof key hashes admission ID + digest. Existing proof is returned before current checks/secret lookup. | No method caller; local nonempty-secret callback only, no transport or provider callback. |
| D09 | `GovernedStationaryCredentialResolutionV2Service::prove()` | EXISTS_FRAGMENTED | V2 admission ID; D08 checks plus activation reference/consumption/current expiry. Same historical proof replay rule, separate v2 store. | No method caller; local nonempty-secret callback only. V1 ID is rejected rather than upgraded. |
| D10 | `ProviderBindingActivationRevocationAuthorityIssuanceService::issue()` | EXISTS_FRAGMENTED | Resolves old activation/principal/binding; checks integrity, instance and activation joins, bounds validity to sources. Does not require descriptor inactive or principal `ATTESTED_INERT`. | No method caller. Revocation winner service reads its authority and old activation; it does not revoke native principal/transition state. |
| D11 | `GovernedToolResultReconstructionService::reconstruct()` | EXISTS_FRAGMENTED | Normalized admission + eligibility; follows normalized result to original binding/tool/auth/decoder and raw evidence. No current descriptor status/expiry requirement. Historical read-only result is not effect admission. | No method caller. Reads evidence/eligibility stores; returns original descriptor as evidence, never native effective status. |

## Array readers missed by directory-only searches

| ID | Entrypoint | Classification | Read/caller and consequence |
| --- | --- | --- | --- |
| A01 | `ProviderBoundCredentialEligibilityService::assess` | EXISTS_FRAGMENTED | Caller supplies binding array + capability; validates digest/schema/inactive/time/operation/family and stores `ELIGIBLE_INACTIVE`. No production method caller. Injected policy has one application implementation, `AgentMailCredentialFamilyPolicy`; no explicit native-reader alias. |
| A02 | `AgentMailProviderRequestEncoder::encode` | EXISTS_FRAGMENTED | Caller binding selects provider/adapter/family/encoder; does not validate descriptor status, digest or current native lineage. Returns transient authentication-bearing request and secret-free evidence. No production method caller; transport does not use it. |
| A03 | `ProviderNeutralRawEvidenceService::preserve` | EXISTS_FRAGMENTED | Caller binding must be inactive with matching digest/tool/auth; raw observation gets immutable evidence ID from binding, claim, content/status. No production method caller. No native currentness. |
| A04 | `ProviderBoundEvidenceNormalizationService::normalize` | EXISTS_FRAGMENTED | Caller binding inactive/intact and raw-evidence join; invokes injected `BoundProviderEvidenceDecoder`. Only application implementation is AgentMail decoder. No production method caller. |
| A05 | `AgentMailProviderEvidenceDecoder::supports/decode` | EXISTS_FRAGMENTED | Called by A04; provider/decoder identity and raw content digest checks, without native/current binding check. It is a decoder, not an admission authority. |

## Established corridors and exact callers

Arrows marked **records** denote persisted references, not function calls. All
code names below resolve to fully read paths in the ledger. None of these routes
was executed in preparation.

| ID | Classification | Entry/call graph, identity, wiring and bypass |
| --- | --- | --- |
| E01 | EXISTS_FRAGMENTED | `bin/console` creates `Application(Kernel)`; default discovery finds native command; command directly constructs native state/consumer; consumer calls native reader. Native namespace is excluded from service discovery. No downstream consumer follows that return. |
| E02 | EXISTS_CANONICALLY | `imperium:email:send-agentmail` -> retired command -> failure. No operation data, descriptor read, provider call or successful execution path. Preserve this refusal. |
| E03 | EXISTS_FRAGMENTED | `imperium:deterministic:smoke` -> direct-new executor/IronGate/Lazaretto/local broker + anonymous fake transport. Request operation is `email.send` despite command class name. This proves a mechanical fixture path only. Executor constructor dependencies are also autowirable, but its transport is a method argument. Real AgentMail transport has no production selector/caller. |
| E04 | EXISTS_FRAGMENTED | Deterministic claim -> **records** effect-start journal -> **records** `DeterministicJournalBoundCredentialBroker::invoke` -> injected credential broker and AgentMail idempotency adapter -> wrapped caller callback. No production method caller invokes claim/start/broker in `src`; classes remain callable services. Broker callback boundary is real code, not evidence of a wired provider transport. |
| E05 | EXISTS_FRAGMENTED | E04 callback -> content/envelope/checkpoints -> **records** `DeterministicRawProviderResultService::seal` -> **records** `DeterministicLazarettoReceiptAdmissionService::admit` -> **records** `DeterministicReceiptReconstructionService::reconstruct`. No production method callers of those three services. Receipt binding here means result-to-claim binding, not provider implementation binding. Original claim/auth/request replay edges are retained. |
| E06 | EXISTS_FRAGMENTED | A03 -> **records passed by caller** A04 -> AgentMail decoder -> **result passed by caller** `NormalizedToolResultAdmissionService::admit` -> **records** D11. A01 eligibility is a separate required D11 input. No production orchestrator connects these producers or the real transport. |
| E07 | EXISTS_FRAGMENTED | Old activation/authority/admission services D01–D10 use the descriptor but no production controller/command composes them. Their individual source/record graph still competes for the exact email meaning. Native migration does not disable these routes. |
| E08 | DEFERRED_BOUNDARY | `imperium:mission:execute-bounded-iteration` -> `BoundedOperationalExecutionService::execute` -> aliased `SymfonyAiOperationalExecutionCognitionGateway` -> operational claim broker -> platform. `imperium:legate:perform-bounded-cognition-turn` -> `LegateBoundedCognitionTurnService::perform` -> aliased Legate gateway -> Legate broker -> platform. `DelegateMissionBoundedCognitionTurnService::execute` has no production method caller; it calls claim service then aliased Delegate gateway -> aliased brokered provider invoker -> claim broker -> platform. These read mission occupancy, model binding/activation and cognition claims, not the email descriptor. Operational claim ID, Delegate invocation claim/idempotency key and Legate activation-derived claim are distinct replay roots. `DeepSeekDelegatePlatformAdapter::OPERATION` is `deepseek.model.invoke`; implementation uses injected `ai.deepseek.client` and Generic Factory. |
| E09 | DEFERRED_BOUNDARY | Sortie smoke commands -> injected `SortieBoundaryExecutor` -> IronGate -> `SortieProcessLauncher` -> `bin/console imperium:sortie:run --env=sortie` -> `OneShotSortieRunner` -> Sortie gateway -> configured `http.get` executor and/or brokered cognition invoker -> DeepSeek platform. Manifest-derived authority digest keys its CAS journal; `sortie.deepseek.model.invoke` is distinct from `email.send`. Default gateway is unavailable; `services_sortie.yaml` installs child command/gateway/tool. `OracleResearchCommissionService::outboundRequest` constructs `external.research` Sortie requests, but has no production caller or automatic dispatch edge. |
| E10 | DEFERRED_BOUNDARY | `imperium:dev:profile-elaboration-smoke` -> `ProfileElaborationSmokeService::run` -> direct-new bounded operational service using injected cognition. It also calls profile/examination cognition gateways. This fixture-producing development path is not native email provenance and cannot be promoted. `BearerJsonPostTransport` is a separate `http.post.json` sink with no production caller. HTTP route `/lacortine/inbound/agentmail` -> webhook verifier -> inbound Lazaretto -> persistOnce; it has no outbound transport edge. |

## State, replay and failure matrix

Each row is `EXISTS_FRAGMENTED` except the explicitly bounded native read-only
mechanism C21. “Unchanged” means the historical path does not observe native
state, **not** that its invocation is authorized.

| Native/source condition | Native reader/reconstruction | D01–D10 and A01–A05 | E03/E04/E05 and D11 |
| --- | --- | --- | --- |
| No attempt, intact inactive original | Reader returns `BOUND_INACTIVE`; reconstruction `ABSENT`. | Historical preconditions remain, including explicit inactive requirements where listed. | E03/E04 do not read descriptor. D11 can reconstruct archived original meaning. |
| Complete current commit | Reader returns `BOUND_ACTIVE_FOR_EXACT_OPERATION` only after complete seven-record verification; reconstruction `COMMITTED`. | Still see unchanged original `BOUND_INACTIVE`, or caller-provided arrays. No native adoption/currentness check. | No native join; current transition receipt does not become provider receipt or callback authority. |
| Valid historical commit, now expired/revoked/noncurrent | Reader fails current validation; reconstruction can return `COMMITTED_NOT_CURRENT` with historical receipt. Broken historical trust may instead be unknown. | Source-local validity checks only. D06–D09 exact existing-record shortcuts can return historical records before fresh checks. | Existing old receipt reconstruction remains historical; no new attempt from it. E03 broker uses its own capability clock; E04 checks journal/capability expiry. |
| Journal/pending event, partial/missing commit or retirement | Native read/reconstruction refuses with unknown where uncertainty is detected. | Native incompleteness is invisible; old admission/credential paths can still evaluate their own sources. | E03/E04 native uncertainty is invisible. Partial old response/checkpoint chain fails old reconstruction rather than authorizing retry. |
| Corrupt source/changed digest/wrong instance or root | Native loaders throw typed NIR/EAT errors or unknown; reconstruction conservatively unknown. Never convert all errors into inactive. | Direct reads may throw JSON/record errors; resolve checks digest; schema/status validation varies. A02/A05 do not establish descriptor integrity. | E03 validates only request/capability/transport inputs; no binding corruption detection. E04 validates own claim/journal. D11 rejects its own broken references. |
| Different operation/binding/instance | Distinct native root; must first prove requested operation belongs to original descriptor. | Existing request, authorization, provider and family joins retain their own meanings. | No “other operation” escape for `email.send`; `http.post.json`, DeepSeek and Sortie remain separate on demonstrated graph edges. |

Native root `R = sha256(CanonicalJson({instance, binding, operation}))` is a
transition root. With `operation = email.send`, it is **not** a unique message,
execution ID, authority ID, request fingerprint or provider idempotency key.
Old deterministic claim winner scope is authorization ID; claim replay
fingerprint also binds request, credential metadata and provider-safety values.
Effect journal keys claim/digest/fingerprint/key; callback admission keys
journal/digest/claim; v1 admission keys authority/digest; v2 keys activation/digest.
These keys must be joined, never replaced by `R` or by a random IronGate attempt.

## Smallest acyclic correction route (proposal, not implementation)

The decision/authority/successor substrate is retained. The missing arrow is
from an **established operation consumer** to authoritative interpretation.
Use the existing deterministic email claim/journal corridor and its actual
`DeterministicJournalBoundCredentialBroker::invoke` consumer as the primary
callback boundary, with checks earlier than `DeterministicEffectStartJournalService::start`.
Do not create another command or wrapper to demonstrate a self-contained success.

1. Derive instance, source authorization and operation from authoritative stored
   request/claim lineage; resolve the exact original descriptor by those joins
   (or an already authoritative exact reference). Missing or multiple candidates
   refuse. A caller-selected binding, destination, hash, optional flag or array
   cannot opt an email request out of native interpretation. Compare descriptor
   scope operation, tool, authorization digest, provider/adapter/family, policy
   and assurance; retain request ID/bytes/destination/execution/replay identity.
2. The shared interpretation reads immutable descriptor plus native transition,
   principal currentness, complete admission/adoption/winner/receipt and
   independent reconstruction. It returns a bounded interpretation, not a live
   credential, authority, effect permit or mutated descriptor. The existing
   reader/reconstructor should be reused/refactored, not wrapped merely to gain
   a canonical name. No decision may depend on the digest of its future
   admission, receipt, callback or consumer output.
3. Make that interpretation mandatory at the existing email pre-effect callers
   and revalidate at their durable decision cuts. D01–D10 must explicitly
   distinguish legacy evidence/authority from current native operation intent;
   same-operation native uncertainty must not fall back to the old inactive
   route. A01–A05 need authoritative context or explicit evidence-only use.
   D11/E05 use archival reconstruction, never live permission. Do not relabel old
   v1/v2 admissions as native v3 or widen inert principal/family constants.
4. Close competing `email.send` ingress in the existing generic executor and
   direct AgentMail transport/callback/encoder seams. Missing authoritative
   context must refuse before IronGate, broker consumption or transport. Retain
   retired command refusal. Preserve unrelated `http.post.json`, cognition,
   Sortie and receipt meanings with explicit operation dispatch and regression
   evidence, not a blanket “legacy” escape or fixture promotion.

Dependency direction: authoritative request/claim -> exact original descriptor
and native stored chain -> interpretation -> established pre-effect decision;
archival evidence -> reconstruction. Interpretation must not call an effect
consumer, issue authority, acquire a write lock just to read, or create an
admission. No effect callback is used as evidence that admission existed earlier.

## Roots, durable fields, cuts and reconstruction obligations

These obligations are C22/C27/C28 `EXISTS_FRAGMENTED`/`ABSENT`; excluded physical
and live-effect proof remains C29/C30 `DEFERRED_BOUNDARY`/`EXISTS_FRAGMENTED`.

- Reuse native `R`, original descriptor reference, committed admission/receipt
  references and old request/authorization/claim/fingerprint identities. No new
  authority root or mutable binding projection is justified. If Batch 1 needs a
  durable join, enumerate its exact producer, fields, schema, consumer and
  recovery rule before adding it. Current old claims/OutboundRequest contain no
  such native join; a nullable default would preserve the bypass.
- Existing native write set remains authority consumption, v3 admission,
  adoption join, source binding transition, successor binding activation,
  winner target, receipt target, atomically published in one transition event.
  New interpretation alone should add zero transition records. The historical
  `NOT_IMPLEMENTED` contract constant and false effect flags stay intact.
- Native order is global transition lock -> sorted immutable source/trust locks
  -> sorted registered pinned-store domain locks -> event publication. Historical
  authority/activation/claim/journal locks differ. Design one acyclic shared
  exclusion order before joining consumers; do not acquire source locks then
  reenter the native global lock. Current read-only reconstruction uses stable
  snapshots, not a write-side lock.
- Cover cuts before/after authoritative mapping, between interpretation and
  existing durable claim/admission/effect-start decisions, revocation/expiry
  during that interval, after replay detection, and source substitution between
  descriptor/commit/reconstruction reads. Any new persisted join adds before
  open, flush, before publish, after publish and orphan/partial reconstruction
  obligations. Native pending/retirement uncertainty must remain
  `UNKNOWN_REPLAY_PROHIBITED`; no cleanup/reset/retry repairs its way to success.
- Old E04 cuts are admission -> credential-attempt -> credential consumption ->
  callback-start -> callback -> response content -> response envelope -> raw
  result -> receipt admission. They exist but are not newly authorized. Tests
  for this correction must stop before live credentials/effects; counters or
  failing test doubles must show these cuts are not reached by refused input.
- Native migration's empty pinned-directory retirement is not a migration of
  old activation, admission, deterministic journal or receipt stores. Inventory
  those roots separately; historical committed evidence remains readable, and
  no live conversion, reseal or revival of an old grant is implied.
- Keep credential bytes, references/capabilities, authentication headers and
  callback closures outside durable interpretation records and test artifacts.
  Existing old formats and profile constants are observed, not authority to
  handle their live values. Do not claim that hashes of configured credentials
  prove native provenance.
- Local Windows path normalization, cooperative same-host `flock`, PHP 8.4+
  syntax, filesystem rename and `fsync` are existing assumptions. No network
  filesystem, hostile writer, physical power-loss or portable capability-custody
  guarantee is established by documentary tests or prior process termination.

## Ordered remaining campaign and acceptance evidence

Four planned stages remain. No Batch 1 implementation is authorized by this
completion. Future operator instructions must respect each boundary.

| Stage | Classification | Smallest deliverable and gate |
| --- | --- | --- |
| Batch 1 — canonical interpretation boundary | ABSENT | Exact authoritative request/descriptor/native-root join and state vocabulary, acyclic dependencies, old/new replay identity mapping, archival/current distinction. Enumerate any necessary new durable fields before implementation. No new wrapper, renamed canonical route or reconstructed grant. |
| Batch 2 — established-consumer integration and bypass closure | ABSENT | Mandatory interpretation in selected existing pre-effect corridor plus D/A/E bypass disposition; explicit container wiring; refuse same-operation missing/ambiguous/unknown native joins; preserve unrelated legacy and retired command behavior. No live effect rollout. |
| Batch 3 — application and adversarial proof | ABSENT | Real Kernel/container and Console Application command discovery/negative path; resolve existing services from the container, not only direct construction. Exercise the existing consumer at its pre-effect decision with disposable state and failing effect doubles. Test every state/root mismatch, ambiguous mapping, raw array and generic-executor bypass, stale proof replay, expiry/revocation race, old v1/v2 family/status mismatch, and source/pending/retirement corruption. Observe zero credential/transport/IronGate/Lazaretto calls. Preserve historical reconstruction and unrelated operation semantics. |
| Batch 4 — separate terminal Blackquill audit | DEFERRED_BOUNDARY | Start separately from clean merged Batch 3 `main`; independently follow actual container/caller/descriptor/effect edges and durable evidence. A newly named command calling its own reader cannot close the campaign. |

PHPUnit must run after each subsequently authorized batch, with fixes for real
failures. Preparation tests check the reading ledger, classified source graph,
descriptor-reader coverage, bypass evidence, wiring exclusions, state/replay
obligations and publication. They do not construct runtime services, bootstrap a
mission, provision/sign Root acts, consume authority, persist transition state,
resolve secrets or call a provider. Full runtime/application proof is still absent.
