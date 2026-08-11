<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\AdmittedInboundArtifact;
use App\Imperium\Runtime\LaCortine\InboundArtifactStore;
use PHPUnit\Framework\TestCase;

final class InboundArtifactStoreTest extends TestCase
{
    public function testPersistsExactRawPayloadOncePerProviderMessage(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-inbound-store-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $raw = "{\n  \"event_type\": \"message.received\"\n}";
        $artifact = new AdmittedInboundArtifact(
            'inbound-artifact.test',
            'raw.test',
            hash('sha256', $raw),
            $raw,
            '{"event_type":"message.received"}',
            ['authority' => 'none'],
            new \DateTimeImmutable('2026-08-11T06:00:00-04:00'),
        );
        $store = new InboundArtifactStore($root);

        try {
            self::assertTrue($store->persistOnce('msg_retry_safe', $artifact));
            self::assertFalse($store->persistOnce('msg_retry_safe', $artifact));

            $path = $root.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'lacortine'.DIRECTORY_SEPARATOR.'inbound'.DIRECTORY_SEPARATOR.hash('sha256', 'msg_retry_safe').'.json';
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($raw, $record['raw_content']);
            self::assertSame(hash('sha256', $raw), $record['raw_payload_digest']);
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $child = $path.DIRECTORY_SEPARATOR.$entry;
            is_dir($child) ? $this->removeTree($child) : @unlink($child);
        }
        @rmdir($path);
    }
}
