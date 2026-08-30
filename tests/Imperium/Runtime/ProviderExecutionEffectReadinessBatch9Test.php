<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationAggregateReconstructor;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationFixtureStore;

final class ProviderExecutionEffectReadinessBatch9Test extends ProviderExecutionEffectReadinessBatch8Test
{
    public function testCompleteChainReconstructsEligibleWithoutWriting(): void
    {
        $fixtures = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationFixtureStore($this->root);
        $store->putDecision(
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['decidedAt'],
        );
        $store->putActivation(
            $fixtures['activation'],
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt']->modify('+1 minute'),
        );
        $before = $this->fileDigests();

        $result = (new ProviderExecutorPrincipalActivationAggregateReconstructor(
            $this->root,
        ))->reconstruct(
            $fixtures['decision']['decision_id'],
            $fixtures['activation']['principal_activation_id'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt']->modify('+1 minute'),
        );

        self::assertSame('ELIGIBLE_OFFLINE_EVIDENCE', $result['classification']);
        self::assertSame([], $result['reasons']);
        self::assertSame($before, $this->fileDigests());
        self::assertTrue($result['read_only']);
        foreach ([
            'fixture_created',
            'fixture_repaired',
            'principal_activated',
            'principal_reactivated',
            'activation_authority_created',
            'activation_authority_consumed',
            'execution_authority_created',
            'execution_authority_consumed',
            'provider_binding_activated',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_io_performed',
            'retry_authorized',
        ] as $field) {
            self::assertFalse($result[$field], $field);
        }
    }

    public function testAbsentCorruptAndRefusedDecisionChainsClassifyExactly(): void
    {
        $fixtures = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationFixtureStore($this->root);
        $reconstructor = new ProviderExecutorPrincipalActivationAggregateReconstructor(
            $this->root,
        );

        $store->putDecision(
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['decidedAt'],
        );
        self::assertSame(
            'INCOMPLETE',
            $reconstructor->reconstruct(
                $fixtures['decision']['decision_id'],
                $fixtures['activation']['principal_activation_id'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            )['classification'],
        );

        $activationPath = $this->root.'/'
            .ProviderExecutorPrincipalActivationFixtureStore::ACTIVATIONS
            .'/'.$fixtures['activation']['principal_activation_id'].'.json';
        mkdir(dirname($activationPath), 0770, true);
        file_put_contents($activationPath, '{}');
        self::assertSame(
            'CONFLICTED',
            $reconstructor->reconstruct(
                $fixtures['decision']['decision_id'],
                $fixtures['activation']['principal_activation_id'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            )['classification'],
        );

        unlink($activationPath);
        $decisionPath = $this->root.'/'
            .ProviderExecutorPrincipalActivationFixtureStore::DECISIONS
            .'/'.$fixtures['decision']['decision_id'].'.json';
        unlink($decisionPath);
        $refused = $fixtures['decision'];
        $refused['disposition'] = 'REFUSED';
        $refused['activation_authority'] = null;
        $refused = self::seal($refused);
        file_put_contents($decisionPath, CanonicalJson::encode($refused));
        self::assertSame(
            'REFUSED',
            $reconstructor->reconstruct(
                $refused['decision_id'],
                $fixtures['activation']['principal_activation_id'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            )['classification'],
        );
    }

    public function testValidExpiredActivationRefusesWithoutRuntimeMutation(): void
    {
        $fixtures = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationFixtureStore($this->root);
        $expired = $fixtures['activation'];
        $expired['status'] = 'EXPIRED';
        $expired = self::seal($expired);
        $afterExpiry = $fixtures['activatedAt']->modify('+11 minutes');

        $store->putDecision(
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['decidedAt'],
        );
        $store->putActivation(
            $expired,
            $fixtures['decision'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $afterExpiry,
        );
        $before = $this->fileDigests();

        $result = (new ProviderExecutorPrincipalActivationAggregateReconstructor(
            $this->root,
        ))->reconstruct(
            $fixtures['decision']['decision_id'],
            $expired['principal_activation_id'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $afterExpiry,
        );

        self::assertSame('REFUSED', $result['classification']);
        self::assertSame(['PRINCIPAL_ACTIVATION_NOT_ACTIVE'], $result['reasons']);
        self::assertSame($before, $this->fileDigests());
    }

    public function testInvalidIdentifierRefusesWithoutFilesystemMutation(): void
    {
        $fixtures = $this->fixtures();
        $before = $this->fileDigests();
        $result = (new ProviderExecutorPrincipalActivationAggregateReconstructor(
            $this->root,
        ))->reconstruct(
            '../decision',
            'activation',
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt'],
        );

        self::assertSame('REFUSED', $result['classification']);
        self::assertSame(['IDENTIFIER_INVALID'], $result['reasons']);
        self::assertSame($before, $this->fileDigests());
    }

    public function testReconstructorHasNoRuntimeProducerCredentialOrProviderDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationAggregateReconstructor.php',
        );

        foreach ([
            'AuthorityConsumptionStore',
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthorityIssuanceService',
            'IronGate',
            'Lazaretto',
            'public function activate',
            'public function issue',
            'public function consume',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationKeepsReconstructionOfflineAndRuntimeClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $doc = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/provider-execution-effect-readiness-principal-activation-reconstruction.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/provider-execution-effect-readiness-batch-9-complete.md',
            ),
        );

        foreach ([
            'BATCH_9_READ_ONLY_PRINCIPAL_ACTIVATION_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'ELIGIBLE_OFFLINE_EVIDENCE',
            'INCOMPLETE',
            'CONFLICTED',
            'REFUSED',
            'validates each existing artifact before reading the next',
            'refused decision is not masked by absent activation evidence',
            'principal remains inert',
            'provider binding remains inactive',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Batch 10 may next be considered',
            'terminal adversarial audit',
            'no live adoption',
            'no provider was invoked',
            'Iron Gate',
            'Lazaretto',
            'approximately one batch',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function fileDigests(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[$file->getPathname()] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }
}
