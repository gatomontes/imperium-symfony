<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
exit(App\ProtectedMission\Cli::run(App\ProtectedMission\InstalledRuntime::owner(...),array_slice($argv,1)));
