<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LegateClaimBoundCredentialBroker
{
    private string $activations;
    private string $claims;
    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
        private CredentialBroker $credentials,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->activations = $root.'/var/imperium/offices/clavium/citadel-legate-provider-invocation-activations';
        $this->claims = $root.'/var/imperium/operational/citadel-legate-provider-invocation-claims';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function consume(array $activation, array $claim, \DateTimeImmutable $at, callable $providerOperation): mixed
    {
        try {
            $storedActivation = $this->authoritative($activation, 'activation_id', '/^citadel-legate-provider-invocation-activation-[a-f0-9]{20}$/', $this->activations);
            $storedClaim = $this->authoritative($claim, 'claim_id', '/^citadel-legate-provider-invocation-claim-[a-f0-9]{20}$/', $this->claims);
            $valid = $this->isAuthorized($storedActivation, $storedClaim, $at);
        } catch (\Throwable) {
            throw new \RuntimeException('CLV431_LEGATE_CREDENTIAL_GRANT_INVALID');
        }
        if (!$valid) {
            throw new \RuntimeException('CLV431_LEGATE_CREDENTIAL_GRANT_INVALID');
        }

        $capability = $this->credentials->issue(
            DeepSeekDelegatePlatformAdapter::CREDENTIAL_REFERENCE,
            $storedClaim['claim_id'],
            DeepSeekDelegatePlatformAdapter::OPERATION,
            new \DateTimeImmutable($storedActivation['credential_lease']['expires_at']),
        );

        return $this->credentials->consume($capability, $providerOperation);
    }

    private function authoritative(array $presented, string $identity, string $pattern, string $directory): array
    {
        $id = $presented[$identity] ?? null;
        if (!is_string($id) || !preg_match($pattern, $id)) {
            throw new \RuntimeException('CLV431_LEGATE_CREDENTIAL_GRANT_INVALID');
        }
        $stored = $this->validator->read($directory.'/'.$id.'.json', 'CLV431_LEGATE_CREDENTIAL_GRANT_INVALID');
        if (!$this->validator->isIntact($stored)
            || !hash_equals(CanonicalJson::encode($stored), CanonicalJson::encode($presented))) {
            throw new \RuntimeException('CLV431_LEGATE_CREDENTIAL_GRANT_INVALID');
        }

        return $stored;
    }

    private function isAuthorized(array $activation, array $claim, \DateTimeImmutable $at): bool
    {
        $lease = $activation['credential_lease'] ?? [];

        return 'CITADEL_LEGATE_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN' === ($activation['status'] ?? null)
            && true === ($activation['provider_invocation_authority'] ?? null)
            && true === ($activation['credential_use_authority'] ?? null)
            && false === ($activation['provider_invoked'] ?? null)
            && true === ($lease['authority_single_use'] ?? null)
            && false === ($lease['consumed'] ?? null)
            && false === ($lease['credential_reference_disclosed'] ?? null)
            && false === ($lease['credential_possession_transferred'] ?? null)
            && 'deepseek' === ($lease['provider'] ?? null)
            && is_string($lease['expires_at'] ?? null)
            && new \DateTimeImmutable($lease['expires_at']) > $at
            && 'imperium.citadel-legate-provider-invocation-claim/v1' === ($claim['schema'] ?? null)
            && 'PROVIDER_INVOCATION_CLAIMED_AT_MOST_ONCE' === ($claim['status'] ?? null)
            && false === ($claim['replay_permitted'] ?? null)
            && ($activation['activation_id'] ?? null) === ($claim['source_provider_activation']['id'] ?? null)
            && ($activation['record_digest'] ?? null) === ($claim['source_provider_activation']['digest'] ?? null);
    }
}
