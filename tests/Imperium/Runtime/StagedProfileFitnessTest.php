<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationProfileFitness,ProfileFitnessContract as C,FormationInstitution,FormationOwnerFrame};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F,FreshDesignationFixture as D,NativeAssignmentProof};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class StagedProfileFitnessTest extends TestCase
{
    public static function seats(): iterable { foreach(C::SEATS as $seat) { yield $seat=>[$seat]; } }
    #[DataProvider('seats')]
    public function testActualCurrentActorsProduceSevenOriginalsAndTerminalWithdrawal(string $seat): void
    {
        $f=new F(); $d=null; $keys=[];
        try {
            $d=new D($f,$seat); $d->service->publish($d->envelope(),$d->m->candidate);
            NativeAssignmentProof::appoint($f,$d,1);
            $service=new FormationProfileFitness($f->store,$f->signatures,new FormationInstitution($f->root),$f->personnel);
            $policy=array_values($f->journal->read()['state']['onboarding']['policies'])[0]['record'];
            $contract=$service->prepare('fitness-'.str_replace('.','-',$seat),$d->m->candidate,$seat,R::reference($policy),$d->m->correspondence);
            $refs=[]; $originals=[]; $observations=[]; $delegations=[];
            foreach(C::OBLIGATIONS as $obligation) {
                $pair=sodium_crypto_sign_keypair(); $secret=sodium_crypto_sign_secretkey($pair); $keys[]=$secret;
                $terms=['domain'=>C::DOMAIN,'instance_id'=>$f->store->instance,'citadel_id'=>$f->store->citadel,
                    'actor'=>$f->personnel->authoritySource($obligation['producer_role']),'role'=>$obligation['producer_role'],
                    'public_key'=>base64_encode(sodium_crypto_sign_publickey($pair)),'seat'=>$seat,'profile_evidence_digest'=>$d->m->candidate['profile'],
                    'fitness_contract_ref'=>R::reference($contract),'not_before'=>$f->clock->at,'expires_at'=>$f->clock->at+180,'nonce'=>bin2hex(random_bytes(24))];
                $decision=$f->sign('DELEGATE_PROFILE_FITNESS',$terms);
                $delegation=$service->delegate(CanonicalJson::encode($contract),$d->m->candidate,$terms,$decision)['reference']; $delegations[]=$delegation;
                $exercise=null; $evidence=$contract['sources'];
                if($obligation['id']==='workload_practice') {
                    $state=$f->journal->read()['state'];
                    $exercise=$f->store->make(C::EXERCISE,'exercise-'.str_replace('.','-',$seat),[
                        'workload_ref'=>$contract['body']['workload_ref'],'profile_ref'=>C::nativeRef(\App\Imperium\Runtime\Citadel\Formation\FormationModelPreparation::EVIDENCE,$state['personnel_evidence'][$d->m->candidate['profile']]),
                        'binding_ref'=>$d->m->correspondence['binding_ref'],'duties'=>array_map(static fn(string $id):array=>[
                            'id'=>$id,'observed_return'=>'SYNTHETIC offline exercise: '.$id.'; no provider measurement or execution.','result'=>'PASS','evidence_refs'=>$contract['sources']],C::DUTIES),
                        'limitations'=>'SYNTHETIC semantic results. Public mechanics only; no measured capability, provider access or execution.'],$contract['sources']);
                    $evidence=R::refs([...$evidence,R::reference($exercise)]);
                }
                $p=['schema'=>C::JUDGMENT,'domain'=>C::DOMAIN,'delegation_ref'=>$delegation,'instance_id'=>$f->store->instance,'citadel_id'=>$f->store->citadel,
                    'seat'=>$seat,'profile_evidence_digest'=>$d->m->candidate['profile'],'fitness_contract_ref'=>R::reference($contract),
                    'obligation_id'=>$obligation['id'],'result'=>'PASS','evidence_refs'=>$evidence,
                    'rationale'=>'SYNTHETIC judgment for '.$seat.': '.$obligation['meaning'].' Exact originals are cited; semantic exercise results are simulated, not external facts.',
                    'issued_at'=>$f->clock->at,'expires_at'=>$f->clock->at+150,'nonce'=>bin2hex(random_bytes(24))];
                $sign=static fn(array $payload):array=>['payload'=>$payload,'signature'=>base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload),$secret))];
                $e=$sign($p);
                if($obligation['id']==='seat_duties') {
                    $bad=$p; $bad['obligation_id']='authority_limits'; $bad['nonce']=bin2hex(random_bytes(24));
                    $before=$f->head();
                    $badEnvelope=$sign($bad);
                    self::assertSame('PPC10_FITNESS_ROLE',NativeAssignmentProof::refusal(fn()=>$service->record(CanonicalJson::encode($badEnvelope))));
                    self::assertSame($before,$f->head());
                    $observations[]=['negative'=>'PPC10_FITNESS_ROLE','original'=>$badEnvelope,'before'=>$before,'after'=>$f->head(),'control'=>'valid seat_duties original retained below'];
                    $corrupt=$e; $corrupt['signature']=base64_encode(str_repeat("\0",64));
                    self::assertSame('PPC10_FITNESS_SIGNATURE',NativeAssignmentProof::refusal(fn()=>$service->record(CanonicalJson::encode($corrupt))));
                    self::assertSame($before,$f->head());
                    $observations[]=['negative'=>'PPC10_FITNESS_SIGNATURE','original'=>$corrupt,'raw_corruption'=>true,'before'=>$before,'after'=>$f->head(),'control'=>'valid seat_duties original retained below'];
                }
                if($exercise!==null) {
                    $bad=$exercise; $bad['body']['duties'][0]['result']='UNKNOWN'; unset($bad['record_digest']); $bad=R::seal($bad);
                    $bp=$p; $bp['evidence_refs']=R::refs([...$contract['sources'],R::reference($bad)]); $bp['nonce']=bin2hex(random_bytes(24));
                    $negative=$sign($bp);
                    $before=$f->head(); $code=NativeAssignmentProof::refusal(fn()=>$service->record(CanonicalJson::encode($negative),CanonicalJson::encode($bad)));
                    self::assertSame('PPC10_FITNESS_EXERCISE_NOT_PASS',$code); self::assertSame($before,$f->head());
                    $observations[]=['negative'=>$code,'original'=>$negative,'exercise'=>$bad,'before'=>$before,'after'=>$f->head(),'control'=>'valid practice original retained next'];
                }
                $wrong=$p; $wrong['seat']=$seat===C::SEATS[0]?C::SEATS[1]:C::SEATS[0];
                $wrongEnvelope=$sign($wrong);
                $before=$f->head(); $code=NativeAssignmentProof::refusal(fn()=>$service->record(CanonicalJson::encode($wrongEnvelope)));
                self::assertSame('PPC10_FITNESS_JUDGMENT_SCOPE',$code); self::assertSame($before,$f->head());
                $result=$service->record(CanonicalJson::encode($e),$exercise===null?null:CanonicalJson::encode($exercise));
                self::assertSame('JUDGMENT_RETAINED',$result['status']); $refs[]=$result['reference'];
                fwrite(STDERR,gmdate('c').' C2 '.$seat.' '.$obligation['id']." retained\n");
                $originals[]=['terms'=>$terms,'decision'=>$decision,'judgment'=>$e,'exercise'=>$exercise];
                $observations[]=['negative'=>$code,'original'=>$wrongEnvelope,'before'=>$before,'after_refusal'=>$before,'control'=>$result];
            }
            $verify=fn()=>$f->journal->inspect(fn(array $frame,FormationOwnerFrame $owner)=>$service->verifyInOwner($f->store,$owner,$contract,$d->m->candidate,$refs));
            $verify(); self::assertCount(7,$refs);
            $before=$f->head();
            $missing=array_slice($refs,1);
            self::assertSame('PPC10_FITNESS_COVERAGE',NativeAssignmentProof::refusal(fn()=>$f->journal->inspect(fn(array $frame,FormationOwnerFrame $owner)=>$service->verifyInOwner($f->store,$owner,$contract,$d->m->candidate,$missing))));
            self::assertSame($before,$f->head()); $verify();
            $other=new \App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore($f->journal,$f->clock,$f->store->instance,$f->store->citadel,$f->store->operator,$f->store->sourceCommit);
            self::assertSame('PPC10_FITNESS_FIXED_STORE',NativeAssignmentProof::refusal(fn()=>$f->journal->inspect(fn(array $frame,FormationOwnerFrame $owner)=>$service->verifyInOwner($other,$owner,$contract,$d->m->candidate,$refs))));
            $expired=$f->journal->inspect(fn(array $frame,FormationOwnerFrame $owner)=>$owner);
            self::assertSame('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED',NativeAssignmentProof::refusal(fn()=>$service->verifyInOwner($f->store,$expired,$contract,$d->m->candidate,$refs)));
            self::assertSame($before,$f->head());
            $before=$f->head(); $last=$originals[6];
            self::assertSame('HISTORICAL_RECOGNITION',$service->record(CanonicalJson::encode($last['judgment']))['status']); self::assertSame($before,$f->head());
            $terms=['delegation_digest'=>$delegations[0]['digest'],'expected_head'=>$f->head()]; $decision=$f->sign('REVOKE_PROFILE_FITNESS_DELEGATION',$terms);
            self::assertSame('DELEGATION_REVOKED',$service->revoke($terms,$decision)['status']);
            $before=$f->head(); self::assertSame('PPC10_FITNESS_DELEGATION_REVOKED',NativeAssignmentProof::refusal($verify)); self::assertSame($before,$f->head());
            self::assertSame('HISTORICAL_RECOGNITION',$service->revoke($terms,$decision)['status']); self::assertSame($before,$f->head());
            $path=getenv('PPC10_PUBLIC_EVIDENCE'); if(is_string($path) && $path!=='') {
                if(!is_dir($path)) { mkdir($path,0700,true); }
                file_put_contents($path.'/fitness-'.str_replace('.','-',$seat).'.json',json_encode(['schema'=>'imperium.ppc10-fitness-observation/v1','seat'=>$seat,
                    'contract'=>$contract,'originals'=>$originals,'observations'=>$observations,'withdrawal'=>['terms'=>$terms,'decision'=>$decision],
                    'formation_public_key'=>$f->journal->read()['state']['trust']['public_key'],
                    'head'=>$f->head(),'assignment_applied'=>false,'protected_delivery'=>false,'external_semantics'=>'SYNTHETIC'],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
                $old=getenv('PPC9_PUBLIC_EVIDENCE'); putenv('PPC9_PUBLIC_EVIDENCE='.$path);
                try { NativeAssignmentProof::exportCustody($f,'fitness-'.str_replace('.','-',$seat)); }
                finally { $old===false?putenv('PPC9_PUBLIC_EVIDENCE'):putenv('PPC9_PUBLIC_EVIDENCE='.$old); }
            }
        } finally { foreach($keys as &$key) { sodium_memzero($key); } unset($key); $d?->close(); $f->close(); }
    }
}
