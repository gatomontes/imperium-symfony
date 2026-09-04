# Canonical Native Effect Process Custody and Formal Closure Remediation — Preparation inventory v1

`PREPARATION_BATCH_0_COMPLETE_PROCESS_CUSTODY_AND_FORMAL_CLOSURE_GAPS_CLASSIFIED`
`DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Audited baseline: clean local `main` at
`ef69362fd49252e15893af72ca71a3e2abb7a209`. The cached refs `main`,
`origin/main` and `origin/HEAD` resolved to the same commit. No fetch, network
request or external CI lookup was performed.

## Decision

The merged tuple and admitted-provenance corrections remain useful candidate
substrate, but continuation custody is transferable. PHP's default object
serialization restores the issuer's private registry and the capability as one
referential graph; the restored issuer recognizes the restored capability. A
shallow clone of the issuer retains references to the same registered
capability, and a shallow clone of `NativeEffectAdmissionOutcome` retains its
recognized continuation. On a `pcntl_fork` platform the child inherits the same
registry/object graph by copy-on-write and the code checks no actual process
fact. The authority-supplied `execution_boundary.id` is a governance label, not
an OS process-incarnation identity.

`NativeEffectDoubleExecutionService::execute()` also has three acts hidden in
one API. It may execute the first callback, return an existing receipt, or bind
a receipt from a sealed response. The latter two branches occur before
`assertAndConsumeContinuation()`. They correctly avoid provider reinvocation,
but forward mutation has no separate reconciliation authority or claim.

Preparation Batch 0 defines the smallest correction and proof sequence only.
Batch 1 is not authorized.

## Classified surface inventory

| ID | Surface | Classification | Audited fact / required disposition |
| --- | --- | --- | --- |
| S01 | Capability native serialization | `EXISTS_CANONICALLY` | No `__serialize`, `__unserialize`, `__sleep` or `__wakeup` refusal; all readonly public fields serialize and restore. |
| S02 | Issuer native serialization | `EXISTS_CANONICALLY` | Private `issued` and `consumed` arrays serialize and restore. |
| S03 | Issuer + capability graph serialization | `EXISTS_CANONICALLY` | PHP preserves graph references; restored issuer recognizes the restored capability. |
| S04 | Admission outcome graph serialization | `EXISTS_CANONICALLY` | Outcome serializes its admission and continuation; a graph containing issuer + outcome restores recognized custody. |
| S05 | Unserialization fail-closed hook | `ABSENT` | Constructors are bypassed and no wakeup/unserialize rejection exists. |
| C01 | Clone capability alone | `EXISTS_FRAGMENTED` | Clone is allowed but the original issuer does not recognize the cloned object. This narrow refusal does not protect the graph. |
| C02 | Clone issuer | `EXISTS_CANONICALLY` | Default shallow clone copies the registry array with the same capability object; both issuers recognize it. |
| C03 | Clone admission outcome | `EXISTS_CANONICALLY` | Default shallow clone retains the same continuation object; the issuer recognizes the cloned outcome's continuation. |
| C04 | Explicit clone refusal | `ABSENT` | Issuer, capability and outcome define no `__clone` fail-closed behavior. |
| F01 | Linux/Unix `pcntl_fork` inherited memory | `EXISTS_FRAGMENTED` | PHP fork semantics inherit the issuer/object graph; no PID/incarnation check exists. Direct proof is unavailable on this Windows PHP build. |
| F02 | Windows spawned process | `EXISTS_CANONICALLY` | `proc_open` starts independent memory; existing fresh-process tests refuse a hand-built lookalike only because its issuer registry is empty. |
| F03 | Cross-platform process-incarnation source | `ABSENT` | No source records runtime PID plus an untransferable per-incarnation nonce. |
| F04 | Authority execution-boundary label | `EXISTS_CANONICALLY` | `execution_boundary.id` is copied into `processBoundaryId`; it is authority supplied and never compared with `getmypid()`. |
| F05 | PID reuse posture | `ABSENT` | PID alone is not proposed or checked; no nonce/lifetime rule handles reuse. |
| F06 | Process start metadata | `EXISTS_FRAGMENTED` | Linux procfs start ticks/boot id and Windows process creation time are platform options, not portable PHP guarantees or current dependencies. |
| F07 | In-process cryptographic nonce | `EXISTS_FRAGMENTED` | Capability IDs use `random_bytes`, but no issuer/process-incarnation nonce is created and verified. |
| L01 | Issuer lifetime | `EXISTS_FRAGMENTED` | Lifetime is whichever caller retains the manually created issuer; there is no explicit process scope. |
| L02 | Symfony service lifetime | `EXISTS_FRAGMENTED` | `CanonicalNativeEffectCorridor` and `NativeState` are default shared services within one container, but corridor methods create fresh issuers/services. Container lifetime is not process-incarnation proof. |
| L03 | Direct ProviderTransition autowiring | `EXISTS_CANONICALLY` | `config/services.yaml` excludes the namespace; effect services are reached through the auto-discovered corridor or test construction. |
| L04 | Production native-effect command/caller | `ABSENT` | The native transition command ends before this effect corridor; no production execution call site exists. |
| P01 | Continuation issuance | `EXISTS_CANONICALLY` | Only `issueForNewWinner()` creates it, after admission publication, using a random ID and authority label. |
| P02 | Continuation recognition | `EXISTS_CANONICALLY` | Exact object identity in `$issued`, plus absence from `$consumed`. No process fact. |
| P03 | Continuation consumption | `EXISTS_CANONICALLY` | `execute()` removes the object from `$issued` and marks its ID consumed before callback-start publication. |
| P04 | Replay mint refusal | `EXISTS_CANONICALLY` | A non-new admission returns no continuation unless the same admission service has a cached outcome. |
| P05 | Same-service cached outcome | `EXISTS_FRAGMENTED` | `$outcomes` returns the original continuation object for exact replay; its lifetime and transfer behavior are not process bound. |
| P06 | Credential capability path | `EXISTS_CANONICALLY` | Credential capability is recognized/consumed inside admission; this campaign does not widen or execute it. |
| E01 | Existing-receipt branch before custody | `EXISTS_CANONICALLY` | `execute()` returns the stored receipt before checking continuation, payload, key or time. This belongs in read-only `reconstruct()`, not execution. |
| E02 | Sealed-response branch before custody | `EXISTS_CANONICALLY` | `execute()` binds a receipt before custody validation when callback-start and response exist. |
| E03 | First-callback custody check | `EXISTS_FRAGMENTED` | Check occurs before callback-start for the new-callback branch, but proves registry identity only. |
| E04 | Callback-start before provider double | `EXISTS_CANONICALLY` | Start is durably published under the continuation lock, which is released before the double. |
| E05 | Callback non-reinvocation | `EXISTS_CANONICALLY` | Start without response throws `UNKNOWN_REPLAY_PROHIBITED`; existing response is never sent to the provider double again. |
| E06 | Read-only receipt reconstruction | `EXISTS_CANONICALLY` | `reconstruct(receiptId)` reads only a digest-checked receipt and asserts no retry/credential/provider action. |
| E07 | Governed forward-completion API | `ABSENT` | No distinct method/service accepts a reconciliation claim and only binds admission + response to receipt. |
| E08 | Reconciliation authority/claim | `ABSENT` | No claim identifies admission, response, allowed act, issuer, lifetime and no-provider invariant. |
| E09 | Provider-double boundary | `EXISTS_CANONICALLY` | The service constructs a callback request with `authentication_present=false` and has no credential resolver/network transport. |
| X01 | Loss before admission temp write/rename | `EXISTS_FRAGMENTED` | No final admission is visible; consumed in-memory credential and possible orphan temp are not a durable atomic aggregate. |
| X02 | Loss after admission rename before continuation issue | `EXISTS_CANONICALLY` | Durable winner is stranded with no continuation. First callback must remain impossible. |
| X03 | Loss after continuation issue before return | `EXISTS_CANONICALLY` | Capability dies or is inherited/transferred; intended rule is no reconstruction and no callback after loss. |
| X04 | Loss after return before execute | `EXISTS_FRAGMENTED` | Current transfer/fork paths can preserve custody; corrected semantics must strand first execution. |
| X05 | Loss after consumption before callback-start rename | `EXISTS_FRAGMENTED` | No provider ran, but the ephemeral right is gone and no durable start proves the cut; reconcile as abandoned pre-callback, never retry callback. |
| X06 | Loss after callback-start before/during callback | `EXISTS_CANONICALLY` | Terminal unknown; no callback reinvocation. |
| X07 | Loss after provider observation before response rename | `EXISTS_CANONICALLY` | Start exists and response does not; terminal unknown. |
| X08 | Loss after response rename before receipt | `EXISTS_FRAGMENTED` | Safe forward-only completion exists mechanically, but is reached through `execute()` without governed claim. |
| X09 | Loss after receipt rename | `EXISTS_CANONICALLY` | Read-only reconstruction is sufficient. |
| G01 | `ee6e983941a23b75d9ee77b4ba4aa741a34bdbd6` implementation provenance | `EXISTS_CANONICALLY` | One commit contains Preparation Batch 0 and Batches 1–5 together: 39 files, 2,529 insertions, 100 deletions. |
| G02 | `dc62d4e564bfde3230117d740ec157e0928abf35` merge provenance | `EXISTS_CANONICALLY` | Merge parents are `77d26f4c7f5655dcce67b5c3765714b5c0ede85e` and `ee6e983941a23b75d9ee77b4ba4aa741a34bdbd6`; no separately merged Batch 1–4 chain exists. |
| G03 | GitHub CI for `dc62d4e564bfde3230117d740ec157e0928abf35` | `ABSENT` | Repository history/documents contain no attached run; network verification was prohibited. Local 2,291/49,398 is not CI. |
| G04 | PHPUnit workflow | `EXISTS_CANONICALLY` | `.github/workflows/phpunit.yml` runs `vendor/bin/phpunit tests` on push and pull request, but a trigger definition is not a run result. |
| G05 | Independent terminal audit | `ABSENT` | Prior Batch 5 was same-workspace, same-agent and uncommitted when performed. |
| D01 | Top current campaign entries | `EXISTS_CANONICALLY` | Flow, handoff README and Blackquill todo select this process-custody campaign and its hard stop. |
| D02 | Prior continuation flow section | `EXISTS_FRAGMENTED` | Still titled/current and says Preparation Batch 0 only although merged Batches 1–5 and formal refusal supersede it. |
| D03 | Prior continuation handoff README section | `EXISTS_FRAGMENTED` | Still calls the old campaign the next local campaign and points at its Batch 0 entrypoint. |
| D04 | Prior continuation Blackquill checklist | `EXISTS_FRAGMENTED` | All six old stages remain unchecked despite the merged candidate and refused closure. |
| D05 | Historical handoffs | `EXISTS_CANONICALLY` | Correctly retain batch-local claims and the formal-closure-blocked handoff; they must remain historical evidence, not be rewritten. |
| D06 | Prior campaign-ready handoff | `EXISTS_FRAGMENTED` | Still presents the superseded continuation/exclusivity campaign as ready and its Preparation Batch 0 entrypoint as active. |
| D07 | Prior Preparation Batch 0 local-ready handoff | `EXISTS_FRAGMENTED` | Remains executable historical text; canonical consumers must prevent it from competing with the process-custody campaign. |
| H01 | Multi-host/distributed custody and locking | `DEFERRED_BOUNDARY` | `flock`, process memory and atomic rename cover one host/cooperative shared filesystem only. |
| H02 | Hostile same-process memory/reflection/extension attacker | `DEFERRED_BOUNDARY` | PHP userland cannot defend against an attacker able to mutate private memory or execute arbitrary code in the process. |
| H03 | Live provider, credential and Batch 7 behavior | `DEFERRED_BOUNDARY` | Explicitly suspended and outside this campaign preparation. |

## Exact stale canonical consumers

| Consumer | Stale text | Required reconciliation stage |
| --- | --- | --- |
| `docs/delegate-mission-flow.md` prior continuation/exclusivity section | Calls the old campaign a current continuation, says `PREPARATION_BATCH_0_AUTHORIZED_ONLY`, and points to the superseded entrypoint. | Mark historical/superseded after the current campaign evidence is merged; do not alter the historical handoffs. |
| `docs/handoffs/README.md` prior continuation/exclusivity section | Calls the old campaign the next local campaign and its Batch 0 handoff active. | Replace operational precedence with the current process-custody completion handoff while retaining a historical link. |
| `todo/blackquill-todos.md` old continuation/exclusivity checklist | Leaves Preparation Batch 0 and Batches 1–5 unchecked although `ee6e983...` merged and formal closure was refused. | Record implementation as historically executed together, not procedurally accepted; keep closure refused. |
| `docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-campaign-ready.md` | Says the old campaign is ready and points to its old active entrypoint. | Classify as historical campaign-selection evidence. |
| `docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-preparation-batch-0-local-ready.md` | Contains a runnable superseded entrypoint. | Classify as historical and non-authorizing. |

The prior Preparation Batch 0 completion, Batch 1–4 completion handoffs and
Batch 5 formal-closure-blocked handoff are not rewritten: their dated,
stage-local claims are canonical historical provenance. The stale defect is
their presentation as current operational instructions by the consumers above.

## Concrete counterexamples observed locally

A disposable `php -r` probe constructed synthetic, non-authoritative objects
and used reflection only to model an already populated issuer registry. It did
not call any authority/capability issuer or consumer.

| Counterexample | Observed result on PHP 8.4.14 / Windows |
| --- | --- |
| `serialize($capability)` | succeeds |
| `serialize($issuer)` | succeeds |
| `unserialize(serialize([$issuer, $capability]))` | restored issuer recognizes restored capability |
| `clone $issuer` | cloned issuer recognizes original registered capability |
| `clone $capability` | original issuer does not recognize the clone; insufficient because issuer/outcome cloning remains transferable |
| `unserialize(serialize([$issuer, $outcome]))` | restored issuer recognizes restored outcome continuation |
| `clone $outcome` | cloned outcome retains the recognized continuation object |
| `pcntl_fork()` | not executable here: Windows and `pcntl_loaded=false`; Linux proof remains mandatory |

## Runtime identity, threat and deployment assumptions

The minimum actual incarnation identity is an issuer-owned value
`{initial_pid, random_incarnation_nonce}` created inside the running PHP process.
Every issue/recognize/consume call must compare `getmypid()` with `initial_pid`
and the capability's opaque incarnation binding with the issuer's nonce. The
nonce must never be exposed as authority metadata, serialized, cloned or
reconstructed. A fork changes PID and therefore invalidates the inherited
issuer before it can recognize custody. A separately started process receives a
fresh nonce even if the OS reuses a PID. PID alone is forbidden.

Linux `/proc/self/stat` start ticks plus boot ID and Windows process-creation
time may be defense-in-depth only. They are platform-specific, can be
unavailable in restricted containers, and do not replace the nonce. If the PID
cannot be obtained or changes, custody fails closed. No design claim covers
arbitrary in-process code execution, debugger/FFI memory mutation, VM snapshot
restoration, distributed hosts or non-cooperating filesystems.

## Smallest acyclic correction

1. A non-serializable, non-cloneable process-incarnation source owns initial
   PID and a random nonce. Issuer, capability and admission outcome fail closed
   on serialization/unserialization and cloning; recognition verifies current
   PID, issuer identity, nonce binding, exact capability object and unused state.
2. Admission keeps the existing native -> sorted source/trust -> authority ->
   semantic tuple -> admission-store order. It publishes the durable winner,
   releases locks, then mints custody only for that newly published winner.
3. `executeFirst(...)` is the only callback API. Under the admission
   continuation scope it validates and consumes custody before callback-start
   publication, releases all filesystem locks, invokes only the injected
   provider double, then seals response and receipt.
4. `reconstruct(receiptId)` remains a distinct read-only act and accepts no
   continuation or reconciliation authority.
5. `forwardComplete(admissionId, responseId, reconciliationClaim, at)` is a
   distinct no-callback act. The governed claim is sealed, exact-scope,
   forward-completion-only, bound to admission and response digests, explicitly
   denies provider/credential/retry authority, and is idempotent for the one
   deterministic receipt. It may be durable because process loss is its use
   case; it is never accepted by `executeFirst`.

Recovery lock order is: admission continuation scope -> exact reconciliation
claim scope (if durable claim state is required) -> receipt immutable-store
scope. No recovery path may acquire native, authority or tuple locks, and no
filesystem lock may be held across a callback. Repeated forward completion must
return the same receipt without reissuing a claim or invoking a callback.

## Staged implementation and independent evidence plan

1. Batch 1: define only process-incarnation, non-transferability, first-execute,
   read-only reconstruct and reconciliation-claim contracts.
2. Batch 2: implement PID + nonce custody and serialization/clone/fork refusal;
   retain provider absence.
3. Batch 3: split `executeFirst`, `reconstruct` and `forwardComplete`; require
   the exact governed claim for forward mutation and preserve callback
   non-reinvocation.
4. Batch 4: execute the complete adversarial matrix on Windows and Linux where
   available, with provider doubles and disposable storage only.
5. Batch 5: begin only after separately authorized/committed/merged Batches 1–4
   from clean synchronized Batch 4 `main`; run focused and full PHPUnit locally,
   retain GitHub run URL/id/SHA/job/log totals separately, and have a reviewer
   independent of implementation issue the terminal verdict.

No production runtime behavior, configuration or service wiring changed in
Preparation Batch 0. No authority or capability was issued or consumed, and no
provider, credential, network, mission or live effect was reached.
