> Historical selection, superseded by `docs/next-campaign-protected-mission-authority.md`.
> Use `docs/handoffs/protected-mission-authority-local-ready.md` for the current run.
> Preserve the previous candidate/test evidence; do not resume its blocked real Batch 4.

# Canonical Mission Authenticity and Real Snapshot Remediation — local entrypoint

Start from clean local `main` exactly at campaign-entry commit
`4b0eddee3410d74f9931963b460e5efe8c401e0f`. The accepted runtime implementation beneath the
documentation-only selection merge remains
`2527b33925bf3ef47d029786e60a6aefe752737b`.

Read completely:

1. `docs/canonical-mission-thread-post-review-blackquill-v1.md`
2. `docs/next-campaign-canonical-mission-authenticity-real-snapshot-remediation.md`
3. `contracts/mission-planning.md`
4. `contracts/mission-authorization.md`
5. `src/Imperium/Runtime/Curia/MissionAuthorizationDerivationService.php`
6. Current native-effect reconciliation authorization, issuance, capability, resolver, custody,
   consumption and claim-derivation sources and their tests
7. The quarantined local branch range
   `2527b33925bf3ef47d029786e60a6aefe752737b..3c4890ffd30f403f72a35b92f1e639d51c8c98f8`

Create local branch
`codex/canonical-mission-authenticity-real-snapshot-remediation`.

Execute Preparation Batch 0 and Batches 1–3 exactly as selected. Commit each batch separately and
run focused tests after each batch plus the complete local PHPUnit suite after Batch 3.

Do not merge or cherry-pick the quarantined candidate wholesale. Recover only shapes justified by
the controlling review and reimplement every authority or evidence boundary from the accepted
baseline.

Hard stop after Batch 3:

`CANONICAL_MISSION_AUTHENTICITY_BATCH_3_COMPLETE_AWAITING_OPERATOR_ORDER`

At that stop, return the exact local commit, full test result, clean worktree status, approval-ready
mission dossier and digest, actual target commit/tree, requested permissions, explicit
prohibitions, budget, expiry, success criteria and the one exact action reserved for the human
Operator.

Do not perform that action. Do not execute the reference mission. Do not access the network,
credentials or providers. Do not push, open or merge a pull request, modify remote `main`, perform
external I/O, run a live trial or begin Batches 4–6.
