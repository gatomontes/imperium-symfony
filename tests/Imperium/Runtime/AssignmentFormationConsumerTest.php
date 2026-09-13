<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{AssignmentConsumerFixture as F,AssignmentConsumerTransport,AssignmentConsumerCredentials};
use App\Imperium\Runtime\Onboarding\Assignment\{SettingsBoundTransport,SettingsPreparedTransport,FormationSettingsBinding};
use App\Imperium\Runtime\Citadel\Formation\{FormationCognition,FormationSessionAuthority,CustodiedFormationTransport,FormationJournal as J};
use App\Imperium\Runtime\Clavium\{FormationClaimCustodyBroker,FormationSessionLeaseService,ProviderResponseEnvelopeService};
use App\Imperium\Runtime\Curia\ReceivingFormationHandoffService;
use PHPUnit\Framework\TestCase;

final class AssignmentFormationConsumerTest extends TestCase
{
    private function runtime(F $f,AssignmentConsumerTransport $wire,bool $prepared,?AssignmentConsumerCredentials $credentials=null,?object $formation=null):FormationCognition
    {
        $g=$formation??$f->formation;$responses=$g->container->get(ProviderResponseEnvelopeService::class);$leases=$g->container->get(FormationSessionLeaseService::class);
        if($prepared){
            $broker=new FormationClaimCustodyBroker($g->journal,new FormationSessionAuthority($g->signatures,$g->personnel,$g->clock),$leases,
                $credentials??new AssignmentConsumerCredentials(),$wire,$responses,$g->clock,new FormationSettingsBinding($f->settings(),'courtyard.courtthane'));
            $transport=new SettingsPreparedTransport($f->settings(),new CustodiedFormationTransport($wire,$broker),'courtyard.courtthane');
        }else{$transport=new SettingsBoundTransport($f->settings(),$wire,'courtyard.courtthane');}
        return new FormationCognition($g->journal,$g->signatures,$g->personnel,$transport,$responses,$leases,$g->clock,new ReceivingFormationHandoffService($g->root,$g->journal));
    }
    private function applied(F $f):void
    {$f->assessed();$d=$f->fresh->d;$f->ledger()->advance($d->f::json($d->request('apply-assignments')));}
    private function refuses(callable $action,string $message):void
    {
        try{$action();self::fail('Expected '.$message);}catch(\RuntimeException|\InvalidArgumentException $e){self::assertStringContainsString($message,$e->getMessage());}
    }
    public function testRealOwnerOrdinaryAndCustodiedExecutionExactConfigurationRecoveryAndNegatives():void
    {
        $f=new F();try{
            $this->applied($f);$g=$f->formation;$wire=new AssignmentConsumerTransport();$credentials=new AssignmentConsumerCredentials();
            self::assertTrue($g->journal->sameOwner($f->fresh->d->f->store->journal));
            self::assertSame([],glob($g->root.'/synthetic-parent-institution/var/imperium/citadel/formation/*.json'));
            $before=$g->journal->read()['state']['onboarding'];$calls=$f->requests;
            $foreign=new \App\Tests\Imperium\Runtime\Support\CitadelFormationFixture();try{
                $foreign->appoint();$tuple=$f->settings()->resolve('courtyard.courtthane');
                foreach([false,true] as $prepared){
                    $id=$foreign->receive('Foreign aggregate request','foreign-aggregate-request-'.(int)$prepared)['intake_id'];
                    $terms=$foreign->terms($id,'interview');$terms['provider']=$tuple['provider'];$terms['model']=$tuple['model_id'];$terms['model_settings']=$tuple;
                    if($prepared){$terms['transport']=\App\Tests\Imperium\Runtime\Support\FormationCustodyFixture::authorization();}
                    $sid=$foreign->grant($id,'interview',$terms);$head=$foreign->journal->read();
                    $this->refuses(fn()=>$this->runtime($f,$wire,$prepared,$credentials,$foreign)->call($sid,'foreign-aggregate-attempt'),'SETTINGS_FOREIGN_AGGREGATE');
                    self::assertSame($head,$foreign->journal->read());self::assertSame([],$wire->calls);
                }
            }finally{$foreign->close();}
            foreach(['ordinary'=>false,'prepared'=>true] as $name=>$prepared){
                $runtime=$this->runtime($f,$wire,$prepared,$credentials);$sid=$f->sessions[$name];
                $wire->configuration['temperature']=1;$head=$g->journal->read();$dispatches=count($wire->calls);
                $this->refuses(fn()=>$runtime->call($sid,'altered-config-'.$name),'SETTINGS_EFFECTIVE_CONFIGURATION');
                self::assertSame($head,$g->journal->read());self::assertCount($dispatches,$wire->calls);
                $wire->configuration['temperature']=0;
                $record=$runtime->call($sid,'positive-attempt-'.$name);
                self::assertCount($dispatches+1,$wire->calls);self::assertFalse($record['execution_authority']);
                self::assertSame(J::digest($wire->configuration),J::digest($wire->calls[$dispatches]['configuration']));
                self::assertSame(J::digest($g->journal->read()['state']['courtthane']),J::digest($wire->calls[$dispatches]['request']['holder']));
                // Recompose actual owners; recognition never dispatches again.
                self::assertSame($record,$this->runtime($f,$wire,$prepared,$credentials)->call($sid,'positive-attempt-'.$name));
                self::assertCount($dispatches+1,$wire->calls);
                if($prepared){self::assertSame('RESPONSE_RETAINED',$g->journal->read()['state']['sessions'][$sid]['attempts']['positive-attempt-'.$name]['custody']['status']);}
            }
            self::assertSame(1,$credentials->issues);self::assertSame(1,$credentials->consumptions);
            self::assertSame($before,$g->journal->read()['state']['onboarding']);self::assertSame($calls,$f->requests);
            // An unassociated session still has no shared-budget rights in v4.
            $id=$g->receive('Unassociated bounded request','unassociated-request')['intake_id'];
            $terms=$g->terms($id,'interview');$tuple=$f->settings()->resolve('courtyard.courtthane');
            $terms['provider']=$tuple['provider'];$terms['model']=$tuple['model_id'];$terms['model_settings']=$tuple;
            $sid=$g->grant($id,'interview',$terms);$head=$g->journal->read();
            $this->refuses(fn()=>$this->runtime($f,$wire,false)->call($sid,'unassociated-attempt'),'SHARED_BUDGET_SOURCE_UNRESOLVED');
            self::assertSame($head,$g->journal->read());self::assertCount(2,$wire->calls);
            // A real authorized replacement makes the independently signed old tuple stale.
            $q=$f->replacementRequest();$f->ledger()->replace($f->fresh->d->f::json($q));$head=$g->journal->read();
            foreach([false,true] as $prepared){$this->refuses(fn()=>$this->runtime($f,$wire,$prepared)->call($f->sessions[$prepared?'stale-prepared':'stale'],'stale-attempt-'.(int)$prepared),'SETTINGS_TRANSPORT_GENERATION');}
            self::assertSame($head,$g->journal->read());self::assertCount(2,$wire->calls);
            // Actual newly qualified and appointed successor, with fresh signed sessions.
            $g->appoint('successor');$tuple=$f->settings()->resolve('courtyard.courtthane');
            foreach([false,true] as $prepared){
                $id=$g->receive('Wrong executing Profile','wrong-profile-request-'.(int)$prepared)['intake_id'];$terms=$g->terms($id,'interview');
                $terms['provider']=$tuple['provider'];$terms['model']=$tuple['model_id'];$terms['model_settings']=$tuple;
                if($prepared){$terms['transport']=\App\Tests\Imperium\Runtime\Support\FormationCustodyFixture::authorization();}
                $sid=$g->grant($id,'interview',$terms);$head=$g->journal->read();
                $this->refuses(fn()=>$this->runtime($f,$wire,$prepared)->call($sid,'wrong-profile-attempt'),'SETTINGS_EXECUTING_PROFILE');
                self::assertSame($head,$g->journal->read());self::assertCount(2,$wire->calls);
            }
            self::assertSame($calls,$f->requests);
        }finally{$f->close();}
    }
    public function testSameAggregateForeignInstanceRefusesBothRoutes():void
    {
        $f=new F(instance:'different-native-instance');try{
            $this->applied($f);$wire=new AssignmentConsumerTransport();$head=$f->formation->journal->read();
            foreach(['ordinary'=>false,'prepared'=>true] as $name=>$prepared){$this->refuses(fn()=>$this->runtime($f,$wire,$prepared)->call($f->sessions[$name],'foreign-instance-attempt'),'SETTINGS_FOREIGN_INSTANCE');}
            self::assertSame($head,$f->formation->journal->read());self::assertSame([],$wire->calls);
        }finally{$f->close();}
    }
    public function testCustodyRechecksCurrentSettingsAfterCredentialConsumptionBoundary():void
    {
        $f=new F();try{
            $this->applied($f);$wire=new AssignmentConsumerTransport();$credentials=new AssignmentConsumerCredentials();
            $before=$f->formation->journal->read()['state']['onboarding'];$calls=$f->requests;
            $credentials->onConsume=static function()use($f):void{$f->available=false;};
            $runtime=$this->runtime($f,$wire,true,$credentials);
            $this->refuses(fn()=>$runtime->call($f->sessions['custody-race'],'custody-currentness-attempt'),'CMF059_OUTCOME_UNKNOWN_NO_RETRY');
            self::assertSame(1,$credentials->issues);self::assertSame(1,$credentials->consumptions);self::assertSame([],$wire->calls);
            self::assertSame($before,$f->formation->journal->read()['state']['onboarding']);self::assertSame($calls,$f->requests);
            $attempt=$f->formation->journal->read()['state']['sessions'][$f->sessions['custody-race']]['attempts']['custody-currentness-attempt'];
            self::assertSame('CONSUMPTION_COMMITTED_OUTCOME_UNCERTAIN',$attempt['custody']['status']);self::assertFalse($attempt['claim']['automatic_retry_permitted']);
        }finally{$f->close();}
    }
}
