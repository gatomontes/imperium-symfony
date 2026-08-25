<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSecurityQuestionCommissionDispositionService
{
    private DelegateMissionSubsequentQuestionCommissionDispositionEngine $engine;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->engine = new DelegateMissionSubsequentQuestionCommissionDispositionEngine($root);
    }

    public function decide(string $commissionId, string $securitySenatorBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        return $this->engine->decide('security', $commissionId, $securitySenatorBindingId, $disposition, $rationale, $decidedAt);
    }
}
