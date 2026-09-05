param([Parameter(Mandatory)][string]$Destination,
      [string]$Source=(Split-Path $PSScriptRoot),
      [string]$PhpSource='C:\php\php-8.4',
      [string]$ShellSource=(Split-Path (Get-Command pwsh).Source))
$ErrorActionPreference='Stop'
if(Test-Path -LiteralPath $Destination){throw 'FRESH_PACKAGE_DIRECTORY_REQUIRED'}
function Copy-PmaTree([string]$From,[string]$To){
    $items=@(Get-Item -LiteralPath $From -Force)+@(Get-ChildItem -LiteralPath $From -Recurse -Force)
    if($items|Where-Object{$_.Attributes -band [IO.FileAttributes]::ReparsePoint}){throw 'PACKAGE_REPARSE_REFUSED'}
    Copy-Item -LiteralPath $From -Destination $To -Recurse
}
[void][IO.Directory]::CreateDirectory($Destination)
$code=Join-Path $Destination 'ProtectedMissionCode'
[void][IO.Directory]::CreateDirectory($code)
foreach($name in @('src','bin','vendor','tools')){Copy-PmaTree (Join-Path $Source $name) $code}
$php=Join-Path $Destination 'ProtectedMissionPHP'
[void][IO.Directory]::CreateDirectory($php)
[void][IO.Directory]::CreateDirectory((Join-Path $php 'ext'))
[void][IO.Directory]::CreateDirectory((Join-Path $php 'empty-ini'))
foreach($file in @((Get-Item (Join-Path $PhpSource 'php.exe')))+@(Get-ChildItem $PhpSource -Filter '*.dll' -File)){
    if($file.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'PHP_REPARSE_REFUSED'}
    Copy-Item -LiteralPath $file.FullName -Destination $php
}
foreach($name in @('php_sodium.dll','php_mbstring.dll')){Copy-Item -LiteralPath (Join-Path $PhpSource "ext/$name") -Destination (Join-Path $php 'ext')}
$ini=@'
extension_dir = "C:/ProgramData/Imperium/ProtectedMissionPHP/ext"
extension = php_sodium.dll
extension = php_mbstring.dll
auto_prepend_file =
auto_append_file =
allow_url_fopen = Off
allow_url_include = Off
display_errors = stderr
log_errors = Off
memory_limit = 256M
date.timezone = UTC
'@
[IO.File]::WriteAllText((Join-Path $php 'php.ini'),$ini,[Text.UTF8Encoding]::new($false))
Copy-PmaTree $ShellSource (Join-Path $Destination 'ProtectedMissionShell')
$target=Join-Path $Destination 'ProtectedMissionTarget'
$inventory=& (Join-Path $PhpSource 'php.exe') (Join-Path $Source 'tools/local-isolation.php') materialize $Source $target
if($LASTEXITCODE -ne 0){throw 'TARGET_PREPARATION_FAILED'}
[IO.File]::WriteAllText((Join-Path $Destination 'target-inventory.json'),($inventory -join "`n"),[Text.UTF8Encoding]::new($false))
$draft=& (Join-Path $PhpSource 'php.exe') (Join-Path $Source 'tools/local-isolation.php') draft (Join-Path $Destination 'target-inventory.json') 'C:\ProgramData\Imperium\ProtectedMissionTarget'
if($LASTEXITCODE -ne 0){throw 'DRAFT_FAILED'}
[IO.File]::WriteAllText((Join-Path $Destination 'mission-draft.json'),($draft -join "`n"),[Text.UTF8Encoding]::new($false))
$manifest=& (Join-Path $PhpSource 'php.exe') (Join-Path $Source 'tools/local-isolation.php') manifest $Destination
if($LASTEXITCODE -ne 0){throw 'MANIFEST_FAILED'}
[IO.File]::WriteAllText((Join-Path $Destination 'package-manifest.json'),($manifest -join "`n"),[Text.UTF8Encoding]::new($false))
[pscustomobject]@{package=$Destination;manifest_sha256=(Get-FileHash (Join-Path $Destination 'package-manifest.json')).Hash;isolation_proved=$false}
