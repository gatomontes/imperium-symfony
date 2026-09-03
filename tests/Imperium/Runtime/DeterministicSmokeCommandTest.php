<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Command\DeterministicHttpPostSmokeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DeterministicSmokeCommandTest extends TestCase
{
    public function testOldEmailSmokeRefusesWithoutCanonicalBindingRoot(): void
    {
        $tester = new CommandTester(new DeterministicHttpPostSmokeCommand());

        self::assertSame(1, $tester->execute([]));
        $display = $tester->getDisplay();

        self::assertStringContainsString('REFUSED CCI_EMAIL_REQUEST_HAS_NO_BINDING_ROOT', $display);
        self::assertStringNotContainsString('DETERMINISTIC_ROUND_TRIP_OK', $display);
        self::assertStringNotContainsString('receipt.sha256=', $display);
        self::assertStringNotContainsString('smoke-secret-', $display);
    }
}
