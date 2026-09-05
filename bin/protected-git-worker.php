<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
try {
    $payload=json_decode(file_get_contents($argv[1]),true,128,JSON_THROW_ON_ERROR);
    $snapshot=(new App\ProtectedMission\OfflineGitInspector())->inspect($payload);
    echo json_encode(['ok'=>true,'snapshot'=>$snapshot],JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e instanceof RuntimeException?$e->getMessage():'PMA_INSPECTION_FAILED'],JSON_THROW_ON_ERROR);
}
