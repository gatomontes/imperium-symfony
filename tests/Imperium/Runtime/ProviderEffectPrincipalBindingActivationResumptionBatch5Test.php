<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalFixtureStore;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationService;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalCanonicalActivationService;

final class ProviderEffectPrincipalBindingActivationResumptionBatch5Test
    extends ProviderEffectPrincipalBindingActivationResumptionBatch4Test
{
    public function testChangedRootAndChangedEvidenceRefuseWithoutConsumptionOnlyState(): void
    {
        $f = $this->canonicalFixtures();
        $this->storeCanonicalChain($f);
        $path = $this->root.'/'
            .ProviderExecutorPrincipalActivationCanonicalFixtureStore::ACTIVATION_INPUTS
            .'/'.$f['input']['input_id'].'.json';
        $changed = $f['input'];
        $changed['replay_contention_root']['root_id'] = 'substituted-root';
        $changed = self::seal($changed);
        file_put_contents($path, CanonicalJson::encode($changed));

        $this->expectCanonicalRefusal($f);

        self::assertSame([], $this->activationFiles());
    }

    public function testGenerationSubstitutionRefusesBeforeWinner(): void
    {
        $f = $this->canonicalFixtures();
        $this->storeCanonicalChain($f);
        $candidate = $f;
        $candidate['attestation']['principal']['generation'] = 2;
        $candidate['attestation'] = self::seal($candidate['attestation']);

        $this->expectCanonicalRefusal($candidate);
        self::assertSame([], $this->activationFiles());
    }

    public function testBindingSubstitutionRefusesBeforeWinner(): void
    {
        $f = $this->canonicalFixtures();
        $this->storeCanonicalChain($f);
        $candidate = $f;
        $candidate['attestation']['principal']['binding_id'] = 'substituted-binding';
        $candidate['attestation'] = self::seal($candidate['attestation']);

        $this->expectCanonicalRefusal($candidate);
        self::assertSame([], $this->activationFiles());
    }

    public function testCrashBoundariesAndProcessLocalReconstructionConvergeExactly(): void
    {
        $f = $this->canonicalFixtures();
        $service = new ProviderExecutorPrincipalCanonicalActivationService($this->root);

        $this->expectCanonicalRefusal($f);
        self::assertSame([], $this->activationFiles());

        $this->storeCanonicalChain($f);
        $winner = $this->activate($service, $f);
        unset($service);

        $reconstructed = $this->activate(
            new ProviderExecutorPrincipalCanonicalActivationService($this->root),
            $f,
        );

        self::assertSame($winner, $reconstructed);
        self::assertCount(1, $this->activationFiles());
        self::assertTrue($winner['consumed_activation_authority']['consumed']);
        self::assertFalse($winner['consumed_activation_authority']['continuing_authority']);
    }

    public function testCombinedWinnerExcludesSecretsAndDurableDownstreamAuthority(): void
    {
        $f = $this->canonicalFixtures();
        $this->storeCanonicalChain($f);
        $winner = $this->activate(
            new ProviderExecutorPrincipalCanonicalActivationService($this->root),
            $f,
        );
        $encoded = CanonicalJson::encode($winner);

        foreach ([
            'credential_secret',
            'credential_bytes',
            'capability_token',
            'environment_variable',
            'retry_authority',
            'provider_binding_activation',
            'provider_invocation',
            'external_io',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded);
        }

        self::assertSame('ACTIVE', $winner['status']);
        self::assertArrayNotHasKey('provider_binding', $winner);
        self::assertArrayNotHasKey('credential', $winner);
        self::assertArrayNotHasKey('provider_effect', $winner);
        self::assertCount(1, $this->activationFiles());
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $doc = $this->document(
            'docs/provider-effect-principal-binding-activation-resumption-batch-5-adversarial-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-5-complete.md',
        );

        foreach ([
            'RESUMPTION_BATCH_5_ADVERSARIAL_AUDIT_COMPLETE',
            'contention convergence',
            'changed-root and changed-evidence refusal',
            'expiry and revocation refusal',
            'crash before the combined commit',
            'crash after the combined commit',
            'process-local material disappears',
            'generation and binding substitution refusal',
            'secret exclusion',
            'no consumption-only state',
            'provider binding remains BOUND_INACTIVE',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Provider Effect Principal and Binding Activation Resumption Batch 6',
            'terminal audit',
            'may not activate a provider binding',
            'may not handle a credential or capability',
            'provider invocation',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'approximately one batch',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function expectCanonicalRefusal(array $f): void
    {
        try {
            $this->activate(
                new ProviderExecutorPrincipalCanonicalActivationService($this->root),
                $f,
            );
            self::fail('Adversarial candidate produced a principal activation winner.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PRA400_CANONICAL_ACTIVATION_NOT_READY', $exception->getMessage());
        }
    }

    private function activationFiles(): array
    {
        return glob(
            $this->root.'/'.ProviderExecutorPrincipalActivationService::ACTIVATIONS.'/*.json',
        ) ?: [];
    }
}
