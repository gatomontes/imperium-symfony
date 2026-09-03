# Canonical Native Effect Corridor Activation — Preparation inventory v1

`CANONICAL_NATIVE_EFFECT_CORRIDOR_ACTIVATION_PREPARATION_BATCH_0_COMPLETE`
`PREPARATION_ONLY_NO_EXECUTABLE_EFFECT_AUTHORITY`
`LIVE_EFFECT_AUTHORITY_ABSENT`

Baseline: clean synchronized `main` at
`3f5d53ce8dfff0a74702b32500696373823b5e41`.

## Decision

Imperium has a canonical, current, read-only native transition interpretation and
several separately proved pre-provider/evidence corridors. It does **not** have
one executable authority joining a current native root to a provider effect.
Historical effect journals, callbacks, raw results and receipts belong to the
unbound deterministic proof corridor. They are evidence, not permission to use a
committed native root.

The first lawful implementation must therefore add a new effect authority and a
new exact-root consumer. It must not reinterpret the consumed native-transition
authority, the v3 pre-effect admission, an inactive descriptor, a historical
execution claim, a stationary credential proof, or an archived receipt as effect
authority.

## Surface inventory

| ID | Surface | Classification | Current fact and required disposition |
| --- | --- | --- | --- |
| N01 | Exact native operation root | `EXISTS_CANONICALLY` | `sha256(canonical-json({instance,binding,operation}))`, derived by `NativeBindingReader::root`; no caller-selected projection. |
| N02 | Native transition authority | `EXISTS_CANONICALLY` | Signed Root act -> native principal -> successor -> native decision/custody/authority -> seven-record pre-effect commit. It is consumed by the transition and grants no effect. |
| N03 | Current native receipt/inspection | `EXISTS_CANONICALLY` | `COMMITTED_CURRENT`, `COMMITTED_NOT_CURRENT`, `BOUND_INACTIVE`, `INCOMPLETE`, `CORRUPT`; always read-only, effect false, retry false. |
| N04 | Snapshot consistency | `EXISTS_CANONICALLY` | Two-attempt optimistic manifest covers claim, issuance, journal, native/trust/legacy and native source trees. It is observation, not a lease or transferable admission. |
| N05 | Native-root-to-effect authority | `ABSENT` | No record binds current native receipt/root to one destination, payload, effect replay identity, provider contract, credential family and expiry. |
| N06 | Competent effect decision and issuer | `ABSENT` | Proposed: an Imperator decision plus separate issuer, addressed to one fixed La Cortine same-process effect consumer. Operator prose or the transition issuer cannot substitute. |
| N07 | Native effect consumer | `ABSENT` | No production service accepts a native root/receipt plus effect authority and reaches a credential/provider callback. |
| N08 | Native effect winner/start aggregate | `ABSENT` | No atomic record jointly consumes effect authority and commits effect-start for the native root. |
| N09 | Native effect replay identity | `ABSENT` | Proposed identity is the digest of native root/receipt, authority, operation, destination, payload digest, provider, adapter, credential family, return contract and provider idempotency key digest. |
| N10 | Native transition replay | `EXISTS_CANONICALLY` | Committed transition is read-only; any journal without commit is `UNKNOWN_REPLAY_PROHIBITED`. It must remain distinct from message/effect replay. |
| E01 | Historical deterministic execution claim | `EXISTS_CANONICALLY` | Exact authorization/request/execution/capability/idempotency evidence exists for `LEGACY_UNBOUND`; it cannot consume a native root. |
| E02 | Historical effect-start journal | `EXISTS_CANONICALLY` | Durable pre-callback journal exists, but its current native guard admits only `LEGACY_UNBOUND`. It conservatively says external I/O may have started. |
| E03 | Journal-bound broker | `EXISTS_CANONICALLY` | Validates journal/claim/capability, then refuses any bound native classification before admission, credential attempt, consumption or callback. |
| E04 | Provider callback/idempotency adapter | `EXISTS_CANONICALLY` | Exact AgentMail endpoint/payload/idempotency header and callback-start checkpoint exist only behind the legacy-unbound broker. |
| E05 | Raw callback response provenance | `EXISTS_CANONICALLY` | Callback-bound response envelope, content digest/bytes and observed/received times exist. This proves local callback lineage, not remote cryptographic authorship. |
| E06 | Raw result -> deterministic Lazaretto receipt | `EXISTS_CANONICALLY` | Accepted/rejected raw result, exact AgentMail return validation, receipt binding and read-only reconstruction exist for the historical corridor. |
| E07 | Provider-neutral raw evidence -> normalization | `EXISTS_CANONICALLY` | Separate archival chain exists and is non-authorizing; normalized reconstruction has no outgoing effect edge. |
| E08 | Generic Iron Gate executor | `EXISTS_CANONICALLY` | Explicitly rejects `email.send` because `OutboundRequest` has no binding root, before dispatch/credential use. |
| E09 | Direct AgentMail transport | `EXISTS_CANONICALLY` | Validates shape then always refuses `CCI_EMAIL_TRANSPORT_HAS_NO_BINDING_ROOT`; no network body remains. |
| E10 | Retired AgentMail command | `EXISTS_CANONICALLY` | Default action refuses; `--inspect-claim` is read-only and can only report interpretation. |
| E11 | Native transition command | `EXISTS_CANONICALLY` | Accepts an existing native transition authority ID and returns pre-effect receipt; it has no payload, destination, credential or provider callback. |
| A01 | One-effect authority schema | `ABSENT` | Batch 1 must define it without a producer or consumer. Required scope is listed below. |
| A02 | Single-use effect authority custody | `ABSENT` | No holder/custodian/delivery record exists for a native effect authority. Durable exact-consumer loading is the minimum acceptable posture. |
| A03 | Authority revocation/cancellation | `EXISTS_FRAGMENTED` | Native principal revocation and historical activation revocation exist, but no native-effect authority revocation/cancellation winner exists. |
| A04 | Authority expiry | `EXISTS_FRAGMENTED` | Constituent expiry is validated in native and historical lanes; no transitive native-effect expiry has been defined. |
| C01 | Environment capability object | `EXISTS_CANONICALLY` | Same-object, same-broker, process-local issuance/consumption; contains the clear credential reference privately and cannot cross processes. |
| C02 | Cross-process capability custody | `ABSENT` | Earlier campaign terminally refused it. Metadata serialization/reconstruction or reissuance is prohibited. |
| C03 | Stationary same-process credential resolution | `EXISTS_CANONICALLY` | Combined v2 admission can read `AGENTMAIL_API_KEY` callback-locally and persist only a secret-free proof, but native roots are excluded and no provider call follows. |
| C04 | Native credential-family join | `ABSENT` | The native successor names provider/binding/boundary; no native-effect record binds the exact credential family and effect authority to the same process. |
| C05 | Durable capability consumption | `ABSENT` | `EnvironmentCredentialBroker` use count is memory-only. It cannot participate in a crash-durable atomic file transaction. |
| C06 | Zero-plaintext persistence | `EXISTS_CANONICALLY` | Current bounded records persist opaque IDs/digests only; tests scan for fixture secrets and environment names. New records must preserve this invariant. |
| R01 | Duplicate/contending native effect submissions | `ABSENT` | No effect-root winner exists. Required result: one immutable winner, exact replay returns it read-only, all changed tuples conflict. |
| R02 | Expiry before first winner | `EXISTS_FRAGMENTED` | Proven in source lanes; new consumer must revalidate all constituents immediately before publication. |
| R03 | Revocation/cancellation before first winner | `ABSENT` | Must be mutually exclusive with first effect admission under the same effect-authority scope. |
| R04 | Cancellation after winner | `ABSENT` | Cannot undo consumption or imply provider cancellation. It may only request governed reconciliation/containment. |
| R05 | Provider idempotency key | `EXISTS_FRAGMENTED` | Stable key/header and local replay identity exist; AgentMail retention, collision domain and duplicate semantics have no admitted operational evidence. |
| R06 | Safe automatic retry | `ABSENT` | Correctly absent after the irreversible winner. Only pre-winner validation failures may be retried with the same inputs. |
| R07 | Sealed-response forward recovery | `EXISTS_CANONICALLY` | Historical corridor can finish raw result/receipt from an already sealed exact response without reinvocation. Native join is absent. |
| R08 | Unknown-outcome reconciliation | `EXISTS_FRAGMENTED` | `UNKNOWN_REPLAY_PROHIBITED` and recovery assessment exist; no native-effect reconciliation decision/result exists. |
| B01 | Legacy descriptor readers | `EXISTS_CANONICALLY` | D01-D10 and A01/A02 are guarded under the native outer lock before cached return, consumption, credential or publication cuts. D11 is archival only. |
| B02 | Direct/generic/container bypass refusal | `EXISTS_CANONICALLY` | Real container/Console tests prove broker, executor, direct transport, encoder and stationary legacy paths refuse the native root. |
| B03 | Fixture-created authority risk | `EXISTS_FRAGMENTED` | Synthetic Root signatures, authorities, capabilities, callbacks and provider responses prove mechanics only. They are not production provenance. |
| B04 | Service-container native-effect wiring | `ABSENT` | No effect consumer is wired; adding it is expressly outside Batch 0. |
| L01 | Disposable live-trial definition | `EXISTS_FRAGMENTED` | Campaign names an exact one-effect trial and marker, but destination, payload, provider evidence contract, retention owner and sanitation manifest are not approved. |
| L02 | Credential-adjacent sanitation | `EXISTS_FRAGMENTED` | Existing evidence rules exclude secrets/env dumps/private payloads; a native-effect allowlist, recursive scan and deletion/retention procedure remain to be written. |
| H01 | Cooperative single-host root | `EXISTS_CANONICALLY` | Current proof assumes PHP, local filesystem, `flock`, atomic rename and cooperative trusted writers on one physical root. |
| H02 | Physical power-loss durability | `DEFERRED_BOUNDARY` | Directory fsync, device write ordering and corruption recovery are unproved. |
| H03 | Multi-host/distributed ownership | `DEFERRED_BOUNDARY` | No consensus, fencing, split-brain prevention or network-filesystem guarantee. |
| H04 | Hostile-writer non-forgeability | `DEFERRED_BOUNDARY` | Unkeyed digests and public-root validation do not defeat a hostile writer controlling the trusted root. |
| H05 | Provider deduplication/authorship | `DEFERRED_BOUNDARY` | Provider idempotency retention and cryptographic response authorship are unproved. |
| H06 | Clock authority | `EXISTS_FRAGMENTED` | UTC timestamps and expiry checks exist, but wall-clock rollback/skew protection and a trusted clock source are not proved. |

