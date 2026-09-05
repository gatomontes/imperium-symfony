<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Issues exact mission-transition capabilities only after authenticating persisted authority. */
final readonly class MissionCapabilityIssuanceService
{
    public const string ISSUER = 'imperium.runtime.canonical-mission-capability-issuer/v1';
    private AuthenticatedMissionAuthorizationBridge $bridge;
    private MissionCapabilityKeyStore $keys;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->bridge = new AuthenticatedMissionAuthorizationBridge($root);
        $this->keys = new MissionCapabilityKeyStore($root);
        $this->atomic = new AtomicTransition($root);
    }

    /** @return list<MissionCapability> */
    public function issue(string $authorizationId, \DateTimeImmutable $at): array
    {
        $authorization = $this->bridge->authenticate($authorizationId, $at);

        return $this->atomic->run('canonical-mission-capability-issuer', function () use ($authorization, $at): array {
            $key = $this->keys->initialize();
            $capabilities = [];
            foreach ($authorization->mission->toArray()['lifecycle_transitions'] as $transition) {
                $unsigned = [
                    'schema' => MissionCapability::SCHEMA,
                    'capability_id' => '',
                    'authorization_id' => $authorization->authorizationId,
                    'authorization_digest' => $authorization->authorizationDigest,
                    'dossier_id' => $authorization->dossierId,
                    'dossier_digest' => $authorization->dossierDigest,
                    'mission_id' => $authorization->mission->id(),
                    'mission_digest' => $authorization->mission->digest(),
                    'action' => $transition['action'],
                    'actor' => $transition['actor'],
                    'target' => $transition['target'],
                    'issuer' => self::ISSUER,
                    'required_state' => $transition['from'],
                    'resulting_state' => $transition['to'],
                    'not_before' => $at->getTimestamp(),
                    'expires_at' => $authorization->mission->expiresAt()->getTimestamp(),
                    'nonce' => bin2hex(random_bytes(16)),
                    'signature' => '',
                ];
                $unsigned['capability_id'] = 'mission-capability-'.hash('sha256', CanonicalJson::encode($unsigned));
                $unsigned['signature'] = hash_hmac('sha256', CanonicalJson::encode($unsigned), $key);
                $capabilities[] = MissionCapability::fromArray($unsigned);
            }
            return $capabilities;
        });
    }
}
