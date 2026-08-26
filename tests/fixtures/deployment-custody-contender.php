<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Imperium\Runtime\Garrison\DelegateMissionDeploymentCustodyTransitionCoordinator;

[, $root, $fixturePath, $gate, $variant] = $argv;
while (!is_file($gate)) usleep(1000);
$fixture = json_decode((string) file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
$transition = $fixture['transition'];
$transition['contention_variant'] = (int) $variant;
try {
    (new DelegateMissionDeploymentCustodyTransitionCoordinator($root))->run(
        $fixture['authorization_id'], $fixture['transition_id'], $transition,
        $fixture['prior'], $fixture['deployed'],
    );
    echo 'STORED';
} catch (\RuntimeException $error) {
    echo $error->getMessage();
}
