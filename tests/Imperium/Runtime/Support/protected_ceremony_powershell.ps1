param([string]$Repository = (Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path)
$ErrorActionPreference = 'Stop'
. (Join-Path $Repository 'tools/ProtectedMission.ps1')
$proofRoot = Join-Path ([IO.Path]::GetTempPath()) ('imperium-protected-powershell-' + [guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($proofRoot)
$testCli = Join-Path $PSScriptRoot 'protected_mission_cli.php'
$keyCode = '$p=sodium_crypto_sign_keypair(); echo json_encode(["public"=>base64_encode(sodium_crypto_sign_publickey($p)),"secret"=>base64_encode(sodium_crypto_sign_secretkey($p)),"fingerprint"=>hash("sha256",sodium_crypto_sign_publickey($p))]);'
$testKeys = (& php -r $keyCode) | ConvertFrom-Json
$now = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$publicTrust = @{identity='disposable-powershell-operator'; competence='APPROVE_CANONICAL_MISSION_PLAN'; public_key=$testKeys.public; not_before=$now-5; expires_at=$now+3600} | ConvertTo-Json -Compress
$result = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'enroll', $testKeys.fingerprint) -InputText $publicTrust
if ($result.ExitCode -ne 0) { throw $result.Error }
$inputCode = 'require $argv[1]."/vendor/autoload.php"; echo json_encode(App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture::input());'
$planInput = & php -r $inputCode $Repository
$result = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'prepare') -InputText $planInput
if ($result.ExitCode -ne 0) { throw $result.Error }
$challenge = ($result.Output | ConvertFrom-Json).challenge_id
$result = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'export', $challenge)
$payloadPath = Join-Path $proofRoot 'payload.json'
Write-PmaCanonicalPayload -Result $result -Path $payloadPath
$reexport = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'export', $challenge)
if ($result.Output -cne $reexport.Output) { throw 'PMA_EXPORT_UNSTABLE' }
$responsePath = Join-Path $proofRoot 'response.json'
$secureTestKey = ConvertTo-SecureString -String $testKeys.secret -AsPlainText -Force
$testKeys.secret = $null
try { $null = Sign-PmaApproval -Payload $payloadPath -Response $responsePath -HeldKey $secureTestKey }
finally { $secureTestKey.Dispose() }
$result = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'submit') -InputText (Get-Content -LiteralPath $responsePath -Raw)
if ($result.ExitCode -ne 0) { throw $result.Error }
$result = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'derive', $challenge)
if ($result.ExitCode -ne 0) { throw $result.Error }
$authorization = ($result.Output | ConvertFrom-Json).authorization_id
$result = Invoke-PmaProcess -Script $testCli -Arguments @($proofRoot, 'verify', $authorization)
if ($result.ExitCode -ne 0) { throw $result.Error }
$receipt = [ordered]@{ test_only=$true; same_user=$true; deployment_isolation_measured=$false; powershell=$PSVersionTable.PSVersion.ToString(); challenge_id=$challenge; authorization_id=$authorization; payload_sha256=(Get-FileHash -LiteralPath $payloadPath -Algorithm SHA256).Hash; proof_root=$proofRoot; result='POWERSHELL_CEREMONY_PASSED'; real_mission_executed=$false }
$receipt | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $proofRoot 'sanitized-powershell-transcript.json') -Encoding utf8NoBOM
$receipt | ConvertTo-Json
