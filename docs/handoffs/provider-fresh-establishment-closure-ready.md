# PPC8 local handoff — R4 closure continuation

Ready under [existing PPC7 approval](provider-fresh-establishment-approval.json). [Campaign](../next-campaign-provider-fresh-establishment-closure.md), [implementation/proof plan](../provider-fresh-establishment-closure-plan.md), [complete prompt](provider-fresh-establishment-closure-local-prompt.txt). Preparation adds no implementation acceptance. Countdown **3–5**, decrement **0**.

Run from an existing repository checkout in PowerShell. These branch/path names must be unused; preserve earlier checkouts and installed applications.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git -c core.autocrlf=false worktree add -b codex/provider-fresh-establishment-closure ../imperium-provider-fresh-establishment-closure origin/codex/provider-fresh-establishment-closure-preparation
if ($LASTEXITCODE -ne 0) { throw "PPC8 worktree creation failed" }
Set-Location ../imperium-provider-fresh-establishment-closure
$ppc8Start = git rev-parse HEAD
if ($LASTEXITCODE -ne 0) { throw "Cannot resolve start" }
$ppc8Published = git rev-parse origin/codex/provider-fresh-establishment-closure-preparation
if ($LASTEXITCODE -ne 0 -or $ppc8Start -ne $ppc8Published) { throw "Start differs from published PPC8 handoff" }
git merge-base --is-ancestor 2fffa8f51197dce0464b4b6ca5f0d613f1a4dc7b HEAD
if ($LASTEXITCODE -ne 0) { throw "PPC7 reviewed integration is missing" }
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
$ppc8Approval = Get-Content docs/handoffs/provider-fresh-establishment-approval.json -Raw | ConvertFrom-Json
if ($ppc8Approval.status -ne 'OWNER_APPROVED_FOR_BOUNDED_OFFLINE_IMPLEMENTATION') { throw "Existing approval is unavailable" }
$ppc8Hash = (Get-FileHash docs/provider-fresh-establishment-proposal-v1.md -Algorithm SHA256).Hash.ToLowerInvariant()
if ($ppc8Hash -ne 'e32bfd849a96f380505c0486702124c4bb60a9d5b26a15e7a39e7ef30eed3d4e' -or $ppc8Hash -ne $ppc8Approval.proposal_sha256) { throw "Proposal bytes differ; check Git bytes and newline settings" }
Get-Content docs/handoffs/provider-fresh-establishment-closure-local-prompt.txt -Raw
```

Give local Codex the entire printed prompt. Record the actual start SHA/tree in the report and use that exact commit as the bounded bundle prerequisite. The preparation branch is the published starting point, distinct from the pre-preparation main baseline recorded in the source inventory.

Return the committed report, `imperium-ppc8-public-review.zip` and its separate `.zip.sha256`. Local work ends at the packet; receiving review and fresh hosted validation come next. No duplicate approval, live work or subagents. All five flags remain false, `DEFER_ENROLLMENT`, retry allowlist `[]`.

*Imperium via solitaria est.*
