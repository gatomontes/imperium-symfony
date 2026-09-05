# Synthetic trusted-observation fixtures; never actual-account isolation evidence.
$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
. "$repo\tools\LocalIsolationReadiness.ps1"
$root=Join-Path ([IO.Path]::GetTempPath()) ('imperium-readiness-'+[guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($root)
function Save($v,$p){[IO.File]::WriteAllText($p,($v|ConvertTo-Json -Depth 100),[Text.UTF8Encoding]::new($false))}
function Clone($v){return $v|ConvertTo-Json -Depth 100|ConvertFrom-Json -AsHashtable -DateKind String}
foreach($name in @('ProtectedMissionCode','ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget','ProtectedMission','ProtectedMissionExchange','ProtectedMissionProbePlans','ProtectedMissionPostEnrollmentPlans')){
 [void][IO.Directory]::CreateDirectory("$root\$name");[IO.File]::WriteAllText("$root\$name\fixture",'inert')
}
[IO.File]::WriteAllText("$root\ProtectedMissionPHP\php.ini",'inert protected config')
$manifest=@{};foreach($name in @('ProtectedMissionCode','ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget')){
 foreach($f in Get-ChildItem "$root\$name" -File){$manifest[$name+'/'+$f.Name]=(Get-FileHash $f.FullName).Hash}
}
Save $manifest "$root\ProtectedMissionExchange\package-manifest.json"
$metadata=@{runtime_sid='S-1-5-21-1-2-3-1001';caller_sid='S-1-5-21-1-2-3-1002';setup_session=('a'*32);package_manifest_sha256=(Get-FileHash "$root\ProtectedMissionExchange\package-manifest.json").Hash}
Save $metadata "$root\ProtectedMission\installation.json";Save $metadata "$root\ProtectedMissionProbePlans\deployment-binding.json"
$binding=Get-PmaInstallationBinding $root
$now=[DateTime]::UtcNow.Ticks;$start=$now-[TimeSpan]::FromSeconds(30).Ticks
$public=[byte[]](1..32);$fingerprint=[Convert]::ToHexString([Security.Cryptography.SHA256]::HashData($public)).ToLowerInvariant()
$trust=@{identity='fixture-public-only';competence='APPROVE_CANONICAL_MISSION_PLAN';public_key=[Convert]::ToBase64String($public);not_before=[DateTimeOffset]::UtcNow.ToUnixTimeSeconds()-60;expires_at=[DateTimeOffset]::UtcNow.ToUnixTimeSeconds()+3600}
Save $trust "$root\ProtectedMissionExchange\public-trust.json"
$enrolled=$trust.Clone();$enrolled.fingerprint=$fingerprint;$enrolled.revoked=$false
$items=[Collections.Generic.List[object]]::new()
function Item($path,$class,$directory,$ticks){return @{path=$path;class=$class;directory=$directory;created_ticks=$ticks;attributes=0;owner='S-1-5-32-544';group='S-1-5-32-544';sddl='fixture-only-not-an-ACL'}}
foreach($name in @('ProtectedMissionCode','ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget','ProtectedMissionProbePlans','ProtectedMissionPostEnrollmentPlans')){
 $items.Add((Item "$root\$name" 'immutable' $true ($start-1)))
 $items.Add((Item "$root\$name\fixture" 'immutable' $false ($start-1)))
}
$items.Add((Item "$root\ProtectedMissionExchange" 'exchange-root' $true ($start-1)))
$items.Add((Item "$root\ProtectedMissionExchange\owner-readiness.json" 'reference' $false ($start-1)))
$items.Add((Item "$root\ProtectedMissionExchange\plan.json" 'runtime-output' $false ($start-1)))
$items.Add((Item "$root\ProtectedMission" 'state-root' $true ($start-1)))
$items.Add((Item "$root\ProtectedMission\installation.json" 'reference' $false ($start-1)))
$items.Add((Item "$root\ProtectedMission\state-canary" 'runtime-state' $false ($start-1)))
$items.Add((Item $root 'parent' $true ($start-1)))
$items.Add((Item "$root\ProtectedMission\authority.journal" 'runtime-state' $false ($start+1)))
$script:fixtureInventory=@{captured_ticks=$now;items=$items.ToArray()}
# Only observation collection is replaced: validation, policy, hashes and file-set
# comparison are production implementations exercised against disposable files.
function Get-PmaInventory([string]$Base){return $script:fixtureInventory}
$ready=@{schema=$PmaReadinessSchema;binding=$binding;captured_ticks=$now;public_fingerprint=$fingerprint;transport='Synthetic fixture only';custody='Synthetic fixture only';phases=@{}}
$backups=@{}
foreach($phase in @('pre','post','current')){
 $tick=$start+(@('pre','post','current').IndexOf($phase)*1000)
 $dir=Join-Path $root $(if($phase -eq 'pre'){'ProtectedMissionProbePlans'}else{'ProtectedMissionPostEnrollmentPlans'})
 $dir=Join-Path $dir ($phase+'-'+[guid]::NewGuid().ToString('N'));[void][IO.Directory]::CreateDirectory($dir)
 $inv=@{captured_ticks=$tick;items=@($items|Where-Object{$_.created_ticks -le $tick})}
 Save $inv "$dir\inventory.json"
 foreach($role in @('Runtime','Caller')){
  $plan=@{schema='imperium.local-isolation-plan/v2';binding=$binding;phase=$phase;role=$role;sid=$binding[($role.ToLower()+'_sid')];probes=(Get-PmaRequiredProbes $inv $role)}
  Save $plan "$dir\$role-plan.json"
  $measurement=@{schema='imperium.local-access-measurement/v2';role=$role;binding=$binding;phase=$phase;sid=$plan.sid;groups=@('S-1-5-32-545','S-1-5-11');administrator_token=$false;administrator_group_present=$false;plan_sha256=(Get-FileHash "$dir\$role-plan.json").Hash;captured_ticks=$tick+1;result='RECORDED_ACCESS_EXPECTATIONS_MET';probes=@($plan.probes|ForEach-Object{$r=Clone $_;$r.result=$r.expected;$r.win32_error=if($r.expected -eq 'ACCESS_SUCCEEDED'){0}else{5};$r.pass=$true;$r})}
  Save $measurement "$dir\$role-access.json"
  $checker=if($role -eq 'Runtime'){@{ExitCode=0;Output="PMA_INSTALLATION_ACL_AND_IDENTITY_VERIFIED`r`n";Error=''}}else{@{ExitCode=2;Output="PMA_RUNTIME_IDENTITY_REFUSED`r`n";Error=''}}
  $cli=if($role -eq 'Caller'){@{ExitCode=2;Output='';Error="PMA_RUNTIME_IDENTITY_REFUSED`n"}}elseif($phase -eq 'pre'){@{ExitCode=2;Output='';Error="PMA_TRUST_ABSENT`n"}}else{@{ExitCode=0;Output=($enrolled|ConvertTo-Json);Error=''}}
  $startup=@{binding=$binding;phase=$phase;role=$role;sid=$plan.sid;groups=$measurement.groups;administrator_token=$false;administrator_group_present=$false;captured_ticks=$tick+1;checker=$checker;cli=$cli}
  Save $startup "$dir\$role-startup.json"
 }
 $hashes=@{};foreach($file in Get-ChildItem $dir -File){$hashes[$file.Name]=(Get-FileHash $file.FullName).Hash;$backups[$file.FullName]=[IO.File]::ReadAllBytes($file.FullName)}
 Save $hashes "$dir\detached-manifest.json"
 $ready.phases[$phase]=@{directory=$dir;sha256=(Get-FileHash "$dir\detached-manifest.json").Hash}
 $backups["$dir\detached-manifest.json"]=[IO.File]::ReadAllBytes("$dir\detached-manifest.json")
}
$valid=Test-PmaReadiness $ready $root
if($valid.result -cne 'LOCAL_ISOLATION_READINESS_VALID'){throw ('VALID_SET_REFUSED: '+($valid|ConvertTo-Json))}
$cases=[Collections.Generic.List[string]]::new()
function Bad([string]$CaseName,[scriptblock]$Mutation,[bool]$Reseal=$true){
 $candidate=Clone $ready
 & $Mutation $candidate
 if($Reseal){
  foreach($ref in $candidate.phases.Values){
   $hashes=Read-PmaEvidence ($ref.directory+'\detached-manifest.json')
   foreach($name in @($hashes.Keys)){if(Test-Path ($ref.directory+'\'+$name)){$hashes[$name]=(Get-FileHash ($ref.directory+'\'+$name)).Hash}}
   Save $hashes ($ref.directory+'\detached-manifest.json');$ref.sha256=(Get-FileHash ($ref.directory+'\detached-manifest.json')).Hash
  }
 }
 $result=Test-PmaReadiness $candidate $root
 if($result.result -cne 'LOCAL_ISOLATION_READINESS_REFUSED'){throw ('BAD_SET_ACCEPTED: '+$CaseName)}
 $cases.Add($CaseName)
 foreach($path in $backups.Keys){[IO.File]::WriteAllBytes($path,$backups[$path])}
}
function Alter($c,$file,[scriptblock]$Change){$p=$c.phases.current.directory+'\'+$file;$v=Read-PmaEvidence $p;& $Change $v;Save $v $p}
Bad 'missing-row' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.probes=@($v.probes|Select-Object -Skip 1)}}
Bad 'duplicate-row' {param($c) Alter $c 'Caller-access.json' {param($v)$v.probes[1]=$v.probes[0]}}
Bad 'altered-mask' {param($c) Alter $c 'Caller-access.json' {param($v)$v.probes[0].mask=2}}
Bad 'altered-expectation' {param($c) Alter $c 'Caller-access.json' {param($v)$v.probes[0].expected='ACCESS_DENIED'}}
Bad 'unknown-result' {param($c) Alter $c 'Caller-access.json' {param($v)$v.probes[0].result='UNKNOWN_OS_ERROR'}}
Bad 'wrong-native-error' {param($c) Alter $c 'Caller-access.json' {param($v)$v.probes[0].win32_error=123}}
Bad 'wrong-sid' {param($c) Alter $c 'Caller-access.json' {param($v)$v.sid='S-1-5-21-1-2-3-9999'}}
Bad 'administrator-token' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.administrator_token=$true}}
Bad 'filtered-administrator-group' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.groups+='S-1-5-32-544'}}
Bad 'changed-token-groups' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.groups+='S-1-5-99'}}
Bad 'wrong-session' {param($c)$c.binding.setup_session='b'*32}
Bad 'wrong-package' {param($c)$c.binding.package_manifest_sha256='B'*64}
Bad 'wrong-fingerprint' {param($c)$c.public_fingerprint='b'*64}
Bad 'stale-ready' {param($c)$c.captured_ticks=1}
Bad 'stale-measurement' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.captured_ticks=1}}
Bad 'wrong-measurement-binding' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.binding.setup_session='b'*32}}
Bad 'wrong-plan-hash' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.plan_sha256='B'*64}}
Bad 'reduced-plan' {param($c) Alter $c 'Runtime-plan.json' {param($v)$v.probes=@($v.probes|Select-Object -Skip 1)}}
Bad 'missing-exchange-inventory' {param($c) Alter $c 'inventory.json' {param($v)$v.items=@($v.items|Where-Object{$_.class -ne 'exchange-root'})}}
Bad 'missing-probe-surface' {param($c) Alter $c 'inventory.json' {param($v)$v.items=@($v.items|Where-Object{$_.path -notlike '*ProtectedMissionProbePlans*'})}}
Bad 'missing-post-plan-surface' {param($c) Alter $c 'inventory.json' {param($v)$v.items=@($v.items|Where-Object{$_.path -notlike '*ProtectedMissionPostEnrollmentPlans*'})}}
Bad 'missing-startup' {param($c) Alter $c 'Caller-startup.json' {param($v)$v.Remove('cli')}}
Bad 'generic-checker-failure' {param($c) Alter $c 'Caller-startup.json' {param($v)$v.checker.Output="PMA_INSTALLATION_ACL_OR_IDENTITY_REFUSED`r`n"}}
Bad 'generic-cli-failure' {param($c) Alter $c 'Caller-startup.json' {param($v)$v.cli.Error="PMA_INPUT_OR_OPERATION_FAILED`n"}}
Bad 'wrong-startup-exit' {param($c) Alter $c 'Caller-startup.json' {param($v)$v.cli.ExitCode=1}}
Bad 'changed-bytes-unchanged-hash' {param($c) Alter $c 'Runtime-access.json' {param($v)$v.sid='tamper'}} $false
Bad 'missing-phase' {param($c)$c.phases.Remove('post')}
Bad 'extra-phase' {param($c)$c.phases.other=Clone $c.phases.post}
Bad 'missing-evidence-file' {param($c)[IO.File]::Move(($c.phases.current.directory+'\Caller-startup.json'),($c.phases.current.directory+'\retained-startup.json'))} $false
Bad 'changed-installed-bytes' {param($c)[IO.File]::WriteAllText("$root\ProtectedMissionCode\fixture",'changed')}
[IO.File]::WriteAllText("$root\ProtectedMissionCode\fixture",'inert')
$script:fixtureInventory.items+=Item "$root\ProtectedMissionExchange\challenge.json" 'runtime-output' $false ($now+1)
Bad 'later-output-requires-current-measurement' {param($c)}
$script:fixtureInventory.items=@($script:fixtureInventory.items|Where-Object{$_.path -notlike '*\challenge.json'})
# Real supported action dispatcher must refuse BEFORE marker or CLI invocation.
. "$repo\tools\LocalMission.ps1"
$exchange=Join-Path $root 'dispatch';[void][IO.Directory]::CreateDirectory($exchange)
Save $ready "$exchange\owner-readiness.json"
$context=@{Php=(Get-Command php).Source;Script="$repo\tests\Imperium\Runtime\Support\protected_mission_cli.php";Prefix=@("$root\never-authority");Exchange=$exchange}
foreach($action in @('Prepare','Accept','Step')){
 $before=@(Get-ChildItem $exchange -File).Count;$refused=$false
 try{$null=Invoke-PmaLocalAction $context $action}catch{$refused=$true}
 if(-not $refused -or @(Get-ChildItem $exchange -File).Count -ne $before -or (Test-Path "$root\never-authority")){throw 'READINESS_DISPATCH_MUTATED'}
 $cases.Add('no-authority-mutation-'+$action)
}
$status=Invoke-PmaLocalAction $context Status
if($status.result -cne 'NO_PERSISTED_ID_PRESERVE_ATTEMPT_MARKERS' -or @(Get-ChildItem $exchange -File).Count -ne 1){throw 'STATUS_NOT_READ_ONLY'}
$cases.Add('status-with-invalid-readiness')
$f=(& $context.Php "$PSScriptRoot\local_isolation_status_fixture.php")|ConvertFrom-Json -AsHashtable
if($LASTEXITCODE -ne 0){throw 'STATUS_FIXTURE_FAILED'}
$context.Prefix=@($f.root)
Save @{authorization_id=$f.authorization_id} "$exchange\authorization.json"
$journalHash=(Get-FileHash ($f.root+'/authority.journal')).Hash
$filesBefore=@(Get-ChildItem $exchange -File|ForEach-Object{@{path=$_.FullName;hash=(Get-FileHash $_.FullName).Hash}})
foreach($action in @('Prepare','Accept','Step')){try{$null=Invoke-PmaLocalAction $context $action;throw 'UNEXPECTED_MUTATION'}catch{if($_.Exception.Message -eq 'UNEXPECTED_MUTATION'){throw}}}
$status=Invoke-PmaLocalAction $context Status
Assert-PmaEqual $status.lifecycle.state 'AUTHORIZED' 'STATUS_WRONG_STATE'
Assert-PmaEqual (Get-FileHash ($f.root+'/authority.journal')).Hash $journalHash 'READINESS_OR_STATUS_CHANGED_JOURNAL'
Assert-PmaEqual @(Get-ChildItem $exchange -File|ForEach-Object{@{path=$_.FullName;hash=(Get-FileHash $_.FullName).Hash}}) $filesBefore 'STATUS_CHANGED_EXCHANGE'
$cases.Add('real-cli-status-and-refusals-preserve-journal-and-exchange')
@{result='LOCAL_ISOLATION_READINESS_FIXTURES_PASSED';negative_cases=$cases.Count;cases=$cases;valid_rows=$valid.rows;fixture=$root;actual_account_isolation=$false}|ConvertTo-Json -Depth 10
