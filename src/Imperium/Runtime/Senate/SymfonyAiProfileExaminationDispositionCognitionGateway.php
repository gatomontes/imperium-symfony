<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;

final readonly class SymfonyAiProfileExaminationDispositionCognitionGateway implements ProfileExaminationDispositionCognitionGateway{

public function __construct(private GovernanceCognitionInvoker$cognition){

    }

public function decide(array $authority,
    array $findings,
    array $reconciliation):array{
        $prompt=implode("\n",
        ['Exact disposition authority: '.json_encode($authority,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Exact sealed findings: '.json_encode($findings,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Exact sealed reconciliation: '.json_encode($reconciliation,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Issue one attributable verdict without voting, averaging, scoring, aggregation, or suppressed dissent. '.
        'A Security FAIL/CRITICAL prohibits APPROVED.',
        'Return one JSON object with exactly disposition, finding_references, rationale, reconciliation_treatment, limitations, and uncertainties. ' .
        'disposition must be APPROVED, RETURN_FOR_REVISION, REFUSED, or UNRESOLVED. Every other field must be a non-empty string except ' .
        'finding_references, limitations, and uncertainties, which must be arrays of non-empty strings; use [] for empty lists. Copy all ' .
        'available_finding_references exactly. No markdown or extra fields.',
        'Exact shape: {"disposition":"APPROVED","finding_references":["..."],"rationale":"...","reconciliation_treatment":"...","limitations":[],"uncertainties":[]}']);
        $content=$this->cognition->invoke(
        'senate-profile-examination',
        'disposition',
        (string)($authority['opening_id']??$authority['disposition_authority_id']??''),
        'senate.lord-speaker',
        'decide-profile-disposition',
        [$authority,$findings,$reconciliation],
        $prompt);
        if(!is_string($content))throw $this->invalid('NON_TEXT_RESPONSE');
        $content=trim($content);
        if(str_starts_with($content,
        '```'))$content=preg_replace('/^```(?:json)?\s*|\s*```$/i',
        '',
        $content)??$content;
        try{
            $d=json_decode(trim($content),
            true,
            16,
            JSON_THROW_ON_ERROR);

        }
        catch(\JsonException $e){
            throw $this->invalid('JSON_INVALID',
            $e);

        }
        if(!is_array($d)||
        array_is_list($d))throw $this->invalid('ROOT_NOT_OBJECT');
        $keys=array_keys($d);
        sort($keys,
        SORT_STRING);
        if(['disposition',
        'finding_references',
        'limitations',
        'rationale',
        'reconciliation_treatment',
        'uncertainties']!==$keys)throw $this->invalid('FIELDS_INVALID');
        foreach(['disposition',
        'rationale',
        'reconciliation_treatment']as$f){
            if(!is_string($d[$f])||
            ''===trim($d[$f]))throw $this->invalid(strtoupper($f).'_INVALID');
            $d[$f]=trim($d[$f]);

        }
        $d['disposition']=strtoupper($d['disposition']);
        foreach(['finding_references',
        'limitations',
        'uncertainties']as$f){
            $v=$d[$f];
            if(is_string($v)&&
            ''!==trim($v))$v=[$v];
            if(!is_array($v)||
            !array_is_list($v))throw $this->invalid(strtoupper($f).'_TYPE_INVALID');
            $d[$f]=[];
            foreach($v as$x){
                if(!is_string($x)||
                ''===trim($x))throw $this->invalid(strtoupper($f).'_ITEM_INVALID');
                $d[$f][]=trim($x);

            }

        }
        return $d;

    }

private function invalid(string$r,
    ?\Throwable$p=null):\RuntimeException{
        return new \RuntimeException('S280_PROFILE_EXAMINATION_DISPOSITION_COGNITION_INVALID: '.$r,
        0,
        $p);

    }

}
