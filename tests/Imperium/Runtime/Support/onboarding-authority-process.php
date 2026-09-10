<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Admission};

// Test subprocess only; input contains public signed originals, never private keys.
$root=$argv[1]; $mode=$argv[2]; $request=json_decode((string)file_get_contents($argv[3]),true,512,JSON_THROW_ON_ERROR);
$clock=new class implements Clock { public function now():DateTimeImmutable { return new DateTimeImmutable('@1800000000'); } };
$store=new AuthorityStore($root,$clock,'instance-test','citadel-test','operator-test',str_repeat('a',40));
if ($mode==='before-publication') {
    $store->journal->changeAtHead(static function(array &$state,array $head)use($root):void {
        $state['unpublished-test-mutation']='must never appear after restart';
        file_put_contents($root.'/before-ready','ready');
        exit(55); // Abrupt process termination while lock/state mutation is in flight.
    });
}
$deadline=microtime(true)+15;
while (!is_file($root.'/start')) { if (microtime(true)>$deadline) { exit(70); } usleep(1000); }
try {
    $result=(new Admission($store))->retain($request['envelope'],$request['object'],$request['support']);
    echo json_encode($result,JSON_THROW_ON_ERROR);
    if ($mode==='after-publication') { exit(56); }
} catch (Throwable $e) { echo $e->getMessage(); exit(1); }
