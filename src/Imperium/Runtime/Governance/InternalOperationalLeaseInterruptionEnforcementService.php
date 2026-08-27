<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalOperationalLeaseInterruptionEnforcementService
{
    private const string AUTHORITIES = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities';
    private const string DISPOSITIONS = 'var/imperium/runtime/operational-cognition-lease-interruption-dispositions';
    private const string RESULTS = 'var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results';
    private const string LEASES = 'var/imperium/offices/clavium/operational-cognition-leases';
    private const string CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private const string OCCUPANCY = 'var/imperium/offices/clavium/occupancy';

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

    public function enforce(string $authorityId, string $locksmithBindingId, \DateTimeImmutable $consumedAt): array
    {
        if (!preg_match('/^operational-lease-interruption-enforcement-authority-[a-f0-9]{20}$/', $authorityId)
            || !preg_match('/^clavium-locksmith-binding-[a-f0-9]{20}$/', $locksmithBindingId)) {
            throw new \InvalidArgumentException('OCI300_OPERATIONAL_LEASE_ENFORCEMENT_INPUT_INVALID');
        }
        $authority = $this->record(self::AUTHORITIES, $authorityId, 'authority_id', 'OCI301_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_ABSENT');
        $leaseId = $authority['affected_scope']['lease']['id'] ?? null;
        if (!is_string($leaseId) || !preg_match('/^operational-cognition-lease-[a-f0-9]{20}$/', $leaseId)) {
            throw new \RuntimeException('OCI302_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_INVALID');
        }

        return $this->atomic->run('oca-lease:'.hash('sha256', $leaseId), function () use ($authorityId, $authority, $leaseId, $locksmithBindingId, $consumedAt): array {
            $this->assertAuthority($authorityId, $authority, $locksmithBindingId, $consumedAt);
            $disposition = $this->source(self::DISPOSITIONS, $authority['source_disposition'] ?? [], 'disposition_id', 'OCI303_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
            $lease = $this->source(self::LEASES, $authority['affected_scope']['lease'] ?? [], 'lease_id', 'OCI304_OPERATIONAL_COGNITION_LEASE_INVALID');
            if ('imperium.operational-cognition-lease-interruption-disposition/v1' !== ($disposition['schema'] ?? null)
                || 'INTERRUPT' !== ($disposition['disposition'] ?? null)
                || ($disposition['lineage'] ?? null) !== ($authority['lineage'] ?? null)
                || ($disposition['affected_scope'] ?? null) !== ($authority['affected_scope'] ?? null)
                || 'imperium.clavium-operational-cognition-lease/v1' !== ($lease['schema'] ?? null)
                || 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
                || ($lease['instance_id'] ?? null) !== ($authority['instance_id'] ?? null)
                || false !== ($lease['lease_consumed'] ?? null)
                || new \DateTimeImmutable((string) ($lease['expires_at'] ?? '1970-01-01')) <= $consumedAt
                || true !== ($lease['sealed'] ?? null)) {
                throw new \RuntimeException('OCI304_OPERATIONAL_COGNITION_LEASE_INVALID');
            }
            $this->assertNoClaim($leaseId);
            $enforcer = $this->currentLocksmith($locksmithBindingId, (string) $authority['instance_id']);
            if ($enforcer !== ($authority['enforcer'] ?? null)) {
                throw new \RuntimeException('OCI305_LOCKSMITH_ENFORCER_INVALID');
            }

            foreach (glob($this->root.'/'.self::RESULTS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->requireIntact($this->validator->read($path, 'OCI307_OPERATIONAL_LEASE_ENFORCEMENT_RESULT_CONFLICT'), 'OCI307_OPERATIONAL_LEASE_ENFORCEMENT_RESULT_CONFLICT');
                if (($prior['source_authority']['id'] ?? null) !== $authorityId) {
                    continue;
                }
                if (($prior['source_authority']['digest'] ?? null) !== $authority['record_digest'] || ($prior['enforcer'] ?? null) !== $enforcer) {
                    throw new \RuntimeException('OCI307_OPERATIONAL_LEASE_ENFORCEMENT_RESULT_CONFLICT');
                }

                return $prior;
            }

            $resultId = 'operational-lease-interruption-enforcement-result-'.substr(hash('sha256', CanonicalJson::encode([$authorityId, $authority['record_digest'], $enforcer])), 0, 20);

            return $this->records->put(self::RESULTS, $resultId, [
                'schema' => 'imperium.operational-cognition-lease-interruption-enforcement-result/v1',
                'result_id' => $resultId,
                'instance_id' => $authority['instance_id'],
                'source_authority' => ['id' => $authorityId, 'digest' => $authority['record_digest']],
                'source_disposition' => $authority['source_disposition'],
                'enforcer' => $enforcer,
                'lineage' => $authority['lineage'],
                'affected_scope' => $authority['affected_scope'],
                'performed_transition' => 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE',
                'consumed_at' => $consumedAt->format(DATE_ATOM),
                'authority_consumed' => true,
                'claim_created' => false,
                'cognition_authority_consumed' => false,
                'lease_consumed' => false,
                'lease_mutated' => false,
                'lease_closed' => false,
                'request_mutated' => false,
                'decision_mutated' => false,
                'credential_resolved' => false,
                'credential_reference_disclosed' => false,
                'credential_material_present' => false,
                'credential_mutated' => false,
                'provider_invoked' => false,
                'provider_journal_created' => false,
                'network_access_performed' => false,
                'propagation_performed' => false,
                'continuation_authority' => false,
                'external_action_authority' => false,
                'perimeter_authority' => false,
                'sealed' => true,
            ]);
        });
    }

    private function assertAuthority(string $id, array $authority, string $bindingId, \DateTimeImmutable $at): void
    {
        foreach (['claim_creation_authority', 'cognition_authority', 'credential_authority', 'provider_journal_authority', 'network_access_authority', 'lease_mutation_authority', 'lease_closure_authority', 'propagation_authority', 'continuing_authority', 'external_action_authority', 'perimeter_authority'] as $flag) {
            if (false !== ($authority[$flag] ?? null)) {
                throw new \RuntimeException('OCI302_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_INVALID');
            }
        }
        if ('imperium.operational-cognition-lease-interruption-enforcement-authority/v1' !== ($authority['schema'] ?? null)
            || $id !== ($authority['authority_id'] ?? null) || $bindingId !== ($authority['enforcer']['binding_id'] ?? null)
            || 'clavium.locksmith' !== ($authority['enforcer']['seat'] ?? null)
            || 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE' !== ($authority['affected_scope']['kind'] ?? null)
            || 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE' !== ($authority['permitted_transition'] ?? null)
            || true !== ($authority['single_use'] ?? null) || true !== ($authority['exercisable'] ?? null) || false !== ($authority['consumed'] ?? null)
            || new \DateTimeImmutable((string) ($authority['issued_at'] ?? '2999-01-01')) > $at
            || new \DateTimeImmutable((string) ($authority['expires_at'] ?? '1970-01-01')) <= $at
            || true !== ($authority['sealed'] ?? null)) {
            throw new \RuntimeException('OCI302_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_INVALID');
        }
    }

    private function assertNoClaim(string $leaseId): void
    {
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $claim = $this->validator->requireIntact($this->validator->read($path, 'OCI306_OPERATIONAL_LEASE_ALREADY_CLAIMED'), 'OCI306_OPERATIONAL_LEASE_ALREADY_CLAIMED');
            if (($claim['lease_consumption']['lease_id'] ?? null) === $leaseId) {
                throw new \RuntimeException('OCI306_OPERATIONAL_LEASE_ALREADY_CLAIMED');
            }
        }
    }

    private function currentLocksmith(string $id, string $instanceId): array
    {
        $occupancy = $this->record(self::OCCUPANCY, $id, 'binding_id', 'OCI305_LOCKSMITH_ENFORCER_INVALID');
        if ('imperium.clavium-locksmith-occupancy/v1' !== ($occupancy['schema'] ?? null)
            || $instanceId !== ($occupancy['instance_id'] ?? null) || 'clavium.locksmith' !== ($occupancy['seat'] ?? null)
            || 'ACTIVE' !== ($occupancy['status'] ?? null) || true === ($occupancy['credential_disclosure_authority'] ?? null)
            || true === ($occupancy['execution_authority'] ?? null)) {
            throw new \RuntimeException('OCI305_LOCKSMITH_ENFORCER_INVALID');
        }
        foreach (glob($this->root.'/'.self::OCCUPANCY.'/*.json') ?: [] as $path) {
            $other = $this->validator->requireIntact($this->validator->read($path, 'OCI305_LOCKSMITH_ENFORCER_INVALID'), 'OCI305_LOCKSMITH_ENFORCER_INVALID');
            if ('clavium.locksmith' === ($other['seat'] ?? null) && 'ACTIVE' === ($other['status'] ?? null)
                && $instanceId === ($other['instance_id'] ?? null) && $id !== ($other['binding_id'] ?? null)) {
                throw new \RuntimeException('OCI305_LOCKSMITH_ENFORCER_INVALID');
            }
        }

        return ['seat' => 'clavium.locksmith', 'binding_id' => $id, 'binding_digest' => $occupancy['record_digest'], 'manifestation_id' => $occupancy['manifestation_id'], 'occupancy_generation' => $occupancy['occupancy_generation']];
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
