$ErrorActionPreference='Stop'
$repo=Split-Path (Split-Path (Split-Path (Split-Path $PSScriptRoot)))
. "$repo\tools\ProtectedMission.ps1"
$saved=$env:PSModulePath
try {
 $env:PSModulePath="$PSHOME\Modules;C:\Windows\System32\WindowsPowerShell\v1.0\Modules"
 $polluted=$env:PSModulePath
 $code=@'
$ErrorActionPreference='Stop';$ProgressPreference='SilentlyContinue'
try { $null=Get-Acl -LiteralPath 'C:\Windows'; 'ACL_READ_OK'; exit 0 }
catch { 'MODULE_IMPORT_FAILED'; exit 2 }
'@
 $encoded=[Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($code))
 $win='C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe'
 $start=[Diagnostics.ProcessStartInfo]::new($win)
 $start.UseShellExecute=$false;$start.CreateNoWindow=$true
 $start.RedirectStandardOutput=$true;$start.RedirectStandardError=$true
 foreach($arg in @('-NoProfile','-NonInteractive','-EncodedCommand',$encoded)){$start.ArgumentList.Add($arg)}
 $child=[Diagnostics.Process]::Start($start)
 $baseline=$child.StandardOutput.ReadToEnd();$err=$child.StandardError.ReadToEnd();$child.WaitForExit()
 if($child.ExitCode -ne 2 -or $baseline.Trim() -cne 'MODULE_IMPORT_FAILED'){throw 'POLLUTED_BASELINE_NOT_REPRODUCED'}
 $child.Dispose()
 $direct=Invoke-PmaProcess -Php $win -Script '-NoProfile' -Arguments @('-NonInteractive','-EncodedCommand',$encoded)
 if($direct.ExitCode -ne 0 -or $direct.Output.Trim() -cne 'ACL_READ_OK' -or $direct.Error -ne ''){throw 'POWERSHELL_BOUNDARY_FAILED'}
 $nested=Invoke-PmaProcess -Php (Get-Command php).Source -Script "$PSScriptRoot\local_isolation_startup_child.php" -Arguments @($encoded)
 if($nested.ExitCode -ne 0 -or $nested.Output.Trim() -cne 'ACL_READ_OK' -or $nested.Error -ne ''){throw 'PHP_BOUNDARY_FAILED'}
 if($env:PSModulePath -cne $polluted){throw 'PARENT_ENVIRONMENT_CHANGED'}
 'POLLUTED_BASELINE_REPRODUCED_BOTH_CHILD_BOUNDARIES_PASSED_PARENT_UNCHANGED'
} finally { $env:PSModulePath=$saved }
