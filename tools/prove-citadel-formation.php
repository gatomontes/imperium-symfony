<?php
declare(strict_types=1);

/** Offline demonstration only: no caller-selected runtime, transport or signer. */
require dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/tests/Imperium/Runtime/Support/CitadelFormationFixture.php';

use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;

if ($argc !== 1) { fwrite(STDERR, "Usage: php tools/prove-citadel-formation.php > proof.json\n"); exit(2); }
$fixture = new CitadelFormationFixture();
try {
    $intake = $fixture->receive();
    $id = $intake['intake_id'];
    $castellan = $fixture->appoint();
    $understanding = $fixture->understand($id);
    $draft = $fixture->draft($id);
    $presentation = $fixture->present($id);
    $review = $fixture->approve($presentation);
    $reservation = $fixture->run('reserve-mission', ['reviewId' => $review['review_id']]);
    $handoff = $fixture->run('deliver-handoff', ['intakeId' => $id]);
    $session = $fixture->grant($id, 'acceptance');
    $fixture->transport->response = ['disposition' => 'ACCEPTED', 'rationale' => 'The exact synthetic mandate and original exchange have been received.', 'gaps' => '', 'dissent' => 'The deadline objection remains in the original exchange.'];
    $acceptance = $fixture->run('call', ['sessionId' => $session, 'attemptId' => 'proof-acceptance-0001']);
    $validation = $fixture->run('validate-step-one', ['intakeId' => $id]);
    $state = $fixture->journal->read();
    $institutions = [];
    foreach (glob($fixture->root.'/var/imperium/operator-root/installations/*.json') ?: [] as $path) {
        $institutions[] = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
    $proof = ['schema' => 'imperium.citadel-offline-proof/v1', 'php_version' => PHP_VERSION,
        'synthetic_clock' => $fixture->clock->now()->format(DATE_ATOM),
        'boundary' => ['network_calls' => 0, 'real_credentials' => false, 'real_installation_changes' => false,
            'activation' => false, 'mission_execution' => false, 'semantic_competence_proven' => false],
        'native_institutional_installations' => $institutions,
        'intake' => $intake, 'castellan' => $castellan, 'understanding' => $understanding,
        'draft' => $draft, 'review' => $review, 'reservation' => $reservation, 'handoff' => $handoff,
        'acceptance' => $acceptance, 'step_one_validation' => $validation,
        'provider_calls' => $fixture->transport->calls,
        'session_ledgers' => $state['state']['sessions'],
        'journal_identity' => ['generation' => $state['generation'], 'record_digest' => $state['record_digest']]];
    echo json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
} finally { $fixture->close(); }
