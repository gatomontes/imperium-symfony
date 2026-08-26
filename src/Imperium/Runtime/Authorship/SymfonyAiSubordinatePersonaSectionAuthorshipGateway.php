<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Authorship;
use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;

final readonly class SymfonyAiSubordinatePersonaSectionAuthorshipGateway implements SubordinatePersonaSectionAuthorshipGateway{
    public function __construct(private GovernanceCognitionInvoker $invoker){

    }
    public function author(string$office,
    array$acceptance,
    array$commission,
    array$specification,
    array$case):array{
        [$type,$seat,$boundary]=match($office){
            'hagiography'=>['hagiography-section-authorship','hagiography.sanctographer','Author only attributable evidence-derived Persona sections. Do not write governance doctrine.'],
            'studium'=>['studium-section-authorship','studium.chancellor','Author only Persona Governance Doctrine sections. Do not derive evidentiary traits.'],
            default=>throw new \InvalidArgumentException('A105_AUTHORSHIP_OFFICE_INVALID'),
        };
        $prompt=implode("\n",
        [$boundary,
        'Exact acceptance: '.json_encode($acceptance,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Exact commission: '.json_encode($commission,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Sealed Persona specification: '.json_encode($specification,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Construction case: '.json_encode($case,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Preserve every inherited requirement and explicit exclusion. Do not assemble or approve a complete Persona or Profile; do not select, spawn, admit, bind, or execute.',
        'Return only one JSON object with exactly: disposition, authored_sections, source_citations, unresolved_questions. disposition must be ' .
        'SECTION_PACKET_COMPLETE or CLARIFICATION_REQUIRED. authored_sections must be a non-empty object whose values are explicit strings or arrays ' .
        'of explicit strings. source_citations and unresolved_questions must be arrays of explicit strings.']);
        $content=$this->invoker->invoke('section-authorship',$type,(string)($acceptance['acceptance_id']??''),$seat,'author-persona-sections',[$acceptance,$commission,$specification,$case],$prompt);
        if(''===trim($content))throw new \RuntimeException('A103_SUBORDINATE_AUTHORSHIP_EMPTY');
        $content=trim($content);
        if(str_starts_with($content,
        '```'))$content=preg_replace('/^```(?:json)?\s*|\s*```$/i',
        '',
        $content)??$content;
        try{
            $r=json_decode(trim($content),
            true,
            32,
            JSON_THROW_ON_ERROR);

        }
        catch(\JsonException$e){
            throw new \RuntimeException('A104_SUBORDINATE_AUTHORSHIP_INVALID',
            0,
            $e);

        }
        return is_array($r)?$r:throw new \RuntimeException('A104_SUBORDINATE_AUTHORSHIP_INVALID');

    }

}
