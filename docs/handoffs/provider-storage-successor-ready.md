# PPC4 local handoff — countdown 5–7

The [campaign](../next-campaign-provider-storage-successor.md) and [source successor clauses A–F](../provider-storage-successor-contract-v1.md) are prepared. **Runtime implementation awaits approval of that exact proposal.** The [approval record](provider-storage-successor-approval.json) records its hash and pending state. The preceding “proceed” selected preparation; it is not relabelled as approval of unseen clauses. Approval permits bounded offline work, never live operations or automatic implementation acceptance.

From an existing repository checkout in PowerShell, fetch and create an isolated worktree. Choose unused branch/path names if necessary; do not remove previous worktrees or installed state.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-storage-successor ../imperium-provider-storage-successor origin/codex/provider-storage-successor-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-storage-successor
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-storage-successor-local-prompt.txt -Raw
```

Give local Codex the complete prompt and the owner's actual A–F approval reference. If approval is already supplied locally, record it and continue; do not ask for it a second time. Fetching/reading this preparation is safe before approval. Return the complete PPC4 packet and separate checksum, with the report, writer/alias map, successor evidence and actual test results specified in the campaign.

**Countdown: 5–7 → 5–7, decrement 0.** Full receiving acceptance of R2 could make it 4–6; preparation and partial improvements cannot. All five flags remain false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty.
