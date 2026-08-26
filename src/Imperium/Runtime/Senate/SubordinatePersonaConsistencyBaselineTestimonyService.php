<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaConsistencyBaselineTestimonyService
{
    private string $senate; private RecordReferenceValidator $validator; private ImmutableRecordStore $records;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root, private PersonaWitnessTestimonyCognitionGateway $cognition, ?RecordReferenceValidator $validator=null, ?ImmutableRecordStore $records=null)
    {$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);$this->records=$records??new ImmutableRecordStore($root,new AtomicTransition($root));}

    public function complete(string $questionRecordId):array
    {
        if(!preg_match('/^senate-persona-baseline-question-[a-f0-9]{20}$/',$questionRecordId))throw new \InvalidArgumentException('S158_BASELINE_QUESTION_ID_INVALID');
        $q=$this->read('persona-questions',$questionRecordId,'S159_BASELINE_QUESTION_ABSENT');
        $d=$this->read('depositions',(string)($q['deposition_id']??''),'S160_CONSISTENCY_TESTIMONY_CHAIN_INVALID');
        $w=$this->read('persona-witnesses',(string)($q['manifestation_id']??''),'S160_CONSISTENCY_TESTIMONY_CHAIN_INVALID');
        $a=$q['testimony_authority']??null;
        if(!is_array($a)||'consistency'!==($q['jurisdiction']??null)||'CONSISTENCY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION'!==($q['status']??null)
            ||true!==($a['authority_single_use']??null)||true!==($a['authority_exercisable']??null)||false!==($a['consumed']??null)
            ||($q['deposition_digest']??null)!==($d['record_digest']??null)||($q['manifestation_digest']??null)!==($w['record_digest']??null)
            ||!is_array($q['prior_testimony']??null)||2!==count($q['prior_testimony'])||null!==($q['testimony']??null)||true===($q['testimony_sealed']??null))
            throw new \RuntimeException('S160_CONSISTENCY_TESTIMONY_CHAIN_INVALID');
        $context=$d;$context['prior_testimony']=$q['prior_testimony'];
        $answer=$this->cognition->answer($q,$context,$w);$this->validate($answer);
        $id='senate-persona-baseline-testimony-turn-'.substr(hash('sha256',CanonicalJson::encode([$questionRecordId,$q['record_digest'],$a['authority_id'],$answer])),0,20);
        return $this->records->put('var/imperium/offices/senate/testimony-turns',$id,[
            'schema'=>'imperium.senate-persona-baseline-testimony-turn/v1','turn_id'=>$id,'instance_id'=>$q['instance_id'],'deposition_id'=>$q['deposition_id'],'deposition_digest'=>$q['deposition_digest'],
            'manifestation_id'=>$q['manifestation_id'],'manifestation_digest'=>$q['manifestation_digest'],'candidate_id'=>$q['candidate_id'],'candidate_digest'=>$q['candidate_digest'],
            'originating_guildhall_commission_id'=>$q['originating_guildhall_commission_id'],'originating_guildhall_commission_digest'=>$q['originating_guildhall_commission_digest'],
            'review_target_lineage'=>$q['review_target_lineage'],'source_first_testimony_turn'=>$q['source_first_testimony_turn'],'source_governance_testimony_turn'=>$q['source_governance_testimony_turn'],'prior_testimony'=>$q['prior_testimony'],
            'jurisdiction'=>'consistency','assignment'=>$q['assignment'],'question'=>$q['question'],'testimony'=>$answer,
            'source_question_record'=>['id'=>$questionRecordId,'digest'=>$q['record_digest']],'testimony_authority'=>['id'=>$a['authority_id'],'consumed'=>true,'continuing_authority'=>false],
            'question_dispatched_unchanged'=>true,'question_authority_consumed'=>true,'testimony_authority_consumed'=>true,'testimony_sealed'=>true,
            'status'=>'CONSISTENCY_BASELINE_TESTIMONY_SEALED_PENDING_SECURITY_QUESTION','senator_finding'=>null,'senate_disposition'=>null,
            'admission_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }
    private function read(string $directory,string $id,string $error):array{$record=$this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json',$error);return $this->validator->requireIntact($record,$error);}
    private function validate(array $answer):void{$keys=array_keys($answer);sort($keys,SORT_STRING);if(['answer','evidence_claims','refusals','uncertainties']!==$keys||!is_string($answer['answer'])||''===trim($answer['answer']))throw new \RuntimeException('S161_CONSISTENCY_TESTIMONY_INVALID');foreach(['evidence_claims','refusals','uncertainties']as$field){if(!is_array($answer[$field])||!array_is_list($answer[$field]))throw new \RuntimeException('S161_CONSISTENCY_TESTIMONY_INVALID');foreach($answer[$field]as$value)if(!is_string($value)||''===trim($value))throw new \RuntimeException('S161_CONSISTENCY_TESTIMONY_INVALID');}}
}
