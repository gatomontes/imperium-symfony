<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalClaimBoundCredentialBroker
{
    private const CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private const REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';

    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $root,
        private CredentialBroker $credentials,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function claimFor(array $authorization, array $manifestation, \DateTimeImmutable $at): array
    {
        $authorizationId = $authorization['authorization_id'] ?? null;
        $authorizationDigest = $authorization['record_digest'] ?? null;
        if (!is_string($authorizationId) || !preg_match('/^bounded-execution-authorization-[a-f0-9]{20}$/', $authorizationId)
            || !is_string($authorizationDigest)
            || ($authorization['manifestation'] ?? null) !== $manifestation) {
            throw new \RuntimeException('M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE');
        }

        $matches = [];
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            try {
                $claim = $this->validator->read($path, 'M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE');
                $request = $this->validator->resolve(
                    $this->root.'/'.self::REQUESTS,
                    $claim['source_cognition_request'] ?? [],
                    'M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE',
                    'M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE',
                    'request_id',
                );
            } catch (\Throwable) {
                continue;
            }
            if (($request['source_bounded_execution_authorization']['id'] ?? null) === $authorizationId
                && ($request['source_bounded_execution_authorization']['digest'] ?? null) === $authorizationDigest
                && ($request['target'] ?? null) === ($claim['target'] ?? null)) {
                $matches[] = $claim;
            }
        }

        if (1 !== count($matches) || !$this->isAuthorized($matches[0], $at)) {
            throw new \RuntimeException('M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE');
        }

        return $matches[0];
    }

    public function consume(array $claim, \DateTimeImmutable $at, callable $providerOperation): mixed
    {
        $claimId = $claim['claim_id'] ?? null;
        if (!is_string($claimId) || !preg_match('/^operational-cognition-invocation-claim-[a-f0-9]{20}$/', $claimId)) {
            throw new \RuntimeException('CLV450_OPERATIONAL_CREDENTIAL_GRANT_INVALID');
        }
        try {
            $stored = $this->validator->read(
                $this->root.'/'.self::CLAIMS.'/'.$claimId.'.json',
                'CLV450_OPERATIONAL_CREDENTIAL_GRANT_INVALID',
            );
            $valid = $this->validator->isIntact($stored)
                && hash_equals(CanonicalJson::encode($stored), CanonicalJson::encode($claim))
                && $this->isAuthorized($stored, $at);
        } catch (\Throwable) {
            throw new \RuntimeException('CLV450_OPERATIONAL_CREDENTIAL_GRANT_INVALID');
        }
        if (!$valid) {
            throw new \RuntimeException('CLV450_OPERATIONAL_CREDENTIAL_GRANT_INVALID');
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
        $authority = $claim['cognition_authority_consumption'] ?? [];

        return $this->validator->isIntact($claim)
            && 'imperium.clavium-operational-cognition-invocation-claim/v1' === ($claim['schema'] ?? null)
            && 'OPERATIONAL_INVOCATION_CLAIMED_DURABLE_PRE_IO' === ($claim['status'] ?? null)
            && true === ($lease['consumed'] ?? null)
            && false === ($lease['continuing_authority'] ?? null)
            && is_string($lease['expires_at'] ?? null)
            && new \DateTimeImmutable($lease['expires_at']) > $at
            && true === ($authority['consumed'] ?? null)
            && false === ($authority['continuing_authority'] ?? null)
            && DeepSeekDelegatePlatformAdapter::PROVIDER === ($claim['provider'] ?? null)
            && DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL === ($claim['model'] ?? null)
            && false === ($claim['provider_request']['external_io_started'] ?? null)
            && is_string($claim['provider_request']['idempotency_identity'] ?? null)
            && false === ($claim['credential_resolved'] ?? null)
            && false === ($claim['credential_material_present'] ?? null)
            && false === ($claim['network_access_performed'] ?? null)
            && false === ($claim['execution_continuation_authority'] ?? null)
            && false === ($claim['recovery']['automatic_replay_permitted'] ?? null);
    }
}
