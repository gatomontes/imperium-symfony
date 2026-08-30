<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract;
use App\Imperium\Runtime\Imperator\ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch1Test extends TestCase
{
    public function testContractsSeparateGrantPendingSuccessorAndDecisionIssuance(): void
    {
        self::assertSame(
            'AUTHORIZE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_SCOPE_SUCCESSOR',
            ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::PERMITTED_TRANSITION,
        );
        self::assertSame(
            ['provider_executor_principal_activation_decision_authority'],
            ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_SCOPE_DELTA_FIELDS,
        );
        self::assertSame(
            'COMMIT_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_SCOPE_SUCCESSOR',
            ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::PERMITTED_TRANSITION,
        );
        self::assertSame(
            'PENDING_ACTIVATION',
            ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::INITIAL_STATUS,
        );
        self::assertContains(
            'separate_activation_authority_required',
            ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::REQUIRED_FIELDS,
        );
        self::assertSame(
            'PRODUCE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_AND_AUTHORITY',
            ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::PERMITTED_TRANSITION,
        );
    }

    public function testScopeDeltaIsNarrowAndExistingScopeIsPreserved(): void
    {
        $expected = [
            'provider_binding_activation_authority',
            'outbound_email_authority',
            'credential_authority',
            'provider_execution_authority',
            'corridor_disposition_authority',
        ];

        self::assertSame(
            $expected,
            ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_PRESERVED_SCOPE_FIELDS,
        );
        self::assertSame(
            $expected,
            ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::REQUIRED_PRESERVED_SCOPE_FIELDS,
        );
    }

    public function testEveryContractIsAuthorityEmpty(): void
    {
        foreach ([
            ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::NON_AUTHORITIES,
            ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::NON_AUTHORITIES,
            ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::NON_AUTHORITIES,
        ] as $claims) {
            foreach ($claims as $claim) {
                self::assertFalse($claim);
            }
        }
    }

    public function testIssuanceAuthorizationBindsExactActivationDecisionLineage(): void
    {
        foreach ([
            'issuer_principal',
            'scope_successor',
            'activation_disposition',
            'principal_attestation',
            'provider_assurance_admission',
            'execution_boundary',
            'decision_id',
            'activation_authority_id',
            'authority_single_use',
            'authority_exercisable',
            'issuance_winner_required',
            'consumption_winner_required',
            'revocation',
            'consumed',
            'continuing_authority',
        ] as $field) {
            self::assertContains(
                $field,
                ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::REQUIRED_FIELDS,
            );
        }
    }

    public function testDocumentationAuthorizesOnlyValidatorsAndFixtureStoresNext(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-1-complete.md',
        );

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_SCOPE_SUCCESSOR_AND_DECISION_ISSUANCE_CONTRACTS_COMPLETE',
            'only provider_executor_principal_activation_decision_authority',
            'PENDING_ACTIVATION',
            'distinct lifecycle transition',
            'Every NON_AUTHORITIES value is false',
            'Provider Effect Principal and Binding Activation remains paused',
        ] as $finding) {
            self::assertNotFalse(stripos($contracts, $finding), $finding);
        }

        foreach ([
            'Only remediation Batch 2 may next be considered',
            'fail-closed validators',
            'immutable fixture stores',
            'may not identify a live Operator Root',
            'issue or consume authority',
            'create a decision',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'approximately six batches',
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
