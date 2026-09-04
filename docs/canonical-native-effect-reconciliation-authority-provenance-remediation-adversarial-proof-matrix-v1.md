# Canonical Native Effect Reconciliation Authority Provenance Remediation — adversarial proof matrix v1

`PREPARATION_BATCH_0_ADVERSARIAL_MATRIX_ONLY`
`NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL`

All future executable cases use disposable local storage and no-provider
recovery fixtures. This matrix authorizes no case execution in Preparation
Batch 0.

| ID | Case | Present result | Required later proof/result | State |
| --- | --- | --- | --- | --- |
| CNT01 | Caller copies public schema/issuer/holder/act and seals array | Admitted | Refuse before any authority/claim write | `ABSENT` |
| CNT02 | Caller changes authority ID but keeps same lineage | Creates another authority/claim | Refuse because no canonical issuance exists | `ABSENT` |
| CNT03 | Caller chooses its own effective/expiry window | Accepted if current | Only issuer-bounded window accepted | `ABSENT` |
| CNT04 | Caller serializes/deserializes authority array | Works | Serialized evidence is not custody; only resolver-issued typed custody can enter admission | `ABSENT` |
| CNT05 | Caller constructs typed lookalike with copied fields | No type exists | Resolver/issuer rejects exact-object lookalike | `ABSENT` |
| CNT06 | Clone resolver/custody object | No type exists | Explicit clone refusal | `ABSENT` |
| INT01 | Correct digest on caller-authored bytes | Treated as sealed authority | Refuse: integrity is not issuance | `ABSENT` |
| INT02 | Tamper stored canonical authority bytes | Immutable read refuses | Preserve | `EXISTS_CANONICALLY` |
| INT03 | Tamper issuance evidence | No issuance exists | Refuse resolution | `ABSENT` |
| INT04 | Substitute authority record at valid filename | Trusted-store compromise outside present model | Digest/source/identity mismatch refuses under cooperative-store model | `ABSENT` |
| PRV01 | Constant issuer string without principal | Accepted | Refuse | `ABSENT` |
| PRV02 | Inactive/expired Imperator principal | Not checked | Refuse at issuance/resolution | `ABSENT` |
| PRV03 | Principal lacks reconciliation competence | Not checked | Refuse | `ABSENT` |
| PRV04 | Substituted principal version digest | Not checked | Refuse | `ABSENT` |
| PRV05 | Substituted Operator Root lineage | Not checked | Refuse | `ABSENT` |
| PRV06 | Fixture stored with valid digest but no authenticated ingress | Can look structurally valid | Must not be promoted to live authenticated Root issuance | `ABSENT` |
| SRC01 | Missing issuance decision | Not required | Refuse | `ABSENT` |
| SRC02 | Refused/non-issuable decision | Not required | Refuse | `ABSENT` |
| SRC03 | Decision target digest differs from authority | Not required | Refuse | `ABSENT` |
| SRC04 | Missing issuance evidence | Not required | Refuse resolver/admission | `ABSENT` |
| SRC05 | Issuance artifact reference differs | Not required | Refuse | `ABSENT` |
| LIN01 | Admission digest substituted | Refuses CNE510 | Preserve | `EXISTS_CANONICALLY` |
| LIN02 | Callback digest substituted | Refuses CNE510 | Preserve | `EXISTS_CANONICALLY` |
| LIN03 | Response digest substituted | Refuses CNE510 | Preserve | `EXISTS_CANONICALLY` |
| LIN04 | Response points to different callback | Refuses lineage | Preserve | `EXISTS_CANONICALLY` |
| LIN05 | Deterministic receipt ID substituted | Refuses lineage | Preserve | `EXISTS_CANONICALLY` |
| LIN06 | Issuer selects stale but intact effect lineage | No issuer | Refuse under exact source policy | `ABSENT` |
| REV01 | Authority expired before resolution | Caller time checked only at admit | Refuse from issuer-bounded record | `EXISTS_FRAGMENTED` |
| REV02 | Authority revoked before resolution | No revocation | Refuse | `ABSENT` |
| REV03 | Revoked after resolution, before consumption | No revocation/custody | Refuse first consumption | `ABSENT` |
| REV04 | Expired/revoked after consumption cut, before receipt | Undefined | Only exact deterministic completion policy; never a new claim/effect | `ABSENT` |
| REV05 | Claim expired before first consumption | Refuses today | Preserve | `EXISTS_CANONICALLY` |
| REV06 | Claim expired after consumption cut, retry same receipt | No consumption cut | Complete/return exact receipt only, per Batch 1 policy | `ABSENT` |
| CON01 | Two processes consume one reconciliation authority | Both can presently admit independently | One durable winner; loser refuses | `ABSENT` |
| CON02 | Same authority exact retry after consumption-before-claim cut | No consumption | Same source/consumer converges to claim | `ABSENT` |
| CON03 | Different consumer retries consumed authority | No consumption | Conflict/refusal | `ABSENT` |
| CON04 | Two claims compete for first receipt mutation | Both valid; receipt converges | One claim-consumption winner; no second authority use | `ABSENT` |
| CON05 | Exact claim retry after consumption-before-receipt cut | No consumption | Completes/returns same receipt | `ABSENT` |
| CON06 | Different receipt ID after claim consumption | Deterministic ID checked but no consumption | Refuse | `EXISTS_FRAGMENTED` |
| RPL01 | Re-admit exact self-sealed authority | Converges | Public array route absent | `ABSENT` |
| RPL02 | Reuse same claim before expiry | Returns/binds same receipt | First mutation consumes; subsequent path is result replay only | `EXISTS_FRAGMENTED` |
| RPL03 | Existing receipt through `forwardComplete` after claim expiry | Refuses before receipt read | Read through `reconstruct`; no authority replay | `EXISTS_CANONICALLY` |
| RPL04 | Existing receipt through `reconstruct` | Read-only | Preserve | `EXISTS_CANONICALLY` |
| RPL05 | Try claim in first-execution API | API has no claim input | Preserve | `EXISTS_CANONICALLY` |
| RPL06 | Try continuation in recovery API | API has no continuation input | Preserve | `EXISTS_CANONICALLY` |
| CUT01 | Exit before issuance-authority consumption | No issuer exists | No authority/claim; retry re-evaluates sources | `ABSENT` |
| CUT02 | Exit after issuance consumption before authority write | No issuer exists | Exact issuer retry finishes; substitution refuses | `ABSENT` |
| CUT03 | Exit after authority write before issuance evidence | No issuer exists | Exact retry finishes or resolver refuses incomplete chain | `ABSENT` |
| CUT04 | Exit after authority consumption before claim write | No authority consumption | Exact retry finishes one claim | `ABSENT` |
| CUT05 | Exit after claim write before return | Durable claim exists | Fresh process resolves it; no callback/provider | `EXISTS_FRAGMENTED` |
| CUT06 | Exit after claim consumption before receipt | No claim consumption | Exact retry finishes one receipt | `ABSENT` |
| CUT07 | Exit during receipt temp write | Response/claim remain | Exact retry creates/returns one receipt | `EXISTS_FRAGMENTED` |
| CUT08 | Exit after receipt rename | Receipt durable | Read-only reconstruct | `EXISTS_CANONICALLY` |
| PRC01 | Fresh process resolves unconsumed authority | Current caller can rebuild array | Resolve canonical durable record and obtain fresh local typed custody | `ABSENT` |
| PRC02 | Fresh process after authority consumed | Current caller can create another authority ID | Refuse new claim; permit exact existing-claim recovery only | `ABSENT` |
| PRC03 | Fresh process with copied typed-custody metadata | No type | Refuse | `ABSENT` |
| PRC04 | Existing continuation process-custody tests | Pass on accepted substrate | Preserve unchanged | `EXISTS_CANONICALLY` |
| API01 | Reflection sees `admit(array $authority, int $at)` | Present | Method absent or no array authority input | `ABSENT` |
| API02 | Corridor exposes caller-array admission factory | Present | Expose only canonical ID/resolution boundary | `ABSENT` |
| API03 | Directly instantiate old admission service | Tests do | Old bypass removed/refuses | `ABSENT` |
| API04 | Direct write to authority directory through general store | Possible to in-process code with root | No application caller; hostile same-process/filesystem remains deferred | `DEFERRED_BOUNDARY` |
| APP01 | Real Symfony container resolves corridor | Proven in test kernel | Prove corrected service lifetime and no old method | `EXISTS_FRAGMENTED` |
| APP02 | Production command reaches recovery | No command exists | If canonical consumer is later selected, prove only corrected route; do not invent one in Batch 0 | `ABSENT` |
| APP03 | Disposable worker forwards stored claim | Present | Preserve, with corrected claim provenance/consumption | `EXISTS_FRAGMENTED` |
| APP04 | Source scan finds competing schema constructors | Four test helpers | Zero runtime/self-sealed constructors; fixtures use canonical issuer setup only | `ABSENT` |
| BND01 | Recovery source accepts callback/provider/payload/key | It does not | Preserve source/reflection guard | `EXISTS_CANONICALLY` |
| BND02 | Recovery resolves credential/environment/network | It does not | Preserve repository scan | `EXISTS_CANONICALLY` |
| BND03 | Receipt binder invokes provider | It does not | Preserve | `EXISTS_CANONICALLY` |
| BND04 | Multi-host competing claimants | Not covered | Do not claim | `DEFERRED_BOUNDARY` |
| GIT01 | Batch 1 starts in same Preparation workspace | Prohibited | Refuse; separate instruction/commit/merge required | `ABSENT` |
| GIT02 | Batches 1–4 separately committed/merged | Future | Retain exact chain | `ABSENT` |
| CI01 | Workflow existence treated as CI result | Possible rhetoric, not evidence | Retain exact run ID/job/SHA/conclusion only | `ABSENT` |
| CI02 | Terminal audit before clean merged Batch 4 | Future | Refuse | `ABSENT` |
| DOC01 | Historical zero-stages marker treated as current | Markers remain | Current refusal and campaign precedence must win | `EXISTS_FRAGMENTED` |

## Required evidence by later stage

- **Batch 1:** contract/source tests only: authenticated issuance versus digest,
  exact competence/source/revocation fields, durable record versus typed custody,
  authority and claim consumption, replay semantics and lock order.
- **Batch 2:** issuer/source/resolver tests, exact immutable issuance evidence,
  authority-consumption contention and interruption cuts. No recovery receipt is
  completed.
- **Batch 3:** old `admit(array)` absence, corrected corridor integration,
  claim derivation/consumption and recovery of existing sealed responses only.
- **Batch 4:** every counterfeit, provenance, stale/revoked/substituted,
  competing, cut, fresh-process, container, worker and bypass case above, plus
  all accepted process-custody regressions. No provider or credential access.
- **Batch 5:** independent clean-main reconstruction, focused/full local runs
  and retained exact SHA-bound GitHub CI evidence.

Passing digest checks, immutable storage, one deterministic receipt, provider
idempotency, a green historical suite or constant Imperator prose cannot replace
the missing provenance proof.

