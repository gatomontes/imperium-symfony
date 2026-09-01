[CmdletBinding()]
param(
    [string] $OutputDirectory = $env:TEMP
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $projectRoot

$dirtyBefore = @(git status --porcelain)
if ($LASTEXITCODE -ne 0 -or $dirtyBefore.Count -ne 0) {
    throw 'REFUSED_DIRTY_EXECUTION_SOURCE'
}

$sourceCommit = (git rev-parse HEAD).Trim()
$sourceTree = (git rev-parse 'HEAD^{tree}').Trim()
if ($LASTEXITCODE -ne 0) {
    throw 'REFUSED_SOURCE_IDENTITY_UNAVAILABLE'
}

$phpVersion = (& php -r 'echo PHP_VERSION;').Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($phpVersion)) {
    throw 'REFUSED_PHP_RUNTIME_UNAVAILABLE'
}

$missionId = 'ATOMIC-TRANSITION-DISPOSABLE-PROOF-1'
$privateJunit = Join-Path $OutputDirectory 'imperium-batch5-private-junit.xml'
$sanitizedSummary = Join-Path $OutputDirectory 'imperium-batch5-sanitized.json'
$runnerDigest = (Get-FileHash $PSCommandPath -Algorithm SHA256).Hash.ToLowerInvariant()
$dependencyLockDigest = (Get-FileHash (Join-Path $projectRoot 'composer.lock') -Algorithm SHA256).Hash.ToLowerInvariant()

$phpunitArguments = @(
    'vendor/bin/phpunit',
    '--testdox',
    '--log-junit', $privateJunit,
    'tests/Imperium/Runtime/AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch2Test.php',
    'tests/Imperium/Runtime/AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch3Test.php',
    'tests/Imperium/Runtime/ProviderBindingSuccessorAtomicLiveTransitionBatch4Test.php',
    'tests/Imperium/Runtime/AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch4Test.php'
)

& php @phpunitArguments
$missionExitCode = $LASTEXITCODE
if ($missionExitCode -ne 0) {
    throw "REFUSED_DISPOSABLE_MISSION_FAILED:$missionExitCode"
}

$dirtyAfter = @(git status --porcelain)
if ($LASTEXITCODE -ne 0 -or $dirtyAfter.Count -ne 0) {
    throw 'REFUSED_RUNTIME_MUTATED_WORKTREE'
}

[xml] $report = Get-Content -Raw $privateJunit
$cases = @($report.SelectNodes('//testcase'))
$failures = @($report.SelectNodes('//failure')).Count
$errors = @($report.SelectNodes('//error')).Count
$assertions = ($cases | ForEach-Object { [int] $_.assertions } | Measure-Object -Sum).Sum
if ($cases.Count -ne 20 -or $assertions -ne 211 -or $failures -ne 0 -or $errors -ne 0) {
    throw 'REFUSED_DISPOSABLE_MISSION_RESULT_SET_MISMATCH'
}

$evidence = [ordered] @{
    schema = 'imperium.sanitized-atomic-transition-disposable-mission-evidence/v1'
    mission_id = $missionId
    source_commit = $sourceCommit
    source_tree_digest = $sourceTree
    dependency_lock_digest = $dependencyLockDigest
    runner_digest = $runnerDigest
    php_version = $phpVersion
    worktree_clean_before_and_after = $true
    tests = $cases.Count
    assertions = $assertions
    failures = $failures
    errors = $errors
    test_cases = @($cases | ForEach-Object { $_.name })
    private_junit_digest = (Get-FileHash $privateJunit -Algorithm SHA256).Hash.ToLowerInvariant()
    private_junit_retention = 'OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT'
    provider_or_external_effect_authorized = $false
    live_credential_or_capability_authorized = $false
    disposition = 'PROVED'
}

$evidence | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $sanitizedSummary

Write-Host ''
Write-Host 'DISPOSABLE MISSION PROVED'
Write-Host "Sanitized evidence: $sanitizedSummary"
Write-Host "Private JUnit (retain locally only): $privateJunit"
Write-Host ''
Get-Content $sanitizedSummary
