# O4-B0 ready for one local implementation run

O3 is integrated at `61d3f555d4bc903437687d454f05f4484ff538c4`. [Closure](provider-onboarding-o3-complete.md), [campaign](../next-campaign-provider-onboarding-o4-assignment-application.md), [contract](../../contracts/provider-onboarding-assignment-application.md), [full local prompt](provider-onboarding-o4-b0-local-prompt.txt).

Implement atomic whole-set model settings for Courtthane and formation Locksmith, persistent resolution, explicit operator change/revalidation and crash/replay proof. All tests use temporary roots, synthetic authority and mock transport. No live activation or O5 CLI.

## PowerShell: fetch directly into an isolated worktree

Run from your existing repository checkout. This fetches the preparation without changing that checkout's files. If the destination or local branch already exists, use a fresh name; do not delete/reset earlier work.

```powershell
git fetch origin refs/heads/codex/provider-onboarding-o4-b0-preparation
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-onboarding-o4-b0 ../imperium-onboarding-o4-b0 FETCH_HEAD
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-onboarding-o4-b0
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-onboarding-o4-b0-local-prompt.txt -Raw
```

Start a new local Codex chat in that worktree and paste the full prompt printed by the final command. Implement and validate directly. Return the complete O4-B0 packet for review and fresh full CI before implementation integration. The next planned batch after accepted O4 is O5-B0, the offline CLI journey.
