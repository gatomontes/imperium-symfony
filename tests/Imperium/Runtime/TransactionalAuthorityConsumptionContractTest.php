<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Persistence\AuthorityConsumptionRecoveryContract;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionContract;
use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionContractTest extends TestCase
{
    public function testConsumptionAndRecoveryContractsAreSeparatelyNamedAndVersioned(): void
    {
        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', TransactionalAuthorityConsumptionContract::SCHEMA);
        self::assertSame(1, TransactionalAuthorityConsumptionContract::VERSION);
        self::assertSame('imperium.runtime-authority-consumption-recovery/v1', AuthorityConsumptionRecoveryContract::SCHEMA);
        self::assertSame(1, AuthorityConsumptionRecoveryContract::VERSION);
        self::assertNotSame(TransactionalAuthorityConsumptionContract::SCHEMA, AuthorityConsumptionRecoveryContract::SCHEMA);
    }

    public function testConsumptionContractBindsCompleteInputsConsumerOrderAndResult(): void
    {
        foreach (['authority_set', 'authoritative_inputs', 'replay_fingerprint', 'consumer', 'lock_plan', 'consumption_result', 'recovery'] as $field) {
            self::assertContains($field, TransactionalAuthorityConsumptionContract::REQUIRED_FIELDS);
        }
        self::assertSame([
            'authority_id', 'authority_schema', 'source', 'issuer', 'holder', 'scope', 'expires_at',
            'single_use', 'expected_unconsumed',
        ], TransactionalAuthorityConsumptionContract::REQUIRED_AUTHORITY_FIELDS);
        self::assertSame(['order', 'scope', 'authority_id'], TransactionalAuthorityConsumptionContract::REQUIRED_LOCK_FIELDS);
        self::assertSame(['actor', 'competent_service', 'bounded_act'], TransactionalAuthorityConsumptionContract::REQUIRED_CONSUMER_FIELDS);
        self::assertSame(['state', 'authority_consumptions', 'immutable_result', 'continuing_authority'], TransactionalAuthorityConsumptionContract::REQUIRED_RESULT_FIELDS);
        self::assertNotContains(false, TransactionalAuthorityConsumptionContract::REPLAY_REQUIREMENTS);
    }

    public function testRecoveryContractMakesEveryCommitBoundaryAndUnknownOutcomeExplicit(): void
    {
        self::assertSame([
            'NOT_STARTED', 'PREPARED', 'CONSUMPTION_COMMITTED', 'RESULT_COMMITTED', 'COMPLETE', 'UNKNOWN',
        ], AuthorityConsumptionRecoveryContract::CHECKPOINTS);
        self::assertContains('FORWARD_RECOVERY_REQUIRED', AuthorityConsumptionRecoveryContract::OUTCOMES);
        self::assertSame([
            'automatic_retry_permitted', 'same_replay_fingerprint_required', 'provider_reinvocation_permitted',
        ], AuthorityConsumptionRecoveryContract::REQUIRED_RETRY_FIELDS);
        self::assertFalse(AuthorityConsumptionRecoveryContract::UNKNOWN_OUTCOME_RULES['automatic_retry_permitted']);
        self::assertFalse(AuthorityConsumptionRecoveryContract::UNKNOWN_OUTCOME_RULES['provider_reinvocation_permitted']);
        self::assertFalse(AuthorityConsumptionRecoveryContract::UNKNOWN_OUTCOME_RULES['authority_unconsume_permitted']);
        self::assertTrue(AuthorityConsumptionRecoveryContract::UNKNOWN_OUTCOME_RULES['governed_resolution_required']);
        self::assertTrue(AuthorityConsumptionRecoveryContract::UNKNOWN_OUTCOME_RULES['sealed_response_forward_recovery_only']);
    }

    public function testContractsDescribeMechanicsWithoutOpeningOrChangingAuthority(): void
    {
        self::assertNotContains(true, TransactionalAuthorityConsumptionContract::CONSTITUTIONAL_BOUNDARY);
        self::assertNotContains(true, AuthorityConsumptionRecoveryContract::RECOVERY_BOUNDARY);
    }
}
