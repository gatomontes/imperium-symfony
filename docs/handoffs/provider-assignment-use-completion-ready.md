# PPC9 local handoff

Continue approved PPC2 A–F after accepted PPC8 R4. [Campaign](../next-campaign-provider-assignment-use-completion.md), [implementation/proof plan](../provider-assignment-use-completion-plan.md), [scope/source hashes](../provider-assignment-use-completion-source-record.json). **Countdown 3–4; preparation earns no decrement.** Full R3 acceptance could leave 2–3.

Use a new isolated worktree. The commands preserve existing checkouts and installed applications; if the path or local branch exists, choose a new unused name. They resolve the published preparation commit before creating the worktree, verify accepted PPC8 ancestry and the original proposal hash, and print the complete local prompt. [PowerShell script](provider-assignment-use-completion-pull.ps1).

```powershell
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
```

Give local Codex the entire printed [prompt](provider-assignment-use-completion-local-prompt.txt). Record the actual start commit/tree and retain that exact published commit as the bounded bundle prerequisite. Main baseline in the inventory is the pre-preparation accepted tree, not the implementation start.

Return the committed report, `imperium-ppc9-public-review.zip` and its separate `.zip.sha256`. Local work ends at public review deliverables; receiving review and fresh hosted validation follow. No repeat approval for existing scope. No live work or subagents. All five flags false, `DEFER_ENROLLMENT`, retry allowlist `[]`.

*Imperium via solitaria est.*
