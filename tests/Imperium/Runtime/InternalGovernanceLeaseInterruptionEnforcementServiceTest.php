<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\GovernanceCognitionInvocationClaimService;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityRegistry;
use App\Imperium\Runtime\Governance\InternalGovernanceLeaseInterruptionEnforcementService;
use PHPUnit\Framework\TestCase;

final class InternalGovernanceLeaseInterruptionEnforcementServiceTest extends TestCase
{
    public function testExactLeaseEnforcementDeniesLaterClaimWithoutMutatingLease(): void
    {
        $root = sys_get_temp_dir().'/imperium-cag-lease-enforcement-'.bin2hex(random_bytes(5));
        try {
            [$authority, $locksmith, $leaseId] = $this->fixtures($root);
            $service = new InternalGovernanceLeaseInterruptionEnforcementService($root);
            $result = $service->enforce($authority, $locksmith, new \DateTimeImmutable('2026-08-27T12:02:00+00:00'));

            self::assertTrue($result['authority_consumed']);
            foreach (['claim_created', 'lease_consumed', 'lease_mutated', 'lease_closed', 'credential_mutated', 'propagation_performed', 'continuation_authority'] as $flag) {
                self::assertFalse($result[$flag]);
            }
            self::assertSame([], glob($root.'/var/imperium/runtime/governance-cognition-invocation-claims/*.json') ?: []);
            $lease = json_decode((string) file_get_contents($root.'/var/imperium/offices/clavium/governance-cognition-leases/'.$leaseId.'.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertFalse($lease['lease_consumed']);

            $this->expectExceptionMessage('GCA405_GOVERNANCE_LEASE_INTERRUPTED_PRE_CLAIM');
            (new GovernanceCognitionInvocationClaimService($root, new GovernanceCognitionAuthorityRegistry()))
                ->claim($leaseId, 'authority-test', new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public function testExpiredAuthorityFailsStopped(): void
    {
        $root = sys_get_temp_dir().'/imperium-cag-lease-enforcement-expiry-'.bin2hex(random_bytes(5));
        try {
            [$authority, $locksmith] = $this->fixtures($root);
            $this->expectExceptionMessage('CAG1402_LEASE_ENFORCEMENT_AUTHORITY_INVALID');
            (new InternalGovernanceLeaseInterruptionEnforcementService($root))
                ->enforce($authority, $locksmith, new \DateTimeImmutable('2026-08-27T12:05:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public function testExistingClaimFailsStopped(): void
    {
        $root = sys_get_temp_dir().'/imperium-cag-lease-enforcement-claim-'.bin2hex(random_bytes(5));
        try {
            [$authority, $locksmith, $leaseId] = $this->fixtures($root);
            $claim = 'governance-cognition-invocation-claim-'.str_repeat('f', 20);
            $this->write($root.'/var/imperium/runtime/governance-cognition-invocation-claims/'.$claim.'.json', $this->record(['lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true]]));
            $this->expectExceptionMessage('CAG1405_GOVERNANCE_LEASE_NO_LONGER_ENFORCEABLE_UNCLAIMED');
            (new InternalGovernanceLeaseInterruptionEnforcementService($root))
                ->enforce($authority, $locksmith, new \DateTimeImmutable('2026-08-27T12:02:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root): array
    {
        $leaseId = 'governance-cognition-lease-'.str_repeat('a', 20);
        $locksmith = 'clavium-locksmith-binding-'.str_repeat('b', 20);
        $lease = $this->record(['schema' => 'imperium.clavium-governance-cognition-lease/v1', 'lease_id' => $leaseId, 'instance_id' => 'imperium-test', 'expires_at' => '2026-08-27T12:05:00+00:00', 'status' => 'GOVERNANCE_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'lease_single_use' => true, 'lease_consumed' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/offices/clavium/governance-cognition-leases/'.$leaseId.'.json', $lease);

        $occupancy = $this->record(['schema' => 'imperium.clavium-locksmith-occupancy/v1', 'binding_id' => $locksmith, 'instance_id' => 'imperium-test', 'seat' => 'clavium.locksmith', 'manifestation_id' => 'locksmith', 'occupancy_generation' => 1, 'status' => 'ACTIVE']);
        $this->write($root.'/var/imperium/offices/clavium/occupancy/'.$locksmith.'.json', $occupancy);
        $enforcer = ['seat' => 'clavium.locksmith', 'binding_id' => $locksmith, 'binding_digest' => $occupancy['record_digest'], 'manifestation_id' => 'locksmith', 'occupancy_generation' => 1];
        $scope = ['kind' => 'UNCLAIMED_INTERNAL_GOVERNANCE_COGNITION_LEASE', 'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'lease_consumed' => false];

        $dispositionId = 'revocation-disposition-'.str_repeat('c', 20);
        $disposition = $this->record(['schema' => 'imperium.continuous-governance-revocation-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => 'imperium-test', 'disposition' => 'INTERRUPT', 'affected_scope' => $scope, 'sealed' => true]);
        $this->write($root.'/var/imperium/runtime/continuous-governance-revocation-dispositions/'.$dispositionId.'.json', $disposition);

        $authorityId = 'revocation-enforcement-authority-'.str_repeat('d', 20);
        $authority = $this->record(['schema' => 'imperium.continuous-governance-enforcement-authority/v1', 'authority_id' => $authorityId, 'instance_id' => 'imperium-test', 'source_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']], 'issuer' => ['seat' => 'curia.seneschal'], 'enforcer' => $enforcer, 'affected_scope' => $scope, 'permitted_transition' => 'DENY_DURABLE_GOVERNANCE_INVOCATION_CLAIM_FOR_EXACT_LEASE', 'issued_at' => '2026-08-27T12:01:00+00:00', 'expires_at' => '2026-08-27T12:05:00+00:00', 'single_use' => true, 'exercisable' => true, 'consumed' => false, 'continuing_authority' => false, 'external_action_authority' => false, 'perimeter_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/runtime/continuous-governance-enforcement-authorities/'.$authorityId.'.json', $authority);

        return [$authorityId, $locksmith, $leaseId];
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function write(string $path, array $record): void
    {
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
