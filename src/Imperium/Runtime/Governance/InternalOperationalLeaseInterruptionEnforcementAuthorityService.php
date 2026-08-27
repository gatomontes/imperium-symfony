<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalOperationalLeaseInterruptionEnforcementAuthorityService
{
    private const string DISPOSITIONS = 'var/imperium/runtime/operational-cognition-lease-interruption-dispositions';
    private const string AUTHORITIES = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities';
    private const string AUTHORIZATIONS = 'var/imperium/offices/curia/bounded-execution-authorizations';
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private const string DECISIONS = 'var/imperium/imperator/operational-provider-resource-decisions';
    private const string LEASES = 'var/imperium/offices/clavium/operational-cognition-leases';
    private const string CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private const string CURIA_OCCUPANCY = 'var/imperium/offices/curia/occupancy';
    private const string CLAVIUM_OCCUPANCY = 'var/imperium/offices/clavium/occupancy';

    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?RecordReferenceValidator $validator = null,
        ?ImmutableRecordStore $records = null,
        ?AtomicTransition $atomic = null,
    ) {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
    }

    public function issue(
        string $dispositionId,
        string $locksmithBindingId,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ): array {
        if (!preg_match('/^operational-lease-interruption-disposition-[a-f0-9]{20}$/', $dispositionId)
            || !preg_match('/^clavium-locksmith-binding-[a-f0-9]{20}$/', $locksmithBindingId)
            || $expiresAt <= $issuedAt || $expiresAt > $issuedAt->modify('+5 minutes')) {
            throw new \InvalidArgumentException('OCI200_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_INPUT_INVALID');
        }

        $disposition = $this->record(self::DISPOSITIONS, $dispositionId, 'disposition_id', 'OCI201_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_ABSENT');
        $leaseId = $disposition['lineage']['operational_cognition_lease']['id'] ?? null;
        if (!is_string($leaseId) || !preg_match('/^operational-cognition-lease-[a-f0-9]{20}$/', $leaseId)) {
            throw new \RuntimeException('OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
        }

        return $this->atomic->run('oca-lease:'.hash('sha256', $leaseId), function () use ($dispositionId, $disposition, $leaseId, $locksmithBindingId, $issuedAt, $expiresAt): array {
            $authorization = $this->source(self::AUTHORIZATIONS, $disposition['lineage']['bounded_execution_authorization'] ?? [], 'authorization_id', 'OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
            $request = $this->source(self::REQUESTS, $disposition['lineage']['operational_cognition_request'] ?? [], 'request_id', 'OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
            $decision = $this->source(self::DECISIONS, $disposition['lineage']['imperator_provider_resource_decision'] ?? [], 'decision_id', 'OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
            $lease = $this->source(self::LEASES, $disposition['lineage']['operational_cognition_lease'] ?? [], 'lease_id', 'OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
            $this->assertChain($dispositionId, $disposition, $authorization, $request, $decision, $leaseId, $lease, $issuedAt, $expiresAt);
            $issuer = $this->currentActor(self::CURIA_OCCUPANCY, 'imperium.curia-seneschal-occupancy/v1', 'curia.seneschal', (string) ($disposition['competent_actor']['binding_id'] ?? ''), (string) $disposition['instance_id'], 'OCI203_SOURCE_AUTHORIZER_NO_LONGER_CURRENT');
            if ($issuer !== ($disposition['competent_actor'] ?? null)) {
                throw new \RuntimeException('OCI203_SOURCE_AUTHORIZER_NO_LONGER_CURRENT');
            }
            $enforcer = $this->currentActor(self::CLAVIUM_OCCUPANCY, 'imperium.clavium-locksmith-occupancy/v1', 'clavium.locksmith', $locksmithBindingId, (string) $disposition['instance_id'], 'OCI204_LOCKSMITH_ENFORCER_NOT_CURRENT');
            $this->assertNoClaim($leaseId);
            $authorityId = 'operational-lease-interruption-enforcement-authority-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], $issuer, $enforcer])), 0, 20);
            $expected = [
                'schema' => 'imperium.operational-cognition-lease-interruption-enforcement-authority/v1',
                'authority_id' => $authorityId,
                'instance_id' => $disposition['instance_id'],
                'source_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
                'issuer' => $issuer,
                'enforcer' => $enforcer,
                'lineage' => $disposition['lineage'],
                'affected_scope' => $disposition['affected_scope'],
                'permitted_transition' => 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE',
                'issued_at' => $issuedAt->format(DATE_ATOM),
                'expires_at' => $expiresAt->format(DATE_ATOM),
                'single_use' => true,
                'exercisable' => true,
                'consumed' => false,
                'claim_creation_authority' => false,
                'cognition_authority' => false,
                'credential_authority' => false,
                'provider_journal_authority' => false,
                'network_access_authority' => false,
                'lease_mutation_authority' => false,
                'lease_closure_authority' => false,
                'propagation_authority' => false,
                'continuing_authority' => false,
                'external_action_authority' => false,
                'perimeter_authority' => false,
                'sealed' => true,
            ];

            foreach (glob($this->root.'/'.self::AUTHORITIES.'/*.json') ?: [] as $path) {
                $prior = $this->validator->requireIntact($this->validator->read($path, 'OCI206_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_CONFLICT'), 'OCI206_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_CONFLICT');
                if (($prior['source_disposition']['id'] ?? null) !== $dispositionId) {
                    continue;
                }
                $comparison = $prior;
                unset($comparison['record_digest']);
                if ($comparison !== $expected) {
                    throw new \RuntimeException('OCI206_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_CONFLICT');
                }

                return $prior;
            }

            return $this->records->put(self::AUTHORITIES, $authorityId, $expected);
        });
    }

    private function assertChain(string $id, array $disposition, array $authorization, array $request, array $decision, string $leaseId, array $lease, \DateTimeImmutable $issuedAt, \DateTimeImmutable $expiresAt): void
    {
        $earliestExpiry = min(array_map(
            static fn (string $value): int => (new \DateTimeImmutable($value))->getTimestamp(),
            [(string) ($request['expires_at'] ?? '1970-01-01'), (string) ($decision['expires_at'] ?? '1970-01-01'), (string) ($lease['expires_at'] ?? '1970-01-01')],
        ));
        $sourceAuthorizer = $authorization['authorizer'] ?? null;
        $competentActor = $disposition['competent_actor'] ?? null;
        $sameAuthorizer = is_array($sourceAuthorizer) && is_array($competentActor)
            && ($sourceAuthorizer['seat'] ?? null) === ($competentActor['seat'] ?? null)
            && ($sourceAuthorizer['binding_id'] ?? null) === ($competentActor['binding_id'] ?? null)
            && ($sourceAuthorizer['manifestation_id'] ?? null) === ($competentActor['manifestation_id'] ?? null)
            && ($sourceAuthorizer['occupancy_generation'] ?? null) === ($competentActor['occupancy_generation'] ?? null)
            && $sourceAuthorizer === ($request['authorizer'] ?? null);
        if ('imperium.operational-cognition-lease-interruption-disposition/v1' !== ($disposition['schema'] ?? null)
            || $id !== ($disposition['disposition_id'] ?? null) || 'INTERRUPT' !== ($disposition['disposition'] ?? null)
            || 'SOURCE_AUTHORIZER_CURRENT_INTERNAL_OPERATIONAL_ITERATION' !== ($disposition['authority_basis']['jurisdiction'] ?? null)
            || ($disposition['authority_basis']['source_bounded_execution_authorization'] ?? null) !== ($disposition['lineage']['bounded_execution_authorization'] ?? null)
            || 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE' !== ($disposition['affected_scope']['kind'] ?? null)
            || $leaseId !== ($disposition['affected_scope']['lease']['id'] ?? null)
            || ($lease['record_digest'] ?? null) !== ($disposition['affected_scope']['lease']['digest'] ?? null)
            || false !== ($disposition['affected_scope']['lease_consumed'] ?? null)
            || true !== ($disposition['enforcement_required'] ?? null)
            || false !== ($disposition['enforcement_authority_opened'] ?? null)
            || false !== ($disposition['claim_created'] ?? null)
            || false !== ($disposition['cognition_authority_consumed'] ?? null)
            || false !== ($disposition['lease_consumed'] ?? null)
            || false !== ($disposition['lease_mutated'] ?? null)
            || false !== ($disposition['lease_closed'] ?? null)
            || false !== ($disposition['credential_resolved'] ?? null)
            || false !== ($disposition['provider_journal_created'] ?? null)
            || false !== ($disposition['network_access_performed'] ?? null)
            || false !== ($disposition['propagation_performed'] ?? null)
            || false !== ($disposition['authority_granted'] ?? null)
            || false !== ($disposition['continuation_authority'] ?? null)
            || new \DateTimeImmutable((string) ($disposition['effective_at'] ?? '2999-01-01')) > $issuedAt
            || 'imperium.curia-bounded-execution-authorization/v1' !== ($authorization['schema'] ?? null)
            || 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION' !== ($authorization['status'] ?? null)
            || true !== ($authorization['bounded_execution_authority'] ?? null)
            || true !== ($authorization['bounded_execution_authority_exercisable'] ?? null)
            || ($request['source_bounded_execution_authorization'] ?? null) !== ['id' => $authorization['authorization_id'], 'digest' => $authorization['record_digest']]
            || 'imperium.curia-operational-cognition-request/v1' !== ($request['schema'] ?? null)
            || 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || ($decision['source_cognition_request'] ?? null) !== ['id' => $request['request_id'], 'digest' => $request['record_digest']]
            || 'imperium.imperator-operational-provider-resource-decision/v1' !== ($decision['schema'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' !== ($decision['status'] ?? null)
            || 'imperium.clavium-operational-cognition-lease/v1' !== ($lease['schema'] ?? null)
            || 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
            || true !== ($lease['lease_single_use'] ?? null) || false !== ($lease['lease_consumed'] ?? null)
            || ($lease['source_cognition_request'] ?? null) !== ['id' => $request['request_id'], 'digest' => $request['record_digest']]
            || ($lease['source_provider_resource_decision'] ?? null) !== ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']]
            || ($disposition['instance_id'] ?? null) !== ($lease['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($decision['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($request['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($authorization['instance_id'] ?? null)
            || !$sameAuthorizer
            || $issuedAt->getTimestamp() >= $earliestExpiry || $expiresAt->getTimestamp() > $earliestExpiry) {
            throw new \RuntimeException('OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
        }
    }

    private function currentActor(string $directory, string $schema, string $seat, string $bindingId, string $instanceId, string $error): array
    {
        $occupancy = $this->record($directory, $bindingId, 'binding_id', $error);
        if ($schema !== ($occupancy['schema'] ?? null) || $instanceId !== ($occupancy['instance_id'] ?? null)
            || $seat !== ($occupancy['seat'] ?? null) || 'ACTIVE' !== ($occupancy['status'] ?? null)
            || !is_string($occupancy['manifestation_id'] ?? null) || !is_int($occupancy['occupancy_generation'] ?? null)
            || ('clavium.locksmith' === $seat && (true === ($occupancy['credential_disclosure_authority'] ?? null) || true === ($occupancy['execution_authority'] ?? null)))) {
            throw new \RuntimeException($error);
        }
        foreach (glob($this->root.'/'.$directory.'/*.json') ?: [] as $path) {
            $other = $this->validator->requireIntact($this->validator->read($path, $error), $error);
            if ($seat === ($other['seat'] ?? null) && 'ACTIVE' === ($other['status'] ?? null)
                && $instanceId === ($other['instance_id'] ?? null) && $bindingId !== ($other['binding_id'] ?? null)) {
                throw new \RuntimeException($error);
            }
        }

        return ['seat' => $seat, 'binding_id' => $bindingId, 'binding_digest' => $occupancy['record_digest'], 'manifestation_id' => $occupancy['manifestation_id'], 'occupancy_generation' => $occupancy['occupancy_generation']];
    }

    private function assertNoClaim(string $leaseId): void
    {
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $claim = $this->validator->requireIntact($this->validator->read($path, 'OCI205_OPERATIONAL_LEASE_NO_LONGER_ENFORCEABLE_UNCLAIMED'), 'OCI205_OPERATIONAL_LEASE_NO_LONGER_ENFORCEABLE_UNCLAIMED');
            if (($claim['lease_consumption']['lease_id'] ?? null) === $leaseId) {
                throw new \RuntimeException('OCI205_OPERATIONAL_LEASE_NO_LONGER_ENFORCEABLE_UNCLAIMED');
            }
        }
    }

    private function source(string $directory, array $reference, string $key, string $error): array
    {
        return $this->validator->resolve($this->root.'/'.$directory, $reference, $error, $error, $key);
    }

    private function record(string $directory, string $id, string $key, string $error): array
    {
        $record = $this->validator->requireIntact($this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', $error), $error);
        if ($id !== ($record[$key] ?? null)) {
            throw new \RuntimeException($error);
        }

        return $record;
    }
}
