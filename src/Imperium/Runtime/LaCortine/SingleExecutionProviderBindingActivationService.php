<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Imperator\ProviderBindingActivationAuthorityContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SingleExecutionProviderBindingActivationService
{
    public const string ACTIVATIONS = 'var/imperium/offices/la-cortine/single-execution-provider-binding-activations';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function activate(string $authorityId, \DateTimeImmutable $activatedAt): array
    {
        if (!preg_match('/^provider-binding-activation-authority-[a-f0-9]{20}$/', $authorityId)) throw new \InvalidArgumentException('PBA400_ACTIVATION_AUTHORITY_ID_INVALID');

        return $this->atomic->run('provider-binding-single-execution-activation:'.$authorityId, function () use ($authorityId, $activatedAt): array {
            [$issuance, $authority] = $this->findAuthority($authorityId);
            $this->assertAuthority($authorityId, $issuance, $authority, $activatedAt);
            $claim = $this->validator->resolve($this->root.'/'.DeterministicExecutionClaimService::CLAIMS, $authority['execution_claim'], 'PBA403_EXECUTION_CLAIM_ABSENT', 'PBA404_EXECUTION_CLAIM_MISMATCH', 'claim_id');
            $binding = $this->validator->resolve($this->root.'/'.ProviderImplementationBindingService::BINDINGS, $authority['provider_binding'], 'PBA405_PROVIDER_BINDING_ABSENT', 'PBA406_PROVIDER_BINDING_MISMATCH', 'binding_id');
            if (DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim) || ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($binding) || 'CLAIMED_PRE_IO' !== ($claim['effect']['checkpoint'] ?? null) || false !== ($claim['effect']['external_io_started'] ?? null) || 'BOUND_INACTIVE' !== ($binding['status'] ?? null) || new \DateTimeImmutable($claim['expires_at']) <= $activatedAt || new \DateTimeImmutable($binding['validity']['expires_at']) <= $activatedAt || $authority['tool_authority'] !== $binding['tool_operation'] || $authority['assurance_profile'] !== $binding['assurance_profile'] || $authority['destination_policy'] !== $binding['destination_policy'] || ($authority['effect_authorization']['id'] ?? null) !== ($claim['source_authorization']['id'] ?? null) || ($authority['effect_authorization']['digest'] ?? null) !== ($claim['source_authorization']['digest'] ?? null) || ($authority['scope']['execution_id'] ?? null) !== ($claim['execution_identity']['execution_id'] ?? null) || ($authority['scope']['operation'] ?? null) !== ($claim['request']['operation'] ?? null) || ($authority['scope']['exact_destination'] ?? null) !== ($claim['request']['destination'] ?? null) || false !== ($authority['scope']['provider_substitution_permitted'] ?? null)) throw new \RuntimeException('PBA407_ACTIVATION_LINEAGE_INVALID');

            $activationId = 'single-execution-provider-binding-activation-'.substr(hash('sha256', $authorityId.'|'.$authority['record_digest']), 0, 20);
            return $this->records->put(self::ACTIVATIONS, $activationId, ['schema' => SingleExecutionProviderBindingActivationContract::SCHEMA, 'activation_id' => $activationId, 'instance_id' => $authority['instance_id'], 'source_authority' => ['id' => $authorityId, 'digest' => $authority['record_digest'], 'schema' => $authority['schema']], 'tool_authority' => $authority['tool_authority'], 'effect_authorization' => $authority['effect_authorization'], 'execution_claim' => $authority['execution_claim'], 'provider_binding' => $authority['provider_binding'], 'assurance_profile' => $authority['assurance_profile'], 'destination_policy' => $authority['destination_policy'], 'scope' => $authority['scope'], 'activation_authority_consumption' => ['authority_id' => $authorityId, 'authority_digest' => $authority['record_digest'], 'consumed_at' => $activatedAt->format(DATE_ATOM), 'consumed' => true, 'continuing_authority' => false], 'status' => 'ACTIVATED_UNCONSUMED', 'activated_at' => $activatedAt->format(DATE_ATOM), 'expires_at' => $authority['expires_at'], 'single_execution' => true, 'sealed' => true]);
        });
    }

    private function findAuthority(string $authorityId): array
    {
        foreach (glob($this->root.'/'.ProviderBindingActivationIssuanceService::ISSUANCES.'/*.json') ?: [] as $path) {
            $issuance = $this->validator->read($path, 'PBA401_ACTIVATION_ISSUANCE_INVALID');
            if (($issuance['issued_activation_authority']['authority_id'] ?? null) === $authorityId) return [$issuance, $issuance['issued_activation_authority']];
        }
        throw new \RuntimeException('PBA402_ACTIVATION_AUTHORITY_ABSENT');
    }

    private function assertAuthority(string $authorityId, array $issuance, array $authority, \DateTimeImmutable $at): void
    {
        if (!$this->validator->isIntact($issuance) || ProviderBindingActivationIssuanceContract::REQUIRED_ISSUANCE_FIELDS !== array_keys($issuance) || !$this->validator->isIntact($authority) || ProviderBindingActivationAuthorityContract::REQUIRED_FIELDS !== array_keys($authority) || ProviderBindingActivationAuthorityContract::SCHEMA !== ($authority['schema'] ?? null) || $authorityId !== ($authority['authority_id'] ?? null) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null) || false !== ($authority['consumed'] ?? null) || false !== ($authority['continuing_authority'] ?? null) || ProviderBindingActivationAuthorityContract::REQUIRED_SCOPE_FIELDS !== array_keys($authority['scope'] ?? []) || new \DateTimeImmutable($authority['issued_at']) > $at || new \DateTimeImmutable($authority['expires_at']) <= $at || true !== ($issuance['authority_issued'] ?? null) || false !== ($issuance['provider_binding_activated'] ?? null) || false !== ($issuance['credential_capability_issued'] ?? null) || false !== ($issuance['external_action_performed'] ?? null)) throw new \RuntimeException('PBA401_ACTIVATION_ISSUANCE_INVALID');
    }
}
