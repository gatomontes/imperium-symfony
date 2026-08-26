<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;

final readonly class SymfonyAiSubordinatePersonaSpecificationCognitionGateway implements SubordinatePersonaSpecificationCognitionGateway
{
    public function __construct(private FoundryGovernanceCognitionInvoker $invoker){

    }
    public function specify(array $case):array{
        $prompt=implode("\n",
        ['Exact construction case: '.json_encode($case,
        JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        'Produce only a bounded Persona specification for this one case. Preserve every subordinate requirement. Do not claim an existing Persona, write or approve a Profile, spawn, admit, bind, or execute.',
        'Return only one JSON object with exactly these keys: disposition, persona_name, purpose, identity_constraints, competencies, ' .
        'behavioral_directives, evidence_obligations, explicit_exclusions, source_requirements, return_contracts, stop_conditions.',
        'disposition must be PERSONA_SPECIFICATION_COMPLETE or CLARIFICATION_REQUIRED. persona_name and purpose must be non-empty strings. Every ' .
        'other field must be an array of explicit non-empty strings. A complete specification requires at least one item in identity_constraints, ' .
        'competencies, behavioral_directives, evidence_obligations, explicit_exclusions, and stop_conditions.']);
        $content=$this->invoker->invoke('persona-specification', (string)($case['case_id']??''), 'foundry.artificer', [$case], $prompt);
        if(!is_string($content)||
        ''===trim($content))throw new \RuntimeException('F107_PERSONA_SPECIFICATION_COGNITION_EMPTY');
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
            throw new \RuntimeException('F108_PERSONA_SPECIFICATION_COGNITION_INVALID',
            0,
            $e);

        }
        $expected=['behavioral_directives',
        'competencies',
        'disposition',
        'evidence_obligations',
        'explicit_exclusions',
        'identity_constraints',
        'persona_name',
        'purpose',
        'return_contracts',
        'source_requirements',
        'stop_conditions'];
        $keys=is_array($r)?array_keys($r):[];
        sort($keys,
        SORT_STRING);
        if($expected!==$keys||
        !in_array($r['disposition']??null,
        ['PERSONA_SPECIFICATION_COMPLETE',
        'CLARIFICATION_REQUIRED'],
        true)||
        !is_string($r['persona_name']??null)||
        ''===trim($r['persona_name'])||
        !is_string($r['purpose']??null)||
        ''===trim($r['purpose']))throw new \RuntimeException('F109_PERSONA_SPECIFICATION_CONTRACT_INVALID');
        foreach(array_diff($expected,
        ['disposition',
        'persona_name',
        'purpose'])as$f){
            if(!is_array($r[$f]))throw new \RuntimeException('F109_PERSONA_SPECIFICATION_CONTRACT_INVALID');
            foreach($r[$f]as$i)if(!is_string($i)||
            ''===trim($i))throw new \RuntimeException('F109_PERSONA_SPECIFICATION_CONTRACT_INVALID');

        }
        if('PERSONA_SPECIFICATION_COMPLETE'===$r['disposition'])foreach(['identity_constraints',
        'competencies',
        'behavioral_directives',
        'evidence_obligations',
        'explicit_exclusions',
        'stop_conditions']as$f)if([]===$r[$f])throw new \RuntimeException('F109_PERSONA_SPECIFICATION_CONTRACT_INVALID');
        return$r;

    }

}
