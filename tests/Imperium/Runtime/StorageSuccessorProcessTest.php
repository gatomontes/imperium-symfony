<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FormationInstitution,FormationJournal as J};
use App\Tests\Imperium\Runtime\Support\ModelBoundFormationFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class StorageSuccessorProcessTest extends TestCase
{
 private array $processes=[];
 private function start(string $root,array $input,string $name):array {
  $c=$root.'/'.$name;file_put_contents($c.'.json',json_encode($input,JSON_THROW_ON_ERROR));
  $p=proc_open([PHP_BINARY,__DIR__.'/Support/storage-successor-worker.php',$root,$c.'.json',$c],[1=>['file',$c.'.out','w'],2=>['file',$c.'.err','w']],$pipes);
  self::assertIsResource($p);$this->processes[]=$p;$this->waitFile($c.'.attempt');return[$p,$c];
 }
 private function waitFile(string $path):void {$end=microtime(true)+30;while(!is_file($path)&&microtime(true)<$end){usleep(1000);clearstatcache(true,$path);}self::assertFileExists($path);}
 private function finish(array $w):array {
  [$p,$c]=$w;$end=microtime(true)+30;do{$s=proc_get_status($p);if(!$s['running'])break;usleep(1000);}while(microtime(true)<$end);
  self::assertFalse($s['running'],'Bounded child must not deadlock');$exit=$s['exitcode'];$closed=proc_close($p);$exit=$exit<0?$closed:$exit;
  $out=file_get_contents($c.'.out').file_get_contents($c.'.err');
  if($destination=getenv('PPC4_PUBLIC_EVIDENCE')){if(!is_dir($destination))mkdir($destination,0770,true);file_put_contents($destination.'/process-events.jsonl',json_encode(['checkpoint'=>basename($c),'input'=>json_decode(file_get_contents($c.'.json'),true),'native_exit'=>$exit,'output'=>$out,'utc'=>gmdate('c')],JSON_THROW_ON_ERROR)."\n",FILE_APPEND);}
  return[$exit,$out];
 }
 protected function tearDown():void {foreach($this->processes as$p)if(is_resource($p)){proc_terminate($p);proc_close($p);}}
 private function appoint(ModelBoundFormationFixture $x):void {
  $s=$x->f->journal->read()['state'];$effect=$x->seat==='courtyard.courtthane'?'APPOINT_COURTTHANE':'APPOINT_FORMATION_LOCKSMITH';
  $decision=$x->f->sign($effect,['candidate'=>$x->candidate,'scope'=>$s['citadel_id'],'seat'=>$x->seat,'generation'=>1]);
  if($x->seat==='courtyard.courtthane')$x->f->personnel->appointCourtthane($x->candidate,$decision);else$x->f->personnel->appointLocksmith($x->candidate,$decision);
 }
 private function observe(ModelBoundFormationFixture $x):array {return['mode'=>'observe','at'=>$x->f->clock->at,'seat'=>$x->seat];}
 private function mutation(ModelBoundFormationFixture $x,string $entry):array {
  $record=(new FormationInstitution($x->f->root))->witness('garrison')['occupancy'];$next=$record;$next['status']='RETIRED';
  return['mode'=>'owned','entry'=>$entry,'directory'=>'var/imperium/offices/garrison/occupancy','id'=>'synthetic-successor','path'=>'var/imperium/offices/garrison/occupancy/'.$record['binding_id'].'.json','digest'=>$record['record_digest'],
   'next'=>$entry==='put'?['schema'=>'synthetic-unsupported-successor/v1','seat'=>'garrison.constable','status'=>'CURRENT_ACTIVE']:$next];
 }
 public static function races():iterable {foreach(['courtyard.courtthane','clavium.locksmith']as$s)foreach(['put','cas','guarded']as$e)foreach([true,false]as$first)yield "$s-$e-".($first?'writer-first':'reader-first')=>[$s,$e,$first];}
 #[DataProvider('races')]
 public function testOwnedGenericMutationAndAuthenticCurrentHolderBothOrders(string $seat,string $entry,bool $writerFirst):void {
  $x=new ModelBoundFormationFixture($seat);
  try{
   $this->appoint($x);$root=$x->f->root;$read=$this->observe($x);$write=$this->mutation($x,$entry);
   self::assertSame([0,"OK\n"],$this->finish($this->start($root,$read,'positive')));
   if(($dest=getenv('PPC4_PUBLIC_EVIDENCE'))&&$entry==='put'&&$writerFirst){
    foreach(['citadel/formation','operator-root/installations','operator-root/packages','offices/garrison/occupancy','offices/guildhall/occupancy','offices/laboratorium/occupancy','offices/senate/occupancy','offices/conscription/occupancy']as$dir){
     foreach(glob($root.'/var/imperium/'.$dir.'/*.json')?:[]as$file){$out=$dest.'/originals/'.$seat.'/'.$dir;if(!is_dir($out))mkdir($out,0770,true);copy($file,$out.'/'.basename($file));}
    }
   }
   $first=$this->start($root,[...($writerFirst?$write:$read),'pause'=>true],'first');$this->waitFile($first[1].'.held');
   $h=fopen($root.'/var/imperium/runtime/transition-locks/'.hash('sha256','citadel-formation').'.lock','rb');self::assertFalse(flock($h,LOCK_EX|LOCK_NB));fclose($h);
   $second=$this->start($root,$writerFirst?$read:$write,'second');self::assertTrue(proc_get_status($second[0])['running']);self::assertFileDoesNotExist($second[1].'.observed');
   file_put_contents($first[1].'.release','release');self::assertSame([0,"OK\n"],$this->finish($first));
   $refusal=$entry==='put'?"CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED\n":"CMF121_INSTITUTION_UNAVAILABLE\n";
   self::assertSame($writerFirst?[1,$refusal]:[0,"OK\n"],$this->finish($second));
   self::assertSame([1,$refusal],$this->finish($this->start($root,$read,'fresh-refusal')));
   if(!$writerFirst)self::assertFileExists($first[1].'.observed');
  }finally{$x->close();}
 }
 public static function lower():iterable {foreach(['put','cas','guarded']as$entry)foreach(['lower-lock','held-primitive']as$mode)yield "$entry-$mode"=>[$entry,$mode];}
 #[DataProvider('lower')]
 public function testBareReservedWriteRefusesFromLowerGuardAndHeldTargetLock(string $entry,string $mode):void {
  $x=new ModelBoundFormationFixture();try{
   self::assertSame([1,"PPC401_RESERVED_STORAGE_OWNER_REQUIRED\n"],$this->finish($this->start($x->f->root,['mode'=>$mode,'entry'=>$entry],'lower')));
   self::assertDirectoryDoesNotExist($x->f->root.'/var/imperium/native-authority');self::assertFileDoesNotExist($x->f->root.'/var/imperium/mission/lower.json');
  }finally{$x->close();}
 }
 public function testTerminatedGuardReleasesBothLocksAndLeavesCurrentOriginalIntact():void {
  $x=new ModelBoundFormationFixture();try{
   $this->appoint($x);$root=$x->f->root;$w=$this->start($root,[...$this->mutation($x,'guarded'),'pause'=>true],'interrupted');$this->waitFile($w[1].'.held');proc_terminate($w[0]);self::assertNotSame(0,$this->finish($w)[0]);
   self::assertSame([0,"OK\n"],$this->finish($this->start($root,$this->observe($x),'after-interruption')));
   self::assertSame([0,"OK\n"],$this->finish($this->start($root,$this->mutation($x,'guarded'),'later-writer')));
  }finally{$x->close();}
 }
 public function testOwnedGuardExceptionReleasesBothLocksAndPreservesOriginal():void {
  $x=new ModelBoundFormationFixture();try{
   $this->appoint($x);$root=$x->f->root;$write=$this->mutation($x,'guarded');
   self::assertSame([1,"CONTROLLED_GUARD_EXCEPTION\n"],$this->finish($this->start($root,[...$write,'mode'=>'owned-exception'],'exception')));
   self::assertSame([0,"OK\n"],$this->finish($this->start($root,$this->observe($x),'after-exception')));
   self::assertSame([0,"OK\n"],$this->finish($this->start($root,$write,'later-owned-writer')));
  }finally{$x->close();}
 }
 public static function ordinary():iterable {yield ['put'];yield ['cas'];}
 public static function preflightEntries():iterable {yield ['put'];yield ['cas'];yield ['guarded'];}
 #[DataProvider('preflightEntries')]
 public function testOrdinaryPublicationBetweenAbsentObservationAndDirectoryScan(string $entry):void {
  $x=new ModelBoundFormationFixture();try{
   mkdir($x->f->root.'/var/imperium/mission/ordinary',0770,true);
   $one=$this->start($x->f->root,['mode'=>'ordinary','entry'=>$entry,'preflight_pause'=>true],'paused-preflight');$this->waitFile($one[1].'.held');
   self::assertSame([0,"OK\n"],$this->finish($this->start($x->f->root,['mode'=>'ordinary','entry'=>'put'],'competing-publication')));
   file_put_contents($one[1].'.release','release');
   self::assertSame($entry==='put'?[0,"OK\n"]:[1,"PST121_MUTABLE_STATE_COMPARE_AND_SWAP_CONFLICT\n"],$this->finish($one));
   self::assertFileDoesNotExist($one[1].'.guard');
  }finally{$x->close();}
 }
 #[DataProvider('ordinary')]
 public function testOrdinaryProcessCompetitionKeepsReplayAndCasSemantics(string $entry):void {
  $x=new ModelBoundFormationFixture();try{
   $one=$this->start($x->f->root,['mode'=>'ordinary','entry'=>$entry],'one');$two=$this->start($x->f->root,['mode'=>'ordinary','entry'=>$entry],'two');$results=[$this->finish($one),$this->finish($two)];sort($results);
   self::assertSame($entry==='put'?[[0,"OK\n"],[0,"OK\n"]]:[[0,"OK\n"],[1,"PST121_MUTABLE_STATE_COMPARE_AND_SWAP_CONFLICT\n"]],$results);
  }finally{$x->close();}
 }
}
