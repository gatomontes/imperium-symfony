<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalOperationalLeaseInterruptionReconstructionService
{
    private const string AUTHORIZATIONS = 'var/imperium/offices/curia/bounded-execution-authorizations';
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private const string DECISIONS = 'var/imperium/imperator/operational-provider-resource-decisions';
    private const string LEASES = 'var/imperium/offices/clavium/operational-cognition-leases';
    private const string CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private const string DISPOSITIONS = 'var/imperium/runtime/operational-cognition-lease-interruption-dispositions';
    private const string AUTHORITIES = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities';
    private const string RESULTS = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results';
    private const string CURIA_OCCUPANCY = 'var/imperium/offices/curia/occupancy';
    private const string CLAVIUM_OCCUPANCY = 'var/imperium/offices/clavium/occupancy';

    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function reconstruct(string $leaseId): array
    {
        if (!preg_match('/^operational-cognition-lease-[a-f0-9]{20}$/', $leaseId)) {
            throw new \InvalidArgumentException('OCI400_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INPUT_INVALID');
        }
        $lease = $this->record(self::LEASES, $leaseId, 'lease_id');
        $result = $this->singleResult($leaseId);
        $authority = $this->source(self::AUTHORITIES, $result['source_authority'] ?? [], 'authority_id');
        $disposition = $this->source(self::DISPOSITIONS, $authority['source_disposition'] ?? [], 'disposition_id');
        $lineage = $disposition['lineage'] ?? [];
        $authorization = $this->source(self::AUTHORIZATIONS, $lineage['bounded_execution_authorization'] ?? [], 'authorization_id');
        $request = $this->source(self::REQUESTS, $lineage['operational_cognition_request'] ?? [], 'request_id');
        $decision = $this->source(self::DECISIONS, $lineage['imperator_provider_resource_decision'] ?? [], 'decision_id');
        $seneschal = $this->currentOccupancy(self::CURIA_OCCUPANCY, 'imperium.curia-seneschal-occupancy/v1', 'curia.seneschal', (string) ($disposition['competent_actor']['binding_id'] ?? ''), (string) ($lease['instance_id'] ?? ''));
        $locksmith = $this->currentOccupancy(self::CLAVIUM_OCCUPANCY, 'imperium.clavium-locksmith-occupancy/v1', 'clavium.locksmith', (string) ($authority['enforcer']['binding_id'] ?? ''), (string) ($lease['instance_id'] ?? ''));
        $scope = ['kind' => 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE', 'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'case_id' => $lease['case_id'], 'target' => $lease['target'], 'lease_consumed' => false];
        $sourceAuthorizer = $authorization['authorizer'] ?? null;
        $actor = $disposition['competent_actor'] ?? null;
        $sameAuthorizer = is_array($sourceAuthorizer) && is_array($actor)
            && ($sourceAuthorizer['seat'] ?? null) === ($actor['seat'] ?? null)
            && ($sourceAuthorizer['binding_id'] ?? null) === ($actor['binding_id'] ?? null)
            && ($sourceAuthorizer['manifestation_id'] ?? null) === ($actor['manifestation_id'] ?? null)
            && ($sourceAuthorizer['occupancy_generation'] ?? null) === ($actor['occupancy_generation'] ?? null)
            && $sourceAuthorizer === ($request['authorizer'] ?? null);
        $expectedLineage = [
            'bounded_execution_authorization' => ['id' => $authorization['authorization_id'], 'digest' => $authorization['record_digest']],
            'operational_cognition_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'imperator_provider_resource_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'operational_cognition_lease' => ['id' => $lease['lease_id'], 'digest' => $lease['record_digest']],
        ];

        if ('imperium.curia-bounded-execution-authorization/v1' !== ($authorization['schema'] ?? null)
            || 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION' !== ($authorization['status'] ?? null)
            || ($request['source_bounded_execution_authorization'] ?? null) !== ['id' => $authorization['authorization_id'], 'digest' => $authorization['record_digest']]
            || 'imperium.curia-operational-cognition-request/v1' !== ($request['schema'] ?? null)
            || 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || ($decision['source_cognition_request'] ?? null) !== ['id' => $request['request_id'], 'digest' => $request['record_digest']]
            || 'imperium.imperator-operational-provider-resource-decision/v1' !== ($decision['schema'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' !== ($decision['status'] ?? null)
            || 'imperium.clavium-operational-cognition-lease/v1' !== ($lease['schema'] ?? null)
            || ($lease['source_cognition_request'] ?? null) !== ['id' => $request['request_id'], 'digest' => $request['record_digest']]
            || ($lease['source_provider_resource_decision'] ?? null) !== ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']]
            || 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
            || true !== ($lease['lease_single_use'] ?? null) || false !== ($lease['lease_consumed'] ?? null)
            || 'imperium.operational-cognition-lease-interruption-disposition/v1' !== ($disposition['schema'] ?? null)
            || 'INTERRUPT' !== ($disposition['disposition'] ?? null) || ($disposition['affected_scope'] ?? null) !== $scope
            || $lineage !== $expectedLineage || !$sameAuthorizer
            || ($disposition['authority_basis']['source_bounded_execution_authorization'] ?? null) !== $expectedLineage['bounded_execution_authorization']
            || ($disposition['authority_basis']['source_occupancy'] ?? null) !== ['id' => $seneschal['binding_id'], 'digest' => $seneschal['record_digest']]
            || $actor !== $this->actor($seneschal)
            || true !== ($disposition['enforcement_required'] ?? null) || false !== ($disposition['enforcement_authority_opened'] ?? null)
            || false !== ($disposition['authority_granted'] ?? null) || false !== ($disposition['continuation_authority'] ?? null)
            || 'imperium.operational-cognition-lease-interruption-enforcement-authority/v1' !== ($authority['schema'] ?? null)
            || ($authority['source_disposition'] ?? null) !== ['id' => $disposition['disposition_id'], 'digest' => $disposition['record_digest']]
            || ($authority['issuer'] ?? null) !== $actor || ($authority['enforcer'] ?? null) !== $this->actor($locksmith)
            || ($authority['lineage'] ?? null) !== $lineage || ($authority['affected_scope'] ?? null) !== $scope
            || 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE' !== ($authority['permitted_transition'] ?? null)
            || true !== ($authority['single_use'] ?? null) || true !== ($authority['exercisable'] ?? null) || false !== ($authority['consumed'] ?? null)
            || !$this->allFalse($authority, ['claim_creation_authority', 'cognition_authority', 'credential_authority', 'provider_journal_authority', 'network_access_authority', 'lease_mutation_authority', 'lease_closure_authority', 'propagation_authority', 'continuing_authority', 'external_action_authority', 'perimeter_authority'])
            || !$this->withinEarliestExpiry($authority, $request, $decision, $lease)
            || 'imperium.operational-cognition-lease-interruption-enforcement-result/v1' !== ($result['schema'] ?? null)
            || ($result['source_disposition'] ?? null) !== ($authority['source_disposition'] ?? null)
            || ($result['enforcer'] ?? null) !== ($authority['enforcer'] ?? null)
            || ($result['lineage'] ?? null) !== $lineage || ($result['affected_scope'] ?? null) !== $scope
            || 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE' !== ($result['performed_transition'] ?? null)
            || true !== ($result['authority_consumed'] ?? null)
            || !$this->withinAuthorityWindow($result, $authority)
            || !$this->allFalse($result, ['claim_created', 'cognition_authority_consumed', 'lease_consumed', 'lease_mutated', 'lease_closed', 'request_mutated', 'decision_mutated', 'credential_resolved', 'credential_reference_disclosed', 'credential_material_present', 'credential_mutated', 'provider_invoked', 'provider_journal_created', 'network_access_performed', 'propagation_performed', 'continuation_authority', 'external_action_authority', 'perimeter_authority'])
            || ($lease['instance_id'] ?? null) !== ($authorization['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($request['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($decision['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($disposition['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($authority['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($result['instance_id'] ?? null)) {
            throw new \RuntimeException('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $claim = $this->validator->requireIntact($this->validator->read($path, 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            if (($claim['lease_consumption']['lease_id'] ?? null) === $leaseId) {
                throw new \RuntimeException('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            }
        }

        return [
            'schema' => 'imperium.operational-cognition-lease-interruption-reconstruction/v1',
            'status' => 'INTERNAL_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTED',
            'completeness_claim' => 'NINE_ARTIFACT_OPERATIONAL_LEASE_INTERRUPTION_CHAIN_ONLY',
            'root_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
            'instance_id' => $lease['instance_id'],
            'included_evidence' => ['bounded_execution_authorization' => $authorization, 'current_seneschal_occupancy' => $seneschal, 'operational_cognition_request' => $request, 'imperator_provider_resource_decision' => $decision, 'operational_cognition_lease' => $lease, 'interruption_disposition' => $disposition, 'current_locksmith_occupancy' => $locksmith, 'enforcement_authority' => $authority, 'enforcement_result' => $result],
            'verified_artifact_count' => 9,
            'durable_invocation_claim_absent' => true,
            'read_only' => true,
            'cognition_invoked' => false,
            'credential_resolved' => false,
            'provider_journal_created' => false,
            'network_access_performed' => false,
            'state_mutated' => false,
            'lease_closed' => false,
            'propagation_performed' => false,
            'authority_granted' => false,
            'continuation_authority' => false,
        ];
    }

    private function singleResult(string $leaseId): array
    {
        $matches = [];
        foreach (glob($this->root.'/'.self::RESULTS.'/*.json') ?: [] as $path) {
            $result = $this->validator->requireIntact($this->validator->read($path, 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            if (($result['affected_scope']['lease']['id'] ?? null) === $leaseId) {
                $matches[] = $result;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }

        return $matches[0];
    }

    private function currentOccupancy(string $directory, string $schema, string $seat, string $id, string $instanceId): array
    {
        $record = $this->record($directory, $id, 'binding_id');
        if ($schema !== ($record['schema'] ?? null) || $seat !== ($record['seat'] ?? null)
            || $instanceId !== ($record['instance_id'] ?? null) || 'ACTIVE' !== ($record['status'] ?? null)) {
            throw new \RuntimeException('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }
        foreach (glob($this->root.'/'.$directory.'/*.json') ?: [] as $path) {
            $other = $this->validator->requireIntact($this->validator->read($path, 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            if ($seat === ($other['seat'] ?? null) && 'ACTIVE' === ($other['status'] ?? null)
                && $instanceId === ($other['instance_id'] ?? null) && $id !== ($other['binding_id'] ?? null)) {
                throw new \RuntimeException('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            }
        }

        return $record;
    }

    private function actor(array $occupancy): array
    {
        return ['seat' => $occupancy['seat'], 'binding_id' => $occupancy['binding_id'], 'binding_digest' => $occupancy['record_digest'], 'manifestation_id' => $occupancy['manifestation_id'], 'occupancy_generation' => $occupancy['occupancy_generation']];
    }

    private function allFalse(array $record, array $fields): bool
    {
        foreach ($fields as $field) {
            if (false !== ($record[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function withinEarliestExpiry(array $authority, array $request, array $decision, array $lease): bool
    {
        try {
            $issuedAt = new \DateTimeImmutable((string) ($authority['issued_at'] ?? ''));
            $expiresAt = new \DateTimeImmutable((string) ($authority['expires_at'] ?? ''));
            $earliest = min(array_map(
                static fn (array $record): \DateTimeImmutable => new \DateTimeImmutable((string) ($record['expires_at'] ?? '')),
                [$request, $decision, $lease],
            ));

            return $expiresAt > $issuedAt && $expiresAt <= $issuedAt->modify('+5 minutes') && $expiresAt <= $earliest;
        } catch (\Throwable) {
            return false;
        }
    }

    private function withinAuthorityWindow(array $result, array $authority): bool
    {
        try {
            $consumedAt = new \DateTimeImmutable((string) ($result['consumed_at'] ?? ''));

            return $consumedAt >= new \DateTimeImmutable((string) ($authority['issued_at'] ?? ''))
                && $consumedAt < new \DateTimeImmutable((string) ($authority['expires_at'] ?? ''));
        } catch (\Throwable) {
            return false;
        }
    }

    private function source(string $directory, array $reference, string $key): array
    {
        return $this->validator->resolve($this->root.'/'.$directory, $reference, 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID', 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID', $key);
    }

    private function record(string $directory, string $id, string $key): array
    {
        $record = $this->validator->requireIntact($this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        if ($id !== ($record[$key] ?? null)) {
            throw new \RuntimeException('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }

        return $record;
    }
}
