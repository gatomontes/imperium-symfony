<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
$f=new App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture();
echo json_encode(['root'=>$f->root,'authorization_id'=>$f->id],JSON_THROW_ON_ERROR);
