<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
// Test-only root selection. Production entry uses InstalledRuntime exclusively.
$root=$argv[1]; $request=json_decode(base64_decode($argv[2],true),true,512,JSON_THROW_ON_ERROR);
$deadline=microtime(true)+10;
while (!is_file($argv[3])) { if (microtime(true)>$deadline) exit(3); usleep(1000); }
try {
    $result=(new App\ProtectedMission\AuthorityOwner($root))->dispatch($request);
    echo json_encode(['ok'=>true,'result'=>$result],JSON_THROW_ON_ERROR);
} catch (Throwable $error) { echo json_encode(['ok'=>false,'error'=>$error->getMessage()],JSON_THROW_ON_ERROR); }
