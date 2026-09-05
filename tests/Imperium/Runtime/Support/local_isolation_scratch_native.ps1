$ErrorActionPreference='Stop'
$repo=(Resolve-Path "$PSScriptRoot/../../../..").Path
. "$repo/tools/ProtectedMissionScratch.ps1"
. "$repo/tools/LocalIsolationReadiness.ps1"
$root=Join-Path ([IO.Path]::GetTempPath()) ('imperium-scratch-native-'+[guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($root)
$state="$root/authority";$scratch=$state+'Scratch'
[void][IO.Directory]::CreateDirectory($scratch)
$sid=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value
# Actual masks mirror the production builder, owner is deliberately different.
$null=& icacls.exe $scratch '/inheritance:r' '/grant:r' ('*'+$sid+':(RX,WD,AD)')
if($LASTEXITCODE -ne 0){throw 'NATIVE_ACL_FAILED'}
$null=& icacls.exe $scratch '/grant' ('*'+$sid+':(OI)(CI)(IO)(M)')
if($LASTEXITCODE -ne 0){throw 'NATIVE_ACL_FAILED'}
$rejected=$false;try{Assert-PmaScratchPolicy $scratch $sid}catch{$rejected=$true}
if(-not $rejected){throw 'NONADMIN_OWNER_ACCEPTED'}
$sddl=(Get-Acl $scratch).Sddl
$program=@'
<?php
require $argv[1].'/vendor/autoload.php';
use App\ProtectedMission\ScratchWorkspace;
$state=$argv[2];$sid=$argv[3];
$result=ScratchWorkspace::run($state,function($path):array {
 mkdir($path.'/nested/deep',0700,true);
 file_put_contents($path.'/nested/deep/file','native');
 rename($path.'/nested/deep/file',$path.'/nested/deep/renamed');
 if(file_get_contents($path.'/nested/deep/renamed')!=='native') throw new RuntimeException('READ_FAILED');
 return ['native_nested_write_rename_read'=>true];
});
ScratchWorkspace::assertClean($state);
try {
 ScratchWorkspace::run($state,function($path)use($sid):array {
  $file=$path.'/retained';file_put_contents($file,'incident evidence');
  $p=proc_open(['icacls.exe',$file,'/deny','*'.$sid.':(D)'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  fclose($pipes[0]);stream_get_contents($pipes[1]);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);
  if(proc_close($p)!==0) throw new RuntimeException('DENY_FAILED');
  return [];
 });
 throw new RuntimeException('CLEANUP_SHOULD_FAIL');
}catch(RuntimeException $e){if($e->getMessage()!=='PMA_SCRATCH_CLEANUP_FAILED')throw $e;}
try{ScratchWorkspace::assertClean($state);throw new RuntimeException('ABANDONED_ACCEPTED');}
catch(RuntimeException $e){if($e->getMessage()!=='PMA_SCRATCH_ABANDONED_OR_BUSY')throw $e;}
$result['cleanup_failure_preserved']=true;
echo json_encode($result);
'@
[IO.File]::WriteAllText("$root/components.php",$program)
$result=& php "$root/components.php" $repo $state $sid
if($LASTEXITCODE -ne 0){throw ('NATIVE_COMPONENTS_FAILED '+$result)}
# A real NTFS junction must refuse before following it or deleting its target.
[void][IO.Directory]::CreateDirectory("$root/target")
[IO.File]::WriteAllText("$root/target/keep",'keep')
$null=New-Item -ItemType Junction -Path "$root/linkScratch" -Target "$root/target"
$code='require $argv[1]."/vendor/autoload.php"; try{App\ProtectedMission\ScratchWorkspace::assertClean($argv[2]);exit(3);}catch(RuntimeException $e){echo $e->getMessage();}'
$refusal=& php -r $code $repo "$root/link"
if($LASTEXITCODE -ne 0 -or $refusal -notin @('PMA_SCRATCH_REPARSE','PMA_SCRATCH_PATH_SUBSTITUTED')){throw 'JUNCTION_NOT_REFUSED'}
if([IO.File]::ReadAllText("$root/target/keep") -cne 'keep'){throw 'JUNCTION_TARGET_CHANGED'}
$policy=New-PmaScratchAcl $sid
$inventory=@{items=@(@{path=$scratch;class='scratch-root';directory=$true})}
$r=Get-PmaRequiredProbes $inventory Runtime;$c=Get-PmaRequiredProbes $inventory Caller
foreach($mask in @(1,2,4)){if(@($r|Where-Object{$_.mask -eq $mask -and $_.expected -eq 'ACCESS_SUCCEEDED'}).Count -ne 1){throw 'RUNTIME_POLICY_MISSING'}}
if(@($c|Where-Object{$_.expected -ne 'ACCESS_DENIED'}).Count){throw 'CALLER_POLICY_WEAKENED'}
@{result='SCRATCH_NATIVE_COMPONENTS_PASSED';fixture=$root;token=(Get-PmaToken);actual_sddl=$sddl;production_sddl=$policy.Sddl;components=($result|ConvertFrom-Json);junction_refused=$refusal;equivalent_ceremony_proof=$false}|ConvertTo-Json -Depth 10
