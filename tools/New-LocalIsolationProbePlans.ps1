# Run as owner administrator, before enrollment. Produces exhaustive immutable-file
# and parent replacement handle probes plus Runtime-positive state canary access.
param([Parameter(Mandatory)][string]$RuntimeSid,[Parameter(Mandatory)][string]$CallerSid,
      [Parameter(Mandatory)][string]$OutputDirectory,[switch]$IncludeJournal)
$ErrorActionPreference='Stop'
if(Test-Path $OutputDirectory){throw 'FRESH_PROBE_OUTPUT_REQUIRED'}
[void][IO.Directory]::CreateDirectory($OutputDirectory)
$base='C:\ProgramData\Imperium';$state="$base\ProtectedMission"
$immutable=@("$base\ProtectedMissionCode","$base\ProtectedMissionPHP","$base\ProtectedMissionShell","$base\ProtectedMissionTarget",'C:\Windows\System32\WindowsPowerShell\v1.0')
$items=@();foreach($root in $immutable){$items+=@(Get-Item $root -Force)+@(Get-ChildItem $root -Recurse -Force)}
if($items|Where-Object{$_.Attributes -band [IO.FileAttributes]::ReparsePoint}){throw 'REPARSE_REFUSED'}
$aclRows=@($items|ForEach-Object{[ordered]@{path=$_.FullName;attributes=$_.Attributes.ToString();sddl=(Get-Acl $_.FullName).Sddl}})
$aclRows|ConvertTo-Json -Depth 8|Set-Content (Join-Path $OutputDirectory 'owner-acls.json')
foreach($role in @('Runtime','Caller')){
 $rows=[Collections.Generic.List[object]]::new()
 foreach($item in $items){
    foreach($right in @(@('write-data',2),@('append-data',4),@('delete',65536),@('write-dac',262144),@('write-owner',524288))){$rows.Add([ordered]@{path=$item.FullName;right=$right[0];mask=$right[1];expected='ACCESS_DENIED'})}
    if($item.PSIsContainer){$rows.Add([ordered]@{path=$item.FullName;right='delete-child';mask=64;expected='ACCESS_DENIED'})}
    if($role -eq 'Runtime'){$rows.Add([ordered]@{path=$item.FullName;right='read-or-list';mask=1;expected='ACCESS_SUCCEEDED'})}
 }
 foreach($path in @('C:\','C:\ProgramData',$base,'C:\Windows','C:\Windows\System32','C:\Windows\System32\WindowsPowerShell')){
    if((Get-Item -LiteralPath $path -Force).Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'PARENT_REPARSE_REFUSED'}
    foreach($right in @(@('delete-child',64),@('write-dac',262144),@('write-owner',524288))){$rows.Add([ordered]@{path=$path;right=$right[0];mask=$right[1];expected='ACCESS_DENIED'})}
 }
 $stateFiles=@('state-canary','metadata-canary','installation.json');if($IncludeJournal){$stateFiles+='authority.journal'}
 foreach($file in $stateFiles){
    foreach($right in @(@('read-data',1),@('write-data',2),@('delete',65536),@('write-dac',262144),@('write-owner',524288))){
        if($role -eq 'Runtime' -and $file -eq 'authority.journal' -and $right[0] -in @('write-dac','write-owner')){continue}
        # Runtime owns the real journal it creates. Only the caller must be excluded.
        $expected=if($role -eq 'Runtime' -and ($right[0] -eq 'read-data' -or ($file -eq 'state-canary' -and $right[0] -in @('write-data','delete')) -or $file -eq 'authority.journal')){'ACCESS_SUCCEEDED'}else{'ACCESS_DENIED'}
        $rows.Add([ordered]@{path="$state\$file";right=$right[0];mask=$right[1];expected=$expected})
    }
 }
 foreach($right in @(@('list',1),@('add-file',2),@('delete-child',64))){
    $rows.Add([ordered]@{path=$state;right=$right[0];mask=$right[1];expected=if($role -eq 'Runtime' -and $right[0] -ne 'delete-child'){'ACCESS_SUCCEEDED'}else{'ACCESS_DENIED'}})
 }
 [ordered]@{role=$role;sid=if($role -eq 'Runtime'){$RuntimeSid}else{$CallerSid};probes=$rows}|ConvertTo-Json -Depth 10|Set-Content (Join-Path $OutputDirectory "$role-plan.json")
}
'PROBE_PLANS_PREPARED_NOT_MEASURED'
