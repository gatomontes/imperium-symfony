<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\ProtectedMission\AuthorityOwner;
use App\ProtectedMission\PublicTrust;
use App\Imperium\Runtime\Curia\{ProceedingStore,PlanningDossierAssemblyService,ImperatorPlanningDossierReviewService,MissionAuthorizationDerivationService};

/** Fresh canonical ceremony fixture. save() is only for explicit corruption/adverse tests. */
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
        $challenge=$this->call('prepare',self::input())['challenge_id'];
        $payload=$this->call('export',['challenge_id'=>$challenge]);
        $this->call('submit',['challenge_id'=>$challenge,'signature'=>$this->sign($payload)]);
        $this->id=$this->call('derive',['challenge_id'=>$challenge])['authorization_id'];
        $handle=fopen($this->root.'/authority.journal','rb');
        while (($line=fgets($handle))!==false) {$length=(int)explode(' ',$line)[0];$this->state=json_decode(fread($handle,$length),true,512,JSON_THROW_ON_ERROR);}
        fclose($handle);
    }
    public static function input():array
    {
        return ['mission'=>['mission_id'=>'disposable-mission-'.bin2hex(random_bytes(8)),
            'target'=>['repository'=>'not-accessed','commit'=>str_repeat('a',40),'tree'=>str_repeat('b',40)],
            'paths'=>['evidence.txt'],'budget'=>['max_files'=>1,'max_bytes'=>100000,'max_findings'=>1,'max_seconds'=>10],
            'expires_at'=>time()+1800,'permissions'=>['READ_EXACT_GIT_OBJECTS'],'prohibitions'=>['NETWORK','TARGET_MUTATION','PROVIDERS','CREDENTIALS'],
            'transitions'=>[['action'=>'admit','actor'=>'protected-git-inspector','from'=>'AUTHORIZED','to'=>'ADMITTED'],['action'=>'inspect','actor'=>'protected-git-inspector','from'=>'ADMITTED','to'=>'INSPECTING'],['action'=>'complete','actor'=>'protected-git-inspector','from'=>'INSPECTING','to'=>'COMPLETED']]],
            'disclosures'=>array_fill_keys(['material_facts','assumptions','unknowns','dependencies','personnel','tools_credentials_data','external_operations','cost_time_retention_limits','risks_contingencies_fallbacks','evidence_provenance_reporting','expiry_revocation_reauthorization'],[])];
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
