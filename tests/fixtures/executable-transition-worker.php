<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer};

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $directory, $pin, $gate] = $argv;
$deadline = microtime(true) + 10;
while (!is_file($gate)) {
    if (microtime(true) >= $deadline) { exit(72); }
    usleep(1000);
}
try {
    $store = new TransitionStore($directory);
    $consumer = new TransitionConsumer($store, new TransitionAuthority($store, $pin));
    $consumer->execute($pin, 150);
    echo 'COMMITTED';
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
