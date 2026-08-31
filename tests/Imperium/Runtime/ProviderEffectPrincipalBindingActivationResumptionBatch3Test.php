<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalFixtureStore;

final class ProviderEffectPrincipalBindingActivationResumptionBatch3Test
    extends ProviderEffectPrincipalBindingActivationResumptionBatch2Test
{
    public function testCompleteAggregateReconstructsDeterministicallyWithoutWriting(): void
    {
        $f = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);
        $this->storeChain($store, $f);
        $before = $this->fileDigests();
        $reconstructor = new ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor($this->root);

        $first = $this->reconstruct($reconstructor, $f);
        $second = $this->reconstruct($reconstructor, $f);

        self::assertSame('READY_OFFLINE_ACTIVATION_INPUT', $first['classification']);
        self::assertSame([], $first['reasons']);
        self::assertSame($first, $second);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first['proof_digest']);
        self::assertTrue($first['read_only']);
        foreach ([
            'fixture_created',
            'fixture_repaired',
            'resolution_admission_created',
            'activation_input_created',
            'activation_winner_created',
            'activation_authority_issued',
            'activation_authority_consumed',
            'principal_activated',
            'provider_binding_activated',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_io_started',
            'retry_authority_created',
        ] as $nonAuthority) {
            self::assertFalse($first[$nonAuthority], $nonAuthority);
        }
        self::assertSame($before, $this->fileDigests());
    }

    public function testAbsentAndCorruptAggregatesClassifyExactlyWithoutRepair(): void
    {
        $f = $this->fixtures();
        $reconstructor = new ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor($this->root);
        self::assertSame('INCOMPLETE', $this->reconstruct($reconstructor, $f)['classification']);

        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);
        $store->putResolutionAdmission(
            $f['admission'], $f['production'], $f['decision'], $f['attestation'],
            $f['assurance'], $f['boundary'], $f['at'],
        );
        self::assertSame('INCOMPLETE', $this->reconstruct($reconstructor, $f)['classification']);

        $this->storeInput($store, $f);
        $inputPath = $this->root.'/'
            .ProviderExecutorPrincipalActivationCanonicalFixtureStore::ACTIVATION_INPUTS
            .'/'.$f['input']['input_id'].'.json';
        file_put_contents($inputPath, '{}');

        $before = $this->fileDigests();
        $result = $this->reconstruct($reconstructor, $f);
        self::assertSame('CONFLICTED', $result['classification']);
        self::assertSame($before, $this->fileDigests());
        self::assertFalse($result['fixture_repaired']);
    }

    public function testExpiredRevokedChangedAndSecretBearingChainsRefuse(): void
    {
        $f = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);
        $this->storeChain($store, $f);
        $reconstructor = new ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor($this->root);

        foreach (['expired', 'revoked', 'changed'] as $case) {
            $candidate = $f;
            if ('expired' === $case) {
                $candidate['at'] = new \DateTimeImmutable('2026-08-31T12:11:00+00:00');
            } elseif ('revoked' === $case) {
                $candidate['decision']['validity']['revocation_reference'] = self::referenceRecord('revocation-1');
                $candidate['decision'] = self::seal($candidate['decision']);
            } else {
                $candidate['decision']['actor']['binding_id'] = 'changed-binding';
                $candidate['decision'] = self::seal($candidate['decision']);
            }
            self::assertSame('REFUSED', $this->reconstruct($reconstructor, $candidate)['classification'], $case);
        }

        $secretInput = $f['input'];
        $secretInput['activation_target']['credential_secret'] = 'forbidden';
        $secretInput = self::seal($secretInput);
        $inputPath = $this->root.'/'
            .ProviderExecutorPrincipalActivationCanonicalFixtureStore::ACTIVATION_INPUTS
            .'/'.$f['input']['input_id'].'.json';
        file_put_contents($inputPath, CanonicalJson::encode($secretInput));

        self::assertSame('REFUSED', $this->reconstruct($reconstructor, $f)['classification']);
    }

    public function testSameRootContentionLeavesExactWinnerReconstructable(): void
    {
        $f = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);
        $store->putResolutionAdmission(
            $f['admission'], $f['production'], $f['decision'], $f['attestation'],
            $f['assurance'], $f['boundary'], $f['at'],
        );

        $contender = $f['admission'];
        $contender['admitted_at'] = '2026-08-31T12:00:01+00:00';
        $contender = self::seal($contender);
        try {
            $store->putResolutionAdmission(
                $contender, $f['production'], $f['decision'], $f['attestation'],
                $f['assurance'], $f['boundary'], $f['at'],
            );
            self::fail('Changed same-root contender was accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PST111_IMMUTABLE_RECORD_CONFLICT', $exception->getMessage());
        }

        $this->storeInput($store, $f);
        $result = $this->reconstruct(
            new ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor($this->root),
            $f,
        );
        self::assertSame('READY_OFFLINE_ACTIVATION_INPUT', $result['classification']);
        self::assertSame(
            $f['admission']['record_digest'],
            $result['chain']['resolution_admission']['digest'],
        );
    }

    public function testInvalidIdentifierRefusesWithoutFilesystemMutation(): void
    {
        $f = $this->fixtures();
        $before = $this->fileDigests();
        $result = (new ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor($this->root))
            ->reconstruct(
                '../admission',
                $f['input']['input_id'],
                $f['production'],
                $f['decision'],
                $f['attestation'],
                $f['assurance'],
                $f['boundary'],
                $f['at'],
            );

        self::assertSame('REFUSED', $result['classification']);
        self::assertSame(['IDENTIFIER_INVALID'], $result['reasons']);
        self::assertSame($before, $this->fileDigests());
    }

    public function testReconstructorHasNoResolverProducerConsumerOrProviderPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor.php',
        );

        foreach ([
            'CredentialCapability',
            'EnvironmentCredentialBroker',
            'AgentMailEmailTransport',
            'ProviderExecutorPrincipalActivationService',
            'AuthorityConsumptionStore',
            'public function resolve',
            'public function issue',
            'public function consume',
            'public function activate',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesCanonicalActivationEntryPointNext(): void
    {
        $doc = $this->document('docs/provider-effect-principal-binding-activation-resumption-batch-3-reconstruction.md');
        $handoff = $this->document('docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-3-complete.md');

        foreach ([
            'RESUMPTION_BATCH_3_READ_ONLY_AGGREGATE_RECONSTRUCTION_PROOF_COMPLETE',
            'complete, absent, corrupt, expired, revoked and changed-evidence',
            'deterministic proof digest',
            'same-root contention',
            'secret exclusion',
            'read-only',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }
        foreach ([
            'Only Provider Effect Principal and Binding Activation Resumption Batch 4',
            'canonical activation entry point',
            'single atomic winner',
            'may not activate a provider binding',
            'may not handle a credential or capability',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'approximately three batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function storeChain(ProviderExecutorPrincipalActivationCanonicalFixtureStore $store, array $f): void
    {
        $store->putResolutionAdmission(
            $f['admission'], $f['production'], $f['decision'], $f['attestation'],
            $f['assurance'], $f['boundary'], $f['at'],
        );
        $this->storeInput($store, $f);
    }

    private function storeInput(ProviderExecutorPrincipalActivationCanonicalFixtureStore $store, array $f): void
    {
        $store->putActivationInput(
            $f['input'], $f['admission'], $f['production'], $f['decision'],
            $f['attestation'], $f['assurance'], $f['boundary'], $f['at'],
        );
    }

    private function reconstruct(
        ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor $reconstructor,
        array $f,
    ): array {
        return $reconstructor->reconstruct(
            $f['admission']['resolution_admission_id'],
            $f['input']['input_id'],
            $f['production'],
            $f['decision'],
            $f['attestation'],
            $f['assurance'],
            $f['boundary'],
            $f['at'],
        );
    }

    private function fileDigests(): array
    {
        if (!is_dir($this->root)) {
            return [];
        }
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
