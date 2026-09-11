<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
require_once __DIR__.'/CitadelFormationFixture.php';require_once __DIR__.'/FormationCustodyFixture.php';require_once __DIR__.'/OnboardingLedgerFixture.php';require_once dirname(__DIR__).'/ProviderOnboardingSharedExposureTest.php';
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal,FormationSignatures,FormationPersonnel,FormationSessionAuthority,FormationCognition,CustodiedFormationTransport};
use App\Imperium\Runtime\Clavium\{FormationSessionLeaseService,FormationClaimCustodyBroker,ProviderResponseEnvelopeService};
use App\Tests\Imperium\Runtime\Support\{SyntheticFormationClock,FormationCustodyFixture,CountingOnboardingPorts};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,CustodyCoordinator};
[$script,$root,$kind]=$argv;$input=json_decode(file_get_contents($root.'/b1-cross-input.json'),true,512,JSON_THROW_ON_ERROR);$clock=new SyntheticFormationClock();$clock->at=$input['now'];
if($kind==='formation'){
    $c=new FormationCustodyFixture($root,$clock);$wire=new App\Tests\Imperium\Runtime\SmallSharedFormationWire($c->wire);$journal=$c->journal;
    $broker=new FormationClaimCustodyBroker($journal,$c->container->get(FormationSessionAuthority::class),$c->container->get(FormationSessionLeaseService::class),$c->credentials,$wire,$c->container->get(ProviderResponseEnvelopeService::class),$clock);
    $runtime=new FormationCognition($journal,$c->container->get(FormationSignatures::class),$c->container->get(FormationPersonnel::class),new CustodiedFormationTransport($wire,$broker),$c->container->get(ProviderResponseEnvelopeService::class),$c->container->get(FormationSessionLeaseService::class),$clock,new App\Imperium\Runtime\Curia\ReceivingFormationHandoffService($root,$journal));
}else{$ports=new CountingOnboardingPorts($root,$input['operation']);$store=new AuthorityStore($root,$clock,'instance-test',$input['citadel'],'operator-test',str_repeat('a',40));$runtime=new CustodyCoordinator(new CommandLedger($store,$ports,$ports),$ports,$ports,$ports);}
file_put_contents($root.'/cross-ready-'.$kind,'ready');$deadline=microtime(true)+40;while(!is_file($root.'/cross-go')){if(microtime(true)>$deadline){exit(74);}usleep(10000);}
try{if($kind==='formation'){$runtime->call($input['session_id'],'cross-formation-attempt');}else{$runtime->advance(json_encode($input['request']));}exit(0);}catch(\Throwable $e){echo $e->getMessage();exit(2);}
