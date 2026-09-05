# Owner custody: import exact public files into reserved slots, once only.
param([Parameter(Mandatory)][string]$Directory,[Parameter(Mandatory)][ValidateSet('Runtime','Caller')][string]$Role,
      [Parameter(Mandatory)][string]$Access,[Parameter(Mandatory)][string]$Startup)
$ErrorActionPreference='Stop'
$principal=[Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'OWNER_ELEVATED_TERMINAL_REQUIRED'}
foreach($pair in @(@('access',$Access),@('startup',$Startup))) {
 $destination=Join-Path $Directory ($Role+'-'+$pair[0]+'.json')
 if([IO.File]::ReadAllText($destination) -cne 'PMA_RESERVED_UNMEASURED'){throw 'EVIDENCE_ALREADY_IMPORTED_PRESERVE'}
 [IO.File]::WriteAllBytes($destination,[IO.File]::ReadAllBytes($pair[1]))
}
'EXACT_PUBLIC_BYTES_IMPORTED'
