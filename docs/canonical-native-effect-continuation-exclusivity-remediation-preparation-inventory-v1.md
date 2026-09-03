# Canonical Native Effect Continuation and Exclusivity Remediation — Preparation inventory v1

`PREPARATION_BATCH_0_COMPLETE_CONTINUATION_EXCLUSIVITY_GAPS_CLASSIFIED`
`DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Audited baseline: clean synchronized `main` at
`77d26f4c7f5655dcce67b5c3765714b5c0ede85e`.

## Decision

The Batch 3 admission is a durable, secret-free pre-callback winner, but it is
not an uninterrupted continuation grant. The admitted credential capability is
only recognized by its original in-memory issuer and cannot survive or be
reconstructed after process loss; more importantly, continuation does not ask
for it. The Batch 4 service can therefore begin the first callback from a fresh
process using only durable/caller-reconstructible values.

The replay identity is authority-specific, not effect-specific. Two separately
sealed authorities carrying the same native/request/provider tuple acquire
different authority locks, replay locks and admission paths, so both can win.
Receipt construction also trusts fields from a post-admission caller authority
array whose seal is never recomputed. These are executable counterexamples, not
terminology defects. Batch 1 is not authorized by this inventory.

## Classified surface inventory

| ID | Surface | Classification | Audited fact / required disposition |
| --- | --- | --- | --- |
| G01 | Admission-to-callback graph | `EXISTS_FRAGMENTED` | Admission and double continuation exist as separate public services; no process-local object joins the calls. |
| G02 | Durable admission lookup | `EXISTS_CANONICALLY` | `admission_id` locates a digest-checked immutable record. |
| G03 | Uninterrupted admission-winner continuation | `ABSENT` | `execute()` accepts no capability and no admission-return object. |
| G04 | Fresh-process first continuation | `EXISTS_CANONICALLY` | A new `NativeEffectDoubleExecutionService` can read admission and write callback start when none exists. This is the BQ-CNE-01 counterexample. |
| G05 | Post-callback-start reinvocation refusal | `EXISTS_CANONICALLY` | Callback-start without response yields `UNKNOWN_REPLAY_PROHIBITED`. |
| G06 | Sealed-response forward completion | `EXISTS_FRAGMENTED` | A fresh process can bind a receipt from a sealed response, but it must still supply authority semantics and pass pre-expiry continuation validation. |
| G07 | Completed receipt reconstruction | `EXISTS_CANONICALLY` | `reconstruct()` is read-only; `execute()` returns an existing receipt before validating caller inputs. |
| C01 | Secret-free credential capability | `EXISTS_CANONICALLY` | Random object identity is registered only in one issuer instance; metadata contains no credential or secret. |
| C02 | Capability issue-time validation | `EXISTS_FRAGMENTED` | Schema/basic scope/expiry are checked, but the authority seal and native lineage are not checked by the issuer. |
| C03 | Capability admission recognition | `EXISTS_CANONICALLY` | The exact object must be present in the same issuer map. |
| C04 | Capability single-use mutation | `ABSENT` | Admission records consumption durably but never removes or marks the in-memory object consumed. |
| C05 | Capability survival after process loss | `ABSENT` | The issuer map and object identity die with the process. |
| C06 | Capability reconstruction/reissue after loss | `ABSENT` | Reconstruction is impossible today and must remain prohibited. Metadata is not a token. |
| C07 | Dedicated continuation capability | `ABSENT` | No unforgeable object is created only for the newly published admission winner. |
| I01 | Authority id derivation | `EXISTS_FRAGMENTED` | Contract requires an id; fixtures choose `native-effect-authority-<native-root>`. No production issuer or canonical derivation exists. |
| I02 | Authority digest | `EXISTS_CANONICALLY` | Seal is SHA-256 of canonical authority content and is validated at admission inspection. |
| I03 | Current replay identity | `EXISTS_CANONICALLY` | Deterministic digest includes native/request/provider facts **and authority id/digest**. |
| I04 | Semantic effect tuple identity | `ABSENT` | No authority-independent digest exists. This is the BQ-CNE-02 counterexample. |
| I05 | Admission id | `EXISTS_FRAGMENTED` | First 20 hex characters of authority-specific replay digest; deterministic but only 80-bit truncated and not tuple-exclusive. |
| I06 | Callback/response/receipt ids | `EXISTS_CANONICALLY` | Deterministically chained from admission id, then protected by immutable-record conflict. |
| I07 | Same-authority duplicate exclusion | `EXISTS_CANONICALLY` | Admission directory scan and authority lock converge exact reuse or raise `CNE302`. |
| I08 | Distinct-authority same-tuple exclusion | `ABSENT` | Different authority ids/digests produce different replay locks and admissions; both may publish. |
| I09 | Losing-authority disposition | `ABSENT` | There is no cross-authority loser, refusal record or proof that the losing authority remains unconsumed. |
| L01 | Native exclusion lock | `EXISTS_CANONICALLY` | `NativeBindingReader::legacy()` acquires `native-provider-transition`. |
| L02 | Sorted native source/trust locks in effect admission | `ABSENT` | Admission calls `legacy()`, not `NativeState::locked()`; validator reads through optimistic snapshots. |
| L03 | Authority lock | `EXISTS_CANONICALLY` | Scope hashes authority id and nests inside native exclusion. |
| L04 | Current effect lock | `EXISTS_FRAGMENTED` | Scope uses authority-specific replay identity, so it is not a semantic tuple lock. |
| L05 | Admission immutable-store lock | `EXISTS_CANONICALLY` | Directory-scoped immutable lock nests after current effect lock. |
| L06 | Continuation lock | `EXISTS_CANONICALLY` | Admission-id lock spans callback-start publication, provider-double call, response and receipt publication. |
| L07 | Callback/response/receipt store locks | `EXISTS_CANONICALLY` | Each directory lock is acquired serially within continuation lock, not simultaneously. |
| L08 | Cross-authority acyclic lock order | `ABSENT` | The semantic tuple scope that would make the order meaningful does not exist. |
| P01 | Operation provenance | `EXISTS_CANONICALLY` | Admission copies operation; callback derives it from admission. |
| P02 | Destination provenance | `EXISTS_CANONICALLY` | Admission copies destination; callback derives it from admission. |
| P03 | Payload provenance | `EXISTS_FRAGMENTED` | Admission stores digest only; callback takes caller bytes and verifies their SHA-256. This is acceptable only with the admitted digest as sole meaning. |
| P04 | Provider provenance | `EXISTS_FRAGMENTED` | Admission persists provider id through credential scope, but omits adapter id/version and assurance admission. |
| P05 | Expected-return provenance | `ABSENT` | Admission omits it; receipt copies it from caller authority. This is the BQ-CNE-03 counterexample. |
| P06 | Idempotency provenance | `EXISTS_FRAGMENTED` | Admission stores key digest; callback takes caller key and verifies SHA-256. Provider-side semantics remain unverified. |
| P07 | Request fingerprint provenance | `EXISTS_CANONICALLY` | Admission stores the authority-bound fingerprint, though the double request does not expose/use it. |
| P08 | Authority reference provenance | `EXISTS_FRAGMENTED` | Admission has a sealed reference; receipt recomputes a reference from an unvalidated caller array. |
| P09 | Callback request independence from caller authority | `EXISTS_CANONICALLY` | Operation/destination come from admission, while payload/key are digest checked. |
| P10 | Receipt independence from caller authority | `ABSENT` | `bindReceipt()` consumes caller authority for authority ref and expected-return contract. |
| S01 | Tampered authority with unchanged old digest | `ABSENT` | Seal is not recomputed in continuation; unchanged id/schema/digest passes, allowing expected-return substitution. |
| S02 | Tampered and resealed authority | `EXISTS_CANONICALLY` | Changed digest fails equality with admitted authority reference. |
| S03 | Stale exact authority after admission | `EXISTS_FRAGMENTED` | It is accepted during continuation; harmless only after all semantics are admission-derived. |
| S04 | Different authority with copied id/schema/digest | `ABSENT` | Non-reference fields are unconstrained and can reach receipt construction. |
| S05 | Payload substitution | `EXISTS_CANONICALLY` | Wrong bytes fail the admitted digest check. |
| S06 | Idempotency-key substitution | `EXISTS_CANONICALLY` | Wrong key fails the admitted digest check. |
| X01 | Expiry before admission | `EXISTS_CANONICALLY` | Validator and capability checks refuse before publication. |
| X02 | Expiry after admission, before first callback | `EXISTS_CANONICALLY` | Continuation refuses at/after admitted expiry. |
| X03 | Expiry after sealed response | `EXISTS_FRAGMENTED` | Current validation blocks forward receipt binding; expiry should never reopen callback, but should not destroy safe forward recovery. |
| X04 | Revocation/cancellation before admission | `EXISTS_FRAGMENTED` | Non-null embedded references refuse, but no durable concurrent winner/service exists. |
| X05 | Revocation/cancellation race with admission | `ABSENT` | No effect-authority lifecycle record or shared lock protocol exists. |
| X06 | Revocation/cancellation after winner | `ABSENT` | No reconciliation-only disposition exists; it must never unconsume or imply remote cancellation. |
| X07 | Process loss before admission rename | `EXISTS_FRAGMENTED` | No final record is visible, but an orphan `.tmp.*` is not classified and future admission ignores it. |
| X08 | Process loss after admission rename | `EXISTS_FRAGMENTED` | Durable unknown exists, but current fresh process can still start the callback. |
| X09 | Process loss after callback-start | `EXISTS_CANONICALLY` | No response means unknown and reinvocation is prohibited. |
| X10 | Process loss after response seal | `EXISTS_FRAGMENTED` | Forward receipt binding exists but depends on caller authority and unexpired time. |
| X11 | Rejected/malformed/exception outcome | `EXISTS_CANONICALLY` | Rejection is terminal; malformed/exception becomes unknown; none grants retry. |
| B01 | Production facade construction | `EXISTS_CANONICALLY` | Auto-discovered facade constructs validator, issuer, admission service and double service. |
| B02 | Direct service autowiring | `EXISTS_CANONICALLY` | ProviderTransition namespace is excluded; services are not directly auto-discovered. |
| B03 | Fresh-container bypass | `EXISTS_FRAGMENTED` | The public test facade proves construction; `providerDouble()` needs no issuer/capability and enables fresh-process first continuation. |
| B04 | Production command edge | `ABSENT` | No canonical native-effect command exists; native transition command ends pre-effect. |
| B05 | Worker first-continuation mode | `ABSENT` | Existing worker has admit/exit and callback-start/retry modes, but no admit-exit-then-first-callback test. |
| B06 | Fixture provenance | `EXISTS_FRAGMENTED` | Tests fabricate sealed authorities and provider observations; mechanics only, never production authority. |
| B07 | Credential/provider/network edge | `ABSENT` | Corridor facade and worker contain no credential resolver, AgentMail transport, HTTP client or network operation. |
| E01 | Local Batch 6 total | `EXISTS_CANONICALLY` | Historical local report: 2,189 tests / 48,255 assertions. |
| E02 | CI Batch 6 total | `EXISTS_CANONICALLY` | GitHub Actions run 33813014897: 2,189 tests / 48,253 assertions. |
| E03 | Unified source-neutral total | `ABSENT` | The two observations differ and may not be collapsed. This is BQ-CNE-04. |
| E04 | This Batch 0 local evidence | `DEFERRED_BOUNDARY` | Recorded only after the documentary test is run; no CI result exists at authoring time. |
| H01 | Multi-host/distributed exclusivity | `DEFERRED_BOUNDARY` | `flock` and atomic rename prove only cooperative processes sharing one filesystem. |
| H02 | Live credential/provider behavior | `DEFERRED_BOUNDARY` | Suspended Batch 7; not exercised or authorized here. |

## Blackquill findings and exact counterexamples

| Finding | Minimal executable counterexample | Smallest acyclic correction |
| --- | --- | --- |
| BQ-CNE-01 | Process A calls `admit()` and exits after the admission rename. Process B constructs the double service and calls `execute(admission_id, authority, payload, key, current_time, callback)` before any callback-start record. The callback runs. | First publication returns a registry-recognized, single-use `NativeEffectContinuationCapability`. `execute()` requires the exact object and same custody registry. No durable input, exact replay, new service or new process may mint/reconstruct it. Post-response forward recovery is a separate admission-derived path. |
| BQ-CNE-02 | Seal A and B with different authority ids but identical native root, operation, destination, payload/provider/credential/return/idempotency facts. Their replay digests and all admission ids/locks differ; both publish. | Derive an authority-independent semantic tuple id; serialize it with candidate authority consumption; publish one tuple winner. A losing authority is explicitly refused and remains unconsumed. |
| BQ-CNE-03 | After admission, change only `expected_return_contract` while retaining original authority id/schema/record digest. `NativeState::ref()` still matches; receipt asserts the substituted contract. | Persist the complete immutable request/provider/return/idempotency provenance in admission. Remove authority array from continuation and receipt APIs; derive all later meaning from admission/response records. |
| BQ-CNE-04 | The ledger's local `48,255` assertion observation differs from CI run 33813014897's `48,253`. | Keep separate source-labelled evidence entries. Never infer CI totals from a local run or rewrite historical records. |

## Smallest correction sequence

1. Batch 1 defines separate authority-consumption identity, authority-independent
   semantic effect tuple, tuple winner/loser disposition, ephemeral continuation
   capability and immutable admitted-provenance contracts. No callback.
2. Batch 2 publishes one tuple winner and exact authority consumption under the
   required lock order, then creates the process-local continuation capability
   only for the newly published winner. A restart has reconciliation authority
   only. No credential or callback.
3. Batch 3 requires and consumes that capability for first callback start,
   removes caller authority arrays, and makes request/response/receipt meaning
   derive from sealed records. Provider doubles only.
4. Batch 4 proves every adversarial/process/contention/substitution/bypass case
   in the proof matrix without network or external I/O.
5. Batch 5 reconciles local/CI evidence from clean merged Batch 4 `main` and runs
   a separate terminal Blackquill audit. It alone may decide whether the
   original Batch 7 suspension can be reconsidered.

## Prohibited recovery and retry

Never recreate or reissue a continuation capability; start a first callback
from durable admission alone; burn a losing authority; retry after any tuple
winner, pending/partial winner, capability-consumption attempt, callback start,
timeout/disconnect, malformed/rejected/missing response, unknown outbound-byte
status or process loss after publication; reinterpret expiry/revocation/
cancellation as permission; or use provider idempotency as local retry authority.
Only a provably clean pre-winner refusal may be submitted again. Forward
recovery from an already sealed response may write later evidence without any
provider callback and only from immutable admitted facts.

No production runtime behavior, service wiring or configuration changed in
Preparation Batch 0.
