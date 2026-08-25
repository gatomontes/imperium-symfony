<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use PHPUnit\Framework\TestCase;

final class ProviderInvocationJournalServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-journal-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testExternalIoStartIsDurableAndResponseIdentityIsSealed(): void
    {
        $claim = $this->claim();
        $journal = new ProviderInvocationJournalService($this->root);
        $started = $journal->start($claim, new \DateTimeImmutable('2026-08-25T13:00:00+00:00'));

        self::assertSame('INVOCATION_IN_FLIGHT', $started['status']);
        self::assertTrue($started['external_io_started']);
        self::assertFalse($started['automatic_replay_permitted']);
        self::assertNull($started['provider_response_identity']);

        $sealed = $journal->sealResponse($claim, 'provider response', new \DateTimeImmutable('2026-08-25T13:00:01+00:00'));
        self::assertSame('PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING', $sealed['status']);
        self::assertSame('sha256:'.hash('sha256', 'provider response'), $sealed['provider_response_identity']);
        self::assertStringNotContainsString('provider response', CanonicalJson::encode($sealed));
    }

    public function testStartedInvocationCannotBeStartedAgain(): void
    {
        $claim = $this->claim();
        $journal = new ProviderInvocationJournalService($this->root);
        $journal->start($claim, new \DateTimeImmutable());

        $this->expectExceptionMessage('CLV412_PROVIDER_INVOCATION_ALREADY_STARTED');
        $journal->start($claim, new \DateTimeImmutable());
    }

    public function testUnknownOutcomeIsTerminallyReplayProhibited(): void
    {
        $claim = $this->claim();
        $journal = new ProviderInvocationJournalService($this->root);
        $journal->start($claim, new \DateTimeImmutable());
        $unknown = $journal->markUnknown($claim, new \DateTimeImmutable());

        self::assertSame('PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED', $unknown['status']);
        self::assertFalse($unknown['automatic_replay_permitted']);

        $this->expectExceptionMessage('CLV412_PROVIDER_INVOCATION_ALREADY_STARTED');
        $journal->start($claim, new \DateTimeImmutable());
    }

    public function testCallerSuppliedClaimMustMatchDurableClaimExactly(): void
    {
        $claim = $this->claim();
        $claim['model']['configuration']['temperature'] = 0.9;

        $this->expectExceptionMessage('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        (new ProviderInvocationJournalService($this->root))->start($claim, new \DateTimeImmutable());
    }

    private function claim(): array
    {
        $id = 'provider-invocation-'.str_repeat('a', 20);
        $claim = [
            'schema' => 'imperium.clavium-provider-invocation-claim/v1',
            'claim_id' => $id,
            'claim_fingerprint' => str_repeat('b', 64),
            'instance_id' => 'imperium-test',
            'source_activation' => ['id' => 'activation', 'digest' => str_repeat('c', 64)],
            'target' => ['commission_id' => 'commission'],
            'model' => [
                'runtime_binding' => [
                    'provider' => 'deepseek',
                    'platform_service' => 'ai.platform.generic.deepseek',
                    'runtime_model' => 'deepseek-v4-flash',
                ],
                'configuration' => ['temperature' => 0.2],
            ],
            'lease_consumption' => [
                'lease_id' => 'lease',
                'consumed' => true,
                'consumed_at' => '2026-08-25T12:59:00+00:00',
                'expires_at' => '2026-08-25T14:00:00+00:00',
                'continuing_authority' => false,
            ],
            'turn_authority_consumption' => [
                'authority_id' => 'authority',
                'consumed' => true,
                'consumed_at' => '2026-08-25T12:59:00+00:00',
                'continuing_authority' => false,
            ],
            'provider_request' => [
                'idempotency_key' => 'imperium-'.$id,
                'external_io_started' => false,
                'provider_response_identity' => null,
            ],
            'recovery' => [
                'automatic_replay_permitted' => false,
                'unknown_outcome_requires_governed_resolution' => true,
            ],
            'claimed_at' => '2026-08-25T12:59:00+00:00',
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
            'provider_invoked' => false,
            'credential_material_present' => false,
            'sealed' => true,
        ];
        $this->write($this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json', $claim);

        return json_decode((string) file_get_contents($this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
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
