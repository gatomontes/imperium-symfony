<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\AgentMailCredentialFamilyPolicy;
use App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityContract;
use App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityService;
use App\Imperium\Runtime\LaCortine\AgentMailProviderProfile;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch5Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-credential-eligibility-'.bin2hex(random_bytes(5));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testExactProviderFamilyCapabilityAndTargetBecomeEligibleWithoutResolution(): void
    {
        $record = $this->service()->assess($this->binding(), $this->capability(), new \DateTimeImmutable('2026-08-29T02:00:00+00:00'));

        self::assertSame(ProviderBoundCredentialEligibilityContract::REQUIRED_FIELDS, array_keys($record));
        self::assertSame('ELIGIBLE_INACTIVE', $record['status']);
        self::assertSame('agentmail', $record['provider']);
        self::assertSame(AgentMailProviderProfile::CREDENTIAL_FAMILY_ID, $record['credential_family']);
        self::assertFalse($record['credential_resolved']);
        self::assertFalse($record['external_io_permitted']);
        self::assertStringNotContainsString('env:AGENTMAIL_API_KEY', json_encode($record, JSON_THROW_ON_ERROR));
    }

    public function testExactReplayConverges(): void
    {
        $service = $this->service();
        $at = new \DateTimeImmutable('2026-08-29T02:00:00+00:00');
        self::assertSame($service->assess($this->binding(), $this->capability(), $at), $service->assess($this->binding(), $this->capability(), $at));
    }

    #[DataProvider('substitutions')]
    public function testMismatchFailsBeforeAnyCredentialResolution(callable $change): void
    {
        [$binding, $capability] = $change($this->binding(), $this->capability());
        $this->expectExceptionMessage('GTP500_PROVIDER_BOUND_CREDENTIAL_INELIGIBLE');
        $this->service()->assess($binding, $capability, new \DateTimeImmutable('2026-08-29T02:00:00+00:00'));
    }

    public static function substitutions(): iterable
    {
        yield 'provider' => [static function (array $binding, CredentialCapability $capability): array { $binding['provider_implementation']['provider_id'] = 'substitute'; return [self::seal($binding), $capability]; }];
        yield 'family' => [static function (array $binding, CredentialCapability $capability): array { $binding['credential_family']['family_id'] = 'substitute-family'; return [self::seal($binding), $capability]; }];
        yield 'reference' => [static fn (array $binding, CredentialCapability $capability): array => [$binding, new CredentialCapability($capability->capabilityId, 'env:OTHER_KEY', $capability->commissionId, $capability->operation, $capability->expiresAt)]];
        yield 'target' => [static fn (array $binding, CredentialCapability $capability): array => [$binding, new CredentialCapability($capability->capabilityId, AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX, 'other-target', $capability->operation, $capability->expiresAt)]];
        yield 'operation' => [static fn (array $binding, CredentialCapability $capability): array => [$binding, new CredentialCapability($capability->capabilityId, AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX, $capability->commissionId, 'other.operation', $capability->expiresAt)]];
    }

    public function testExpiredCapabilityAndTamperedBindingFailClosed(): void
    {
        $binding = $this->binding();
        $binding['credential_family']['family_id'] = 'tampered';
        try {
            $this->service()->assess($binding, $this->capability(), new \DateTimeImmutable('2026-08-29T02:00:00+00:00'));
            self::fail('Tampered binding accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('GTP500_PROVIDER_BOUND_CREDENTIAL_INELIGIBLE', $exception->getMessage());
        }

        $this->expectExceptionMessage('GTP500_PROVIDER_BOUND_CREDENTIAL_INELIGIBLE');
        $expired = new CredentialCapability('credential-capability.test', AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX, 'email-effect-1', 'email.send', new \DateTimeImmutable('2026-08-29T01:59:00+00:00'));
        $this->service()->assess($this->binding(), $expired, new \DateTimeImmutable('2026-08-29T02:00:00+00:00'));
    }

    public function testBoundaryAndHandoffKeepResolutionAndIoClosed(): void
    {
        foreach (ProviderBoundCredentialEligibilityContract::BOUNDARY as $permission) self::assertFalse($permission);
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Clavium/ProviderBoundCredentialEligibilityService.php');
        self::assertStringNotContainsString('CredentialBroker', $source);
        self::assertStringNotContainsString('->consume(', $source);

        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/governed-tool-provider-separation-batch-5-complete.md');
        foreach (['Only Batch 6 may next be considered', 'Runtime behavior is unchanged', 'No credential was resolved', 'Batch 6 is not authorized'] as $proof) self::assertStringContainsString($proof, $handoff);
    }

    private function service(): ProviderBoundCredentialEligibilityService
    {
        return new ProviderBoundCredentialEligibilityService($this->root, new AgentMailCredentialFamilyPolicy());
    }

    private function capability(): CredentialCapability
    {
        return new CredentialCapability('credential-capability.test', AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX, 'email-effect-1', 'email.send', new \DateTimeImmutable('2026-08-29T02:05:00+00:00'));
    }

    private function binding(): array
    {
        return self::seal(['schema' => 'imperium.la-cortine.provider-implementation-binding/v1', 'binding_id' => 'provider-implementation-binding-aaaaaaaaaaaaaaaaaaaa', 'instance_id' => 'imperium-test', 'source_authority' => ['id' => 'authority', 'digest' => str_repeat('1', 64), 'schema' => 'authority/v1'], 'tool_operation' => ['id' => 'email.send.v1', 'digest' => str_repeat('2', 64), 'schema' => 'tool/v1'], 'provider_implementation' => ['provider_id' => 'agentmail', 'adapter_id' => 'agentmail.email-send', 'adapter_version' => '1'], 'assurance_profile' => ['id' => 'assurance', 'digest' => str_repeat('3', 64), 'schema' => 'assurance/v1'], 'credential_family' => ['family_id' => AgentMailProviderProfile::CREDENTIAL_FAMILY_ID, 'provider_id' => 'agentmail', 'secret_persistence_permitted' => false], 'request_encoder' => ['id' => 'encoder', 'digest' => str_repeat('4', 64), 'schema' => 'encoder/v1'], 'evidence_decoder' => ['id' => 'decoder', 'digest' => str_repeat('5', 64), 'schema' => 'decoder/v1'], 'destination_policy' => ['policy_id' => 'destination', 'policy_digest' => str_repeat('6', 64), 'exact_destination_required' => true], 'scope' => ['operation' => 'email.send', 'authorization_target_id' => 'email-effect-1', 'authorization_target_digest' => str_repeat('7', 64), 'provider_substitution_permitted' => false], 'validity' => ['effective_at' => '2026-08-29T01:00:00+00:00', 'expires_at' => '2026-08-29T02:10:00+00:00'], 'status' => 'BOUND_INACTIVE', 'bound_at' => '2026-08-29T01:30:00+00:00', 'sealed' => true]);
    }

    private static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
