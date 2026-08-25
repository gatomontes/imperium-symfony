<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\DelegateMissionCommissionReadinessRecordMechanics;
use PHPUnit\Framework\TestCase;

final class DelegateMissionCommissionReadinessRecordMechanicsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-commission-readiness-mechanics-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testCommissionAndReadinessUseImmutableExactReplay(): void
    {
        $mechanics = new DelegateMissionCommissionReadinessRecordMechanics($this->root);
        $commission = ['commission_id' => 'commission-123', 'status' => 'CONSTRUCTED'];
        $readiness = ['assessment_id' => 'assessment-123', 'status' => 'ASSESSED'];

        $storedCommission = $mechanics->saveCommission('commission-123', $commission);
        $storedReadiness = $mechanics->saveReadiness('assessment-123', $readiness);

        self::assertSame($storedCommission, $mechanics->saveCommission('commission-123', $commission));
        self::assertSame($storedReadiness, $mechanics->saveReadiness('assessment-123', $readiness));
        self::assertTrue($mechanics->isIntact($storedCommission));
        self::assertTrue($mechanics->isIntact($storedReadiness));
    }

    public function testCommissionConflictKeepsEstablishedErrorVocabulary(): void
    {
        $mechanics = new DelegateMissionCommissionReadinessRecordMechanics($this->root);
        $mechanics->saveCommission('commission-123', ['status' => 'FIRST']);

        $this->expectExceptionMessage('C269_DELEGATE_MISSION_COGNITION_COMMISSION_CONFLICT');
        $mechanics->saveCommission('commission-123', ['status' => 'CHANGED']);
    }

    public function testReadinessConflictKeepsEstablishedErrorVocabulary(): void
    {
        $mechanics = new DelegateMissionCommissionReadinessRecordMechanics($this->root);
        $mechanics->saveReadiness('assessment-123', ['status' => 'FIRST']);

        $this->expectExceptionMessage('C279_DELEGATE_MISSION_READINESS_CONFLICT');
        $mechanics->saveReadiness('assessment-123', ['status' => 'CHANGED']);
    }

    public function testTamperedRecordIsNotIntact(): void
    {
        $record = ['status' => 'SEALED'];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $record['status'] = 'TAMPERED';

        self::assertFalse((new DelegateMissionCommissionReadinessRecordMechanics($this->root))->isIntact($record));
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
