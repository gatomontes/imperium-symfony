# Mission Amendment Authority and Evidence Binding — local corrective campaign

`MISSION_AMENDMENT_CORRECTION_SELECTED`
`LOCAL_BATCHES_0_THROUGH_3_AUTHORIZED`
`IMPLEMENTATION_ACCEPTANCE_PENDING`

## Purpose and exact bases

Correct two source-review findings in the protected mission candidate without restarting its completed construction campaign:
AM01, unsigned proposal preparation changes active authority; AM02, amended authorization inherits lifecycle/inspection evidence from an earlier authorization sharing its mission ID.

Preserved implementation base: `7deb84b9f687ecb09c93f568464a606f0a602a89` on
`codex/protected-mission-authority-operator-ceremony`.
Accepted main before this documentation selection: `0099c5f7e4008fe06cafc9c5dfa3458dc68f4db9`.
The implementation candidate is not accepted into main. This campaign explicitly permits a new corrective descendant of that candidate; it does not merge the candidate into main or change its preserved branch.

Read the controlling review `docs/protected-mission-amendment-review.md` and runner
`docs/handoffs/mission-amendment-correction-local-ready.md`.
Existing protected mission requirements, mission-planning and mission-authorization contracts continue to apply except where this selection explicitly replaces the old entry sequence and amendment semantics.

## Selected amendment policy

Untrusted proposal creation is inert with respect to active authority and already presented pending challenges. A proposal may create its own bounded non-authorizing record. Knowing a mission or challenge ID must not grant power to revoke, supersede, cancel, hide or replace it. Failed/unsigned submissions must leave authority, progress, evidence and existing challenge usability unchanged. Do not rely on a trusted human forwarding stdio requests as authorization for their contents.

Choose a signed activation at canonical authorization derivation: the exact approval payload must disclose the mission, new dossier/version/digest, expected current authorization ID/digest (or explicit absence for first approval), and the requested replacement. Preparing, exporting or submitting a proposal does not replace the active authorization. Derivation verifies signature and current predecessor inside the owner's common transaction, consumes the derivation authority and atomically installs the successor. A stale competing approval refuses without deactivating the actual current version. Signed control remains the route for explicit cancellation/revocation.

Do not allow multiple pending proposals to overwrite a mission-wide pointer that makes an earlier challenge unusable. Bound proposal storage and report capacity refusal; unsigned input must not evict active or approved records. Resource exhaustion protection remains bounded rather than a claim of unlimited availability.

Each authorization receives a distinct execution generation. Bind commissions, capabilities, lifecycle, inspection evidence, receipts and status to that exact authorization/dossier/generation and its target, allowlist and budgets. A successor starts fresh at AUTHORIZED. This focused correction does not introduce progress transfer or automatic evidence revalidation between generations. Preserve predecessor history for explicit historical inspection, never as successor progress.

An amendment to a nonterminal generation may be activated as above. A completed/failed/cancelled mission cannot be reopened by a new nonce, new authority, proposal or same-ID amendment; a genuinely new run needs a new mission identity and approval. Signed revocation/expiry and historical status must remain distinguishable from successful completion.

The existing common exclusion must cover activation versus consumption/revocation. Define the publication point and prove both forced orderings: an old consumption published before activation remains historical; after activation old capabilities refuse. No new version may complete using the predecessor's inspection even if commit/tree are identical.

## Steps

| Batch | Work | Exit evidence |
| --- | --- | --- |
| 0 | Preserve candidate/evidence, inspect canonical paths and reproduce AM01/AM02 using public protocol and disposable identities. Freeze policy, read set and affected-test inventory. | Reproducible old behavior with exact SHA; regression expectations and state/evidence hashes. No production fixes in this batch. |
| 1 | Separate inert proposals from signed predecessor-bound activation; integrate canonical approval/derivation and control. | Unsigned/failed/stale requests cannot displace active authority or pending approvals; first authorization and signed replacement work; competing activation has one winner. |
| 2 | Bind all execution state/evidence/status to exact authorization generation; prohibit inherited progress. | A-inspect/B-amend/B-complete refuses; B fresh inspection succeeds; A remains historical; old capabilities and wrong-generation receipts refuse. |
| 3 | Real CLI/PowerShell and deterministic separate-process proof, followed by a separately sequenced local audit. Update flow, steps, runbook and evidence. | Both races' forced orderings; fresh exact-Git mission; focused/full results; no unexplained test changes; precise residual limits. |

