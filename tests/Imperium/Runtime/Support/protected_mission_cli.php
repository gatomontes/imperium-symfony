<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
// Explicit test-only deployment substitute. No production entry accepts this root argument.
$root=$argv[1];
exit(App\ProtectedMission\Cli::run(fn()=>new App\ProtectedMission\AuthorityOwner($root),array_slice($argv,2)));
