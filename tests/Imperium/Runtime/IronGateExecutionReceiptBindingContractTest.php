<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicReceiptBindingContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingContractTest extends TestCase
{
    public function testClaimAndReceiptContractsAreSeparatelyNamedAndVersioned(): void
    {
        self::assertSame('imperium.la-cortine.deterministic-execution-claim/v1', DeterministicExecutionClaimContract::SCHEMA);
        self::assertSame(1, DeterministicExecutionClaimContract::VERSION);
        self::assertSame('imperium.la-cortine.deterministic-receipt-binding/v1', DeterministicReceiptBindingContract::SCHEMA);
        self::assertSame(1, DeterministicReceiptBindingContract::VERSION);
        self::assertNotSame(DeterministicExecutionClaimContract::SCHEMA, DeterministicReceiptBindingContract::SCHEMA);
    }

    public function testClaimBindsNativeAuthorityExactActWinnerAndProviderSafety(): void
    {
        foreach (['source_authorization', 'request', 'holder', 'replay_fingerprint', 'execution_identity', 'credential_capability', 'provider_safety', 'effect'] as $field) {
            self::assertContains($field, DeterministicExecutionClaimContract::REQUIRED_FIELDS);
        }
        foreach (['operation', 'destination', 'payload_digest', 'expected_return_contract'] as $field) {
            self::assertContains($field, DeterministicExecutionClaimContract::REQUIRED_REQUEST_FIELDS);
        }
        self::assertSame(['PROVIDER_IDEMPOTENCY_KEY', 'NON_REPLAYABLE_UNKNOWN_OUTCOME'], DeterministicExecutionClaimContract::PROVIDER_SAFETY_STRATEGIES);
        self::assertContains('CLAIMED_PRE_IO', DeterministicExecutionClaimContract::EFFECT_CHECKPOINTS);
        self::assertContains('UNKNOWN_REPLAY_PROHIBITED', DeterministicExecutionClaimContract::EFFECT_OUTCOMES);
    }

    public function testUnknownOutcomeCannotReplayCredentialOrProvider(): void
    {
        self::assertFalse(DeterministicExecutionClaimContract::UNKNOWN_OUTCOME_RULES['automatic_replay_permitted']);
        self::assertFalse(DeterministicExecutionClaimContract::UNKNOWN_OUTCOME_RULES['credential_reconsumption_permitted']);
        self::assertFalse(DeterministicExecutionClaimContract::UNKNOWN_OUTCOME_RULES['provider_reinvocation_permitted']);
        self::assertFalse(DeterministicExecutionClaimContract::UNKNOWN_OUTCOME_RULES['receipt_may_claim_acceptance']);
        self::assertTrue(DeterministicExecutionClaimContract::UNKNOWN_OUTCOME_RULES['governed_resolution_required']);
    }

    public function testReceiptBindsTruthfulOutcomeRawBytesAdmissionAndRecovery(): void
    {
        foreach (['execution_claim', 'source_authorization', 'request', 'provider_outcome', 'raw_receipt', 'lazaretto_admission', 'recovery'] as $field) {
            self::assertContains($field, DeterministicReceiptBindingContract::REQUIRED_FIELDS);
        }
        self::assertSame(['ACCEPTED', 'REJECTED', 'UNKNOWN_REPLAY_PROHIBITED'], DeterministicReceiptBindingContract::PROVIDER_OUTCOMES);
        self::assertContains('sealed_content_reference', DeterministicReceiptBindingContract::REQUIRED_RAW_RECEIPT_FIELDS);
        self::assertContains('expected_return_contract_validated', DeterministicReceiptBindingContract::REQUIRED_ADMISSION_FIELDS);
        self::assertNotContains(false, DeterministicReceiptBindingContract::RECONSTRUCTION_REQUIREMENTS);
    }

    public function testContractsAreDeclarativeAndOpenNoPerimeterBehavior(): void
    {
        self::assertNotContains(true, DeterministicExecutionClaimContract::CONTRACT_BOUNDARY);
        self::assertNotContains(true, DeterministicReceiptBindingContract::CONTRACT_BOUNDARY);
    }
}
