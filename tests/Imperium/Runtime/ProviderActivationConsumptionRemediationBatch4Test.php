<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationAuthorityConsumptionContract;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationContract;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationWinnerContract;
use PHPUnit\Framework\TestCase;

final class ProviderActivationConsumptionRemediationBatch4Test extends TestCase
{
    public function testOneWinnerCarriesRevocationAndAuthorityConsumption(): void
    {
        self::assertSame(
            [
                'schema', 'winner_id', 'instance_id',
                'provider_binding_activation', 'revocation_authority',
                'revocation_authority_consumption', 'reason_code',
                'winner_scope', 'revoked_at', 'sealed', 'record_digest',
            ],
            ProviderBindingActivationRevocationWinnerContract::REQUIRED_FIELDS,
        );
        self::assertSame(
            [
                'authority_id', 'authority_digest', 'single_use',
                'consumed', 'continuing_authority',
            ],
            ProviderBindingActivationRevocationWinnerContract
                ::REQUIRED_CONSUMPTION_FIELDS,
        );
        self::assertSame(
            'governed-provider-execution-admission:',
            ProviderBindingActivationRevocationWinnerContract::LOCK_SCOPE_PREFIX,
        );
    }

    public function testSeparateComponentProductionIsExplicitlyProhibited(): void
    {
        self::assertSame(
            'DO_NOT_PRODUCE_SEPARATELY',
            ProviderBindingActivationRevocationContract::PRODUCTION_POSTURE,
        );
        self::assertSame(
            'DO_NOT_PRODUCE_SEPARATELY',
            ProviderBindingActivationRevocationAuthorityConsumptionContract
                ::PRODUCTION_POSTURE,
        );
    }

    public function testAtomicWinnerCarriesNoUnrelatedAuthority(): void
    {
        foreach (
            ProviderBindingActivationRevocationWinnerContract::NON_AUTHORITIES
            as $permission
        ) {
            self::assertFalse($permission);
        }
    }

    public function testDocumentationRefusesDualWriteAndAuthorizesOneRecordOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/'
                .'provider-activation-consumption-remediation-revocation-atomicity-correction.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-activation-consumption-remediation-batch-4-complete.md',
            ),
        );

        foreach ([
            'BATCH_4_REVOCATION_DUAL_WRITE_REFUSED_ATOMIC_WINNER_CONTRACT_DEFINED',
            'does not make two filesystem record writes transactional',
            'one immutable activation-keyed record',
            'DO_NOT_PRODUCE_SEPARATELY',
            'Only remediation Batch 5 may next be considered',
            'No runtime producer was added',
            'may not migrate stationary credential resolution',
            'invoke a provider',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'three batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($document.$handoff, $boundary), $boundary);
        }
    }
}
