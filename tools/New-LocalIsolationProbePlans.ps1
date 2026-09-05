# Run as owner administrator, before enrollment. Produces exhaustive immutable-file
# and parent replacement handle probes plus Runtime-positive state canary access.
param([Parameter(Mandatory)][string]$RuntimeSid,[Parameter(Mandatory)][string]$CallerSid,
      [Parameter(Mandatory)][string]$OutputDirectory)
$ErrorActionPreference='Stop'
if(Test-Path $OutputDirectory){throw 'FRESH_PROBE_OUTPUT_REQUIRED'}
[void][IO.Directory]::CreateDirectory($OutputDirectory)
$base='C:\ProgramData\Imperium';$state="$base\ProtectedMission"
$immutable=@("$base\ProtectedMissionCode","$base\ProtectedMissionPHP","$base\ProtectedMissionShell","$base\ProtectedMissionTarget")
$items=@();foreach($root in $immutable){$items+=@(Get-Item $root -Force)+@(Get-ChildItem $root -Recurse -Force)}
if($items|Where-Object{$_.Attributes -band [IO.FileAttributes]::ReparsePoint}){throw 'REPARSE_REFUSED'}
$aclRows=@($items|ForEach-Object{[ordered]@{path=$_.FullName;attributes=$_.Attributes.ToString();sddl=(Get-Acl $_.FullName).Sddl}})
$aclRows|ConvertTo-Json -Depth 8|Set-Content (Join-Path $OutputDirectory 'owner-acls.json')
foreach($role in @('Runtime','Caller')){
 $rows=@()
 foreach($item in $items){
    foreach($right in @(@('write-data',2),@('append-data',4),@('delete',65536),@('write-dac',262144),@('write-owner',524288))){$rows+=[ordered]@{path=$item.FullName;right=$right[0];mask=$right[1];expected='ACCESS_DENIED'}}
    if($item.PSIsContainer){$rows+=[ordered]@{path=$item.FullName;right='delete-child';mask=64;expected='ACCESS_DENIED'}}
 }
 foreach($path in @('C:\','C:\ProgramData',$base)){
    foreach($right in @(@('delete-child',64),@('write-dac',262144),@('write-owner',524288))){$rows+=[ordered]@{path=$path;right=$right[0];mask=$right[1];expected='ACCESS_DENIED'}}
 }
 foreach($file in @('state-canary','metadata-canary','installation.json')){
    foreach($right in @(@('read-data',1),@('write-data',2),@('delete',65536),@('write-dac',262144),@('write-owner',524288))){
        $expected=if($role -eq 'Runtime' -and ($right[0] -eq 'read-data' -or ($file -eq 'state-canary' -and $right[0] -in @('write-data','delete')))){'ACCESS_SUCCEEDED'}else{'ACCESS_DENIED'}
        $rows+=[ordered]@{path="$state\$file";right=$right[0];mask=$right[1];expected=$expected}
    }
 }
 foreach($right in @(@('list',1),@('add-file',2),@('delete-child',64))){
    $rows+=[ordered]@{path=$state;right=$right[0];mask=$right[1];expected=if($role -eq 'Runtime' -and $right[0] -ne 'delete-child'){'ACCESS_SUCCEEDED'}else{'ACCESS_DENIED'}}
 }
 [ordered]@{role=$role;sid=if($role -eq 'Runtime'){$RuntimeSid}else{$CallerSid};probes=$rows}|ConvertTo-Json -Depth 10|Set-Content (Join-Path $OutputDirectory "$role-plan.json")
}
'PROBE_PLANS_PREPARED_NOT_MEASURED'
