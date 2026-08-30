<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionEffectReadinessBatch6Test extends TestCase
{
    public function testDecisionAndActivationAreSeparatelyVersionedAuthorityEmptyContracts(): void
    {
        self::assertSame(1, ProviderExecutorPrincipalActivationDecisionContract::VERSION);
        self::assertSame(1, ProviderExecutorPrincipalActivationContract::VERSION);
        self::assertNotSame(
            ProviderExecutorPrincipalActivationDecisionContract::SCHEMA,
            ProviderExecutorPrincipalActivationContract::SCHEMA,
        );
        self::assertSame(
            ['AUTHORIZED', 'REFUSED'],
            ProviderExecutorPrincipalActivationDecisionContract::DISPOSITIONS,
        );
        self::assertSame(
            [
                'ACTIVE',
                'EXPIRED',
                'REVOKED',
            ],
            ProviderExecutorPrincipalActivationContract::STATUSES,
        );

        foreach ([
            ProviderExecutorPrincipalActivationDecisionContract::NON_AUTHORITIES,
            ProviderExecutorPrincipalActivationContract::NON_AUTHORITIES,
        ] as $nonAuthorities) {
            foreach ($nonAuthorities as $permission) {
                self::assertFalse($permission);
            }
        }
    }

    public function testDecisionBindsExactCompetenceGenerationAssuranceAndSingleUseAuthorityShape(): void
    {
        foreach ([
            'source_authority',
            'actor',
            'principal_attestation',
            'provider_assurance_admission',
            'scope',
            'activation_authority',
            'validity',
            'external_action_performed',
        ] as $field) {
            self::assertContains(
                $field,
                ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_FIELDS,
            );
        }

        self::assertSame(
            'ACTIVATE_EXACT_ATTESTED_PROVIDER_EXECUTOR_PRINCIPAL_GENERATION',
            ProviderExecutorPrincipalActivationDecisionContract::PERMITTED_TRANSITION,
        );
        self::assertSame(
            [
                'authority_id',
                'authority_single_use',
                'authority_exercisable',
                'issuer_service',
                'permitted_transition',
                'target_attestation_digest',
                'expires_at',
                'consumed',
                'continuing_authority',
            ],
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
        );
        self::assertContains(
            'principal_generation',
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_SCOPE_FIELDS,
        );
        self::assertContains(
            'process_boundary_id',
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_SCOPE_FIELDS,
        );
    }

    public function testActivationIsSeparateFromImmutableInertAttestation(): void
    {
        self::assertContains('ATTESTED_INERT', ProviderExecutorPrincipalContract::STATUSES);
        self::assertFalse(
            ProviderExecutorPrincipalContract::NON_AUTHORITIES['activates_principal'],
        );

        foreach ([
            'source_decision',
            'consumed_activation_authority',
            'provider_assurance_admission',
            'execution_boundary',
            'principal_attestation',
            'principal',
            'scope',
            'validity',
            'reconstruction',
        ] as $field) {
            self::assertContains(
                $field,
                ProviderExecutorPrincipalActivationContract::REQUIRED_FIELDS,
            );
        }

        self::assertSame(
            [
                'read_only',
                'exact_replay_only',
                'reactivation_permitted',
                'generation_upgrade_permitted',
            ],
            ProviderExecutorPrincipalActivationContract::REQUIRED_RECONSTRUCTION_FIELDS,
        );
        self::assertSame(
            'UNKNOWN_REPLAY_PROHIBITED',
            ProviderExecutorPrincipalActivationContract::UNKNOWN_OUTCOME_POSTURE,
        );
    }

    public function testContractsContainNoSecretOrRuntimeProducerSurface(): void
    {
        $root = dirname(__DIR__, 3);
        $sources = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/'
                .'ProviderExecutorPrincipalActivationDecisionContract.php',
        );
        $sources .= (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationContract.php',
        );

        foreach ([
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthorityIssuanceService',
            'function activate',
            'function issue',
            'function consume',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $sources);
        }
    }

    public function testDocumentationPreservesTheClosedRuntimePerimeter(): void
    {
        $doc = $this->document(
            'docs/provider-execution-effect-readiness-principal-activation-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-execution-effect-readiness-batch-6-complete.md',
        );

        foreach ([
            'BATCH_6_AUTHORITY_EMPTY_EXECUTOR_PRINCIPAL_ACTIVATION_CONTRACTS_COMPLETE',
            'ATTESTED_INERT',
            'Activation is a separate record',
            'BOUND_INACTIVE',
            'UNKNOWN_REPLAY_PROHIBITED',
            'No producer, validator, store or transition exists',
            'Contract existence grants no authority',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Batch 7 may next be considered',
            'pure fail-closed validators',
            'immutable caller-supplied offline fixture stores',
            'principal remains inert',
            'provider binding remains inactive',
            'no provider was invoked',
            'Iron Gate',
            'Lazaretto',
            'approximately four batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
