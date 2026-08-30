<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionContract;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationContract;
use PHPUnit\Framework\TestCase;

final class ProviderActivationConsumptionRemediationBatch1Test extends TestCase
{
    public function testCombinedAdmissionIsSeparatelyVersionedAndConsumesBothIdentities(): void
    {
        self::assertSame(1, GovernedProviderExecutionAdmissionContract::VERSION);
        self::assertSame(2, GovernedProviderExecutionCombinedAdmissionContract::VERSION);
        self::assertNotSame(
            GovernedProviderExecutionAdmissionContract::SCHEMA,
            GovernedProviderExecutionCombinedAdmissionContract::SCHEMA,
        );
        self::assertSame(
            [
                'activation_id', 'activation_digest', 'single_operation', 'consumed',
                'continuing_authority', 'winner_scope', 'revocation_status',
                'revocation_checked_at',
            ],
            GovernedProviderExecutionCombinedAdmissionContract
                ::REQUIRED_ACTIVATION_CONSUMPTION_FIELDS,
        );
        self::assertContains(
            'activation_consumption',
            GovernedProviderExecutionCombinedAdmissionContract::REQUIRED_FIELDS,
        );
        self::assertContains(
            'authority_consumption',
            GovernedProviderExecutionCombinedAdmissionContract::REQUIRED_FIELDS,
        );
        self::assertStringContainsString(
            'ACTIVATION_AND_AUTHORITY_CONSUMED',
            GovernedProviderExecutionCombinedAdmissionContract::CHECKPOINT,
        );
    }

    public function testRevocationIsAnExactAppendOnlyFactWithSharedLockVocabulary(): void
    {
        self::assertSame(
            [
                'schema', 'revocation_id', 'instance_id',
                'provider_binding_activation', 'source_revocation_authority',
                'reason_code', 'revoked_at', 'sealed', 'record_digest',
            ],
            ProviderBindingActivationRevocationContract::REQUIRED_FIELDS,
        );
        self::assertSame(
            'governed-provider-execution-admission:',
            ProviderBindingActivationRevocationContract::LOCK_SCOPE_PREFIX,
        );
        self::assertSame(
            ['id', 'digest', 'schema'],
            ProviderBindingActivationRevocationContract::REQUIRED_REFERENCE_FIELDS,
        );
        self::assertNotEmpty(
            ProviderBindingActivationRevocationContract::REASON_CODES,
        );
    }

    public function testContractsCarryNoRuntimeAuthority(): void
    {
        foreach ([
            GovernedProviderExecutionCombinedAdmissionContract::NON_AUTHORITIES,
            ProviderBindingActivationRevocationContract::NON_AUTHORITIES,
        ] as $permissions) {
            foreach ($permissions as $permission) {
                self::assertFalse($permission);
            }
        }
    }

    public function testBatchDocumentationAuthorizesProductionOnlyNext(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/provider-activation-consumption-remediation-contracts.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-activation-consumption-remediation-batch-1-complete.md',
            ),
        );

        foreach ([
            'BATCH_1_V2_COMBINED_ADMISSION_AND_REVOCATION_CONTRACTS_DEFINED',
            'No producer or consumer is implemented',
            'v1 is unchanged',
            'Only Batch 2 may next be considered',
            'shared lock',
            'may not migrate stationary credential resolution',
            'handle a credential or capability',
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
