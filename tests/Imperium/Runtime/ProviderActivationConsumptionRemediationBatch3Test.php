<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceContract;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationAuthorityConsumptionContract;
use PHPUnit\Framework\TestCase;

final class ProviderActivationConsumptionRemediationBatch3Test extends TestCase
{
    public function testRevocationAuthorityIsExactBoundedSingleUseAndNonContinuing(): void
    {
        foreach ([
            'provider_binding_activation',
            'execution_boundary',
            'executor_principal',
            'provider_binding',
            'allowed_reason_codes',
            'validity',
            'authority_single_use',
            'authority_exercisable',
            'consumed',
            'continuing_authority',
        ] as $field) {
            self::assertContains(
                $field,
                ProviderBindingActivationRevocationAuthorityContract::REQUIRED_FIELDS,
            );
        }
        self::assertSame(
            ['effective_at', 'expires_at', 'revocation_reference'],
            ProviderBindingActivationRevocationAuthorityContract::REQUIRED_VALIDITY_FIELDS,
        );
    }

    public function testDecisionIssuanceBindsExactRevocationTargetAndBasis(): void
    {
        self::assertSame(
            'provider_binding_activation_revocation_authority',
            ProviderBindingActivationRevocationAuthorityIssuanceContract::TARGET_KIND,
        );
        self::assertSame(
            [
                'provider_binding_activation', 'execution_boundary',
                'executor_principal', 'provider_binding',
            ],
            ProviderBindingActivationRevocationAuthorityIssuanceContract
                ::REQUIRED_BASIS_FIELDS,
        );
        self::assertContains(
            'revocation_authority_issued',
            ProviderBindingActivationRevocationAuthorityIssuanceContract
                ::REQUIRED_ISSUANCE_FIELDS,
        );
    }

    public function testConsumptionBindsOneAuthorityActivationAndRevocationFact(): void
    {
        foreach ([
            'revocation_authority',
            'provider_binding_activation',
            'revocation_fact',
            'single_use',
            'consumed',
            'continuing_authority',
            'winner_scope',
        ] as $field) {
            self::assertContains(
                $field,
                ProviderBindingActivationRevocationAuthorityConsumptionContract
                    ::REQUIRED_FIELDS,
            );
        }
        self::assertSame(
            'single-authoritative-root:provider-binding-activation:',
            ProviderBindingActivationRevocationAuthorityConsumptionContract
                ::WINNER_SCOPE_PREFIX,
        );
    }

    public function testEveryContractIsAuthorityEmptyBeyondItsExactPurpose(): void
    {
        foreach ([
            ProviderBindingActivationRevocationAuthorityContract::NON_AUTHORITIES,
            ProviderBindingActivationRevocationAuthorityIssuanceContract::NON_AUTHORITIES,
            ProviderBindingActivationRevocationAuthorityConsumptionContract::NON_AUTHORITIES,
        ] as $permissions) {
            foreach ($permissions as $permission) {
                self::assertFalse($permission);
            }
        }
    }

    public function testDocumentationAuthorizesProductionOnlyNext(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/'
                .'provider-activation-consumption-remediation-revocation-authority-contracts.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-activation-consumption-remediation-batch-3-complete.md',
            ),
        );

        foreach ([
            'BATCH_3_REVOCATION_AUTHORITY_ISSUANCE_AND_CONSUMPTION_CONTRACTS_DEFINED',
            'No authority or revocation fact is produced',
            'shared activation lock',
            'Only remediation Batch 4 may next be considered',
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
