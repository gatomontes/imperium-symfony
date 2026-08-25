<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionQuestionDispatchAuthorizationEngine;
use PHPUnit\Framework\TestCase;

final class DelegateMissionQuestionDispatchAuthorizationEngineTest extends TestCase
{
    public function testUnknownJurisdictionFailsBeforeEvidenceAccess(): void
    {
        $engine = new DelegateMissionQuestionDispatchAuthorizationEngine(sys_get_temp_dir().'/imperium-dispatch-authorization-'.bin2hex(random_bytes(4)));
        $this->expectExceptionMessage('S793_DELEGATE_MISSION_QUESTION_DISPATCH_AUTHORIZATION_JURISDICTION_INVALID');
        $engine->decide('unknown', 'not-read', 'not-read', 'AUTHORIZED', 'not-read', new \DateTimeImmutable());
    }
}
