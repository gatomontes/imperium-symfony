<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativePrincipal, NativeConsumer, NativeReconstructor, NativeBindingReader, TransitionContract, TransitionStore};

require_once __DIR__.'/NativeTransitionBatch5Test.php';

class NativeTransitionBatch6Test extends NativeTransitionBatch5Test
{
    public function testResealedCreationCannotPredateItsOriginalExecutorActivation(): void
    {
        [$p, $a, $at] = $this->nativeInputs();
        // A valid earlier competence time does not make the later executor activation retroactive.
        $p['constituted_at'] = $at - 1; $p = NativeState::seal($p);
        $this->rewrite('principals', $p['principal_version_id'], $p);
        $event = $this->state->get('activations', $p['principal_version_id']);
        $event['at'] = $at - 1; $event['principal'] = NativeState::ref($p, 'principal_version_id');
        $this->rewrite('activations', $p['principal_version_id'], NativeState::seal($event));
        $service = new \App\Imperium\Runtime\ProviderTransition\NativeSuccessor($this->state, static fn () => $at);
        $s = $service->create($p['principal_version_id'], NativeState::ref($a, 'principal_activation_id'));
        $s['at'] = $at - 1; $s['decision']['at'] = $at - 1; $s['decision'] = NativeState::seal($s['decision']);
        $s['successor']['source_decision'] = NativeState::ref($s['decision'], 'decision_id');
        $s['successor']['consumed_activation_authority'] = [...NativeState::ref($s['decision'], 'decision_id'),
            'consumed_at' => gmdate(DATE_ATOM, $at - 1), 'consumed' => true, 'continuing_authority' => false];
        $s['successor']['activated_at'] = gmdate(DATE_ATOM, $at - 1);
        $s['successor']['validity']['effective_at'] = gmdate(DATE_ATOM, $at - 1);
        $s['successor'] = NativeState::seal($s['successor']);
        $s['creation_winner']['source_decision'] = NativeState::ref($s['decision'], 'decision_id');
        $s['creation_winner']['successor'] = NativeState::ref($s['successor'], 'successor_id');
        $s['creation_winner'] = NativeState::seal($s['creation_winner']);
        $this->rewrite('successors', $s['successor']['successor_id'], $s);
        try {
            $service->load($s['successor']['successor_id'], $at);
            self::fail('Resealed creation before original executor activation must refuse');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('ACTIVATION', $e->getMessage());
        }
    }

