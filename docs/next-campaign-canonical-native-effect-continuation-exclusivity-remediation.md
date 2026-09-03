# Next campaign: Canonical Native Effect Continuation and Exclusivity Remediation

`CANONICAL_NATIVE_EFFECT_CONTINUATION_EXCLUSIVITY_REMEDIATION_CAMPAIGN_SELECTED`
`PREPARATION_BATCH_0_AUTHORIZED_ONLY`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Baseline: clean synchronized `main` after
`2debf65c7a6534674f869b8703f66c9ee0c2f664`.

Controlling review:
`docs/canonical-native-effect-corridor-post-batch-6-blackquill-review-v1.md`.

## Why this campaign exists

The Canonical Native Effect Corridor reached a bounded, non-network Batch 6.
The post-Batch 6 review found that three security claims exceed the executable
proof: the durable admission does not enforce uninterrupted same-process
continuation; effect replay identity cannot exclude the same semantic effect
across distinct authorities; and receipt construction can consume caller-
supplied authority semantics after admission.

This campaign corrects only those defects and their evidence accounting.
Batches 1–6 remain candidate substrate, not accepted authorization for Batch 7.
No live effect is part of this remediation.

## Governing question

Can Imperium prove that exactly one semantic effect tuple wins across all
competing authorities, that only the uninterrupted admission winner possesses
the process-local right to begin its callback, and that every later response
and receipt meaning derives from immutable admitted facts rather than caller
substitution?

## Success boundary

The campaign may close only if:

- effect-tuple identity is derived independently of authority identity and is
  locked atomically with exact authority consumption;
- the winning admission returns or retains an unforgeable process-local
  continuation capability that cannot be reconstructed after process loss;
- a fresh process with every durable record and request input cannot begin the
  first callback;
- distinct authorities for the same semantic effect tuple produce exactly one
  admission winner without silently burning the losing authority;
- callback request and receipt semantics derive exclusively from sealed
  admission/source records;
- tampered or merely self-consistent caller authority arrays cannot alter
  expected-return, provider, destination or authority lineage;
- interruption, expiry, revocation, cancellation and unknown-outcome behavior
  remains fail-closed; and
- local and CI test totals are attributed to their actual runs.

Passing same-authority contention alone is insufficient.

## Planned sequence

Campaign countdown at selection: six stages including Preparation Batch 0.

1. **Preparation Batch 0 — continuation, tuple and evidence inventory.** Trace
   the exact admission-to-callback call graph, process boundary, capability
   lifetime, authority and effect lock scopes, replay derivation, receipt input
   provenance, all fresh-process/tamper bypasses and source-attributed test
   evidence. Produce the smallest correction plan. No runtime change.
2. **Batch 1 — corrected contracts and identities.** Separate semantic effect-
   tuple identity from authority-consumption identity; define the ephemeral
   continuation capability and immutable receipt-input contract. No provider
   callback and no credential access.
3. **Batch 2 — atomic tuple winner and continuation custody.** Atomically bind
   effect-tuple winner, authority consumption and process-local continuation
   consumption under an explicit lock order. A restarted process must retain
   reconciliation authority only.
4. **Batch 3 — admission-derived continuation and receipt binding.** Remove
   caller-supplied authority semantics from callback/receipt construction and
   derive the exact request, provider, return contract and lineage from sealed
   admitted records. Provider doubles only.
5. **Batch 4 — adversarial process, contention and substitution proof.** Prove
   fresh-process first-continuation refusal, distinct-authority/same-tuple
   single-winner behavior, losing-authority disposition, tampered authority
   refusal, interruption, expiry/revocation/cancellation and container bypass
   closure without network or external I/O.
6. **Batch 5 — evidence reconciliation and terminal Blackquill audit.** Start
   from clean merged Batch 4 `main`; reconcile local/CI evidence by source and
   independently decide whether the original corridor may resume at its still-
   separately-authorized Batch 7 gate.

## Preparation Batch 0 requirements

Classify each surface as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT` or
`DEFERRED_BOUNDARY`. At minimum inspect:

1. `NativeEffectAtomicAdmissionService`, capability issuer/object and every
   construction/call site;
2. `NativeEffectDoubleExecutionService` continuation inputs and receipt binding;
3. authority-id, replay-id, admission-id and semantic effect-tuple derivation;
4. exact authority, effect-tuple, native and immutable-store lock ordering;
5. process death immediately after admission return and before callback start;
6. distinct sealed authorities carrying the same effect tuple;
7. forged, stale, resealed and old-digest authority substitutions;
8. expected-return, provider, destination, payload and idempotency provenance;
9. losing-authority state and whether it remains usable or explicitly refused;
10. all existing Batch 3–6 tests, workers, container wiring and missing cases;
11. local versus GitHub test-result provenance and documentary consumers; and
12. the smallest acyclic implementation and proof sequence.

## Authorization grammar

This selection authorizes Preparation Batch 0 only.

Requests to continue, proceed, push, merge, update steps, write a prompt, cite a
Latin motto or run green tests do not authorize Batch 1 or any live effect.
The old live-trial marker does not override the suspension recorded here.

## Preparation boundary

Authorized now: repository inspection, versioned inventories/call graphs/lock
matrices/reading ledgers, completion handoff and documentary/structural tests.

Not authorized now: production runtime or service wiring changes; authority or
capability issuance/consumption; credential access; provider invocation;
network/external I/O; mission or live-trial execution; email sending; Iron Gate
or Lazaretto opening; non-disposable runtime publication; Batch 1; restoration
of the original Batch 7 gate; or alteration of historical evidence.

## Exit criterion

The remediation closes only after Batch 5 independently verifies the corrected
runtime and evidence from clean merged Batch 4 `main`. Closure restores no live
authority. The original Batch 7 would still require a new exact Operator marker
and approved disposable operation/destination after its suspension is lifted.
