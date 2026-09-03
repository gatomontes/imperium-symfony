# Native Inspection Snapshot Consistency preparation inventory v1

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_PREPARATION_BATCH_0_COMPLETE`
`OPTIMISTIC_WHOLE_READ_SET_WITH_BOUNDED_REFUSAL_SELECTED`

## Basis, claim boundary and classification rule

Preparation Batch 0 was performed from clean synchronized local `main` at
`aff1017f456b35110d0e64b07cf6e89990d71cc0`. It is documentary only. No
runtime behavior, production service wiring, lock, result classification or
native state was changed.

`EXISTS_CANONICALLY` means the exact reviewed mechanism exists in the current
tree. `EXISTS_FRAGMENTED` means useful pieces exist but do not establish the
required whole-inspection guarantee. `ABSENT` means no such mechanism or promise
was found in the complete traced sources. `DEFERRED_BOUNDARY` is deliberately
outside this campaign's cooperative single-host boundary.

The accepted earlier claim remains bounded pre-effect. `BOUND_INACTIVE`, the
historical v3 `NOT_IMPLEMENTED`, `UNKNOWN_REPLAY_PROHIBITED`, and all false
effect/retry fields remain unchanged. Inspection is not authority, admission,
credential access, recovery, retry or permission to invoke a provider.

## Caller inventory and exclusion posture

| ID | Caller / edge | Lock posture at the inspection edge | Classification | Finding |
| --- | --- | --- | --- | --- |
| C00 | `NativeBindingReader::interpret()` public direct calls | Unlocked unless its caller already owns exclusion | EXISTS_FRAGMENTED | It validates the descriptor, reads journal/commit, invokes reconstruction, and may call `read()`. It has no whole-method snapshot. |
| C01 | `NativeBindingReader::forClaim()` -> `interpret()` | Unlocked for direct/command inspection; inherited lock in authorizing corridors | EXISTS_FRAGMENTED | Claim and binding snapshots are rechecked, but issuance and the complete interpretation read set are outside that envelope. |
| C02 | `NativeBindingReader::forJournal()` -> `forClaim()` | In production called inside adapter `legacy()` | EXISTS_CANONICALLY | Exact stored journal and claim digest are checked. Its consistency comes from the caller's already-held native exclusion, not from `forJournal()` itself. |
| C03 | `NativeBindingReader::read()` -> `NativeReconstructor::reconstruct()` | Held by `NativeConsumer`; unlocked in direct/test use and from unlocked `interpret()` | EXISTS_FRAGMENTED | It performs repeated commit, journal, authority, admission and reconstruction reads without one outer observation boundary. |
| C04 | `NativeReconstructor::reconstruct()` direct and reader calls | Never acquires a lock | EXISTS_FRAGMENTED | Its private before/after snapshot covers native/source/trust/legacy trees and refuses change, but not claim, claim issuance or effect-journal inputs used by enclosing inspection. |
| C05 | `DeterministicJournalBoundCredentialBroker::inspectClaim()` -> `forClaim()` | Unlocked | EXISTS_FRAGMENTED | This is the read-only API used by the command. It can return a classification assembled across more than its claim/binding snapshot. |
| C06 | `AgentMailEmailSendCommand --inspect-claim` -> broker `inspectClaim()` | Unlocked | EXISTS_FRAGMENTED | It displays/codes success from the returned classification. It writes no business state, but currently has no coherent-snapshot proof. |
| C07 | `DeterministicJournalBoundCredentialBroker::invoke()` -> `legacy()` -> `inspectClaim()` | Already holds `native-provider-transition` | EXISTS_CANONICALLY | This is the authorizing journal-broker path. Interpretation occurs before admission, credential attempt/consumption and callback while the existing outer exclusion remains held. It must not be made to reacquire that lock. |
| C08 | `DeterministicEffectStartJournalService::start()` -> `legacy()` -> `forClaim()` | Already holds `native-provider-transition` | EXISTS_CANONICALLY | The nested reader instance does not acquire a second lock; publication remains after interpretation in the same outer scope. |
| C09 | `AgentMailIdempotencyHeaderAdapter::invoke()` -> `legacy()` -> `forJournal()` | Already holds `native-provider-transition`; broker nesting uses process-local legacy depth | EXISTS_CANONICALLY | Direct adapter and broker-nested use both refuse bound native outcomes before callback. |
| C10 | `NativeConsumer::execute()` -> `NativeBindingReader::read()` | Holds `NativeState::locked()` and all declared source/trust locks plus migration locks | EXISTS_CANONICALLY | This read is the authorizing transition's post-publication return, not an unlocked operator inspection. |
| C11 | Ten effect-side legacy services and `ProviderBoundCredentialEligibilityService` / `AgentMailProviderRequestEncoder` | Each enters `NativeBindingReader::legacy()` before old locks, cached returns, publication or credential access | EXISTS_CANONICALLY | These are guarded consumers of `assertLegacy*`, not unlocked classification clients. Their existing bounded pre-effect exclusion is not reopened. |
| C12 | Direct tests in `CanonicalConsumerCorrectionBatch1Test`, Batch 3 and `NativeTransitionBatch3Test` through Batch 7/6A | Unlocked except where the test invokes `NativeConsumer` | EXISTS_CANONICALLY | They prove current classifications and conservative interruption behavior, but one test explicitly observes `INCOMPLETE` while another process holds publication after its journal. They do not prove coherent unlocked inspection. |
| C13 | Any future cache, display, signer, admission process or inter-process handoff of an inspection result | No consuming contract found | ABSENT | The result has no freshness token, observation epoch or authority. Reuse after return is a TOCTOU boundary and must remain non-authorizing. |

The exact effect-side `legacy()` entrypoints are: activation decision, activation
issuance, single-execution activation, single-operation activation issuance,
durable execution-authority issuance, revocation-authority issuance, both
governed admissions, both stationary credential resolvers, credential
eligibility, request encoding, effect-journal start, the journal broker and the
idempotency adapter. They acquire the native outer lock before their own domain
or immutable locks. They are not evidence that unlocked inspection is coherent.

## Complete semantic read set

| ID | Read set / enumeration | Classification | Current coverage and gap |
| --- | --- | --- | --- |
| R00 | Exact deterministic execution-claim JSON | EXISTS_CANONICALLY | `forClaim()` reads it before and after and validates its sealed shape. |
| R01 | Full binding-directory membership and SHA-256, then every candidate descriptor | EXISTS_CANONICALLY | `bindingSnapshot()` detects additions/removals/content changes during `forClaim()`. |
| R02 | Outbound-email authorization-issuance directory membership and matching issuance/embedded authorization | EXISTS_FRAGMENTED | `assertBoundClaim()` enumerates and validates it, but no before/after snapshot covers a concurrent second match or replacement. |
| R03 | Exact deterministic effect-start journal used by `forJournal()` | EXISTS_FRAGMENTED | Exact stored equality and claim digest are checked once; it is outside a whole enclosing snapshot. |
| R04 | Native layout, event-kind directories and exact transition root | EXISTS_FRAGMENTED | Layout is checked and event reads reject pending/corrupt records; outer calls can span multiple stable-but-different layouts. |
| R05 | Transition commit and native journal events | EXISTS_FRAGMENTED | Per-event pending state is fail-closed. `interpret()`, `read()` and reconstruction reread these independently. |
| R06 | Authority aggregate and its decision, custody and authority records | EXISTS_FRAGMENTED | Fully reconstructed and included in the reconstructor snapshot, but only within that inner call. |
| R07 | Successor aggregate, creation target/decision/winner and selected successor | EXISTS_FRAGMENTED | Fully reconstructed with original references, again within the inner snapshot only. |
| R08 | Principal event plus native activation and revocation events | EXISTS_FRAGMENTED | Constitution time, activation, revocation and currentness are checked for the supplied `at`; no outer inspection epoch joins them to claim resolution. |
| R09 | Original principal directory, lifecycle dispositions and higher-generation membership | EXISTS_FRAGMENTED | Full directories are scanned and included in the reconstructor snapshot. Retroactive/coincident publication can invalidate an outer result. |
| R10 | Binding, executor activation, boundary, attestation, production and assurance source records plus competing activation/production memberships | EXISTS_FRAGMENTED | Reference digests and validators are strong; source snapshots are inner-reconstructor only. |
| R11 | Operator Root identity, operationalization seal and migration inventory | EXISTS_FRAGMENTED | Trust is read and snapshotted by reconstruction. Anchor/migration maintenance during outer claim inspection can fracture the result. |
| R12 | Every registered legacy transition directory: retirement pending/commit and absence of grant, authority, journal, commit, refusal and revocation | EXISTS_FRAGMENTED | Reconstruction snapshots registered/visible legacy state; claim/journal wrapping is not part of the same observation. |
| R13 | Directory/file type, symlink rejection and relevant `.json` / `.pending` content hashes | EXISTS_CANONICALLY | Existing snapshots include directories and semantic files, reject symlinks, and intentionally do not make lock files business evidence. |
| R14 | Wall-clock / caller time | EXISTS_CANONICALLY | Inspection accepts one integer `at`; currentness is evaluated against it. `assertLegacy()` separately samples `time()` and is not the proposed inspection contract. |
| R15 | A single manifest spanning R00-R13 for the entire top-level inspection | ABSENT | This is the precise missing coherence boundary. |

The proposed manifest must cover directory membership, entry type and content
digest for the claim, matching-issuance directory, binding directory, exact
effect journal when present, native event tree, all eight `NativeState::SOURCES`,
trust files and registered legacy stores. It must exclude mutex files from the
semantic digest while still rejecting aliases and unexpected semantic entries.

## Publication and mutation inventory

| ID | Mutation / canonical order | Classification | Observation consequence |
| --- | --- | --- | --- |
| P00 | Generic immutable source publication: directory lock -> seal/compare -> sibling temporary write -> rename | EXISTS_CANONICALLY | One record becomes atomically named, not a multi-directory snapshot. Several source producers use additional outer business locks but share the immutable directory lock. |
| P01 | Claim and outbound-email issuance publication | EXISTS_CANONICALLY | Separate immutable records precede inspection and can gain new directory members independently. |
| P02 | Original descriptor publication followed by separate authority-consumption publication | EXISTS_CANONICALLY | Descriptor remains `BOUND_INACTIVE`; the two publications are not one atomic snapshot. |
| P03 | Principal constitute -> separate signed activation or revocation event | EXISTS_CANONICALLY | Each native event is a distinct `TransitionStore` commit under `NativeState::locked()`. |
| P04 | Successor aggregate publication -> authority aggregate publication | EXISTS_CANONICALLY | Separate durable precursor commits; either can be present without the final operation transition. |
| P05 | Native execution: journal pending/write/flush/fsync/rename | EXISTS_CANONICALLY | Journal visibility makes the attempt irreversible and non-retryable even before the final commit. |
| P06 | Registered legacy retirements, in sorted storage identity order, each pending/write/flush/fsync/rename | EXISTS_CANONICALLY | Partial retirement is an interruption state, never absence or success. |
| P07 | Recompute admission at commit time -> final transition pending/write/flush/fsync -> final currentness revalidation -> rename | EXISTS_CANONICALLY | The rename of the transition commit is the sole visibility point for the embedded ordered seven-member write set. |
| P08 | Embedded order: authority consumption, v3 admission, adoption join, source binding transition, successor binding activation, winner, receipt | EXISTS_CANONICALLY | These are one commit body, not seven separately visible files. Original descriptor is never mutated. |
| P09 | Trust-anchor / migration maintenance and source replacement outside reviewed canonical writers | DEFERRED_BOUNDARY | The current proof assumes trusted cooperative host maintenance. ABA replacement, hostile deletion and unregistered legacy locations are not solved by hashes. |
| P10 | Deletion or overwrite API for native committed events | ABSENT | Canonical runtime exposes immutable put/get and no delete/repair. Manual filesystem mutation is outside the cooperative guarantee and must fail closed when observed. |

## Lock graph, nesting and liveness

Canonical order proved by the reviewed bodies is:

`native-provider-transition`
-> sorted `immutable:<sha256(source-or-trust-directory)>` locks
-> sorted registered legacy `domain.lock` locks
-> one native event `domain.lock` at a time.

Effect-side legacy paths use:

`native-provider-transition`
-> their existing business winner/domain lock
-> their immutable/authority locks.

| ID | Lock property | Classification | Finding |
| --- | --- | --- | --- |
| L00 | Native outer exclusion shared by `NativeState::locked()` and `NativeBindingReader::legacy()` | EXISTS_CANONICALLY | Both use the same `AtomicTransition` scope and physical project root. |
| L01 | Source/trust locks sorted before native mutation | EXISTS_CANONICALLY | This excludes cooperative immutable source writers while a native transition validates and publishes. |
| L02 | Registered legacy locks sorted by physical identity | EXISTS_CANONICALLY | `NativeMigration` nests them only after native/source locks and writes retirement without reacquisition. |
| L03 | Process-local legacy reentrancy | EXISTS_CANONICALLY | `NativeBindingReader::$legacyScopes` avoids broker -> adapter reacquisition and clears in `finally`. |
| L04 | `NativeState::locked()` reentrancy across a different `NativeState` instance | ABSENT | Its instance boolean cannot detect the broker's static legacy scope. Wrapping unlocked inspection with it would risk same-process self-blocking when reused inside C07-C09. |
| L05 | Read-only lock acquisition without filesystem mutation | ABSENT | `AtomicTransition::run()` creates the lock directory and opens a persistent `.lock` file. That violates the present no-write inspection promise on an unprimed root. |
| L06 | Bounded wait/timeout/cancellation for `AtomicTransition` | ABSENT | `flock(LOCK_EX)` blocks without timeout. Lock-covered operator inspection can inherit transition/source/migration liveness delays. |
| L07 | Proven reverse edge from a held source/legacy lock to native outer | ABSENT | Reviewed native and corrected legacy paths acquire the native outer first. No canonical inversion was found. |
| L08 | Cross-host/distributed or hostile-writer exclusion | DEFERRED_BOUNDARY | `flock`, realpath and same-host rename are the explicit boundary. Network filesystems, physical power loss and hostile replacement remain unproved. |

Therefore lock-covered linearizable inspection is not the smallest safe change.
It requires a unified reentrant ownership abstraction, read-only lock-file
provisioning policy, timeout/cancellation semantics and proof across every nested
caller. Those are larger than the identified observation defect and carry
liveness and side-effect changes.

## Current documentary promise and TOCTOU inventory

| ID | Surface | Classification | Current promise / correction needed |
| --- | --- | --- | --- |
| T00 | Code says “Authoritative operation interpretation” and “read-only classification” | EXISTS_FRAGMENTED | Authority here means canonical derivation, not a linearizable observation. The wording lacks an explicit consistency level. |
| T01 | Terminal audit says source snapshots detect changes and inspection grants no later right | EXISTS_CANONICALLY | Accurate but incomplete: it does not claim a whole enclosing snapshot and acknowledges no transition lock. |
| T02 | Tests assert byte-for-byte no writes for stable command inspection | EXISTS_CANONICALLY | This promise forbids solving the problem by silently creating inspection lock artifacts. |
| T03 | Linearizability promise | ABSENT | No document or test proves one instant at which every top-level input simultaneously had the returned values. |
| T04 | Whole-read-set snapshot consistency promise | ABSENT | Inner native reconstruction plus claim/binding checks are insufficient for the combined call. |
| T05 | Monotonic classification promise | ABSENT | Expiry/revocation legitimately changes current to noncurrent; interruption may expose incomplete, and stable repair is forbidden. |
| T06 | Bounded best-effort observation | EXISTS_FRAGMENTED | Current behavior is conservative in many races, but neither retry bound nor complete manifest is specified. |
| T07 | Immediate broker use under existing exclusion | EXISTS_CANONICALLY | Classification is consumed before the protected pre-effect cuts. It is not cached as authority. |
| T08 | CLI display/exit code after return | EXISTS_FRAGMENTED | It is inherently stale after the observation completes; output must be described as a historical observation at supplied time, never an ongoing currentness guarantee. |
| T09 | Signing, caching, admission or transfer to another process | ABSENT | No freshness or authorization contract exists. Any future consumer must re-inspect and independently authorize under its own boundary. |

## Selected smallest defensible contract

Select **optimistic coherent snapshot with bounded conservative refusal** for
unlocked read-only inspection. Do not select lock-covered linearizability,
unqualified best effort, or silent indefinite retry.

The minimum later implementation is one shared semantic-manifest routine used at
the outermost `interpret`, `forClaim`, `forJournal`, `read` and direct
`NativeReconstructor::reconstruct` boundary. For each attempt it captures manifest
A, performs the existing derivation with the same caller-supplied `at`, captures
manifest B, and accepts only when A equals B. One internal re-read may be allowed;
after at most two attempts, instability returns/refuses conservatively as
`UNKNOWN_REPLAY_PROHIBITED` through the existing classification/error mapping.
This is an observation reread, not execution retry or recovery authorization.

Under the reviewed cooperative writers, semantic records are append-only and
content-addressed/sealed, publication is rename-based, and irreversible pending
or retirement evidence is included. Equality therefore proves that no canonical
semantic publication crossed the accepted attempt. ABA replacement and hostile
mutation remain `DEFERRED_BOUNDARY`. The routine must be nesting-aware so
`forClaim` -> `interpret` -> `read` -> reconstruction shares one outer attempt
instead of independently accepting several snapshots. When C07-C10 already hold
exclusion, the same derivation may run without reacquiring the production lock;
this preserves the authorizing corridor and avoids deadlock.

The current public shapes are fragmented and must not be silently conflated:
`interpret` returns root, classification, descriptor, receipt, `read_only=true`,
`provider_effect_permitted=false`, `retry_authorized=false`, and
`recovery=UNKNOWN_REPLAY_PROHIBITED`; `forClaim` adds the existing claim identity
fields; `read` returns root, effective status, descriptor and receipt; the direct
reconstructor returns classification, receipt, `read_only=true`,
`execution_authority=false`, `retry_authorized=false`, and
`provider_effect_started=false`. The smallest Batch 1 contract keeps each exact
public projection and every existing classification unchanged, but makes them
projections of one coherent internal observation. It adds no snapshot token,
digest, credential or transferable authority. If proof requires visible
consistency metadata, Batch 1 must justify it before any shape change.

Successful output means “coherent over the declared local read set at the
supplied `at` during one accepted attempt,” not “still current.” Every
non-authorizing projection retains its existing false authority/effect/retry
fields; callers of the lower-level `read` projection acquire no authority from
its absence of those fields.

## Remaining sequence

Five stages remain and none is authorized by this preparation:

1. Batch 1 — define the canonical non-authorizing result and optimistic snapshot contract, including nesting, bounds and refusal semantics.
2. Batch 2 — implement the shared whole-read-set manifest and bounded acceptance/refusal without a new production lock.
3. Batch 3 — execute the separate-process publication, revocation, expiry, interruption, migration and repeated-read proof in the race matrix.
4. Batch 4 — prove real container/CLI wiring, zero authorization transfer, zero credential/provider/effect access and no inspection filesystem mutation.
5. Batch 5 — conduct a separately sequenced terminal Blackquill audit from clean merged Batch 4 `main`.

PHPUnit must run after each subsequently authorized batch. Preparation Batch 0
stops here.
