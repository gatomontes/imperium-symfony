<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenatePersonaFreshConsistencyTestimonyGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private string $senate;private RecordReferenceValidator $validator;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root,?RecordReferenceValidator $validator=null){$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);}
    public function supports(string $cluster,string $authorityType):bool{return'senate-persona-confirmation'===$cluster&&'testimony-fresh-consistency'===$authorityType;}
    public function resolve(string $cluster,string $authorityType,string $authorityId):array
    {
        if(!$this->supports($cluster,$authorityType))throw new \RuntimeException('GCA600_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_UNSUPPORTED');
        $q=$this->question($authorityId);$baseline=$this->read('jurisdiction-baselines',(string)($q['baseline_id']??''));$source=$this->read('persona-witnesses',(string)($baseline['manifestation_id']??''));$fresh=$this->read('persona-witnesses',(string)($q['fresh_witness']['manifestation_id']??''));$a=$q['testimony_authority']??null;
        $context=['baseline'=>$baseline,'comparison_target'=>$q['comparison_target'],'fresh_manifestation_id'=>$fresh['manifestation_id']];
        $valid=is_array($a)&&$authorityId===($a['authority_id']??null)&&true===($a['authority_single_use']??null)&&true===($a['authority_exercisable']??null)&&false===($a['consumed']??null)
            &&'imperium.senate-persona-fresh-consistency-question/v1'===($q['schema']??null)&&'FRESH_INSTANCE_CONSISTENCY_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION'===($q['status']??null)
            &&'imperium.senate-persona-jurisdiction-baseline/v1'===($baseline['schema']??null)&&'REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS'===($baseline['status']??null)&&true===($baseline['additional_trials_required']??null)&&true===($q['pressure_trials_required']??null)
            &&($q['baseline_digest']??null)===($baseline['record_digest']??null)&&($q['fresh_witness']['manifestation_digest']??null)===($fresh['record_digest']??null)
            &&($a['witness_manifestation_id']??null)===($fresh['manifestation_id']??null)&&($a['witness_manifestation_digest']??null)===($fresh['record_digest']??null)
            &&($fresh['source_manifestation_id']??null)===($source['manifestation_id']??null)&&($fresh['source_manifestation_digest']??null)===($source['record_digest']??null)
            &&($fresh['candidate_digest']??null)===($source['candidate_digest']??null)&&CanonicalJson::encode($fresh['persona']??null)===CanonicalJson::encode($source['persona']??null)
            &&null===($q['testimony']??null)&&false===($q['testimony_sealed']??null)&&false===($q['execution_authority']??null);
        if(!$valid)throw new \RuntimeException('GCA602_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_INVALID');
        return['cluster'=>$cluster,'authority_type'=>$authorityType,'authority_id'=>$authorityId,'instance_id'=>$q['instance_id'],'case_id'=>$q['baseline_id'],'case_digest'=>$q['baseline_digest'],'seat'=>'senate.stand','purpose'=>'answer-persona-question',
            'input_digest'=>hash('sha256',CanonicalJson::encode([$q['question'],$context,$fresh])),'source'=>['id'=>$q['question_record_id'],'digest'=>$q['record_digest']],
            'single_use'=>true,'exercisable'=>true,'consumed'=>$this->consumed($q['question_record_id']),'expires_at'=>'9999-12-31T23:59:59+00:00'];
    }
    private function question(string $authorityId):array{$found=[];foreach(glob($this->senate.'/fresh-consistency-questions/*.json')?:[]as$path){$r=$this->validator->read($path,'GCA601_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_ABSENT');if($this->validator->isIntact($r)&&$authorityId===($r['testimony_authority']['authority_id']??null))$found[]=$r;}if(1!==count($found))throw new \RuntimeException('GCA601_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_ABSENT');return$found[0];}
    private function consumed(string $questionId):bool{foreach(glob($this->senate.'/fresh-consistency-trials/*.json')?:[]as$path){$r=$this->validator->read($path,'GCA602_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_INVALID');if($this->validator->isIntact($r)&&$questionId===($r['source_question_record']['id']??null))return true;}return false;}
    private function read(string $directory,string $id):array{$r=$this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json','GCA601_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_ABSENT');return$this->validator->requireIntact($r,'GCA602_FRESH_CONSISTENCY_TESTIMONY_AUTHORITY_INVALID');}
}
