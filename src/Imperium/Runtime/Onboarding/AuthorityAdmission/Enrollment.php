<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;
use App\Imperium\Runtime\Onboarding\Selection\Shape;

/** Separate deployment-administrator API, never called from signed act ingress. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Enrollment
{
    public function __construct(private AuthorityStore $store) {}
    public function enroll(string $receiptJson,string $confirmedFingerprint,array $expectedHead): array {
        $h=Rules::record(StrictJson::decode($receiptJson)); $this->store->identity($h);
        Rules::require($h['schema']==='imperium.bootstrap-enrollment/v1' && $h['sources']===[],'ENROLLMENT_SCHEMA');
        $b=Rules::object($h['body'],['public_key','fingerprint','issuer','competence','effects','not_before','expires_at','custody_receipt']);
        Rules::text($b['custody_receipt']); Rules::require(Rules::same($b['issuer'],['kind'=>'operator','id'=>$this->store->operator]) && $b['competence']==='OPERATOR_BOOTSTRAP_POLICY','ENROLLMENT_COMPETENCE');
        Rules::digest($confirmedFingerprint); $key=Rules::bytes($b['public_key'],32);
        Rules::require($b['fingerprint']===$confirmedFingerprint && $confirmedFingerprint==='sha256:'.hash('sha256',$key),'ENROLLMENT_FINGERPRINT');
        $seen=[]; foreach (Shape::list($b['effects'],true) as $effect) { Rules::effect($effect,'signed_act'); Rules::supported($effect); Rules::require(!isset($seen[$effect]),'DUPLICATE_EFFECT'); $seen[$effect]=true; }
        Rules::require(isset($seen['AUTHORIZE_BOOTSTRAP_POLICY'],$seen['REVOKE_BOOTSTRAP']),'ENROLLMENT_EFFECTS');
        Rules::time($b['not_before']); Rules::time($b['expires_at']); Rules::head($expectedHead);
        return $this->store->journal->changeAtHead(function(array &$state,array $head) use($h,$b,$receiptJson,$expectedHead):array {
            Rules::require(!isset($state['onboarding']),'ALREADY_ENROLLED');
            Rules::require(!isset($state['citadel_id']) || $state['citadel_id']===$this->store->citadel,'CITADEL_IDENTITY');
            Rules::require(Rules::same($head,$expectedHead),'STALE_HEAD'); $now=$this->store->now();
            Rules::require($h['created_at']<=$now && $b['not_before']<=$now && $now<$b['expires_at'],'ENROLLMENT_TIME');
            $trustBody=$b; unset($trustBody['custody_receipt']); $trustBody['enrollment_receipt_ref']=Rules::reference($h);
            $trust=$this->store->make('imperium.bootstrap-trust/v1','trust-'.substr($b['fingerprint'],7,48),$trustBody,[Rules::reference($h)]);
            $state['citadel_id']=$this->store->citadel;
            $state['onboarding']=['schema'=>'imperium.onboarding-authority-state/v1','trust'=>$trust,'acts'=>[],'policies'=>[],
                'evidence'=>[Rules::key(Rules::reference($h))=>['record'=>$h,'raw'=>$receiptJson,'admission_key'=>null]],'revocations'=>[],'admissions'=>[]];
            return $trust;
        });
    }
}
