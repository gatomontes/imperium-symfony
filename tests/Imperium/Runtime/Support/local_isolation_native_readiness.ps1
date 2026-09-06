# Native Windows handles and startup, in fresh relocated disposable fixtures only.
$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
. "$repo\tools\ProtectedMission.ps1"
$root=Join-Path ([IO.Path]::GetTempPath()) ('imperium-readiness-native-'+[guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($root)
$sid=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value
$file="$root\owner-reference.json";[IO.File]::WriteAllText($file,'inert public bytes')
$before=(Get-FileHash $file).Hash
$acl=Get-Acl $file
$acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.SecurityIdentifier]$sid,[Security.AccessControl.FileSystemRights]'WriteData,AppendData,Delete',[Security.AccessControl.AccessControlType]::Deny))
Set-Acl -LiteralPath $file -AclObject $acl
$acl=Get-Acl $root
$acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.SecurityIdentifier]$sid,[Security.AccessControl.FileSystemRights]::DeleteSubdirectoriesAndFiles,[Security.AccessControl.AccessControlType]::Deny))
Set-Acl -LiteralPath $root -AclObject $acl
$probes=@(@{path=$file;right='read-or-list';mask=1;expected='ACCESS_SUCCEEDED'})
foreach($right in @(@('write-data',2),@('append-data',4),@('delete',65536))){$probes+=@{path=$file;right=$right[0];mask=$right[1];expected='ACCESS_DENIED'}}
$probes+=@{path=$root;right='delete-child';mask=64;expected='ACCESS_DENIED'}
$plan=@{role='DisposableSameUser';sid=$sid;probes=$probes;phase='current';binding=@{fixture_only=$true}}
$plan|ConvertTo-Json -Depth 10|Set-Content "$root\plan.json"
& pwsh -NoProfile -File "$repo\tools\Test-LocalIsolationAccess.ps1" -Plan "$root\plan.json" -Output "$root\access.json" > "$root\access-command.txt"
if($LASTEXITCODE -ne 0 -or (Get-FileHash $file).Hash -cne $before){throw 'NATIVE_PROBE_FAILED_OR_MUTATED'}
$measurement=Get-Content "$root\access.json" -Raw|ConvertFrom-Json
if($measurement.schema -cne 'imperium.local-access-measurement/v2' -or $measurement.probes.Count -ne 5){throw 'V2_CAPTURE_FAILED'}
# Relocate copies of startup sources; do not install or touch fixed production paths.
$code="$root\ProtectedMissionCode"
foreach($dir in @("$code\src\ProtectedMission","$code\tools","$root\ProtectedMissionProbePlans")){[void][IO.Directory]::CreateDirectory($dir)}
$checker=[IO.File]::ReadAllText("$repo\tools\Assert-ProtectedMissionInstallation.ps1").Replace('C:\ProgramData\Imperium',$root)
[IO.File]::WriteAllText("$code\tools\Assert-ProtectedMissionInstallation.ps1",$checker)
$runtime=[IO.File]::ReadAllText("$repo\src\ProtectedMission\InstalledRuntime.php").Replace('C:/ProgramData/Imperium',$root.Replace('\','/'))
[IO.File]::WriteAllText("$code\src\ProtectedMission\InstalledRuntime.php",$runtime)
$bootstrap='<?php require '+"'"+$repo.Replace('\','/')+"/vendor/autoload.php'; require __DIR__.'/ProtectedMissionCode/src/ProtectedMission/InstalledRuntime.php'; exit(App\ProtectedMission\Cli::run(fn()=>App\ProtectedMission\InstalledRuntime::owner(),['trust']));"
[IO.File]::WriteAllText("$root\cli.php",$bootstrap)
$binding=@{runtime_sid='S-1-5-21-1-2-3-1001';caller_sid='S-1-5-21-1-2-3-1002';setup_session=('a'*32);package_manifest_sha256=('A'*64)}
$binding|ConvertTo-Json|Set-Content "$root\ProtectedMissionProbePlans\deployment-binding.json"
$native=Invoke-PmaProcess -Php 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe' -Script '-NoProfile' -Arguments @('-NonInteractive','-File',"$code\tools\Assert-ProtectedMissionInstallation.ps1",'-CodePath',$code)
if($native.ExitCode -ne 2 -or $native.Output -cne "PMA_RUNTIME_IDENTITY_REFUSED`r`n" -or $native.Error -cne ''){throw 'WRONG_IDENTITY_CHECKER_RESULT'}
$cli=Invoke-PmaProcess -Script "$root\cli.php" -Arguments @()
if($cli.ExitCode -ne 2 -or $cli.Error -cne "PMA_RUNTIME_IDENTITY_REFUSED`n" -or $cli.Output -cne ''){throw ('WRONG_IDENTITY_CLI_RESULT '+($cli|ConvertTo-Json))}
$binding.runtime_sid='malformed';$binding|ConvertTo-Json|Set-Content "$root\ProtectedMissionProbePlans\deployment-binding.json"
$generic=Invoke-PmaProcess -Script "$root\cli.php" -Arguments @()
if($generic.ExitCode -ne 2 -or $generic.Error -cne "PMA_INSTALLATION_CHECK_FAILED`n"){throw 'GENERIC_FAILURE_MISCLASSIFIED'}
@{result='LOCAL_ISOLATION_NATIVE_READINESS_PASSED';fixture=$root;handle_rows=5;startup_checks=3;windows_powershell='5.1';actual_account_isolation=$false;bytes_unchanged=$true}|ConvertTo-Json