All four batches are authorized sequentially locally with separate commits. Batch 3 implementation/test changes must be committed before its audit phase begins; identify that tested SHA. Run focused tests after each change, full `php vendor/bin/phpunit tests` after Batch 2 and at terminal audit. Repeat only to resolve actual failures or code changes. A later documentation-only evidence commit must be identified as not separately tested.

## Required regressions

1. Prepare/export unsigned B with A's mission ID while A is active: A still verifies; A's outstanding challenge, capabilities, progress, history and receipt are not displaced. Proposal record creation is allowed; whole-journal equality is not the appropriate success assertion.
2. Invalid signature, altered predecessor/dossier, expired approval, replay and rejected activation: no authority/evidence mutation or partial successor. Assert exact affected-record hashes and absence of new authority.
3. Inspect A, activate signed B with the same commit/tree but a different allowed path set. B's complete capability refuses before a new admit/inspect sequence. B then finishes with only its own authorized findings. Also vary budget and repository identity independently.
4. Query A after B progresses: return A's own historical lifecycle/receipt and inactive/currentness indication. Query B: never expose A's evidence as B's results.
5. Race two approved replacements of the same predecessor: exactly one activation wins; the other refuses as stale and cannot deactivate the winner.
6. Force consume-before-activate and activate-before-consume using real processes and controlled barriers. Do not rely only on random scheduling. Test revocation races and restart/lost-response replay with the same transaction owner.
7. Terminal same-ID re-entry refuses; fresh mission identity still works. Preserve real clock expiry and trust/key/protocol refusal proofs.

Reproduction may use test-only signing keys but must not directly rewrite the journal to create the two primary counterexamples. Corruption tests can separately mutate disposable state with explicit labeling. Do not fix these defects by banning all amendments or weakening existing valid execution tests.

## Previous test and branch handling

Keep the previous candidate branch and the two earlier quarantined branches unchanged. Preserve the reported audit result 2665 tests / 52515 assertions at `f12524f2b25942b8149e8c455a39da5d26217a9b`; its later evidence commit is `7deb84b9f687ecb09c93f568464a606f0a602a89`. Those results are limited historical local evidence, not new-head proof or remote CI.

Retain existing useful tests. Record every modified/removed expectation and its replacement; the former unsigned-prepare-invalidates-authority expectation, if present, is explicitly superseded by AM01 policy. Preserve old source/results rather than rewriting history. Add rejection regressions and the positive amendment path. Do not reuse old keys, dossiers, capabilities or runtime records in new test instances.

No actual installation or journal migration is authorized. If a state schema changes, old/mixed records must refuse at use or remain explicitly historical; do not silently reinterpret mission-ID-only records as generation-bound. Document a future owner-reviewed migration requirement. Never reset a real journal to make a test pass.

## Closure and bounds

This is a local correction and self-audit, not independent acceptance. Return all exact bases/commits/tree, focused/full counts and skips, source-to-finding evidence, changed-test map, old/new version status examples and tested PowerShell commands. Retain:
`DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED`.
Windows-only startup, loose SHA-1 Git-object support, bounded journal capacity and unproved hardware power-loss behavior remain explicit limitations.

Successful local status:
`MISSION_AMENDMENT_CORRECTION_LOCAL_COMPLETE_PENDING_INDEPENDENT_REVIEW`.
Otherwise report `IMPLEMENTATION_INCOMPLETE` with concrete remaining evidence gaps.

Stop before real Operator setup/enrollment/signing, installer execution, real mission, provider/credential/external-system access, Iron Gate/Lazaretto or live Batch 7. No implementation push, PR, merge to main or branch deletion. A read-only initial Git fetch and local integration of this documentation selection into the new corrective branch are authorized exceptions, as detailed in the runner.

*In imperium fidimus.*
