<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Governance\InternalGovernanceLeaseInterruptionReconstructionService;
use PHPUnit\Framework\TestCase;

final class InternalGovernanceLeaseInterruptionReconstructionServiceTest extends TestCase
{
    private const LEASE = 'governance-cognition-lease-aaaaaaaaaaaaaaaaaaaa';

    public function testFourArtifactLeaseInterruptionReconstructsWithClaimAbsent(): void
    {
        $root = sys_get_temp_dir().'/imperium-cag-lease-reconstruct-'.bin2hex(random_bytes(5));
        try {
            $this->fixtures($root);
            $result = (new InternalGovernanceLeaseInterruptionReconstructionService($root))->reconstruct(self::LEASE);
            self::assertSame('INTERNAL_GOVERNANCE_LEASE_INTERRUPTION_RECONSTRUCTED', $result['status']);
            self::assertSame('FOUR_ARTIFACT_UNCLAIMED_LEASE_INTERRUPTION_SUBCHAIN_ONLY', $result['completeness_claim']);
            self::assertSame(4, $result['verified_artifact_count']);
            self::assertTrue($result['durable_invocation_claim_absent']);
            self::assertTrue($result['read_only']);
            foreach (['cognition_invoked', 'claim_created', 'state_mutated', 'lease_closed', 'propagation_performed', 'authority_granted', 'continuation_authority'] as $flag) {
                self::assertFalse($result[$flag]);
            }
        } finally {
            $this->remove($root);
        }
    }

    public function testUnexpectedClaimInvalidatesReconstruction(): void
    {
        $root = sys_get_temp_dir().'/imperium-cag-lease-reconstruct-claim-'.bin2hex(random_bytes(5));
        try {
            $this->fixtures($root);
            $claim = 'governance-cognition-invocation-claim-'.str_repeat('f', 20);
            $this->write($root.'/var/imperium/runtime/governance-cognition-invocation-claims/'.$claim.'.json', ['lease_consumption' => ['lease_id' => self::LEASE, 'consumed' => true]]);
            $this->expectExceptionMessage('CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            (new InternalGovernanceLeaseInterruptionReconstructionService($root))->reconstruct(self::LEASE);
        } finally {
            $this->remove($root);
        }
    }

    public function testDuplicateResultFailsStopped(): void
    {
        $root = sys_get_temp_dir().'/imperium-cag-lease-reconstruct-duplicate-'.bin2hex(random_bytes(5));
        try {
            $result = $this->fixtures($root);
            $result['result_id'] = 'revocation-lease-enforcement-result-'.str_repeat('f', 20);
            unset($result['record_digest']);
            $this->write($root.'/var/imperium/runtime/continuous-governance-lease-enforcement-results/'.$result['result_id'].'.json', $result);
            $this->expectExceptionMessage('CAG1501_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
            (new InternalGovernanceLeaseInterruptionReconstructionService($root))->reconstruct(self::LEASE);
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root): array
    {
        $lease = $this->record(['schema' => 'imperium.clavium-governance-cognition-lease/v1', 'lease_id' => self::LEASE, 'instance_id' => 'imperium-test', 'status' => 'GOVERNANCE_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'lease_single_use' => true, 'lease_consumed' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/offices/clavium/governance-cognition-leases/'.self::LEASE.'.json', $lease);
        $actor = ['seat' => 'curia.seneschal', 'binding_id' => 'operational-seat-binding-'.str_repeat('b', 20)];
        $enforcer = ['seat' => 'clavium.locksmith', 'binding_id' => 'clavium-locksmith-binding-'.str_repeat('c', 20)];
        $scope = ['kind' => 'UNCLAIMED_INTERNAL_GOVERNANCE_COGNITION_LEASE', 'lease' => ['id' => self::LEASE, 'digest' => $lease['record_digest']], 'lease_consumed' => false];
        $dispositionId = 'revocation-disposition-'.str_repeat('d', 20);
        $disposition = $this->record(['schema' => 'imperium.continuous-governance-revocation-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => 'imperium-test', 'disposition' => 'INTERRUPT', 'competent_actor' => $actor, 'affected_scope' => $scope, 'enforcement_required' => true, 'enforcement_authority_opened' => false, 'state_mutated' => false, 'authority_granted' => false, 'continuation_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/runtime/continuous-governance-revocation-dispositions/'.$dispositionId.'.json', $disposition);
        $authorityId = 'revocation-enforcement-authority-'.str_repeat('e', 20);
        $authority = $this->record(['schema' => 'imperium.continuous-governance-enforcement-authority/v1', 'authority_id' => $authorityId, 'instance_id' => 'imperium-test', 'source_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']], 'issuer' => $actor, 'enforcer' => $enforcer, 'affected_scope' => $scope, 'permitted_transition' => 'DENY_DURABLE_GOVERNANCE_INVOCATION_CLAIM_FOR_EXACT_LEASE', 'issued_at' => '2026-08-27T12:01:00+00:00', 'expires_at' => '2026-08-27T12:05:00+00:00', 'single_use' => true, 'exercisable' => true, 'consumed' => false, 'continuing_authority' => false, 'external_action_authority' => false, 'perimeter_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/runtime/continuous-governance-enforcement-authorities/'.$authorityId.'.json', $authority);
        $resultId = 'revocation-lease-enforcement-result-'.str_repeat('1', 20);
        $result = $this->record(['schema' => 'imperium.continuous-governance-lease-enforcement-result/v1', 'result_id' => $resultId, 'instance_id' => 'imperium-test', 'source_authority' => ['id' => $authorityId, 'digest' => $authority['record_digest']], 'source_disposition' => $authority['source_disposition'], 'enforcer' => $enforcer, 'affected_scope' => $scope, 'performed_transition' => 'DENY_DURABLE_GOVERNANCE_INVOCATION_CLAIM_FOR_EXACT_LEASE', 'authority_consumed' => true, 'claim_created' => false, 'lease_consumed' => false, 'lease_mutated' => false, 'lease_closed' => false, 'credential_mutated' => false, 'propagation_performed' => false, 'continuation_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/runtime/continuous-governance-lease-enforcement-results/'.$resultId.'.json', $result);
        return $result;
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function write(string $path, array $record): void
    {
        if (!isset($record['record_digest'])) {
            $record = $this->record($record);
        }
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
