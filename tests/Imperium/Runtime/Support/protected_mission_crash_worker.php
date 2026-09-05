<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
if ($argv[2]==='torn-frame') {
    // Storage-format crash injection in a real dying process; not an instruction-level owner hook.
    $handle=fopen($argv[1].'/authority.journal','ab');flock($handle,LOCK_EX);
    fwrite($handle,'200 '.str_repeat('a',64)."\ninterrupted-frame");fflush($handle);exit(23);
}
$request=json_decode(base64_decode($argv[3],true),true,512,JSON_THROW_ON_ERROR);
(new App\ProtectedMission\AuthorityOwner($argv[1]))->dispatch($request);
exit(23); // Complete durable publication, no response delivered to caller.
