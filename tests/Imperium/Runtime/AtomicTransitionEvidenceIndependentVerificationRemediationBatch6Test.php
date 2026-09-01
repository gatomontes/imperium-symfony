<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\AtomicTransitionIndependentVerificationTerminalAuditor;
use App\IndependentVerification\AtomicTransitionLocalVerificationPreflight;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceIndependentVerificationRemediationBatch6Test extends TestCase
{
    public function testTerminalAuditRetainsRequalificationAndRefusesClosure(): void
    {
        $summary = json_decode((string) file_get_contents(
            dirname(__DIR__, 3).'/docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json',
        ), true, 512, JSON_THROW_ON_ERROR);
        $preflight = (new AtomicTransitionLocalVerificationPreflight())->assess('preflight.terminal', $summary);
        $audit = (new AtomicTransitionIndependentVerificationTerminalAuditor())->audit('audit.terminal', $preflight);

        self::assertSame('CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT', $audit['status']);
        self::assertTrue($audit['requalification_retained']);
        self::assertTrue($audit['campaign_terminal']);
        foreach (['acceptance_case_evidence_retained', 'passing_independent_report_available',
            'detached_attestation_available', 'independent_verification_admission_available',
            'legacy_unsigned_closure_accepted', 'closure_restored', 'runtime_state_written',
            'authority_issued_or_consumed', 'provider_invoked', 'external_io_started',
            'continuing_authority'] as $field) {
            self::assertFalse($audit[$field], $field);
        }
        self::assertSame('BOUND_INACTIVE', $audit['provider_binding_status']);
        self::assertSame('NOT_IMPLEMENTED', $audit['required_v3_execution_admission']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $audit['unknown_replay_posture']);
    }

    public function testLegacyClosureIsDisabledAndConsumerHasNoConfiguredTrustAnchor(): void
    {
        $root = dirname(__DIR__, 3);
        $legacy = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceTerminalAdversarialAuditor.php');
        $services = (string) file_get_contents($root.'/config/services.yaml');
        self::assertStringContainsString('PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED', $legacy);
        self::assertStringContainsString('AtomicTransitionEvidenceTerminalAdversarialAuditor.php', $services);
        self::assertStringContainsString('AtomicTransitionIndependentVerificationAdmissionConsumer.php', $services);
    }

    public function testTamperedPreflightCannotBecomeTerminalEvidence(): void
    {
        $this->expectExceptionMessage('PBL1034_INDEPENDENT_VERIFICATION_TERMINAL_EVIDENCE_INVALID');
        (new AtomicTransitionIndependentVerificationTerminalAuditor())->audit('audit.invalid', [
            'schema' => 'imperium.atomic-transition-local-verification-preflight/v1',
            'disposition' => 'ELIGIBLE_FOR_EXPLICITLY_AUTHORIZED_LOCAL_VERIFICATION',
            'sealed' => true, 'record_digest' => str_repeat('0', 64),
        ]);
    }
}
