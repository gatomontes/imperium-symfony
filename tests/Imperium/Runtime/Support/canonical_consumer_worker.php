<?php

declare(strict_types=1);

require dirname(__DIR__, 4).'/vendor/autoload.php';

use App\Imperium\Runtime\ProviderTransition\{NativeConsumer, NativeState};
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService;

[$script, $mode, $root, $authority, $at] = $argv;
$resolved = realpath($root);
$temp = realpath(sys_get_temp_dir());
if (false === $resolved || false === $temp || dirname($resolved) !== $temp || !str_starts_with(basename($resolved), 'native-transition-')) {
    throw new RuntimeException('Disposable fixture storage required');
}
$at = (int) $at;
try {
    if ('legacy' === $mode) {
        echo "ATTEMPTING\n"; flush();
        try { (new GovernedProviderExecutionAdmissionService($root))->admit($authority, new DateTimeImmutable('@'.$at)); }
        catch (RuntimeException $e) {
            if ('CCI_NATIVE_STATE_PRECLUDES_LEGACY' !== $e->getMessage()) { throw $e; }
            echo $e->getMessage(); exit(0);
        }
        throw new RuntimeException('Legacy admission unexpectedly succeeded');
    }
    $state = new NativeState($root, static function (string $cut) use ($mode, $root): void {
        if ('journals.commit.after_publish' !== $cut) { return; }
        if ('cut' === $mode) { exit(23); }
        file_put_contents($root.'/process-held', 'held');
        $deadline = microtime(true) + 15;
        while (!is_file($root.'/process-release')) {
            if (microtime(true) > $deadline) { throw new RuntimeException('Test release timeout'); }
            usleep(10000); clearstatcache();
        }
    });
    $result = (new NativeConsumer($state, static fn () => $at))->execute($authority);
    echo $result['effective_status'];
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(1); }
