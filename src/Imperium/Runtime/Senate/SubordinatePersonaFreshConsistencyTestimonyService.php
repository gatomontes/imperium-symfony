<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaFreshConsistencyTestimonyService
{
    private string $senate;private RecordReferenceValidator $validator;private ImmutableRecordStore $records;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root,private PersonaWitnessTestimonyCognitionGateway $cognition,?RecordReferenceValidator $validator=null,?ImmutableRecordStore $records=null){$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);$this->records=$records??new ImmutableRecordStore($root,new AtomicTransition($root));}
    public function complete(string $questionRecordId):array
    {
        if(!preg_match('/^senate-persona-fresh-consistency-question-[a-f0-9]{20}$/',$questionRecordId))throw new \InvalidArgumentException('S868_FRESH_QUESTION_ID_INVALID');
        $q=$this->read('fresh-consistency-questions',$questionRecordId,'S869_FRESH_QUESTION_ABSENT');$baseline=$this->read('jurisdiction-baselines',(string)($q['baseline_id']??''),'S870_FRESH_TESTIMONY_CHAIN_INVALID');$source=$this->read('persona-witnesses',(string)($baseline['manifestation_id']??''),'S870_FRESH_TESTIMONY_CHAIN_INVALID');$fresh=$this->read('persona-witnesses',(string)($q['fresh_witness']['manifestation_id']??''),'S870_FRESH_TESTIMONY_CHAIN_INVALID');$a=$q['testimony_authority']??null;
        if(!is_array($a)||'FRESH_INSTANCE_CONSISTENCY_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION'!==($q['status']??null)||'REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS'!==($baseline['status']??null)||true!==($baseline['additional_trials_required']??null)||true!==($q['pressure_trials_required']??null)||true!==($a['authority_single_use']??null)||true!==($a['authority_exercisable']??null)||false!==($a['consumed']??null)
            ||($q['baseline_digest']??null)!==($baseline['record_digest']??null)||($q['fresh_witness']['manifestation_digest']??null)!==($fresh['record_digest']??null)||($a['witness_manifestation_digest']??null)!==($fresh['record_digest']??null)
            ||($fresh['source_manifestation_id']??null)!==($source['manifestation_id']??null)||($fresh['source_manifestation_digest']??null)!==($source['record_digest']??null)||($fresh['candidate_digest']??null)!==($source['candidate_digest']??null)||CanonicalJson::encode($fresh['persona']??null)!==CanonicalJson::encode($source['persona']??null)
            ||null!==($q['testimony']??null)||true===($q['testimony_sealed']??null)||true===($q['execution_authority']??null))throw new \RuntimeException('S870_FRESH_TESTIMONY_CHAIN_INVALID');
        $context=['baseline'=>$baseline,'comparison_target'=>$q['comparison_target'],'fresh_manifestation_id'=>$fresh['manifestation_id']];
        $dispatch=$q;$dispatch['jurisdiction']='consistency';$dispatch['cognition_authority_type']='testimony-fresh-consistency';
        $answer=$this->cognition->answer($dispatch,$context,$fresh);$this->validate($answer);
        $comparison=['baseline_manifestation_id'=>$baseline['manifestation_id'],'baseline_manifestation_digest'=>$baseline['manifestation_digest'],'baseline_turn'=>$q['comparison_target'],'fresh_manifestation_id'=>$fresh['manifestation_id'],'fresh_manifestation_digest'=>$fresh['record_digest'],'fresh_question'=>$q['question'],'fresh_testimony'=>$answer,
            'candidate_id_equal'=>$baseline['candidate_id']===$fresh['candidate_id'],'candidate_digest_equal'=>$baseline['candidate_digest']===$fresh['candidate_digest'],'persona_equal'=>true,'variance_assessment'=>null,'consistency_finding'=>null];
        $id='senate-persona-fresh-consistency-trial-'.substr(hash('sha256',CanonicalJson::encode([$questionRecordId,$q['record_digest'],$a['authority_id'],$answer])),0,20);
        return$this->records->put('var/imperium/offices/senate/fresh-consistency-trials',$id,[
            'schema'=>'imperium.senate-persona-fresh-consistency-trial/v1','trial_record_id'=>$id,'instance_id'=>$q['instance_id'],'baseline_id'=>$q['baseline_id'],'baseline_digest'=>$q['baseline_digest'],'candidate_id'=>$q['candidate_id'],'candidate_digest'=>$q['candidate_digest'],
            'originating_guildhall_commission_id'=>$q['originating_guildhall_commission_id'],'originating_guildhall_commission_digest'=>$q['originating_guildhall_commission_digest'],'review_target_lineage'=>$q['review_target_lineage'],'assignment'=>$q['assignment'],'fresh_witness'=>$q['fresh_witness'],
            'question'=>$q['question'],'testimony'=>$answer,'comparison_record'=>$comparison,'source_question_record'=>['id'=>$questionRecordId,'digest'=>$q['record_digest']],
            'question_dispatched_unchanged'=>true,'question_authority_consumed'=>true,'testimony_authority'=>['id'=>$a['authority_id'],'consumed'=>true,'continuing_authority'=>false],'testimony_authority_consumed'=>true,'testimony_sealed'=>true,
            'status'=>'FRESH_INSTANCE_CONSISTENCY_TRIAL_SEALED_PENDING_PRESSURE_TRIALS','pressure_trials_required'=>true,'senator_finding'=>null,'drift_conclusion'=>null,'aggregate_score'=>null,'vote'=>null,'senate_disposition'=>null,
            'admission_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false,'sealed'=>true]);
    }
    private function read(string $directory,string $id,string $error):array{$r=$this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json',$error);return$this->validator->requireIntact($r,$error);}
    private function validate(array $answer):void{$keys=array_keys($answer);sort($keys,SORT_STRING);if(['answer','evidence_claims','refusals','uncertainties']!==$keys||!is_string($answer['answer'])||''===trim($answer['answer']))throw new \RuntimeException('S871_FRESH_TESTIMONY_INVALID');foreach(['evidence_claims','refusals','uncertainties']as$field){if(!is_array($answer[$field])||!array_is_list($answer[$field]))throw new \RuntimeException('S871_FRESH_TESTIMONY_INVALID');foreach($answer[$field]as$value)if(!is_string($value)||''===trim($value))throw new \RuntimeException('S871_FRESH_TESTIMONY_INVALID');}}
}
