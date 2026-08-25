<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionSubsequentQuestionCommissionDispositionEngine;
use PHPUnit\Framework\TestCase;

final class DelegateMissionSubsequentQuestionCommissionDispositionEngineTest extends TestCase
{
    public function testTrustCannotEnterTheSubsequentQuestionDispositionEngine(): void
    {
        $root = sys_get_temp_dir().'/imperium-subsequent-disposition-'.bin2hex(random_bytes(6));
        $engine = new DelegateMissionSubsequentQuestionCommissionDispositionEngine($root);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('S702_DELEGATE_MISSION_SUBSEQUENT_QUESTION_DISPOSITION_JURISDICTION_INVALID');

        $engine->decide('trust', 'not-read', 'not-read', 'ACCEPTED', 'not-read', new \DateTimeImmutable());
    }
}
