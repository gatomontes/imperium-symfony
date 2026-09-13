<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
$keys = new \App\Imperium\Runtime\Onboarding\Deployment\FileKeySource($argv[1]);
echo $keys->generation();
$keys->withKey(static function(#[\SensitiveParameter] string $key): void {
    if (!str_starts_with($key,'synthetic-')) { throw new \RuntimeException('fixture required'); }
    echo ':delivered';
});
