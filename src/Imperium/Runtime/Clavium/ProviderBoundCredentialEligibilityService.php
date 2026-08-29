<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBoundCredentialEligibilityService
{
    public const string ELIGIBILITIES = 'var/imperium/offices/clavium/provider-bound-credential-eligibilities';
    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        private ProviderCredentialFamilyPolicy $policy,
    ) {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function assess(array $binding, CredentialCapability $capability, \DateTimeImmutable $at): array
    {
        $digest = $binding['record_digest'] ?? null;
        $unsealed = $binding;
        unset($unsealed['record_digest']);
        $provider = $binding['provider_implementation']['provider_id'] ?? null;
        $family = $binding['credential_family']['family_id'] ?? null;
        $targetId = $binding['scope']['authorization_target_id'] ?? null;
        $targetDigest = $binding['scope']['authorization_target_digest'] ?? null;
        $effectiveAt = new \DateTimeImmutable((string) ($binding['validity']['effective_at'] ?? '1970-01-01'));
        $bindingExpiresAt = new \DateTimeImmutable((string) ($binding['validity']['expires_at'] ?? '1970-01-01'));

        if (!is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($unsealed)))
            || ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($binding)
            || ProviderImplementationBindingContract::SCHEMA !== ($binding['schema'] ?? null)
            || 'BOUND_INACTIVE' !== ($binding['status'] ?? null)
            || !is_string($provider) || !is_string($family)
            || $provider !== ($binding['credential_family']['provider_id'] ?? null)
            || false !== ($binding['credential_family']['secret_persistence_permitted'] ?? null)
            || false !== ($binding['scope']['provider_substitution_permitted'] ?? null)
            || !$this->policy->supports($provider, $family)
            || !$this->policy->acceptsReference($capability->credentialRef)
            || $targetId !== $capability->commissionId
            || ($binding['scope']['operation'] ?? null) !== $capability->operation
            || 'email.send' !== $capability->operation
            || 1 !== $capability->maxUses
            || $effectiveAt > $at
            || $bindingExpiresAt <= $at
            || $capability->expiresAt <= $at
            || !is_string($targetDigest) || !preg_match('/^[a-f0-9]{64}$/', $targetDigest)) {
            throw new \RuntimeException('GTP500_PROVIDER_BOUND_CREDENTIAL_INELIGIBLE');
        }

        $bindingReference = ['id' => $binding['binding_id'], 'digest' => $digest, 'schema' => $binding['schema']];
        $eligibilityId = 'provider-bound-credential-eligibility-'.substr(hash('sha256', CanonicalJson::encode([$bindingReference, $capability->metadata()])), 0, 20);

        return $this->records->put(self::ELIGIBILITIES, $eligibilityId, [
            'schema' => ProviderBoundCredentialEligibilityContract::SCHEMA,
            'eligibility_id' => $eligibilityId,
            'instance_id' => $binding['instance_id'],
            'provider_binding' => $bindingReference,
            'authorization_target' => ['id' => $targetId, 'digest' => $targetDigest],
            'credential_capability' => ['capability_id' => $capability->capabilityId, 'credential_reference_digest' => hash('sha256', $capability->credentialRef), 'operation' => $capability->operation, 'max_uses' => $capability->maxUses],
            'provider' => $provider,
            'credential_family' => $family,
            'status' => 'ELIGIBLE_INACTIVE',
            'assessed_at' => $at->format(DATE_ATOM),
            'expires_at' => ($capability->expiresAt <= $bindingExpiresAt ? $capability->expiresAt : $bindingExpiresAt)->format(DATE_ATOM),
            'credential_resolved' => false,
            'external_io_permitted' => false,
            'sealed' => true,
        ]);
    }
}
