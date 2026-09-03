<?php

declare(strict_types=1);

require dirname(__DIR__, 4).'/vendor/autoload.php';

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativePrincipal, NativeReconstructor, NativeState};

[$script, $mode, $root, $at, $sync] = $argv;
$resolved = realpath($root);
$temp = realpath(sys_get_temp_dir());
if (false === $resolved || false === $temp || dirname($resolved) !== $temp || !str_starts_with(basename($resolved), 'native-transition-')) {
    throw new RuntimeException('Disposable fixture storage required');
}
if (!is_dir($sync) && !mkdir($sync, 0770, true) && !is_dir($sync)) {
    throw new RuntimeException('Sync directory unavailable');
}
$at = (int) $at;

$wait = static function (string $path): void {
    $deadline = microtime(true) + 15;
    while (!is_file($path)) {
        if (microtime(true) > $deadline) { throw new RuntimeException('Worker rendezvous timeout'); }
        usleep(10000);
        clearstatcache(true, $path);
    }
};

try {
    if ('revoke' === $mode) {
        $signed = json_decode((string) file_get_contents($sync.'/signed-revocation.json'), true, 32, JSON_THROW_ON_ERROR);
        $wait($sync.'/inspector-1');
        (new NativePrincipal(new NativeState($root), static fn (): int => $at))->lifecycle($signed['act']['target_id'], $signed);
        file_put_contents($sync.'/release-1', 'release');
        echo "REVOKED\n";
        exit(0);
    }
    if ('churn' === $mode) {
        $relative = 'var/imperium/la-cortine/deterministic-execution-claims/process-churn';
        $target = $resolved.'/'.$relative;
        for ($attempt = 1; $attempt <= 2; ++$attempt) {
            $wait($sync.'/inspector-'.$attempt);
            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0770, true) && !is_dir(dirname($target))) {
                throw new RuntimeException('Churn directory unavailable');
            }
            file_put_contents($target, 'attempt-'.$attempt);
            file_put_contents($sync.'/release-'.$attempt, 'release');
        }
        echo "CHURNED\n";
        exit(0);
    }

    $pauseEveryAttempt = 'inspect-each' === $mode;
    $pauseFirstAttempt = 'inspect-once' === $mode || $pauseEveryAttempt;
    $checkpoint = ($pauseFirstAttempt || $pauseEveryAttempt)
        ? static function (string $cut, int $attempt) use ($sync, $wait, $pauseEveryAttempt): void {
            if ('inspection.manifest_a' !== $cut || (!$pauseEveryAttempt && 1 !== $attempt)) { return; }
            file_put_contents($sync.'/inspector-'.$attempt, 'held');
            $wait($sync.'/release-'.$attempt);
        }
        : null;
    $state = new NativeState($root);
    if ('reconstruct' === $mode) {
        $result = (new NativeReconstructor($state, $checkpoint))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at);
    } else {
        $result = (new NativeBindingReader($state, $checkpoint))->interpret('imperium-test', 'provider-binding', 'email.send', $at);
    }
    echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage());
    exit(1);
}
