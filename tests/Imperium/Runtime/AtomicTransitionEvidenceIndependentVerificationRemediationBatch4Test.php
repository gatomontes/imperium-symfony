<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\AtomicTransitionLocalVerificationPreflight;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceIndependentVerificationRemediationBatch4Test extends TestCase
{
    public function testRetainedPublicSummaryRefusesBeforePrivateIntakeOrSigning(): void
    {
        $summary = json_decode((string) file_get_contents(
            dirname(__DIR__, 3).'/docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json',
        ), true, 512, JSON_THROW_ON_ERROR);
        $result = (new AtomicTransitionLocalVerificationPreflight())->assess('preflight.1', $summary);

        self::assertSame('REFUSED_ACCEPTANCE_CASE_EVIDENCE_NOT_RETAINED', $result['disposition']);
        foreach (['private_receipt_intake_permitted', 'signing_custody_opening_permitted',
            'local_verifier_executed', 'private_receipt_inspected', 'signing_capability_handled',
            'detached_signature_created', 'mission_rerun_permitted', 'replacement_receipt_permitted',
            'runtime_state_written', 'continuing_authority'] as $field) {
            self::assertFalse($result[$field], $field);
        }
        self::assertTrue($result['read_only']);
    }

    public function testSyntheticCompletePublicBindingOnlyPermitsLaterExplicitIntake(): void
    {
        $summary = ['record_digest' => str_repeat('a', 64),
            'acceptance_matrix' => array_fill(0, 8, 'observed'),
            'acceptance_case_evidence_digest' => str_repeat('b', 64)];
        $result = (new AtomicTransitionLocalVerificationPreflight())->assess('preflight.2', $summary);
        self::assertSame('ELIGIBLE_FOR_EXPLICITLY_AUTHORIZED_LOCAL_VERIFICATION', $result['disposition']);
        self::assertTrue($result['private_receipt_intake_permitted']);
        self::assertTrue($result['signing_custody_opening_permitted']);
        self::assertFalse($result['local_verifier_executed']);
    }
}
