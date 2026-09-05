param([Parameter(Mandatory)][string]$Manifest,[Parameter(Mandatory)][string]$ManifestSha256,
      [string]$Base='C:\ProgramData\Imperium')
$ErrorActionPreference='Stop'
if((Get-FileHash -LiteralPath $Manifest).Hash -ine $ManifestSha256){throw 'MANIFEST_DIGEST_CHANGED'}
$rows=Get-Content -LiteralPath $Manifest -Raw|ConvertFrom-Json -AsHashtable
$roots=@('ProtectedMissionCode','ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget')
$count=0
foreach($name in $roots){
 $root=Join-Path $Base $name
 $items=@(Get-Item -LiteralPath $root -Force)+@(Get-ChildItem -LiteralPath $root -Recurse -Force)
 if($items|Where-Object{$_.Attributes -band [IO.FileAttributes]::ReparsePoint}){throw 'INSTALLED_REPARSE_POINT'}
 $expected=@($rows.Keys|Where-Object{$_.StartsWith($name+'/')}|Sort-Object)
 $actual=@($items|Where-Object{-not $_.PSIsContainer}|ForEach-Object{$name+'/'+$_.FullName.Substring($root.Length+1).Replace('\','/')}|Sort-Object)
 if(($expected -join "`n") -cne ($actual -join "`n")){throw "INSTALLED_FILE_SET_CHANGED: $name"}
 foreach($path in $expected){if((Get-FileHash -LiteralPath (Join-Path $Base $path)).Hash -ine $rows[$path]){throw "INSTALLED_HASH_CHANGED: $path"};$count++}
}
[ordered]@{result='INSTALLED_BYTES_MATCH_REVIEWED_PACKAGE';files=$count;manifest_sha256=$ManifestSha256;utc=[DateTimeOffset]::UtcNow.ToString('o');sid=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value;isolation_proved=$false}|ConvertTo-Json
