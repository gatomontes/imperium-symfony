# PPC6 local handoff — countdown 3–5

**Ready under existing approval.** [Campaign](../next-campaign-provider-designation-assignment.md), [implementation plan](../provider-designation-assignment-implementation-plan.md), [exact retained scope/approval](provider-designation-assignment-scope.json). The remaining designation and assignment/use work is covered by approved PPC2 A–F; do not request that approval again.

From an existing repository checkout in PowerShell, use unused branch/path names and preserve earlier worktrees:

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-designation-assignment ../imperium-provider-designation-assignment origin/codex/provider-designation-assignment-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-designation-assignment
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-designation-assignment-local-prompt.txt -Raw
```

Give local Codex the complete prompt. It contains the existing approval references and the actual implementation requirements. Return `imperium-ppc6-public-review.zip`, its separate `.zip.sha256`, and the committed report specified in the campaign.

**Countdown: 3–5 → 3–5, decrement 0.** Complete R3 acceptance could yield 2–4. The known R4 FRESH establishment gap may block final same-root application/use proof; it must remain explicit and cannot be bypassed with a synthetic parent or fabricated authority. Preparation, approval and partial components earn no decrement. All five flags remain false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty.

*Imperium via solitaria est.*
