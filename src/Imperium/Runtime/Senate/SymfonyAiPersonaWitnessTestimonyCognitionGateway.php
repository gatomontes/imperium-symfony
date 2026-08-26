<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;

final readonly class SymfonyAiPersonaWitnessTestimonyCognitionGateway
    implements PersonaWitnessTestimonyCognitionGateway
{
    public function __construct(
        private GovernanceCognitionInvoker $cognition,
    ) {}

    public function authorQuestion(
        array $assignment,
        array $deposition,
        array $witness,
    ): array {
        $jurisdiction = $assignment["jurisdiction"] ?? null;
        if (!in_array($jurisdiction, ['practice', 'governance', 'consistency', 'security'], true)) throw new \RuntimeException('S135_SENATOR_QUESTION_COGNITION_INVALID');
        $authorityId = (string) ($assignment['authority_id'] ?? '');
        $authorityType = (string) ($assignment['cognition_authority_type'] ?? 'question-'.$jurisdiction);
        $specialQuestionAuthority = match ($jurisdiction) {
            'consistency' => 'question-fresh-consistency',
            'governance' => 'question-pressure-governance',
            default => null,
        };
        if ('question-'.$jurisdiction !== $authorityType && $specialQuestionAuthority !== $authorityType) throw new \RuntimeException('S135_SENATOR_QUESTION_COGNITION_INVALID');
        $prompt = implode("\n", [
            "Exact attributable Senator assignment: " . $this->encode($assignment),
            "Exact secured deposition: " . $this->encode($deposition),
            "Exact Persona witness identity and Persona: " . $this->encode($witness),
            "Author one bounded " . $jurisdiction . "-jurisdiction question for the assigned trial. Do not answer it, make a finding, or dictate a disposition.",
            "Return only JSON with exactly: question_set_id, trial_id, purpose, question.",
        ]);
        $content = $this->cognition->invoke('senate-persona-confirmation', $authorityType, $authorityId, 'senate.committee.'.$jurisdiction, 'author-persona-question', [$assignment, $deposition, $witness], $prompt);
        return $this->decode($content, 'S135_SENATOR_QUESTION_COGNITION_INVALID');
    }

    public function answer(
        array $question,
        array $deposition,
        array $witness,
    ): array {
        $questionPayload = isset($question['question_record_id']) ? ($question['question'] ?? []) : $question;
        if (!is_array($questionPayload)) throw new \RuntimeException('S136_PERSONA_WITNESS_COGNITION_INVALID');
        $prompt = implode("\n", [
            "You are the exact sterile Persona-only witness on the Senate stand.",
            "Exact elaborated Persona and witness constraints: " . $this->encode($witness),
            "Exact deposition boundary: " . $this->encode($deposition),
            "Exact attributable question: " . $this->encode($questionPayload),
            "Answer only from the elaborated Persona. You have no Profile, Officer substrate, Seat, tools, credentials, mission authority, or external-action authority.",
            "Return only JSON with exactly: answer, uncertainties, refusals, evidence_claims.",
        ]);
        $authorityId = (string) ($question['testimony_authority']['authority_id'] ?? '');
        $jurisdiction = (string) ($question['jurisdiction'] ?? 'practice');
        if (!in_array($jurisdiction, ['practice', 'governance', 'consistency', 'security'], true)) throw new \RuntimeException('S136_PERSONA_WITNESS_COGNITION_INVALID');
        $authorityType = (string) ($question['cognition_authority_type'] ?? 'testimony-'.$jurisdiction);
        $specialTestimonyAuthority = match ($jurisdiction) {
            'consistency' => 'testimony-fresh-consistency',
            'governance' => 'testimony-pressure-governance',
            default => null,
        };
        if ('testimony-'.$jurisdiction !== $authorityType && $specialTestimonyAuthority !== $authorityType) throw new \RuntimeException('S136_PERSONA_WITNESS_COGNITION_INVALID');
        $content = $this->cognition->invoke('senate-persona-confirmation', $authorityType, $authorityId, 'senate.stand', 'answer-persona-question', [$questionPayload, $deposition, $witness], $prompt);
        return $this->decode($content, "S136_PERSONA_WITNESS_COGNITION_INVALID");
    }

    private function decode(mixed $content, string $error): array
    {
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException($error);
        }
        $content = trim($content);
        if (str_starts_with($content, chr(96) . chr(96) . chr(96))) {
            $content = preg_replace('/^\x60\x60\x60(?:json)?\s*|\s*\x60\x60\x60$/i', "", $content) ?? $content;
        }
        try {
            $decoded = json_decode(trim($content), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException($error, 0, $exception);
        }
        return is_array($decoded)
            ? $decoded
            : throw new \RuntimeException($error);
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
