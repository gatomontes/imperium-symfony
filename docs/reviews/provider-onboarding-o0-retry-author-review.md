# O0 v1.3.1 targeted author review

Status: AUTHOR_REVIEW_ONLY; INDEPENDENT_REVIEW_REQUIRED.
This is the executor's assessment, not a new independent verdict. The preserved
[F1/F2 review](provider-onboarding-o0-freeze-independent-review.md) accepted its
exact v1.2 source; its acceptance is not extended to new retry behavior.

| Boundary | Review conclusion / correction | Required implementation proof |
| --- | --- | --- |
| Finite attempt graph | Exactly four ordered attempts per W1/W2/W3, W1→W2→W3 success dependency; skipped retries have no invented claims | Reject duplicate/cyclic/cross-group/non-immediate edges; prove all success placements |
| Slot dependencies | Failure predecessor is only in owning run_condition; slot success dependencies match its owner | No alternate slot interface or dependency bypass |
| Group input | Closed public/group-result selectors; original selected outcome and semantic bytes frozen once; current authority rechecked | Catalogue/Profile/result drift cannot change a retry's input |
| Application input | Fixed result_step_id removed from draft terms; three selected group outcomes feed one local view | No application on last-attempt termination alone, unknown or failed predicates |
| F1 replay | Exact command recognized before currentness, changed fingerprint conflicts; new command cannot recover old step | Crash/replay after admission, response, settlement and application; stale heads |
| F2 authority | Twelve distinct one-use assessment slots; S/B/P rules unchanged; policy not self-authorizing | All tagged variants, original trust and current holder at each custody checkpoint |
| Failure evidence | Empty DeepSeek retry allowlist; HTTP/timeout/exception/model text cannot assert safe retry | Attributable final outcome/usage and provider-specific fixed verifier before any reason admitted |
| Shared resources | Admission/consumption/reservation atomic under one aggregate; no lock over I/O | Real concurrent attempts, shared budgets, revocation races and unknown exposure |
| Recovery | Evidence-only resume cannot dispatch; original unknown claim remains fenced across IDs | Interruptions at every custody boundary, lost result and disputed lineage |
| Completion | Success skips remaining attempts; terminal/fourth failure blocks; every dispatch counts | Separate progression outcome from unsettled usage; never zero-fill absence |
| Whole-set application | Exactly Courtthane/formation Locksmith settings under approved tuples/current generations; one slot | Atomic receipt/assignment commit and duplicate recognition; no partial set or Augur replacement |
| Institutional scope | Fresh branch requires real root/window/Profile evidence; no cutover fallback | Missing producer/consumer bridges remain missing; no conversion of development acts or Castellan appointments |

43 passing author checks include graph mutations and small abstract protocol
oracles. Serialized competing-admission examples are not concurrent process tests;
synthetic safe-failure classifications are not provider evidence. Source/API/runtime
integration is unimplemented. The runtime proof areas above and the original
F1/F2 acceptance cases must be exercised only after separately selected implementation.
No new independent verdict, live qualification or operational flag follows here.

The current [report](../handoffs/provider-onboarding-o0-provider-report.md) identifies
actual entry/import/final-packet identities, delivery and remaining evidence.
