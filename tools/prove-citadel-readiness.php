<?php
declare(strict_types=1);

/** Fresh synthetic roots only; no root/clock/key/transport arguments. */
require dirname(__DIR__).'/vendor/autoload.php';

use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;
use App\Imperium\Runtime\Citadel\Formation\{FormationPreparation, FormationJournal};
use App\Command\CitadelPreparationCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Imperium\Runtime\Clock;

if ($argc !== 1) { fwrite(STDERR, "No arguments allowed.\n"); exit(2); }
$f = new CitadelFormationFixture();
try {
    $c = new ContainerBuilder(); $c->register(Clock::class)->setSynthetic(true)->setPublic(true);
    $c->register(FormationPreparation::class)->setAutowired(true);
    $c->register(CitadelPreparationCommand::class)->setAutowired(true)->setPublic(true);
    $c->compile(); $c->set(Clock::class, $f->clock);
    $command = new CommandTester($c->get(CitadelPreparationCommand::class));
    $packets = []; $transcript = [];
    $run = function (string $mode, array $input) use ($f, $command, &$transcript): array {
        $file = $f->root.'/public-preparation.json'; file_put_contents($file, json_encode($input, JSON_THROW_ON_ERROR));
        $before = $f->journal->read(); $calls = count($f->transport->calls);
        $status = $command->execute(['mode' => $mode, 'public-file' => $file]);
        if ($status !== ($mode === 'inspect' ? 2 : 0) || $before !== $f->journal->read() || $calls !== count($f->transport->calls)) {
            throw new RuntimeException('Preparation had an effect or failed: '.$command->getDisplay());
        }
        $result = json_decode($command->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $transcript[] = ['command' => 'imperium:citadel:prepare '.$mode.' public-preparation.json', 'exit_code' => $status,
            'input' => $input, 'output' => $result, 'journal_unchanged' => true, 'provider_calls_unchanged' => true];
        return $result;
    };
    $public = json_decode(file_get_contents(dirname(__DIR__).'/docs/citadel-readiness/public-evidence-request.json'), true, 512, JSON_THROW_ON_ERROR);
    $inspection = $run('inspect', $public);
    $sign = function (string $effect, array $object) use ($f, $run, $public, &$packets): array {
        $state = $f->journal->read()['state'];
        $packet = $run('decision', ['schema' => 'imperium.citadel-preparation-request/v1', 'citadel_id' => $state['citadel_id'],
            'trust_fingerprint' => $state['trust']['fingerprint'], 'effect' => $effect, 'object' => $object, 'expires_at' => $f->clock->at + 3600,
            'source_identity' => ['commit' => trim(shell_exec('git rev-parse HEAD')), 'tree' => trim(shell_exec('git rev-parse "HEAD^{tree}"')),
                'public_export_digest' => FormationJournal::digest($public)]]);
        $signature = $f->signPrepared($packet);
        $assembled = $run('assemble', ['packet' => $packet, 'public_key' => $state['trust']['public_key'], 'signature' => $signature['signature']]);
        $packets[] = $packet;
        return $assembled['decision'];
    };
    $id = $f->receive()['intake_id']; $f->appoint();
    $grant = function (string $phase) use ($f, $id, $sign): string {
        $terms = $f->terms($id, $phase);
        $effect = match ($phase) {'interview' => 'AUTHORIZE_INTERVIEW_SESSION', 'drafting' => 'AUTHORIZE_EXACT_DRAFTING', 'acceptance' => 'AUTHORIZE_RECEIVING_ASSESSMENT'};
        return $f->run('grant', ['intakeId' => $id, 'phase' => $phase, 'terms' => $terms, 'decision' => $sign($effect, $terms)]);
    };
    $interview = $grant('interview'); $f->transport->response = $f::understanding();
    $f->run('call', ['sessionId' => $interview, 'attemptId' => 'readiness-interview-0001']);
    if (!empty($f->journal->read()['state']['dossiers'])) { throw new RuntimeException('Premature proposal'); }
    $f->run('drafting-request', ['intakeId' => $id, 'charter' => ['scope' => 'Present synthetic material only.', 'questions' => 'Express the bounded plan.',
        'inputs' => 'Exact interview.', 'offices' => [], 'external_effects' => [], 'disclosure' => 'Synthetic data to in-process fake only.',
        'expected_return' => 'Numbered complete Step 1 plan.', 'stop_conditions' => 'Scope change.', 'amendment_triggers' => 'New investigation.',
        'retention' => 'Retain versions.', 'expires_at' => $f->clock->at + 600]]);
    $drafting = $grant('drafting'); $f->transport->response = $f::plan();
    $f->run('call', ['sessionId' => $drafting, 'attemptId' => 'readiness-drafting-0001']);
    $terms = $f->present($id); $lines = array_column($terms['dossier']['lines'], 'line_digest'); $rationale = 'Separate exact synthetic mission approval.';
    $review = $f->run('review-mission', ['terms' => $terms, 'disposition' => 'APPROVE', 'lineDigests' => $lines, 'rationale' => $rationale,
        'decision' => $sign('APPROVE_MISSION_AND_CONSTITUTION', ['terms' => $terms, 'line_digests' => $lines, 'rationale' => $rationale])]);
    $f->run('reserve-mission', ['reviewId' => $review['review_id']]); $handoff = $f->run('deliver-handoff', ['intakeId' => $id]);
    $receiving = $grant('acceptance'); $f->transport->response = ['disposition' => 'ACCEPTED', 'rationale' => 'Synthetic exact original exchange received.', 'gaps' => '', 'dissent' => 'Deadline objection retained.'];
    $f->run('call', ['sessionId' => $receiving, 'attemptId' => 'readiness-receiving-0001']);
    $validation = $f->run('validate-step-one', ['intakeId' => $id]);
    echo json_encode(['schema' => 'imperium.citadel-readiness-proof/v1', 'synthetic_only' => true,
        'boundary' => ['network_calls' => 0, 'real_credentials' => false, 'activation' => false, 'mission_execution' => false],
        'inspection' => $inspection, 'preparation_transcript' => $transcript, 'signing_packets' => $packets,
        'calls' => $f->transport->calls, 'handoff' => $handoff, 'validation' => $validation, 'final_frame' => $f->journal->read()],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
} finally { $f->close(); }
