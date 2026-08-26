<?php

declare(strict_types=1);

use App\Imperium\Runtime\Clavium\OperationalCognitionInvocationClaimService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $leaseId, $authorityId, $gate] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}
$claim = (new OperationalCognitionInvocationClaimService($root))->claim(
    $leaseId,
    $authorityId,
    new \DateTimeImmutable('2026-08-26T16:03:00+00:00'),
);
echo $claim['claim_id'];
