<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ClaimBoundCredentialBroker
{
    private string $claims;
    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
        private CredentialBroker $credentials,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->claims = $root.'/var/imperium/runtime/provider-invocations';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function consume(array $claim, \DateTimeImmutable $at, callable $providerOperation): mixed
    {
        $claimId = $claim['claim_id'] ?? null;
        if (!is_string($claimId) || !preg_match('/^provider-invocation-[a-f0-9]{20}$/', $claimId)) {
            throw new \RuntimeException('CLV430_CREDENTIAL_GRANT_INVALID');
        }

        try {
            $stored = $this->validator->read(
                $this->claims.'/'.$claimId.'.json',
                'CLV430_CREDENTIAL_GRANT_INVALID',
            );
            $valid = $this->validator->isIntact($stored)
                && hash_equals(CanonicalJson::encode($stored), CanonicalJson::encode($claim))
                && $this->isAuthorized($stored, $at);
        } catch (\Throwable) {
            throw new \RuntimeException('CLV430_CREDENTIAL_GRANT_INVALID');
        }
        if (!$valid) {
            throw new \RuntimeException('CLV430_CREDENTIAL_GRANT_INVALID');
        }

        $capability = $this->credentials->issue(
            DeepSeekDelegatePlatformAdapter::CREDENTIAL_REFERENCE,
            $claimId,
            DeepSeekDelegatePlatformAdapter::OPERATION,
            new \DateTimeImmutable($stored['lease_consumption']['expires_at']),
        );

        return $this->credentials->consume($capability, $providerOperation);
    }

    private function isAuthorized(array $claim, \DateTimeImmutable $at): bool
    {
        $lease = $claim['lease_consumption'] ?? [];
        $turn = $claim['turn_authority_consumption'] ?? [];
        $runtime = $claim['model']['runtime_binding'] ?? [];

        return 'imperium.clavium-provider-invocation-claim/v1' === ($claim['schema'] ?? null)
            && 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO' === ($claim['status'] ?? null)
            && true === ($lease['consumed'] ?? null)
            && false === ($lease['continuing_authority'] ?? null)
            && is_string($lease['lease_id'] ?? null)
            && is_string($lease['expires_at'] ?? null)
            && new \DateTimeImmutable($lease['expires_at']) > $at
            && true === ($turn['consumed'] ?? null)
            && false === ($turn['continuing_authority'] ?? null)
            && is_string($turn['authority_id'] ?? null)
            && DeepSeekDelegatePlatformAdapter::PROVIDER === ($runtime['provider'] ?? null)
            && DeepSeekDelegatePlatformAdapter::PLATFORM_SERVICE === ($runtime['platform_service'] ?? null)
            && DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL === ($runtime['runtime_model'] ?? null)
            && false === ($claim['provider_request']['external_io_started'] ?? null)
            && is_string($claim['provider_request']['idempotency_key'] ?? null)
            && false === ($claim['credential_material_present'] ?? null);
    }
}
