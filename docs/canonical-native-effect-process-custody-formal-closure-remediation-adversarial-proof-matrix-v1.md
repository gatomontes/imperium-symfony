# Canonical Native Effect Process Custody and Formal Closure Remediation — adversarial proof matrix v1

`PREPARATION_BATCH_0_ADVERSARIAL_MATRIX_ONLY`
`NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL`

All future executable cases use disposable local storage and provider doubles.

| ID | Case | Current result | Required proof/result | State |
| --- | --- | --- | --- | --- |
| SER01 | Serialize capability alone | Succeeds | Serialization throws; no bytes accepted for restoration | `ABSENT` |
| SER02 | Serialize issuer alone | Succeeds with private registries | Serialization throws | `ABSENT` |
| SER03 | Serialize issuer + capability graph | Restored issuer recognizes restored capability | Entire operation refuses | `ABSENT` |
| SER04 | Serialize issuer + admission outcome graph | Restored issuer recognizes outcome continuation | Entire operation refuses | `ABSENT` |
| SER05 | Direct `unserialize` crafted payload | No wakeup guard | Deterministic fail closed; no usable object | `ABSENT` |
| CLN01 | Clone capability | Allowed; clone unrecognized | Explicit clone refusal | `EXISTS_FRAGMENTED` |
| CLN02 | Clone issuer, use original capability | Clone recognizes it | Explicit clone refusal before use | `ABSENT` |
| CLN03 | Clone outcome, use retained continuation | Recognized | Explicit clone refusal | `ABSENT` |
| PRC01 | Same process, exact issuer/capability | Registry recognizes | One use only with matching incarnation | `EXISTS_FRAGMENTED` |
| PRC02 | Same process, fresh issuer | Refuses | Preserve | `EXISTS_CANONICALLY` |
| PRC03 | Fresh spawned process, copied metadata | Refuses by empty registry | Preserve and assert PID/nonce mismatch | `EXISTS_FRAGMENTED` |
| PRC04 | Linux `pcntl_fork`, child uses inherited graph | Untested; no PID check | Child refuses before callback-start | `ABSENT` |
| PRC05 | Parent uses custody after child refusal | Untested | Parent may use once only if still current and policy permits | `ABSENT` |
| PRC06 | PID reused in fresh interpreter | No proof | Fresh nonce prevents recognition | `ABSENT` |
| PRC07 | PID/process metadata unavailable | No proof | Fail closed | `ABSENT` |
| PRC08 | Authority label equals current PID text | Accepted as label | Does not authenticate process | `ABSENT` |
| PRC09 | PID changes while issuer lives | No check | Issuer permanently invalidates inherited custody | `ABSENT` |
| LIF01 | Two corridor `continuationIssuer()` calls | Two fresh issuers | Wrong issuer refuses; documented lifetime | `EXISTS_CANONICALLY` |
| LIF02 | Same container shared facade | Facade shared, issuer manually fresh | No container assumption substitutes for process identity | `EXISTS_FRAGMENTED` |
| LIF03 | Kernel reboot/new container same process | Fresh issuer | Old custody refuses | `EXISTS_FRAGMENTED` |
| CUT01 | Exit before admission rename | No final record; possible temp | Clean/ambiguous state distinguished; no effect | `EXISTS_FRAGMENTED` |
| CUT02 | Exit after admission rename before issue | Durable stranded winner | No callback; no custody reconstruction | `EXISTS_FRAGMENTED` |
| CUT03 | Exit after issue before return | Current transfer paths possible | No callback after process loss | `ABSENT` |
| CUT04 | Exit after return before execute | Current transfer/fork paths possible | No callback after process loss | `ABSENT` |
| CUT05 | Exit after custody consume before start rename | No durable start | Abandoned pre-callback; no reissue/retry | `ABSENT` |
| CUT06 | Exit after callback-start before double | Terminal unknown | Preserve; callback count zero or one, never retry | `EXISTS_CANONICALLY` |
| CUT07 | Exit inside provider double | Terminal unknown | Callback count one | `EXISTS_CANONICALLY` |
| CUT08 | Exit after observation before response rename | Terminal unknown | No reinvocation | `EXISTS_CANONICALLY` |
| CUT09 | Exit after response rename before receipt | `execute()` forward-binds without custody | Only claimed `forwardComplete`; callback count remains one | `EXISTS_FRAGMENTED` |
| CUT10 | Exit during receipt temp write | Response remains | Idempotent forward recovery creates/returns one receipt | `EXISTS_FRAGMENTED` |
| CUT11 | Exit after receipt rename | `reconstruct()` reads | Read-only exact receipt | `EXISTS_CANONICALLY` |
| API01 | Existing receipt passed to `execute()` with garbage capability | Returns receipt before validation | First-execute API must not return it; reconstruct only | `EXISTS_FRAGMENTED` |
| API02 | Sealed response passed to `execute()` with fabricated capability | Binds before validation | First-execute refuses/has no recovery branch | `EXISTS_FRAGMENTED` |
| API03 | `reconstruct()` absent receipt | Immutable absent error | Preserve read-only refusal | `EXISTS_CANONICALLY` |
| REC01 | Forward complete with exact claim | No claim exists | One receipt, no callback/credential | `ABSENT` |
| REC02 | Missing claim | Current execute binds | Refuse mutation | `ABSENT` |
| REC03 | Fabricated/resealed claim | No claim exists | Refuse before receipt write | `ABSENT` |
| REC04 | Claim for different admission/response | No claim exists | Refuse exact join | `ABSENT` |
| REC05 | Claim replay after receipt | No claim exists | Return same receipt, no second mutation | `ABSENT` |
| REC06 | Expired admission with pre-expiry sealed response | Current early path binds | Apply explicit claim policy; never callback | `EXISTS_FRAGMENTED` |
| REC07 | Response digest/content tamper | Immutable/content checks refuse | Preserve, no callback | `EXISTS_CANONICALLY` |
| REC08 | Response callback/admission reference substitution | Partial coverage | Refuse every mismatched edge | `EXISTS_FRAGMENTED` |
| CB01 | First callback with valid custody | Provider double once | Start published first; lock released across double | `EXISTS_FRAGMENTED` |
| CB02 | Reuse consumed custody | Start branch yields terminal behavior | Refuse without invoking callback | `EXISTS_CANONICALLY` |
| CB03 | Callback throws | Unknown | Callback count one across retries | `EXISTS_CANONICALLY` |
| CB04 | Sealed response recovery | No reinvocation | Preserve under distinct API and claim | `EXISTS_FRAGMENTED` |
| BND01 | Worker/facade source scan | No credential/network edge | Preserve | `EXISTS_CANONICALLY` |
| BND02 | Production command scan | No canonical effect command | Preserve | `EXISTS_CANONICALLY` |
| BND03 | ProviderTransition direct container lookup | Excluded | Preserve | `EXISTS_CANONICALLY` |
| BND04 | Multi-host/distributed process | Not covered | Do not claim | `DEFERRED_BOUNDARY` |
| WIN01 | Windows spawned-process suite | Available via `proc_open` | Run serialization/clone/spawn/container/cuts | `EXISTS_FRAGMENTED` |
| LIN01 | Linux fork suite | Current host unavailable | Run `pcntl_fork` when extension supported; otherwise explicit skip plus CI Linux evidence | `ABSENT` |
| GIT01 | Batches 1–4 separately authorized/committed/merged | Prior campaign failed this | One merge provenance chain per stage | `ABSENT` |
| CI01 | CI tied to each candidate SHA | Workflow exists, prior run absent | Retain run URL/id/SHA/job/result | `ABSENT` |
| CI02 | Terminal audit from clean merged Batch 4 main | Prior audit same workspace | Independent Batch 5 only | `ABSENT` |
| DOC01 | Canonical flow/README/todo reflect actual stage | Prior entries stale | Reconcile without rewriting historical handoffs | `EXISTS_FRAGMENTED` |

## Required evidence by stage

- Batch 1: contract-only reflection/source tests for non-transferability,
  process facts versus labels, API separation and claim scope.
- Batch 2: direct serialization/unserialization/clone refusal; Windows spawned
  process; Linux fork; PID-change and nonce-substitution tests; service/container
  lifetime proof. No provider double is necessary.
- Batch 3: every CUT/API/REC/CB row with injected provider doubles, exact
  callback counters and disposable records.
- Batch 4: combined adversarial suite, real Symfony container, workers,
  forbidden dependency scans, Windows/Linux evidence and all prior tuple,
  substitution, expiry, cancellation and interruption regressions.
- Batch 5: clean-main focused/full local runs plus source-attributed GitHub CI
  and an independent terminal audit.

A skipped fork test, green Windows-only run, workflow file, local test total or
provider idempotency assertion cannot substitute for the missing evidence.
