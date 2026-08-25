<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSecurityTestimonyResponseService
{
    private DelegateMissionTestimonyResponseEngine $engine;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ProfileExaminationTestimonyCognitionGateway $cognition)
    {
        $this->engine = new DelegateMissionTestimonyResponseEngine($root, $cognition);
    }

    public function respond(string $dispatchId, \DateTimeImmutable $respondedAt): array
    {
        return $this->engine->respond('security', $dispatchId, $respondedAt);
    }
}
