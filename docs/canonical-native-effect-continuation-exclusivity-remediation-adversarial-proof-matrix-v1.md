# Canonical Native Effect Continuation and Exclusivity Remediation — adversarial proof matrix v1

`PREPARATION_BATCH_0_ADVERSARIAL_MATRIX_ONLY`
`NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL`

All future cases use provider doubles and disposable local storage. A test may
prove refusal or record mechanics; it cannot prove production authority,
provider-side idempotency or live delivery.

| ID | Adversarial case | Current result | Required proof/result | State |
| --- | --- | --- | --- | --- |
| A01 | Same authority, same capability, same tuple, same process | Exact admission replay returns winner even after expiry | Read-only winner; never mint another continuation | `EXISTS_FRAGMENTED` |
| A02 | Same authority, newly issued capability | `CNE302` after directory scan | Refuse; no new tuple winner/capability | `EXISTS_CANONICALLY` |
| A03 | Same authority, changed tuple | Replay differs; same-authority scan refuses | Refuse without changing winner | `EXISTS_CANONICALLY` |
| A04 | Distinct authorities, identical semantic tuple, sequential | Both can admit | Exactly one tuple winner; loser explicit and unconsumed | `ABSENT` |
| A05 | Distinct authorities, identical semantic tuple, simultaneous processes | Different locks; both can admit | Exactly one tuple winner under shared tuple scope | `ABSENT` |
| A06 | Distinct authorities, different semantic tuples | Independent admissions possible | Independent winners only if each authority is valid and exact | `EXISTS_FRAGMENTED` |
| A07 | Losing authority reused for its original already-won tuple | No loser concept | Deterministic loser/refusal; never effect | `ABSENT` |
| A08 | Losing authority redirected to a different tuple | Contract authority is exact but no disposition rule | Refuse unless separately governed; never automatic widening | `ABSENT` |
| C01 | Admit and immediately execute with returned continuation object | No such object; reconstructible values suffice | One first callback; capability consumed once | `ABSENT` |
| C02 | Admit-and-exit, then fresh process first continuation | Callback can run | Refuse before callback-start publication | `ABSENT` |
| C03 | Fresh service/container in same process without original custody registry | Callback can run | Refuse; object/registry identity required | `ABSENT` |
| C04 | Reconstruct capability from persisted metadata | Admission metadata is copyable; execute ignores it | Refuse; metadata never authenticates | `ABSENT` |
| C05 | Reissue capability after durable admission | Issuer can issue credential capability; execute ignores it | No continuation mint on replay/reissue | `ABSENT` |
| C06 | Reuse consumed continuation object | Not applicable | Second use refuses before callback | `ABSENT` |
| C07 | Substitute lookalike object with copied fields/id | Admission recognizes credential capability by identity; continuation absent | Refuse by custody-registry identity | `ABSENT` |
| C08 | Process loss after winner rename but before capability creation/return | Fresh callback possible | Reconciliation only; never reconstruct capability | `ABSENT` |
| S01 | Tamper expected-return contract, retain old digest | Passes reference check and alters receipt | Refuse/remove authority input; receipt unchanged | `ABSENT` |
| S02 | Tamper provider fields, retain old digest | Reference check passes; currently not used by callback/receipt | No caller authority accepted; admitted provider wins | `ABSENT` |
| S03 | Tamper destination/operation, retain old digest | Reference check passes, but callback uses admission | No caller authority accepted; admission remains sole source | `EXISTS_FRAGMENTED` |
| S04 | Tamper authority id/schema/digest | Reference mismatch generally refuses | Refuse deterministically | `EXISTS_CANONICALLY` |
| S05 | Tamper then reseal authority | New digest mismatches admission ref | Refuse | `EXISTS_CANONICALLY` |
| S06 | Old exact sealed authority after admission | Accepted | Harmless only after API removes authority semantics | `EXISTS_FRAGMENTED` |
| S07 | Copy original id/schema/digest onto unrelated array | Passes current ref comparison | Refuse by removing array; use admitted reference | `ABSENT` |
| S08 | Wrong payload with same authority | Digest check refuses | Refuse before callback-start | `EXISTS_CANONICALLY` |
| S09 | Wrong idempotency key | Digest check refuses | Refuse before callback-start | `EXISTS_CANONICALLY` |
| S10 | Payload/key digest collision assumption | SHA-256 treated as binding | Explicit cryptographic assumption; collision outside proof | `DEFERRED_BOUNDARY` |
| P01 | Admission omits adapter version/assurance | Current record incomplete | Persist/reference complete provider provenance | `ABSENT` |
| P02 | Admission omits expected-return contract | Receipt reads caller authority | Persist contract; validate/bind from admission | `ABSENT` |
| P03 | Accepted body violates admitted return contract | Hard-coded AgentMail shape rejects, but caller label can differ | Contract-selected validator derived from admission; refuse mismatch | `EXISTS_FRAGMENTED` |
| P04 | Rejected body | Truthful rejected receipt, no retry | Preserve terminal rejection | `EXISTS_CANONICALLY` |
| P05 | Response bytes/digest tamper | `CNE404` | Preserve refusal; no callback | `EXISTS_CANONICALLY` |
| P06 | Response ref/admission/callback substitution | Immutable records are digest checked; joins need explicit adversarial cases | Refuse every mismatched lineage edge | `EXISTS_FRAGMENTED` |
| P07 | Existing receipt with garbage caller inputs | Returned before validation | Read-only return is acceptable only by explicit reconstruction API | `EXISTS_FRAGMENTED` |
| X01 | Expiry just before admission validation | Refuses | No tuple winner/capability | `EXISTS_CANONICALLY` |
| X02 | Expiry between validation and winner rename | No injected final recheck proof | Revalidate under locks immediately before publication | `ABSENT` |
| X03 | Expiry after winner, before first callback | Refuses | Refuse first callback; never reopen tuple | `EXISTS_CANONICALLY` |
| X04 | Expiry after sealed response | Blocks current receipt binding | Permit forward-only completion from sealed facts, no callback | `EXISTS_FRAGMENTED` |
| X05 | Embedded revocation/cancellation before admission | Refuses non-null reference | Preserve plus durable race proof | `EXISTS_FRAGMENTED` |
| X06 | Concurrent revocation vs first winner | No lifecycle writer/shared protocol | One ordered winner; losing action gets explicit disposition | `ABSENT` |
| X07 | Revocation/cancellation after winner | No disposition | Cannot unconsume/cancel provider; reconciliation only | `ABSENT` |
| X08 | Exit before admission temp write | No final record | Clean pre-winner retry only | `EXISTS_CANONICALLY` |
| X09 | Exit during admission temp write | Orphan temp possible and ignored | Classify clean orphan vs ambiguous partial; fail closed if uncertain | `EXISTS_FRAGMENTED` |
| X10 | Exit after admission rename before return | Fresh callback possible | Durable winner, no capability, reconciliation only | `ABSENT` |
| X11 | Exit before callback-start rename | With valid ephemeral object, ambiguity must be controlled | No start may allow only same uninterrupted call; any process loss stops | `ABSENT` |
| X12 | Exit after callback-start rename before callback | Restart refuses | Preserve `UNKNOWN_REPLAY_PROHIBITED` | `EXISTS_CANONICALLY` |
| X13 | Exit inside callback | Restart refuses | Preserve terminal unknown | `EXISTS_CANONICALLY` |
| X14 | Exit after observation before response rename | Callback start remains, response absent | Preserve terminal unknown, no reinvocation | `EXISTS_CANONICALLY` |
| X15 | Exit after response rename before receipt | Caller-bound forward path | Forward bind from admission/response only | `EXISTS_FRAGMENTED` |
| X16 | Exception/timeout/disconnect | Exception becomes unknown | No retry, regardless of provider idempotency claim | `EXISTS_CANONICALLY` |
| X17 | Provider 429/5xx | Would classify returned non-2xx as rejected | No automatic retry | `EXISTS_CANONICALLY` |
| X18 | Clock rollback/invalid time | Integer window checks only | Never regain callback permission; fail closed | `EXISTS_FRAGMENTED` |
| B01 | Direct `new NativeEffectDoubleExecutionService` in fresh process | Enables first continuation | Refuse without continuation custody | `ABSENT` |
| B02 | Auto-discovered facade `providerDouble()` | Constructs service without issuer | Same refusal; facade must not bypass custody | `ABSENT` |
| B03 | Direct service container lookup | ProviderTransition excluded | Remain unavailable except governed facade | `EXISTS_CANONICALLY` |
| B04 | Command invokes corridor | No such command | Remain absent through remediation | `EXISTS_CANONICALLY` |
| B05 | Legacy transport/executor reaches corridor | No call site found | Structural scan remains empty | `EXISTS_CANONICALLY` |
| B06 | Worker admit-and-exit then first-callback mode | Missing | Add local process test proving refusal | `ABSENT` |
| B07 | Fixture writes forged admission/response directly | Tests can write storage/code | Structural fixtures must not be mistaken for production provenance | `DEFERRED_BOUNDARY` |
| B08 | Credential/AgentMail/HTTP/environment access | Absent in corridor/worker | Static sentinels remain empty | `EXISTS_CANONICALLY` |
| E01 | Local and CI totals equal | Historically false by two assertions | Record each run id/source/commit/command/result independently | `ABSENT` |
| E02 | New focused documentary run | Not yet run at matrix authoring | Record exact local result in handoff after execution | `DEFERRED_BOUNDARY` |
| E03 | New CI result | No CI run in Batch 0 authoring | Do not claim one | `DEFERRED_BOUNDARY` |

## Required proof families by later stage

- Batch 1: pure identity vectors, field-inclusion/exclusion vectors, contract
  keys and forbidden dependency/source scans.
- Batch 2: sequential and concurrent distinct-authority/same-tuple winners,
  losing-authority disposition, lock-order checkpoints, interruption at every
  publication cut and no continuation mint on replay/process loss.
- Batch 3: exact-object custody, copied/reissued/restarted refusal, caller-array
  removal, admitted provenance vectors, forward-only receipt completion and
  callback-start-before-double proof.
- Batch 4: all A/C/S/P/X/B rows above in direct, worker and real-container
  forms, with no-effect sentinels and zero network/environment access.
- Batch 5: clean-main independent rerun, source-labelled local/CI evidence and
  terminal Blackquill audit.

Passing same-authority contention, an immutable-file digest check or a green
full suite alone does not satisfy this matrix.
