<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativePrincipal, NativeAuthority, NativeSuccessor, NativeConsumer};
use App\Imperium\Runtime\Persistence\{AtomicTransition, ImmutableRecordStore};

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $action, $root, $id, $timestamp, $optionsPath] = $argv;
$at = (int) $timestamp;
$options = json_decode((string) file_get_contents($optionsPath), true, 32, JSON_THROW_ON_ERROR);
$wait = static function (string $file): void {
    $deadline = microtime(true) + 15;
    while (!is_file($file)) { if (microtime(true) >= $deadline) { throw new RuntimeException('WORKER_BARRIER_TIMEOUT'); } usleep(1000); clearstatcache(true, $file); }
};
$checkpoint = static function (string $cut) use ($options, $wait): void {
    if (($options['exit_cut'] ?? null) === $cut) { echo 'INTERRUPTED'; exit(73); }
    if (($options['hold_cut'] ?? null) === $cut) {
        touch($options['held']); $wait($options['release']);
    }
};
$calls = 0;
$clock = static function () use ($at, $options, &$calls): int {
    ++$calls;
    return ($options['expire_final'] ?? false) && $calls >= 3 ? $at + 601 : $at;
};
try {
    if (isset($options['ready'])) { touch($options['ready']); }
    if (isset($options['go'])) { $wait($options['go']); }
    $state = new NativeState($root, $checkpoint);
    if ('execute' === $action) {
        (new NativeConsumer($state, $clock, $checkpoint))->execute($id); echo 'COMMITTED';
    } elseif ('constitute' === $action) {
        (new NativePrincipal($state, $clock))->constitute($options['envelope']); echo 'DONE';
    } elseif ('lifecycle' === $action) {
        (new NativePrincipal($state, $clock))->lifecycle($id, $options['envelope']); echo 'DONE';
    } elseif ('issue' === $action) {
        (new NativeAuthority($state, $clock))->issue($id); echo 'DONE';
    } elseif ('successor' === $action) {
        (new NativeSuccessor($state, $clock))->create($id, $options['activation']); echo 'DONE';
    } elseif ('generation' === $action) {
        $scope = 'immutable:'.hash('sha256', NativeState::SOURCES['principal']);
        $lock = fopen($root.'/var/imperium/runtime/transition-locks/'.hash('sha256', $scope).'.lock', 'c+');
        if (flock($lock, LOCK_EX | LOCK_NB)) { flock($lock, LOCK_UN); throw new RuntimeException('SOURCE_LOCK_NOT_HELD'); }
        fclose($lock); touch($options['blocked']);
        $p = $state->json(NativeState::SOURCES['principal'].'/principal-v2.json');
        $p['principal_version_id'] = 'later-principal'; $p['principal_generation'] = 2;
        $p['lifecycle']['constituted_at'] = gmdate(DATE_ATOM, $at);
        (new ImmutableRecordStore($root, new AtomicTransition($root)))->put(NativeState::SOURCES['principal'], 'later-principal', $p);
        echo 'DONE';
    } else { throw new RuntimeException('UNKNOWN_WORKER_ACTION'); }
} catch (Throwable $e) { echo $e->getMessage(); }
