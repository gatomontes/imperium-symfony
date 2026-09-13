<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Augur\{BaseProjection,FreshProducer,ConstitutionEvidence,AugurAdapter};
use App\Imperium\Runtime\Onboarding\DeepSeek\{KeySource,AccessAdapter,MissingEvidence};
use App\Imperium\Runtime\Onboarding\Assignment\{AssignmentEvidence,PersistentSettings};
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;

/** Reopens only synthetic producer history. No state seeding or signing in workers. */
final class AssignmentProcessContext
{
    public static function open(string $root,array $input,string $mode):array
    {
        $clock=new class($input['now']) implements \App\Imperium\Runtime\Clock{public function __construct(private int $now){}public function now():\DateTimeImmutable{return new \DateTimeImmutable('@'.$this->now);}};
        $store=new AuthorityStore($root,$clock,'instance-test','citadel-test','operator-test',str_repeat('a',40));
        $constitution=new class($input['constitution'],$input['artifacts']) implements ConstitutionEvidence{
            public function __construct(private array $pin,private array $artifacts){}
            public function verify(array $constitution,array $originals):void{
                R::require(R::same($constitution,$this->pin),'SYNTHETIC_CONSTITUTION_ORIGINAL');
                foreach($this->artifacts as $key=>$artifact){R::require(isset($originals[$key]) && R::same($originals[$key],$artifact),'SYNTHETIC_CONSTITUTION_ARTIFACT');}
            }
        };
        $basePins=array_filter($input['pins'],static fn(array $h):bool=>in_array($h['body']['kind']??'', ['augur-base-facts','augur-base-observation','synthetic-provider-bytes'],true));
        $producer=new FreshProducer(new OperatorRootOwnership($root),new BaseProjection(new SyntheticAugurBaseEvidence($basePins)),$constitution);
        $keys=new class implements KeySource{public function generation():string{return 'synthetic-generation';}public function withKey(callable $delivery):void{throw new \RuntimeException('WORKER_CREDENTIAL_ACCESS_FORBIDDEN');}};
        $adapter=new AugurAdapter(new AccessAdapter($input['grant'],new MissingEvidence(),$keys),$producer,$keys,new SyntheticAssignmentCognitionEvidence($input['pins'],$input['credential_ref']));
        $evidence=new class($input['pins'],$mode) implements AssignmentEvidence{
            public function __construct(private array $pins,private string $mode){}
            public function verify(array $policy,array $assignments,array $originals,array $responses):void{
                foreach($originals as $key=>$h){R::require(isset($this->pins[$key]) && R::same($h,$this->pins[$key]),'SYNTHETIC_ASSIGNMENT_ORIGINAL');}
                foreach($assignments as $tuple){$found=0;foreach($originals as $h){if(($h['body']['kind']??null)==='synthetic-assignment-profile-finding'){$b=Policy::content($h,'synthetic-assignment-profile-finding');if(R::same($b['tuple'],$tuple) && $b['disposition']==='PASS'){$found++;}}}R::require($found===1,'SYNTHETIC_PROFILE_PREDICATE');}
                R::require(count($responses)===3,'SYNTHETIC_RESPONSES');
            }
        };
        return [$store,new CommandLedger($store,$adapter,$adapter,$producer,$evidence),new PersistentSettings($store,$adapter,$evidence)];
    }
    public static function export(AssignmentFixture $f,array $request):array
    {
        $d=$f->fresh->d;
        return ['now'=>$d->f->now,'request'=>$request,'constitution'=>$f->fresh->constitution,'artifacts'=>$f->fresh->constitutionalOriginals,
            'pins'=>$f->assignmentPins,'grant'=>$d->grant,'credential_ref'=>$d->policy['body']['credential_ref']];
    }
}
