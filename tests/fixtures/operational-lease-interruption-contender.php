<?php

declare(strict_types=1);

use App\Imperium\Runtime\Clavium\OperationalCognitionInvocationClaimService;
use App\Imperium\Runtime\Governance\InternalOperationalLeaseInterruptionEnforcementService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $mode, $root, $leaseId, $cognitionAuthorityId, $enforcementAuthorityId, $locksmithBindingId, $gate] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}
if ('claim' === $mode) {
    $record = (new OperationalCognitionInvocationClaimService($root))->claim($leaseId, $cognitionAuthorityId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));
    echo $record['claim_id'];
} else {
    $record = (new InternalOperationalLeaseInterruptionEnforcementService($root))->enforce($enforcementAuthorityId, $locksmithBindingId, new \DateTimeImmutable('2026-08-27T12:03:00+00:00'));
    echo $record['result_id'];
}
