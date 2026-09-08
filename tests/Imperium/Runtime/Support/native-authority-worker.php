<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
use App\Imperium\Runtime\Citadel\Authority\{AuthorityInput as A,GarrisonAuthorityRequest,RecruiterEvidence};
use App\Imperium\Runtime\Citadel\NativeAuthority\{NativeJournal,NativeProtocol,NativeBoundary};
use App\Imperium\Runtime\Clock;

[$script,$mode,$root]=$argv;
$resolved=realpath($root);
if($resolved===false||dirname($resolved)!==realpath(sys_get_temp_dir())||!str_starts_with(basename($resolved),'citadel-authority-synthetic-')||!in_array($mode,['first','second'],true))exit(99);
$input=A::read($root.'/worker-'.$mode.'.json');
$clock=new class($input['now']) implements Clock {public function __construct(private int $time){}public function now():\DateTimeImmutable{return new \DateTimeImmutable('@'.$this->time);}};
$hook=function(string $stage)use($input,$root,$mode):void {
    if(($input['pause']??null)!==$stage)return;
    file_put_contents($root.'/'.$mode.'-paused',$stage);
    $until=microtime(true)+12;
    while(!is_file($root.'/'.$mode.'-release')){if(microtime(true)>$until)throw new \RuntimeException('SYNTHETIC_WORKER_TIMEOUT');usleep(10000);}
};
$protocol=new NativeProtocol(new NativeJournal($root,$hook),$clock,new GarrisonAuthorityRequest($clock),new RecruiterEvidence($root,$clock));
file_put_contents($root.'/'.$mode.'-started','synthetic');
try {
    $result=match($input['operation']) {
        'apply'=>$protocol->apply($input['arguments']),
        'admit'=>$protocol->admit($input['arguments']['delivery_id'],$input['arguments']['binding_id']),
        'resolve'=>$protocol->resolve($input['arguments']['seat']),
        'enroll'=>(new \App\Imperium\Runtime\Citadel\NativeAuthority\NativeTrust(new NativeJournal($root),$clock))->enroll($input['arguments']['policy'],$input['arguments']['fingerprint']),
        'legacy'=>NativeBoundary::legacy($root,function()use($root,$hook){$hook('legacy-locked');$store=new \App\Bootstrap\StateStore($root);$store->locked(fn()=>$store->write(\App\Tests\Imperium\Runtime\Support\CitadelAuthorityFixture::state()));return ['legacy'=>true];}),
        default=>throw new \RuntimeException('SYNTHETIC_MODE_INVALID')};
    echo json_encode($result,JSON_THROW_ON_ERROR);exit(0);
}catch(\Throwable $e){echo json_encode(['refused'=>true,'code'=>preg_match('/^NAT[0-9]{3}_[A-Z0-9_]+$/D',$e->getMessage())?$e->getMessage():'SYNTHETIC_FIXED_REFUSAL']);exit(1);}
