<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer};

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $directory, $pin, $cut] = $argv;
$store = new TransitionStore($directory, static function (string $observed) use ($cut): void {
    if ($observed === $cut) { exit(73); }
});
$custody = new TransitionAuthority($store, $pin, static fn () => 150);
if (str_starts_with($cut, 'authority.')) { $custody->issue(); }
else { (new TransitionConsumer($store, $custody, static fn () => 150))->execute($pin); }
exit(74);
