<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Augur\{BaseProjection,FreshProducer,ConstitutionEvidence};
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Tests\Imperium\Runtime\Support\SyntheticAugurBaseEvidence;
$root=$argv[1];$mode=$argv[2];$resolved=realpath($root);$temporary=realpath(sys_get_temp_dir());
if($resolved===false || $temporary===false || !str_starts_with(strtolower($resolved),strtolower($temporary.DIRECTORY_SEPARATOR).'imperium-o2-test-')){exit(91);}
$input=json_decode(file_get_contents($root.'/fresh-worker-input.json'),true,512,JSON_THROW_ON_ERROR);
$clock=new class($input['now']) implements App\Imperium\Runtime\Clock{public function __construct(private int $now){}public function now():DateTimeImmutable{return new DateTimeImmutable('@'.$this->now);}};
$store=new AuthorityStore($root,$clock,'instance-test','citadel-test','operator-test',str_repeat('a',40));
$constitution=new class($input['constitution'],$input['artifacts']) implements ConstitutionEvidence{
    public function __construct(private array $pin,private array $artifacts){}
    public function verify(array $constitution,array $originals):void{
        R::require(R::same($constitution,$this->pin),'SYNTHETIC_CONSTITUTION_ORIGINAL');foreach($this->artifacts as $key=>$artifact){R::require(isset($originals[$key]) && R::same($originals[$key],$artifact),'SYNTHETIC_CONSTITUTION_ARTIFACT');}
        $grant=Policy::content($constitution,'augur-constitution');R::require(R::same(Policy::content($originals[R::key($grant['charter_ref'])],'synthetic-augur-charter'),['route'=>'FRESH','seat'=>'oracle.augur']),'SYNTHETIC_CONSTITUTION_CHARTER');
    }
};
$base=new BaseProjection(new SyntheticAugurBaseEvidence($input['base_pins'],$mode==='before-publication'?static function():void{exit(73);}:null));
$producer=new FreshProducer(new OperatorRootOwnership($root),$base,$constitution);$ledger=new CommandLedger($store,founding:$producer);
file_put_contents($root.'/fresh-ready-'.$mode,'ready');$deadline=microtime(true)+120;
while(!is_file($root.'/fresh-go')){if(microtime(true)>$deadline){exit(92);}usleep(10000);}
try{$result=$ledger->advance(json_encode($input['request'],JSON_THROW_ON_ERROR));if($mode==='after-publication'){exit(74);}echo $result['status']."\n";exit(0);}
catch(RuntimeException $e){echo $e->getMessage()."\n";exit(23);}
