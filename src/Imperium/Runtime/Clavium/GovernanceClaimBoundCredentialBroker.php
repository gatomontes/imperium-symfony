<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernanceClaimBoundCredentialBroker
{
    private const CLAIMS = 'var/imperium/runtime/governance-cognition-invocation-claims';
    private const REQUESTS = 'var/imperium/runtime/governance-cognition-requests';
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private CredentialBroker $credentials, ?RecordReferenceValidator $validator = null)
    { $this->validator = $validator ?? new RecordReferenceValidator($root); }

    public function claimFor(string $cluster, string $authorityType, string $authorityId, string $seat, string $purpose, string $inputDigest, \DateTimeImmutable $at): array
    {
        $matches = [];
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            try {
                $claim = $this->validator->read($path, 'GCA450_GOVERNANCE_PROVIDER_CLAIM_UNAVAILABLE');
                $request = $this->validator->resolve($this->root.'/'.self::REQUESTS, $claim['source_cognition_request'] ?? [], 'GCA450_GOVERNANCE_PROVIDER_CLAIM_UNAVAILABLE', 'GCA450_GOVERNANCE_PROVIDER_CLAIM_UNAVAILABLE', 'request_id');
            } catch (\Throwable) { continue; }
            if ($cluster === ($request['cluster'] ?? null) && $authorityType === ($request['authority_type'] ?? null)
                && $authorityId === ($request['authority_identity'] ?? null) && ['seat' => $seat, 'purpose' => $purpose] === ($request['target'] ?? null)
                && $inputDigest === ($request['input_digest'] ?? null) && ($request['target'] ?? null) === ($claim['target'] ?? null)
                && $cluster === ($claim['cluster'] ?? null) && $inputDigest === ($claim['input_digest'] ?? null)
                && ($request['source_governance_authority'] ?? null) === ($claim['source_governance_authority'] ?? null)) { $matches[] = $claim; }
        }
        if (1 !== count($matches) || !$this->authorized($matches[0], $at)) { throw new \RuntimeException('GCA450_GOVERNANCE_PROVIDER_CLAIM_UNAVAILABLE'); }
        return $matches[0];
    }

    public function consume(array $claim, \DateTimeImmutable $at, callable $operation): mixed
    {
        $id = $claim['claim_id'] ?? null;
        try { $stored = is_string($id) ? $this->validator->read($this->root.'/'.self::CLAIMS.'/'.$id.'.json', 'GCA451_GOVERNANCE_CREDENTIAL_GRANT_INVALID') : []; }
        catch (\Throwable) { throw new \RuntimeException('GCA451_GOVERNANCE_CREDENTIAL_GRANT_INVALID'); }
        if (CanonicalJson::encode($stored) !== CanonicalJson::encode($claim) || !$this->authorized($stored, $at)) { throw new \RuntimeException('GCA451_GOVERNANCE_CREDENTIAL_GRANT_INVALID'); }
        $capability = $this->credentials->issue(DeepSeekDelegatePlatformAdapter::CREDENTIAL_REFERENCE, (string) $id, DeepSeekDelegatePlatformAdapter::OPERATION, new \DateTimeImmutable($stored['lease_consumption']['expires_at']));
        return $this->credentials->consume($capability, $operation);
    }

    private function authorized(array $claim, \DateTimeImmutable $at): bool
    {
        return $this->validator->isIntact($claim) && 'imperium.clavium-governance-cognition-invocation-claim/v1' === ($claim['schema'] ?? null)
            && 'GOVERNANCE_INVOCATION_CLAIMED_DURABLE_PRE_IO' === ($claim['status'] ?? null) && true === ($claim['lease_consumption']['consumed'] ?? null)
            && new \DateTimeImmutable((string) ($claim['lease_consumption']['expires_at'] ?? '1970-01-01')) > $at
            && true === ($claim['governance_authority_consumption']['consumed'] ?? null) && DeepSeekDelegatePlatformAdapter::PROVIDER === ($claim['provider'] ?? null)
            && DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL === ($claim['model'] ?? null) && false === ($claim['provider_request']['external_io_started'] ?? null)
            && false === ($claim['credential_resolved'] ?? null) && false === ($claim['credential_material_present'] ?? null)
            && false === ($claim['network_access_performed'] ?? null) && false === ($claim['continuing_authority'] ?? null)
            && false === ($claim['lease_consumption']['continuing_authority'] ?? null)
            && false === ($claim['governance_authority_consumption']['continuing_authority'] ?? null)
            && false === ($claim['recovery']['automatic_replay_permitted'] ?? null);
    }
}
