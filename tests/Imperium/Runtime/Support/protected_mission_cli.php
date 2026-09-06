<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
// Explicit test-only deployment substitute. No production entry accepts this root argument.
$root=$argv[1];
if (($argv[2] ?? '')==='enroll' && !is_dir(App\ProtectedMission\ScratchWorkspace::root($root))) mkdir(App\ProtectedMission\ScratchWorkspace::root($root),0700,true);
exit(App\ProtectedMission\Cli::run(fn()=>new App\ProtectedMission\AuthorityOwner($root),array_slice($argv,2)));
