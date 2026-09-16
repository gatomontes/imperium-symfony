param(
    [string]$ExpectedPreparation = '',
    [string]$WorktreePath = '',
    [string]$LocalBranch = 'codex/provider-staged-assignment-ppc10'
)
$ErrorActionPreference = 'Stop'
$ppc10Repo = git rev-parse --show-toplevel
if ($LASTEXITCODE -ne 0) { throw 'Run from an existing imperium-symfony checkout.' }
$ppc10Repo = ([string]$ppc10Repo).Trim()
git -C $ppc10Repo fetch origin main codex/provider-staged-assignment-ready
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed.' }
$ppc10Published = git -C $ppc10Repo rev-parse origin/codex/provider-staged-assignment-ready
if ($LASTEXITCODE -ne 0) { throw 'Published preparation is unavailable.' }
$ppc10Published = ([string]$ppc10Published).Trim()
if ($ExpectedPreparation -ne '' -and $ppc10Published -ne $ExpectedPreparation) { throw 'Published preparation differs from the expected commit; inspect before running.' }
git -C $ppc10Repo merge-base --is-ancestor 6cadc3125db6d9ccdbec9d40264033cd73a94de1 $ppc10Published
if ($LASTEXITCODE -ne 0) { throw 'Accepted PPC10 proposal integration is missing.' }
if ($WorktreePath -eq '') { $WorktreePath = Join-Path (Split-Path $ppc10Repo -Parent) 'imperium-ppc10' }
if (Test-Path -LiteralPath $WorktreePath) { throw 'Worktree path exists; choose a new unused -WorktreePath.' }
git -C $ppc10Repo show-ref --verify --quiet "refs/heads/$LocalBranch"
$ppc10BranchExit = $LASTEXITCODE
if ($ppc10BranchExit -eq 0) { throw 'Local branch exists; choose a new unused -LocalBranch.' }
if ($ppc10BranchExit -ne 1) { throw 'Could not check local branch.' }
git -C $ppc10Repo -c core.autocrlf=false worktree add -b $LocalBranch $WorktreePath $ppc10Published
if ($LASTEXITCODE -ne 0) { throw 'Worktree creation failed; preserve existing branches/checkouts.' }
Set-Location -LiteralPath $WorktreePath
$ppc10Start = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or ([string]$ppc10Start).Trim() -ne $ppc10Published) { throw 'Implementation start differs from published preparation.' }
$ppc10Tree = git rev-parse 'HEAD^{tree}'
if ($LASTEXITCODE -ne 0) { throw 'Cannot read start tree.' }
$ppc10Dirty = git status --porcelain
if ($LASTEXITCODE -ne 0 -or $ppc10Dirty) { throw 'New worktree is not clean.' }
$ppc10ProposalHash = (Get-FileHash -LiteralPath docs/provider-staged-assignment-proposal-v1.md -Algorithm SHA256).Hash.ToLowerInvariant()
if ($ppc10ProposalHash -ne 'bd817ea572c259874887375389ed3ecea0972ecf6ff0bb22b1cf72e75d06844d') { throw 'Approved proposal bytes differ; check checkout normalization.' }
$ppc10Scope = Get-Content -LiteralPath docs/provider-staged-assignment-implementation-source-record.json -Raw -Encoding utf8 | ConvertFrom-Json
$ppc10Approval = Get-Content -LiteralPath docs/handoffs/provider-staged-assignment-approval.json -Raw -Encoding utf8 | ConvertFrom-Json
$ppc10Decision = Get-Content -LiteralPath docs/handoffs/provider-staged-assignment-decision.json -Raw -Encoding utf8 | ConvertFrom-Json
if ($ppc10Scope.status -ne 'READY_UNDER_EXACT_C1_C2_APPROVAL' -or $ppc10Scope.proposal_sha256 -ne $ppc10ProposalHash) { throw 'Scope/hash mismatch.' }
if ($ppc10Approval.status -ne 'APPROVED_FOR_OFFLINE_IMPLEMENTATION' -or $ppc10Approval.owner_reply_exact -ne 'approved' -or $ppc10Approval.proposal_sha256 -ne $ppc10ProposalHash -or $ppc10Approval.approved_clauses.Count -ne 10) { throw 'Exact C1/C2 approval is missing.' }
if (-not $ppc10Decision.implementation_authorized -or $ppc10Decision.approval_reference -ne $ppc10Scope.approval_record) { throw 'Decision record is not implementation-ready.' }
foreach ($ppc10Doc in $ppc10Scope.implementation_protected_document_hashes) {
    $ppc10Hash = (Get-FileHash -LiteralPath $ppc10Doc.path -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($ppc10Hash -ne $ppc10Doc.sha256) { throw "Protected document differs: $($ppc10Doc.path)" }
}
$ppc10Inventory = Get-Content -LiteralPath $ppc10Scope.source_inventory -Raw -Encoding utf8 | ConvertFrom-Json
foreach ($ppc10Source in $ppc10Inventory.baseline_sources) {
    $ppc10Hash = (Get-FileHash -LiteralPath $ppc10Source.path -Algorithm SHA256).Hash.ToLowerInvariant()
    $ppc10Blob = git hash-object --no-filters -- $ppc10Source.path
    if ($LASTEXITCODE -ne 0 -or $ppc10Hash -ne $ppc10Source.sha256 -or ([string]$ppc10Blob).Trim() -ne $ppc10Source.git_blob -or (Get-Item -LiteralPath $ppc10Source.path).Length -ne $ppc10Source.bytes) { throw "Baseline source differs: $($ppc10Source.path)" }
}
$ppc10Count = Get-Content -LiteralPath docs/provider-first-interview-countdown.json -Raw -Encoding utf8 | ConvertFrom-Json
if ($ppc10Count.selected_campaign -ne 'PPC10' -or $ppc10Count.remaining_batches.minimum -ne 3 -or $ppc10Count.remaining_batches.maximum -ne 4 -or $ppc10Count.selected_campaign_status -ne 'READY_UNDER_EXACT_C1_C2_APPROVAL') { throw 'Countdown/campaign mismatch.' }
if ($ppc10Count.native_enrollment_disposition -ne 'DEFER_ENROLLMENT' -or $ppc10Count.actual_retry_allowlist.Count -ne 0) { throw 'Operational boundary mismatch.' }
foreach ($ppc10Flag in $ppc10Count.operational_flags.PSObject.Properties) {
    if ($ppc10Flag.Value -ne $false) { throw "Unexpected operational flag: $($ppc10Flag.Name)" }
}
Write-Output "PPC10 implementation start / bundle prerequisite: $ppc10Published"
Write-Output "PPC10 start tree: $ppc10Tree"
Write-Output 'Countdown: 3-4; approval earns no decrement.'
Get-Content -LiteralPath docs/handoffs/provider-staged-assignment-approval.json -Raw -Encoding utf8
Get-Content -LiteralPath docs/handoffs/provider-staged-assignment-local-prompt.txt -Raw -Encoding utf8
