# PPC7 local handoff — approved; countdown 3–5

**Ready for bounded offline implementation.** The owner approved the [exact clauses A–F](../provider-fresh-establishment-proposal-v1.md) with the reply “approved” after preparation PR #831. [Exact approval/hash record](provider-fresh-establishment-approval.json), [implementation plan](../provider-fresh-establishment-implementation-plan.md), [complete local prompt](provider-fresh-establishment-local-prompt.txt). The original proposal bytes remain unchanged. No repeat approval is required for this scope; implementation acceptance remains outstanding.

From an existing repository checkout in PowerShell, use unused branch/path names and preserve previous worktrees and installed applications:

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git -c core.autocrlf=false worktree add -b codex/provider-fresh-establishment ../imperium-provider-fresh-establishment origin/codex/provider-fresh-establishment-approved
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
