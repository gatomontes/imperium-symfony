<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use App\Imperium\Runtime\Governance\InternalCognitionLeaseControls;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernanceCognitionLeaseService
{
    private const DECISIONS = 'var/imperium/imperator/governance-provider-resource-decisions';
    private const REQUESTS = 'var/imperium/runtime/governance-cognition-requests';
    private const OCCUPANCY = 'var/imperium/offices/clavium/occupancy';
    private const LEASES = 'var/imperium/offices/clavium/governance-cognition-leases';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, ?RecordReferenceValidator $validator = null, ?ImmutableRecordStore $records = null, ?AtomicTransition $atomic = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
    }

    public function issue(string $decisionId, string $activationAuthorityId, string $locksmithBindingId, \DateTimeImmutable $expiresAt, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^governance-provider-resource-decision-[a-f0-9]{20}$/', $decisionId)
            || !preg_match('/^governance-clavium-lease-activation-authority-[a-f0-9]{20}$/', $activationAuthorityId)
            || !preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $locksmithBindingId)
            || $expiresAt <= $issuedAt || $expiresAt > $issuedAt->modify('+5 minutes')) {
            throw new \InvalidArgumentException('GCA300_GOVERNANCE_LEASE_INPUT_INVALID');
        }
        $decision = $this->validator->read($this->root.'/'.self::DECISIONS.'/'.$decisionId.'.json', 'GCA301_GOVERNANCE_DECISION_ABSENT');
        $request = $this->validator->resolve($this->root.'/'.self::REQUESTS, $decision['source_cognition_request'] ?? [], 'GCA302_GOVERNANCE_REQUEST_ABSENT', 'GCA303_GOVERNANCE_LEASE_CHAIN_INVALID', 'request_id');
        $locksmith = $this->validator->read($this->root.'/'.self::OCCUPANCY.'/'.$locksmithBindingId.'.json', 'GCA304_LOCKSMITH_OCCUPANCY_ABSENT');
        $activation = $decision['clavium_lease_activation_authority'] ?? [];
        if (!$this->validator->isIntact($decision) || !$this->validator->isIntact($locksmith)
            || 'imperium.imperator-governance-provider-resource-decision/v1' !== ($decision['schema'] ?? null) || $decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null) || 'GOVERNANCE_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' !== ($decision['status'] ?? null)
            || $activationAuthorityId !== ($activation['authority_id'] ?? null) || true !== ($activation['authority_single_use'] ?? null)
            || true !== ($activation['authority_exercisable'] ?? null) || false !== ($activation['consumed'] ?? null)
            || new \DateTimeImmutable((string) ($decision['expires_at'] ?? '1970-01-01')) <= $issuedAt || $expiresAt > new \DateTimeImmutable($decision['expires_at'])
            || ($decision['cluster'] ?? null) !== ($request['cluster'] ?? null) || ($decision['target'] ?? null) !== ($request['target'] ?? null)
            || ($decision['source_governance_authority'] ?? null) !== ($request['source_governance_authority'] ?? null) || ($decision['input_digest'] ?? null) !== ($request['input_digest'] ?? null)
            || 'imperium.clavium-locksmith-occupancy/v1' !== ($locksmith['schema'] ?? null) || $locksmithBindingId !== ($locksmith['binding_id'] ?? null)
            || 'clavium.locksmith' !== ($locksmith['seat'] ?? null) || 'ACTIVE' !== ($locksmith['status'] ?? null)
            || ($decision['instance_id'] ?? null) !== ($locksmith['instance_id'] ?? null) || true !== ($locksmith['governance_cognition_lease_issuance_authority'] ?? null)
            || true === ($locksmith['credential_disclosure_authority'] ?? null) || true === ($locksmith['execution_authority'] ?? null)) {
            throw new \RuntimeException('GCA303_GOVERNANCE_LEASE_CHAIN_INVALID');
        }

        $issuer = ['seat' => 'clavium.locksmith', 'binding_id' => $locksmithBindingId, 'binding_digest' => $locksmith['record_digest'], 'manifestation_id' => $locksmith['manifestation_id'], 'occupancy_generation' => $locksmith['occupancy_generation']];
        $leaseId = 'governance-cognition-lease-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $activationAuthorityId, $issuer, $issuedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)])), 0, 20);
        $record = [
            'schema' => 'imperium.clavium-governance-cognition-lease/v1', 'lease_id' => $leaseId,
            'instance_id' => $decision['instance_id'], 'case_id' => $decision['case_id'], 'case_digest' => $decision['case_digest'],
            'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'source_cognition_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'source_governance_authority' => $request['source_governance_authority'], 'activation_authority' => ['id' => $activationAuthorityId, 'consumed' => true, 'continuing_authority' => false],
            'issuer' => $issuer, 'cluster' => $request['cluster'], 'target' => $request['target'], 'provider' => $decision['provider'], 'model' => $decision['model'],
            'model_configuration' => $decision['model_configuration'], 'resource_ceiling' => $decision['resource_ceiling'], 'input_digest' => $request['input_digest'],
            'issued_at' => $issuedAt->format(DATE_ATOM), 'expires_at' => $expiresAt->format(DATE_ATOM), 'status' => 'GOVERNANCE_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM',
            'continuous_governance_controls' => InternalCognitionLeaseControls::governance($decision, $request, $issuedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)),
            'opaque' => true, 'lease_single_use' => true, 'lease_consumed' => false, 'credential_reference_disclosed' => false, 'credential_material_present' => false,
            'credential_use_authority' => false, 'network_access_authority' => false, 'provider_invocation_authority' => false, 'continuing_authority' => false, 'sealed' => true,
        ];

        return $this->atomic->run('gca-lease-source:'.$decisionId, function () use ($decisionId, $leaseId, $record): array {
            foreach (glob($this->root.'/'.self::LEASES.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'GCA305_GOVERNANCE_LEASE_CONFLICT');
                if (($prior['source_provider_resource_decision']['id'] ?? null) !== $decisionId) { continue; }
                if (!$this->validator->isIntact($prior) || ($prior['lease_id'] ?? null) !== $leaseId) { throw new \RuntimeException('GCA305_GOVERNANCE_LEASE_CONFLICT'); }
                return $prior;
            }
            return $this->records->put(self::LEASES, $leaseId, $record);
        });
    }
}
