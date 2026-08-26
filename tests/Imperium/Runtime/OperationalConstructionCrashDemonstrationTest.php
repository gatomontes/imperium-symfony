<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\OperationalConstructionCrashDemonstration;
use PHPUnit\Framework\TestCase;

final class OperationalConstructionCrashDemonstrationTest extends TestCase
{
    private string $evidenceDirectory;

    protected function setUp(): void
    {
        $this->evidenceDirectory = sys_get_temp_dir().'/imperium-demo-evidence-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->evidenceDirectory);
    }

    public function testAllSixCrashBoundariesProduceRetainedAndSanitizedEvidence(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $result = (new OperationalConstructionCrashDemonstration($projectRoot))->run(
            $this->evidenceDirectory,
            new \DateTimeImmutable('2026-08-26T12:00:00+00:00'),
        );

        self::assertSame('PROVED', $result['summary']['disposition']);
        self::assertSame(6, $result['summary']['cases_executed']);
        self::assertFalse($result['summary']['continuing_operational_authority']);
        self::assertFileExists($result['private_evidence_file']);
        self::assertFileExists($result['sanitized_summary_file']);

        $private = $this->read($result['private_evidence_file']);
        self::assertCount(6, $private['cases']);
        self::assertSame(1, $private['single_winner_contention']['winner_count']);
        self::assertSame(1, $private['single_winner_contention']['conflict_count']);
        self::assertTrue($private['single_winner_contention']['single_winner_proved']);
        self::assertSame(
            [
                'BEFORE_QUALIFICATION_INDEXED',
                'QUALIFICATION_INDEXED',
                'BEFORE_ASSEMBLY_INDEXED',
                'ASSEMBLY_INDEXED',
                'BEFORE_BINDING_INDEXED',
                'BINDING_INDEXED',
            ],
            array_column($private['cases'], 'crash_point'),
        );
        foreach ($private['cases'] as $case) {
            self::assertSame('PROVED', $case['disposition']);
            self::assertTrue($case['conflict']['rejected']);
            self::assertSame(3, $case['replay']['generation_after']);
            self::assertSame([1, 2, 3], array_column($case['replay']['stages'], 'generation'));
            self::assertNotContains(false, $case['assertions']);
        }

        $sanitized = $this->read($result['sanitized_summary_file']);
        self::assertArrayNotHasKey('cases', $sanitized);
        self::assertArrayNotHasKey('fixture', $sanitized);
        self::assertStringNotContainsString('var/imperium', json_encode($sanitized, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('deepseek', strtolower(json_encode($sanitized, JSON_THROW_ON_ERROR)));
    }

    private function read(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
