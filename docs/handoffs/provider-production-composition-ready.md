# PPC0 local implementation handoff

Next selected work: [provider production composition](../next-campaign-provider-production-composition.md). O0–O5 are closed in their offline scope. This new run connects the dormant runtime to supported deployment custody/evidence sources; it does not perform commissioning. Read the [source assessment](../provider-production-composition-source-map.md) and [full local prompt](provider-production-composition-local-prompt.txt).

## PowerShell setup

Run from an existing repository checkout. Fetch and create an isolated worktree; these commands do not pull into or modify the installed application. If either branch or destination exists, use a new name without deleting/resetting prior work. The preparation branch pins the launch source even if main later advances.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-production-composition ../imperium-provider-production-composition origin/codex/provider-production-composition-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-production-composition
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-production-composition-local-prompt.txt -Raw
```

Start local Codex in that worktree and paste the full prompt. Implement and validate directly. Record actual environment/platform results; do not treat Windows development as proof of Ubuntu installation state. No real secrets or installed private files are needed.

Return the public review packet and report with exact supported/missing evidence sources. Implementation stops at local commits for source review and complete CI. O5's accepted results remain historical; PPC0 must supply fresh validation for changed executable code. Real commissioning and existing-installation cutover remain separate.
