<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

/** Test process only: observe the actual journal rename without replacing it. */
function rename(string $from, string $to): bool
{
    $hook = $GLOBALS['ppc5_publication_barrier'] ?? null;
    if ($hook !== null) { $hook('before', $to); }
    $result = \rename($from, $to);
    if ($result && $hook !== null) { $hook('after', $to); }
    return $result;
}
