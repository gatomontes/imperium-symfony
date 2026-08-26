<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\DeploymentCustodyCrashDemonstration;
use PHPUnit\Framework\TestCase;

final class DeploymentCustodyCrashDemonstrationTest extends TestCase
{
    private string $directory;
    protected function setUp(): void { $this->directory = sys_get_temp_dir().'/imperium-deployment-demo-'.bin2hex(random_bytes(5)); }
    protected function tearDown(): void { $this->remove($this->directory); }

    public function testFourCrashBoundariesProducePrivateAndSanitizedEvidence(): void
    {
        $result = (new DeploymentCustodyCrashDemonstration(dirname(__DIR__, 3)))->run($this->directory, new \DateTimeImmutable('2026-08-26T14:00:00+00:00'));
        self::assertSame('PROVED', $result['summary']['disposition']);
        self::assertSame(4, $result['summary']['cases_executed']);
        self::assertFalse($result['summary']['runtime_activation_created']);
        self::assertFileExists($result['private_evidence_file']);
        self::assertFileExists($result['sanitized_summary_file']);
        $private = $this->read($result['private_evidence_file']);
        self::assertSame(['PREPARED', 'CUSTODY_DEPLOYED', 'TRANSITION_RECORDED', 'COMPLETE'], array_column($private['cases'], 'crash_point'));
        self::assertTrue($private['single_winner_contention']['single_winner_proved']);
        foreach ($private['cases'] as $case) {
            self::assertSame('PROVED', $case['disposition']);
            self::assertNotContains(false, $case['assertions']);
        }
        $sanitized = $this->read($result['sanitized_summary_file']);
        self::assertArrayNotHasKey('cases', $sanitized);
        self::assertStringNotContainsString('var/imperium', json_encode($sanitized, JSON_THROW_ON_ERROR));
    }

    private function read(string $path): array { return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function remove(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child=$path.'/'.$entry; is_dir($child)?$this->remove($child):unlink($child); } rmdir($path); }
}
