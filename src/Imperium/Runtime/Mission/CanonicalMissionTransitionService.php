<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Authority-bearing entry point whose verifier is fixed by Runtime construction. */
final readonly class CanonicalMissionTransitionService
{
    private AuthenticatedMissionAuthorizationBridge $bridge;
    private MissionCapabilityVerifier $verifier;
    private MissionLifecycleStore $lifecycles;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->bridge = new AuthenticatedMissionAuthorizationBridge($root);
        $this->verifier = new MissionCapabilityVerifier($root);
        $this->lifecycles = new MissionLifecycleStore($root);
    }

    /** Verification-only cut until durable transition consumption is introduced in Batch 3. */
    public function verify(MissionCapability $capability, string $authorizationId, \DateTimeImmutable $at): array
    {
        $authorization = $this->bridge->authenticate($authorizationId, $at);
        $transition = $this->transition($authorization, $capability);
        $this->verifier->verify($capability, $authorization, $transition, $at);
        return $transition;
    }

    public function consume(MissionCapability $capability, string $authorizationId, \DateTimeImmutable $at): array
    {
        $authorization = $this->bridge->authenticate($authorizationId, $at);
        $transition = $this->transition($authorization, $capability);
        $this->verifier->verify($capability, $authorization, $transition, $at);
        return $this->lifecycles->consume($capability, $authorization, $at);
    }

    private function transition(AuthenticatedMissionAuthorization $authorization, MissionCapability $capability): array
    {
        foreach ($authorization->mission->toArray()['lifecycle_transitions'] as $transition) {
            if ($transition['action'] === $capability->get('action')) { return $transition; }
        }
        throw new \RuntimeException('MIS413_CAPABILITY_BINDING_MISMATCH');
    }
}
