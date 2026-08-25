<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use PHPUnit\Framework\TestCase;

final class ProviderResponseEnvelopeServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-response-envelope-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testResponseIsDurableBeforeTurnPersistenceAndExactlyReplayable(): void
    {
        $claim = ['claim_id' => 'provider-invocation-'.str_repeat('a', 20), 'record_digest' => str_repeat('b', 64)];
        $service = new ProviderResponseEnvelopeService($this->root);
        $at = new \DateTimeImmutable('2026-08-25T14:00:00+00:00');
        $first = $service->seal($claim, '{"disposition":"COMPLETED"}', $at);
        $replay = $service->seal($claim, '{"disposition":"COMPLETED"}', $at);

        self::assertSame($first, $replay);
        self::assertSame('{"disposition":"COMPLETED"}', $first['response']);
        self::assertSame('sha256:'.hash('sha256', $first['response']), $first['provider_response_identity']);
        self::assertFalse($first['automatic_provider_replay_permitted']);
        self::assertFalse($first['credential_material_present']);
    }

    public function testConflictingResponseForClaimFailsStopped(): void
    {
        $claim = ['claim_id' => 'provider-invocation-'.str_repeat('a', 20), 'record_digest' => str_repeat('b', 64)];
        $service = new ProviderResponseEnvelopeService($this->root);
        $at = new \DateTimeImmutable('2026-08-25T14:00:00+00:00');
        $service->seal($claim, 'first', $at);

        $this->expectExceptionMessage('PST111_IMMUTABLE_RECORD_CONFLICT');
        $service->seal($claim, 'changed', $at);
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
