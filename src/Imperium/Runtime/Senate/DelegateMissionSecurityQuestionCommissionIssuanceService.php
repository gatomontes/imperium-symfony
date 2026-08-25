<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSecurityQuestionCommissionIssuanceService
{
    private DelegateMissionSubsequentQuestionCommissionIssuanceEngine $engine;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->engine = new DelegateMissionSubsequentQuestionCommissionIssuanceEngine($root);
    }

    public function issue(string $turnId, string $lordSpeakerBindingId, string $securitySenatorBindingId, \DateTimeImmutable $issuedAt): array
    {
        return $this->engine->issue('security', $turnId, $lordSpeakerBindingId, $securitySenatorBindingId, $issuedAt);
    }
}
