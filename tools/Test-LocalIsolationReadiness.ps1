param([string]$Base='C:\ProgramData\Imperium')
$ErrorActionPreference='Stop'
. "$PSScriptRoot\LocalIsolationReadiness.ps1"
try{$result=Test-PmaReadiness (Read-PmaEvidence "$Base\ProtectedMissionExchange\owner-readiness.json") $Base}
catch{$result=@{result='LOCAL_ISOLATION_READINESS_REFUSED';reason=$_.Exception.Message}}
$result|ConvertTo-Json -Depth 20
if($result.result -cne 'LOCAL_ISOLATION_READINESS_VALID'){exit 2}
