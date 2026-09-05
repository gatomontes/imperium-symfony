param([string]$Repository = (Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path)
$ErrorActionPreference = 'Stop'
. (Join-Path $Repository 'tools/ProtectedMission.ps1')
$proofRoot = Join-Path ([IO.Path]::GetTempPath()) ('imperium-amendment-powershell-' + [guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($proofRoot)
$target = Join-Path $proofRoot 'target'
[void][IO.Directory]::CreateDirectory($target)
$hooks = Join-Path $proofRoot 'empty-hooks'
[void][IO.Directory]::CreateDirectory($hooks)
function Invoke-TestGit([string[]]$GitArguments) {
    $out = & git -C $target -c "core.hooksPath=$hooks" -c core.fsmonitor=false @GitArguments
    if ($LASTEXITCODE -ne 0) { throw 'TEST_GIT_FAILED' }
    return $out
}
$null = Invoke-TestGit @('init','--quiet')
[IO.File]::WriteAllText((Join-Path $target 'a.txt'), "A committed test bytes.`n")
[IO.File]::WriteAllText((Join-Path $target 'b.txt'), "B committed test bytes.`n")
$null = Invoke-TestGit @('add','a.txt','b.txt')
$null = Invoke-TestGit @('-c','user.name=Disposable','-c','user.email=test@example.invalid','-c','commit.gpgsign=false','commit','--quiet','-m','Disposable amendment target')
$commit = (Invoke-TestGit @('rev-parse','HEAD')).Trim()
$tree = (Invoke-TestGit @('rev-parse','HEAD^{tree}')).Trim()
function Get-TargetHashes {
    $rows = Get-ChildItem -LiteralPath $target -File -Recurse -Force | Sort-Object FullName | ForEach-Object { $_.FullName + ':' + (Get-FileHash -LiteralPath $_.FullName).Hash }
    return ($rows -join "`n")
}
$targetBefore = Get-TargetHashes
$testCli = Join-Path $PSScriptRoot 'protected_mission_cli.php'
function Invoke-TestCli([string[]]$CliArguments, [string]$InputJson = '') {
    $r = Invoke-PmaProcess -Script $testCli -Arguments (@($proofRoot) + $CliArguments) -InputText $InputJson
    if ($r.ExitCode -ne 0) { throw $r.Error }
    return $r.Output | ConvertFrom-Json -AsHashtable
}
function Invoke-TestRequest([string]$Operation, [hashtable]$Arguments) {
    return Invoke-TestCli @('request') ([ordered]@{operation=$Operation;arguments=$Arguments} | ConvertTo-Json -Depth 100 -Compress)
}
$keyCode = '$p=sodium_crypto_sign_keypair(); echo json_encode(["public"=>base64_encode(sodium_crypto_sign_publickey($p)),"secret"=>base64_encode(sodium_crypto_sign_secretkey($p)),"fingerprint"=>hash("sha256",sodium_crypto_sign_publickey($p))]);'
$testKeys = (& php -r $keyCode) | ConvertFrom-Json
if ($LASTEXITCODE -ne 0) { throw 'TEST_KEY_GENERATION_FAILED' }
$secureTestKey = ConvertTo-SecureString -String $testKeys.secret -AsPlainText -Force
$testKeys.secret = $null
try {
    $now = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
    $trust = @{identity='disposable-amendment-operator';competence='APPROVE_CANONICAL_MISSION_PLAN';public_key=$testKeys.public;not_before=$now-5;expires_at=$now+3600}
    $null = Invoke-TestCli @('enroll',$testKeys.fingerprint) ($trust | ConvertTo-Json -Compress)
    $inputCode = 'require $argv[1]."/vendor/autoload.php"; echo json_encode(App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture::input());'
    $plan = (& php -r $inputCode $Repository) | ConvertFrom-Json -AsHashtable
    if ($LASTEXITCODE -ne 0) { throw 'TEST_PLAN_FAILED' }
    $plan.mission.target = @{repository=$target;commit=$commit;tree=$tree}
    $plan.mission.paths = @('a.txt')
    function Prepare-TestApproval([string]$Label) {
        $prepared = Invoke-TestCli @('prepare') ($plan | ConvertTo-Json -Depth 100 -Compress)
        $cid = $prepared.challenge_id
        $export = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot,'export',$cid)
        $payloadPath = Join-Path $proofRoot ($Label + '-payload.json')
        Write-PmaCanonicalPayload -Result $export -Path $payloadPath
        $repeat = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot,'export',$cid)
        if ($repeat.ExitCode -ne 0 -or $repeat.Output -cne $export.Output) { throw 'UNSTABLE_EXPORT' }
        $render = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot,'render',$cid)
        if ($render.ExitCode -ne 0) { throw $render.Error }
        return @{id=$cid;path=$payloadPath;payload=($export.Output | ConvertFrom-Json -AsHashtable)}
    }
    function Sign-AndDerive($Challenge, [string]$Label) {
        $response = Join-Path $proofRoot ($Label + '-response.json')
        $null = Sign-PmaApproval -Payload $Challenge.path -Response $response -HeldKey $secureTestKey
        $null = Invoke-TestCli @('submit') (Get-Content -LiteralPath $response -Raw)
        return (Invoke-TestCli @('derive',$Challenge.id)).authorization_id
    }
    $ca = Prepare-TestApproval 'a'
    if ($null -ne $ca.payload.activation.expected_predecessor) { throw 'INITIAL_PREDECESSOR_NOT_ABSENT' }
    $a = Sign-AndDerive $ca 'a'
    $capsA = (Invoke-TestRequest 'issue' @{authorization_id=$a}).capabilities
    foreach ($cap in $capsA[0..1]) { $null = Invoke-TestRequest 'consume' @{capability=$cap} }
    $before = Invoke-TestCli @('status',$a)
    $plan.mission.paths = @('b.txt')
    $cb = Prepare-TestApproval 'b'
    $afterProposal = Invoke-TestCli @('status',$a)
    if (($before | ConvertTo-Json -Depth 100 -Compress) -cne ($afterProposal | ConvertTo-Json -Depth 100 -Compress)) { throw 'UNSIGNED_PROPOSAL_CHANGED_AUTHORITY' }
    if ($cb.payload.activation.expected_predecessor.authorization_id -cne $a) { throw 'WRONG_PREDECESSOR' }
    $b = Sign-AndDerive $cb 'b'
    $fresh = Invoke-TestCli @('status',$b)
    if ($fresh.lifecycle.state -cne 'AUTHORIZED' -or $fresh.lifecycle.history.Count -ne 0 -or $null -ne $fresh.receipt) { throw 'INHERITED_STATE' }
    $capsB = (Invoke-TestRequest 'issue' @{authorization_id=$b}).capabilities
    $journalBefore = (Get-FileHash -LiteralPath (Join-Path $proofRoot 'authority.journal')).Hash
    $r = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot,'request') -InputText ([ordered]@{operation='consume';arguments=@{capability=$capsB[2]}} | ConvertTo-Json -Depth 100 -Compress)
    if ($r.ExitCode -ne 2 -or $r.Error.Trim() -cne 'PMA_REQUIRED_STATE') { throw 'INHERITED_COMPLETION_NOT_REFUSED' }
    if ($journalBefore -cne (Get-FileHash -LiteralPath (Join-Path $proofRoot 'authority.journal')).Hash) { throw 'REFUSAL_MUTATED_JOURNAL' }
    foreach ($cap in $capsB) { $null = Invoke-TestRequest 'consume' @{capability=$cap} }
    $historical = Invoke-TestCli @('status',$a)
    $completed = Invoke-TestCli @('status',$b)
    if ($historical.lifecycle.state -cne 'INSPECTING' -or $historical.is_current -or $null -ne $historical.receipt) { throw 'HISTORICAL_STATUS_WRONG' }
    if ($completed.lifecycle.state -cne 'COMPLETED' -or $completed.receipt.snapshot.findings.Count -ne 1 -or $completed.receipt.snapshot.findings[0].path -cne 'b.txt') { throw 'SUCCESSOR_RECEIPT_WRONG' }
    $bytes = [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($completed.receipt.snapshot.findings[0].bytes_base64))
    if ($bytes -cne "B committed test bytes.`n") { throw 'COMMITTED_BYTES_WRONG' }
    if ($targetBefore -cne (Get-TargetHashes)) { throw 'TARGET_MUTATED' }
    $proof = [ordered]@{result='MISSION_AMENDMENT_POWERSHELL_PASSED';test_only=$true;same_user=$true;deployment_isolation_proved=$false;proof_root=$proofRoot;commit=$commit;tree=$tree;unsigned_proposal_preserved_authority=$true;inherited_completion_refused=$true;target_unchanged=$true;old=$historical;successor_initial=$fresh;successor_completed=$completed}
    $proof | ConvertTo-Json -Depth 100 | Set-Content -LiteralPath (Join-Path $proofRoot 'sanitized-amendment-proof.json') -Encoding utf8NoBOM
    $proof | ConvertTo-Json -Depth 100
} finally { $secureTestKey.Dispose() }
