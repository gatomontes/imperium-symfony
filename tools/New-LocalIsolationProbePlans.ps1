# Compatibility entry: reviewed v2 plans bind installed SIDs and setup session.
param([Parameter(Mandatory)][ValidateSet('pre','post','current')][string]$Phase)
$ErrorActionPreference='Stop'
& (Join-Path $PSScriptRoot 'New-LocalIsolationMeasurement.ps1') -Phase $Phase
