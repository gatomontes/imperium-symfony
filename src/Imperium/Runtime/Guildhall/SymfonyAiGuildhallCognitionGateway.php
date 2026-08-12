<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class SymfonyAiGuildhallCognitionGateway implements GuildhallCognitionGateway
{
    public function __construct(
        private AgentInterface $disciplinaryFit,
        private AgentInterface $composition,
        private AgentInterface $boundaryChallenge,
        private AgentInterface $guildmaster,
    ) {
    }

    public function deliberate(array $missionPlan, array $commissionScope, array $occupancy): array
    {
        $common = implode("\n", [
            'Mission Plan: '.json_encode($missionPlan, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Commission scope: '.json_encode($commissionScope, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Guildhall occupancy: '.json_encode($occupancy, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Return only one JSON object with exactly: disposition, findings, requirements, questions.',
            'disposition must be PASS or CLARIFICATION_REQUIRED. The other fields must be arrays of explicit strings.',
            'Do not invent available personnel, Personas, exemplars, credentials, tools, or Garrison inventory facts.',
        ]);
        $committee = [
            'disciplinary_fit' => $this->committee($this->disciplinaryFit, $common."\nDetermine required professions, disciplinary fitness, exemplar criteria, and evidence standards."),
            'composition' => $this->committee($this->composition, $common."\nDetermine role composition, quantities, independence requirements, collaboration needs, and conflicts."),
            'boundary_challenge' => $this->committee($this->boundaryChallenge, $common."\nChallenge professional boundaries, overreach risks, segregation requirements, and personnel-related stop conditions."),
        ];
        $prompt = implode("\n", [
            $common,
            'Committee records: '.json_encode($committee, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Synthesize without erasing disagreements. Return only one JSON object with exactly these keys:',
            'disposition: PROFESSION_DETERMINATION_COMPLETE or CLARIFICATION_REQUIRED',
            'rationale: non-empty string',
            'required_professions: array of explicit strings',
            'exemplar_criteria: array of explicit strings',
            'team_composition: array of explicit strings',
            'boundary_controls: array of explicit strings',
            'garrison_inventory_queries: array of exact inventory questions; required when determination is complete',
            'unresolved_questions: array of explicit strings',
            'This is not a final Personnel Disposition. No person may be declared suitable until exact Garrison facts return.',
        ]);
        $synthesis = $this->invoke($this->guildmaster, $prompt);
        $keys = array_keys($synthesis);
        sort($keys, SORT_STRING);
        $expected = ['boundary_controls', 'disposition', 'exemplar_criteria', 'garrison_inventory_queries', 'rationale', 'required_professions', 'team_composition', 'unresolved_questions'];
        if ($expected !== $keys
            || !in_array($synthesis['disposition'] ?? null, ['PROFESSION_DETERMINATION_COMPLETE', 'CLARIFICATION_REQUIRED'], true)
            || !is_string($synthesis['rationale'] ?? null)
            || '' === trim($synthesis['rationale'])
        ) {
            throw new \RuntimeException('G51_GUILDMASTER_CONTRACT_INVALID: synthesis violates its return contract.');
        }
        foreach (array_diff($expected, ['disposition', 'rationale']) as $field) {
            $this->stringArray($synthesis[$field] ?? null, 'G51_GUILDMASTER_CONTRACT_INVALID');
        }
        if ('PROFESSION_DETERMINATION_COMPLETE' === $synthesis['disposition']
            && ([] === $synthesis['required_professions'] || [] === $synthesis['garrison_inventory_queries'])
        ) {
            throw new \RuntimeException('G51_GUILDMASTER_CONTRACT_INVALID: complete determination requires professions and exact inventory queries.');
        }

        return ['committee' => $committee, 'guildmaster' => $synthesis];
    }

    private function committee(AgentInterface $agent, string $prompt): array
    {
        $decision = $this->invoke($agent, $prompt);
        $keys = array_keys($decision);
        sort($keys, SORT_STRING);
        if (['disposition', 'findings', 'questions', 'requirements'] !== $keys
            || !in_array($decision['disposition'] ?? null, ['PASS', 'CLARIFICATION_REQUIRED'], true)
        ) {
            throw new \RuntimeException('G50_COMMITTEE_CONTRACT_INVALID: committee response violates its return contract.');
        }
        foreach (['findings', 'requirements', 'questions'] as $field) {
            $this->stringArray($decision[$field] ?? null, 'G50_COMMITTEE_CONTRACT_INVALID');
        }

        return $decision;
    }

    private function invoke(AgentInterface $agent, string $prompt): array
    {
        $content = $agent->call(new MessageBag(Message::ofUser($prompt)))->getContent();
        if (!is_string($content) || '' === trim($content)) {
            throw new \RuntimeException('G49_GUILDHALL_COGNITION_EMPTY: cognition returned no disposition.');
        }
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        }
        try {
            $result = json_decode(trim($content), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('G49_GUILDHALL_COGNITION_INVALID: cognition did not return valid JSON.', 0, $exception);
        }
        if (!is_array($result)) {
            throw new \RuntimeException('G49_GUILDHALL_COGNITION_INVALID: cognition response must be an object.');
        }

        return $result;
    }

    private function stringArray(mixed $value, string $error): void
    {
        if (!is_array($value)) {
            throw new \RuntimeException($error.': expected an array.');
        }
        foreach ($value as $item) {
            if (!is_string($item) || '' === trim($item)) {
                throw new \RuntimeException($error.': entries must be explicit strings.');
            }
        }
    }
}
