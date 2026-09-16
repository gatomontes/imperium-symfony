# PPC10 local implementation handoff

**Ready under exact C1.1–C1.6 / C2.1–C2.4 approval.** The owner's immediate reply was “approved”; [exact approval record](provider-staged-assignment-approval.json), [decision](provider-staged-assignment-decision.json), [unchanged approved proposal](../provider-staged-assignment-proposal-v1.md). No repeat approval is required for this scope. Approval is not implementation acceptance.

Implement staged native admission and substantive fitness, then full R3 initial/replacement assignment and actual shared protected settings use. Follow the complete [local prompt](provider-staged-assignment-local-prompt.txt), [proof plan](../provider-staged-assignment-plan.md) and [scope/source record](../provider-staged-assignment-implementation-source-record.json). Original proposal/inventory pending notices are historical; the new approval supersedes only that status.

## Pull into a new isolated worktree

Run from an existing `imperium-symfony` checkout. The [PowerShell script](provider-staged-assignment-pull.ps1) preserves existing checkouts, refuses existing destination/branch names, creates a new sibling `imperium-ppc10` worktree with newline conversion disabled, checks accepted ancestry, exact proposal/approval/source hashes and all 82 baseline source byte/blob observations, then prints the approval and complete local prompt.

```powershell
$ErrorActionPreference = 'Stop'
git fetch origin main codex/provider-staged-assignment-ready
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed' }
$ppc10Published = git rev-parse origin/codex/provider-staged-assignment-ready
if ($LASTEXITCODE -ne 0) { throw 'Preparation unavailable' }
$ppc10Script = git show ('{0}:docs/handoffs/provider-staged-assignment-pull.ps1' -f $ppc10Published)
if ($LASTEXITCODE -ne 0) { throw 'Pull script unavailable' }
& ([scriptblock]::Create(($ppc10Script -join "`n"))) -ExpectedPreparation $ppc10Published
```

If needed, pass an unused `-WorktreePath 'E:/htdocs/imperium-ppc10-new'` and `-LocalBranch 'codex/provider-staged-assignment-ppc10-new'`. Do not delete or reset existing worktrees to make room. The script verifies everything before presenting the prompt; a failed post-check leaves the new worktree available for inspection and authorizes no workaround.

The exact printed published preparation commit/tree is the implementation start and sole bounded bundle prerequisite. `6cadc3125db6d9ccdbec9d40264033cd73a94de1` is the pre-preparation accepted main baseline, not that implementation start. Preserve these different identities. Give local Codex the **entire printed prompt**. The approved proposal SHA-256 is `bd817ea572c259874887375389ed3ecea0972ecf6ff0bb22b1cf72e75d06844d`.

## Return

Return the committed `provider-staged-assignment-report.md`, `imperium-ppc10-public-review.zip`, separate `.zip.sha256`, and independent public verification output. The report must identify exact tested/final commits/trees, complete original test/guard evidence, all remaining gaps, external synthetic/unavailable facts and the unchanged operational boundary. The packet contains no signing keys, private fixture roots, installed state, credentials or vendor/cache.

Local work stops at commits and public review deliverables. Receiving source review, fresh complete unchanged hosted CI and acceptance follow separately. Default Composition and missing production ports stay unchanged. No installed migration, provider calls, enrollment or live authority.

**Countdown 3–4, unchanged; decrement zero.** R1/R2/R4 remain accepted; R3 remains open until receiving accepts the complete outcome. Full R3 acceptance could leave 2–3, not applied. R5 remains open, R6 deferred. All five flags false; `DEFER_ENROLLMENT`; actual retry allowlist `[]`. No subagents.

*Imperium via solitaria est.*
