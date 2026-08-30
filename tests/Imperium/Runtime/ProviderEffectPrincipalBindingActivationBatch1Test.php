<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationProductionInterruptionProofService;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationService;

final class ProviderEffectPrincipalBindingActivationBatch1Test extends ProviderExecutionEffectReadinessBatch7Test
{
    public function testOneCombinedRecordConsumesAuthorityAndActivatesExactGeneration(): void
    {
        $fixtures = $this->fixtures();
        $service = new ProviderExecutorPrincipalActivationService($this->root);

        $activation = $service->activate(
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt'],
        );

        self::assertSame('ACTIVE', $activation['status']);
        self::assertTrue($activation['consumed_activation_authority']['consumed']);
        self::assertFalse(
            $activation['consumed_activation_authority']['continuing_authority'],
        );
        self::assertSame(
            $fixtures['decision']['activation_authority']['authority_id'],
            $activation['consumed_activation_authority']['id'],
        );
        self::assertSame(
            $fixtures['attestation']['principal'],
            $activation['principal'],
        );
        self::assertSame(
            $activation,
            $service->reconstruct(
                $activation['principal_activation_id'],
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
            ),
        );
    }

    public function testExactReplayConvergesAndChangedDecisionConflicts(): void
    {
        $fixtures = $this->fixtures();
        $left = new ProviderExecutorPrincipalActivationService($this->root);
        $right = new ProviderExecutorPrincipalActivationService($this->root);

        $winner = $left->activate(
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt'],
        );
        self::assertSame(
            $winner,
            $right->activate(
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
            ),
        );

        $changed = $fixtures['decision'];
        $changed['rationale'] = 'Changed valid authority basis.';
        $changed = self::seal($changed);

        try {
            $right->activate(
                $changed,
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
            );
            self::fail('Changed decision reused an activated principal generation.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                $exception->getMessage(),
            );
        }
    }

    public function testBeforeAndAfterCommitCutsExposeNoConsumptionOnlyState(): void
    {
        $fixtures = $this->fixtures();
        $proof = new ProviderExecutorPrincipalActivationProductionInterruptionProofService(
            $this->root,
        );
        $service = new ProviderExecutorPrincipalActivationService($this->root);
        $activationId = 'principal-activation-'.hash('sha256', implode(':', [
            $fixtures['decision']['instance_id'],
            $fixtures['attestation']['principal']['principal_id'],
            (string) $fixtures['attestation']['principal']['generation'],
            $fixtures['attestation']['principal']['process_boundary_id'],
        ]));

        try {
            $proof->activate(
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
                ProviderExecutorPrincipalActivationProductionInterruptionProofService::CUT_BEFORE_COMBINED_COMMIT,
            );
            self::fail('Before-commit cut did not interrupt.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'PPB110_INTERRUPTED_BEFORE_COMBINED_ACTIVATION_COMMIT',
                $exception->getMessage(),
            );
        }

        try {
            $service->reconstruct(
                $activationId,
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
            );
            self::fail('Before-commit cut left an activation.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PST112_IMMUTABLE_RECORD_ABSENT', $exception->getMessage());
        }

        try {
            $proof->activate(
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
                ProviderExecutorPrincipalActivationProductionInterruptionProofService::CUT_AFTER_COMBINED_COMMIT,
            );
            self::fail('After-commit cut did not interrupt.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'PPB111_INTERRUPTED_AFTER_COMBINED_ACTIVATION_COMMIT',
                $exception->getMessage(),
            );
        }

        self::assertSame(
            'ACTIVE',
            $service->reconstruct(
                $activationId,
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt'],
            )['status'],
        );
    }

    public function testExpiredRevokedRefusedOrWrongGenerationDecisionCannotActivate(): void
    {
        $fixtures = $this->fixtures();
        $service = new ProviderExecutorPrincipalActivationService($this->root);

        $cases = [];

        $expired = $fixtures['decision'];
        $cases[] = [$expired, $fixtures['activatedAt']->modify('+11 minutes')];

        $revoked = $fixtures['decision'];
        $revoked['validity']['revocation_reference'] = [
            'id' => 'revocation-1',
            'digest' => str_repeat('b', 64),
            'schema' => 'imperium.test.revocation/v1',
        ];
        $cases[] = [self::seal($revoked), $fixtures['activatedAt']];

        $wrongGeneration = $fixtures['decision'];
        $wrongGeneration['scope']['principal_generation'] = 2;
        $cases[] = [self::seal($wrongGeneration), $fixtures['activatedAt']];

        $refused = $fixtures['decision'];
        $refused['disposition'] = 'REFUSED';
        $refused['activation_authority'] = null;
        $cases[] = [self::seal($refused), $fixtures['activatedAt']];

        foreach ($cases as [$decision, $at]) {
            try {
                $service->activate(
                    $decision,
                    $fixtures['attestation'],
                    $fixtures['assurance'],
                    $fixtures['boundary'],
                    $at,
                );
                self::fail('Ineligible decision activated the principal.');
            } catch (\RuntimeException $exception) {
                self::assertContains(
                    $exception->getMessage(),
                    [
                        'PEA700_ACTIVATION_DECISION_INVALID',
                        'PPB100_PRINCIPAL_ACTIVATION_NOT_AUTHORIZED',
                    ],
                );
            }
        }
    }

    public function testProductionTransitionHasNoBindingCredentialProviderOrIoDependency(): void
    {
        $root = dirname(__DIR__, 3);
        $source = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationService.php',
        );

        foreach ([
            'AuthorityConsumptionStore',
            'ProviderBindingActivation',
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthority',
            'external_io',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesOnlyPrincipalProductionNext(): void
    {
        $doc = $this->document(
            'docs/provider-effect-principal-binding-activation-batch-1.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-batch-1-complete.md',
        );

        foreach ([
            'BATCH_1_ATOMIC_PRINCIPAL_ACTIVATION_PRODUCTION_COMPLETE',
            'one immutable combined consumption-and-activation winner',
            'no consumption-only durable state',
            'principal generation is ACTIVE',
            'provider binding remains BOUND_INACTIVE',
            'UNKNOWN_REPLAY_PROHIBITED',
            'Only Batch 2 may next be considered',
            'principal-production lifecycle terminal audit',
            'Iron Gate and Lazaretto remain closed',
        ] as $finding) {
            self::assertNotFalse(stripos($doc.' '.$handoff, $finding), $finding);
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
