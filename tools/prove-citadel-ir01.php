<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;
if ($argc !== 1) { throw new RuntimeException('No arguments allowed.'); }
$f = new CitadelFormationFixture();
try {
    $id = $f->receive()['intake_id']; $f->appoint(); $sid = $f->grant($id, 'interview');
    $f->transport->response = $f::understanding();
    $admitted = $f->run('call', ['sessionId' => $sid, 'attemptId' => 'ir01-understood-0001']);
    $before = $f->journal->read(); $calls = count($f->transport->calls); $refusal = null;
    try { $f->run('call', ['sessionId' => $sid, 'attemptId' => 'ir01-after-understood-0002']); }
    catch (RuntimeException $e) { $refusal = $e->getMessage(); }
    $extra = count($f->transport->calls) - $calls;
    $recovered = $f->run('recover-response', ['sessionId' => $sid, 'attemptId' => 'ir01-understood-0001']);
    if ($extra !== 0 || !str_contains($refusal ?? '', 'CMF067') || $before !== $f->journal->read() || $recovered !== $admitted) {
        throw new RuntimeException('IR01 completion invariant failed');
    }
    echo json_encode(['synthetic_only' => true, 'session_status' => $before['state']['sessions'][$sid]['status'],
        'extra_provider_calls_after_understood' => $extra, 'refusal' => $refusal,
        'journal_and_exposure_unchanged' => true, 'original_recovery_idempotent' => true,
        'boundary' => ['network_calls' => 0, 'mission_execution' => false]], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} finally { $f->close(); }
