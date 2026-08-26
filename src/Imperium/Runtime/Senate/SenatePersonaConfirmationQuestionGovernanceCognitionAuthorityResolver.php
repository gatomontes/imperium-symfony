<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenatePersonaConfirmationQuestionGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private const JURISDICTIONS=['question-practice'=>'practice','question-governance'=>'governance','question-consistency'=>'consistency','question-security'=>'security'];
    private string $senate; private RecordReferenceValidator $validator;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root,?RecordReferenceValidator $validator=null){$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);}

    public function supports(string $cluster,string $authorityType):bool{return 'senate-persona-confirmation'===$cluster&&isset(self::JURISDICTIONS[$authorityType]);}

    public function resolve(string $cluster,string $authorityType,string $authorityId):array
    {
        if(!$this->supports($cluster,$authorityType))throw new \RuntimeException('GCA580_SENATE_PERSONA_QUESTION_AUTHORITY_UNSUPPORTED');
        $jurisdiction=self::JURISDICTIONS[$authorityType];
        $source=$this->source($jurisdiction,$authorityId);
        $deposition='practice'===$jurisdiction?$source:$this->read('depositions',(string)($source['deposition_id']??''));
        $witness=$this->read('persona-witnesses',(string)($deposition['manifestation_id']??''));
        $senator=$this->senator($jurisdiction,(string)($deposition['instance_id']??''));
        $prior=$this->prior($jurisdiction,$source);
        $assignment=['jurisdiction'=>$jurisdiction,'authority_id'=>$authorityId,'senator'=>$this->actor($senator),'scope'=>$this->scope($jurisdiction)];
        $assignment['confirmation_plan_digest']=$deposition['confirmation_plan_digest'];
        if('practice'!==$jurisdiction)$assignment['prior_testimony_digests']=array_column($prior,'turn_digest');
        $assignment['question_authority']=true;$assignment['finding_authority_exercisable']=false;
        $context=$deposition;if('practice'!==$jurisdiction)$context['prior_testimony']=$prior;
        $this->validate($jurisdiction,$authorityId,$source,$deposition,$witness,$prior);
        return['cluster'=>$cluster,'authority_type'=>$authorityType,'authority_id'=>$authorityId,'instance_id'=>$deposition['instance_id'],'case_id'=>$deposition['deposition_id'],'case_digest'=>$deposition['record_digest'],
            'seat'=>'senate.committee.'.$jurisdiction,'purpose'=>'author-persona-question','input_digest'=>hash('sha256',CanonicalJson::encode([$assignment,$context,$witness])),
            'source'=>['id'=>$authorityId,'digest'=>$source['record_digest']],'single_use'=>true,'exercisable'=>true,'consumed'=>$this->consumed($jurisdiction,$authorityId),'expires_at'=>'9999-12-31T23:59:59+00:00'];
    }

    private function source(string $jurisdiction,string $id):array{return $this->read('practice'===$jurisdiction?'depositions':'testimony-turns',$id);}
    private function prior(string $jurisdiction,array $source):array
    {
        if('practice'===$jurisdiction)return[];
        if('governance'===$jurisdiction)return[$this->reference($source,'practice')];
        $first=$this->referencedTurn($source['source_first_testimony_turn']??null,'practice');
        if('consistency'===$jurisdiction){$prior=[$first];$this->requireCarriedPrior($source,$prior);$prior[]=$this->reference($source,'governance');return$prior;}
        $governance=$this->referencedTurn($source['source_governance_testimony_turn']??null,'governance');
        $prior=[$first,$governance];$this->requireCarriedPrior($source,$prior);$prior[]=$this->reference($source,'consistency');return$prior;
    }
    private function validate(string $jurisdiction,string $id,array $source,array $deposition,array $witness,array $prior):void
    {
        $valid=$this->validator->isIntact($source)&&$this->validator->isIntact($deposition)&&$this->validator->isIntact($witness)
            &&($deposition['manifestation_digest']??null)===($witness['record_digest']??null)&&false===($deposition['execution_authority']??null);
        $valid=$valid&&match($jurisdiction){
            'practice'=>$id===($deposition['deposition_id']??null)&&'OPEN_PENDING_FIRST_QUESTION'===($deposition['status']??null)&&[]===($deposition['questions']??null)&&[]===($deposition['testimony']??null),
            'governance'=>'practice'===($source['jurisdiction']??null)&&'FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS'===($source['status']??null)&&true===($source['testimony_sealed']??null)&&1===count($prior),
            'consistency'=>'governance'===($source['jurisdiction']??null)&&'GOVERNANCE_BASELINE_TESTIMONY_SEALED_PENDING_CONSISTENCY_QUESTION'===($source['status']??null)&&true===($source['testimony_sealed']??null)&&2===count($prior),
            'security'=>'consistency'===($source['jurisdiction']??null)&&'CONSISTENCY_BASELINE_TESTIMONY_SEALED_PENDING_SECURITY_QUESTION'===($source['status']??null)&&true===($source['testimony_sealed']??null)&&3===count($prior),
        };
        if(!$valid||('practice'!==$jurisdiction&&(($source['deposition_digest']??null)!==($deposition['record_digest']??null)||($source['manifestation_digest']??null)!==($witness['record_digest']??null)||true!==($source['testimony_authority_consumed']??null))))throw new \RuntimeException('GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');
    }
    private function consumed(string $jurisdiction,string $authorityId):bool{foreach(glob($this->senate.'/persona-questions/*.json')?:[]as$path){$record=$this->validator->read($path,'GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');if($this->validator->isIntact($record)&&$jurisdiction===($record['jurisdiction']??null)&&$authorityId===($record['assignment']['authority_id']??null))return true;}return false;}
    private function senator(string $jurisdiction,string $instanceId):array{$matches=[];foreach(glob($this->senate.'/occupancy/*.json')?:[]as$path){$record=$this->validator->read($path,'GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');if($this->validator->isIntact($record)&&'senate.committee.'.$jurisdiction===($record['seat']??null))$matches[]=$record;}if(1!==count($matches)||$instanceId!==($matches[0]['instance_id']??null)||'ACTIVE'!==($matches[0]['status']??null)||true!==($matches[0]['senator_question_authority']??null)||true===($matches[0]['execution_authority']??null))throw new \RuntimeException('GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');return$matches[0];}
    private function scope(string $jurisdiction):string{return match($jurisdiction){'practice'=>'professional decisions, methods, competence, and competence boundaries','governance'=>'authority, obligations, prohibitions, evidence, refusal, escalation, and stop conditions','consistency'=>'contradictions, drift, unstable priorities, and materially different conduct','security'=>'credential, tool, network, data-custody, escalation, refusal, and external-action boundaries'};}
    private function reference(array $turn,string $jurisdiction):array{return['jurisdiction'=>$jurisdiction,'assignment'=>$turn['assignment'],'question'=>$turn['question'],'testimony'=>$turn['testimony'],'question_dispatched_unchanged'=>$turn['question_dispatched_unchanged'],'question_authority_consumed'=>$turn['question_authority_consumed'],'testimony_sealed'=>$turn['testimony_sealed'],'senator_finding'=>null,'turn_digest'=>$turn['record_digest']];}
    private function referencedTurn(mixed $reference,string $jurisdiction):array{if(!is_array($reference)||!is_string($reference['id']??null)||!is_string($reference['digest']??null))throw new \RuntimeException('GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');$turn=$this->read('testimony-turns',$reference['id']);if($reference['digest']!==($turn['record_digest']??null)||$jurisdiction!==($turn['jurisdiction']??null)||true!==($turn['testimony_sealed']??null))throw new \RuntimeException('GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');return$this->reference($turn,$jurisdiction);}
    private function requireCarriedPrior(array $source,array $prior):void{if(CanonicalJson::encode($prior)!==CanonicalJson::encode($source['prior_testimony']??null))throw new \RuntimeException('GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');}
    private function actor(array $binding):array{return['seat'=>$binding['seat'],'binding_id'=>$binding['binding_id'],'binding_digest'=>$binding['record_digest'],'manifestation_id'=>$binding['manifestation_id'],'occupancy_generation'=>$binding['occupancy_generation'],'founding_class'=>$binding['founding_class']??'ARTIFACT_BACKED','placeholder_version'=>$binding['placeholder_version']??null];}
    private function read(string $directory,string $id):array{$record=$this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json','GCA581_SENATE_PERSONA_QUESTION_AUTHORITY_ABSENT');return$this->validator->requireIntact($record,'GCA582_SENATE_PERSONA_QUESTION_AUTHORITY_INVALID');}
}
