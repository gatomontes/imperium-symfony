<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer, TransitionContract};
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/ExecutableTransitionBatch1Test.php';

final class ExecutableTransitionBatch3Test extends TestCase
{
    public function testIssuedAuthorityCommitsOneAggregateAndReplayDoesNotReconsume(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            $grant = ExecutableTransitionBatch1Test::grant($directory);
            $store = new TransitionStore($directory);
            $store->locked(fn () => $store->put('grant', $grant));
            $pin = TransitionContract::digest($grant);
            $custody = new TransitionAuthority($store, $pin, static fn () => 150);
            $authority = $custody->issue();
            $consumer = new TransitionConsumer($store, $custody, static fn () => 150);
            $result = $consumer->execute($pin);
            self::assertSame(TransitionContract::WRITE_SET, array_keys($result['records']));
            self::assertTrue($result['records']['authority_consumption']['authority_consumed']);
            self::assertFalse($result['records']['receipt_target']['provider_effect_started']);
            self::assertSame('BOUND_INACTIVE', $result['records']['source_binding_transition']['descriptor_status']);
            try { $consumer->execute($pin); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_ALREADY_COMMITTED_READ_ONLY_REPLAY', $e->getMessage()); }
            self::assertSame($result, $store->read('commit'));
            self::assertSame($authority, $store->read('authority'));
            self::assertCount(1, glob($directory.'/commit.json'));
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public function testMissingAuthorityCannotBeReconstructedFromTheGrant(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            $grant = ExecutableTransitionBatch1Test::grant($directory);
            $store = new TransitionStore($directory);
            $store->locked(fn () => $store->put('grant', $grant));
            $pin = TransitionContract::digest($grant);
            try { (new TransitionConsumer($store, new TransitionAuthority($store, $pin, static fn () => 150), static fn () => 150))->execute($pin); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_AUTHORITY_LINEAGE_INVALID', $e->getMessage()); }
            self::assertNull($store->read('journal'));
            self::assertNull($store->read('commit'));
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }
}
