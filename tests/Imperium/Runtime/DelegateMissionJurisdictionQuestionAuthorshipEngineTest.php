<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionJurisdictionQuestionAuthorshipEngine;
use App\Imperium\Runtime\Senate\ProfileExaminationQuestionCognitionGateway;
use PHPUnit\Framework\TestCase;

final class DelegateMissionJurisdictionQuestionAuthorshipEngineTest extends TestCase
{
    public function testUnsupportedJurisdictionFailsBeforeEvidenceOrCognition(): void
    {
        $gateway = new class implements ProfileExaminationQuestionCognitionGateway {
            public function authorQuestion(string $jurisdiction, array $commission, array $opening): array
            {
                TestCase::fail('Unsupported jurisdiction reached cognition.');
            }
        };
        $engine = new DelegateMissionJurisdictionQuestionAuthorshipEngine(sys_get_temp_dir().'/imperium-jurisdiction-'.bin2hex(random_bytes(4)), $gateway);

        $this->expectExceptionMessage('S700_DELEGATE_MISSION_QUESTION_JURISDICTION_INVALID');
        $engine->author('politics', 'irrelevant', 'irrelevant', new \DateTimeImmutable());
    }
}
