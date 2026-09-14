<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Persistence;

/** Test-only pause after a real absent observation, before parent enumeration.
 * Competing publication still uses the unmodified public storage entry. */
function file_exists(string $path): bool
{
    $observed = \file_exists($path);
    if (!$observed && ($GLOBALS['ppc4_preflight_observation_path'] ?? null) === $path) {
        unset($GLOBALS['ppc4_preflight_observation_path']);
        $channel = $GLOBALS['ppc4_preflight_observation_channel'];
        \file_put_contents($channel.'.held', 'absent-observed');
        $end = microtime(true) + 30;
        while (!\file_exists($channel.'.release')) {
            if (microtime(true) > $end) { throw new \RuntimeException('PREFLIGHT_BARRIER_TIMEOUT'); }
            usleep(1000);
        }
    }
    return $observed;
}
