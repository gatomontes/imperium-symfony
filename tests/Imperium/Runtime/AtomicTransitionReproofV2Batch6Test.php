<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\ReproofV2\Contract;
use App\ReproofV2\Records;
use PHPUnit\Framework\TestCase;

/** Public records only. No key generation, private-key access or new signatures. */
final class AtomicTransitionReproofV2Batch6Test extends TestCase
{
    public function testActualDetachedEvidenceMatchesApprovedPinsAndPassesAllDomains(): void
    {
        $anchor = $this->read('trust-anchor');
        foreach (['candidate', 'identity', 'report', 'attestation'] as $kind) {
            $record = $this->read($kind);
            self::assertSame(Contract::FIELDS[$kind], array_keys($record));
            self::assertSame(Contract::SCHEMAS[$kind], $record['schema']);
            self::assertSame($record, Records::seal($record));
            self::assertSame($anchor[$kind.'_digest'], $record['record_digest']);
        }
        $identity = $this->read('identity'); $report = $this->read('report'); $attestation = $this->read('attestation');
        self::assertSame('PASS', $report['disposition']);
        self::assertSame(array_fill_keys(Contract::DOMAINS, 'PASS'), $report['domain_outcomes']);
        self::assertSame($identity['record_digest'], $report['trusted_identity_digest']);
        self::assertSame($identity['record_digest'], $attestation['identity_digest']);
        self::assertSame($report['record_digest'], $attestation['report_digest']);
        self::assertSame($anchor['public_key_digest'], hash('sha256', hex2bin($identity['public_key'])));
        self::assertSame($anchor['verifier_root'], $report['verifier_root']);
        self::assertSame($anchor['verifier_root'], $identity['verifier_root']);
        self::assertSame(Contract::PURPOSE, $identity['purpose']);
        self::assertSame(Contract::PURPOSE, $attestation['purpose']);
        self::assertTrue(sodium_crypto_sign_verify_detached(hex2bin($attestation['signature']), Contract::PURPOSE."\0".$report['record_digest'], hex2bin($identity['public_key'])));
        self::assertFalse($report['qualification_removed']); self::assertFalse($report['campaign_closed']);
    }

    public function testDetachedSignatureRejectsReportAndPurposeSubstitution(): void
    {
        $identity = $this->read('identity'); $report = $this->read('report'); $attestation = $this->read('attestation');
        $signature = hex2bin($attestation['signature']); $key = hex2bin($identity['public_key']);
        $changed = $report; $changed['campaign_closed'] = true; $changed = Records::seal($changed);
        self::assertFalse(sodium_crypto_sign_verify_detached($signature, Contract::PURPOSE."\0".$changed['record_digest'], $key));
        self::assertFalse(sodium_crypto_sign_verify_detached($signature, 'another-purpose'."\0".$report['record_digest'], $key));
        $signature[0] = chr(ord($signature[0]) ^ 1);
        self::assertFalse(sodium_crypto_sign_verify_detached($signature, Contract::PURPOSE."\0".$report['record_digest'], $key));
    }

    public function testIdentityHasFiniteValidityAndSeparateOperatorApproval(): void
    {
        $identity = $this->read('identity');
        self::assertSame(86400, strtotime($identity['expires_at']) - strtotime($identity['not_before']));
        $root = dirname(__DIR__, 3);
        $approval = file_get_contents($root.'/docs/atomic-transition-reproof-v2-batch-6-operator-approval.md');
        self::assertStringContainsString('Approved. Proceed', $approval);
        self::assertStringContainsString('11731fa32c45d2731f1a961d4be5d492d3b34b6573fd072dbb444dea80393f9b', $approval);
        $handoff = file_get_contents($root.'/docs/handoffs/atomic-transition-reproof-v2-batch-6-complete.md');
        foreach (['all eight', 'Two stages remain', 'private key', 'No retry occurred',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT'] as $text) {
            self::assertStringContainsString($text, $handoff);
        }
    }

    private function read(string $kind): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 3).'/docs/evidence/atomic-transition-reproof-v2-proof-2-'.$kind.'.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
