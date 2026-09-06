<?php
declare(strict_types=1);
// Owner-run disposable proof. Only the exact relocated production CLI owns authority.
require dirname(__DIR__).'/vendor/autoload.php';
use App\Bootstrap\CanonicalJson;
use App\ProtectedMission\PublicTrust;
use App\ProtectedMission\ProofProcess;
$mode=$argv[1] ?? '';$base=$argv[2] ?? '';$private=$argv[3] ?? '';
if (!preg_match('~^C:[/\\\\]ProgramData[/\\\\]PmaScratchProof-[a-f0-9]{32}[/\\\\]installation$~D',$base)) throw new RuntimeException('DISPOSABLE_BASE_REQUIRED');
$processInput=[];
$call=static function(array $args,string $input='',?string $expectedRefusal=null)use($base,&$processInput):string {
    $result=ProofProcess::run([PHP_BINARY,$base.'/ProtectedMissionCode/bin/protected-mission.php',...$args],$input);
    $label=$expectedRefusal===null?$args[0]:'abandoned-prepare';
    $processInput[]=ProofProcess::check($result,$label,$expectedRefusal);
    return $result['output'];
};
if($mode==='keys') {
    // Private directory was created/ACL-protected by the owner in their own profile.
    $pair=sodium_crypto_sign_keypair();$key=sodium_crypto_sign_publickey($pair);
    $trust=['identity'=>'disposable-scratch-operator','competence'=>PublicTrust::COMPETENCE,'public_key'=>base64_encode($key),'not_before'=>time()-5,'expires_at'=>time()+86400];
    $h=fopen($private.'/disposable.secret','xb');if(!$h)throw new RuntimeException('EXISTING_KEY_REFUSED');
    fwrite($h,sodium_crypto_sign_secretkey($pair));fclose($h);sodium_memzero($pair);
    $h=fopen($private.'/public-trust.json','xb');fwrite($h,json_encode($trust,JSON_THROW_ON_ERROR));fclose($h);
    echo json_encode(['fingerprint'=>hash('sha256',$key),'disposable_only'=>true]);exit;
}
$trust=json_decode(file_get_contents($base.'/ProtectedMissionExchange/public-trust.json'),true,32,JSON_THROW_ON_ERROR);
if($mode==='enroll') {echo $call(['enroll',hash('sha256',base64_decode($trust['public_key'],true))],json_encode($trust,JSON_THROW_ON_ERROR));exit;}
if($mode!=='ceremony')throw new RuntimeException('MODE_REFUSED');
// The PowerShell wrapper verifies complete native pre/post/current readiness first.
$input=json_decode(file_get_contents($base.'/ProtectedMissionExchange/mission-draft.json'),true,128,JSON_THROW_ON_ERROR);
// Packaged draft wrapper is converted by the production draft builder below.
$draft=ProofProcess::run([PHP_BINARY,$base.'/ProtectedMissionCode/tools/local-isolation.php','draft',$base.'/ProtectedMissionExchange/target-inventory.json',$base.'/ProtectedMissionTarget',(string)(time()+1800)]);
$processInput[]=ProofProcess::check($draft,'draft');$input=$draft['output'];
$c=json_decode($call(['prepare'],$input),true,128,JSON_THROW_ON_ERROR)['challenge_id'];
$payload=$call(['export',$c]);$render=$call(['render',$c]);
if($payload!==$call(['export',$c]) || !str_contains($render,hash('sha256',$payload)))throw new RuntimeException('EXPORT_RENDER_MISMATCH');
$secret=file_get_contents($private.'/disposable.secret');
try{$signature=base64_encode(sodium_crypto_sign_detached($payload,$secret));}finally{sodium_memzero($secret);}
$call(['submit'],json_encode(['challenge_id'=>$c,'signature'=>$signature],JSON_THROW_ON_ERROR));
$aid=json_decode($call(['derive',$c]),true,128,JSON_THROW_ON_ERROR)['authorization_id'];
$chain=json_decode($call(['verify',$aid]),true,512,JSON_THROW_ON_ERROR);
if(CanonicalJson::encode($chain['payload'])!==$payload)throw new RuntimeException('CHAIN_PAYLOAD_CHANGED');
$status=json_decode($call(['status',$aid]),true,128,JSON_THROW_ON_ERROR);
if($status['lifecycle']['state']!=='AUTHORIZED')throw new RuntimeException('WRONG_STATE');
App\ProtectedMission\ScratchWorkspace::assertClean($base.'/ProtectedMission');
$journal=$base.'/ProtectedMission/authority.journal';$before=hash_file('sha256',$journal);
$abandoned=$base.'/ProtectedMissionScratch/work-'.bin2hex(random_bytes(16));
mkdir($abandoned);file_put_contents($abandoned.'/incident','disposable abandoned-workspace probe');
$call(['prepare'],$input,'PMA_INSTALLATION_CHECK_FAILED');
$recovery=json_decode($call(['status',$aid]),true,128,JSON_THROW_ON_ERROR);
if($recovery['lifecycle']['state']!=='AUTHORIZED' || hash_file('sha256',$journal)!==$before || !is_file($abandoned.'/incident'))throw new RuntimeException('RECOVERY_CHANGED_STATE');
// Remove only this harness-created, known inert probe after checking its bytes.
if(file_get_contents($abandoned.'/incident')!=='disposable abandoned-workspace probe')throw new RuntimeException('PROBE_CHANGED_PRESERVE');
if(!unlink($abandoned.'/incident') || !rmdir($abandoned))throw new RuntimeException('PROBE_CLEANUP_FAILED');
App\ProtectedMission\ScratchWorkspace::assertClean($base.'/ProtectedMission');
echo json_encode(['result'=>'ACTUAL_INSTALLED_CEREMONY_PASSED','challenge_id'=>$c,'authorization_id'=>$aid,'payload_sha256'=>hash('sha256',$payload),'render_sha256'=>hash('sha256',$render),'scratch_clean'=>true,'abandoned_refusal_and_native_status_recovery'=>true,'target_execution'=>false,'process_input'=>$processInput],JSON_THROW_ON_ERROR);
