<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
use App\Imperium\Runtime\Persistence\{AtomicTransition,ImmutableRecordStore as Immutable,MutableStateStore as Mutable};
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J,FormationOwnerFrame as Owner,FormationPersonnel,FormationInstitution,FormationSignatures};
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture,SyntheticFormationClock};
class_exists(CitadelFormationFixture::class);
[$script,$root,$inputFile,$channel]=$argv;$in=json_decode(file_get_contents($inputFile),true,512,JSON_THROW_ON_ERROR);
if($in['preflight_pause']??false){
 require __DIR__.'/StoragePreflightObservationBarrier.php';
 $GLOBALS['ppc4_preflight_observation_path']=str_replace('\\','/',$root).'/var/imperium/mission/ordinary/new.json';
 $GLOBALS['ppc4_preflight_observation_channel']=$channel;
}
$barrier=static function()use($in,$channel):void{
 if(!($in['pause']??false))return;
 file_put_contents($channel.'.held','held');$end=microtime(true)+30;
 while(!file_exists($channel.'.release')){if(microtime(true)>$end)throw new RuntimeException('BARRIER_TIMEOUT');usleep(1000);}
};
file_put_contents($channel.'.attempt',$in['mode']);
try{
 $a=new AtomicTransition($root);$i=new Immutable($root,$a);$m=new Mutable($root,$a);
 if($in['mode']==='observe'){
  $clock=new SyntheticFormationClock();$clock->at=$in['at'];$j=new J($root);$p=new FormationPersonnel($j,new FormationSignatures($j,$clock),$clock,new FormationInstitution($root));
  $j->inspect(function(array $frame,Owner $o)use($p,$in,$barrier,$channel):void{$barrier();$v=$in['seat']==='courtyard.courtthane'?$p->currentCourtthaneInOwner($o):$p->currentLocksmithInOwner($o);file_put_contents($channel.'.observed',J::digest($v));});
 }elseif($in['mode']==='owned'){
  Owner::run($root,function(Owner $o)use($i,$m,$in,$barrier):void{
   if($in['entry']==='put'){$barrier();$i->putInOwner($o,$in['directory'],$in['id'],$in['next']);}
   elseif($in['entry']==='cas'){$barrier();$m->compareAndSwapInOwner($o,$in['path'],$in['digest'],$in['next']);}
   else{$m->compareAndSwapGuardedInOwner($o,$in['path'],$in['digest'],$barrier,$in['next']);}
  });
 }elseif($in['mode']==='owned-exception'){
  Owner::run($root,function(Owner $o)use($m,$in):void{$m->compareAndSwapGuardedInOwner($o,$in['path'],$in['digest'],static function():void{throw new RuntimeException('CONTROLLED_GUARD_EXCEPTION');},$in['next']);});
 }elseif($in['mode']==='lower-lock'){
  $entry=$in['entry'];$dir='var/imperium/native-authority';$path=$dir.'/new.json';
  $attempt=static function()use($entry,$i,$m,$dir,$path):void{
   if($entry==='put')$i->put($dir,'new',[]);elseif($entry==='cas')$m->compareAndSwap($path,null,[]);else$m->compareAndSwapGuarded($path,null,static function(){throw new RuntimeException('FORBIDDEN_GUARD');},[]);
  };
  // The ordinary guard already holds a lower storage lock; no upward acquisition is allowed.
  $m->compareAndSwapGuarded('var/imperium/mission/lower.json',null,$attempt,[]);
 }elseif($in['mode']==='held-primitive'){
  $entry=$in['entry'];$dir='var/imperium/native-authority';$path=$dir.'/new.json';
  $scope=($entry==='put'?'immutable:':'mutable:').hash('sha256',$entry==='put'?$dir:$path);
  $a->run($scope,static function()use($entry,$i,$m,$dir,$path):void{
   if($entry==='put')$i->put($dir,'new',[]);elseif($entry==='cas')$m->compareAndSwap($path,null,[]);else$m->compareAndSwapGuarded($path,null,static function(){throw new RuntimeException('FORBIDDEN_GUARD');},[]);
  });
 }elseif($in['mode']==='ordinary'){
  if($in['entry']==='put')$i->put('var/imperium/mission/ordinary','new',['value'=>1]);
  elseif($in['entry']==='guarded')$m->compareAndSwapGuarded('var/imperium/mission/ordinary/new.json',null,static function()use($channel):void{file_put_contents($channel.'.guard','called');},['value'=>1]);
  else$m->compareAndSwap('var/imperium/mission/ordinary/new.json',null,['value'=>1]);
 }else throw new RuntimeException('UNKNOWN_MODE');
 echo "OK\n";exit(0);
}catch(Throwable $e){echo $e->getMessage()."\n";exit(1);}
