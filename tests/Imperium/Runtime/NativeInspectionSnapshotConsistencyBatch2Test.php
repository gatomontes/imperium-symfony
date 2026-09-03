<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeInspectionSnapshot, NativeReconstructor, NativeState};

require_once __DIR__.'/NativeTransitionBatch4Test.php';

final class NativeInspectionSnapshotConsistencyBatch2Test extends NativeTransitionBatch4Test
{
    public function testStableInterpretationUsesOneOutermostObservationAndKeepsItsShape(): void
    {
        [, $at] = $this->readyTransition();
        $cuts = [];
        $reader = new NativeBindingReader($this->state, static function (string $cut, int $attempt) use (&$cuts): void {
            $cuts[] = [$cut, $attempt];
        });
        $before = $this->snapshotFiles();
        $result = $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame(['root', 'classification', 'descriptor', 'receipt', 'read_only',
            'provider_effect_permitted', 'retry_authorized', 'recovery'], array_keys($result));
        self::assertSame('BOUND_INACTIVE', $result['classification']);
        self::assertSame([['inspection.manifest_a', 1], ['inspection.before_manifest_b', 1]], $cuts);
        self::assertSame($before, $this->snapshotFiles());
    }

    public function testOneCrossingMutationCausesOneInternalRereadThenStableAcceptance(): void
    {
        [, $at] = $this->readyTransition();
        $path = $this->root.'/var/imperium/la-cortine/deterministic-execution-claims/observation-marker';
        $reader = new NativeBindingReader($this->state, function (string $cut, int $attempt) use ($path): void {
            if ('inspection.before_manifest_b' === $cut && 1 === $attempt) {
                if (!is_dir(dirname($path))) { mkdir(dirname($path), 0770, true); }
                file_put_contents($path, 'published-between-manifests');
            }
        });
        self::assertSame('BOUND_INACTIVE', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    public function testContinuousCrossingMutationRefusesAfterExactlyTwoAttempts(): void
    {
        [, $at] = $this->readyTransition();
        $path = $this->root.'/var/imperium/la-cortine/deterministic-execution-claims/continuous-marker';
        $attempts = [];
        $reader = new NativeBindingReader($this->state, function (string $cut, int $attempt) use ($path, &$attempts): void {
            if ('inspection.before_manifest_b' !== $cut) { return; }
            $attempts[] = $attempt;
            if (!is_dir(dirname($path))) { mkdir(dirname($path), 0770, true); }
            file_put_contents($path, 'attempt-'.$attempt);
        });
        $result = $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame([1, 2], $attempts);
        self::assertSame('INCOMPLETE', $result['classification']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $result['recovery']);
        self::assertFalse($result['provider_effect_permitted']);
        self::assertFalse($result['retry_authorized']);
    }

    public function testDirectReadAndReconstructionUseTheirExistingConservativeMappings(): void
    {
        [, $at] = $this->readyTransition();
        $path = $this->root.'/var/imperium/operator-root/transition-trust/churn';
        $checkpoint = function (string $cut, int $attempt) use ($path): void {
            if ('inspection.before_manifest_b' === $cut) { file_put_contents($path, 'attempt-'.$attempt); }
        };
        $reader = new NativeBindingReader($this->state, $checkpoint);
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $reader->read('imperium-test', 'provider-binding', 'email.send', $at));
        $proof = (new NativeReconstructor($this->state, $checkpoint))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame(['classification', 'receipt', 'read_only', 'execution_authority',
            'retry_authorized', 'provider_effect_started'], array_keys($proof));
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $proof['classification']);
        self::assertFalse($proof['execution_authority']);
        self::assertFalse($proof['provider_effect_started']);
    }

    public function testMutexFilesAreNotSemanticAndImplementationContainsNoLockAcquisition(): void
    {
        [, $at] = $this->readyTransition();
        $path = $this->root.'/var/imperium/runtime/native-provider-transition/inspection.lock';
        $reader = new NativeBindingReader($this->state, function (string $cut, int $attempt) use ($path): void {
            if ('inspection.before_manifest_b' === $cut) { file_put_contents($path, 'mutex-'.$attempt); }
        });
        self::assertSame('BOUND_INACTIVE', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        $source = file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeInspectionSnapshot.php');
        self::assertNotFalse($source);
        self::assertStringNotContainsString('AtomicTransition', $source);
        self::assertStringNotContainsString('flock(', $source);
        self::assertStringNotContainsString('file_put_contents(', $source);
        self::assertStringContainsString('MAX_ATTEMPTS = 2', $source);
    }

    private function snapshotFiles(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[$file->getPathname()] = hash_file('sha256', $file->getPathname());
        }
        ksort($files);
        return $files;
    }
}
