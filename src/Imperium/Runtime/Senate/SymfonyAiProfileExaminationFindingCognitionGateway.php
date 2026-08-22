<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiProfileExaminationFindingCognitionGateway implements ProfileExaminationFindingCognitionGateway
{
    public function __construct(
        #[Autowire(service: 'ai.agent.profile_finding_trust')] private AgentInterface $trust,
        #[Autowire(service: 'ai.agent.profile_finding_security')] private AgentInterface $security,
        #[Autowire(service: 'ai.agent.profile_finding_usability')] private AgentInterface $usability,
    ) {}

    public function find(string $jurisdiction, array $authority, array $evidence): array
    {
        $agent = match ($jurisdiction) {
            'trust' => $this->trust,
            'security' => $this->security,
            'usability' => $this->usability,
            default => throw new \RuntimeException('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID'),
        };
        $prompt = implode("\n", [
            'Exact jurisdiction-bound finding authority: '.json_encode($authority, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Only admissible evidence: '.json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Issue one attributable finding limited to this jurisdiction and evidence. Apply only the supplied defect-attribution rubric.',
            'Do not inspect another jurisdiction, deliberate, reconcile disagreement, vote, aggregate, issue a Senate disposition, approve, install, bind, deploy, or execute.',
            'Return one JSON object with exactly seven fields and these exact types: disposition, rationale, and severity must each be one non-empty string; attributed_defect must be null for PASS and one exact rubric-category string otherwise; evidence_references, limitations, and uncertainty must each be an array containing only non-empty strings. Use [] when a list has no entries. Do not return nested objects, markdown, commentary, or additional fields.',
            'disposition must be exactly PASS, CONCERN, FAIL, or UNRESOLVED. severity must be exactly NONE, LOW, MEDIUM, HIGH, or CRITICAL. For PASS, severity must be NONE.',
            'Copy the one supplied available_evidence_references value exactly into evidence_references.',
            'Exact PASS response shape: {"disposition":"PASS","attributed_defect":null,"evidence_references":["testimony:<jurisdiction>:<digest>"],"rationale":"...","severity":"NONE","limitations":[],"uncertainty":[]}',
        ]);
        $content = $agent->call(new MessageBag(Message::ofUser($prompt)))->getContent();
        if (!is_string($content)) throw $this->invalid('NON_TEXT_RESPONSE');
        if ('' === trim($content)) throw $this->invalid('EMPTY_RESPONSE');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $finding = json_decode(trim($content), true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw $this->invalid('JSON_INVALID', $exception); }
        if (!is_array($finding) || array_is_list($finding)) throw $this->invalid('ROOT_NOT_OBJECT');
        $keys = array_keys($finding); sort($keys, SORT_STRING);
        if (['attributed_defect', 'disposition', 'evidence_references', 'limitations', 'rationale', 'severity', 'uncertainty'] !== $keys) throw $this->invalid('FIELDS_INVALID');
        foreach (['disposition', 'rationale', 'severity'] as $field) {
            if (!is_string($finding[$field]) || '' === trim($finding[$field])) throw $this->invalid(strtoupper($field).'_INVALID');
            $finding[$field] = trim($finding[$field]);
        }
        $finding['disposition'] = strtoupper($finding['disposition']);
        $finding['severity'] = strtoupper($finding['severity']);
        if (is_string($finding['attributed_defect'])) {
            $finding['attributed_defect'] = trim($finding['attributed_defect']);
            if ('' === $finding['attributed_defect']) throw $this->invalid('ATTRIBUTED_DEFECT_INVALID');
        } elseif (null !== $finding['attributed_defect']) {
            throw $this->invalid('ATTRIBUTED_DEFECT_INVALID');
        }
        foreach (['evidence_references', 'limitations', 'uncertainty'] as $field) {
            $values = $finding[$field];
            if (is_string($values) && '' !== trim($values)) $values = [$values];
            if (!is_array($values) || !array_is_list($values)) throw $this->invalid(strtoupper($field).'_TYPE_INVALID');
            $finding[$field] = [];
            foreach ($values as $value) {
                if (!is_string($value) || '' === trim($value)) throw $this->invalid(strtoupper($field).'_ITEM_INVALID');
                $finding[$field][] = trim($value);
            }
        }
        return $finding;
    }

    private function invalid(string $reason, ?\Throwable $previous = null): \RuntimeException
    {
        return new \RuntimeException('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID: '.$reason, 0, $previous);
    }
}
