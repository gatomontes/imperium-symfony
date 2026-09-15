# PPC7 local handoff — prepared, approval pending

The [exact new clauses A–F](../provider-fresh-establishment-proposal-v1.md) and [complete implementation plan](../provider-fresh-establishment-implementation-plan.md) are reviewable. [Approval/hash record](provider-fresh-establishment-approval.json). PPC6 section 3 explicitly excludes a new establishment contract decision; the prior “proceed” authorizes preparation but does not approve these newly written terms. No implementation is accepted or started by this handoff.

The [complete local prompt](provider-fresh-establishment-local-prompt.txt) is ready for use after approval. The approval follow-up must retain the proposal bytes, record the exact reply and publish `codex/provider-fresh-establishment-approved`. Do not substitute the preparation branch for an approved implementation start.

For review now, from an existing checkout in PowerShell:

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git show origin/codex/provider-fresh-establishment-preparation:docs/provider-fresh-establishment-proposal-v1.md
if ($LASTEXITCODE -ne 0) { throw "Proposal read failed" }
```

After the exact approval record is published, use unused branch/path names:

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-fresh-establishment ../imperium-provider-fresh-establishment origin/codex/provider-fresh-establishment-approved
if ($LASTEXITCODE -ne 0) { throw "Approved worktree creation failed" }
Set-Location ../imperium-provider-fresh-establishment
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
$ppc7Approval = Get-Content docs/handoffs/provider-fresh-establishment-approval.json -Raw | ConvertFrom-Json
if ($ppc7Approval.status -ne 'OWNER_APPROVED_FOR_BOUNDED_OFFLINE_IMPLEMENTATION') { throw "PPC7 approval is pending" }
$ppc7Hash = (Get-FileHash docs/provider-fresh-establishment-proposal-v1.md -Algorithm SHA256).Hash.ToLowerInvariant()
if ($ppc7Hash -ne $ppc7Approval.proposal_sha256) { throw "Proposal bytes differ; check exact Git blob and checkout newline settings" }
Get-Content docs/handoffs/provider-fresh-establishment-local-prompt.txt -Raw
```

Give local Codex the complete prompt. Return the committed report, `imperium-ppc7-public-review.zip` and separate `.zip.sha256` with the [campaign's exact evidence and verification contents](../next-campaign-provider-fresh-establishment.md). Preserve earlier worktrees and installed applications; local work stops at commits for receiving review.

**Countdown: 3–5 → 3–5; decrement 0.** Complete R4-only receiving acceptance would leave an estimated 3–4; R3 remains open. All five flags remain false, `DEFER_ENROLLMENT`, empty actual retry allowlist. No live work or subagents.

*Imperium via solitaria est.*
