<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use PHPUnit\Framework\TestCase;

final class ReplayFingerprintTest extends TestCase
{
    public function testAssociativeKeyOrderDoesNotChangeFingerprint(): void
    {
        self::assertSame(
            ReplayFingerprint::of(['source' => ['id' => 'a', 'digest' => 'b'], 'authority' => 'c']),
            ReplayFingerprint::of(['authority' => 'c', 'source' => ['digest' => 'b', 'id' => 'a']]),
        );
    }

    public function testChangedAuthoritativeInputFailsStopped(): void
    {
        $recorded = ReplayFingerprint::of(['source_id' => 'source-a', 'digest' => 'digest-a']);

        $this->expectExceptionMessage('REPLAY_CONFLICT');
        ReplayFingerprint::requireMatch($recorded, ['source_id' => 'source-a', 'digest' => 'digest-b'], 'REPLAY_CONFLICT');
    }
}
