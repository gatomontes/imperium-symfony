<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Governance\InternalOperationalLeaseInterruptionDispositionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InternalOperationalLeaseInterruptionDispositionServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-lease-interruption-disposition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSourceAuthorizerSealsExactDispositionWithoutAuthorityOrMutation(): void
    {
        [$leaseId, $seneschalId, $leasePath] = $this->fixtures();
        $service = new InternalOperationalLeaseInterruptionDispositionService($this->root);
        $at = new \DateTimeImmutable('2026-08-27T12:03:00+00:00');
        $result = $service->interrupt($leaseId, $seneschalId, 'Stop before durable operational claim creation.', $at);

        self::assertSame('imperium.operational-cognition-lease-interruption-disposition/v1', $result['schema']);
        self::assertSame('INTERRUPT', $result['disposition']);
        self::assertSame('SOURCE_AUTHORIZER_CURRENT_INTERNAL_OPERATIONAL_ITERATION', $result['authority_basis']['jurisdiction']);
        self::assertSame('UNCLAIMED_INTERNAL_OPERATIONAL_COGNITION_LEASE', $result['affected_scope']['kind']);
        self::assertCount(4, $result['lineage']);
        foreach (['enforcement_authority_opened', 'claim_created', 'cognition_authority_consumed', 'lease_consumed', 'lease_mutated', 'lease_closed', 'credential_resolved', 'provider_journal_created', 'network_access_performed', 'propagation_performed', 'authority_granted', 'continuation_authority'] as $flag) {
            self::assertFalse($result[$flag]);
        }
        self::assertSame($result, $service->interrupt($leaseId, $seneschalId, 'Stop before durable operational claim creation.', $at->modify('+1 minute')));
        self::assertSame([], glob($this->root.'/var/imperium/runtime/continuous-governance-enforcement-authorities/*.json') ?: []);
        $lease = json_decode((string) file_get_contents($leasePath), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($lease['lease_consumed']);
    }

    #[DataProvider('invalidCases')]
    public function testExpiredClaimedSubstitutedAndDuplicateActorCasesFailStopped(string $case, string $error): void
    {
        [$leaseId, $seneschalId, $leasePath, $requestPath, , $occupancy] = $this->fixtures();
        if ('expired' === $case) {
            $at = new \DateTimeImmutable('2026-08-27T12:07:00+00:00');
        } else {
            $at = new \DateTimeImmutable('2026-08-27T12:03:00+00:00');
            if ('claimed' === $case) {
                $this->write($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/claim.json', $this->sealed(['lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true]]));
            } elseif ('substituted' === $case) {
                $request = $this->unsealed($requestPath);
                $request['authorizer']['manifestation_id'] = 'manifestation-substituted';
                $this->write($requestPath, $this->sealed($request));
            } else {
                $other = $occupancy;
                unset($other['record_digest']);
                $other['binding_id'] = 'curia-seneschal-binding-'.str_repeat('9', 20);
                $other['manifestation_id'] = 'manifestation-other-seneschal';
                $this->write($this->root.'/var/imperium/offices/curia/occupancy/'.$other['binding_id'].'.json', $this->sealed($other));
            }
        }

        $this->expectExceptionMessage($error);
        (new InternalOperationalLeaseInterruptionDispositionService($this->root))->interrupt($leaseId, $seneschalId, 'Stop.', $at);
    }

    public static function invalidCases(): array
    {
        return [
            ['expired', 'OCI102_OPERATIONAL_LEASE_LINEAGE_INVALID'],
            ['claimed', 'OCI105_OPERATIONAL_LEASE_NOT_INTERRUPTIBLE_UNCLAIMED'],
            ['substituted', 'OCI102_OPERATIONAL_LEASE_LINEAGE_INVALID'],
            ['duplicate', 'OCI103_SOURCE_AUTHORIZER_NOT_COMPETENT_CURRENT_OCCUPANT'],
        ];
    }

    private function fixtures(): array
    {
        $seneschalId = 'curia-seneschal-binding-'.str_repeat('1', 20);
        $authorizer = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 4];
        $occupancy = $this->sealed(['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $seneschalId, 'instance_id' => 'imperium-test', 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 4, 'status' => 'ACTIVE', 'bounded_execution_authorization_authority' => true, 'execution_authority' => false, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/occupancy/'.$seneschalId.'.json', $occupancy);

        $binding = ['id' => 'operational-seat-binding-'.str_repeat('2', 20), 'digest' => str_repeat('3', 64)];
        $custody = ['id' => 'persona-custody-'.str_repeat('4', 20), 'digest' => str_repeat('5', 64)];
        $target = ['seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'binding_id' => $binding['id'], 'binding_digest' => $binding['digest'], 'custody_id' => $custody['id'], 'custody_digest' => $custody['digest']];
        $authorizationId = 'bounded-execution-authorization-'.str_repeat('6', 20);
        $authorization = $this->sealed(['schema' => 'imperium.curia-bounded-execution-authorization/v1', 'authorization_id' => $authorizationId, 'instance_id' => 'imperium-test', 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'authorizer' => $authorizer, 'source_binding' => $binding, 'seat' => $target['seat'], 'manifestation_id' => $target['manifestation_id'], 'operational_custody' => $custody, 'input_digest' => str_repeat('8', 64), 'status' => 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION', 'bounded_execution_authority' => true, 'bounded_execution_authority_exercisable' => true, 'maximum_iterations' => 1, 'sealed' => true]);
        $this->write($this->root.'/var/imperium/offices/curia/bounded-execution-authorizations/'.$authorizationId.'.json', $authorization);

        $requestId = 'operational-cognition-request-'.str_repeat('9', 20);
        $request = $this->sealed(['schema' => 'imperium.curia-operational-cognition-request/v1', 'request_id' => $requestId, 'instance_id' => 'imperium-test', 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'source_bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']], 'authorizer' => $authorizer, 'target' => $target, 'input_digest' => $authorization['input_digest'], 'profile_model_requirements_digest' => str_repeat('a', 64), 'iteration' => 1, 'requested_at' => '2026-08-27T12:00:00+00:00', 'expires_at' => '2026-08-27T12:15:00+00:00', 'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION', 'sealed' => true]);
        $requestPath = $this->root.'/var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json';
        $this->write($requestPath, $request);

        $decisionId = 'operational-provider-resource-decision-'.str_repeat('b', 20);
        $configuration = ['temperature' => 0.2];
        $ceiling = ['maximum_input_tokens' => 4096, 'maximum_output_tokens' => 1024, 'maximum_cost_microusd' => 250000];
        $decision = $this->sealed(['schema' => 'imperium.imperator-operational-provider-resource-decision/v1', 'decision_id' => $decisionId, 'instance_id' => 'imperium-test', 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'target' => $target, 'input_digest' => $request['input_digest'], 'iteration' => 1, 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $configuration, 'resource_ceiling' => $ceiling, 'disposition' => 'AUTHORIZED', 'decided_at' => '2026-08-27T12:01:00+00:00', 'expires_at' => '2026-08-27T12:10:00+00:00', 'status' => 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE', 'sealed' => true]);
        $this->write($this->root.'/var/imperium/imperator/operational-provider-resource-decisions/'.$decisionId.'.json', $decision);

        $leaseId = 'operational-cognition-lease-'.str_repeat('c', 20);
        $lease = $this->sealed(['schema' => 'imperium.clavium-operational-cognition-lease/v1', 'lease_id' => $leaseId, 'instance_id' => 'imperium-test', 'case_id' => 'case-test', 'case_digest' => str_repeat('7', 64), 'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'target' => $target, 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $configuration, 'resource_ceiling' => $ceiling, 'input_digest' => $request['input_digest'], 'iteration' => 1, 'issued_at' => '2026-08-27T12:02:00+00:00', 'expires_at' => '2026-08-27T12:07:00+00:00', 'status' => 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'opaque' => true, 'lease_single_use' => true, 'lease_consumed' => false, 'sealed' => true]);
        $leasePath = $this->root.'/var/imperium/offices/clavium/operational-cognition-leases/'.$leaseId.'.json';
        $this->write($leasePath, $lease);

        return [$leaseId, $seneschalId, $leasePath, $requestPath, $authorization, $occupancy];
    }

    private function unsealed(string $path): array
    {
        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        unset($record['record_digest']);

        return $record;
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
