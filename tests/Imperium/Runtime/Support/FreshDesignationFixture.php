<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationProfileDesignation as D,FormationJournal as J,FormationModelPreparation as M,FormationInstitution,FormationProfileDesignationInitialization,FormationOwnerFrame};

/** Public synthetic acts through supported native producers; private keys stay in memory. */
final class FreshDesignationFixture
{
    public FreshModelPreparationFixture $m;
    public D $service;
    public array $delegations=[];
    private string $secret;
    public function __construct(FreshEstablishmentFixture $combined, public string $seat)
    {
        $m=$this->m=new FreshModelPreparationFixture($combined, $seat); $f=$m->f;
        $this->service=new D($f->journal,$f->signatures,$f->clock,new FormationInstitution($f->root),$f->personnel);
        $head=$m->head(); $terms=['schema'=>FormationProfileDesignationInitialization::SCHEMA,'citadel_id'=>$m->context['citadel_id'],'expected_head'=>$head];
        if (!isset($f->journal->read()['state']['profile_designations'])) { (new \App\Imperium\Runtime\Citadel\Formation\FreshInstitutionalPreparation($f->root,$f->store))->initializeDesignations($terms,$f->sign('INITIALIZE_FORMATION_PROFILE_DESIGNATIONS',$terms)); }
        $pair=sodium_crypto_sign_keypair(); $this->secret=sodium_crypto_sign_secretkey($pair);
        foreach([D::DESIGNATE,D::REVOKE] as $purpose) {
            $terms=['schema'=>D::DELEGATION,'instance_id'=>$m->context['instance_id'],'citadel_id'=>$m->context['citadel_id'],
                'steward'=>$m->context['steward'],'target'=>$seat,'purpose'=>$purpose,'actor'=>$f->personnel->authoritySource('laboratorium'),
                'public_key'=>base64_encode(sodium_crypto_sign_publickey($pair)),'not_before'=>$f->clock->at,'expires_at'=>$f->clock->at+900,'expected_head'=>$m->head()];
            $this->delegations[$purpose]=$this->service->delegate($terms,$f->sign('DELEGATE_FORMATION_PROFILE_DESIGNATION',$terms));
        }
    }
    public function envelope(?array $previous=null,string $effect=D::DESIGNATE,int $life=200):array
    {
        $m=$this->m; $state=$m->f->journal->read()['state']; $c=$m->candidate;
        $p=['schema'=>D::ACT,'effect'=>$effect,'instance_id'=>$m->context['instance_id'],'citadel_id'=>$m->context['citadel_id'],
            'steward'=>$m->context['steward'],'target'=>$this->seat,'delegation_ref'=>$this->delegations[$effect],
            'actor'=>$m->f->personnel->authoritySource('laboratorium'),'expected_head'=>$m->head(),
            'predecessor_ref'=>$previous===null?null:D::eventRef($previous),'profile_ref'=>array_intersect_key($m->artifact,array_flip(['profile_id','profile_version','content_digest']))];
        foreach(['profile_evidence_ref'=>'profile','examination_ref'=>'examination','qualification_ref'=>'qualification'] as $ref=>$field) {
            $e=$state['personnel_evidence'][$c[$field]]; $p[$ref]=D::ref($e['payload']['schema']??'imperium.formation-personnel-evidence/v1',$c[$field],$e);
        }
        $p['approval_ref']=D::ref('imperium.citadel-owner-decision/v1',J::digest($c['profile_approval']),$c['profile_approval']);
        $p+=['model_binding'=>$m->correspondence,'designation_generation'=>$previous===null?1:$previous['envelope']['payload']['designation_generation']+1,
            'binding_generation'=>$m->correspondence['binding_generation'],'not_before'=>$m->f->clock->at,'issued_at'=>$m->f->clock->at,
            'expires_at'=>$m->f->clock->at+$life,'nonce'=>bin2hex(random_bytes(24)),'correlation_id'=>'ppc6-offline','reason'=>'Disposable designation lifecycle proof'];
        return $this->sign($p);
    }
    public function sign(array $p):array { return ['payload'=>$p,'signature'=>base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($p),$this->secret))]; }
    public function current():array { return $this->m->f->journal->inspect(fn(array $frame,FormationOwnerFrame $owner):array=>$this->service->currentInOwner($owner,$this->seat)); }
    public function successor():void
    {
        $m=$this->m; $t=$m->authorizationTerms;
        $t['id']='successor-'.bin2hex(random_bytes(8)); $t['source_profile']=$m->artifact; $t['source_evidence']=$m->preparation->sourceOriginal($m->candidate['profile']);
        $t['expected_head']=$m->head(); $t['nonce']=bin2hex(random_bytes(24));
        $m->authorization=$m->preparation->authorize($t,$m->ownerSign($t));
        $m->sealingEnvelope=$m->sealerSign($m->preparation->sealingPayload(M::reference($m->authorization),$m->head(),bin2hex(random_bytes(24))));
        $m->finish();
    }
    public function close():void { sodium_memzero($this->secret); $this->m->close(); }
}
