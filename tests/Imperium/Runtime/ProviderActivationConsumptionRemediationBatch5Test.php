<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationWinnerContract;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationWinnerService;

final class ProviderActivationConsumptionRemediationBatch5Test extends ProviderExecutionBoundaryRedesignBatch6Test
{
    public function testLawfulAuthorityProducesOneAtomicRevocationWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T23:00:00+00:00');
        $executionAuthority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $revocationAuthority = $this->issueRevocationAuthority($executionAuthority, $at);
        $service = new ProviderBindingActivationRevocationWinnerService($this->root);

        $winner = $service->revoke(
            $revocationAuthority['authority_id'],
            'OPERATOR_REVOKED',
            $at,
        );
        $replayed = $service->revoke(
            $revocationAuthority['authority_id'],
            'OPERATOR_REVOKED',
            $at->modify('+20 minutes'),
        );

        self::assertSame($winner, $replayed);
        self::assertSame(
            ProviderBindingActivationRevocationWinnerContract::SCHEMA,
            $winner['schema'],
        );
        self::assertTrue(
            $winner['revocation_authority_consumption']['single_use'],
        );
        self::assertTrue(
            $winner['revocation_authority_consumption']['consumed'],
        );
        self::assertFalse(
            $winner['revocation_authority_consumption']['continuing_authority'],
        );
        self::assertSame('OPERATOR_REVOKED', $winner['reason_code']);

        $this->expectExceptionMessage('PEB622_PROVIDER_ACTIVATION_REVOKED');
        (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($executionAuthority['authority_id'], $at);
    }

    public function testCombinedAdmissionWinnerPreventsLaterRevocationWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T23:00:00+00:00');
        $executionAuthority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $revocationAuthority = $this->issueRevocationAuthority($executionAuthority, $at);
        (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($executionAuthority['authority_id'], $at);

        $this->expectExceptionMessage('PEB774_COMBINED_ADMISSION_ALREADY_WON');
        (new ProviderBindingActivationRevocationWinnerService($this->root))
            ->revoke(
                $revocationAuthority['authority_id'],
                'OPERATOR_REVOKED',
                $at,
            );
    }

    public function testRevocationAuthorityIssuanceConsumesOnlyItsIssuancePermission(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T23:00:00+00:00');
        $executionAuthority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $revocationAuthority = $this->issueRevocationAuthority($executionAuthority, $at);

        self::assertSame(
            ProviderBindingActivationRevocationAuthorityContract::SCHEMA,
            $revocationAuthority['schema'],
        );
        self::assertTrue($revocationAuthority['authority_single_use']);
        self::assertTrue($revocationAuthority['authority_exercisable']);
        self::assertFalse($revocationAuthority['consumed']);
        self::assertFalse($revocationAuthority['continuing_authority']);
    }

    public function testSourcesContainNoCredentialProviderOrExternalIoPath(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime';
        $sources = (string) file_get_contents(
            $root.'/Imperator/'
            .'ProviderBindingActivationRevocationAuthorityIssuanceService.php',
        );
        $sources .= (string) file_get_contents(
            $root.'/LaCortine/'
            .'ProviderBindingActivationRevocationWinnerService.php',
        );

        foreach ([
            'revocation_authority_consumption',
            "'consumed' => true",
            "'continuing_authority' => false",
            'LOCK_SCOPE_PREFIX',
            'PEB774_COMBINED_ADMISSION_ALREADY_WON',
        ] as $proof) {
            self::assertStringContainsString($proof, $sources);
        }
        foreach ([
            'EnvironmentCredentialBroker',
            'CredentialCapability',
            'DeterministicTransport',
            'AgentMail',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $sources);
        }
    }

