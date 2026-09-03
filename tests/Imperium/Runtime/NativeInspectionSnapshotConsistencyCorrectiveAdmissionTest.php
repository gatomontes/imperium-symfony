<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class NativeInspectionSnapshotConsistencyCorrectiveAdmissionTest extends TestCase
{
    public function testBreachIsPreservedAndRetroactiveAuthorizationIsRejected(): void
    {
        $review = $this->compact($this->read('docs/native-inspection-snapshot-consistency-authorization-breach-review-v1.md'));
        foreach ([
            'NATIVE_INSPECTION_SNAPSHOT_CLOSURE_REFUSED_UNAUTHORIZED_SCOPE_EXPANSION',
            'UNADMITTED_CANDIDATE_IMPLEMENTATION_AT_9EB4C60',
            'Preparation Batch 0 only',
            'does not rewrite the past',
            'no CI run',
        ] as $required) {
            self::assertStringContainsString($required, $review);
        }
    }

    public function testCorrectiveAdmissionPinsAuthorityCandidateAndEvidenceQualification(): void
    {
        $audit = $this->compact($this->read('docs/native-inspection-snapshot-consistency-corrective-admission-audit-v1.md'));
        foreach ([
            'Fix it',
            '9eb4c608bb496159aee9f7024fdcedae9a9e8f8a',
            'aff1017f456b35110d0e64b07cf6e89990d71cc0',
            'prospective authority only',
            '2,092 tests / 47,277 assertions',
            'does not claim that this corrective reviewer executed the suite',
            'NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CORRECTED_CLOSURE_ACCEPTED',
        ] as $required) {
            self::assertStringContainsString($required, $audit);
        }
    }

    public function testHistoricalClosureAndCurrentHandoffExposeSupersession(): void
    {
        $historical = $this->read('docs/handoffs/native-inspection-snapshot-consistency-campaign-complete.md');
        self::assertStringContainsString('HISTORICAL_CLOSURE_INVALID_UNAUTHORIZED_SCOPE_EXPANSION', $historical);
        $current = $this->read('docs/handoffs/native-inspection-snapshot-consistency-corrective-admission-complete.md');
        self::assertStringContainsString('AUTHORIZATION_BREACH_PRESERVED_CANDIDATE_PROSPECTIVELY_ADMITTED', $current);
        self::assertStringContainsString('No remediation stage remains', $current);
    }

    private function compact(string $document): string
    {
        return preg_replace('/\\s+/', ' ', trim($document)) ?? $document;
    }

    private function read(string $relative): string
    {
        $bytes = file_get_contents(dirname(__DIR__, 3).'/'.$relative);
        self::assertNotFalse($bytes);
        return $bytes;
    }
}
