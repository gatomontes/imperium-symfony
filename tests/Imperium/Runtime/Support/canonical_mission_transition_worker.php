<?php

declare(strict_types=1);

use App\Imperium\Runtime\Mission\CanonicalMissionTransitionService;
use App\Imperium\Runtime\Mission\MissionCapability;

require dirname(__DIR__, 4).'/vendor/autoload.php';

[$script, $root, $authorizationId, $encodedCapability, $at, $gate] = $argv;
$deadline = microtime(true) + 10;
while (!is_file($gate) && microtime(true) < $deadline) { usleep(1000); }
if (!is_file($gate)) { fwrite(STDOUT, 'WORKER_GATE_TIMEOUT'); exit(0); }

try {
    $record = json_decode(base64_decode($encodedCapability, true), true, 512, JSON_THROW_ON_ERROR);
    (new CanonicalMissionTransitionService($root))->consume(
        MissionCapability::fromArray($record),
        $authorizationId,
        new DateTimeImmutable($at),
    );
    fwrite(STDOUT, 'CONSUMED');
} catch (Throwable $error) {
    fwrite(STDOUT, $error->getMessage());
}
