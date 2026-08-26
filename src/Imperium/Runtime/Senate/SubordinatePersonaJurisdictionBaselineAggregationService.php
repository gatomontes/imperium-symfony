<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaJurisdictionBaselineAggregationService
{
    private string $senate; private RecordReferenceValidator $validator; private ImmutableRecordStore $records;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root,?RecordReferenceValidator $validator=null,?ImmutableRecordStore $records=null){$this->senate=$root.'/var/imperium/offices/senate';$this->validator=$validator??new RecordReferenceValidator($root);$this->records=$records??new ImmutableRecordStore($root,new AtomicTransition($root));}

    public function aggregate(string $securityTurnId):array
    {
        if(!preg_match('/^senate-persona-baseline-testimony-turn-[a-f0-9]{20}$/',$securityTurnId))throw new \InvalidArgumentException('S860_SECURITY_TURN_ID_INVALID');
        $security=$this->read('testimony-turns',$securityTurnId,'S861_SECURITY_TURN_ABSENT');
        $deposition=$this->read('depositions',(string)($security['deposition_id']??''),'S862_BASELINE_AGGREGATION_CHAIN_INVALID');
        $witness=$this->read('persona-witnesses',(string)($security['manifestation_id']??''),'S862_BASELINE_AGGREGATION_CHAIN_INVALID');
        $practice=$this->referenced($security['source_first_testimony_turn']??null,'practice');
        $governance=$this->referenced($security['source_governance_testimony_turn']??null,'governance');
        $consistency=$this->referenced($security['source_consistency_testimony_turn']??null,'consistency');
        $ordered=[$practice,$governance,$consistency,$security];$turns=array_map(fn(array $turn):array=>$this->reference($turn),$ordered);
        $statuses=['FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS','GOVERNANCE_BASELINE_TESTIMONY_SEALED_PENDING_CONSISTENCY_QUESTION','CONSISTENCY_BASELINE_TESTIMONY_SEALED_PENDING_SECURITY_QUESTION','SECURITY_BASELINE_TESTIMONY_SEALED_PENDING_BASELINE_AGGREGATION'];
        $valid=($security['deposition_digest']??null)===($deposition['record_digest']??null)&&($security['manifestation_digest']??null)===($witness['record_digest']??null)
            &&CanonicalJson::encode(array_slice($turns,0,3))===CanonicalJson::encode($security['prior_testimony']??null)&&false===($deposition['execution_authority']??null);
        foreach($ordered as$index=>$turn)$valid=$valid&&$this->validator->isIntact($turn)&&['practice','governance','consistency','security'][$index]===($turn['jurisdiction']??null)&&$statuses[$index]===($turn['status']??null)
            &&true===($turn['testimony_sealed']??null)&&true===($turn['testimony_authority_consumed']??null)&&($turn['deposition_digest']??null)===($deposition['record_digest']??null)&&($turn['manifestation_digest']??null)===($witness['record_digest']??null)
            &&null===($turn['senator_finding']??null)&&null===($turn['senate_disposition']??null)&&false===($turn['execution_authority']??null);
        if(!$valid)throw new \RuntimeException('S862_BASELINE_AGGREGATION_CHAIN_INVALID');
        $firstId=(string)$security['source_first_testimony_turn']['id'];
        $id='senate-persona-jurisdiction-baseline-'.substr(hash('sha256',CanonicalJson::encode([$firstId,$practice['record_digest'],$securityTurnId,$security['record_digest'],array_column($turns,'turn_digest')])),0,20);
        return$this->records->put('var/imperium/offices/senate/jurisdiction-baselines',$id,[
            'schema'=>'imperium.senate-persona-jurisdiction-baseline/v1','baseline_id'=>$id,'instance_id'=>$deposition['instance_id'],'deposition_id'=>$deposition['deposition_id'],'deposition_digest'=>$deposition['record_digest'],
            'manifestation_id'=>$witness['manifestation_id'],'manifestation_digest'=>$witness['record_digest'],'candidate_id'=>$deposition['candidate_id'],'candidate_digest'=>$deposition['candidate_digest'],
            'originating_guildhall_commission_id'=>$deposition['originating_guildhall_commission_id'],'originating_guildhall_commission_digest'=>$deposition['originating_guildhall_commission_digest'],'review_target_lineage'=>$deposition['review_target_lineage'],
            'first_turn_id'=>$firstId,'first_turn_digest'=>$practice['record_digest'],'source_security_turn'=>['id'=>$securityTurnId,'digest'=>$security['record_digest']],
            'jurisdictions'=>['practice','governance','consistency','security'],'turns'=>$turns,'status'=>'REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS','additional_trials_required'=>true,
            'senator_findings'=>[],'aggregate_score'=>null,'vote'=>null,'senate_disposition'=>null,'admission_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }
    private function referenced(mixed $reference,string $jurisdiction):array{if(!is_array($reference)||!is_string($reference['id']??null)||!is_string($reference['digest']??null))throw new \RuntimeException('S862_BASELINE_AGGREGATION_CHAIN_INVALID');$turn=$this->read('testimony-turns',$reference['id'],'S862_BASELINE_AGGREGATION_CHAIN_INVALID');if($reference['digest']!==($turn['record_digest']??null)||$jurisdiction!==($turn['jurisdiction']??null))throw new \RuntimeException('S862_BASELINE_AGGREGATION_CHAIN_INVALID');return$turn;}
    private function reference(array $turn):array{return['jurisdiction'=>$turn['jurisdiction'],'assignment'=>$turn['assignment'],'question'=>$turn['question'],'testimony'=>$turn['testimony'],'question_dispatched_unchanged'=>$turn['question_dispatched_unchanged'],'question_authority_consumed'=>$turn['question_authority_consumed'],'testimony_sealed'=>$turn['testimony_sealed'],'senator_finding'=>null,'turn_digest'=>$turn['record_digest']];}
    private function read(string $directory,string $id,string $error):array{$record=$this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json',$error);return$this->validator->requireIntact($record,$error);}
}
