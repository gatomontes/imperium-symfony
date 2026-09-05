# Historical reproduction: execute copies of the selected baseline, never an install.
$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
$root=Join-Path ([IO.Path]::GetTempPath()) ('imperium-li-c0-'+[guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($root)
$source=(& git -C $repo show '574bfc69:tools/New-LocalIsolationProbePlans.ps1') -join "`n"
# Relocate every absolute baseline path into this disposable directory.
$source=$source.Replace('C:\ProgramData\Imperium',"$root\ProgramData\Imperium").Replace('C:\ProgramData',"$root\ProgramData").Replace('C:\Windows',"$root\Windows").Replace("'C:\'","'$root'")
$base="$root\ProgramData\Imperium"
foreach($name in @('ProtectedMissionCode','ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget','ProtectedMission','ProtectedMissionExchange','ProtectedMissionPostEnrollmentPlans')) {
 [void][IO.Directory]::CreateDirectory("$base\$name")
 [IO.File]::WriteAllText("$base\$name\public-fixture",'inert')
}
[void][IO.Directory]::CreateDirectory("$root\Windows\System32\WindowsPowerShell\v1.0")
$script=Join-Path $root 'baseline-plans.ps1';[IO.File]::WriteAllText($script,$source)
& $script -RuntimeSid 'S-1-5-21-1-2-3-1001' -CallerSid 'S-1-5-21-1-2-3-1002' -OutputDirectory "$base\ProtectedMissionProbePlans"
$plan=Get-Content "$base\ProtectedMissionProbePlans\Runtime-plan.json" -Raw|ConvertFrom-Json
foreach($surface in @('ProtectedMissionExchange','ProtectedMissionProbePlans','ProtectedMissionPostEnrollmentPlans')) {
 if(@($plan.probes|Where-Object{$_.path -like "*$surface*"}).Count){throw 'LI01_NOT_REPRODUCED'}
}
$launcher=(& git -C $repo show '574bfc69:tools/Invoke-LocalMission.ps1') -join "`n"
$gateLine=($launcher -split "`n"|Where-Object{$_.StartsWith('if($gate.status')})
$gate=@{status='OWNER_REVIEWED_ACTUAL_DEPLOYMENT_READY';runtime_measurement_sha256='not-a-hash';caller_measurement_sha256='no-file'}
& ([scriptblock]::Create($gateLine))
[ordered]@{result='LI01_AND_LI02_REPRODUCED';baseline='574bfc69aebf12b5197f4f808b54896bf02ba142';fixture=$root;omitted_surfaces=3;nonexistent_evidence_accepted=$true;production_actions=$false}|ConvertTo-Json
