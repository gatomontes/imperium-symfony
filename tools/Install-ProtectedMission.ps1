[CmdletBinding(SupportsShouldProcess)]
param([Parameter(Mandatory)][string]$Source,
      [Parameter(Mandatory)][string]$RuntimeSid,
      [Parameter(Mandatory)][string]$CallerSid)
$ErrorActionPreference='Stop'
$statePath='C:\ProgramData\Imperium\ProtectedMission'
$codePath='C:\ProgramData\Imperium\ProtectedMissionCode'
if ($RuntimeSid -eq $CallerSid -or $RuntimeSid -notmatch '^S-1-5-21-(\d+-){3}\d+$' -or $CallerSid -notmatch '^S-1-5-21-(\d+-){3}\d+$') { throw 'Distinct non-builtin account SIDs are required.' }
if (-not $PSCmdlet.ShouldProcess("$statePath and $codePath",'Install reviewed code and explicit protected ACLs; no account creation or trust enrollment')) {
    [pscustomobject]@{StatePath=$statePath;CodePath=$codePath;RuntimeSid=$RuntimeSid;CallerSid=$CallerSid;Applied=$false}
    return
}
$principal=New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Run the reviewed installer as deployment administrator.' }
if ((Test-Path -LiteralPath $statePath) -or (Test-Path -LiteralPath $codePath)) { throw 'Existing installations are never replaced by bootstrap.' }
foreach ($sid in @($RuntimeSid,$CallerSid)) { $null=([Security.Principal.SecurityIdentifier]$sid).Translate([Security.Principal.NTAccount]) }
function Set-ExactPmaAcl([string]$Path,[bool]$Directory,[bool]$RuntimeWrite,[bool]$CallerRead) {
    if ($Directory) { $acl=New-Object Security.AccessControl.DirectorySecurity } else { $acl=New-Object Security.AccessControl.FileSecurity }
    $acl.SetAccessRuleProtection($true,$false)
    $acl.SetOwner([Security.Principal.SecurityIdentifier]'S-1-5-32-544')
    $inheritance=if($Directory){[Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit'}else{[Security.AccessControl.InheritanceFlags]::None}
    $rights=@{'S-1-5-32-544'='FullControl';'S-1-5-18'='FullControl'}
    $rights[$RuntimeSid]=if($RuntimeWrite){'Modify'}else{'ReadAndExecute'}
    if($CallerRead){$rights[$CallerSid]='ReadAndExecute'}
    foreach($sid in $rights.Keys){
        $rule=New-Object Security.AccessControl.FileSystemAccessRule([Security.Principal.SecurityIdentifier]$sid,[Security.AccessControl.FileSystemRights]$rights[$sid],$inheritance,[Security.AccessControl.PropagationFlags]::None,[Security.AccessControl.AccessControlType]::Allow)
        $acl.AddAccessRule($rule)
    }
    Set-Acl -LiteralPath $Path -AclObject $acl
}
[void][IO.Directory]::CreateDirectory($statePath)
[void][IO.Directory]::CreateDirectory($codePath)
Set-ExactPmaAcl $statePath $true $true $false
Set-ExactPmaAcl $codePath $true $false $true
foreach($folder in @('src','bin','vendor','tools')) {
    $from=Join-Path $Source $folder
    if(-not (Test-Path -LiteralPath $from -PathType Container)){throw "Reviewed source folder missing: $folder"}
    if(Get-ChildItem -LiteralPath $from -Recurse -Force | Where-Object { $_.Attributes -band [IO.FileAttributes]::ReparsePoint }){throw 'Reparse points are not accepted in installed code.'}
    Copy-Item -LiteralPath $from -Destination $codePath -Recurse
}
foreach($item in Get-ChildItem -LiteralPath $codePath -Recurse -Force){Set-ExactPmaAcl $item.FullName $item.PSIsContainer $false $true}
$installation=[ordered]@{separate_runtime_account_required=$true;code_path=$codePath;runtime_sid=$RuntimeSid;caller_sid=$CallerSid}
$file=Join-Path $statePath 'installation.json'
[IO.File]::WriteAllText($file,($installation|ConvertTo-Json -Compress),[Text.UTF8Encoding]::new($false))
Set-ExactPmaAcl $file $false $false $false
Write-Output 'Installed without trust. Validate from the Runtime and caller accounts before enrollment.'
