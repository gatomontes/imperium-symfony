<?php

declare(strict_types=1);

use App\Imperium\Runtime\Clavium\ProviderInvocationClaimService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $activationId, $authorityId, $gate] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}
$claim = (new ProviderInvocationClaimService($root))->claim(
    $activationId,
    $authorityId,
    new \DateTimeImmutable('2026-08-25T12:00:00+00:00'),
);
echo $claim['claim_id'];
