# PPC1 local implementation handoff

Implement [native institutional evidence producers](../next-campaign-provider-institutional-evidence.md): Augur constitutional provenance and current Profile/model-binding facts, behind the two existing ports. Read the [full prompt](provider-institutional-evidence-local-prompt.txt). PPC0 composition/custody is integrated; the other three provider-dependent evidence ports remain unresolved.

Run from an existing repository checkout in PowerShell. Fetch and create a fresh worktree without updating the installed application. If a branch/path exists, choose a new name; do not delete or reset earlier work.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-institutional-evidence ../imperium-provider-institutional-evidence origin/codex/provider-institutional-evidence-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-institutional-evidence
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-institutional-evidence-local-prompt.txt -Raw
```

Start local Codex in that worktree and paste the prompt. Implement directly, using disposable roots and synthetic authority with the real producer code. No real provider/account access or installed private files are needed. Return the complete public review packet. Stop at local commits for review and complete CI before implementation publication/integration.
