# O3-B1 ready for one local implementation run

O3-B0 is integrated at `32bb8d76424d6b7447cc423cb7932af35b1f074e`. [Acceptance](../reviews/provider-onboarding-o3-b0-correction-acceptance.md), [campaign](../next-campaign-provider-onboarding-o3-augur-cognition.md), [contract](../../contracts/provider-onboarding-augur-cognition.md), [full local prompt](provider-onboarding-o3-b1-local-prompt.txt).

The selected run closes authentic base projection, constitutional FRESH founding/holder binding and the actual O2/O3 W1/W2/W3 cognition path. It stops before persistent target-assignment application. The new code remains dormant and all tests use temporary roots, synthetic authority and mock HTTP.

## PowerShell pull and isolated worktree

Run from the existing repository checkout. These commands fetch without altering that checkout's files and create a new local branch/worktree from the published preparation branch. If that destination/branch already exists, choose a new name; do not delete or reset an existing worktree.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-onboarding-o3-b1 ../imperium-onboarding-o3-b1 origin/codex/provider-onboarding-o3-b1-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-onboarding-o3-b1
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-onboarding-o3-b1-local-prompt.txt -Raw
```

Start a new local Codex chat in that worktree and paste the full prompt printed by the last command. Run implementation and validation directly; no additional preparation pass is needed. Existing installation material must not be read or copied into test roots.

Return the complete O3-B1 ZIP and individual report/instructions. The local implementation stops for review and fresh full CI before push/merge. Next after accepted integration: O4-B0 atomic whole-set assignments, then O5-B0 offline CLI journey. Live commissioning remains separate.
