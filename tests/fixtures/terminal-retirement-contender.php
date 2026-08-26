<?php

declare(strict_types=1);

require dirname(__DIR__,2).'/vendor/autoload.php';

use App\Imperium\Runtime\Garrison\DelegateMissionTerminalTransitionCoordinator;

[, $root, $fixturePath, $gate, $variant] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}

$fixture = json_decode((string) file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
$terminal = $fixture['terminal'];
$terminal['contention_variant'] = (int) $variant;

try {
    (new DelegateMissionTerminalTransitionCoordinator($root))->run(
        $fixture['authorization_id'],
        $fixture['terminal_id'],
        $terminal,
        $fixture['prior_custody'],
        $fixture['restored_custody'],
        $fixture['prior_binding'],
        $fixture['retired_binding'],
    );
    echo 'STORED';
} catch (\RuntimeException $error) {
    echo $error->getMessage();
}
