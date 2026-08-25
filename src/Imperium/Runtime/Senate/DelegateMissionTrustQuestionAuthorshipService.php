<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionTrustQuestionAuthorshipService
{
    private DelegateMissionJurisdictionQuestionAuthorshipEngine $engine;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        ProfileExaminationQuestionCognitionGateway $cognition,
    ) {
        $this->engine = new DelegateMissionJurisdictionQuestionAuthorshipEngine($root, $cognition);
    }

    public function author(string $dispositionId, string $trustSenatorBindingId, \DateTimeImmutable $authoredAt): array
    {
        return $this->engine->author('trust', $dispositionId, $trustSenatorBindingId, $authoredAt);
    }
}
