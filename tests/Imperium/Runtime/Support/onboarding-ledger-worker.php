<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
require_once __DIR__.'/OnboardingLedgerFixture.php';
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,CustodyCoordinator};
use App\Tests\Imperium\Runtime\Support\CountingOnboardingPorts;
[$script,$root,$token,$mode]=$argv;$input=json_decode(file_get_contents($root.'/b1-worker.json'),true,512,JSON_THROW_ON_ERROR);
$clock=new class($root,$input['now'],$mode) implements \App\Imperium\Runtime\Clock {
    public function __construct(private string $root,private int $at,private string $mode){}
    public function now():\DateTimeImmutable{
        if($this->mode==='after-response'){$files=glob($this->root.'/var/imperium/citadel/formation/*.json');sort($files);$state=json_decode(file_get_contents(end($files)),true)['state'];foreach($state['onboarding']['claims']??[] as $c){if(count($c['custody'])===5){exit(73);}}}
        return new \DateTimeImmutable('@'.$this->at);
    }
};
$store=new AuthorityStore($root,$clock,'instance-test',$input['citadel'],'operator-test',str_repeat('a',40));$ports=new CountingOnboardingPorts($root,$input['operation']);
$ports->hook=static function(string $at)use($mode,$root):void{if($at===$mode){exit(73);}if($mode==='pause-'.$at){file_put_contents($root.'/b1-checkpoint-ready','ready');$deadline=microtime(true)+40;while(!is_file($root.'/b1-checkpoint-go')){if(microtime(true)>$deadline){exit(74);}usleep(10000);}}};
$ledger=new CommandLedger($store,$ports,$ports);$custody=new CustodyCoordinator($ledger,$ports,$ports,$ports);
file_put_contents($root.'/b1-ready-'.$token,'ready');$deadline=microtime(true)+40;
while(!is_file($root.'/b1-go')){if(microtime(true)>$deadline){exit(74);}usleep(10000);}
if($mode==='before-reservation'){exit(73);}
try{
    if($mode==='after-reservation'){$ledger->advance(json_encode($input['request']));exit(73);}
    $result=$custody->advance(json_encode($input['request']));echo json_encode($result);exit($result['status']==='HISTORICAL_RECOGNITION'?2:0);
}catch(\Throwable $e){echo $e->getMessage();exit(2);}
