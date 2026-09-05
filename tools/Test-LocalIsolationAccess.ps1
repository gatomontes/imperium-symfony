# Open handles only: never read, write, truncate, rename or delete the target.
param([Parameter(Mandatory)][string]$Plan,[Parameter(Mandatory)][string]$Output)
$ErrorActionPreference='Stop'
if(Test-Path -LiteralPath $Output){throw 'OUTPUT_EXISTS'}
Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
using Microsoft.Win32.SafeHandles;
public static class PmaAccessHandle {
 [DllImport("kernel32.dll", CharSet=CharSet.Unicode, SetLastError=true)]
 public static extern SafeFileHandle CreateFile(string path,uint access,uint share,IntPtr security,uint mode,uint flags,IntPtr template);
 public static int Probe(string path,uint access) {
  using(var h=CreateFile(path,access,7,IntPtr.Zero,3,0x02000000,IntPtr.Zero)) {
   return h.IsInvalid ? Marshal.GetLastWin32Error() : 0;
  }
 }
}
'@
$p=Get-Content -LiteralPath $Plan -Raw | ConvertFrom-Json
$identity=[Security.Principal.WindowsIdentity]::GetCurrent()
$principal=[Security.Principal.WindowsPrincipal]::new($identity)
$rows=[Collections.Generic.List[object]]::new();$failed=$false;$metadata=@{}
foreach($probe in $p.probes){
    $code=[PmaAccessHandle]::Probe($probe.path,[uint32]$probe.mask)
    $result=switch($code){0{'ACCESS_SUCCEEDED'} 5{'ACCESS_DENIED'} 2{'FILE_ABSENT'} 3{'PATH_ABSENT'} default{'UNKNOWN_OS_ERROR'}}
    $pass=($probe.expected -ceq $result)
    if(-not $pass){$failed=$true}
    if(-not $metadata.ContainsKey($probe.path)){
        $sddl=$null;try{$sddl=(Get-Acl -LiteralPath $probe.path).Sddl}catch{}
        $metadata[$probe.path]=$sddl
    }
    $rows.Add([ordered]@{path=$probe.path;right=$probe.right;mask=$probe.mask;expected=$probe.expected;result=$result;win32_error=$code;pass=$pass})
}
$identityMatches=($identity.User.Value -ceq $p.sid)
$admin=$principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
$administratorMember=@($identity.Groups|Where-Object{$_.Value -eq 'S-1-5-32-544'}).Count -gt 0
if(-not $identityMatches -or $admin){$failed=$true}
if($p.role -in @('Runtime','Caller') -and $administratorMember){$failed=$true}
$result=[ordered]@{schema='imperium.local-access-measurement/v1';role=$p.role;sid=$identity.User.Value;expected_sid=$p.sid;identity_matches=$identityMatches;administrator_token=$admin;administrator_group_present=$administratorMember;groups=@($identity.Groups|ForEach-Object{$_.Value});utc=[DateTimeOffset]::UtcNow.ToString('o');plan_sha256=(Get-FileHash -LiteralPath $Plan).Hash;probes=$rows;acl_sddl_if_readable=$metadata;result=if($failed){'ISOLATION_FAILED_OR_UNRESOLVED'}else{'RECORDED_ACCESS_EXPECTATIONS_MET'};deployment_isolation_claimed=$false}
[IO.File]::WriteAllText($Output,($result|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
if($failed){throw 'ISOLATION_FAILED_OR_UNRESOLVED: preserve output and stop'}
$result.result
