<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer};

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $directory, $pin, $cut] = $argv;
$store = new TransitionStore($directory, static function (string $observed) use ($cut): void {
    if ($observed === $cut) { exit(73); }
});
$custody = new TransitionAuthority($store, $pin);
if (str_starts_with($cut, 'authority.')) { $custody->issue(150); }
else { (new TransitionConsumer($store, $custody))->execute($pin, 150); }
exit(74);
