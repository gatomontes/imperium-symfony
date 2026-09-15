$ErrorActionPreference = 'Stop'
# Run from any existing imperium-symfony checkout.
git fetch origin main codex/provider-assignment-use-completion-preparation
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
$ppc9Published = git rev-parse origin/codex/provider-assignment-use-completion-preparation
if ($LASTEXITCODE -ne 0) { throw "Published preparation is unavailable" }
$ppc9Parent = Split-Path (Get-Location).Path -Parent
$ppc9Worktree = Join-Path $ppc9Parent 'imperium-assignment-use-completion'
if (Test-Path $ppc9Worktree) { throw "Worktree path exists; choose an unused path" }
git -c core.autocrlf=false worktree add -b codex/provider-assignment-use-completion $ppc9Worktree $ppc9Published
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed; preserve existing branches and choose a new name" }
Set-Location $ppc9Worktree
$ppc9Start = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or $ppc9Start -ne $ppc9Published) { throw "Start differs from published handoff" }
git merge-base --is-ancestor 23d21a3bbbf4bfa33b8097b7bbacf0f78bd1b92c HEAD
if ($LASTEXITCODE -ne 0) { throw "PPC8 reviewed integration is missing" }
$ppc9Hash = (Get-FileHash docs/provider-profile-designation-amendment.md -Algorithm SHA256).Hash.ToLowerInvariant()
if ($ppc9Hash -ne 'c24939fd808cd5917c8057efe518787cc2448d3da60be089511e7abd6dee2984') { throw "Original proposal bytes differ; check Git bytes/newline settings" }
$ppc9Scope = Get-Content docs/provider-assignment-use-completion-source-record.json -Raw | ConvertFrom-Json
if ($ppc9Scope.status -ne 'READY_UNDER_EXISTING_PPC2_APPROVAL' -or $ppc9Scope.proposal_sha256 -ne $ppc9Hash) { throw "Scope/hash mismatch" }
$ppc9Count = Get-Content docs/provider-first-interview-countdown.json -Raw | ConvertFrom-Json
if ($ppc9Count.selected_campaign -ne 'PPC9' -or $ppc9Count.remaining_batches.minimum -ne 3 -or $ppc9Count.remaining_batches.maximum -ne 4) { throw "Countdown mismatch" }
Get-Content docs/handoffs/provider-profile-designation-approval.md -Raw
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-assignment-use-completion-local-prompt.txt -Raw
