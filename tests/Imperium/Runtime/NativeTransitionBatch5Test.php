<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativePrincipal, NativeAuthority, NativeSuccessor, NativeBindingReader};

require_once __DIR__.'/NativeTransitionBatch4Test.php';

class NativeTransitionBatch5Test extends NativeTransitionBatch4Test
{
    public function testDifferentNativeSuccessorsContendInSeparateProcessesForOneOperation(): void
    {
        [$first, $at] = $this->readyTransition();
        [$p, $a] = $this->nativeInputs('-contender');
        $s = (new NativeSuccessor($this->state, static fn () => $at))->create($p['principal_version_id'], NativeState::ref($a, 'principal_activation_id'));
        $second = (new NativeAuthority($this->state, static fn () => $at))->issue($p['principal_version_id'], $s['successor']['successor_id'])['authority']['authority_id'];
        self::assertNotSame($first, $second);
        $go = $this->root.'/go';
        $left = $this->start('execute', $first, $at, ['ready' => $this->root.'/left', 'go' => $go]);
        $right = $this->start('execute', $second, $at, ['ready' => $this->root.'/right', 'go' => $go]);
        try {
            $this->until($this->root.'/left'); $this->until($this->root.'/right'); touch($go);
            $results = [$this->finish($left)[1], $this->finish($right)[1]]; sort($results);
            self::assertSame(['COMMITTED', 'NIR_ALREADY_COMMITTED_READ_ONLY_REPLAY'], $results);
            self::assertCount(1, $this->state->ids('transitions'));
        } finally { $this->stop($left); $this->stop($right); }
    }

    public function testNativeImmutableSourceWriterCannotCrossThePublicationLock(): void
    {
        [$id, $at] = $this->readyTransition();
        $held = $this->root.'/held'; $release = $this->root.'/release'; $blocked = $this->root.'/blocked';
        $consumer = $this->start('execute', $id, $at, ['hold_cut' => 'transitions.commit.before_publish', 'held' => $held, 'release' => $release]);
        $writer = null;
        try {
            $this->until($held);
            $writer = $this->start('generation', 'unused', $at + 1, ['blocked' => $blocked]);
            $this->until($blocked);
            self::assertFileDoesNotExist($this->root.'/'.NativeState::SOURCES['principal'].'/later-principal.json');
            touch($release);
            self::assertSame([0, 'COMMITTED'], $this->finish($consumer));
            self::assertSame([0, 'DONE'], $this->finish($writer));
            $reader = new NativeBindingReader($this->state);
            self::assertSame('BOUND_ACTIVE_FOR_EXACT_OPERATION', $reader->read('imperium-test', 'provider-binding', 'email.send', $at)['effective_status']);
            $this->fails('NIR_SOURCE_GENERATION_CHANGED', fn () => $reader->read('imperium-test', 'provider-binding', 'email.send', $at + 1));
        } finally { touch($release); $this->stop($consumer); $this->stop($writer); }
    }

