<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationFixtureInterruptionProofService;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationFixtureStore;

final class ProviderExecutionEffectReadinessBatch8Test extends ProviderExecutionEffectReadinessBatch7Test
{
    public function testBothFixturePathsHaveTruthfulBeforeAndAfterCommitRecovery(): void
    {
        $fixtures = $this->fixtures();
        $service = new ProviderExecutorPrincipalActivationFixtureInterruptionProofService(
            $this->root,
        );
        $cases = [
            [
                fn (?string $cut): array => $service->putDecision(
                    $fixtures['decision'],
                    $fixtures['attestation'],
                    $fixtures['assurance'],
                    $fixtures['boundary'],
                    $fixtures['decidedAt'],
                    $cut,
                ),
                $this->root.'/'.ProviderExecutorPrincipalActivationFixtureStore::DECISIONS
                    .'/*.json',
                $fixtures['decision'],
            ],
            [
                fn (?string $cut): array => $service->putActivation(
                    $fixtures['activation'],
                    $fixtures['decision'],
                    $fixtures['attestation'],
                    $fixtures['assurance'],
                    $fixtures['boundary'],
                    $fixtures['activatedAt']->modify('+1 minute'),
                    $cut,
                ),
                $this->root.'/'.ProviderExecutorPrincipalActivationFixtureStore::ACTIVATIONS
                    .'/*.json',
                $fixtures['activation'],
            ],
        ];

        foreach ($cases as $index => [$put, $glob, $expected]) {
            try {
                $put(
                    ProviderExecutorPrincipalActivationFixtureInterruptionProofService::CUT_BEFORE_COMMIT,
                );
                self::fail('Before-commit cut did not interrupt at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertSame(
                    'PEA800_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT',
                    $exception->getMessage(),
                );
            }
            self::assertSame([], glob($glob) ?: []);

            try {
                $put(
                    ProviderExecutorPrincipalActivationFixtureInterruptionProofService::CUT_AFTER_COMMIT,
                );
                self::fail('After-commit cut did not interrupt at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertSame(
                    'PEA801_INTERRUPTED_AFTER_IMMUTABLE_COMMIT',
                    $exception->getMessage(),
                );
            }
            self::assertCount(1, glob($glob) ?: []);
            self::assertSame($expected, $put(null));
        }
    }

    public function testTwoSameRootServicesConvergeForBothExactFixtures(): void
    {
        $fixtures = $this->fixtures();
        $left = new ProviderExecutorPrincipalActivationFixtureInterruptionProofService(
            $this->root,
        );
        $right = new ProviderExecutorPrincipalActivationFixtureInterruptionProofService(
            $this->root,
        );

        foreach ([$left, $right] as $service) {
            self::assertSame(
                $fixtures['decision'],
                $service->putDecision(
                    $fixtures['decision'],
                    $fixtures['attestation'],
                    $fixtures['assurance'],
                    $fixtures['boundary'],
                    $fixtures['decidedAt'],
                ),
            );
            self::assertSame(
                $fixtures['activation'],
                $service->putActivation(
                    $fixtures['activation'],
                    $fixtures['decision'],
                    $fixtures['attestation'],
                    $fixtures['assurance'],
                    $fixtures['boundary'],
                    $fixtures['activatedAt']->modify('+1 minute'),
                ),
            );
        }
    }

    public function testChangedValidDecisionAndActivationEvidenceConflictImmutably(): void
    {
        $fixtures = $this->fixtures();
        $service = new ProviderExecutorPrincipalActivationFixtureInterruptionProofService(
            $this->root,
        );
        $service->putDecision(
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['decidedAt'],
        );
        $service->putActivation(
            $fixtures['activation'],
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt']->modify('+1 minute'),
        );

        $changedDecision = $fixtures['decision'];
        $changedDecision['rationale'] = 'Changed but otherwise valid decision evidence.';
        $changedDecision = self::seal($changedDecision);

        try {
            $service->putDecision(
                $changedDecision,
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['decidedAt'],
            );
            self::fail('Changed decision evidence reused an immutable identity.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PST111_IMMUTABLE_RECORD_CONFLICT', $exception->getMessage());
        }

        $changedActivation = $fixtures['activation'];
        $changedActivation['source_decision'] = self::reference(
            $changedDecision,
            'decision_id',
        );
        $changedActivation['consumed_activation_authority']['digest'] =
            $changedDecision['record_digest'];
        $changedActivation = self::seal($changedActivation);

        try {
            $service->putActivation(
                $changedActivation,
                $changedDecision,
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            );
            self::fail('Changed activation evidence reused an immutable identity.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PST111_IMMUTABLE_RECORD_CONFLICT', $exception->getMessage());
        }
    }

    public function testInterruptionProofHasNoRuntimeAuthorityCredentialOrProviderDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationFixtureInterruptionProofService.php',
        );

        foreach ([
            'AuthorityConsumptionStore',
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthority',
            'IronGate',
            'Lazaretto',
            'public function activate',
            'public function issue',
            'public function consume',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationKeepsProofOfflineAndPrincipalInert(): void
    {
        $root = dirname(__DIR__, 3);
        $doc = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/provider-execution-effect-readiness-principal-activation-interruption-proof.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/provider-execution-effect-readiness-batch-8-complete.md',
            ),
        );

        foreach ([
            'BATCH_8_OFFLINE_PRINCIPAL_ACTIVATION_FIXTURE_INTERRUPTION_PROVED',
            'before immutable commit leaves no record',
            'after immutable commit leaves one winner',
            'exact replay converges',
            'changed valid evidence conflicts',
            'same-root services converge',
            'principal remains inert',
            'provider binding remains inactive',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Batch 9 may next be considered',
            'read-only aggregate reconstruction',
            'ELIGIBLE_OFFLINE_EVIDENCE',
            'INCOMPLETE',
            'CONFLICTED',
            'REFUSED',
            'no provider was invoked',
            'Iron Gate',
            'Lazaretto',
            'approximately two batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
