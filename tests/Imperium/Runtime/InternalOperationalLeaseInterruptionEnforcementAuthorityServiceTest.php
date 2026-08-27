<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Governance\InternalOperationalLeaseInterruptionEnforcementAuthorityService;
use PHPUnit\Framework\TestCase;

final class InternalOperationalLeaseInterruptionEnforcementAuthorityServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-lease-interruption-authority-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testOpensOneExactExpiringLocksmithAuthorityAndNothingElse(): void
    {
        [$dispositionId, $locksmithId] = $this->fixtures();
        $service = new InternalOperationalLeaseInterruptionEnforcementAuthorityService($this->root);
        $issuedAt = new \DateTimeImmutable('2026-08-27T12:03:00+00:00');
        $authority = $service->issue($dispositionId, $locksmithId, $issuedAt, $issuedAt->modify('+4 minutes'));

        self::assertSame('imperium.operational-cognition-lease-interruption-enforcement-authority/v1', $authority['schema']);
        self::assertSame('clavium.locksmith', $authority['enforcer']['seat']);
        self::assertSame('DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE', $authority['permitted_transition']);
        self::assertTrue($authority['single_use']);
        self::assertTrue($authority['exercisable']);
        self::assertFalse($authority['consumed']);
        foreach (['claim_creation_authority', 'cognition_authority', 'credential_authority', 'provider_journal_authority', 'network_access_authority', 'lease_mutation_authority', 'lease_closure_authority', 'propagation_authority', 'continuing_authority', 'external_action_authority', 'perimeter_authority'] as $flag) {
            self::assertFalse($authority[$flag]);
        }
        self::assertSame($authority, $service->issue($dispositionId, $locksmithId, $issuedAt, $issuedAt->modify('+4 minutes')));
    }

    public function testAuthorityCannotOutliveEarliestSourceExpiry(): void
    {
        [$dispositionId, $locksmithId] = $this->fixtures();
        $this->expectExceptionMessage('OCI202_OPERATIONAL_LEASE_INTERRUPTION_DISPOSITION_INVALID');
        (new InternalOperationalLeaseInterruptionEnforcementAuthorityService($this->root))->issue($dispositionId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'), new \DateTimeImmutable('2026-08-27T12:07:01+00:00'));
    }

    public function testStructurallyDivergentPriorAuthorityFailsReplayStopped(): void
    {
        [$dispositionId, $locksmithId] = $this->fixtures();
        $service = new InternalOperationalLeaseInterruptionEnforcementAuthorityService($this->root);
        $issuedAt = new \DateTimeImmutable('2026-08-27T12:03:00+00:00');
        $prior = $service->issue($dispositionId, $locksmithId, $issuedAt, $issuedAt->modify('+4 minutes'));
        unset($prior['record_digest']);
        $prior['network_access_authority'] = true;
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities/'.$prior['authority_id'].'.json', $this->sealed($prior));

        $this->expectExceptionMessage('OCI206_OPERATIONAL_LEASE_ENFORCEMENT_AUTHORITY_CONFLICT');
        $service->issue($dispositionId, $locksmithId, $issuedAt, $issuedAt->modify('+4 minutes'));
    }

    public function testExistingClaimAndDuplicateCurrentLocksmithFailStopped(): void
    {
        [$dispositionId, $locksmithId, $leaseId, $locksmith] = $this->fixtures();
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/claim.json', $this->sealed(['lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true]]));
        $this->expectExceptionMessage('OCI205_OPERATIONAL_LEASE_NO_LONGER_ENFORCEABLE_UNCLAIMED');
        (new InternalOperationalLeaseInterruptionEnforcementAuthorityService($this->root))->issue($dispositionId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'), new \DateTimeImmutable('2026-08-27T12:07:00+00:00'));
    }

    public function testDuplicateCurrentLocksmithFailsStopped(): void
    {
        [$dispositionId, $locksmithId, , $locksmith] = $this->fixtures();
        unset($locksmith['record_digest']);
        $locksmith['binding_id'] = 'clavium-locksmith-binding-'.str_repeat('f', 20);
        $locksmith['manifestation_id'] = 'manifestation-other-locksmith';
        $this->write($this->root.'/var/imperium/offices/clavium/occupancy/'.$locksmith['binding_id'].'.json', $this->sealed($locksmith));
        $this->expectExceptionMessage('OCI204_LOCKSMITH_ENFORCER_NOT_CURRENT');
        (new InternalOperationalLeaseInterruptionEnforcementAuthorityService($this->root))->issue($dispositionId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'), new \DateTimeImmutable('2026-08-27T12:07:00+00:00'));
    }

    private function fixtures(): array
    {
        $instance = 'imperium-test';
        $seneschalId = 'curia-seneschal-binding-'.str_repeat('1', 20);
        $seneschal = $this->sealed(['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $seneschalId, 'instance_id' => $instance, 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 2, 'status' => 'ACTIVE', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/occupancy/'.$seneschalId.'.json', $seneschal);
        $issuer = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 2];

        $locksmithId = 'clavium-locksmith-binding-'.str_repeat('2', 20);
        $locksmith = $this->sealed(['schema' => 'imperium.clavium-locksmith-occupancy/v1', 'binding_id' => $locksmithId, 'instance_id' => $instance, 'seat' => 'clavium.locksmith', 'manifestation_id' => 'manifestation-locksmith', 'occupancy_generation' => 3, 'status' => 'ACTIVE', 'credential_disclosure_authority' => false, 'execution_authority' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/clavium/occupancy/'.$locksmithId.'.json', $locksmith);

        $authorizationId = 'bounded-execution-authorization-'.str_repeat('3', 20);
        $sourceAuthorizer = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 2];
        $authorization = $this->sealed(['schema' => 'imperium.curia-bounded-execution-authorization/v1', 'authorization_id' => $authorizationId, 'instance_id' => $instance, 'authorizer' => $sourceAuthorizer, 'status' => 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION', 'bounded_execution_authority' => true, 'bounded_execution_authority_exercisable' => true, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/bounded-execution-authorizations/'.$authorizationId.'.json', $authorization);
        $requestId = 'operational-cognition-request-'.str_repeat('4', 20);
        $request = $this->sealed(['schema' => 'imperium.curia-operational-cognition-request/v1', 'request_id' => $requestId, 'instance_id' => $instance, 'source_bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']], 'authorizer' => $sourceAuthorizer, 'expires_at' => '2026-08-27T12:15:00+00:00', 'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json', $request);
        $decisionId = 'operational-provider-resource-decision-'.str_repeat('5', 20);
        $decision = $this->sealed(['schema' => 'imperium.imperator-operational-provider-resource-decision/v1', 'decision_id' => $decisionId, 'instance_id' => $instance, 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'disposition' => 'AUTHORIZED', 'expires_at' => '2026-08-27T12:10:00+00:00', 'status' => 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/imperator/operational-provider-resource-decisions/'.$decisionId.'.json', $decision);
        $leaseId = 'operational-cognition-lease-'.str_repeat('6', 20);
        $lease = $this->sealed(['schema' => 'imperium.clavium-operational-cognition-lease/v1', 'lease_id' => $leaseId, 'instance_id' => $instance, 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'expires_at' => '2026-08-27T12:07:00+00:00', 'status' => 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'lease_single_use' => true, 'lease_consumed' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/clavium/operational-cognition-leases/'.$leaseId.'.json', $lease);

        $lineage = ['bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']], 'operational_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'imperator_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'operational_cognition_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']]];
        $scope = ['kind' => 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE', 'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'case_id' => 'case-test', 'target' => ['seat' => 'foundry.artificer'], 'lease_consumed' => false];
        $dispositionId = 'operational-lease-interruption-disposition-'.str_repeat('7', 20);
        $disposition = $this->sealed(['schema' => 'imperium.operational-cognition-lease-interruption-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => $instance, 'disposition' => 'INTERRUPT', 'competent_actor' => $issuer, 'authority_basis' => ['jurisdiction' => 'SOURCE_AUTHORIZER_CURRENT_INTERNAL_OPERATIONAL_ITERATION', 'source_bounded_execution_authorization' => $lineage['bounded_execution_authorization'], 'source_occupancy' => ['id' => $seneschalId, 'digest' => $seneschal['record_digest']]], 'lineage' => $lineage, 'affected_scope' => $scope, 'effective_at' => '2026-08-27T12:02:30+00:00', 'enforcement_required' => true, 'enforcement_authority_opened' => false, 'claim_created' => false, 'cognition_authority_consumed' => false, 'lease_consumed' => false, 'lease_mutated' => false, 'lease_closed' => false, 'credential_resolved' => false, 'provider_journal_created' => false, 'network_access_performed' => false, 'propagation_performed' => false, 'authority_granted' => false, 'continuation_authority' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-dispositions/'.$dispositionId.'.json', $disposition);

        return [$dispositionId, $locksmithId, $leaseId, $locksmith];
    }

    private function sealed(array $record): array
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
