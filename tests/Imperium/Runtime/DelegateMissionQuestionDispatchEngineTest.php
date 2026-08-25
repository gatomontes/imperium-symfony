<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionQuestionDispatchEngine;
use PHPUnit\Framework\TestCase;

final class DelegateMissionQuestionDispatchEngineTest extends TestCase
{
    public function testUnknownJurisdictionFailsBeforeEvidenceAccess(): void
    {
        $engine = new DelegateMissionQuestionDispatchEngine(sys_get_temp_dir().'/imperium-question-dispatch-'.bin2hex(random_bytes(4)));
        $this->expectExceptionMessage('S790_DELEGATE_MISSION_QUESTION_DISPATCH_JURISDICTION_INVALID');
        $engine->dispatch('unknown', 'not-read', 'not-read', new \DateTimeImmutable());
    }
}
