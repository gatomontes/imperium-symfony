<?php
declare(strict_types=1);

/** OFFLINE ONLY: creates synthetic prerequisites in a new temporary root.
 * No environment credential resolver or HTTP transport is constructed. */
require dirname(__DIR__).'/vendor/autoload.php';

use App\Command\SourceReviewCommand;
use App\SourceReview\Transport;
use App\Tests\SourceReview\Fixture;
use Symfony\Component\Console\Tester\CommandTester;

$out = $argv[1] ?? '';
if ($out === '' || file_exists($out) || !mkdir($out, 0770, true)) { throw new RuntimeException('NEW_OUTPUT_DIRECTORY_REQUIRED'); }
$root = sys_get_temp_dir().'/imperium-source-review-demo-'.bin2hex(random_bytes(12));
$provider = new class implements Transport {
    public array $calls = [];
    public function send(string $secret, string $payload): string {
        $this->calls[] = $payload;
        return json_encode(['disposition' => 'FINDING', 'finding' => ['path' => 'Service.php', 'start_line' => 3, 'end_line' => 3, 'triggering_input' => 'add(2,3)', 'expected_behavior' => '5', 'actual_behavior' => '-1 by static evaluation', 'cause' => 'Subtraction used instead of addition.', 'impact' => 'Incorrect sum.', 'suggested_correction' => 'Replace subtraction with addition.', 'regression_case' => 'Unexecuted: assert add(2,3) is 5.'], 'rationale' => 'Synthetic recording-provider response, not model analysis.'], JSON_THROW_ON_ERROR);
    }
};
$f = new Fixture($root, $provider);
$tester = new CommandTester(new SourceReviewCommand($f->snapshots, $f->workflow));
$steps = [];
$run = function (string $action, array $values) use ($tester, &$steps): array {
    $exit = $tester->execute(['action' => $action, 'values' => $values]);
    if ($exit !== 0) { throw new RuntimeException($tester->getDisplay()); }
    $r = json_decode($tester->getDisplay(), true, 128, JSON_THROW_ON_ERROR);
    $steps[] = ['command' => 'imperium:source-review', 'action' => $action, 'arguments' => $values, 'exit_code' => $exit];
    return $r;
};
$input = $f->input(); $prepared = $run('prepare', [$input]); $p = $prepared['proposal'];
$inspected = $run('inspect', [$p['proposal_id']]);
if ($inspected !== $prepared || count($provider->calls) !== 0) { throw new RuntimeException('OFFLINE_PREPARATION_PROOF_FAILED'); }
$a = $run('authorize', [$p['proposal_id'], $prepared['proposal_digest_for_approval'], $f->transition, $f->seneschal]);
$lease = $run('lease', [$a['decision_id'], $f->locksmith]);
if (count($provider->calls) !== 0) { throw new RuntimeException('PRE_EXECUTION_PROVIDER_CALL'); }
$result = $run('execute', [$a['authorization_id'], $lease['lease_id']]);
$status = $run('status', [$a['authorization_id']]);
if ($provider->calls !== [$p['payload']]) { throw new RuntimeException('EXACT_DELIVERY_PROOF_FAILED'); }
$write = static function (string $name, mixed $value) use ($out): void { file_put_contents($out.'/'.$name, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"); };
$write('proposal.json', $prepared); $write('result.json', $result['output']);
$write('offline-proof.json', ['offline_only' => true, 'provider_calls' => count($provider->calls), 'exact_payload_sha256' => hash('sha256', $provider->calls[0]), 'status' => $status['progress']['result']['status'], 'commands' => $steps, 'real_provider_available_proved' => false, 'installed_isolation_proved' => false]);
foreach (['input.json', 'Service.php', 'behavior.txt'] as $file) { copy($root.'/input/'.$file, $out.'/'.$file); }
file_put_contents($out.'/outgoing-payload.json', $p['payload']);
$hashes = [];
foreach (['proposal.json', 'result.json', 'offline-proof.json', 'input.json', 'Service.php', 'behavior.txt', 'outgoing-payload.json'] as $file) { $hashes[$file] = hash_file('sha256', $out.'/'.$file); }
$write('sha256-manifest.json', ['algorithm' => 'sha256', 'files' => $hashes]);
echo json_encode(['offline_demo' => 'PASSED', 'public_packet' => realpath($out), 'manifest_sha256' => hash_file('sha256', $out.'/sha256-manifest.json'), 'synthetic_root_retained' => $root], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
