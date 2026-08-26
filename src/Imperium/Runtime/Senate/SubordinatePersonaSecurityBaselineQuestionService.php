<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSecurityBaselineQuestionService
{
    private string $senate; private RecordReferenceValidator $validator; private ImmutableRecordStore $records;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root, private PersonaWitnessTestimonyCognitionGateway $cognition, ?RecordReferenceValidator $validator=null, ?ImmutableRecordStore $records=null)
    {$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);$this->records=$records??new ImmutableRecordStore($root,new AtomicTransition($root));}

    public function author(string $consistencyTurnId):array
    {
        if(!preg_match('/^senate-persona-baseline-testimony-turn-[a-f0-9]{20}$/',$consistencyTurnId))throw new \InvalidArgumentException('S162_CONSISTENCY_TURN_ID_INVALID');
        $c=$this->read('testimony-turns',$consistencyTurnId,'S163_CONSISTENCY_TURN_ABSENT');
        $d=$this->read('depositions',(string)($c['deposition_id']??''),'S164_SECURITY_QUESTION_CHAIN_INVALID');
        $w=$this->read('persona-witnesses',(string)($c['manifestation_id']??''),'S164_SECURITY_QUESTION_CHAIN_INVALID');
        if('consistency'!==($c['jurisdiction']??null)||'CONSISTENCY_BASELINE_TESTIMONY_SEALED_PENDING_SECURITY_QUESTION'!==($c['status']??null)
            ||true!==($c['testimony_sealed']??null)||true!==($c['testimony_authority_consumed']??null)||!is_array($c['prior_testimony']??null)||2!==count($c['prior_testimony'])
            ||($c['deposition_digest']??null)!==($d['record_digest']??null)||($c['manifestation_digest']??null)!==($w['record_digest']??null)
            ||null!==($c['senator_finding']??null)||null!==($c['senate_disposition']??null)||true===($c['execution_authority']??null))throw new \RuntimeException('S164_SECURITY_QUESTION_CHAIN_INVALID');
        $senator=$this->senator((string)$c['instance_id']);
        $consistency=$this->reference($c);$prior=[...$c['prior_testimony'],$consistency];
        $assignment=['jurisdiction'=>'security','authority_id'=>$consistencyTurnId,'senator'=>$this->actor($senator),'scope'=>'credential, tool, network, data-custody, escalation, refusal, and external-action boundaries',
            'confirmation_plan_digest'=>$d['confirmation_plan_digest'],'prior_testimony_digests'=>array_column($prior,'turn_digest'),'question_authority'=>true,'finding_authority_exercisable'=>false];
        $context=$d;$context['prior_testimony']=$prior;
        $question=$this->cognition->authorQuestion($assignment,$context,$w);$this->validate($question);
        $id='senate-persona-baseline-question-'.substr(hash('sha256',CanonicalJson::encode([$consistencyTurnId,$c['record_digest'],$senator['record_digest'],$question])),0,20);
        return $this->records->put('var/imperium/offices/senate/persona-questions',$id,[
            'schema'=>'imperium.senate-persona-baseline-question/v1','question_record_id'=>$id,'instance_id'=>$c['instance_id'],'deposition_id'=>$c['deposition_id'],'deposition_digest'=>$c['deposition_digest'],
            'manifestation_id'=>$c['manifestation_id'],'manifestation_digest'=>$c['manifestation_digest'],'candidate_id'=>$c['candidate_id'],'candidate_digest'=>$c['candidate_digest'],
            'originating_guildhall_commission_id'=>$c['originating_guildhall_commission_id'],'originating_guildhall_commission_digest'=>$c['originating_guildhall_commission_digest'],
            'review_target_lineage'=>$c['review_target_lineage'],'source_first_testimony_turn'=>$c['source_first_testimony_turn'],'source_governance_testimony_turn'=>$c['source_governance_testimony_turn'],
            'source_consistency_testimony_turn'=>['id'=>$consistencyTurnId,'digest'=>$c['record_digest']],'prior_testimony'=>$prior,'jurisdiction'=>'security','assignment'=>$assignment,'question'=>$question,
            'question_authority_consumed'=>true,'testimony_authority'=>['authority_id'=>'persona-testimony-authority-'.substr(hash('sha256',CanonicalJson::encode([$id,$question,$w['record_digest']])),0,20),
                'authority_single_use'=>true,'authority_exercisable'=>true,'witness_manifestation_id'=>$c['manifestation_id'],'witness_manifestation_digest'=>$w['record_digest'],'consumed'=>false,'continuing_authority'=>false],
            'testimony'=>null,'testimony_sealed'=>false,'status'=>'SECURITY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
            'senator_finding'=>null,'senate_disposition'=>null,'admission_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }
    private function senator(string $instanceId):array{$matches=[];foreach(glob($this->senate.'/occupancy/*.json')?:[]as$path){$record=$this->validator->read($path,'S165_SECURITY_SENATOR_INVALID');if($this->validator->isIntact($record)&&'senate.committee.security'===($record['seat']??null))$matches[]=$record;}if(1!==count($matches)||$instanceId!==($matches[0]['instance_id']??null)||'ACTIVE'!==($matches[0]['status']??null)||true!==($matches[0]['senator_question_authority']??null)||true===($matches[0]['execution_authority']??null))throw new \RuntimeException('S165_SECURITY_SENATOR_INVALID');return $matches[0];}
    private function reference(array $turn):array{return['jurisdiction'=>'consistency','assignment'=>$turn['assignment'],'question'=>$turn['question'],'testimony'=>$turn['testimony'],'question_dispatched_unchanged'=>$turn['question_dispatched_unchanged'],'question_authority_consumed'=>$turn['question_authority_consumed'],'testimony_sealed'=>$turn['testimony_sealed'],'senator_finding'=>null,'turn_digest'=>$turn['record_digest']];}
    private function actor(array $binding):array{return['seat'=>$binding['seat'],'binding_id'=>$binding['binding_id'],'binding_digest'=>$binding['record_digest'],'manifestation_id'=>$binding['manifestation_id'],'occupancy_generation'=>$binding['occupancy_generation'],'founding_class'=>$binding['founding_class']??'ARTIFACT_BACKED','placeholder_version'=>$binding['placeholder_version']??null];}
    private function validate(array $question):void{$keys=array_keys($question);sort($keys,SORT_STRING);if(['purpose','question','question_set_id','trial_id']!==$keys)throw new \RuntimeException('S166_SECURITY_QUESTION_INVALID');foreach($question as$value)if(!is_string($value)||''===trim($value))throw new \RuntimeException('S166_SECURITY_QUESTION_INVALID');}
    private function read(string $directory,string $id,string $error):array{$record=$this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json',$error);return $this->validator->requireIntact($record,$error);}
}
