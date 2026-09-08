<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\SourceReview\Transport;
use App\Tests\SourceReview\Fixture;

$root = $argv[1];
$transport = new class($root) implements Transport {
    public function __construct(private string $root) {}
    public function send(string $secret, string $payload): string {
        file_put_contents($this->root.'/provider-calls.txt', hash('sha256', $payload)."\n", FILE_APPEND | LOCK_EX);
        usleep(200000);
        return '{"disposition":"INSUFFICIENT_INPUT","finding":null,"rationale":"Synthetic concurrency proof."}';
    }
};
$f = new Fixture($root, $transport, false);
try { $f->workflow->execute($argv[2], $argv[3]); echo 'COMPLETED'; }
catch (\Throwable) { echo 'REFUSED_NO_REPLAY'; }