## Proposed one-effect authority

The first contract should name an immutable `CanonicalNativeEffectAuthority`:

- issuer: a separate competent Imperator effect-decision/issuance service;
- holder/consumer: one exact La Cortine same-process executor principal and generation;
- source: exact native root, committed transition digest, transition receipt digest,
  current successor and v3 admission;
- effect: `email.send`, one destination, payload digest, request fingerprint,
  expected return contract and provider-idempotency-key digest;
- provider: exact provider, adapter/version, binding descriptor, credential family,
  assurance admission and execution boundary;
- lifecycle: effective time, transitive expiry, revocation/cancellation reference,
  single-use, non-continuing authority and one effect-winner scope.

The issuer may decide/issue only. It may not consume, resolve a credential,
construct a callback or invoke a provider. The consumer may consume only an
already-issued exact authority; it may not create or widen one.

## Credential custody decision

The selected safe posture is stationary same-process credential possession.
Durable effect authority is the permission identity. A process-local
`CredentialCapability` may be used only as a callback-local enforcement handle
created and consumed within that same process; it is not durable authority and
must never be serialized, reconstructed, transferred or used for recovery.

If later requirements insist that the process-local capability itself be
atomically/durably consumed with the file-backed effect winner, Batch 3 must stop:
the current environment broker cannot prove that property. It is not lawful to
hide this gap behind a capability ID or credential-reference digest.

