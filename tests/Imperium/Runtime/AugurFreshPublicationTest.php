<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AugurFreshFixture as F;
use App\Imperium\Runtime\Onboarding\Augur\{AugurMigration,Holder};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,LedgerState};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use PHPUnit\Framework\TestCase;

final class AugurFreshPublicationTest extends TestCase
{
    public function testActualFoundingPublicationAndReplay():void
    {
        $f=new F();try{
            $d=$f->d;$store=$d->f->store;
            $prior=$store->journal->read()['state']['onboarding'];
            $migration=(new AugurMigration($store))->migrate($d->f->head());
            self::assertSame($prior,$migration['body']['prior_state']);
            $f->ready();$d->advance('select-base');$d->advance('map-base');$head=$d->f->head();$q=$d->request('found-augur');
            $ledger=new CommandLedger($store,$d->adapter,$d->adapter,$f->founding());
            $result=$ledger->advance($d->f::json($q));$s=$store->journal->read()['state']['onboarding'];
            self::assertCount(1,$s['bindings']);$holder=array_values($s['bindings'])[0]['record'];
            self::assertSame($head['generation']+1,$d->f->head()['generation']);
            self::assertSame($head['generation']+1,$holder['body']['generation']);
            self::assertSame('deepseek-v4-flash',$holder['body']['binding']['model_id']);
            self::assertSame([R::reference($holder)],LedgerState::step($s,$d->policy,'found-augur')['completion']['body']['result_refs']);
            self::assertSame($holder,Holder::current($store,$s,R::reference($holder)));
            foreach(['generation','profile_ref','base_completion_ref'] as $field){
                $forged=$s;$record=$holder;
                $record['body'][$field]=$field==='generation'?$holder['body']['generation']+1:$d->policy['body']['requirements_ref'];
                unset($record['record_digest']);$record=R::seal($record);$bindingKey=array_key_first($forged['bindings']);$forged['bindings'][$bindingKey]['record']=$record;
                $stepKey=LedgerState::key('step',[$store->instance,$d->policy['record_digest'],'found-augur']);$completion=$forged['steps'][$stepKey]['completion'];
                $completion['body']['result_refs']=[R::reference($record)];unset($completion['record_digest']);$forged['steps'][$stepKey]['completion']=R::seal($completion);
                try{LedgerState::validate($forged);self::fail('Rehashed holder and completion must not establish '.$field);}
                catch(\RuntimeException $e){self::assertSame(match($field){'generation'=>'O2_HOLDER_PUBLICATION','profile_ref'=>'O2_HOLDER_ARTIFACT','base_completion_ref'=>'O2_HOLDER_BASE_COMPLETION'},$e->getMessage());}
            }
            $after=$d->f->head();self::assertSame('HISTORICAL_RECOGNITION',$ledger->advance($d->f::json($q))['status']);self::assertSame($after,$d->f->head());
            self::assertSame(array_fill_keys(array_keys($result['operational_flags']),false),$result['operational_flags']);self::assertCount(1,$d->requests);
            // Native producers compete for the same physical root, even under a changed instance label.
            foreach(['instance-test','changed-instance'] as $instance){
                try{(new \App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService(new \App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService($d->f->root)))->install($instance);self::fail('Native installation must not reopen FRESH');}
                catch(\RuntimeException $e){self::assertSame('B225_FRESH_ROOT_OWNED',$e->getMessage());}
                try{(new \App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService($d->f->root))->seal($instance);self::fail('Native sealing must not supersede FRESH');}
                catch(\RuntimeException $e){self::assertSame('B225_FRESH_ROOT_OWNED',$e->getMessage());}
            }
            self::assertSame($after,$d->f->head());
            $q['command_id']='changed-command';$q['expected_head']=['generation'=>$after['generation'],'digest'=>'sha256:'.$after['digest']];$q['predecessor_ref']=$result['result_ref'];
            try{$ledger->advance($d->f::json($q));self::fail('Changed command must not republish');}catch(\RuntimeException $e){self::assertSame('O2_STEP_ALREADY_CONSUMED',$e->getMessage());}
            self::assertSame($after,$d->f->head());
        }finally{$f->close();}
    }
    public function testNativeInstallationAndSealPreventFreshWithoutChangingNativeOriginals():void
    {
        $f=new F();try{$d=$f->d;$store=$d->f->store;(new AugurMigration($store))->migrate($d->f->head());$f->ready();$d->advance('select-base');$d->advance('map-base');
            $native=(new \App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService(new \App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService($d->f->root)))->install('instance-test');
            self::assertNotEmpty($native['installations']);$ledger=new CommandLedger($store,$d->adapter,$d->adapter,$f->founding());
            for($i=0;$i<2;++$i){$head=$d->f->head();$files=[];$iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($d->f->root.'/var/imperium/operator-root',\FilesystemIterator::SKIP_DOTS));foreach($iterator as $file){$files[$file->getPathname()]=hash_file('sha256',$file->getPathname());}
                try{$ledger->advance($d->f::json($d->request('found-augur')));self::fail('Native state must exclude FRESH');}catch(\RuntimeException $e){self::assertSame('O2_ROOT_NOT_FRESH',$e->getMessage());}
                self::assertSame($head,$d->f->head());foreach($files as $path=>$hash){self::assertSame($hash,hash_file('sha256',$path));}
                if($i===0){(new \App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService($d->f->root))->seal('instance-test');}
            }
        }finally{$f->close();}
    }
}
