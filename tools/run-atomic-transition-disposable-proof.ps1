[CmdletBinding()]
param([string] $OutputDirectory = $env:TEMP)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
function Get-StringSha256([string] $Value) {
    $algorithm = [Security.Cryptography.SHA256]::Create()
    try {
        return ([BitConverter]::ToString(
            $algorithm.ComputeHash([Text.Encoding]::UTF8.GetBytes($Value))
        ) -replace '-', '').ToLowerInvariant()
    } finally {
        $algorithm.Dispose()
    }
}
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $projectRoot
if (@(git status --porcelain).Count -ne 0) { throw 'REFUSED_DIRTY_EXECUTION_SOURCE' }

$sourceCommit = (git rev-parse HEAD).Trim()
$treeObject = (git rev-parse 'HEAD^{tree}').Trim()
$phpVersion = (& php -r 'echo PHP_VERSION;').Trim()
if ($LASTEXITCODE -ne 0) { throw 'REFUSED_PHP_RUNTIME_UNAVAILABLE' }

$privateEvidence = Join-Path $OutputDirectory 'imperium-batch5-private-integrated-receipt.json'
$sanitizedSummary = Join-Path $OutputDirectory 'imperium-batch5-sanitized-integrated.json'
$runnerDigest = (Get-FileHash $PSCommandPath -Algorithm SHA256).Hash.ToLowerInvariant()
$missionFile = Join-Path $projectRoot 'tools/run-atomic-transition-integrated-mission.php'
$missionDigest = (Get-FileHash $missionFile -Algorithm SHA256).Hash.ToLowerInvariant()
$lockDigest = (Get-FileHash (Join-Path $projectRoot 'composer.lock') -Algorithm SHA256).Hash.ToLowerInvariant()
$treeDigest = Get-StringSha256 $treeObject
$buildMaterial = "$sourceCommit`n$treeDigest`n$lockDigest`n$runnerDigest`n$missionDigest"
$buildDigest = Get-StringSha256 $buildMaterial

$bindings = @{
    IMPERIUM_PROOF_SOURCE_COMMIT = $sourceCommit
    IMPERIUM_PROOF_SOURCE_TREE_DIGEST = $treeDigest
    IMPERIUM_PROOF_BUILD_DIGEST = $buildDigest
    IMPERIUM_PROOF_LOCK_DIGEST = $lockDigest
    IMPERIUM_PROOF_RUNNER_DIGEST = $runnerDigest
    IMPERIUM_PROOF_MISSION_DIGEST = $missionDigest
    IMPERIUM_PROOF_PRIVATE_FILE = $privateEvidence
    IMPERIUM_PROOF_SANITIZED_FILE = $sanitizedSummary
}
foreach ($entry in $bindings.GetEnumerator()) { Set-Item "Env:$($entry.Key)" $entry.Value }
try {
    & php $missionFile
    if ($LASTEXITCODE -ne 0) { throw "REFUSED_INTEGRATED_MISSION_FAILED:$LASTEXITCODE" }
} finally {
    foreach ($name in $bindings.Keys) { Remove-Item "Env:$name" -ErrorAction SilentlyContinue }
}

if (@(git status --porcelain).Count -ne 0) { throw 'REFUSED_RUNTIME_MUTATED_WORKTREE' }
$evidence = Get-Content -Raw $sanitizedSummary | ConvertFrom-Json
if ($evidence.disposition -ne 'PROVED' -or -not $evidence.integrated_operational_receipt_created) {
    throw 'REFUSED_INTEGRATED_RECEIPT_INVALID'
}
Write-Host ''
Write-Host 'INTEGRATED DISPOSABLE MISSION PROVED'
Write-Host "Sanitized evidence: $sanitizedSummary"
Write-Host "Private receipt (retain locally only): $privateEvidence"
Get-Content $sanitizedSummary
