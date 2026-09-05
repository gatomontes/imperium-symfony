$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
. (Join-Path $repo 'tools/LocalMission.ps1')
$root=Join-Path ([IO.Path]::GetTempPath()) ('imperium-local-isolation-'+[guid]::NewGuid().ToString('N'))
$php=(Get-Command php).Source
& $php (Join-Path $PSScriptRoot 'local_isolation_fixture.php') $root
if($LASTEXITCODE -ne 0){throw 'FIXTURE_FAILED'}
$context=@{Php=$php;Script=(Join-Path $PSScriptRoot 'protected_mission_cli.php');Prefix=@((Join-Path $root 'authority'));Exchange=$root}
$keyCode='$p=sodium_crypto_sign_keypair();echo json_encode(["public"=>base64_encode(sodium_crypto_sign_publickey($p)),"secret"=>base64_encode(sodium_crypto_sign_secretkey($p)),"fingerprint"=>hash("sha256",sodium_crypto_sign_publickey($p))]);'
$keys=(& $php -r $keyCode)|ConvertFrom-Json
if($LASTEXITCODE -ne 0){throw 'TEST_KEYS_FAILED'}
$held=ConvertTo-SecureString $keys.secret -AsPlainText -Force;$keys.secret=$null
try{
 $now=[DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
 $trust=[ordered]@{identity='disposable-local-isolation';competence='APPROVE_CANONICAL_MISSION_PLAN';public_key=$keys.public;not_before=$now-5;expires_at=$now+3600}
 Write-PmaNewJson $trust (Join-Path $root 'trust.json')
 $null=Invoke-PmaChecked $context @('enroll',$keys.fingerprint) ($trust|ConvertTo-Json -Compress)
 $null=Start-PmaPrepared $context (Join-Path $root 'plan.json')
 $null=Sign-PmaApproval (Join-Path $root 'payload.json') (Join-Path $root 'response.json') $held
 $initial=Accept-PmaSigned $context (Join-Path $root 'response.json')
 if($initial.lifecycle.state -cne 'AUTHORIZED'){throw 'INITIAL_STATE_WRONG'}
 # Idempotent resume queries DERIVED; never repeats submit/derive.
 $resumed=Accept-PmaSigned $context (Join-Path $root 'response.json')
 if($resumed.binding.generation_id -cne $initial.binding.generation_id){throw 'RESUME_CHANGED_GENERATION'}
 $progress=@('AUTHORIZED')
 foreach($expected in @('ADMITTED','INSPECTING','COMPLETED')){
    $status=Step-PmaMission $context
    if($status.lifecycle.state -cne $expected){throw 'PROGRESS_WRONG'};$progress+=$expected
 }
 $again=Step-PmaMission $context
 if($again.lifecycle.history.Count -ne 3){throw 'TERMINAL_RESUME_REPLAYED'}
 Write-PmaNewJson $again (Join-Path $root 'completed.json')
 $manifest=& $php (Join-Path $repo 'tools/local-isolation.php') manifest (Join-Path $root 'target')
 if($LASTEXITCODE -ne 0){throw 'AFTER_MANIFEST_FAILED'}
 [IO.File]::WriteAllText((Join-Path $root 'after.json'),($manifest -join "`n"))
 $verify=& $php (Join-Path $repo 'tools/local-isolation.php') verify (Join-Path $root 'chain.json') (Join-Path $root 'completed.json') (Join-Path $root 'trust.json') (Join-Path $root 'inventory.json') ((Get-Content (Join-Path $root 'plan.json') -Raw|ConvertFrom-Json).mission.target.repository) (Join-Path $root 'before.json') (Join-Path $root 'after.json')
 if($LASTEXITCODE -ne 0){throw 'INDEPENDENT_VERIFICATION_FAILED'}
 $verified=($verify -join "`n")|ConvertFrom-Json -AsHashtable
 # Probe errors are not conflated with denied access. Positive write handle changes no bytes.
 $canary=Join-Path $root 'access-canary';[IO.File]::WriteAllText($canary,'unchanged')
 $sid=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value
 $probePlan=@{role='DisposableSameUser';sid=$sid;probes=@(@{path=$canary;right='write';mask=2;expected='ACCESS_SUCCEEDED'},@{path=(Join-Path $root 'absent');right='read';mask=1;expected='FILE_ABSENT'})}
 Write-PmaNewJson $probePlan (Join-Path $root 'probe-plan.json')
 & (Get-Command pwsh).Source -NoProfile -File (Join-Path $repo 'tools/Test-LocalIsolationAccess.ps1') -Plan (Join-Path $root 'probe-plan.json') -Output (Join-Path $root 'probe-result.json')
 if($LASTEXITCODE -ne 0){throw 'PROBE_REHEARSAL_FAILED'}
 if([IO.File]::ReadAllText($canary) -cne 'unchanged'){throw 'PROBE_CHANGED_BYTES'}
 $probePlan.probes[1].expected='ACCESS_DENIED'
 Write-PmaNewJson $probePlan (Join-Path $root 'bad-probe-plan.json')
 & (Get-Command pwsh).Source -NoProfile -File (Join-Path $repo 'tools/Test-LocalIsolationAccess.ps1') -Plan (Join-Path $root 'bad-probe-plan.json') -Output (Join-Path $root 'bad-probe-result.json') 2> (Join-Path $root 'expected-probe-refusal.txt')
 if($LASTEXITCODE -eq 0){throw 'ABSENT_COUNTED_AS_DENIAL'}
 $proof=[ordered]@{result='LOCAL_ISOLATION_DISPOSABLE_REHEARSAL_PASSED';proof_root=$root;same_user=$true;actual_deployment_isolation=$false;progress=$progress;verified=$verified;resume_derived_and_completed=$true;absence_is_not_denial=$true;target_unchanged=$true;analytical_report='No semantic analysis of real target performed in rehearsal.'}
 Write-PmaNewJson $proof (Join-Path $root 'public-rehearsal.json')
 $proof|ConvertTo-Json -Depth 20
}finally{$held.Dispose()}
