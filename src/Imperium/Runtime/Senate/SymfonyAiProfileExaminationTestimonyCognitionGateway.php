<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiProfileExaminationTestimonyCognitionGateway implements ProfileExaminationTestimonyCognitionGateway
{
    public function __construct(#[Autowire(service: 'ai.agent.profile_examination_witness')] private AgentInterface $witness) {}

    public function answer(array $question, array $manifestation): array
    {
        $prompt = implode("\n", [
            'You are the exact examination-only Manifestation secured on senate.stand.',
            'Exact sealed question record: '.json_encode($question, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Exact Manifestation: '.json_encode($manifestation, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Answer the exact question only from the supplied Persona, Profile candidate, and generic Officer substrate. Preserve the custody, authority, tool, credential, external-action, and return boundaries. Do not make a finding, deliberate, approve, install, bind, deploy, use tools, or execute.',
            'Return only JSON with exactly: answer, uncertainties, refusals, evidence_claims.',
        ]);
        $content = $this->witness->call(new MessageBag(Message::ofUser($prompt)))->getContent();
        if (!is_string($content) || '' === trim($content)) throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $answer = json_decode(trim($content), true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID', 0, $exception); }
        return is_array($answer) ? $answer : throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID');
    }
}
