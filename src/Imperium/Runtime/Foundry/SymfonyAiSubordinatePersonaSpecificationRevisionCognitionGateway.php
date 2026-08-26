<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;


final readonly class SymfonyAiSubordinatePersonaSpecificationRevisionCognitionGateway
    implements SubordinatePersonaSpecificationRevisionCognitionGateway
{
    public function __construct(private FoundryGovernanceCognitionInvoker $invoker) {}

    public function revise(
        array $case,
        array $priorSpecification,
        array $revisionReturn,
    ): array {
        $prompt = implode("\n", [
            "Exact construction case: " .
            json_encode($case, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            "Exact prior Persona specification: " .
            json_encode(
                $priorSpecification,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Exact revision return: " .
            json_encode(
                $revisionReturn,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Revise only the bounded Persona specification. Preserve the complete return and prior revision basis verbatim in lineage; do not answer any clarification or correction by inventing facts.",
            "A Persona under construction has no Garrison personnel record. Remove or correct any requirement that asks Hagiography or Studium to obtain Garrison personnel facts about that unfinished Persona. Garrison holds admitted Personas; Foundry owns this construction.",
            "Preserve every valid inherited requirement. Do not claim an existing Persona, write or approve a Profile, spawn, admit, bind, or execute.",
            "Return only one JSON object with exactly these keys: disposition, persona_name, purpose, identity_constraints, competencies, behavioral_directives, evidence_obligations, explicit_exclusions, source_requirements, return_contracts, stop_conditions.",
            "disposition must be PERSONA_SPECIFICATION_COMPLETE or CLARIFICATION_REQUIRED. persona_name and purpose must be non-empty strings. Every other field must be an array of explicit non-empty strings.",
        ]);
        $content = $this->invoker->invoke('persona-specification-revision', (string) ($revisionReturn['return_id'] ?? ''), 'foundry.artificer', [$case, $priorSpecification, $revisionReturn], $prompt);
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException(
                "F128_PERSONA_SPECIFICATION_REVISION_COGNITION_EMPTY",
            );
        }
        $content = trim($content);
        if (str_starts_with($content, "```")) {
            $content =
                preg_replace('/^```(?:json)?\s*|\s*```$/i', "", $content) ??
                $content;
        }
        try {
            $decision = json_decode(
                trim($content),
                true,
                32,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                "F129_PERSONA_SPECIFICATION_REVISION_COGNITION_INVALID",
                0,
                $exception,
            );
        }
        $expected = [
            "behavioral_directives",
            "competencies",
            "disposition",
            "evidence_obligations",
            "explicit_exclusions",
            "identity_constraints",
            "persona_name",
            "purpose",
            "return_contracts",
            "source_requirements",
            "stop_conditions",
        ];
        $keys = is_array($decision) ? array_keys($decision) : [];
        sort($keys, SORT_STRING);
        if (
            $expected !== $keys ||
            !in_array(
                $decision["disposition"] ?? null,
                ["PERSONA_SPECIFICATION_COMPLETE", "CLARIFICATION_REQUIRED"],
                true,
            ) ||
            !is_string($decision["persona_name"] ?? null) ||
            "" === trim($decision["persona_name"]) ||
            !is_string($decision["purpose"] ?? null) ||
            "" === trim($decision["purpose"])
        ) {
            throw new \RuntimeException(
                "F130_PERSONA_SPECIFICATION_REVISION_CONTRACT_INVALID",
            );
        }
        foreach (
            array_diff($expected, ["disposition", "persona_name", "purpose"])
            as $field
        ) {
            if (!is_array($decision[$field])) {
                throw new \RuntimeException(
                    "F130_PERSONA_SPECIFICATION_REVISION_CONTRACT_INVALID",
                );
            }
            foreach ($decision[$field] as $item) {
                if (!is_string($item) || "" === trim($item)) {
                    throw new \RuntimeException(
                        "F130_PERSONA_SPECIFICATION_REVISION_CONTRACT_INVALID",
                    );
                }
            }
        }

        return $decision;
    }
}
