<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';

use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission,AuthorityStore};

// Test-only worker: public signed inputs and a temporary root; no private keys.
[$script,$root,$name]=$argv;
$request=json_decode((string)file_get_contents($root.'/'.$name.'.json'),true,512,JSON_THROW_ON_ERROR);
$clock=new class implements Clock { public function now():DateTimeImmutable { return new DateTimeImmutable('@1800000000'); } };
$store=new AuthorityStore($root,$clock,'instance-test','citadel-test','operator-test',str_repeat('a',40));
file_put_contents($root.'/'.$name.'.ready','ready');
$deadline=microtime(true)+20;
while (!is_file($root.'/'.$name.'.go')) { if (microtime(true)>$deadline) { exit(70); } usleep(1000); }
try {
    echo json_encode((new Admission($store))->retain($request['envelope'],$request['object'],$request['support']),JSON_THROW_ON_ERROR);
} catch (Throwable $error) { echo $error->getMessage(); exit(1); }
