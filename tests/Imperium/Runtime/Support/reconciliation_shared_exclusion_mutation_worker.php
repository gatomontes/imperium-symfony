<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\NativePrincipal;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require dirname(__DIR__, 4).'/vendor/autoload.php';

[$script, $fixturePath] = $argv + [null, null];
try {
    $f = json_decode((string) file_get_contents((string) $fixturePath), true, 32, JSON_THROW_ON_ERROR);
    echo "MUTATION_ATTEMPTING\n"; flush();
    (new NativePrincipal(new NativeState($f['root']), static fn (): int => $f['at']))->lifecycle($f['principal_id'], $f['envelope']);
    file_put_contents($f['marker_path'], 'committed');
    echo "MUTATION_COMMITTED\n";
    exit(0);
} catch (Throwable $error) {
    echo 'MUTATION_REFUSED '.$error->getMessage()."\n";
    exit(3);
}
