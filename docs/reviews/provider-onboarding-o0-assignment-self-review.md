# Imperium O0 final review — caba3201

**Verdict: FINAL_O0_FREEZE_WITHHELD — one application-contract blocker.**
The V1 checker correction passes, and P1–P9 approval is accurately bound to the
presented source. No integrity discrepancy was found. This is a self-review: I
authored the correction and approval-record commits. It supplies no independent
reviewer attestation and does not supersede historical independent review authorship.

Reviewed commit: `caba3201aca8191ed403ddd75d5e50461c2e42c1`.
Tree: `9174323715a8dc2d2fbedc8a9f5f94349acb8ad8`.
Correction: `d3391413f69cb11b01871c91cbc6725245b1a5da`.
Provider submission: `2212281ea33ea19c8c70b048b8e482bbe23dfa12`.
Campaign selection: `6adc635ed9d89bbff7b472cc050de4c6e86d418c`.

## A1 — fitting candidates do not determine an assignment

**Priority: P1 for final contract freeze; no runtime vulnerability claimed.**

The current W2 and W3 workloads ask for fitting candidates and limitations.
Their closed result proposal contains candidate_rows, predicates and FIT/NOT_FIT/
UNKNOWN. It contains no selected target binding or ranking. Meanwhile, the
assessed_assignment_set rule requires local construction of an exact whole set
from permitted tuples after all predicates pass, without specifying how to choose
among multiple fitting tuples for one role.

Relevant source at this exact commit:

- `docs/provider-onboarding/o0-workloads.json`: W2/W3 instruction and result_contract;
- `contracts/provider-onboarding-continuation.md`: assessed_assignment_set terms;
- `contracts/provider-onboarding-retries.md`: group-result-derived assessment view;
- `docs/provider-onboarding-authority-proposal.md`: BootstrapAssessmentAdmission and
  AssignmentApplication transitions;
- `docs/provider-onboarding-base-model-policy.md`: tie-break explicitly belongs to
  selecting the Augur base model.

Concrete counterexample: suppose Flash and Pro both pass all required predicates
for Courtthane and Locksmith, and both bindings per role are in the permitted
scope. All three groups succeed and all authority/currentness checks pass. The
same result evidence then permits four whole assignment sets:

| Courtthane | Locksmith |
| --- | --- |
| Flash | Flash |
| Flash | Pro |
| Pro | Flash |
| Pro | Pro |

No stated rule selects one of these or explicitly refuses the ambiguity. The
existing base-model cost/tie-break cannot silently become the target-assignment
rule: the base policy explicitly distinguishes base selection from later assessment.
Likewise, adding an unrequested recommendation field, using candidate array order,
or making another cognition call would invent behavior outside the current contract.

The adjacent witness JSON is a synthetic specification counterexample, not a claim
that either provider model is genuinely eligible or that a runtime accepted it.
Providing actual Profiles, trust or account evidence would not decide this case.
This is therefore a contract gap, not merely unavailable live evidence. I missed
it in the preceding review and correction pass.

**Smallest recommended closure:** define assignment resolution as a pure operation
on the existing validated group results and permitted complete tuples. Zero valid
whole sets refuses; exactly one permits that set; more than one refuses explicitly
as AMBIGUOUS_ASSIGNMENT_SET. Refusal consumes no application slot and writes no
assignment. Preserve recognition of already committed receipts. If a later policy
is intended to choose among multiple fitting sets, it must bind that exact selection
rule; this review does not introduce a preference for cheapest or highest-ranked.

Bind the resolved set and its original result/predicate refs into the assessment
view and application receipt. Specify the exact result-to-view mapping so an
implementer cannot invent a winner from a field absent in the closed response.
Exercise zero/one/multiple-set examples and permutation invariance as offline
contract checks. No new paid call, budget approval, provider selection or P1–P9
reapproval is needed to document refusal of ambiguity.

## Checks rerun here

- Verified HEAD/tree, clean worktree and ancestry from campaign selection, provider
  submission and correction. Rechecked the final reconstructed tree and final
  approval source snapshots against Git identities.
- Recomputed all approval source-document hashes against their referenced commit.
  Confirmed approved technical values, candidates/configuration values, targets,
  settled limits and the empty retry allowlist match the presented preparation.
- Reran **62/62** offline specification checks. Separately replayed the two exact
  original V1 mutations; both now reject. The original 43 examples retain their
  earlier attribution. The additional cases do not implement a runtime validator.
- Verified CRC/member uniqueness and available payload manifests/checksums through
  **ten nested archives**, starting with the final policy-approval convenience ZIP.
- Verified executable source, tests, configuration, Composer files and CY/FC
  acceptance are unchanged from campaign selection. Contracts and original
  preparation/workload snapshots are unchanged since the provider submission.
  Ran diff whitespace checks. No source changes followed these checks in this turn.

These results are retained in verification.json and spec-rerun.json. Archive/hash
checks show identity and integrity, not genuine authority. No PHP suite, CI,
runtime/concurrency/crash test, installed-state read, credential operation or
provider/account request was run. Earlier official-page retrievals and production
source findings were inspected/carried forward; they were not refreshed here.

## What passes and what remains deferred

The actual authority graph remains honestly described. Policy admission, fresh
constitution, current Augur occupancy, bounded invocation and persistent assignment
application are separate transitions. Legacy development/checksum acts, ACTIVE
labels, credential presence and catalogue listing do not close their missing
producers or grant invocation. Citadel jurisdiction, Courtthane's exact Seat,
Oracle/Augur and Seneschal's Curia role remain distinct. No fabricated Curia or
Courtthane interview grant substitutes for bootstrap authority.

API-key scope, fixed destination/configuration proposal, pure eligible base-model
comparison, persistent consent, one-use custody, command replay and uncertainty
fencing remain consistent at the reviewed design scope. Alias limitation acceptance
is recorded without pretending immutable underlying weights. The three-retry
ceiling still has no admitted DeepSeek safe-failure reason: the allowlist is empty.

P1–P9 choices are settled. Actual public root/window, trusted issuer, approved
Profiles, complete tuples, current generations, wire/tokenizer/usage, account
entitlement, effective tariff and B1 evidence remain necessary at their specified
admission/live stages. Missing producers/consumers remain later implementation work.
They should not force fabricated live acts merely to begin separately authorized
offline engineering. A1 is the concrete defect preventing an unqualified claim
that the complete assignment contract can now be implemented without making an
unstated selection-policy decision.

**Next executable action:** close A1 in the contract and add the bounded ambiguity
examples; rerun affected checks and produce the exact review candidate. This is a
small O0 contract correction, not another provider research campaign. This review
made no source edit, commit, push, merge or activation. CY/FC acceptance remains
preserved; O1–O5 and live commissioning remain deferred. DEFER_ENROLLMENT, unresolved
B1 and all five false operational flags remain unchanged.
