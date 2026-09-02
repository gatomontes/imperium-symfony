<?php

declare(strict_types=1);

// Explicit CLI entrypoint. Merely including this file never executes a mission.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) { return; }
if (7 !== $argc || '--approved-source' !== $argv[1] || '--authorization-digest' !== $argv[3]) {
    fwrite(STDERR, "Usage: php tools/run-atomic-transition-reproof-v2.php --approved-source COMMIT --authorization-digest DIGEST PROOF_ID EXISTING_OUTPUT_PARENT\n");
    exit(2);
}
$repository = dirname(__DIR__);
try {
    require_once $repository.'/src/Bootstrap/CanonicalJson.php';
    require_once $repository.'/src/ReproofV2/Records.php';
    require_once $repository.'/src/ReproofV2/Contract.php';
    require_once $repository.'/src/ReproofV2/SourceBundle.php';
    require_once $repository.'/src/ReproofV2/LocalSourceCapture.php';
    $source = (new \App\ReproofV2\LocalSourceCapture())->capture($repository, $argv[2]);
    $parent = realpath($argv[6]);
    if (false === $parent || str_starts_with(strtolower(str_replace('\\', '/', $parent)).'/', strtolower(str_replace('\\', '/', $repository)).'/')) {
        throw new \RuntimeException('REPROOF_DESTINATION_MUST_BE_OUTSIDE_REPOSITORY');
    }
    foreach (\App\ReproofV2\SourceBundle::PATHS as $path) {
        if (str_starts_with($path, 'src/') && str_ends_with($path, '.php')) { require_once $repository.'/'.$path; }
    }
    $store = new \App\ReproofV2\PackageStore();
    $directory = $store->reserve($parent, $argv[5]);
    $executor = hash('sha256', base64_decode($source['files']['src/ReproofV2/Runner.php']['bytes'], true));
    $package = (new \App\ReproofV2\Runner())->run($argv[5], $source, $argv[4], $executor);
    $store->publish($directory, $package);
    // No private locators, payloads, environment values or exception text are emitted.
    fwrite(STDOUT, "REPROOF_V2_CANDIDATE_WRITTEN_NOT_VERIFIED\n");
} catch (\Throwable) {
    fwrite(STDERR, "REPROOF_V2_EXECUTION_REFUSED_NO_AUTOMATIC_RETRY\n");
    exit(1);
}
