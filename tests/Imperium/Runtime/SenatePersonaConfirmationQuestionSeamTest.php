<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SenatePersonaConfirmationQuestionSeamTest extends TestCase
{
    public function testFirstQuestionPhaseCannotInvokePersonaWitness(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaFirstTestimonyService.php');

        self::assertStringContainsString('FIRST_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION', $service);
        self::assertStringContainsString('"testimony" => null', $service);
        self::assertStringContainsString('"testimony_sealed" => false', $service);
        self::assertStringContainsString('"authority_single_use" => true', $service);
        self::assertStringContainsString('"authority_exercisable" => true', $service);
        self::assertStringContainsString('"consumed" => false', $service);
        self::assertStringNotContainsString('$this->cognition->answer(', $service);
    }

    public function testOperatorCommandNamesTheNewStoppedBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $command = (string) file_get_contents($root.'/src/Command/SenateConductSubordinatePersonaFirstTestimonyCommand.php');

        self::assertStringContainsString('imperium:senate:author-subordinate-persona-first-question', $command);
        self::assertStringContainsString('PENDING_TESTIMONY_AUTHORIZATION', $command);
        self::assertStringNotContainsString('FIRST_TESTIMONY_SEALED</info>', $command);
    }
}
