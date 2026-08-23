<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Cognition\BoundedTransientCognitionCaller;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiProfileExaminationReconciliationCognitionGateway implements ProfileExaminationReconciliationCognitionGateway
{
    public function __construct(#[Autowire(service:'ai.agent.profile_examination_reconciliation')] private AgentInterface $agent, private ?BoundedTransientCognitionCaller $transientCaller = null) {}

    public function reconcile(array $authority, array $findings): array
    {
        $prompt=implode("\n",[
            'Exact bounded reconciliation authority: '.json_encode($authority,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            'Complete admitted sealed findings: '.json_encode($findings,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            'Reconcile agreement and disagreement without modifying a finding, voting, counting, averaging, scoring, aggregating, suppressing dissent, recommending a disposition, or issuing a disposition.',
            'Return one JSON object with exactly eight fields: finding_references, agreements, disagreements, attribution_treatment, severity_treatment, limitations, and uncertainties must each be arrays containing only non-empty strings; rationale must be one non-empty string. Use [] for an empty list. No nulls, nested objects, markdown, commentary, recommendations, or additional fields.',
            'Copy all three supplied available_finding_references values exactly into finding_references.',
            'Exact response shape: {"finding_references":["finding:security:<digest>","finding:trust:<digest>","finding:usability:<digest>"],"agreements":[],"disagreements":[],"attribution_treatment":["..."],"severity_treatment":["..."],"limitations":[],"uncertainties":[],"rationale":"..."}',
        ]);
        $content=($this->transientCaller??new BoundedTransientCognitionCaller())->call($this->agent,$prompt,'S262_PROFILE_EXAMINATION_RECONCILIATION_COGNITION_INVALID');
        if(!is_string($content))throw $this->invalid('NON_TEXT_RESPONSE');
        if(''===trim($content))throw $this->invalid('EMPTY_RESPONSE');
        $content=trim($content);
        if(str_starts_with($content,'```'))$content=preg_replace('/^```(?:json)?\s*|\s*```$/i','',$content)??$content;
        try{$result=json_decode(trim($content),true,16,JSON_THROW_ON_ERROR);}catch(\JsonException $exception){throw $this->invalid('JSON_INVALID',$exception);}
        if(!is_array($result)||array_is_list($result))throw $this->invalid('ROOT_NOT_OBJECT');
        $keys=array_keys($result);sort($keys,SORT_STRING);
        if(['agreements','attribution_treatment','disagreements','finding_references','limitations','rationale','severity_treatment','uncertainties']!==$keys)throw $this->invalid('FIELDS_INVALID');
        if(!is_string($result['rationale'])||''===trim($result['rationale']))throw $this->invalid('RATIONALE_INVALID');
        $normalized=['rationale'=>trim($result['rationale'])];
        foreach(['finding_references','agreements','disagreements','attribution_treatment','severity_treatment','limitations','uncertainties'] as $field){$values=$result[$field];if(is_string($values)&&''!==trim($values))$values=[$values];if(!is_array($values)||!array_is_list($values))throw $this->invalid(strtoupper($field).'_TYPE_INVALID');$normalized[$field]=[];foreach($values as $value){if(!is_string($value)||''===trim($value))throw $this->invalid(strtoupper($field).'_ITEM_INVALID');$normalized[$field][]=trim($value);}}
        $normalized['disagreements']=$this->preserveSealedFindingDisagreement($normalized['disagreements'],$findings);
        return ['finding_references'=>$normalized['finding_references'],'agreements'=>$normalized['agreements'],'disagreements'=>$normalized['disagreements'],'attribution_treatment'=>$normalized['attribution_treatment'],'severity_treatment'=>$normalized['severity_treatment'],'limitations'=>$normalized['limitations'],'uncertainties'=>$normalized['uncertainties'],'rationale'=>$normalized['rationale']];
    }

    private function preserveSealedFindingDisagreement(array $disagreements,array $findings):array
    {
        if([]!==$disagreements)return $disagreements;
        $signatures=[];
        foreach($findings as $finding){
            $jurisdiction=$finding['jurisdiction']??null;$decision=$finding['decision']??null;
            if(!is_string($jurisdiction)||!is_array($decision))continue;
            $signatures[$jurisdiction]=[$decision['disposition']??null,$decision['attributed_defect']??null,$decision['severity']??null];
        }
        if(2>count(array_unique(array_map(static fn(array $signature):string=>json_encode($signature,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$signatures))))return $disagreements;
        ksort($signatures,SORT_STRING);
        $parts=[];
        foreach($signatures as $jurisdiction=>$signature)$parts[]=$jurisdiction.'='.json_encode($signature,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        return ['Sealed finding signatures diverge: '.implode('; ',$parts).'.'];
    }

    private function invalid(string $reason,?\Throwable $previous=null):\RuntimeException{return new \RuntimeException('S262_PROFILE_EXAMINATION_RECONCILIATION_COGNITION_INVALID: '.$reason,0,$previous);}
}
