<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Imperium\Runtime\Cognition\BoundedTransientCognitionCaller;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiProfileElaborationCognitionGateway implements ProfileElaborationCognitionGateway
{
    private const array FIELDS = [
        'disposition', 'operating_posture', 'responsibilities', 'non_responsibilities',
        'reasoning_priorities', 'evidence_discipline', 'tool_use_directives',
        'input_handling', 'output_contract', 'escalation_conditions',
        'uncertainty_behavior', 'failure_behavior', 'persona_adaptations',
    ];

    public function __construct(#[Autowire(service: 'ai.agent.alchemist_profile_elaboration')] private AgentInterface $alchemist, private ?BoundedTransientCognitionCaller $transientCaller = null) {}

    public function elaborate(array $acceptance, array $authorization): array
    {
        $prompt = implode("\n", [
            'Exact accepted Profile-derivation commission: '.$this->encode($acceptance),
            'Exact Imperator authorization and limitations: '.$this->encode($authorization),
            'Elaborate only the mission-specific cognitive Profile content for the exact Persona and immutable Profile scope supplied above.',
            'Adapt the Persona to the mission without changing identity, profession, scope, constraints, stop conditions, authority, custody, tools, data access, or return destination.',
            'Do not version, seal, approve, install, assemble, examine, spawn, bind, deploy, execute, use tools, or claim that any of those actions occurred.',
            'Return only JSON with exactly: disposition, operating_posture, responsibilities, non_responsibilities, reasoning_priorities, evidence_discipline, tool_use_directives, input_handling, output_contract, escalation_conditions, uncertainty_behavior, failure_behavior, persona_adaptations.',
            'disposition must be PROFILE_ELABORATION_COMPLETE. operating_posture must be a non-empty string. Every other field must be a non-empty array of explicit non-empty strings.',
        ]);
        $content = ($this->transientCaller ?? new BoundedTransientCognitionCaller())->call($this->alchemist, $prompt, 'L40_PROFILE_ELABORATION_COGNITION_INVALID');
        if (!is_string($content) || '' === trim($content)) throw new \RuntimeException('L40_PROFILE_ELABORATION_COGNITION_INVALID');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $elaboration = json_decode(trim($content), true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new \RuntimeException('L40_PROFILE_ELABORATION_COGNITION_INVALID', 0, $exception); }
        $keys = is_array($elaboration) ? array_keys($elaboration) : []; sort($keys, SORT_STRING);
        $expected = self::FIELDS; sort($expected, SORT_STRING);
        if ($expected !== $keys || 'PROFILE_ELABORATION_COMPLETE' !== ($elaboration['disposition'] ?? null)
            || !is_string($elaboration['operating_posture'] ?? null) || '' === trim($elaboration['operating_posture'])) {
            throw new \RuntimeException('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        }
        foreach (array_diff(self::FIELDS, ['disposition', 'operating_posture']) as $field) {
            if (!is_array($elaboration[$field]) || [] === $elaboration[$field]) throw new \RuntimeException('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
            foreach ($elaboration[$field] as $item) if (!is_string($item) || '' === trim($item)) throw new \RuntimeException('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        }
        return $elaboration;
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