## Race and interruption disposition

| Last durable fact | Required classification and permitted action |
| --- | --- |
| No effect winner | Validation failure, expiry, revocation or cancellation: `NOT_STARTED`; same exact request may be submitted only while fresh and authorized. |
| Pending/unpublished effect aggregate | `UNKNOWN_REPLAY_PROHIBITED`; never infer absence from a partial path. Governed inspection/reconciliation only. |
| Effect winner + effect-start committed, credential not resolved | `UNKNOWN_REPLAY_PROHIBITED`; no new winner, no authority/capability reissue, no automatic provider attempt. |
| Credential resolution attempted, callback not durably started | `UNKNOWN_REPLAY_PROHIBITED`; memory cannot prove provider non-execution. |
| Callback start committed, no response | Provider may have acted; automatic retry and capability redelivery are prohibited. |
| Provider response observed but envelope not sealed | Unknown until governed provider reconciliation; no caller-supplied response may repair it. |
| Exact response envelope sealed | Forward-only raw-result, normalization/Lazaretto and receipt construction; no credential read or reinvocation. |
| Accepted/rejected receipt sealed | Read-only reconstruction. Rejection does not grant retry. |

The original uninterrupted winning call may proceed once from its just-committed
effect winner into credential resolution and callback start. If control is lost
after that commit, a later process may inspect or reconcile but may not resume a
provider attempt. This distinguishes one in-call continuation from prohibited
automatic replay.

## Live-trial and evidence gate

Before Batch 7, Batch 6 must freeze an allowlisted package containing only:

1. exact disposable inbox/destination and non-sensitive synthetic message;
2. hashes/references for native root, effect authority, winner, journal, request,
   callback start, response envelope, raw result, Lazaretto admission and receipt;
3. provider status, provider receipt identifiers, UTC times and idempotency
   evidence necessary for verification;
4. a recursive denylist scan for credentials, environment names/values,
   Authorization headers, raw private content, local paths and unrelated state;
5. a private-retention location outside Git plus a separately sanitized package;
6. source commit, PHP/runtime identity, exact command and one-use authorization
   marker; and
7. proof that `git status`, tracked files and sanitized artifacts contain no
   credential-adjacent material.

The trial remains impossible without the exact Batch 7 marker and approved
destination/operation. Provider evidence must still distinguish local callback
lineage from provider-side authorship and idempotency guarantees.

## Batch 0 boundary

No production source, service wiring or runtime state changed. No authority or
capability was issued or consumed. No credential, provider, network, mission,
Iron Gate or Lazaretto operation occurred. Batch 1 is not authorized by this
inventory.
