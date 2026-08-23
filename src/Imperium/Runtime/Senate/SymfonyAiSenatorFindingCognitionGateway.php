<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiSenatorFindingCognitionGateway
    implements SenatorFindingCognitionGateway
{
    public function __construct(
        #[Autowire(service: "ai.agent.senator_finding_practice")]
        private AgentInterface $practice,
        #[Autowire(service: "ai.agent.senator_finding_governance")]
        private AgentInterface $governance,
        #[Autowire(service: "ai.agent.senator_finding_consistency")]
        private AgentInterface $consistency,
        #[Autowire(service: "ai.agent.senator_finding_security")]
        private AgentInterface $security,
    ) {}

    public function find(
        string $jurisdiction,
        array $assignment,
        array $evidence,
    ): array {
        $agent = match ($jurisdiction) {
            "practice" => $this->practice,
            "governance" => $this->governance,
            "consistency" => $this->consistency,
            "security" => $this->security,
            default => throw new \RuntimeException(
                "S175_SENATOR_FINDING_COGNITION_INVALID",
            ),
        };
        $prompt = implode("\n", [
            "Exact attributable finding assignment: " . $this->encode($assignment),
            "Exact jurisdiction-competent evidence: " . $this->encode($evidence),
            "Interpret only this evidence. Do not vote, aggregate scores, suppress disagreement, issue the Senate disposition, or create admission authority.",
            "Return only JSON with exactly: disposition, evidence_references, rationale, severity, limitations, mandatory_failure.",
            "disposition must be PASS, CONCERN, FAIL, or UNRESOLVED. severity must be NONE, LOW, MEDIUM, HIGH, or CRITICAL.",
            "Only Security may set mandatory_failure true; if true, disposition must be FAIL and severity CRITICAL.",
        ]);
        $content = $agent
            ->call(new MessageBag(Message::ofUser($prompt)))
            ->getContent();
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException("S175_SENATOR_FINDING_COGNITION_INVALID");
        }
        $content = trim($content);
        if (str_starts_with($content, chr(96) . chr(96) . chr(96))) {
            $content = preg_replace('/^\x60\x60\x60(?:json)?\s*|\s*\x60\x60\x60$/i', "", $content) ?? $content;
        }
        try {
            $finding = json_decode(trim($content), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException("S175_SENATOR_FINDING_COGNITION_INVALID", 0, $exception);
        }
        return is_array($finding)
            ? $finding
            : throw new \RuntimeException("S175_SENATOR_FINDING_COGNITION_INVALID");
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
