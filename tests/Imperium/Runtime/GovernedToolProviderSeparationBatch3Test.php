<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Armory\CanonicalEmailSendToolDefinitionService;
use App\Imperium\Runtime\Imperator\ProviderBindingAuthorizationContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch3Test extends TestCase
{
    private string $root;
    private ImmutableRecordStore $records;
    private array $definition;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-binding-'.bin2hex(random_bytes(5));
        mkdir($this->root, 0770, true);
        $this->records = new ImmutableRecordStore($this->root, new AtomicTransition($this->root));
        $this->definition = (new CanonicalEmailSendToolDefinitionService($this->root))->define(new \DateTimeImmutable('2026-08-29T00:00:00+00:00'));
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testConsumesUpstreamSelectionAndProducesExactInactiveBinding(): void
    {
        $authority = $this->authorize();
        $binding = (new ProviderImplementationBindingService($this->root))->bind($authority['authority_id'], new \DateTimeImmutable('2026-08-29T00:01:00+00:00'));

        self::assertSame(ProviderImplementationBindingContract::REQUIRED_FIELDS, array_keys($binding));
        self::assertSame(['id' => $authority['authority_id'], 'digest' => $authority['record_digest'], 'schema' => ProviderBindingAuthorizationContract::SCHEMA], $binding['source_authority']);
        self::assertSame($authority['provider_implementation'], $binding['provider_implementation']);
        self::assertSame($authority['assurance_profile'], $binding['assurance_profile']);
        self::assertSame($authority['credential_family'], $binding['credential_family']);
        self::assertSame($authority['request_encoder'], $binding['request_encoder']);
        self::assertSame($authority['evidence_decoder'], $binding['evidence_decoder']);
        self::assertSame($authority['destination_policy'], $binding['destination_policy']);
        self::assertSame('BOUND_INACTIVE', $binding['status']);
        self::assertFalse($binding['scope']['provider_substitution_permitted']);

        $consumption = json_decode((string) file_get_contents($this->root.'/var/imperium/runtime/authority-consumptions/authority-consumption-'.hash('sha256', $authority['authority_id']).'.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($consumption['consumed']);
        self::assertFalse($consumption['continuing_authority']);
    }

    public function testExactReplayConverges(): void
    {
        $authority = $this->authorize();
        $service = new ProviderImplementationBindingService($this->root);
        $at = new \DateTimeImmutable('2026-08-29T00:01:00+00:00');

        self::assertSame($service->bind($authority['authority_id'], $at), $service->bind($authority['authority_id'], $at));
    }

    public function testChangedProviderFactIsRefusedAsAuthorityTamper(): void
    {
        $authority = $this->authorize();
        $path = $this->root.'/'.ProviderImplementationBindingService::AUTHORITIES.'/'.$authority['authority_id'].'.json';
        $authority['provider_implementation']['adapter_version'] = '2';
        file_put_contents($path, json_encode($authority, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GTP302_PROVIDER_BINDING_AUTHORITY_INVALID');
        (new ProviderImplementationBindingService($this->root))->bind($authority['authority_id'], new \DateTimeImmutable('2026-08-29T00:01:00+00:00'));
    }

    public function testProviderCredentialMismatchAndExpiredAuthorityAreRefused(): void
    {
        $authority = $this->authorityRecord();
        $authority['credential_family']['provider_id'] = 'different-provider';
        $authority = $this->records->put(ProviderImplementationBindingService::AUTHORITIES, $authority['authority_id'], $authority);

        try {
            (new ProviderImplementationBindingService($this->root))->bind($authority['authority_id'], new \DateTimeImmutable('2026-08-29T00:01:00+00:00'));
            self::fail('Provider/credential mismatch was accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('GTP302_PROVIDER_BINDING_AUTHORITY_INVALID', $exception->getMessage());
        }

        $expired = $this->authorityRecord('provider-binding-authority-bbbbbbbbbbbbbbbbbbbb');
        $expired['expires_at'] = '2026-08-29T00:00:30+00:00';
        $this->records->put(ProviderImplementationBindingService::AUTHORITIES, $expired['authority_id'], $expired);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GTP302_PROVIDER_BINDING_AUTHORITY_INVALID');
        (new ProviderImplementationBindingService($this->root))->bind($expired['authority_id'], new \DateTimeImmutable('2026-08-29T00:01:00+00:00'));
    }

    public function testBindingContainsNoSecretAndOpensNoExecutionBoundary(): void
    {
        $binding = (new ProviderImplementationBindingService($this->root))->bind($this->authorize()['authority_id'], new \DateTimeImmutable('2026-08-29T00:01:00+00:00'));
        $encoded = json_encode($binding, JSON_THROW_ON_ERROR);

        self::assertFalse($binding['credential_family']['secret_persistence_permitted']);
        self::assertStringNotContainsString('api_key', $encoded);
        self::assertStringNotContainsString('token', $encoded);
        foreach (ProviderImplementationBindingContract::CONTRACT_BOUNDARY as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testBatchThreeDocumentationKeepsImplementationAndExecutionClosed(): void
    {
        $route = (string) file_get_contents(dirname(__DIR__, 3).'/docs/governed-provider-binding-route.md');
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/governed-tool-provider-separation-batch-3-complete.md');

        foreach (['`BATCH_3_PROVIDER_BINDING_ROUTE_COMPLETE_INACTIVE`', 'pre-existing authority', '`BOUND_INACTIVE`', 'Exact replay converges', 'no credential material'] as $proof) {
            self::assertStringContainsString($proof, $route);
        }
        foreach (['Only Batch 4 may next be considered', 'No provider adapter was implemented', 'Runtime behavior is unchanged', 'Batch 4 is not authorized'] as $proof) {
            self::assertStringContainsString($proof, $handoff);
        }
    }

    private function authorize(): array
    {
        $authority = $this->authorityRecord();

        return $this->records->put(ProviderImplementationBindingService::AUTHORITIES, $authority['authority_id'], $authority);
    }

    private function authorityRecord(string $authorityId = 'provider-binding-authority-aaaaaaaaaaaaaaaaaaaa'): array
    {
        return [
            'schema' => ProviderBindingAuthorizationContract::SCHEMA,
            'authority_id' => $authorityId,
            'instance_id' => 'imperium-test-instance',
            'source' => ['office' => 'imperator', 'seat' => 'imperator', 'id' => 'provider-selection-decision-1', 'digest' => str_repeat('1', 64)],
            'tool_operation' => ['id' => 'email.send.v1', 'digest' => $this->definition['record_digest'], 'schema' => $this->definition['schema']],
            'provider_implementation' => ['provider_id' => 'fixture-mail-provider', 'adapter_id' => 'fixture-mail-adapter', 'adapter_version' => '1'],
            'assurance_profile' => ['id' => 'fixture-assurance.v1', 'digest' => str_repeat('2', 64), 'schema' => 'imperium.provider-assurance-profile/v1'],
            'credential_family' => ['family_id' => 'fixture-mail-credentials.v1', 'provider_id' => 'fixture-mail-provider', 'secret_persistence_permitted' => false],
            'request_encoder' => ['id' => 'fixture-email-encoder.v1', 'digest' => str_repeat('3', 64), 'schema' => 'imperium.provider-request-encoder/v1'],
            'evidence_decoder' => ['id' => 'fixture-email-decoder.v1', 'digest' => str_repeat('4', 64), 'schema' => 'imperium.provider-evidence-decoder/v1'],
            'destination_policy' => ['policy_id' => 'exact-email-destination.v1', 'policy_digest' => str_repeat('5', 64), 'exact_destination_required' => true],
            'scope' => ['operation' => 'email.send', 'authorization_target_id' => 'email-effect-1', 'authorization_target_digest' => str_repeat('6', 64), 'provider_substitution_permitted' => false],
            'issued_at' => '2026-08-29T00:00:00+00:00',
            'expires_at' => '2026-08-29T00:10:00+00:00',
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ];
    }
}
