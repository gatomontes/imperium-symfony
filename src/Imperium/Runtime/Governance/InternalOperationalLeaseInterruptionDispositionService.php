<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalOperationalLeaseInterruptionDispositionService
{
    private const string LEASES = 'var/imperium/offices/clavium/operational-cognition-leases';
    private const string DECISIONS = 'var/imperium/imperator/operational-provider-resource-decisions';
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private const string AUTHORIZATIONS = 'var/imperium/offices/curia/bounded-execution-authorizations';
    private const string OCCUPANCY = 'var/imperium/offices/curia/occupancy';
    private const string CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private const string DISPOSITIONS = 'var/imperium/runtime/operational-cognition-lease-interruption-dispositions';

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

    public function interrupt(string $leaseId, string $seneschalBindingId, string $reason, \DateTimeImmutable $effectiveAt): array
    {
        $reason = trim($reason);
        if (!preg_match('/^operational-cognition-lease-[a-f0-9]{20}$/', $leaseId)
            || !preg_match('/^curia-seneschal-binding-[a-f0-9]{20}$/', $seneschalBindingId)
            || '' === $reason || strlen($reason) > 500) {
            throw new \InvalidArgumentException('OCI100_OPERATIONAL_LEASE_INTERRUPTION_INPUT_INVALID');
        }

        return $this->atomic->run('oca-lease:'.hash('sha256', $leaseId), function () use ($leaseId, $seneschalBindingId, $reason, $effectiveAt): array {
            $lease = $this->record(self::LEASES, $leaseId, 'lease_id', 'OCI101_OPERATIONAL_COGNITION_LEASE_ABSENT');
            $decision = $this->source(self::DECISIONS, $lease['source_provider_resource_decision'] ?? [], 'decision_id', 'OCI102_OPERATIONAL_LEASE_LINEAGE_INVALID');
            $request = $this->source(self::REQUESTS, $lease['source_cognition_request'] ?? [], 'request_id', 'OCI102_OPERATIONAL_LEASE_LINEAGE_INVALID');
            $authorization = $this->source(self::AUTHORIZATIONS, $request['source_bounded_execution_authorization'] ?? [], 'authorization_id', 'OCI102_OPERATIONAL_LEASE_LINEAGE_INVALID');
            $this->assertLineage($leaseId, $lease, $decision, $request, $authorization, $effectiveAt);
            $actor = $this->currentSourceAuthorizer($seneschalBindingId, $request, $authorization, (string) $lease['instance_id']);
            $this->assertNoClaim($leaseId);

            $scope = [
                'kind' => 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE',
                'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
                'case_id' => $lease['case_id'],
                'target' => $lease['target'],
                'lease_consumed' => false,
            ];
            $lineage = [
                'bounded_execution_authorization' => ['id' => $authorization['authorization_id'], 'digest' => $authorization['record_digest']],
                'operational_cognition_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
                'imperator_provider_resource_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
                'operational_cognition_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
            ];
            $dispositionId = 'operational-lease-interruption-disposition-'.substr(hash('sha256', CanonicalJson::encode([$lineage, $actor, $reason])), 0, 20);

            foreach (glob($this->root.'/'.self::DISPOSITIONS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'OCI106_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_CONFLICT');
                if (($prior['affected_scope']['lease']['id'] ?? null) !== $leaseId) {
                    continue;
                }
                if (!$this->validator->isIntact($prior)
                    || ($prior['lineage'] ?? null) !== $lineage
                    || ($prior['competent_actor'] ?? null) !== $actor
                    || ($prior['reason'] ?? null) !== $reason) {
                    throw new \RuntimeException('OCI106_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_CONFLICT');
                }

                return $prior;
            }

            return $this->records->put(self::DISPOSITIONS, $dispositionId, [
                'schema' => 'imperium.operational-cognition-lease-interruption-disposition/v1',
                'disposition_id' => $dispositionId,
                'instance_id' => $lease['instance_id'],
                'disposition' => 'INTERRUPT',
                'competent_actor' => $actor,
                'authority_basis' => [
                    'jurisdiction' => 'SOURCE_AUTHORIZER_CURRENT_INTERNAL_OPERATIONAL_ITERATION',
                    'source_bounded_execution_authorization' => $lineage['bounded_execution_authorization'],
                    'source_occupancy' => ['id' => $actor['binding_id'], 'digest' => $actor['binding_digest']],
                ],
                'lineage' => $lineage,
                'affected_scope' => $scope,
                'reason' => $reason,
                'effective_at' => $effectiveAt->format(DATE_ATOM),
                'enforcement_required' => true,
                'enforcement_authority_opened' => false,
                'claim_created' => false,
                'cognition_authority_consumed' => false,
                'lease_consumed' => false,
                'lease_mutated' => false,
                'lease_closed' => false,
                'credential_resolved' => false,
                'provider_journal_created' => false,
                'network_access_performed' => false,
                'propagation_performed' => false,
                'authority_granted' => false,
                'continuation_authority' => false,
                'sealed' => true,
            ]);
        });
    }

    private function assertLineage(string $leaseId, array $lease, array $decision, array $request, array $authorization, \DateTimeImmutable $at): void
    {
        $sameTarget = ($authorization['seat'] ?? null) === ($request['target']['seat'] ?? null)
            && ($authorization['manifestation_id'] ?? null) === ($request['target']['manifestation_id'] ?? null)
            && ($authorization['source_binding']['id'] ?? null) === ($request['target']['binding_id'] ?? null)
            && ($authorization['source_binding']['digest'] ?? null) === ($request['target']['binding_digest'] ?? null)
            && ($authorization['operational_custody']['id'] ?? null) === ($request['target']['custody_id'] ?? null)
            && ($authorization['operational_custody']['digest'] ?? null) === ($request['target']['custody_digest'] ?? null);
        $earliestExpiry = min(array_map(
            static fn (string $value): int => (new \DateTimeImmutable($value))->getTimestamp(),
            [(string) ($request['expires_at'] ?? '1970-01-01'), (string) ($decision['expires_at'] ?? '1970-01-01'), (string) ($lease['expires_at'] ?? '1970-01-01')],
        ));

        if ('imperium.clavium-operational-cognition-lease/v1' !== ($lease['schema'] ?? null)
            || $leaseId !== ($lease['lease_id'] ?? null)
            || 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
            || true !== ($lease['opaque'] ?? null) || true !== ($lease['lease_single_use'] ?? null) || false !== ($lease['lease_consumed'] ?? null)
            || 'imperium.imperator-operational-provider-resource-decision/v1' !== ($decision['schema'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' !== ($decision['status'] ?? null)
            || ($decision['source_cognition_request'] ?? null) !== ['id' => $request['request_id'], 'digest' => $request['record_digest']]
            || 'imperium.curia-operational-cognition-request/v1' !== ($request['schema'] ?? null)
            || 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || 'imperium.curia-bounded-execution-authorization/v1' !== ($authorization['schema'] ?? null)
            || 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION' !== ($authorization['status'] ?? null)
            || true !== ($authorization['bounded_execution_authority'] ?? null)
            || true !== ($authorization['bounded_execution_authority_exercisable'] ?? null)
            || 1 !== ($authorization['maximum_iterations'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($decision['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($request['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($authorization['instance_id'] ?? null)
            || ($lease['case_id'] ?? null) !== ($decision['case_id'] ?? null)
            || ($lease['case_id'] ?? null) !== ($request['case_id'] ?? null)
            || ($lease['case_id'] ?? null) !== ($authorization['case_id'] ?? null)
            || ($lease['case_digest'] ?? null) !== ($decision['case_digest'] ?? null)
            || ($lease['case_digest'] ?? null) !== ($request['case_digest'] ?? null)
            || ($lease['case_digest'] ?? null) !== ($authorization['case_digest'] ?? null)
            || !$sameTarget || ($decision['target'] ?? null) !== ($request['target'] ?? null) || ($lease['target'] ?? null) !== ($request['target'] ?? null)
            || ($authorization['input_digest'] ?? null) !== ($request['input_digest'] ?? null)
            || ($decision['input_digest'] ?? null) !== ($request['input_digest'] ?? null)
            || ($lease['input_digest'] ?? null) !== ($request['input_digest'] ?? null)
            || ($decision['provider'] ?? null) !== ($lease['provider'] ?? null)
            || ($decision['model'] ?? null) !== ($lease['model'] ?? null)
            || ($decision['model_configuration'] ?? null) !== ($lease['model_configuration'] ?? null)
            || ($decision['resource_ceiling'] ?? null) !== ($lease['resource_ceiling'] ?? null)
            || ($request['iteration'] ?? null) !== ($decision['iteration'] ?? null)
            || ($request['iteration'] ?? null) !== ($lease['iteration'] ?? null)
            || new \DateTimeImmutable((string) ($request['requested_at'] ?? '2999-01-01')) > $at
            || new \DateTimeImmutable((string) ($decision['decided_at'] ?? '2999-01-01')) > $at
            || new \DateTimeImmutable((string) ($lease['issued_at'] ?? '2999-01-01')) > $at
            || $at->getTimestamp() >= $earliestExpiry) {
            throw new \RuntimeException('OCI102_OPERATIONAL_LEASE_LINEAGE_INVALID');
        }
    }

    private function currentSourceAuthorizer(string $bindingId, array $request, array $authorization, string $instanceId): array
    {
        $source = $authorization['authorizer'] ?? null;
        if (!is_array($source) || $source !== ($request['authorizer'] ?? null)
            || $bindingId !== ($source['binding_id'] ?? null)
            || 'curia.seneschal' !== ($source['seat'] ?? null)
            || !is_string($source['manifestation_id'] ?? null)
            || !is_int($source['occupancy_generation'] ?? null)) {
            throw new \RuntimeException('OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT');
        }
        $occupancy = $this->record(self::OCCUPANCY, $bindingId, 'binding_id', 'OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT');
        if ('imperium.curia-seneschal-occupancy/v1' !== ($occupancy['schema'] ?? null)
            || $instanceId !== ($occupancy['instance_id'] ?? null)
            || 'curia.seneschal' !== ($occupancy['seat'] ?? null)
            || 'ACTIVE' !== ($occupancy['status'] ?? null)
            || ($source['manifestation_id'] ?? null) !== ($occupancy['manifestation_id'] ?? null)
            || ($source['occupancy_generation'] ?? null) !== ($occupancy['occupancy_generation'] ?? null)) {
            throw new \RuntimeException('OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT');
        }
        foreach (glob($this->root.'/'.self::OCCUPANCY.'/*.json') ?: [] as $path) {
            $other = $this->validator->requireIntact(
                $this->validator->read($path, 'OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT'),
                'OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT',
            );
            if ('curia.seneschal' === ($other['seat'] ?? null) && 'ACTIVE' === ($other['status'] ?? null)
                && $instanceId === ($other['instance_id'] ?? null) && $bindingId !== ($other['binding_id'] ?? null)) {
                throw new \RuntimeException('OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT');
            }
        }

        return [
            'seat' => 'curia.seneschal',
            'binding_id' => $bindingId,
            'binding_digest' => $occupancy['record_digest'],
            'manifestation_id' => $occupancy['manifestation_id'],
            'occupancy_generation' => $occupancy['occupancy_generation'],
        ];
    }

    private function assertNoClaim(string $leaseId): void
    {
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $claim = $this->validator->read($path, 'OCI104_OPERATIONAL_CLAIM_UNREADABLE');
            if (!$this->validator->isIntact($claim)) {
                throw new \RuntimeException('OCI104_OPERATIONAL_CLAIM_UNREADABLE');
            }
            if (($claim['lease_consumption']['lease_id'] ?? null) === $leaseId) {
                throw new \RuntimeException('OCI105_OPERATIONAL_LEASE_NOT_INTERRUPTIBLE_UNCLAIMED');
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
