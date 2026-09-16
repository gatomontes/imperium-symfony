# PPC10 v5 continuation — approved local handoff

The owner approved the three narrow v5 source exceptions: SharedExposure dispatch, Augur migration-history validation, and founding-rule derivation. The owner also explicitly approved pushing and merging this documentation handoff. [SharedExposure approval](provider-shared-exposure-extension-approval.json), [two-reader approval](provider-v5-reader-extension-approval.json), [combined scope and flow](../provider-shared-exposure-extension.md).

The component implementation remains unaccepted on `codex/ppc10-components-pending`, commit `b6fffc5cef31f54d47993ceeec19d5124513f754`. Its tree `3b0bd4b352066e13c6aeb806971917bd4cee81f7` equals the uploaded final candidate's tree. This documentation handoff does not merge those components into main or imply full R3 acceptance.

Run from an existing repository checkout in PowerShell:

```powershell
$ErrorActionPreference = 'Stop'
git fetch origin '+refs/heads/codex/ppc10-shared-exposure-approved:refs/remotes/origin/codex/ppc10-shared-exposure-approved'
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed' }
$v5Published = git rev-parse origin/codex/ppc10-shared-exposure-approved
if ($LASTEXITCODE -ne 0) { throw 'Preparation unavailable' }
$v5Script = git show ('{0}:docs/handoffs/provider-v5-extension-pull.ps1' -f $v5Published)
if ($LASTEXITCODE -ne 0) { throw 'Pull script unavailable' }
& ([scriptblock]::Create(($v5Script -join "`n"))) -ExpectedPreparation $v5Published
```

The [script](provider-v5-extension-pull.ps1) fetches both published branches, verifies the exact component tree, creates a new sibling `imperium-ppc10-v5` worktree, and replays only the documentation commit sequence. Existing worktrees remain intact. It requires normal local Git commit identity for the documentation cherry-picks. Existing destination/branch names refuse; choose unused `-WorktreePath` / `-LocalBranch` parameters. Any failure leaves the new worktree for inspection and authorizes no automatic reset.

Retain the printed documentation preparation, local continuation start/tree and published component bundle prerequisite as distinct identities. Give local Codex the **entire printed continuation prompt**. That prompt incorporates the unchanged original C1/C2 implementation prompt and proof plan, superseding only the additional source permissions, continuation start/prerequisite and countdown. Earlier pending notices in the preserved proposal are historical; the new exact approval record controls.

Complete the actual v5 admission/migration, assessment, application/replacement and protected-use chain, the full existing proof matrix and source-bound validation. Return the committed report, new complete public review ZIP, separate checksum and independent verifier output. Local runtime work stops at commits and public deliverables for receiving review and fresh hosted acceptance.

**4–5 remaining; decrement zero.** R1/R2/R4 accepted; R3/R5 open; R6 separately authorized and deferred. All five flags false; `DEFER_ENROLLMENT`; retry allowlist `[]`. No live authority is granted.
