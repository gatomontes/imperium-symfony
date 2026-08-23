<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Cognition\BoundedTransientCognitionCaller;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiProfileExaminationTestimonyCognitionGateway implements ProfileExaminationTestimonyCognitionGateway
{
    public function __construct(#[Autowire(service: 'ai.agent.profile_examination_witness')] private AgentInterface $witness, private ?BoundedTransientCognitionCaller $transientCaller = null) {}

    public function answer(array $question, array $manifestation): array
    {
        $questionForPrompt = $question;
        unset($questionForPrompt['manifestation']);
        $prompt = implode("\n", [
            'You are the exact examination-only Manifestation secured on senate.stand.',
            'Exact sealed question record, with its duplicated manifestation field omitted only from this prompt: '.json_encode($questionForPrompt, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Exact Manifestation: '.json_encode($manifestation, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'The examination-only Profile includes candidate_content and candidate_scope copied unchanged from the sealed Profile candidate. Treat those fields as the substantive evidence under examination, not merely their identity metadata.',
            'Answer the exact question only from the supplied Persona, complete sealed Profile candidate content and scope, and generic Officer substrate. Cite concrete supplied directives or contracts when relevant. Do not claim evidence is absent when it is present in candidate_content or candidate_scope. Preserve the custody, authority, tool, credential, external-action, and return boundaries. Do not make a finding, deliberate, approve, install operationally, bind, deploy, use tools, or execute.',
            'Return one JSON object with exactly four fields and these exact types: answer must be one non-empty string; uncertainties, refusals, and evidence_claims must each be an array containing only non-empty strings. Use [] when a list has no entries. Do not return null, nested objects, markdown, commentary, or additional fields.',
            'Exact response shape: {"answer":"...","uncertainties":[],"refusals":[],"evidence_claims":["..."]}',
        ]);
        $content = ($this->transientCaller ?? new BoundedTransientCognitionCaller())->call($this->witness, $prompt, 'S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID');
        if (!is_string($content)) throw $this->invalid('NON_TEXT_RESPONSE');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $answer = json_decode(trim($content), true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw $this->invalid('JSON_INVALID', $exception); }
        if (!is_array($answer) || array_is_list($answer)) throw $this->invalid('ROOT_NOT_OBJECT');
        $keys = array_keys($answer); sort($keys, SORT_STRING);
        if (['answer', 'evidence_claims', 'refusals', 'uncertainties'] !== $keys) throw $this->invalid('FIELDS_INVALID');
        if (!is_string($answer['answer']) || '' === trim($answer['answer'])) throw $this->invalid('ANSWER_INVALID');
        $normalized = ['answer' => trim($answer['answer'])];
        foreach (['uncertainties', 'refusals', 'evidence_claims'] as $field) {
            $values = $answer[$field];
            if (is_string($values) && '' !== trim($values)) $values = [$values];
            if (!is_array($values) || !array_is_list($values)) throw $this->invalid(strtoupper($field).'_TYPE_INVALID');
            $normalized[$field] = [];
            foreach ($values as $value) {
                if (!is_string($value) || '' === trim($value)) throw $this->invalid(strtoupper($field).'_ITEM_INVALID');
                $normalized[$field][] = trim($value);
            }
        }
        return $normalized;
    }

    private function invalid(string $reason, ?\Throwable $previous = null): \RuntimeException
    {
        return new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: '.$reason, 0, $previous);
    }
}
