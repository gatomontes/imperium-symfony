param([Parameter(Mandatory)][string]$Proof,[Parameter(Mandatory)][ValidateSet('RuntimeCanary','CallerCanary','Ceremony')][string]$Action,
 [Parameter(Mandatory)][string]$Output,[string]$Private)
$ErrorActionPreference='Stop'
$p=Get-Content $Proof -Raw|ConvertFrom-Json -AsHashtable
if($p.workspace -cnotmatch '^C:\\ProgramData\\PmaScratchProof-[a-f0-9]{32}$' -or $p.base -cne ($p.workspace+'\installation')){throw 'DISPOSABLE_PROOF_REQUIRED'}
$base=$p.base;$code="$base/ProtectedMissionCode"
. "$code/tools/LocalIsolationReadiness.ps1"
if(Test-Path $Output){throw 'PRESERVE_EXISTING_OUTPUT'}
$role=if($Action -eq 'CallerCanary'){'Caller'}else{'Runtime'}
$token=Get-PmaToken;Assert-PmaToken $token $p[($role.ToLower()+'_sid')]
$native=(& whoami.exe /all) -join "`n"
if($native -match 'S-1-5-32-544'){throw 'ADMINISTRATOR_MEMBERSHIP_INCLUDING_DENY_ONLY_REFUSED'}
$env:PHPRC="$base/ProtectedMissionPHP/php.ini";$env:PHP_INI_SCAN_DIR="$base/ProtectedMissionPHP/empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
if($Action -eq 'RuntimeCanary'){
 [IO.File]::WriteAllText(($p.old_state+'/file'),'native old policy file')
 $denied=$false;try{[void][IO.Directory]::CreateDirectory(($p.old_state+'/denied'))}catch [UnauthorizedAccessException]{$denied=$true}
 if(-not $denied){throw 'IS03_OLD_POLICY_NOT_REPRODUCED'}
 [void][IO.Directory]::CreateDirectory(($p.scratch_canary+'/work/nested'))
 [IO.File]::WriteAllText(($p.scratch_canary+'/work/nested/file'),'native scratch canary')
 [IO.File]::Move(($p.scratch_canary+'/work/nested/file'),($p.scratch_canary+'/work/nested/renamed'))
 if([IO.File]::ReadAllText(($p.scratch_canary+'/work/nested/renamed')) -cne 'native scratch canary'){throw 'NATIVE_READ_FAILED'}
 [IO.File]::WriteAllText(($p.scratch_canary+'/work/nested/remove'),'remove only this')
 [IO.File]::Delete(($p.scratch_canary+'/work/nested/remove'))
 $result=@{result='RUNTIME_NATIVE_WORK_AND_OLD_DENIAL_PASSED';token=$token;native_token=$native;sddl=(Get-Acl $p.scratch_canary).Sddl;proof_sha256=(Get-FileHash $Proof).Hash}
}elseif($Action -eq 'CallerCanary'){
 $rows=@()
 foreach($entry in @(@($p.scratch_canary,$true),@(($p.scratch_canary+'/work'),$true),@(($p.scratch_canary+'/work/nested/renamed'),$false))){
  foreach($mask in @(1,2,4,65536,262144,524288)+$(if($entry[1]){@(64)}else{@()})){$rows+=@{path=$entry[0];right=('native-'+$mask);mask=$mask;expected='ACCESS_DENIED'}}
 }
 $plan=@{role='Caller';sid=$p.caller_sid;probes=$rows}
 $planPath=$Output+'.plan.json';if(Test-Path $planPath){throw 'PRESERVE_PLAN'}
 [IO.File]::WriteAllText($planPath,($plan|ConvertTo-Json -Depth 10))
 & "$base/ProtectedMissionShell/pwsh.exe" -NoProfile -File "$code/tools/Test-LocalIsolationAccess.ps1" -Plan $planPath -Output $Output
 if($LASTEXITCODE -ne 0){throw 'CALLER_CANARY_FAILED'}
 [IO.File]::WriteAllText(($Output+'.token.txt'),$native)
 return
}else{
 $ready=Read-PmaEvidence "$base/ProtectedMissionExchange/owner-readiness.json"
 $validation=Test-PmaReadiness $ready $base
 if($validation.result -cne 'LOCAL_ISOLATION_READINESS_VALID'){throw ('READINESS_REFUSED '+$validation.reason)}
 $out=& "$base/ProtectedMissionPHP/php.exe" "$code/tools/Test-ProtectedScratchCeremony.php" ceremony $base $Private
 if($LASTEXITCODE -ne 0){throw 'REAL_CEREMONY_REFUSED_PRESERVE_JOURNAL_AND_SCRATCH'}
 $result=@{result='NATIVE_CEREMONY_AND_READINESS_PASSED';ceremony=($out|ConvertFrom-Json -AsHashtable);token=$token;native_token=$native;readiness=$validation;proof_sha256=(Get-FileHash $Proof).Hash;actual_deployment_isolation=$false;disposable_relocation=$true}
}
[IO.File]::WriteAllText($Output,($result|ConvertTo-Json -Depth 30),[Text.UTF8Encoding]::new($false))
$result|ConvertTo-Json -Depth 30
