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
            'Return only JSON with exactly: disposition, attributed_defect, evidence_references, rationale, severity, limitations, uncertainty.',
            'disposition must be PASS, CONCERN, FAIL, or UNRESOLVED. severity must be NONE, LOW, MEDIUM, HIGH, or CRITICAL. attributed_defect must be null for PASS and one exact rubric category otherwise.',
        ]);
        $content = $agent->call(new MessageBag(Message::ofUser($prompt)))->getContent();
        if (!is_string($content) || '' === trim($content)) throw new \RuntimeException('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $finding = json_decode(trim($content), true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new \RuntimeException('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID', 0, $exception); }
        return is_array($finding) ? $finding : throw new \RuntimeException('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID');
    }
}
