<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;

[$script, $root, $claimId, $gate] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}
$claim = json_decode((string) file_get_contents($root.'/var/imperium/runtime/provider-invocations/'.$claimId.'.json'), true, 512, JSON_THROW_ON_ERROR);
try {
    (new ProviderInvocationJournalService($root))->start($claim, new \DateTimeImmutable());
    echo 'STARTED';
} catch (\RuntimeException $exception) {
    echo $exception->getMessage();
}
