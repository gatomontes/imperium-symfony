<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\OperationalCognitionLeaseService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationalCognitionLeaseServiceTest extends TestCase
{
    public function testAuthorizedDecisionIssuesOneOpaqueExactReplaySafeLease(): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-lease-'.bin2hex(random_bytes(5));
        try {
            [$decisionId, $authorityId, $locksmithId] = $this->fixtures($root);
            $issuedAt = new \DateTimeImmutable('2026-08-26T16:02:00+00:00');
            $expiresAt = $issuedAt->modify('+4 minutes');
            $service = new OperationalCognitionLeaseService($root);
            $lease = $service->issue($decisionId, $authorityId, $locksmithId, $expiresAt, $issuedAt);

            self::assertSame('OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', $lease['status']);
            self::assertTrue($lease['opaque']);
            self::assertTrue($lease['lease_single_use']);
            self::assertFalse($lease['lease_consumed']);
            self::assertTrue($lease['activation_authority']['consumed']);
            self::assertSame('deepseek', $lease['provider']);
            self::assertSame('deepseek-v4-flash', $lease['model']);
            self::assertSame(1, $lease['iteration']);
            self::assertSame('OPERATIONAL_COGNITION', $lease['continuous_governance_controls']['lease_family']);
            self::assertSame('DURABLE_INVOCATION_CLAIM', $lease['continuous_governance_controls']['freshness']['revalidation_checkpoint']);
            self::assertSame('UNASSIGNED_DEFERRED_BOUNDARY', $lease['continuous_governance_controls']['revocation']['status']);
            self::assertFalse($lease['continuous_governance_controls']['revocation']['propagation_implemented']);
            foreach (['credential_reference_disclosed', 'credential_material_present', 'credential_possession_transferred', 'credential_use_authority', 'network_access_authority', 'provider_invocation_authority', 'execution_continuation_authority'] as $field) {
                self::assertFalse($lease[$field]);
            }
            $serialized = CanonicalJson::encode($lease);
            foreach (['"credential_ref":', 'DEEPSEEK_API_KEY', 'Bearer ', 'https://api.'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $serialized);
            }
            self::assertSame($lease, $service->issue($decisionId, $authorityId, $locksmithId, $expiresAt, $issuedAt));

            $this->expectExceptionMessage('OCA307_OPERATIONAL_LEASE_CONFLICT');
            $service->issue($decisionId, $authorityId, $locksmithId, $expiresAt->modify('-1 minute'), $issuedAt);
        } finally {
            $this->remove($root);
        }
    }

    #[DataProvider('invalidDecisionCases')]
    public function testRefusedExpiredAndMismatchedDecisionFailStopped(string $case): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-lease-invalid-'.bin2hex(random_bytes(5));
        try {
            [$decisionId, $authorityId, $locksmithId, $decisionPath] = $this->fixtures($root);
            $decision = json_decode((string) file_get_contents($decisionPath), true, 512, JSON_THROW_ON_ERROR);
            unset($decision['record_digest']);
            if ('refused' === $case) {
                $decision['disposition'] = 'REFUSED';
                $decision['status'] = 'OPERATIONAL_PROVIDER_RESOURCE_REFUSED_NO_AUTHORITY';
                $decision['clavium_lease_activation_authority'] = null;
            } elseif ('expired' === $case) {
                $decision['expires_at'] = '2026-08-26T16:01:59+00:00';
                $decision['clavium_lease_activation_authority']['expires_at'] = $decision['expires_at'];
            } else {
                $decision['target']['manifestation_id'] = 'manifestation-substituted';
            }
            $this->write($decisionPath, $this->record($decision));

            $this->expectExceptionMessage('OCA304_OPERATIONAL_LEASE_CHAIN_INVALID');
            (new OperationalCognitionLeaseService($root))->issue($decisionId, $authorityId, $locksmithId, new \DateTimeImmutable('2026-08-26T16:06:00+00:00'), new \DateTimeImmutable('2026-08-26T16:02:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public static function invalidDecisionCases(): array
    {
        return [['refused'], ['expired'], ['mismatched']];
    }

    public function testUnauthorizedOrCredentialCapableLocksmithFailsStopped(): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-lease-locksmith-'.bin2hex(random_bytes(5));
        try {
            [$decisionId, $authorityId, $locksmithId, , $locksmithPath] = $this->fixtures($root);
            $locksmith = json_decode((string) file_get_contents($locksmithPath), true, 512, JSON_THROW_ON_ERROR);
            unset($locksmith['record_digest']);
            $locksmith['operational_cognition_lease_issuance_authority'] = false;
            $locksmith['credential_disclosure_authority'] = true;
            $this->write($locksmithPath, $this->record($locksmith));

            $this->expectExceptionMessage('OCA304_OPERATIONAL_LEASE_CHAIN_INVALID');
            (new OperationalCognitionLeaseService($root))->issue($decisionId, $authorityId, $locksmithId, new \DateTimeImmutable('2026-08-26T16:06:00+00:00'), new \DateTimeImmutable('2026-08-26T16:02:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root): array
    {
        $requestId = 'operational-cognition-request-'.str_repeat('a', 20);
        $target = ['seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'binding_id' => 'operational-seat-binding-'.str_repeat('b', 20), 'binding_digest' => str_repeat('c', 64), 'custody_id' => 'persona-custody-'.str_repeat('d', 20), 'custody_digest' => str_repeat('e', 64)];
        $requirements = ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'capabilities' => ['structured-output', 'text-generation']];
        $request = $this->record([
            'schema' => 'imperium.curia-operational-cognition-request/v1',
            'request_id' => $requestId,
            'instance_id' => 'imperium-test',
            'case_id' => 'operational-case',
            'case_digest' => str_repeat('f', 64),
            'target' => $target,
            'input_digest' => str_repeat('1', 64),
            'profile_model_requirements_digest' => str_repeat('2', 64),
            'model_requirements' => $requirements,
            'iteration' => 1,
            'expires_at' => '2026-08-26T16:15:00+00:00',
            'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION',
            'cognition_authority' => true,
            'cognition_authority_single_use' => true,
            'cognition_authority_consumed' => false,
            'credential_use_authority' => false,
            'network_access_authority' => false,
            'provider_invocation_authority' => false,
            'sealed' => true,
        ]);
        $decisionId = 'operational-provider-resource-decision-'.str_repeat('3', 20);
        $authorityId = 'operational-clavium-lease-activation-authority-'.str_repeat('4', 20);
        $decision = $this->record([
            'schema' => 'imperium.imperator-operational-provider-resource-decision/v1',
            'decision_id' => $decisionId,
            'instance_id' => 'imperium-test',
            'case_id' => 'operational-case',
            'case_digest' => str_repeat('f', 64),
            'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'target' => $target,
            'input_digest' => $request['input_digest'],
            'profile_model_requirements_digest' => $request['profile_model_requirements_digest'],
            'iteration' => 1,
            'provider' => 'deepseek',
            'model' => 'deepseek-v4-flash',
            'model_configuration' => ['temperature' => 0.2],
            'resource_ceiling' => ['maximum_input_tokens' => 4096, 'maximum_output_tokens' => 1024, 'maximum_cost_microusd' => 250000],
            'disposition' => 'AUTHORIZED',
            'expires_at' => '2026-08-26T16:10:00+00:00',
            'status' => 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE',
            'clavium_lease_activation_authority' => ['authority_id' => $authorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'expires_at' => '2026-08-26T16:10:00+00:00', 'consumed' => false],
            'credential_use_authority' => false,
            'network_access_authority' => false,
            'provider_invocation_authority' => false,
            'sealed' => true,
        ]);
        $locksmithId = 'clavium-locksmith-binding-'.str_repeat('5', 20);
        $locksmith = $this->record([
            'schema' => 'imperium.clavium-locksmith-occupancy/v1',
            'binding_id' => $locksmithId,
            'instance_id' => 'imperium-test',
            'seat' => 'clavium.locksmith',
            'manifestation_id' => 'manifestation-locksmith',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'operational_cognition_lease_issuance_authority' => true,
            'credential_disclosure_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $requestPath = $root.'/var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json';
        $decisionPath = $root.'/var/imperium/imperator/operational-provider-resource-decisions/'.$decisionId.'.json';
        $locksmithPath = $root.'/var/imperium/offices/clavium/occupancy/'.$locksmithId.'.json';
        $this->write($requestPath, $request);
        $this->write($decisionPath, $decision);
        $this->write($locksmithPath, $locksmith);

        return [$decisionId, $authorityId, $locksmithId, $decisionPath, $locksmithPath];
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
