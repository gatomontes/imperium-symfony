param(
    [string]$ExpectedPreparation = '',
    [string]$WorktreePath = '',
    [string]$LocalBranch = 'codex/ppc10-v5-extension-local'
)
$ErrorActionPreference = 'Stop'
$v5Repo = git rev-parse --show-toplevel
if ($LASTEXITCODE -ne 0) { throw 'Run from an existing imperium-symfony checkout.' }
$v5Repo = ([string]$v5Repo).Trim()
git -C $v5Repo fetch origin '+refs/heads/codex/ppc10-shared-exposure-approved:refs/remotes/origin/codex/ppc10-shared-exposure-approved' '+refs/heads/codex/ppc10-components-pending:refs/remotes/origin/codex/ppc10-components-pending'
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed.' }
$v5Published = git -C $v5Repo rev-parse origin/codex/ppc10-shared-exposure-approved
if ($LASTEXITCODE -ne 0) { throw 'Documentation preparation unavailable.' }
$v5Published = ([string]$v5Published).Trim()
if ($ExpectedPreparation -ne '' -and $ExpectedPreparation -ne $v5Published) { throw 'Preparation moved; inspect its new identity.' }
$v5Base = 'b6fffc5cef31f54d47993ceeec19d5124513f754'
$v5Tree = git -C $v5Repo rev-parse ($v5Base + '^{tree}')
if ($LASTEXITCODE -ne 0 -or ([string]$v5Tree).Trim() -ne '3b0bd4b352066e13c6aeb806971917bd4cee81f7') { throw 'Reviewed component source tree unavailable or different.' }
git -C $v5Repo merge-base --is-ancestor $v5Base origin/codex/ppc10-components-pending
if ($LASTEXITCODE -ne 0) { throw 'Published component ancestry differs.' }
$v5DocBase = '7049bf1950676257b49728e211e8960ef43c2d73'
git -C $v5Repo merge-base --is-ancestor $v5DocBase $v5Published
if ($LASTEXITCODE -ne 0) { throw 'Documentation ancestry differs.' }
$v5Commits = @(git -C $v5Repo rev-list --reverse ($v5DocBase + '..' + $v5Published))
if ($LASTEXITCODE -ne 0 -or $v5Commits.Count -eq 0) { throw 'Documentation commit sequence unavailable.' }
foreach ($v5Commit in $v5Commits) {
    $v5Parents = git -C $v5Repo rev-list --parents -n 1 $v5Commit
    if ($LASTEXITCODE -ne 0 -or (([string]$v5Parents).Trim() -split ' ').Count -ne 2) { throw 'Expected a linear documentation sequence.' }
    $v5Paths = @(git -C $v5Repo diff-tree --no-commit-id --name-only -r $v5Commit)
    if ($LASTEXITCODE -ne 0 -or $v5Paths.Count -eq 0) { throw 'Cannot inspect documentation commit.' }
    foreach ($v5Path in $v5Paths) {
        if (-not $v5Path.StartsWith('docs/')) { throw "Non-documentation path in handoff: $v5Path" }
    }
}
if ($WorktreePath -eq '') { $WorktreePath = Join-Path (Split-Path $v5Repo -Parent) 'imperium-ppc10-v5' }
if (Test-Path -LiteralPath $WorktreePath) { throw 'Destination exists; choose an unused -WorktreePath.' }
git -C $v5Repo show-ref --verify --quiet "refs/heads/$LocalBranch"
$v5BranchExit = $LASTEXITCODE
if ($v5BranchExit -eq 0) { throw 'Branch exists; choose an unused -LocalBranch.' }
if ($v5BranchExit -ne 1) { throw 'Cannot check branch availability.' }
git -C $v5Repo -c core.autocrlf=false worktree add -b $LocalBranch $WorktreePath $v5Base
if ($LASTEXITCODE -ne 0) { throw 'Worktree creation failed.' }
Set-Location -LiteralPath $WorktreePath
foreach ($v5Commit in $v5Commits) {
    git -c core.autocrlf=false cherry-pick $v5Commit
    if ($LASTEXITCODE -ne 0) { throw 'Documentation replay stopped; preserve this worktree for inspection. No automatic reset.' }
}
$v5Approval = Get-Content docs/handoffs/provider-v5-reader-extension-approval.json -Raw -Encoding utf8 | ConvertFrom-Json
$v5Shared = Get-Content docs/handoffs/provider-shared-exposure-extension-approval.json -Raw -Encoding utf8 | ConvertFrom-Json
if ($v5Approval.status -ne 'APPROVED_FOR_BOUNDED_OFFLINE_IMPLEMENTATION' -or $v5Approval.owner_reply_exact -ne 'approved' -or $v5Shared.owner_reply_exact -ne 'approved') { throw 'Approval records differ.' }
$v5ProposalHash = (Get-FileHash $v5Approval.proposal_path -Algorithm SHA256).Hash.ToLowerInvariant()
if ($v5ProposalHash -ne $v5Approval.proposal_sha256) { throw 'Reader proposal hash differs.' }
$v5OriginalHash = (Get-FileHash docs/provider-staged-assignment-proposal-v1.md -Algorithm SHA256).Hash.ToLowerInvariant()
if ($v5OriginalHash -ne $v5Shared.existing_proposal_sha256) { throw 'Original C1/C2 proposal differs.' }
foreach ($v5Source in (@($v5Shared.additional_protected_source) + @($v5Approval.approved_sources))) {
    $v5Hash = (Get-FileHash $v5Source.path -Algorithm SHA256).Hash.ToLowerInvariant()
    $v5Blob = git hash-object --no-filters -- $v5Source.path
    if ($LASTEXITCODE -ne 0 -or $v5Hash -ne $v5Source.sha256 -or ([string]$v5Blob).Trim() -ne $v5Source.git_blob -or (Get-Item $v5Source.path).Length -ne $v5Source.bytes) { throw "Approved source differs: $($v5Source.path)" }
}
$v5Dirty = git status --porcelain
if ($LASTEXITCODE -ne 0 -or $v5Dirty) { throw 'Continuation checkout is not clean.' }
$v5Start = git rev-parse HEAD
if ($LASTEXITCODE -ne 0) { throw 'Cannot read continuation commit.' }
$v5StartTree = git rev-parse 'HEAD^{tree}'
if ($LASTEXITCODE -ne 0) { throw 'Cannot read continuation tree.' }
Write-Output "Published component / bounded-bundle prerequisite: $v5Base"
Write-Output "Documentation preparation: $v5Published"
Write-Output "Local continuation start: $v5Start"
Write-Output "Local continuation tree: $v5StartTree"
Write-Output 'Countdown 4-5; decrement zero. Components remain unaccepted.'
Get-Content docs/handoffs/provider-shared-exposure-continuation-prompt.txt -Raw -Encoding utf8
