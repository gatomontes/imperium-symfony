<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaConsistencyBaselineQuestionService
{
    private string$senate;private RecordReferenceValidator$validator;private ImmutableRecordStore$records;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root,private PersonaWitnessTestimonyCognitionGateway$cognition,?RecordReferenceValidator$validator=null,?ImmutableRecordStore$records=null)
    {$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);$this->records=$records??new ImmutableRecordStore($root,new AtomicTransition($root));}

    public function author(string$governanceTurnId):array
    {
        if(!preg_match('/^senate-persona-baseline-testimony-turn-[a-f0-9]{20}$/',$governanceTurnId))throw new \InvalidArgumentException('S153_GOVERNANCE_TURN_ID_INVALID');
        $g=$this->read('testimony-turns',$governanceTurnId,'S154_GOVERNANCE_TURN_ABSENT');
        $d=$this->read('depositions',(string)($g['deposition_id']??''),'S155_CONSISTENCY_QUESTION_CHAIN_INVALID');
        $w=$this->read('persona-witnesses',(string)($g['manifestation_id']??''),'S155_CONSISTENCY_QUESTION_CHAIN_INVALID');
        if('governance'!==($g['jurisdiction']??null)||'GOVERNANCE_BASELINE_TESTIMONY_SEALED_PENDING_CONSISTENCY_QUESTION'!==($g['status']??null)
            ||true!==($g['testimony_sealed']??null)||true!==($g['testimony_authority_consumed']??null)||!is_array($g['prior_testimony']??null)||1!==count($g['prior_testimony'])
            ||($g['deposition_digest']??null)!==($d['record_digest']??null)||($g['manifestation_digest']??null)!==($w['record_digest']??null)
            ||null!==($g['senator_finding']??null)||null!==($g['senate_disposition']??null)||true===($g['execution_authority']??null))throw new \RuntimeException('S155_CONSISTENCY_QUESTION_CHAIN_INVALID');
        $senator=$this->senator((string)$g['instance_id']);
        $governance=$this->reference($g);$prior=[...$g['prior_testimony'],$governance];
        $assignment=['jurisdiction'=>'consistency','authority_id'=>$governanceTurnId,'senator'=>$this->actor($senator),'scope'=>'contradictions, drift, unstable priorities, and materially different conduct',
            'confirmation_plan_digest'=>$d['confirmation_plan_digest'],'prior_testimony_digests'=>array_column($prior,'turn_digest'),'question_authority'=>true,'finding_authority_exercisable'=>false];
        $context=$d;$context['prior_testimony']=$prior;
        $question=$this->cognition->authorQuestion($assignment,$context,$w);$this->validate($question);
        $id='senate-persona-baseline-question-'.substr(hash('sha256',CanonicalJson::encode([$governanceTurnId,$g['record_digest'],$senator['record_digest'],$question])),0,20);
        return$this->records->put('var/imperium/offices/senate/persona-questions',$id,[
            'schema'=>'imperium.senate-persona-baseline-question/v1','question_record_id'=>$id,'instance_id'=>$g['instance_id'],'deposition_id'=>$g['deposition_id'],'deposition_digest'=>$g['deposition_digest'],
            'manifestation_id'=>$g['manifestation_id'],'manifestation_digest'=>$g['manifestation_digest'],'candidate_id'=>$g['candidate_id'],'candidate_digest'=>$g['candidate_digest'],
            'originating_guildhall_commission_id'=>$g['originating_guildhall_commission_id'],'originating_guildhall_commission_digest'=>$g['originating_guildhall_commission_digest'],
            'review_target_lineage'=>$g['review_target_lineage'],'source_first_testimony_turn'=>$g['source_first_testimony_turn'],
            'source_governance_testimony_turn'=>['id'=>$governanceTurnId,'digest'=>$g['record_digest']],'prior_testimony'=>$prior,'jurisdiction'=>'consistency','assignment'=>$assignment,'question'=>$question,
            'question_authority_consumed'=>true,'testimony_authority'=>['authority_id'=>'persona-testimony-authority-'.substr(hash('sha256',CanonicalJson::encode([$id,$question,$w['record_digest']])),0,20),
                'authority_single_use'=>true,'authority_exercisable'=>true,'witness_manifestation_id'=>$g['manifestation_id'],'witness_manifestation_digest'=>$w['record_digest'],'consumed'=>false,'continuing_authority'=>false],
            'testimony'=>null,'testimony_sealed'=>false,'status'=>'CONSISTENCY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
            'senator_finding'=>null,'senate_disposition'=>null,'admission_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }
    private function senator(string$i):array{$m=[];foreach(glob($this->senate.'/occupancy/*.json')?:[]as$p){$r=$this->validator->read($p,'S156_CONSISTENCY_SENATOR_INVALID');if($this->validator->isIntact($r)&&'senate.committee.consistency'===($r['seat']??null))$m[]=$r;}if(1!==count($m)||$i!==($m[0]['instance_id']??null)||'ACTIVE'!==($m[0]['status']??null)||true!==($m[0]['senator_question_authority']??null)||true===($m[0]['execution_authority']??null))throw new \RuntimeException('S156_CONSISTENCY_SENATOR_INVALID');return$m[0];}
    private function reference(array$t):array{return['jurisdiction'=>'governance','assignment'=>$t['assignment'],'question'=>$t['question'],'testimony'=>$t['testimony'],'question_dispatched_unchanged'=>$t['question_dispatched_unchanged'],'question_authority_consumed'=>$t['question_authority_consumed'],'testimony_sealed'=>$t['testimony_sealed'],'senator_finding'=>null,'turn_digest'=>$t['record_digest']];}
    private function actor(array$b):array{return['seat'=>$b['seat'],'binding_id'=>$b['binding_id'],'binding_digest'=>$b['record_digest'],'manifestation_id'=>$b['manifestation_id'],'occupancy_generation'=>$b['occupancy_generation'],'founding_class'=>$b['founding_class']??'ARTIFACT_BACKED','placeholder_version'=>$b['placeholder_version']??null];}
    private function validate(array$q):void{$k=array_keys($q);sort($k,SORT_STRING);if(['purpose','question','question_set_id','trial_id']!==$k)throw new \RuntimeException('S157_CONSISTENCY_QUESTION_INVALID');foreach($q as$v)if(!is_string($v)||''===trim($v))throw new \RuntimeException('S157_CONSISTENCY_QUESTION_INVALID');}
    private function read(string$d,string$id,string$e):array{$r=$this->validator->read($this->senate.'/'.$d.'/'.$id.'.json',$e);return$this->validator->requireIntact($r,$e);}
}
