<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SortieProcessLauncherDependencyTest extends TestCase
{
    public function testLauncherDoesNotDependOnSymfonyProcessComponent(): void
    {
        $path = dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/SortieProcessLauncher.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringNotContainsString('Symfony\\Component\\Process\\Process', $source);
        self::assertStringContainsString('proc_open', $source);
    }
}
