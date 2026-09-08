<?php
declare(strict_types=1);

/** Offline command/DI proof. All roots and signing keys are fresh and synthetic. */
require dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/tests/Imperium/Runtime/Support/CitadelFormationFixture.php';
require_once dirname(__DIR__).'/tests/Imperium/Runtime/Support/CitadelPublicationInterruption.php';

use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture, CitadelPublicationInterruption};
use App\Imperium\Runtime\Citadel\Formation\FormationJournal;

if ($argc !== 1) { fwrite(STDERR, "Usage: php tools/prove-citadel-correction.php > proof.json\n"); exit(2); }
function refused(callable $call, string $expected): string {
    try { $call(); } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), $expected)) { throw $e; }
        return $e->getMessage();
    }
    throw new RuntimeException('Expected refusal '.$expected);
}
function reserved(CitadelFormationFixture $f): array {
    $id = $f->receive()['intake_id']; $f->appoint(); $f->understand($id); $f->draft($id);
    $review = $f->approve($f->present($id));
    $f->run('reserve-mission', ['reviewId' => $review['review_id']]);
    return [$id, $review];
}
$proof = ['schema' => 'imperium.citadel-correction-proof/v1', 'php_version' => PHP_VERSION,
    'boundary' => ['network_calls' => 0, 'real_credentials' => false, 'real_installation_changes' => false,
        'activation' => false, 'mission_execution' => false, 'new_authority_from_recovery' => false]];
$f = new CitadelFormationFixture();
try {
    $id = $f->receive()['intake_id']; $f->appoint(); $session = $f->grant($id, 'interview');
    $controls = [];
    foreach (['OPEN', 'DEFERRED', 'REFUSED'] as $s) { $controls[$s] = $f->sign('CONTROL_FORMATION_SESSION', ['session_id' => $session, 'disposition' => $s]); }
    $control = fn (string $s) => $f->run('control-session', ['sessionId' => $session, 'disposition' => $s, 'decision' => $controls[$s]]);
    $control('DEFERRED'); $control('OPEN');
    // CF01 resume/refusal exercises an unfinished interview; UNDERSTOOD now closes it.
    $f->transport->response = [...$f::understanding(), 'disposition' => 'QUESTION', 'question' => 'Which deadline?'];
    $f->run('call', ['sessionId' => $session, 'attemptId' => 'legitimate-resume-001']);
    $control('REFUSED'); $before = $f->journal->read();
    $denials = [];
    foreach (['OPEN', 'DEFERRED', 'OPEN'] as $s) { $denials[] = refused(fn () => $control($s), 'CMF055'); }
    $denials[] = refused(fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'after-refusal-001']), 'CMF067');
    if ($before !== $f->journal->read() || count($f->transport->calls) !== 1) { throw new RuntimeException('CF01 proof invariant'); }
    $proof['CF01'] = ['disposition' => 'CLOSED_LOCAL', 'stale_signed_controls' => $controls, 'refusals' => $denials,
        'session' => $before['state']['sessions'][$session], 'public_trust' => $before['state']['trust'],
        'provider_calls' => $f->transport->calls, 'journal_unchanged_after_refusal' => true];
} finally { $f->close(); }
$f = new CitadelFormationFixture();
try {
    [$id, $review] = reserved($f);
    CitadelPublicationInterruption::$root = $f->root;
    $interruption = refused(fn () => $f->run('deliver-handoff', ['intakeId' => $id]), 'SYNTHETIC_INTERRUPTION_AFTER_REAL_CHILD_RENAME');
    $path = CitadelPublicationInterruption::$publishedPath;
    $bytes = file_get_contents($path); $receipt = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
    $before = $f->journal->read();
    if (isset($before['state']['handoffs'][$id])) { throw new RuntimeException('Parent published before interruption'); }
    $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
    $handoff = $f->run('deliver-handoff', ['intakeId' => $id]); $after = $f->journal->read();
    $repeat = $f->run('deliver-handoff', ['intakeId' => $id]);
    if ($repeat !== $handoff || $after !== $f->journal->read() || $bytes !== file_get_contents($path)
        || count($f->transport->calls) !== 2 || $handoff['acceptances'] !== []) { throw new RuntimeException('CF02 proof invariant'); }
    $proof['CF02'] = ['disposition' => 'CLOSED_LOCAL', 'interruption' => $interruption,
        'authority_frame' => $f->journal->historical($receipt['publication']['authority_generation'], $receipt['publication']['authority_frame_digest']),
        'before_parent_frame' => $before, 'after_parent_frame' => $after, 'receipt' => $receipt,
        'receipt_bytes_sha256_before' => hash('sha256', $bytes), 'receipt_bytes_sha256_after' => hash_file('sha256', $path),
        'recovery_at' => $f->clock->at, 'handoff' => $handoff, 'provider_calls' => $f->transport->calls,
        'repeated_recovery_unchanged' => true, 'acceptance_manufactured' => false];
} finally { CitadelPublicationInterruption::$root = null; $f->close(); }
$f = new CitadelFormationFixture();
try {
    [$id, $review] = reserved($f); $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
    $before = $f->journal->read();
    $refusal = refused(fn () => $f->run('deliver-handoff', ['intakeId' => $id]), 'CMF094');
    if ($before !== $f->journal->read() || is_dir($f->root.'/var/imperium/citadel/children')) { throw new RuntimeException('Expired approval created effect'); }
    $proof['expired_approval_no_child'] = ['refusal' => $refusal, 'review' => $review,
        'journal_unchanged' => true, 'children' => 0, 'additional_provider_calls' => 0];
} finally { $f->close(); }
echo json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
