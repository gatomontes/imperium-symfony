<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionTestimonyResponseEngine;
use App\Imperium\Runtime\Senate\ProfileExaminationTestimonyCognitionGateway;
use PHPUnit\Framework\TestCase;

final class DelegateMissionTestimonyResponseEngineTest extends TestCase
{
    public function testUnknownJurisdictionFailsBeforeEvidenceOrCognition(): void
    {
        $cognition = $this->createMock(ProfileExaminationTestimonyCognitionGateway::class);
        $cognition->expects(self::never())->method('answer');
        $engine = new DelegateMissionTestimonyResponseEngine(sys_get_temp_dir().'/imperium-testimony-'.bin2hex(random_bytes(4)), $cognition);
        $this->expectExceptionMessage('S791_DELEGATE_MISSION_TESTIMONY_RESPONSE_JURISDICTION_INVALID');
        $engine->respond('unknown', 'not-read', new \DateTimeImmutable());
    }
}
