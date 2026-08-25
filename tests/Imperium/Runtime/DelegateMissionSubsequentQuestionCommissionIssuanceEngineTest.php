<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionSubsequentQuestionCommissionIssuanceEngine;
use PHPUnit\Framework\TestCase;

final class DelegateMissionSubsequentQuestionCommissionIssuanceEngineTest extends TestCase
{
    public function testTrustCannotEnterTheSubsequentQuestionEngine(): void
    {
        $engine = new DelegateMissionSubsequentQuestionCommissionIssuanceEngine(sys_get_temp_dir().'/imperium-subsequent-question-'.bin2hex(random_bytes(4)));

        $this->expectExceptionMessage('S701_DELEGATE_MISSION_SUBSEQUENT_QUESTION_JURISDICTION_INVALID');
        $engine->issue('trust', 'irrelevant', 'irrelevant', 'irrelevant', new \DateTimeImmutable());
    }
}
