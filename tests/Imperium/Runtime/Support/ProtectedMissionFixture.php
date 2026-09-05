<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\ProtectedMission\AuthorityOwner;
use App\ProtectedMission\PublicTrust;
use App\Imperium\Runtime\Curia\{ProceedingStore,PlanningDossierAssemblyService,ImperatorPlanningDossierReviewService,MissionAuthorizationDerivationService};

/** Batch 2 storage fixture, not an enrollment/approval handoff or ceremony proof. */
final class ProtectedMissionFixture
{
    public string $root;
    public string $secret;
    public array $trust;
    public array $state;
    public string $id;

    public function __construct()
    {
        $this->root=sys_get_temp_dir().'/imperium-protected-fixture-'.bin2hex(random_bytes(12));
        $pair=sodium_crypto_sign_keypair(); $this->secret=sodium_crypto_sign_secretkey($pair); $public=sodium_crypto_sign_publickey($pair); sodium_memzero($pair);
        $this->trust=PublicTrust::validate(['identity'=>'disposable-operator','competence'=>PublicTrust::COMPETENCE,'public_key'=>base64_encode($public),'not_before'=>time()-5,'expires_at'=>time()+3600],hash('sha256',$public));
        (new AuthorityOwner($this->root))->enroll($this->trust,$this->trust['fingerprint']);
        $store=new ProceedingStore($this->root);
        $store->persist(['proceeding_id'=>'disposable-proceeding','instance_id'=>'disposable-test']);
        $store->appendTurn('disposable-proceeding','test-response',1,['seneschal'=>['disposition'=>'MISSION_PLAN_DRAFTED','mission_plan'=>['objective'=>'Disposable authority proof; no actual target access.']]]);
        $disclosures=array_fill_keys(['material_facts','assumptions','unknowns','dependencies','personnel','tools_credentials_data','external_operations','cost_time_retention_limits','risks_contingencies_fallbacks','evidence_provenance_reporting','expiry_revocation_reauthorization'],[]);
        $d=(new PlanningDossierAssemblyService($store,$this->root))->assemble('disposable-proceeding',1,[],$disclosures,new \DateTimeImmutable());
        $r=(new ImperatorPlanningDossierReviewService($this->root))->review($d['dossier_id'],$d['imperator_review_authority']['authority_id'],'APPROVE_DOSSIER',[],'Disposable test approval only.',true,new \DateTimeImmutable());
        $r['actor']['id']=$this->trust['identity']; unset($r['record_digest']); $r['record_digest']=hash('sha256',CanonicalJson::encode($r));
        file_put_contents($this->root.'/var/imperium/offices/curia/planning-dossier-reviews/'.$r['review_id'].'.json',CanonicalJson::encode($r));
        $a=(new MissionAuthorizationDerivationService($this->root))->derive($r['review_id'],$r['mission_authorization_derivation_authority']['authority_id'],new \DateTimeImmutable());
        $this->id=$a['authorization_id'];
        $payload=['schema'=>'imperium.protected-approval/v1','operator_identity'=>$this->trust['identity'],'competence'=>PublicTrust::COMPETENCE,'trust_fingerprint'=>$this->trust['fingerprint'],
            'mission_id'=>'disposable-mission-'.bin2hex(random_bytes(8)),'dossier'=>$d,'review_preview'=>$r,'expires_at'=>time()+1800,'target'=>['commit'=>str_repeat('a',40),'tree'=>str_repeat('b',40)],
            'transitions'=>[['action'=>'admit','actor'=>'test-runtime','from'=>'AUTHORIZED','to'=>'ADMITTED'],['action'=>'finish','actor'=>'test-runtime','from'=>'ADMITTED','to'=>'COMPLETED']]];
        $this->state=['trust'=>$this->trust,'authorizations'=>[$this->id=>['payload'=>$payload,'signature'=>$this->sign($payload),'dossier'=>$d,'review'=>$r,'authorization'=>$a]],'current'=>[$payload['mission_id']=>$this->id]];
        $this->save();
    }
    public function save(): void
    {
        $bytes=CanonicalJson::encode($this->state);
        file_put_contents($this->root.'/authority.journal',strlen($bytes).' '.hash('sha256',$bytes)."\n".$bytes);
    }
    public function sign(array $payload): string { return base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload),$this->secret)); }
    public function call(string $operation,array $arguments=[]): array {return (new AuthorityOwner($this->root))->dispatch(['operation'=>$operation,'arguments'=>$arguments]);}
    public function control(string $action): array
    {
        $payload=['schema'=>'imperium.protected-control/v1','operator_identity'=>$this->trust['identity'],'competence'=>PublicTrust::COMPETENCE,'trust_fingerprint'=>$this->trust['fingerprint'],'action'=>$action,'authorization_id'=>$this->id,'nonce'=>bin2hex(random_bytes(24)),'expires_at'=>time()+60];
        return ['payload'=>$payload,'signature'=>$this->sign($payload)];
    }
    public function __destruct() { sodium_memzero($this->secret); }
}