    public function testBatchSixDocumentsPreserveTerminalSequencingAndClosedBoundaries(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['docs/handoffs/executable-atomic-transition-native-integration-remediation-batch-6-complete.md',
            'docs/executable-atomic-transition-native-integration-remediation-implementation-v1.md',
            'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md'] as $path) {
            self::assertStringContainsString('NATIVE_INTEGRATION_BATCH_6_INDEPENDENT_RECONSTRUCTION_COMPLETE', file_get_contents($root.'/'.$path));
        }
        $handoff = file_get_contents($root.'/docs/handoffs/executable-atomic-transition-native-integration-remediation-batch-6-complete.md');
        foreach (['clean locally merged Batch 6 main', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED',
            'COMMITTED_NOT_CURRENT', 'Iron Gate', 'Lazaretto'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    public function testApplicationEntrypointHasNoCallerRootClockOrKeyAndRedactsInvalidInput(): void
    {
        $command = new \App\Command\ImperiumNativeProviderTransitionCommand($this->root);
        self::assertSame('imperium:provider-transition:execute', $command->getName());
        self::assertSame(['authority-id'], array_keys($command->getDefinition()->getArguments()));
        self::assertSame([], $command->getDefinition()->getOptions());
        $tester = new \Symfony\Component\Console\Tester\CommandTester($command);
        self::assertSame(1, $tester->execute(['authority-id' => 'SYNTHETIC_SECRET/../invalid']));
        self::assertSame('NIR_IDENTIFIER', trim($tester->getDisplay()));
        self::assertSame(1, $tester->execute(['authority-id' => 'missing-authority']));
        self::assertSame('NIR_AUTHORITY_ABSENT', trim($tester->getDisplay()));
        self::assertSame([], $this->state->ids('journals'));
    }

    public function testInterruptedAttemptRemainsUnknownAfterRootExpires(): void
    {
        [$id, $at] = $this->readyTransition();
        $times = [$at, $at, $at + 601];
        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativeConsumer($this->state, static function () use (&$times): int { return array_shift($times); }))->execute($id));
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => (new NativeConsumer($this->state, static fn () => $at + 601))->execute($id));
    }

    public function testRenamedAuthorityCannotSatisfyStoredReceiptLineage(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $root = NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send');
        $this->rewrite('authorities', 'renamed-authority', $this->state->get('authorities', $id));
        $c = $this->state->get('transitions', $root);
        $c['authority_id'] = 'renamed-authority';
        $this->rewriteCommit($root, $c);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    public function testIndependentReceiptReconstructionIsReadOnlyAndSeparatesExpiry(): void
    {
        [$id, $at] = $this->readyTransition(true);
        $live = (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $before = $this->fingerprint();
        $reader = new NativeReconstructor($this->state);
        $proof = $reader->reconstruct('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame('COMMITTED', $proof['classification']);
        self::assertSame($live['receipt'], $proof['receipt']); self::assertFalse($proof['execution_authority']);
        $expired = $reader->reconstruct('imperium-test', 'provider-binding', 'email.send', $at + 601);
        self::assertSame('COMMITTED_NOT_CURRENT', $expired['classification']);
        self::assertSame($proof['receipt'], $expired['receipt']); self::assertFalse($expired['retry_authorized']);
        self::assertSame($before, $this->fingerprint());
        $source = file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeReconstructor.php');
        foreach (['NativeAdmission', 'NativeConsumer', 'NativeBindingReader', '->put(', '->locked(', 'file_put_contents', 'mkdir('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testRootNamesTheExactExecutionBasisAndRejectsResealedNativeActivation(): void
    {
        [$id, $at, $p] = $this->readyTransition();
        $basis = $p['root_act']['act']['execution_basis'];
        self::assertNotNull($basis);
        $activation = $this->state->source('activation', $basis['activation']);
        $activation['principal']['generation'] += 1;
        $this->write(NativeState::SOURCES['activation'].'/'.$basis['activation']['id'].'.json', NativeState::seal($activation));
        $this->fails('NIR_SOURCE_CHANGED', fn () => (new NativeConsumer($this->state, static fn () => $at))->execute($id));
        self::assertSame([], $this->state->ids('journals'));
    }

    public function testEveryResealedWriteSetMemberRejectsUnexpectedSecretMaterial(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $root = NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send');
        $original = $this->state->get('transitions', $root);
        foreach (TransitionContract::WRITE_SET as $name) {
            $c = $original; $c['records'][$name]['credential_secret'] = 'SYNTHETIC_FORBIDDEN_MARKER';
            $c['records'][$name] = NativeState::seal($c['records'][$name]);
            $this->rewriteCommit($root, $c);
            $proof = (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at);
            self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $proof['classification'], $name);
            self::assertStringNotContainsString('SYNTHETIC_FORBIDDEN_MARKER', json_encode($proof, JSON_THROW_ON_ERROR));
        }
    }

    public function testRelabeledAdmissionCannotBeResealedIntoCanonicalAcceptance(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $root = NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send');
        $c = $this->state->get('transitions', $root);
        $c['records']['v3_admission']['schema'] = 'imperium.provider-successor-executable-admission/v3';
        $c['records']['v3_admission'] = NativeState::seal($c['records']['v3_admission']);
        $this->rewriteCommit($root, $c);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    public function testMissingRetirementOrOrphanRetirementIsNeverSuccessOrEmpty(): void
    {
        [$id, $at] = $this->readyTransition(true);
        $root = NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send');
        $path = $this->root.'/var/imperium/runtime/legacy-provider-transitions/old-store';
        $store = new TransitionStore($path);
        $store->locked(fn () => $store->put('retirement', ['root' => $root]));
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        unlink($path.'/retirement.json');
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        unlink($path.'/retirement.json');
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    public function testRootActIdentityCannotBeReusedAndConstitutionCannotPredateItsGrant(): void
    {
        $p = $this->activate();
        $a = $this->act; $a['act_id'] = 'activate-test';
        $this->fails('NIR_ROOT_ACT_ID_REUSED', fn () => (new NativePrincipal($this->state, static fn () => 100))->constitute($this->sign($a)));
        $p['constituted_at'] = 1; $p = NativeState::seal($p);
        $this->rewrite('principals', $p['principal_version_id'], $p);
        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativePrincipal($this->state))->load($p['principal_version_id'], 100));
    }

    public function testRevocationPreservesHistoricalReceiptButRootRevocationFailsClosed(): void
    {
        [$id, $at, $p] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $act = $this->act; $act['action'] = 'REVOKE'; $act['act_id'] = 'audited-revocation';
        (new NativePrincipal($this->state, static fn () => $at + 1))->lifecycle($p['principal_version_id'], $this->sign($act));
        $reader = new NativeReconstructor($this->state);
        self::assertSame('COMMITTED_NOT_CURRENT', $reader->reconstruct('imperium-test', 'provider-binding', 'email.send', $at + 1)['classification']);
        $this->anchor['revoked'] = true; $this->write(NativeState::TRUST.'/identity.json', $this->anchor);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $reader->reconstruct('imperium-test', 'provider-binding', 'email.send', $at + 1)['classification']);
    }

    private function rewriteCommit(string $root, array $c): void
    {
        $c['records']['winner_target']['records_digest'] = TransitionContract::digest(array_slice($c['records'], 0, 5, true));
        $c['records']['winner_target'] = NativeState::seal($c['records']['winner_target']);
        $c['records']['receipt_target']['winner'] = NativeState::ref($c['records']['winner_target'], 'winner_id');
        $c['records']['receipt_target'] = NativeState::seal($c['records']['receipt_target']);
        $this->rewrite('transitions', $root, NativeState::seal($c));
    }

    private function rewrite(string $kind, string $id, array $body): void
    {
        $this->write(NativeState::DIRECTORY.'/'.$kind.'/'.$id.'/commit.json', ['body' => $body, 'digest' => TransitionContract::digest($body)]);
    }

    private function fingerprint(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[$file->getPathname()] = [hash_file('sha256', $file->getPathname()), $file->getMTime()];
        }
        ksort($files); return $files;
    }
}
