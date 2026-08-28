<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\OperationalCognitionInvocationClaimService;
use App\Imperium\Runtime\Governance\InternalOperationalLeaseInterruptionEnforcementService;
use PHPUnit\Framework\TestCase;

final class InternalOperationalLeaseInterruptionEnforcementServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-lease-interruption-enforcement-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testExactEnforcementDeniesLaterClaimWithoutMutatingSources(): void
    {
        [$leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId, $leasePath] = $this->fixtures();
        $service = new InternalOperationalLeaseInterruptionEnforcementService($this->root);
        $result = $service->enforce($enforcementAuthorityId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));

        self::assertSame('DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE', $result['performed_transition']);
        self::assertTrue($result['authority_consumed']);
        foreach (['claim_created', 'cognition_authority_consumed', 'lease_consumed', 'lease_mutated', 'lease_closed', 'request_mutated', 'decision_mutated', 'credential_resolved', 'credential_reference_disclosed', 'credential_material_present', 'credential_mutated', 'provider_invoked', 'provider_journal_created', 'network_access_performed', 'propagation_performed', 'continuation_authority', 'external_action_authority', 'perimeter_authority'] as $flag) {
            self::assertFalse($result[$flag]);
        }
        self::assertSame($result, $service->enforce($enforcementAuthorityId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00')));
        $lease = json_decode((string) file_get_contents($leasePath), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($lease['lease_consumed']);
        self::assertSame([], glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: []);

        $this->expectExceptionMessage('OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
        (new OperationalCognitionInvocationClaimService($this->root))->claim($leaseId, $cognitionAuthorityId, new \DateTimeImmutable('2026-08-27T12:04:00+00:00'));
    }

    public function testExistingClaimMakesEnforcementFailStopped(): void
    {
        [$leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId] = $this->fixtures();
        (new OperationalCognitionInvocationClaimService($this->root))->claim($leaseId, $cognitionAuthorityId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));

        $this->expectExceptionMessage('OCI306_OPERATIONAL_LEASE_ALREADY_CLAIMED');
        (new InternalOperationalLeaseInterruptionEnforcementService($this->root))->enforce($enforcementAuthorityId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:04:00+00:00'));
    }

    public function testMalformedDenialEvidenceFailsClaimAdmissionStopped(): void
    {
        [$leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId] = $this->fixtures();
        $result = (new InternalOperationalLeaseInterruptionEnforcementService($this->root))->enforce($enforcementAuthorityId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));
        $path = $this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/'.$result['result_id'].'.json';
        $result['claim_created'] = true;
        file_put_contents($path, json_encode($result, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
        (new OperationalCognitionInvocationClaimService($this->root))->claim($leaseId, $cognitionAuthorityId, new \DateTimeImmutable('2026-08-27T12:04:00+00:00'));
    }

    public function testTamperedLeaseSelectorCannotHideMalformedDenialEvidence(): void
    {
        [$leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId] = $this->fixtures();
        $result = (new InternalOperationalLeaseInterruptionEnforcementService($this->root))->enforce($enforcementAuthorityId, $locksmithId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));
        $path = $this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/'.$result['result_id'].'.json';
        $result['affected_scope']['lease']['id'] = 'operational-cognition-lease-'.str_repeat('f', 20);
        file_put_contents($path, json_encode($result, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM');
        (new OperationalCognitionInvocationClaimService($this->root))->claim($leaseId, $cognitionAuthorityId, new \DateTimeImmutable('2026-08-27T12:04:00+00:00'));
    }

    public function testStructurallyDivergentPriorResultFailsReplayStopped(): void
    {
        [, , $enforcementAuthorityId, $locksmithId] = $this->fixtures();
        $service = new InternalOperationalLeaseInterruptionEnforcementService($this->root);
        $at = new \DateTimeImmutable('2026-08-27T12:03:00+00:00');
        $prior = $service->enforce($enforcementAuthorityId, $locksmithId, $at);
        unset($prior['record_digest']);
        $prior['provider_invoked'] = true;
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/'.$prior['result_id'].'.json', $this->sealed($prior));

        $this->expectExceptionMessage('OCI307_OPERATIONAL_LEASE_ENFORCEMENT_RESULT_CONFLICT');
        $service->enforce($enforcementAuthorityId, $locksmithId, $at);
    }

    public function testClaimAndEnforcementRaceProducesExactlyOneWinnerAndNoPartialArtifacts(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        [$leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId] = $this->fixtures();
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/operational-lease-interruption-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $modes = ['claim', 'enforce'];
        $processes = $pipes = [];
        foreach ($modes as $index => $mode) {
            $processes[$index] = proc_open([PHP_BINARY, $worker, $mode, $this->root, $leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId, $gate], $descriptors, $pipes[$index]);
            self::assertIsResource($processes[$index]);
        }
        touch($gate);
        $exitCodes = [];
        foreach ($modes as $index => $mode) {
            stream_get_contents($pipes[$index][1]);
            stream_get_contents($pipes[$index][2]);
            fclose($pipes[$index][1]);
            fclose($pipes[$index][2]);
            $exitCodes[$mode] = proc_close($processes[$index]);
        }

        self::assertSame(1, count(array_filter($exitCodes, static fn (int $code): bool => 0 === $code)));
        $claims = glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: [];
        $results = glob($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-results/*.json') ?: [];
        self::assertSame(1, count($claims) + count($results));
        self::assertFalse([] !== $claims && [] !== $results);
        if ([] !== $claims) {
            $claim = json_decode((string) file_get_contents($claims[0]), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $claim['transactional_consumption']['schema']);
            self::assertSame([$cognitionAuthorityId, $leaseId], array_column($claim['transactional_consumption']['authority_set'], 'authority_id'));
        }
    }

    private function fixtures(): array
    {
        $instance = 'imperium-test';
        $requestId = 'operational-cognition-request-'.str_repeat('1', 20);
        $cognitionAuthorityId = 'operational-cognition-authority-'.str_repeat('2', 20);
        $target = ['seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'binding_id' => 'operational-seat-binding-'.str_repeat('3', 20), 'binding_digest' => str_repeat('4', 64), 'custody_id' => 'persona-custody-'.str_repeat('5', 20), 'custody_digest' => str_repeat('6', 64)];
        $request = $this->sealed(['schema' => 'imperium.curia-operational-cognition-request/v1', 'request_id' => $requestId, 'instance_id' => $instance, 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'target' => $target, 'input_digest' => str_repeat('8', 64), 'profile_model_requirements_digest' => str_repeat('9', 64), 'model_requirements' => ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], 'iteration' => 1, 'expires_at' => '2026-08-27T12:15:00+00:00', 'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION', 'cognition_authority' => true, 'cognition_authority_id' => $cognitionAuthorityId, 'cognition_authority_single_use' => true, 'cognition_authority_consumed' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json', $request);

        $decisionId = 'operational-provider-resource-decision-'.str_repeat('a', 20);
        $configuration = ['temperature' => 0.2];
        $ceiling = ['maximum_input_tokens' => 4096, 'maximum_output_tokens' => 1024, 'maximum_cost_microusd' => 250000];
        $decision = $this->sealed(['schema' => 'imperium.imperator-operational-provider-resource-decision/v1', 'decision_id' => $decisionId, 'instance_id' => $instance, 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'target' => $target, 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $configuration, 'resource_ceiling' => $ceiling, 'disposition' => 'AUTHORIZED', 'expires_at' => '2026-08-27T12:10:00+00:00', 'status' => 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/imperator/operational-provider-resource-decisions/'.$decisionId.'.json', $decision);

        $leaseId = 'operational-cognition-lease-'.str_repeat('b', 20);
        $lease = $this->sealed(['schema' => 'imperium.clavium-operational-cognition-lease/v1', 'lease_id' => $leaseId, 'instance_id' => $instance, 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'target' => $target, 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $configuration, 'resource_ceiling' => $ceiling, 'input_digest' => $request['input_digest'], 'profile_model_requirements_digest' => $request['profile_model_requirements_digest'], 'iteration' => 1, 'expires_at' => '2026-08-27T12:07:00+00:00', 'status' => 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'opaque' => true, 'lease_single_use' => true, 'lease_consumed' => false, 'sealed' => true]);
        $leasePath = $this->root.'/var/imperium/offices/clavium/operational-cognition-leases/'.$leaseId.'.json';
        $this->write($leasePath, $lease);

        $locksmithId = 'clavium-locksmith-binding-'.str_repeat('c', 20);
        $occupancy = $this->sealed(['schema' => 'imperium.clavium-locksmith-occupancy/v1', 'binding_id' => $locksmithId, 'instance_id' => $instance, 'seat' => 'clavium.locksmith', 'manifestation_id' => 'manifestation-locksmith', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'credential_disclosure_authority' => false, 'execution_authority' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/clavium/occupancy/'.$locksmithId.'.json', $occupancy);
        $enforcer = ['seat' => 'clavium.locksmith', 'binding_id' => $locksmithId, 'binding_digest' => $occupancy['record_digest'], 'manifestation_id' => 'manifestation-locksmith', 'occupancy_generation' => 1];

        $scope = ['kind' => 'UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE', 'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'case_id' => 'case-test', 'target' => $target, 'lease_consumed' => false];
        $lineage = ['operational_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'imperator_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'operational_cognition_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']]];
        $dispositionId = 'operational-lease-interruption-disposition-'.str_repeat('d', 20);
        $disposition = $this->sealed(['schema' => 'imperium.operational-cognition-lease-interruption-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => $instance, 'disposition' => 'INTERRUPT', 'lineage' => $lineage, 'affected_scope' => $scope, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-dispositions/'.$dispositionId.'.json', $disposition);

        $enforcementAuthorityId = 'operational-lease-interruption-enforcement-authority-'.str_repeat('e', 20);
        $authority = ['schema' => 'imperium.operational-cognition-lease-interruption-enforcement-authority/v1', 'authority_id' => $enforcementAuthorityId, 'instance_id' => $instance, 'source_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']], 'issuer' => ['seat' => 'curia.seneschal'], 'enforcer' => $enforcer, 'lineage' => $lineage, 'affected_scope' => $scope, 'permitted_transition' => 'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE', 'issued_at' => '2026-08-27T12:02:00+00:00', 'expires_at' => '2026-08-27T12:07:00+00:00', 'single_use' => true, 'exercisable' => true, 'consumed' => false, 'sealed' => true];
        foreach (['claim_creation_authority', 'cognition_authority', 'credential_authority', 'provider_journal_authority', 'network_access_authority', 'lease_mutation_authority', 'lease_closure_authority', 'propagation_authority', 'continuing_authority', 'external_action_authority', 'perimeter_authority'] as $flag) {
            $authority[$flag] = false;
        }
        $this->write($this->root.'/var/imperium/runtime/operational-cognition-lease-interruption-enforcement-authorities/'.$enforcementAuthorityId.'.json', $this->sealed($authority));

        return [$leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithId, $leasePath];
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
