<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Command\DeterministicHttpPostSmokeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DeterministicSmokeCommandTest extends TestCase
{
    public function testSmokeProvesMechanicalLaneWithoutSortie(): void
    {
        $tester = new CommandTester(new DeterministicHttpPostSmokeCommand());

        self::assertSame(0, $tester->execute([]));
        $display = $tester->getDisplay();

        self::assertStringContainsString('DETERMINISTIC_ROUND_TRIP_OK', $display);
        self::assertStringContainsString('operation=email.send', $display);
        self::assertStringContainsString('sortie=NONE', $display);
        self::assertStringNotContainsString('smoke-secret-', $display);
    }
}
