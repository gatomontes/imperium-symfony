<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
// Test-only before-dispatch barriers, never production hooks or journal rewriting.
[$script,$root,$requestFile,$gate]=$argv;
$request=json_decode(file_get_contents($requestFile),true,512,JSON_THROW_ON_ERROR);
file_put_contents($gate.'.ready',(string)getmypid());
$deadline=microtime(true)+20;
while (!is_file($gate)) {if(microtime(true)>$deadline)exit(3);usleep(1000);}
try {$r=(new App\ProtectedMission\AuthorityOwner($root))->dispatch($request);echo json_encode(['ok'=>true,'result'=>$r],JSON_THROW_ON_ERROR);}
catch (Throwable $e) {echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_THROW_ON_ERROR);}
