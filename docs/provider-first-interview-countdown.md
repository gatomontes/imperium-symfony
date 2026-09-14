# Countdown to the first live Courtyard interview

**Working estimate: 4–6 batches remaining. R2 native tenure synchronization is accepted offline after PPC4 source review, fresh hosted CI and integration.**

The owner requested this countdown after PPC2 acceptance and said “proceed. remember countdown”. The estimate starts at accepted main `cd3a46ba45d0b89208c68e39225a0f8e08ca6de6`, tree `451140919d570d196384b5e0802ac076c67ba579`. Earlier O0–O5 and PPC0/PPC1/PPC2 component acceptances are already reflected in this starting estimate; they are not new countdown credits.

The milestone is one separately authorized, bounded live Courtyard interview, with its own authority, settings, provider, custody and return prerequisites. Full mission execution is a later milestone and is outside this estimate.

| Remaining outcome | Estimated additional batches | State |
| --- | --- | --- |
| R1. Competent native model seal → exact O4 binding/configuration/generation correspondence | 1 | Open; needs a concrete source/competence decision |
| R2. Native tenure and registry synchronization, including indirect lock order | 0 | **Accepted offline through PPC3 + PPC4** |
| R3. Designation/revocation/supersession and shared assignment/application/use verifier | 1 | Open; depends on R1 and R2 |
| R4. Combined FRESH institutional establishment | 0–1 additional | Required outcome; may be closed within another internal batch, otherwise needs its own |
| R5. Account/access, base-model and cognition evidence | 1–2 | Three open production ports; batching depends on compatible provider evidence and actual inputs |
| R6. Separately authorized bounded commissioning and first live interview | 1 | Deferred until prerequisites are met and live authorization is explicit |

The remaining internal outcomes R1/R3/R4 account for 2–3 batches; provider evidence for 1–2; commissioning for one. Accepted R2 now contributes zero additional batches. These are planning estimates, not guaranteed release dates or permission to combine incompatible work. R4 remains mandatory even when its additional batch count is zero. Missing external facts or a new contract gap can raise the estimate.

## Counting rule

Every subsequent preparation, implementation report and receiving review must show: previous range, selected outcome, accepted outcome evidence, revised range and reason. Keep [the machine record](provider-first-interview-countdown.json) and this page consistent, and link them from the roadmap/current-status/mission-flow documents.

Preparation, code submission, a green test run by itself, and accepted components that leave the selected outcome open do **not** decrement the countdown. Source review and complete required CI must accept the named outcome; any live outcome also requires its separately authorized observed result. Record the exact integration/review reference. Do not decrement a second time for a documentation follow-up or rename.

PPC4 receiving review accepted complete R2 closure with no new scope, applying the previously conditional reduction exactly once: **5–7 → 4–6**. [Review](reviews/provider-storage-successor-review.md), [exact integration/CI record](provider-storage-successor-reviewed-integration.json). Prior PPC3 partial acceptance and PPC4 preparation/approval remain zero-credit historical entries.

## Ledger

| Entry | Evidence | Count effect |
| --- | --- | --- |
| Starting estimate after PPC2 | [PPC2 receiving review](reviews/provider-profile-designation-review.md); owner estimate discussion | Set remaining range to 5–7 |
| PPC3 preparation selected | [Local campaign](next-campaign-provider-native-tenure.md) | No decrement; 5–7 remains |
| PPC3 component accepted; R2 open | [Receiving review](reviews/provider-native-tenure-review.md), PR #818, merge `fe65bd91efa3eb5cc5bc07a1ac48658f12e46edf` | No decrement; 5–7 remains because generic institutional writers are unfenced |
| PPC4 successor proposal prepared; approval pending | [Clauses A–F](provider-storage-successor-contract-v1.md), [local handoff](handoffs/provider-storage-successor-ready.md) | No decrement; 5–7 remains |
| PPC4 clauses A–F owner-approved | [Exact approval record](handoffs/provider-storage-successor-approval.json) | No decrement; implementation and complete R2 acceptance remain outstanding |
| R2 accepted after PPC4 | [Receiving review](reviews/provider-storage-successor-review.md); PR #822, merge `499877920e06637c109abd741b25a78f06ef7e72`, CI 34853394047 | **Decrement 1; 5–7 → 4–6** |

No live action is authorized by this countdown. All five operational flags remain false, `DEFER_ENROLLMENT` remains, and the actual retry allowlist is empty.
