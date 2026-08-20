<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiPersonaWitnessTestimonyCognitionGateway
    implements PersonaWitnessTestimonyCognitionGateway
{
    public function __construct(
        #[Autowire(service: "ai.agent.senator_practice")]
        private AgentInterface $senator,
        #[Autowire(service: "ai.agent.persona_witness")]
        private AgentInterface $witness,
    ) {}

    public function authorQuestion(
        array $assignment,
        array $deposition,
        array $witness,
    ): array {
        return $this->json($this->senator, implode("\n", [
            "Exact attributable Senator assignment: " . $this->encode($assignment),
            "Exact secured deposition: " . $this->encode($deposition),
            "Exact Persona witness identity and Persona: " . $this->encode($witness),
            "Author one bounded Practice-jurisdiction question for the first trial. Do not answer it, make a finding, or dictate a disposition.",
            "Return only JSON with exactly: question_set_id, trial_id, purpose, question.",
        ]), "S135_SENATOR_QUESTION_COGNITION_INVALID");
    }

    public function answer(
        array $question,
        array $deposition,
        array $witness,
    ): array {
        return $this->json($this->witness, implode("\n", [
            "You are the exact sterile Persona-only witness on the Senate stand.",
            "Exact elaborated Persona and witness constraints: " . $this->encode($witness),
            "Exact deposition boundary: " . $this->encode($deposition),
            "Exact attributable question: " . $this->encode($question),
            "Answer only from the elaborated Persona. You have no Profile, Officer substrate, Seat, tools, credentials, mission authority, or external-action authority.",
            "Return only JSON with exactly: answer, uncertainties, refusals, evidence_claims.",
        ]), "S136_PERSONA_WITNESS_COGNITION_INVALID");
    }

    private function json(AgentInterface $agent, string $prompt, string $error): array
    {
        $content = $agent
            ->call(new MessageBag(Message::ofUser($prompt)))
            ->getContent();
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
