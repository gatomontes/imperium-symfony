<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionUsabilityQuestionDispatchAuthorizationService
{
    private DelegateMissionQuestionDispatchAuthorizationEngine $engine;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->engine = new DelegateMissionQuestionDispatchAuthorizationEngine($root);
    }

    public function decide(string $questionId, string $lordSpeakerBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        return $this->engine->decide('usability', $questionId, $lordSpeakerBindingId, $disposition, $rationale, $decidedAt);
    }
}
