<?php
declare(strict_types=1);
require __DIR__.'/LocalIsolation.php';
try {
    $read=static fn($p)=>json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR);
    $op=$argv[1]??'help';
    $result=match($op){
        'materialize'=>LocalIsolation::materialize($argv[2],$argv[3]),
        'manifest'=>LocalIsolation::manifest($argv[2]),
        'draft'=>LocalIsolation::draft($read($argv[2]),$argv[3],isset($argv[4])?(int)$argv[4]:0),
        'verify'=>LocalIsolation::verify($read($argv[2]),$read($argv[3]),$read($argv[4]),$read($argv[5]),$argv[6],$read($argv[7]),$read($argv[8])),
        default=>throw new RuntimeException('Usage: materialize SOURCE FRESH_TARGET | manifest ROOT | draft INVENTORY TARGET [EXPIRY] | verify CHAIN STATUS TRUST INVENTORY TARGET BEFORE AFTER'),
    };
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(2);}
