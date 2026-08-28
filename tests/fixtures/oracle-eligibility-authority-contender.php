<?php

declare(strict_types=1);

use App\Imperium\Runtime\Oracle\ModelEligibilityFindingService;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $caseId, $authorityId, $bindingId, $gate, $disposition] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}
$eligible = 'ELIGIBLE' === $disposition;
$finding = (new ModelEligibilityFindingService($root))->issue(
    $caseId,
    $authorityId,
    $bindingId,
    $disposition,
    ['fit' => [
        'disposition' => $eligible ? 'SATISFIED' : 'FAILED',
        'rationale' => $eligible ? 'The exact model satisfies the frozen criterion.' : 'The exact model fails the frozen criterion.',
    ]],
    ['source-a'],
    ['claim-a'],
    $eligible ? [] : ['FIT_FAILED'],
    new \DateTimeImmutable($eligible ? '2026-08-28T18:00:00+00:00' : '2026-08-28T19:00:00+00:00'),
);
echo $finding['finding_id'];
