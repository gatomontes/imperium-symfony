<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\AugurCognitionFixture as F;
use App\Imperium\Runtime\Onboarding\Assignment\{AssignmentMigration,AssessmentResolver};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;
use PHPUnit\Framework\TestCase;

final class AssignmentMigrationTest extends TestCase
{
    public function testEarlierMigrationOwnersRemainIdempotentAndCannotDowngradeV4():void
    {
        $f=new \App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture();
        try {
            $f->enroll();
            $v2=(new \App\Imperium\Runtime\Onboarding\Ledger\StateMigration($f->store))->migrate($f->head());
            $v3=(new \App\Imperium\Runtime\Onboarding\Augur\AugurMigration($f->store))->migrate($f->head());
            $v4=(new AssignmentMigration($f->store))->migrate($f->head());
            $before=$f->store->journal->read();$f->now+=20000;
            $stale=['generation'=>0,'digest'=>null];
            self::assertEquals($v2,(new \App\Imperium\Runtime\Onboarding\Ledger\StateMigration($f->store))->migrate($stale));
            self::assertEquals($v3,(new \App\Imperium\Runtime\Onboarding\Augur\AugurMigration($f->store))->migrate($stale));
            self::assertEquals($v4,(new AssignmentMigration($f->store))->migrate($stale));
            self::assertSame($before,$f->store->journal->read());
        } finally {$f->close();}
    }

    public function testExplicitMigrationPreservesProducerHistoryAndRejectsForgedPreservation():void
    {
        $f=new F();
        try {
            $f->ready();$d=$f->fresh->d;$store=$d->f->store;
            foreach(['w1.attempt.0','w2.attempt.0','w3.attempt.0'] as $step){$d->advance($step);}
            $before=$store->journal->read();$prior=$before['state']['onboarding'];$calls=$f->requests;
            $migration=new AssignmentMigration($store);
            $this->refused(fn()=>$migration->migrate(['generation'=>0,'digest'=>null]));
            self::assertSame($before,$store->journal->read());
            $record=$migration->migrate($d->f->head());$after=$store->journal->read();
            self::assertEquals($prior,$record['body']['prior_state']);
            self::assertSame(R::hash($prior),$record['body']['prior_digest']);
            $restored=$after['state']['onboarding'];unset($restored['assignment_migration']);
            $restored['schema']='imperium.onboarding-authority-state/v3';
            self::assertEquals($prior,$restored);
            self::assertEquals($record,$migration->migrate(['generation'=>0,'digest'=>null]));
            self::assertSame($after,$store->journal->read());
            $result=(new AssessmentResolver($store,$f->adapter))->resolve(R::reference($d->policy));
            self::assertCount(3,$result['groups']);self::assertSame($calls,$f->requests);
            self::assertSame($after,$store->journal->read());
            $s=$after['state']['onboarding'];
            foreach(['acts','policies','evidence','admissions','commands','slots','claims'] as $map){
                $bad=$s;unset($bad[$map][array_key_first($bad[$map])]);
                $this->refused(fn()=>LedgerState::validate($bad));
            }
            $bad=$s;$bad['schema']='imperium.onboarding-authority-state/v99';
            $this->refused(fn()=>LedgerState::validate($bad));
            $bad=$s;$h=$bad['assignment_migration'];unset($h['record_digest']);
            $h['body']['prior_digest']='sha256:'.str_repeat('0',64);$bad['assignment_migration']=R::seal($h);
            $this->refused(fn()=>LedgerState::validate($bad));
            self::assertSame($after,$store->journal->read());
            $process=proc_open([PHP_BINARY,'-r',
                'require $argv[1]; $f=(new App\\Imperium\\Runtime\\Citadel\\Formation\\FormationJournal($argv[2]))->read(); '
                .'App\\Imperium\\Runtime\\Onboarding\\Ledger\\LedgerState::validate($f["state"]["onboarding"]); '
                .'echo App\\Imperium\\Runtime\\Onboarding\\AuthorityAdmission\\Rules::hash($f["state"]["onboarding"]);',
                dirname(__DIR__,3).'/vendor/autoload.php',$d->f->root],
                [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
            self::assertIsResource($process);fclose($pipes[0]);
            stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
            $output='';$error='';$deadline=microtime(true)+120;
            do {
                $output.=stream_get_contents($pipes[1]);$error.=stream_get_contents($pipes[2]);
                $status=proc_get_status($process);
                if(!$status['running']){break;}
                if(microtime(true)>$deadline){proc_terminate($process);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);self::fail('Fresh-process migration read exceeded 120 seconds');}
                usleep(10000);
            } while(true);
            $output.=stream_get_contents($pipes[1]);$error.=stream_get_contents($pipes[2]);
            fclose($pipes[1]);fclose($pipes[2]);
            proc_close($process);self::assertSame(0,$status['exitcode'],$error);
            self::assertSame(R::hash($s),$output);
            self::assertSame($after,$store->journal->read());
        } finally {$f->close();}
    }

    private function refused(callable $action):void
    {
        try{$action();self::fail('Expected migration/history refusal');}
        catch(\RuntimeException|\InvalidArgumentException $e){self::assertNotSame('',$e->getMessage());}
    }
}
