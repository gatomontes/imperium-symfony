<?php
declare(strict_types=1);
// Synthetic process proof only; refuses every root outside its generated temp namespace.
require dirname(__DIR__, 4).'/vendor/autoload.php';

use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Citadel\Authority\RecruiterEvidence;
use App\Tests\Imperium\Runtime\Support\CitadelAuthorityFixture;

$mode = $argv[1] ?? ''; $root = $argv[2] ?? '';
$real = realpath($root); $temp = realpath(sys_get_temp_dir());
if ($real === false || $temp === false || dirname($real) !== $temp || !str_starts_with(basename($real), 'citadel-authority-synthetic-')) { exit(9); }
if ($mode === 'writer') {
    $store = new StateStore($real);
    $store->locked(function () use ($store, $real): void {
        file_put_contents($real.'/writer-locked', 'synthetic');
        $until = microtime(true) + 12;
        while (!is_file($real.'/release-writer')) {
            if (microtime(true) > $until) { throw new RuntimeException('Synthetic writer wait expired'); }
            usleep(10000);
        }
        $state = CitadelAuthorityFixture::state(); $state['generation'] = 11;
        $state['events'][] = ['transition' => 'LATER', 'result' => 'SUCCESS', 'generation' => 11];
        $store->write($state);
    });
    echo "writer complete\n";
} elseif ($mode === 'export') {
    $clock = new class implements \App\Imperium\Runtime\Clock { public function now(): DateTimeImmutable { return new DateTimeImmutable('@1900000000'); } };
    file_put_contents($real.'/export-started', 'synthetic');
    echo json_encode((new RecruiterEvidence($real, $clock))->export('synthetic-authority-test'), JSON_THROW_ON_ERROR);
} else { exit(9); }
