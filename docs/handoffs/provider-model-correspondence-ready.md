# PPC5 local handoff — countdown 4–6

The owner **approved clauses A–F for bounded offline implementation**. The [campaign](../next-campaign-provider-model-correspondence.md), [unchanged proposal](../provider-model-correspondence-proposal-v1.md) and [exact approval/hash record](provider-model-correspondence-approval.json) are ready. The proposal’s historical heading remains unchanged; the separate approval record governs. Continue without asking for this approval again. Implementation acceptance remains outstanding.

From an existing repository checkout in PowerShell, use unused branch/path names and preserve prior worktrees:

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-model-correspondence ../imperium-provider-model-correspondence origin/codex/provider-model-correspondence-approved
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-model-correspondence
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-model-correspondence-local-prompt.txt -Raw
```

Give local Codex the complete prompt. The actual owner approval and exact Git-byte proposal hash are already retained in the record. Return `imperium-ppc5-public-review.zip`, its separate checksum and the report specified in the campaign.

**Countdown: 4–6 → 4–6, decrement 0.** Accepted complete R1 could yield 3–5; preparation, approval and partial results earn no decrement. R2 stays accepted. All five operational flags stay false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty.

*Imperium via solitaria est.*
