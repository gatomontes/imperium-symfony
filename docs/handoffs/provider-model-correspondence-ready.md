# PPC5 local handoff — countdown 4–6

The [campaign](../next-campaign-provider-model-correspondence.md) and [exact A–F proposal](../provider-model-correspondence-proposal-v1.md) are prepared. **Implementation awaits approval of this specific new permanent-seat model-preparation provision.** [Approval status and proposal hash](provider-model-correspondence-approval.json). It preserves separate Profile approval, designation, appointment and O4 application. Fetching and reading the preparation requires no additional approval.

From an existing repository checkout in PowerShell, use unused branch/path names and preserve prior worktrees:

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-model-correspondence ../imperium-provider-model-correspondence origin/codex/provider-model-correspondence-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-model-correspondence
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-model-correspondence-local-prompt.txt -Raw
```

Give local Codex the complete prompt and the owner's actual approval reference for A–F. If that approval is already present locally, record the exact proposal hash and proceed without asking again. Return `imperium-ppc5-public-review.zip`, its separate checksum and the report specified in the campaign.

**Countdown: 4–6 → 4–6, decrement 0.** Accepted complete R1 could yield 3–5; preparation, approval and partial results earn no decrement. R2 stays accepted. All five operational flags stay false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty.

*Imperium via solitaria est.*
