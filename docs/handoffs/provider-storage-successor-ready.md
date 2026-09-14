# PPC4 local handoff — countdown 5–7

The owner **approved clauses A–F for bounded offline implementation** in direct response to the proposal question after PR #820. The [approval record](provider-storage-successor-approval.json) retains the exact conversational reference and proposal SHA-256. The [campaign](../next-campaign-provider-storage-successor.md) is ready. The [proposal](../provider-storage-successor-contract-v1.md) stays byte-for-byte unchanged, including its historical proposed-state heading; the separate approval record controls current approval. No repeated approval request is needed within this scope. Implementation acceptance remains a later receiving review.

From an existing repository checkout in PowerShell, fetch and create an isolated worktree. Choose unused branch/path names if necessary; do not remove previous worktrees or installed state.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-storage-successor ../imperium-provider-storage-successor origin/codex/provider-storage-successor-ready
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-storage-successor
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-storage-successor-local-prompt.txt -Raw
```

Give local Codex the complete prompt. The approval reference is already committed in this ready branch; verify its proposal hash and proceed without another permission request. Return the complete PPC4 packet and separate checksum, with the report, writer/alias map, successor evidence and actual test results specified in the campaign.

**Countdown: 5–7 → 5–7, decrement 0.** Full receiving acceptance of R2 could make it 4–6; preparation and partial improvements cannot. All five flags remain false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty.
