<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiLordSpeakerDispositionCognitionGateway
    implements LordSpeakerDispositionCognitionGateway
{
    public function __construct(
        #[Autowire(service: "ai.agent.lord_speaker_disposition")]
        private AgentInterface $agent,
    ) {}

    public function decide(array $authority, array $findingSet): array
    {
        $prompt = implode("\n", [
            "Exact Lord Speaker authority: " . $this->encode($authority),
            "Complete sealed Senator finding set: " . $this->encode($findingSet),
            "Issue one attributable Senate disposition for the exact candidate. Do not vote, count a majority, aggregate a score, suppress disagreement, admit, spawn, bind, or execute.",
            "A Security mandatory failure absolutely prohibits CONFIRMED. Address all four finding digests and explain the treatment of conflicts or the absence of conflict.",
            "Return only JSON with exactly: disposition, finding_references, rationale, conflicting_findings_treatment, limitations.",
            "disposition must be CONFIRMED, RETURN_TO_FOUNDRY, REFUSED, or UNRESOLVED.",
        ]);
        $content = $this->agent
            ->call(new MessageBag(Message::ofUser($prompt)))
            ->getContent();
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException("S182_LORD_SPEAKER_COGNITION_INVALID");
        }
        $content = trim($content);
        if (str_starts_with($content, chr(96) . chr(96) . chr(96))) {
            $content = preg_replace('/^\x60\x60\x60(?:json)?\s*|\s*\x60\x60\x60$/i', "", $content) ?? $content;
        }
        try {
            $decision = json_decode(trim($content), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException("S182_LORD_SPEAKER_COGNITION_INVALID", 0, $exception);
        }
        return is_array($decision)
            ? $decision
            : throw new \RuntimeException("S182_LORD_SPEAKER_COGNITION_INVALID");
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
