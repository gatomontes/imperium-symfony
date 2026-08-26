<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaJurisdictionBaselineService
{
    private string $senate;
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private PersonaWitnessTestimonyCognitionGateway $cognition, ?RecordReferenceValidator $validator=null, ?ImmutableRecordStore $records=null)
    {
        $this->senate=$root.'/var/imperium/offices/senate';
        $this->validator=$validator??new RecordReferenceValidator($root);
        $this->records=$records??new ImmutableRecordStore($root,new AtomicTransition($root));
    }

    public function complete(string $firstTurnId): array
    {
        if(!preg_match('/^senate-persona-testimony-turn-[a-f0-9]{20}$/',$firstTurnId))throw new \InvalidArgumentException('S139_FIRST_TESTIMONY_TURN_ID_INVALID');
        $first=$this->read('testimony-turns',$firstTurnId,'S140_FIRST_TESTIMONY_TURN_ABSENT');
        $deposition=$this->read('depositions',(string)($first['deposition_id']??''),'S141_JURISDICTION_BASELINE_CHAIN_INVALID');
        $witness=$this->read('persona-witnesses',(string)($first['manifestation_id']??''),'S141_JURISDICTION_BASELINE_CHAIN_INVALID');
        if('FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS'!==($first['status']??null)||'practice'!==($first['jurisdiction']??null)
            ||($first['deposition_digest']??null)!==($deposition['record_digest']??null)||($first['manifestation_digest']??null)!==($witness['record_digest']??null)
            ||null!==($first['senator_finding']??null)||null!==($first['senate_disposition']??null)||true===($first['admission_authority']??null)||true===($first['execution_authority']??null))
            throw new \RuntimeException('S141_JURISDICTION_BASELINE_CHAIN_INVALID');
        $senator=$this->senator((string)$deposition['instance_id']);
        $practice=$this->practiceReference($first);
        $assignment=['jurisdiction'=>'governance','senator'=>$this->actor($senator),'scope'=>'authority, obligations, prohibitions, evidence, refusal, escalation, and stop conditions',
            'confirmation_plan_digest'=>$deposition['confirmation_plan_digest'],'prior_testimony_digests'=>[$first['record_digest']],'question_authority'=>true,'finding_authority_exercisable'=>false];
        $context=$deposition;$context['prior_testimony']=[$practice];
        $question=$this->cognition->authorQuestion($assignment,$context,$witness);$this->validateQuestion($question);
        $id='senate-persona-baseline-question-'.substr(hash('sha256',CanonicalJson::encode([$firstTurnId,$first['record_digest'],$senator['record_digest'],$question])),0,20);
        return $this->records->put('var/imperium/offices/senate/persona-questions',$id,[
            'schema'=>'imperium.senate-persona-baseline-question/v1','question_record_id'=>$id,'instance_id'=>$deposition['instance_id'],
            'deposition_id'=>$first['deposition_id'],'deposition_digest'=>$deposition['record_digest'],'manifestation_id'=>$first['manifestation_id'],'manifestation_digest'=>$witness['record_digest'],
            'candidate_id'=>$first['candidate_id'],'candidate_digest'=>$first['candidate_digest'],'originating_guildhall_commission_id'=>$first['originating_guildhall_commission_id'],
            'originating_guildhall_commission_digest'=>$first['originating_guildhall_commission_digest'],'review_target_lineage'=>$first['review_target_lineage'],
            'source_first_testimony_turn'=>['id'=>$firstTurnId,'digest'=>$first['record_digest']],'prior_testimony'=>[$practice],'jurisdiction'=>'governance',
            'assignment'=>$assignment,'question'=>$question,'question_authority_consumed'=>true,
            'testimony_authority'=>['authority_id'=>'persona-testimony-authority-'.substr(hash('sha256',CanonicalJson::encode([$id,$question,$witness['record_digest']])),0,20),
                'authority_single_use'=>true,'authority_exercisable'=>true,'witness_manifestation_id'=>$first['manifestation_id'],'witness_manifestation_digest'=>$witness['record_digest'],'consumed'=>false,'continuing_authority'=>false],
            'testimony'=>null,'testimony_sealed'=>false,'status'=>'GOVERNANCE_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
            'senator_finding'=>null,'senate_disposition'=>null,'admission_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }

    private function senator(string $instanceId):array
    {
        $matches=[];foreach(glob($this->senate.'/occupancy/*.json')?:[]as$path){$r=$this->validator->read($path,'S142_SENATOR_OCCUPANCY_INVALID');if($this->validator->isIntact($r)&&'senate.committee.governance'===($r['seat']??null))$matches[]=$r;}
        if(1!==count($matches)||$instanceId!==($matches[0]['instance_id']??null)||'ACTIVE'!==($matches[0]['status']??null)||true!==($matches[0]['senator_question_authority']??null)||true===($matches[0]['execution_authority']??null))throw new \RuntimeException('S142_SENATOR_OCCUPANCY_INVALID');
        return$matches[0];
    }
    private function practiceReference(array$t):array{return['jurisdiction'=>'practice','assignment'=>$t['assignment'],'question'=>$t['question'],'testimony'=>$t['testimony'],'question_dispatched_unchanged'=>$t['question_dispatched_unchanged'],'question_authority_consumed'=>$t['question_authority_consumed'],'testimony_sealed'=>$t['testimony_sealed'],'senator_finding'=>null,'turn_digest'=>$t['record_digest']];}
    private function actor(array$b):array{return['seat'=>$b['seat'],'binding_id'=>$b['binding_id'],'binding_digest'=>$b['record_digest'],'manifestation_id'=>$b['manifestation_id'],'occupancy_generation'=>$b['occupancy_generation'],'founding_class'=>$b['founding_class']??'ARTIFACT_BACKED','placeholder_version'=>$b['placeholder_version']??null];}
    private function validateQuestion(array$q):void{$keys=array_keys($q);sort($keys,SORT_STRING);if(['purpose','question','question_set_id','trial_id']!==$keys)throw new \RuntimeException('S144_SENATOR_QUESTION_INVALID');foreach($q as$v)if(!is_string($v)||''===trim($v))throw new \RuntimeException('S144_SENATOR_QUESTION_INVALID');}
    private function read(string$d,string$id,string$e):array{$r=$this->validator->read($this->senate.'/'.$d.'/'.$id.'.json',$e);return$this->validator->requireIntact($r,$e);}
}
