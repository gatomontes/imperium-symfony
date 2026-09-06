# IS03 characterization only: old production directory rules, disposable native ACL.
$ErrorActionPreference='Stop'
$repo=(Resolve-Path "$PSScriptRoot/../../../..").Path
. "$repo/tools/LocalIsolationReadiness.ps1"
$token=Get-PmaToken
Assert-PmaToken $token $token.sid
$root=Join-Path ([IO.Path]::GetTempPath()) ('imperium-scratch-s0-'+[guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($root)
$RuntimeSid=$token.sid
# The fixture owner remains this standard user. This proves mkdir denial only,
# never administrator-owned installation or distinct-account equivalence.
$ast=[Management.Automation.Language.Parser]::ParseFile("$repo/tools/Install-LocalIsolationOwnerPackage.ps1",[ref]$null,[ref]$null)
$fn=$ast.Find({param($n) $n -is [Management.Automation.Language.FunctionDefinitionAst] -and $n.Name -eq 'Set-PmaOperationalDirectory'},$true)
if(-not $fn){throw 'OLD_PRODUCTION_FUNCTION_ABSENT'}
# Equivalent tested create-file/create-directory masks; ownership is not equivalent.
$null=& icacls.exe $root '/inheritance:r' '/grant:r' ('*'+$RuntimeSid+':(RX,WD)')
if($LASTEXITCODE -ne 0){throw 'NATIVE_ACL_APPLY_FAILED'}
$null=& icacls.exe $root '/grant' ('*'+$RuntimeSid+':(OI)(IO)(M)')
if($LASTEXITCODE -ne 0){throw 'NATIVE_ACL_APPLY_FAILED'}
[IO.File]::WriteAllText("$root/file-canary",'native file creation succeeds')
$denied=$false
try{[void][IO.Directory]::CreateDirectory("$root/scratch-denied")}catch [UnauthorizedAccessException]{$denied=$true}
if(-not $denied){throw 'IS03_NOT_REPRODUCED'}
@{result='IS03_NATIVE_DIRECTORY_CREATION_DENIED';token=$token;fixture=$root;sddl=(Get-Acl $root).Sddl;create_file=$true;create_directory_denied=$true;equivalent_ceremony_proof=$false;owner_difference='Current standard user owns fixture; no owner/reference equivalence claimed';production_function_sha256=[Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Text.Encoding]::UTF8.GetBytes($fn.Extent.Text)))}|ConvertTo-Json -Depth 10
