param([Parameter(Mandatory)][string]$CodePath,[switch]$Recovery)
$ErrorActionPreference='Stop'
try {
    $root='C:\ProgramData\Imperium\ProtectedMission'
    $expectedCode='C:\ProgramData\Imperium\ProtectedMissionCode'
    if($CodePath.TrimEnd('\','/') -ne $expectedCode){throw 'code'}
    # Public, owner-controlled SID binding permits an exact wrong-identity refusal
    # before the Caller attempts to read its deliberately inaccessible metadata.
    $publicPath='C:\ProgramData\Imperium\ProtectedMissionProbePlans\deployment-binding.json'
    if(Test-Path -LiteralPath $publicPath){
        $binding=Get-Content -LiteralPath $publicPath -Raw|ConvertFrom-Json
        if($binding.runtime_sid -notmatch '^S-1-5-21-(\d+-){3}\d+$' -or $binding.caller_sid -notmatch '^S-1-5-21-(\d+-){3}\d+$' -or $binding.runtime_sid -eq $binding.caller_sid -or $binding.setup_session -notmatch '^[a-f0-9]{32}$' -or $binding.package_manifest_sha256 -notmatch '^[A-Fa-f0-9]{64}$'){throw 'binding'}
        $token=[Security.Principal.WindowsIdentity]::GetCurrent()
        if($token.User.Value -ne $binding.runtime_sid -or @($token.Groups|Where-Object{$_.Value -eq 'S-1-5-32-544'}).Count -gt 0){
            Write-Output 'PMA_RUNTIME_IDENTITY_REFUSED';exit 2
        }
    }
    $file=Join-Path $root 'installation.json'
    $installation=Get-Content -LiteralPath $file -Raw | ConvertFrom-Json
    $identity=[Security.Principal.WindowsIdentity]::GetCurrent()
    $principal=New-Object Security.Principal.WindowsPrincipal($identity)
    if($identity.User.Value -ne $installation.runtime_sid -or $installation.runtime_sid -eq $installation.caller_sid -or $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'identity'}
    $writeMask=[Security.AccessControl.FileSystemRights]'Write,Delete,ChangePermissions,TakeOwnership'
    function Assert-PmaAcl([string]$Path,[bool]$State,[bool]$IsInstallationFile) {
        $item=Get-Item -LiteralPath $Path -Force
        if($item.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'reparse'}
        $acl=Get-Acl -LiteralPath $Path
        if(-not $acl.AreAccessRulesProtected -and $Path -in @($root,$expectedCode,$file)){throw 'inheritance'}
        $owner=$acl.GetOwner([Security.Principal.SecurityIdentifier]).Value
        if(($IsInstallationFile -or -not $State) -and $owner -notin @('S-1-5-32-544','S-1-5-18')){throw 'owner'}
        foreach($rule in $acl.GetAccessRules($true,$true,[Security.Principal.SecurityIdentifier])) {
            if($rule.AccessControlType -ne [Security.AccessControl.AccessControlType]::Allow){continue}
            $sid=$rule.IdentityReference.Value
            if($State -and $sid -notin @('S-1-5-32-544','S-1-5-18',$installation.runtime_sid)){throw 'state-read'}
            if(($rule.FileSystemRights -band $writeMask) -ne 0) {
                $writers=@('S-1-5-32-544','S-1-5-18')
                if($State -and -not $IsInstallationFile){$writers+= $installation.runtime_sid}
                if($sid -notin $writers){throw 'write'}
            }
        }
    }
    Assert-PmaAcl $root $true $false
    Assert-PmaAcl $file $true $true
    Assert-PmaAcl $expectedCode $false $false
    foreach($item in Get-ChildItem -LiteralPath $expectedCode -Recurse -Force){Assert-PmaAcl $item.FullName $false $false}
    foreach($item in Get-ChildItem -LiteralPath $root -Recurse -Force){Assert-PmaAcl $item.FullName $true ($item.FullName -eq $file)}
    # Recovery still verifies identity/code/state; it grants no scratch operation.
    if(-not $Recovery){
        . (Join-Path $PSScriptRoot 'ProtectedMissionScratch.ps1')
        Assert-PmaScratchPolicy ($root+'Scratch') $installation.runtime_sid
        Assert-PmaScratchEmpty ($root+'Scratch')
    }
    Write-Output 'PMA_INSTALLATION_ACL_AND_IDENTITY_VERIFIED'
    exit 0
} catch { Write-Output 'PMA_INSTALLATION_ACL_OR_IDENTITY_REFUSED';exit 2 }