    public function testDocumentationAuthorizesOnlyV2ResolutionMigrationNext(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace(
            '/\\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/'
                .'provider-activation-consumption-remediation-revocation-production.md',
            ),
        );
        $handoff = preg_replace(
            '/\\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-activation-consumption-remediation-batch-5-complete.md',
            ),
        );

        foreach ([
            'BATCH_5_LAWFUL_ATOMIC_ACTIVATION_REVOCATION_PRODUCTION_COMPLETE',
            'same lock',
            'No dual-write revocation state exists',
            'Only remediation Batch 6 may next be considered',
            'require the v2 combined admission',
            'may not invoke a provider',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'two batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($document.$handoff, $boundary), $boundary);
        }
    }

    private function issueRevocationAuthority(
        array $executionAuthority,
        \DateTimeImmutable $at,
    ): array {
        $candidate = [
            'provider_binding_activation' => $executionAuthority['provider_binding_activation'],
            'execution_boundary' => $executionAuthority['execution_boundary'],
            'executor_principal' => $executionAuthority['executor_principal'],
            'provider_binding' => $executionAuthority['provider_binding'],
            'allowed_reason_codes' => ['OPERATOR_REVOKED'],
            'validity' => [
                'effective_at' => $at->format(DATE_ATOM),
                'expires_at' => $at->modify('+5 minutes')->format(DATE_ATOM),
                'revocation_reference' => null,
            ],
        ];
        $decisionId =
            'provider-binding-activation-revocation-authority-decision-1';
        $targetId =
            'provider-binding-activation-revocation-authority-1';
        $decision = $this->records->put(
            ProviderBindingActivationRevocationAuthorityIssuanceService::DECISIONS,
            $decisionId,
            [
                'schema' =>
                    ProviderBindingActivationRevocationAuthorityIssuanceContract
                        ::DECISION_SCHEMA,
                'decision_id' => $decisionId,
                'instance_id' => 'instance-1',
                'source_authority' => $this->reference(
                    'revocation-authority-source-1',
                ),
                'actor' => [
                    'principal_id' => 'imperator-1',
                    'office' => 'imperator',
                    'seat' => 'superadmin',
                    'binding_id' => 'imperator-binding-1',
                    'generation' => 1,
                ],
                'target' => [
                    'kind' =>
                        ProviderBindingActivationRevocationAuthorityIssuanceContract
                            ::TARGET_KIND,
                    'id' => $targetId,
                    'digest' => hash('sha256', CanonicalJson::encode($candidate)),
                    'schema' =>
                        ProviderBindingActivationRevocationAuthorityContract::SCHEMA,
                ],
                'basis' => [
                    'provider_binding_activation' =>
                        $candidate['provider_binding_activation'],
                    'execution_boundary' => $candidate['execution_boundary'],
                    'executor_principal' => $candidate['executor_principal'],
                    'provider_binding' => $candidate['provider_binding'],
                ],
                'disposition' => 'AUTHORIZED',
                'rationale' => 'Exact activation revocation authorized.',
                'limitations' => 'One reason, one activation, no continuation.',
                'issuance_authority' => [
                    'authority_id' =>
                        'revocation-authority-issuance-permission-1',
                    'authority_single_use' => true,
                    'authority_exercisable' => true,
                    'issuer_service' =>
                        'imperator.provider-activation-revocation-authority-issuer',
                    'permitted_transition' =>
                        ProviderBindingActivationRevocationAuthorityIssuanceContract
                            ::PERMITTED_TRANSITION,
                    'target_digest' =>
                        hash('sha256', CanonicalJson::encode($candidate)),
                    'expires_at' => $at->modify('+5 minutes')->format(DATE_ATOM),
                    'consumed' => false,
                    'continuing_authority' => false,
                ],
                'decided_at' => $at->format(DATE_ATOM),
                'expires_at' => $at->modify('+5 minutes')->format(DATE_ATOM),
                'external_action_performed' => false,
                'sealed' => true,
            ],
        );
        (new ProviderBindingActivationRevocationAuthorityIssuanceService($this->root))
            ->issue($decision['decision_id'], $candidate, $at);

        return $this->records->read(
            ProviderBindingActivationRevocationAuthorityIssuanceService::AUTHORITIES,
            $targetId,
        );
    }
}
