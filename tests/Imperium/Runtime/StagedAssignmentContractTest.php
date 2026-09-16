<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\ProfileFitnessContract as C;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{StagedAct,Act,Admission,Rules as R};
use App\Tests\Imperium\Runtime\Support\{OnboardingAuthorityFixture as F,NativeAssignmentProof};
use PHPUnit\Framework\TestCase;

final class StagedAssignmentContractTest extends TestCase
{
    public function testExactNewDomainCannotEnterV1AndAuthenticatesFoundingPolicy(): void
    {
        $f=new F();
        try {
            $f->enroll(); $policy=$f->policy(); $f->admitPolicy($policy);
            $scope=$f->store->make('imperium.staged-assignment-scope/v1','staged-shape-probe',['probe'=>'This is an act authentication object, not a decoded/admitted scope.']);
            $e=$f->sign($scope,'AUTHORIZE_BOOTSTRAP_POLICY'); $p=$e['payload'];
            $p['schema']=StagedAct::SCHEMA; $p['domain']=StagedAct::DOMAIN; $p['policy_ref']=R::reference($policy);
            $e=['payload'=>$p,'signature'=>base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($p),$f->secret))];
            $s=$f->store->state($f->store->journal->read()['state']); $before=$f->head();
            self::assertSame($p,StagedAct::verify($f->store,$s,$e,$scope));
            self::assertSame('O2_ACT_DOMAIN',NativeAssignmentProof::refusal(fn()=>Act::shape($e)));
            self::assertSame('O2_ACT_DOMAIN',NativeAssignmentProof::refusal(fn()=>(new Admission($f->store))->retain(CanonicalJson::encode($e),CanonicalJson::encode($scope))));
            $old=$f->sign($scope,'AUTHORIZE_BOOTSTRAP_POLICY');
            self::assertSame('O2_STAGED_ACT_DOMAIN',NativeAssignmentProof::refusal(fn()=>StagedAct::shape($old)));
            $bad=$e; $bad['signature']=base64_encode(str_repeat("\0",64));
            self::assertSame('O2_STAGED_SIGNATURE',NativeAssignmentProof::refusal(fn()=>StagedAct::verify($f->store,$s,$bad,$scope)));
            self::assertSame($before,$f->head()); self::assertSame($p,StagedAct::verify($f->store,$s,$e,$scope));
        } finally { $f->close(); }
    }
    public function testCompiledObligationsMatchApprovedProposalBytes(): void
    {
        $bytes=file_get_contents(dirname(__DIR__,3).'/docs/provider-staged-assignment-proposal-v1.md');
        self::assertSame('bd817ea572c259874887375389ed3ecea0972ecf6ff0bb22b1cf72e75d06844d',hash('sha256',$bytes));
        self::assertCount(7,C::OBLIGATIONS); self::assertCount(8,C::DUTIES);
        foreach(C::OBLIGATIONS as $obligation) { self::assertStringContainsString($obligation['meaning'],$bytes); }
    }
    public function testFitnessByteAndRationaleBoundsAreExact(): void
    {
        C::text(str_repeat('x',8192)); self::assertTrue(true);
        self::assertSame('PPC10_FITNESS_TEXT',NativeAssignmentProof::refusal(fn()=>C::text(str_repeat('x',8193))));
        $value=['x'=>str_repeat('x',262136)]; self::assertSame(262144,strlen(CanonicalJson::encode($value))); C::bounded($value);
        $value['x'].='x'; self::assertSame('PPC10_FITNESS_BYTE_LIMIT',NativeAssignmentProof::refusal(fn()=>C::bounded($value)));
    }
    public function testPureScopeDecoderKeepsOriginalPolicyAndClosedGenerations(): void
    {
        $f=new F();
        try {
            $policy=$f->policy();
            foreach($policy['body']['effect_slots'] as &$slot) {
                if($slot['effect']==='APPLY_BOOTSTRAP_ASSIGNMENTS') {
                    $slot['terms_rule']['expected_assignments_ref']=$policy['body']['expected_assignments'];
                    $slot['terms_rule']['required_predicates_ref']=$policy['body']['requirements_ref'];
                }
            } unset($slot,$policy['record_digest']); $policy=R::seal($policy);
            $original=CanonicalJson::encode($policy);
            $ref=static fn(string $schema,string $id):array=>['schema'=>$schema,'id'=>$id,'digest'=>'sha256:'.hash('sha256',$id)];
            $targets=[]; $pair=[];
            foreach($policy['body']['targets'] as $i=>$target) {
                $fitness=[]; for($n=0;$n<7;++$n) { $fitness[]=$ref(C::JUDGMENT,'judgment-'.$i.'-'.$n); }
                $targets[]=['role'=>$target['role'],'profile_ref'=>$target['profile_ref'],'permitted_bindings'=>R::refs($target['permitted_bindings']),
                    'profile_generation'=>1,'fitness_contract_ref'=>$ref(C::CONTRACT,'contract-'.$i),'fitness_evidence_refs'=>R::refs($fitness)];
                $b=$policy['body']['candidate_bindings'][$i];
                $pair[]=['role'=>$target['role'],'provider'=>$b['provider'],'model_id'=>$b['model_id'],'model_version'=>$b['model_version'],
                    'binding_ref'=>$b['binding_ref'],'configuration_ref'=>$b['configuration_ref'],'profile_ref'=>$target['profile_ref'],'profile_generation'=>1,'binding_generation'=>1];
            }
            $load=fn(array $r):array=>$f->sources[R::key($r)]; $common=$policy['body']['workload_ref']; $commissions=[];
            foreach(['W1','W2','W3'] as $i=>$group) {
                $commissions[]=['group_id'=>$group,'constitution_ref'=>$common,'profile_ref'=>$i===0?$common:$targets[$i-1]['profile_ref'],
                    'account_scope'=>'synthetic','account_ref'=>$common,'token_ref'=>$common,'tariff_ref'=>$common,'evidence_refs'=>[$common],
                    'not_before'=>$f->now,'expires_at'=>$f->now+100];
            }
            $scope=$f->store->make(\App\Imperium\Runtime\Onboarding\Assignment\StagedScope::SCHEMA,'synthetic-scope-shape',[
                'scope_version'=>'ppc10-staged-v1','founding_policy_ref'=>R::reference($policy),
                'founding_completion_ref'=>$ref('imperium.bootstrap-step-completion/v1','founding-completion'),
                'establishment_completion_ref'=>['schema'=>'imperium.fresh-institutional-completion/v1','id'=>'synthetic-completion','digest'=>str_repeat('a',64)],
                'augur_holder_ref'=>$ref('imperium.bootstrap-augur-holder/v1','original-holder'),'predecessor_scope_ref'=>null,'expected_application_ref'=>null,
                'expected_assignments'=>\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Policy::content($load($policy['body']['expected_assignments']),'expected-assignments'),
                'stage_generation'=>1,'targets'=>$targets,'permitted_sets'=>[$pair],'requirements_ref'=>$policy['body']['requirements_ref'],
                'selection_rule_ref'=>\App\Imperium\Runtime\Onboarding\Assignment\AssignmentRule::slot($policy)['terms_rule']['selection_rule_ref'],
                'workload_ref'=>$common,'candidate_bindings'=>$policy['body']['candidate_bindings'],'assessment_commissions'=>$commissions,
                'application_mode'=>$policy['body']['application_mode'],'not_before'=>$f->now,'expires_at'=>$f->now+100]);
            self::assertSame($scope,\App\Imperium\Runtime\Onboarding\Assignment\StagedScope::decode($scope,$policy,$load));
            $bad=$scope; $bad['body']['stage_generation']=5; unset($bad['record_digest']); $bad=R::seal($bad);
            self::assertSame('O2_STAGED_GENERATION',NativeAssignmentProof::refusal(fn()=>\App\Imperium\Runtime\Onboarding\Assignment\StagedScope::decode($bad,$policy,$load)));
            $bad=$scope; $bad['body']['permitted_sets'][]=$pair; unset($bad['record_digest']); $bad=R::seal($bad);
            self::assertSame('O2_STAGED_DUPLICATE_PAIR',NativeAssignmentProof::refusal(fn()=>\App\Imperium\Runtime\Onboarding\Assignment\StagedScope::decode($bad,$policy,$load)));
            self::assertSame($original,CanonicalJson::encode($policy));
        } finally { $f->close(); }
    }
}

