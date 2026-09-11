# O3-B0 local implementation handoff

O2 is integrated through [PR #790](https://github.com/gatomontes/imperium-symfony/pull/790). The accepted correction tree passed 3,536 tests / 56,244 assertions / four skips. The [integration record](../provider-onboarding/o2-b1-reviewed-integration.json) distinguishes that CI from preparation work.

Run [the local prompt](provider-onboarding-o3-b0-local-prompt.txt) against the [campaign](../next-campaign-provider-onboarding-o3-deepseek-adapter.md) and [contract](../../contracts/provider-onboarding-deepseek-adapter.md). This is one bounded implementation run; implement and validate before the next review.

From your existing repository, use PowerShell to create a separate worktree. The named preparation branch is retained as the exact launch reference even after its PR is merged. These commands leave the current checkout alone and stop if either the new branch or path already exists.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-onboarding-o3-b0 ../imperium-onboarding-o3-b0 origin/codex/provider-onboarding-o3-b0-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed; preserve existing work" }
Set-Location ../imperium-onboarding-o3-b0
git status --short
git log -1 --oneline
Get-Content -Raw docs/handoffs/provider-onboarding-o3-b0-local-prompt.txt
```

Open a new local Codex chat in that directory and use the displayed prompt. No main checkout reset or force operation is needed.

The executor needs the local PHP >=8.4/Composer installation for implementation tests. This preparation was made through repository tools while the execution environment was unavailable; no local PHP, static checker or source-review execution is claimed for the preparation itself. Its documentation-only changes and links are checked separately. Prior O2 results remain attributed to their exact source.

Return one complete O3-B0 public delivery packet for source/full-CI review. The expected result is dormant working adapter code with offline evidence. Missing live provider evidence is a refusal condition, not a reason to fabricate authority or reopen settled policy choices.
