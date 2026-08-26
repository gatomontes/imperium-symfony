<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OperationalCognitionRequestService;
use App\Imperium\Runtime\Imperator\OperationalProviderResourceDecisionService;
use PHPUnit\Framework\TestCase;

final class OperationalCognitionAccessRequestDecisionTest extends TestCase
{
    public function testCuriaRequestIsExactReplaySafeAndGrantsNoProviderAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-cognition-request-'.bin2hex(random_bytes(5));
        try {
            $authorizationId = $this->authorization($root);
            $service = new OperationalCognitionRequestService($root);
            $requestedAt = new \DateTimeImmutable('2026-08-26T16:00:00+00:00');
            $expiresAt = $requestedAt->modify('+15 minutes');
            $requirements = $this->requirements();
            $request = $service->request($authorizationId, $requirements, 1, ['one attributable output is sealed', 'the iteration limit is reached'], $expiresAt, $requestedAt);

            self::assertSame('OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION', $request['status']);
            self::assertSame($authorizationId, $request['source_bounded_execution_authorization']['id']);
            self::assertSame(1, $request['iteration']);
            self::assertTrue($request['cognition_authority']);
            self::assertMatchesRegularExpression('/^operational-cognition-authority-[a-f0-9]{20}$/', $request['cognition_authority_id']);
            self::assertTrue($request['cognition_authority_single_use']);
            self::assertFalse($request['cognition_authority_consumed']);
            foreach (['credential_use_authority', 'network_access_authority', 'provider_invocation_authority', 'execution_continuation_authority'] as $field) {
                self::assertFalse($request[$field]);
            }
            self::assertSame($request, $service->request($authorizationId, $requirements, 1, ['one attributable output is sealed', 'the iteration limit is reached'], $expiresAt, $requestedAt));

            $this->expectExceptionMessage('OCA104_OPERATIONAL_COGNITION_REQUEST_CONFLICT');
            $service->request($authorizationId, $requirements, 1, ['a different stop condition is reached'], $expiresAt, $requestedAt);
        } finally {
            $this->remove($root);
        }
    }

    public function testImperatorAuthorizationIsIndependentExactAndOpensOnlyLeaseActivation(): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-provider-decision-'.bin2hex(random_bytes(5));
        try {
            $request = $this->request($root);
            $service = new OperationalProviderResourceDecisionService($root);
            $decidedAt = new \DateTimeImmutable('2026-08-26T16:01:00+00:00');
            $expiresAt = $decidedAt->modify('+9 minutes');
            $ceiling = $this->ceiling();
            $decision = $service->decide($request['request_id'], 'AUTHORIZED', 'deepseek', 'deepseek-v4-flash', ['temperature' => 0.2], $ceiling, 'Authorize only this exact bounded provider expenditure.', $expiresAt, $decidedAt);

            self::assertSame('OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE', $decision['status']);
            self::assertSame($request['record_digest'], $decision['source_cognition_request']['digest']);
            self::assertSame(['temperature' => 0.2], $decision['model_configuration']);
            self::assertSame($ceiling, $decision['resource_ceiling']);
            self::assertTrue($decision['clavium_lease_activation_authority']['authority_exercisable']);
            self::assertFalse($decision['clavium_lease_activation_authority']['consumed']);
            foreach (['credential_use_authority', 'network_access_authority', 'provider_invocation_authority', 'execution_continuation_authority'] as $field) {
                self::assertFalse($decision[$field]);
            }
            self::assertSame($decision, $service->decide($request['request_id'], 'AUTHORIZED', 'deepseek', 'deepseek-v4-flash', ['temperature' => 0.2], $ceiling, 'Authorize only this exact bounded provider expenditure.', $expiresAt, $decidedAt));

            $this->expectExceptionMessage('OCA206_PROVIDER_RESOURCE_DECISION_CONFLICT');
            $service->decide($request['request_id'], 'REFUSED', 'deepseek', 'deepseek-v4-flash', ['temperature' => 0.2], $ceiling, 'Refuse.', $expiresAt, $decidedAt);
        } finally {
            $this->remove($root);
        }
    }

    public function testImperatorRefusalIsSealedAndOpensNoAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-provider-refusal-'.bin2hex(random_bytes(5));
        try {
            $request = $this->request($root);
            $at = new \DateTimeImmutable('2026-08-26T16:01:00+00:00');
            $decision = (new OperationalProviderResourceDecisionService($root))->decide($request['request_id'], 'REFUSED', 'deepseek', 'deepseek-v4-flash', [], $this->ceiling(), 'The expenditure is refused.', $at->modify('+5 minutes'), $at);

            self::assertSame('OPERATIONAL_PROVIDER_RESOURCE_REFUSED_NO_AUTHORITY', $decision['status']);
            self::assertNull($decision['clavium_lease_activation_authority']);
            self::assertFalse($decision['provider_invocation_authority']);
            self::assertTrue($decision['sealed']);
        } finally {
            $this->remove($root);
        }
    }

    public function testExpiredRequestAndMismatchedModelFailStopped(): void
    {
        $root = sys_get_temp_dir().'/imperium-operational-provider-invalid-'.bin2hex(random_bytes(5));
        try {
            $request = $this->request($root, new \DateTimeImmutable('2026-08-26T16:00:00+00:00'), new \DateTimeImmutable('2026-08-26T16:01:00+00:00'));
            $service = new OperationalProviderResourceDecisionService($root);
            $this->expectExceptionMessage('OCA207_OPERATIONAL_COGNITION_REQUEST_INVALID');
            $service->decide($request['request_id'], 'AUTHORIZED', 'deepseek', 'deepseek-v4-flash', [], $this->ceiling(), 'Too late.', new \DateTimeImmutable('2026-08-26T16:03:00+00:00'), new \DateTimeImmutable('2026-08-26T16:02:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function request(string $root, ?\DateTimeImmutable $requestedAt = null, ?\DateTimeImmutable $expiresAt = null): array
    {
        $authorizationId = $this->authorization($root);
        $requestedAt ??= new \DateTimeImmutable('2026-08-26T16:00:00+00:00');
        $expiresAt ??= $requestedAt->modify('+15 minutes');

        return (new OperationalCognitionRequestService($root))->request($authorizationId, $this->requirements(), 1, ['one attributable output is sealed'], $expiresAt, $requestedAt);
    }

    private function authorization(string $root): string
    {
        $authorizationId = 'bounded-execution-authorization-'.str_repeat('a', 20);
        $record = $this->record([
            'schema' => 'imperium.curia-bounded-execution-authorization/v1',
            'authorization_id' => $authorizationId,
            'instance_id' => 'imperium-test',
            'case_id' => 'operational-case',
            'case_digest' => str_repeat('b', 64),
            'authorizer' => ['seat' => 'curia.seneschal', 'binding_id' => 'curia-seneschal-binding-'.str_repeat('c', 20), 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'source_custody_transition' => ['id' => 'operational-custody-transition-'.str_repeat('d', 20), 'digest' => str_repeat('e', 64)],
            'source_binding' => ['id' => 'operational-seat-binding-'.str_repeat('f', 20), 'digest' => str_repeat('1', 64)],
            'seat' => 'foundry.artificer',
            'manifestation_id' => 'manifestation-artificer',
            'profile_candidate' => ['id' => 'profile-artificer', 'digest' => str_repeat('2', 64)],
            'operational_custody' => ['id' => 'persona-custody-'.str_repeat('3', 20), 'digest' => str_repeat('4', 64)],
            'input' => ['target_url' => 'https://example.test/public-app'],
            'input_digest' => hash('sha256', CanonicalJson::encode(['target_url' => 'https://example.test/public-app'])),
            'status' => 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION',
            'bounded_execution_authority' => true,
            'bounded_execution_authority_exercisable' => true,
            'maximum_iterations' => 1,
            'credentials_authority' => false,
            'network_access_authority' => false,
            'external_action_authority' => false,
            'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/offices/curia/bounded-execution-authorizations/'.$authorizationId.'.json', $record);

        return $authorizationId;
    }

    private function requirements(): array
    {
        return ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'capabilities' => ['structured-output', 'text-generation']];
    }

    private function ceiling(): array
    {
        return ['maximum_input_tokens' => 4096, 'maximum_output_tokens' => 1024, 'maximum_cost_microusd' => 250000];
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