    public function testEveryTransitionAndRetirementCutTerminatesARealProcess(): void
    {
        foreach (['journals.commit', 'legacy.retirement', 'transitions.commit'] as $family) {
            foreach (['before_open', 'before_publish', 'after_publish'] as $point) {
                $this->fresh(); [$id, $at] = $this->readyTransition(true);
                $child = $this->start('execute', $id, $at, ['exit_cut' => $family.'.'.$point]);
                self::assertSame([73, 'INTERRUPTED'], $this->finish($child), $family.'.'.$point);
                $reader = new NativeBindingReader($this->state);
                if ('transitions.commit.after_publish' === $family.'.'.$point) {
                    self::assertSame('BOUND_ACTIVE_FOR_EXACT_OPERATION', $reader->read('imperium-test', 'provider-binding', 'email.send', $at)['effective_status']);
                } else {
                    $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $reader->read('imperium-test', 'provider-binding', 'email.send', $at));
                }
                $retry = $this->start('execute', $id, $at, []);
                self::assertSame([0, 'transitions.commit.after_publish' === $family.'.'.$point ? 'NIR_ALREADY_COMMITTED_READ_ONLY_REPLAY' : 'UNKNOWN_REPLAY_PROHIBITED'], $this->finish($retry));
            }
        }
    }

    public function testEveryNativePrecursorPublicationCutTerminatesARealProcess(): void
    {
        foreach (['principals', 'activations', 'revocations', 'authorities', 'successors'] as $kind) {
            foreach (['before_open', 'before_publish', 'after_publish'] as $point) {
                $this->fresh(); $at = 100; $id = $this->act['target_id']; $options = ['exit_cut' => $kind.'.commit.'.$point];
                if ('principals' === $kind) { $action = 'constitute'; $options['envelope'] = $this->sign($this->act); }
                elseif ('successors' === $kind) {
                    [$p, $a, $at] = $this->nativeInputs(); $id = $p['principal_version_id']; $action = 'successor';
                    $options['activation'] = NativeState::ref($a, 'principal_activation_id');
                } else {
                    $service = new NativePrincipal($this->state, static fn () => 100);
                    if ('activations' === $kind) { $service->constitute($this->sign($this->act)); } else { $this->activate(); }
                    if ('authorities' === $kind) { $action = 'issue'; }
                    else { $action = 'lifecycle'; $a = $this->act; $a['action'] = 'activations' === $kind ? 'ACTIVATE' : 'REVOKE'; $a['act_id'] = 'cut-lifecycle'; $options['envelope'] = $this->sign($a); }
                }
                $child = $this->start($action, $id, $at, $options);
                self::assertSame([73, 'INTERRUPTED'], $this->finish($child), $kind.'.'.$point);
                $ids = $this->state->ids($kind); self::assertCount(1, $ids);
                if ('after_publish' === $point) {
                    self::assertIsArray($this->state->get($kind, $ids[0]));
                    if ('principals' === $kind) { self::assertSame('PENDING_ACTIVATION', (new NativePrincipal($this->state))->load($id, $at, false)['status']); }
                    elseif ('activations' === $kind) { self::assertIsArray((new NativePrincipal($this->state))->load($id, $at)); }
                    elseif ('revocations' === $kind) { $this->fails('NIR_PRINCIPAL_NOT_CURRENT', fn () => (new NativePrincipal($this->state))->load($id, $at)); }
                    elseif ('authorities' === $kind) { self::assertIsArray((new NativeAuthority($this->state))->load($ids[0], $at)); }
                    else { self::assertIsArray((new NativeSuccessor($this->state))->load($ids[0], $at)); }
                }
                else { $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $this->state->get($kind, $ids[0])); }
            }
        }
    }

    public function testSeparateProcessChecksExpiryAtTheFinalPublicationGate(): void
    {
        [$id, $at] = $this->readyTransition();
        $child = $this->start('execute', $id, $at, ['expire_final' => true]);
        self::assertSame([0, 'NIR_ROOT_INELIGIBLE'], $this->finish($child));
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => (new NativeBindingReader($this->state))->read('imperium-test', 'provider-binding', 'email.send', $at));
    }

    private function fresh(): void { $this->tearDown(); $this->setUp(); }

    private function start(string $action, string $id, int $at, array $options): array
    {
        $path = $this->root.'/worker-'.bin2hex(random_bytes(6)).'.json'; $this->write(basename($path), $options);
        $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/fixtures/native-transition-worker.php', $action, $this->root, $id, (string) $at, $path],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        return [$process, $pipes];
    }

    private function finish(array &$child): array
    {
        [$process, $pipes] = $child;
        $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process); $child = [];
        self::assertSame('', $err);
        return [$code, $out];
    }

    private function stop(?array &$child): void
    {
        if ($child) { proc_terminate($child[0]); foreach ($child[1] as $pipe) { fclose($pipe); } proc_close($child[0]); $child = []; }
    }

    private function until(string $path): void
    {
        $deadline = microtime(true) + 15;
        while (!is_file($path) && microtime(true) < $deadline) { usleep(1000); clearstatcache(true, $path); }
        self::assertFileExists($path);
    }
}
