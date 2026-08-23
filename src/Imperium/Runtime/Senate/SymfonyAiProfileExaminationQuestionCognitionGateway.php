<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Cognition\BoundedTransientCognitionCaller;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiProfileExaminationQuestionCognitionGateway implements ProfileExaminationQuestionCognitionGateway
{
    public function __construct(
        #[Autowire(service: 'ai.agent.profile_examiner_trust')] private AgentInterface $trust,
        #[Autowire(service: 'ai.agent.profile_examiner_security')] private AgentInterface $security,
        #[Autowire(service: 'ai.agent.profile_examiner_usability')] private AgentInterface $usability,
        private ?BoundedTransientCognitionCaller $transientCaller = null,
    ) {}

    public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
    {
        $agent = match ($jurisdiction) {
            'trust' => $this->trust,
            'security' => $this->security,
            'usability' => $this->usability,
            default => throw new \RuntimeException('S220_PROFILE_EXAMINATION_QUESTION_COGNITION_INVALID'),
        };
        $prompt = implode("\n", [
            'Exact accepted jurisdiction-bound commission: '.json_encode($commission, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Exact opened testimony context: '.json_encode($opening, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Author one exact question limited to the '.$jurisdiction.' jurisdiction and the shared defect-attribution rubric.',
            'The question must permit attribution to one rubric category or insufficient evidence. Do not dispatch or answer it, make a finding, deliberate, approve, install, bind, deploy, or execute.',
            'Return only JSON with exactly: purpose, question.',
        ]);
        $content = ($this->transientCaller ?? new BoundedTransientCognitionCaller())->call($agent, $prompt, 'S220_PROFILE_EXAMINATION_QUESTION_COGNITION_INVALID');
        if (!is_string($content) || '' === trim($content)) throw new \RuntimeException('S220_PROFILE_EXAMINATION_QUESTION_COGNITION_INVALID');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $question = json_decode(trim($content), true, 8, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new \RuntimeException('S220_PROFILE_EXAMINATION_QUESTION_COGNITION_INVALID', 0, $exception); }
        return is_array($question) ? $question : throw new \RuntimeException('S220_PROFILE_EXAMINATION_QUESTION_COGNITION_INVALID');
    }
}
