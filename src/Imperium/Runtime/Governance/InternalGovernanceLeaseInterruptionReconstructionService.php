<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Governance;

use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalGovernanceLeaseInterruptionReconstructionService
{
    private const LEASES = 'var/imperium/offices/clavium/governance-cognition-leases';
    private const CLAIMS = 'var/imperium/runtime/governance-cognition-invocation-claims';
    private const DISPOSITIONS = 'var/imperium/runtime/continuous-governance-revocation-dispositions';
    private const AUTHORITIES = 'var/imperium/runtime/continuous-governance-enforcement-authorities';
    private const RESULTS = 'var/imperium/runtime/continuous-governance-lease-enforcement-results';
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function reconstruct(string $leaseId): array
    {
        if (!preg_match('/^governance-cognition-lease-[a-f0-9]{20}$/', $leaseId)) {
            throw new \InvalidArgumentException('CAG1500_LEASE_INTERRUPTION_RECONSTRUCTION_INPUT_INVALID');
        }
        $lease = $this->record(self::LEASES, $leaseId, 'lease_id');
        $result = $this->singleResult($leaseId);
        $authority = $this->source(self::AUTHORITIES, $result['source_authority'] ?? [], 'authority_id');
        $disposition = $this->source(self::DISPOSITIONS, $authority['source_disposition'] ?? [], 'disposition_id');
        $scope = ['kind' => 'UNCLAIMED_INTERNAL_GOVERNANCE_COGNITION_LEASE', 'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'lease_consumed' => false];

        if ('imperium.clavium-governance-cognition-lease/v1' !== ($lease['schema'] ?? null)
            || 'GOVERNANCE_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
            || true !== ($lease['lease_single_use'] ?? null) || false !== ($lease['lease_consumed'] ?? null) || true !== ($lease['sealed'] ?? null)
            || 'imperium.continuous-governance-revocation-disposition/v1' !== ($disposition['schema'] ?? null)
            || 'INTERRUPT' !== ($disposition['disposition'] ?? null) || ($disposition['affected_scope'] ?? null) !== $scope
            || true !== ($disposition['enforcement_required'] ?? null) || false !== ($disposition['enforcement_authority_opened'] ?? null)
            || false !== ($disposition['state_mutated'] ?? null) || false !== ($disposition['authority_granted'] ?? null)
            || false !== ($disposition['continuation_authority'] ?? null) || true !== ($disposition['sealed'] ?? null)
            || 'imperium.continuous-governance-enforcement-authority/v1' !== ($authority['schema'] ?? null)
            || ($authority['source_disposition'] ?? null) !== ['id' => $disposition['disposition_id'], 'digest' => $disposition['record_digest']]
            || ($authority['issuer'] ?? null) !== ($disposition['competent_actor'] ?? null)
            || ($authority['affected_scope'] ?? null) !== $scope || 'DENY_DURABLE_GOVERNANCE_INVOCATION_CLAIM_FOR_EXACT_LEASE' !== ($authority['permitted_transition'] ?? null)
            || true !== ($authority['single_use'] ?? null) || true !== ($authority['exercisable'] ?? null) || false !== ($authority['consumed'] ?? null)
            || new \DateTimeImmutable((string) ($authority['expires_at'] ?? '1970-01-01')) <= new \DateTimeImmutable((string) ($authority['issued_at'] ?? '2999-01-01'))
            || false !== ($authority['continuing_authority'] ?? null) || false !== ($authority['external_action_authority'] ?? null) || false !== ($authority['perimeter_authority'] ?? null) || true !== ($authority['sealed'] ?? null)
            || 'imperium.continuous-governance-lease-enforcement-result/v1' !== ($result['schema'] ?? null)
            || ($result['source_disposition'] ?? null) !== ($authority['source_disposition'] ?? null) || ($result['enforcer'] ?? null) !== ($authority['enforcer'] ?? null)
            || ($result['affected_scope'] ?? null) !== $scope || ($result['performed_transition'] ?? null) !== ($authority['permitted_transition'] ?? null)
            || true !== ($result['authority_consumed'] ?? null) || false !== ($result['claim_created'] ?? null) || false !== ($result['lease_consumed'] ?? null)
            || false !== ($result['lease_mutated'] ?? null) || false !== ($result['lease_closed'] ?? null) || false !== ($result['credential_mutated'] ?? null)
            || false !== ($result['propagation_performed'] ?? null) || false !== ($result['continuation_authority'] ?? null) || true !== ($result['sealed'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($disposition['instance_id'] ?? null) || ($lease['instance_id'] ?? null) !== ($authority['instance_id'] ?? null)
            || ($lease['instance_id'] ?? null) !== ($result['instance_id'] ?? null)) {
            throw new \RuntimeException('CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $claim = $this->validator->requireIntact($this->validator->read($path, 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            if (($claim['lease_consumption']['lease_id'] ?? null) === $leaseId) {
                throw new \RuntimeException('CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            }
        }

        return ['schema' => 'imperium.continuous-governance-lease-interruption-reconstruction/v1', 'status' => 'INTERNAL_GOVERNANCE_LEASE_INTERRUPTION_RECONSTRUCTED', 'completeness_claim' => 'FOUR_ARTIFACT_UNCLAIMED_LEASE_INTERRUPTION_SUBCHAIN_ONLY', 'root_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'instance_id' => $lease['instance_id'], 'included_evidence' => ['governance_cognition_lease' => $lease, 'revocation_disposition' => $disposition, 'enforcement_authority' => $authority, 'enforcement_result' => $result], 'verified_artifact_count' => 4, 'durable_invocation_claim_absent' => true, 'read_only' => true, 'cognition_invoked' => false, 'claim_created' => false, 'state_mutated' => false, 'lease_closed' => false, 'propagation_performed' => false, 'authority_granted' => false, 'continuation_authority' => false];
    }

    private function singleResult(string $leaseId): array
    {
        $matches = [];
        foreach (glob($this->root.'/'.self::RESULTS.'/*.json') ?: [] as $path) {
            $result = $this->validator->requireIntact($this->validator->read($path, 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            if (($result['affected_scope']['lease']['id'] ?? null) === $leaseId) {
                $matches[] = $result;
            }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException('CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }
        return $matches[0];
    }

    private function source(string $directory, array $reference, string $key): array
    {
        return $this->validator->resolve($this->root.'/'.$directory, $reference, 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID', 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID', $key);
    }

    private function record(string $directory, string $id, string $key): array
    {
        $record = $this->validator->requireIntact($this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID'), 'CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        if ($id !== ($record[$key] ?? null)) {
            throw new \RuntimeException('CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        }
        return $record;
    }
}
