<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Sortie\GovernedSortieToolRegistry;
use App\Imperium\Runtime\Sortie\SortieToolExecutor;
use PHPUnit\Framework\TestCase;

final class GovernedSortieToolRegistryTest extends TestCase
{
    public function testResolvesExactlyOneSupportingExecutor(): void
    {
        $executor = $this->createMock(SortieToolExecutor::class);
        $executor->expects(self::once())->method('supports')->with('http.get')->willReturn(true);

        $registry = new GovernedSortieToolRegistry([$executor]);

        self::assertSame($executor, $registry->resolve('http.get'));
    }

    public function testRefusesUnavailableTool(): void
    {
        $executor = $this->createMock(SortieToolExecutor::class);
        $executor->expects(self::once())->method('supports')->with('http.post')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SORTIE_TOOL_UNAVAILABLE');

        (new GovernedSortieToolRegistry([$executor]))->resolve('http.post');
    }

    public function testRefusesAmbiguousToolOwnership(): void
    {
        $first = $this->createMock(SortieToolExecutor::class);
        $second = $this->createMock(SortieToolExecutor::class);
        $first->expects(self::once())->method('supports')->with('http.get')->willReturn(true);
        $second->expects(self::once())->method('supports')->with('http.get')->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SORTIE_TOOL_AMBIGUOUS');

        (new GovernedSortieToolRegistry([$first, $second]))->resolve('http.get');
    }
}
