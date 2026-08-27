<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Governance\InternalOperationalLeaseInterruptionReconstructionService;
use PHPUnit\Framework\TestCase;

final class InternalOperationalLeaseInterruptionReconstructionServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-lease-interruption-reconstruction-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testReconstructsExactNineArtifactChainAndMechanicalClaimAbsenceReadOnly(): void
    {
        [$leaseId] = $this->fixtures();
        $reconstruction = (new InternalOperationalLeaseInterruptionReconstructionService($this->root))->reconstruct($leaseId);

        self::assertSame('INTERNAL_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTED', $reconstruction['status']);
        self::assertSame('NINE_ARTIFACT_OPERATIONAL_LEASE_INTERRUPTION_CHAIN_ONLY', $reconstruction['completeness_claim']);
        self::assertSame(9, $reconstruction['verified_artifact_count']);
        self::assertCount(9, $reconstruction['included_evidence']);
        self::assertTrue($reconstruction['durable_invocation_claim_absent']);
        self::assertTrue($reconstruction['read_only']);
        foreach (['cognition_invoked', 'credential_resolved', 'provider_journal_created', 'network_access_performed', 'state_mutated', 'lease_closed', 'propagation_performed', 'authority_granted', 'continuation_authority'] as $flag) {
            self::assertFalse($reconstruction[$flag]);
        }
    }

    public function testAnyMechanicalClaimForLeaseFailsReconstructionStopped(): void
    {
        [$leaseId] = $this->fixtures();
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/claim.json', $this->sealed(['lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true]]));

        $this->expectExceptionMessage('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        (new InternalOperationalLeaseInterruptionReconstructionService($this->root))->reconstruct($leaseId);
    }

    public function testDuplicateResultFailsReconstructionStopped(): void
    {
        [$leaseId, $result] = $this->fixtures();
        unset($result['record_digest']);
        $result['result_id'] = 'operational-lease-interruption-enforcement-result-'.str_repeat('f', 20);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/'.$result['result_id'].'.json', $this->sealed($result));

        $this->expectExceptionMessage('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        (new InternalOperationalLeaseInterruptionReconstructionService($this->root))->reconstruct($leaseId);
    }

    public function testHistoricalReconstructionSurvivesCurrentSeneschalRotation(): void
    {
        [$leaseId, , $seneschal] = $this->fixtures();
        unset($seneschal['record_digest']);
        $seneschal['binding_id'] = 'curia-seneschal-binding-'.str_repeat('f', 20);
        $seneschal['manifestation_id'] = 'manifestation-substitute';
        $this->write($this->root.'/var/imperium/offices/curia/occupancy/'.$seneschal['binding_id'].'.json', $this->sealed($seneschal));

        $reconstruction = (new InternalOperationalLeaseInterruptionReconstructionService($this->root))->reconstruct($leaseId);

        self::assertFalse($reconstruction['present_occupancy_continuity']['seneschal']);
        self::assertFalse($reconstruction['present_occupancy_continuity']['required_for_historical_reconstruction']);
        self::assertSame(9, $reconstruction['verified_artifact_count']);
    }

    public function testMissingConsumedTimestampFailsReconstructionStopped(): void
    {
        [$leaseId, $result] = $this->fixtures();
        unset($result['record_digest'], $result['consumed_at']);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/'.$result['result_id'].'.json', $this->sealed($result));

        $this->expectExceptionMessage('OCI401_OPERATIONAL_LEASE_INTERRUPTION_RECONSTRUCTION_INVALID');
        (new InternalOperationalLeaseInterruptionReconstructionService($this->root))->reconstruct($leaseId);
    }

    private function fixtures(): array
    {
        $instance = 'imperium-test';
        $seneschalId = 'curia-seneschal-binding-'.str_repeat('1', 20);
        $seneschal = $this->sealed(['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $seneschalId, 'instance_id' => $instance, 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 2, 'status' => 'ACTIVE', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/occupancy/'.$seneschalId.'.json', $seneschal);
        $sourceAuthorizer = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 2];
        $issuer = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 2];

        $locksmithId = 'clavium-locksmith-binding-'.str_repeat('2', 20);
        $locksmith = $this->sealed(['schema' => 'imperium.clavium-locksmith-occupancy/v1', 'binding_id' => $locksmithId, 'instance_id' => $instance, 'seat' => 'clavium.locksmith', 'manifestation_id' => 'manifestation-locksmith', 'occupancy_generation' => 3, 'status' => 'ACTIVE', 'credential_disclosure_authority' => false, 'execution_authority' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/clavium/occupancy/'.$locksmithId.'.json', $locksmith);
        $enforcer = ['seat' => 'clavium.locksmith', 'binding_id' => $locksmithId, 'binding_digest' => $locksmith['record_digest'], 'manifestation_id' => 'manifestation-locksmith', 'occupancy_generation' => 3];

        $authorizationId = 'bounded-execution-authorization-'.str_repeat('3', 20);
        $authorization = $this->sealed(['schema' => 'imperium.curia-bounded-execution-authorization/v1', 'authorization_id' => $authorizationId, 'instance_id' => $instance, 'authorizer' => $sourceAuthorizer, 'expires_at' => '2026-08-27T12:12:00+00:00', 'status' => 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/bounded-execution-authorizations/'.$authorizationId.'.json', $authorization);
        $requestId = 'operational-cognition-request-'.str_repeat('4', 20);
        $request = $this->sealed(['schema' => 'imperium.curia-operational-cognition-request/v1', 'request_id' => $requestId, 'instance_id' => $instance, 'source_bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']], 'authorizer' => $sourceAuthorizer, 'expires_at' => '2026-08-27T12:10:00+00:00', 'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json', $request);
        $decisionId = 'operational-provider-resource-decision-'.str_repeat('5', 20);
        $decision = $this->sealed(['schema' => 'imperium.imperator-operational-provider-resource-decision/v1', 'decision_id' => $decisionId, 'instance_id' => $instance, 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'disposition' => 'AUTHORIZED', 'expires_at' => '2026-08-27T12:09:00+00:00', 'status' => 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/imperator/operational-provider-resource-decisions/'.$decisionId.'.json', $decision);
        $leaseId = 'operational-cognition-lease-'.str_repeat('6', 20);
        $target = ['seat' => 'foundry.artificer'];
        $lease = $this->sealed(['schema' => 'imperium.clavium-operational-cognition-lease/v1', 'lease_id' => $leaseId, 'instance_id' => $instance, 'case_id' => 'case-test', 'target' => $target, 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'expires_at' => '2026-08-27T12:07:00+00:00', 'status' => 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'lease_single_use' => true, 'lease_consumed' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/clavium/operational-cognition-leases/'.$leaseId.'.json', $lease);

        $lineage = ['bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']], 'operational_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'imperator_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'operational_cognition_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']]];
        $scope = ['kind' => 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE', 'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'case_id' => 'case-test', 'target' => $target, 'lease_consumed' => false];
        $dispositionId = 'operational-lease-interruption-disposition-'.str_repeat('7', 20);
        $disposition = $this->sealed(['schema' => 'imperium.operational-cognition-lease-interruption-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => $instance, 'disposition' => 'INTERRUPT', 'competent_actor' => $issuer, 'authority_basis' => ['source_bounded_execution_authorization' => $lineage['bounded_execution_authorization'], 'source_occupancy' => ['id' => $seneschalId, 'digest' => $seneschal['record_digest']]], 'lineage' => $lineage, 'affected_scope' => $scope, 'enforcement_required' => true, 'enforcement_authority_opened' => false, 'authority_granted' => false, 'continuation_authority' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-dispositions/'.$dispositionId.'.json', $disposition);
        $authorityId = 'operational-lease-interruption-enforcement-authority-'.str_repeat('8', 20);
        $authority = ['schema' => 'imperium.operational-cognition-lease-interruption-enforcement-authority/v1', 'authority_id' => $authorityId, 'instance_id' => $instance, 'source_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']], 'issuer' => $issuer, 'enforcer' => $enforcer, 'lineage' => $lineage, 'affected_scope' => $scope, 'permitted_transition' => 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE', 'issued_at' => '2026-08-27T12:02:00+00:00', 'expires_at' => '2026-08-27T12:07:00+00:00', 'single_use' => true, 'exercisable' => true, 'consumed' => false, 'sealed' => true];
        foreach (['claim_creation_authority', 'cognition_authority', 'credential_authority', 'provider_journal_authority', 'network_access_authority', 'lease_mutation_authority', 'lease_closure_authority', 'propagation_authority', 'continuing_authority', 'external_action_authority', 'perimeter_authority'] as $flag) {
            $authority[$flag] = false;
        }
        $authority = $this->sealed($authority);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities/'.$authorityId.'.json', $authority);
        $resultId = 'operational-lease-interruption-enforcement-result-'.str_repeat('9', 20);
        $result = ['schema' => 'imperium.operational-cognition-lease-interruption-enforcement-result/v1', 'result_id' => $resultId, 'instance_id' => $instance, 'source_authority' => ['id' => $authorityId, 'digest' => $authority['record_digest']], 'source_disposition' => $authority['source_disposition'], 'enforcer' => $enforcer, 'lineage' => $lineage, 'affected_scope' => $scope, 'performed_transition' => 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE', 'consumed_at' => '2026-08-27T12:03:00+00:00', 'authority_consumed' => true, 'sealed' => true];
        foreach (['claim_created', 'cognition_authority_consumed', 'lease_consumed', 'lease_mutated', 'lease_closed', 'request_mutated', 'decision_mutated', 'credential_resolved', 'credential_reference_disclosed', 'credential_material_present', 'credential_mutated', 'provider_invoked', 'provider_journal_created', 'network_access_performed', 'propagation_performed', 'continuation_authority', 'external_action_authority', 'perimeter_authority'] as $flag) {
            $result[$flag] = false;
        }
        $result = $this->sealed($result);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/'.$resultId.'.json', $result);

        return [$leaseId, $result, $seneschal];
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
