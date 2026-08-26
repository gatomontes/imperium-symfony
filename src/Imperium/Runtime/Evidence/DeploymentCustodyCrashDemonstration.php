<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\DelegateMissionDeploymentCustodyTransitionCoordinator;
use App\Imperium\Runtime\Garrison\DeploymentCustodyTransitionFaultInjector;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeploymentCustodyCrashDemonstration
{
    private const array CHECKPOINTS = ['PREPARED', 'CUSTODY_DEPLOYED', 'TRANSITION_RECORDED', 'COMPLETE'];
    private const array PROHIBITED = [
        'mission_activation_authority', 'cognition_authority', 'provider_invocation_authority',
        'credential_use_authority', 'tool_use_authority', 'perimeter_crossing_authority',
        'external_action_authority', 'execution_authority', 'continuing_turn_authority',
    ];

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, ?\DateTimeImmutable $startedAt = null): array
    {
        $startedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $fixture = $this->fixture();
        $sourceCommit = $this->sourceCommit();
        $fixtureDigest = hash('sha256', CanonicalJson::encode($fixture));
        $runId = 'deployment-recovery-'.substr(hash('sha256', CanonicalJson::encode([
            $sourceCommit, $startedAt->format(DATE_ATOM), $fixtureDigest,
        ])), 0, 20);
        $cases = array_map(fn (string $checkpoint): array => $this->runCase($runId, $checkpoint, $fixture), self::CHECKPOINTS);
        $contention = $this->runContention($runId, $fixture);
        $summary = [
            'schema' => 'imperium.sanitized-deployment-custody-crash-demonstration-summary/v1',
            'demonstration' => 'deployment-custody-recovery',
            'source_commit' => $sourceCommit,
            'cases_executed' => count($cases),
            'properties_proved' => [
                'deterministic_forward_recovery', 'custody_compare_and_swap',
                'single_immutable_transition', 'exact_completed_replay',
                'single_winner_conflict_rejection', 'inactive_deployment_boundary',
            ],
            'final_state_class' => 'deployed-and-bound-inactive',
            'runtime_activation_created' => false,
            'continuing_operational_authority' => false,
            'disposition' => 'PROVED',
        ];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $evidence = [
            'schema' => 'imperium.private-deployment-custody-crash-demonstration-evidence/v1',
            'demonstration_id' => 'crash-demonstration-2',
            'run_id' => $runId,
            'started_at' => $startedAt->format(DATE_ATOM),
            'finished_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'source_commit' => $sourceCommit,
            'runtime' => ['php_version' => PHP_VERSION, 'sapi' => PHP_SAPI],
            'fixture' => ['fixture_id' => 'deployment-custody-deterministic-v1', 'fixture_digest' => $fixtureDigest],
            'cases' => $cases,
            'single_winner_contention' => $contention,
            'sanitized_summary' => $summary,
            'sanitized_summary_digest' => $summary['summary_digest'],
            'disposition' => 'PROVED',
        ];
        $evidence['evidence_record_digest'] = hash('sha256', CanonicalJson::encode($evidence));
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->writeJson($directory.'/'.$runId.'.private.json', $evidence);
        $this->writeJson($directory.'/'.$runId.'.sanitized.json', $summary);

        return [
            'run_id' => $runId,
            'private_evidence_file' => $directory.'/'.$runId.'.private.json',
            'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json',
            'summary' => $summary,
        ];
    }

    private function runCase(string $runId, string $checkpoint, array $fixture): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.strtolower($checkpoint);
        $this->remove($root);
        $this->writeJson($root.'/var/imperium/offices/garrison/custody/'.$fixture['prior']['custody_id'].'.json', $fixture['prior']);
        $fault = new class($checkpoint) implements DeploymentCustodyTransitionFaultInjector {
            public function __construct(private string $selected) {}
            public function after(string $checkpoint): void
            {
                if ($checkpoint === $this->selected) {
                    throw new \RuntimeException('INJECTED_DEPLOYMENT_FAILURE');
                }
            }
        };
        try {
            try {
                (new DelegateMissionDeploymentCustodyTransitionCoordinator($root, faults: $fault))->run(
                    $fixture['authorization_id'], $fixture['transition_id'], $fixture['transition'],
                    $fixture['prior'], $fixture['deployed'],
                );
                throw new \RuntimeException('DEMO_EXPECTED_INJECTION_DID_NOT_OCCUR');
            } catch (\RuntimeException $error) {
                if ('INJECTED_DEPLOYMENT_FAILURE' !== $error->getMessage()) {
                    throw $error;
                }
            }
            $interrupted = $this->snapshot($root, $fixture);
            $coordinator = new DelegateMissionDeploymentCustodyTransitionCoordinator($root);
            $record = $coordinator->resumeForAuthorization($fixture['authorization_id']);
            $recovered = $this->snapshot($root, $fixture);
            $coordinator->run(
                $fixture['authorization_id'], $fixture['transition_id'], $fixture['transition'],
                $fixture['prior'], $fixture['deployed'],
            );
            $replayed = $this->snapshot($root, $fixture);
            $conflict = $fixture['transition'];
            $conflict['demonstration_conflict_variant'] = true;
            try {
                $coordinator->run(
                    $fixture['authorization_id'], $fixture['transition_id'], $conflict,
                    $fixture['prior'], $fixture['deployed'],
                );
                throw new \RuntimeException('DEMO_CONFLICT_WAS_NOT_REJECTED');
            } catch (\RuntimeException $error) {
                if ('GA249_DELEGATE_MISSION_CUSTODY_CONFLICT' !== $error->getMessage()) {
                    throw $error;
                }
            }
            $assertions = $this->assertions($checkpoint, $fixture, $record, $interrupted, $recovered, $replayed);

            return [
                'crash_point' => $checkpoint,
                'injected_failure_observed' => true,
                'interrupted' => $interrupted,
                'recovery' => ['resumed_forward' => true, 'final' => $recovered],
                'replay' => ['exact' => true, 'before' => $recovered, 'after' => $replayed],
                'conflict' => ['rejected' => true, 'error' => 'GA249_DELEGATE_MISSION_CUSTODY_CONFLICT'],
                'assertions' => $assertions,
                'disposition' => 'PROVED',
            ];
        } finally {
            $this->remove($root);
        }
    }

    private function assertions(
        string $checkpoint,
        array $fixture,
        ?array $record,
        array $interrupted,
        array $recovered,
        array $replayed,
    ): array
    {
        $authorityClosed = true;
        foreach (self::PROHIBITED as $field) {
            $authorityClosed = $authorityClosed && true !== ($fixture['transition'][$field] ?? false);
        }
        $expectedInterruption = match ($checkpoint) {
            'PREPARED' => ['ADMITTED_HELD', true, 0],
            'CUSTODY_DEPLOYED' => ['DELEGATE_MISSION_DEPLOYED_BOUND', false, 0],
            'TRANSITION_RECORDED', 'COMPLETE' => ['DELEGATE_MISSION_DEPLOYED_BOUND', false, 1],
        };
        $assertions = [
            'selected_checkpoint_was_durable' => $checkpoint === $interrupted['checkpoint'],
            'interruption_state_matches_matrix' => $expectedInterruption === [
                $interrupted['custody_state'],
                $interrupted['custody_available'],
                $interrupted['transition_count'],
            ],
            'transaction_completed' => 'COMPLETE' === $recovered['checkpoint'],
            'custody_deployed_and_unavailable' => 'DELEGATE_MISSION_DEPLOYED_BOUND' === $recovered['custody_state']
                && false === $recovered['custody_available'],
            'one_transition_recorded' => 1 === $recovered['transition_count'],
            'transition_identity_matches' => $fixture['transition_id'] === ($record['transition_id'] ?? null),
            'exact_replay_preserved_transaction' => $recovered['transaction_digest'] === $replayed['transaction_digest'],
            'exact_replay_preserved_custody' => $recovered['custody_digest'] === $replayed['custody_digest'],
            'runtime_activation_absent' => 0 === $recovered['runtime_activation_count'],
            'prohibited_authorities_absent_or_false' => $authorityClosed,
        ];
        if (in_array(false, $assertions, true)) {
            throw new \RuntimeException('DEMO_DEPLOYMENT_CUSTODY_INVARIANT_FAILED');
        }

        return $assertions;
    }

    private function snapshot(string $root, array $fixture): array
    {
        $transactionPath = $root.'/var/imperium/runtime/delegate-mission-deployment-custody-transitions/'.$fixture['transition_id'].'.json';
        $custodyPath = $root.'/var/imperium/offices/garrison/custody/'.$fixture['prior']['custody_id'].'.json';
        $transaction = $this->read($transactionPath);
        $custody = $this->read($custodyPath);

        return [
            'interruption_origin' => $transaction['checkpoint'],
            'checkpoint' => $transaction['checkpoint'],
            'transaction_digest' => $transaction['record_digest'],
            'custody_state' => $custody['custody_state'],
            'custody_available' => $custody['available'],
            'custody_digest' => $custody['record_digest'],
            'transition_count' => count(glob($root.'/var/imperium/offices/garrison/delegate-mission-operational-custody-transitions/*.json') ?: []),
            'runtime_activation_count' => count(glob($root.'/var/imperium/mission/delegate-mission-runtime-activations/*.json') ?: []),
        ];
    }

    private function runContention(string $runId, array $fixture): array
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('DEMO_PROCESS_CONTENTION_UNAVAILABLE');
        }
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-contention';
        $this->remove($root);
        $this->writeJson($root.'/var/imperium/offices/garrison/custody/'.$fixture['prior']['custody_id'].'.json', $fixture['prior']);
        $fixturePath = $root.'/fixture.json';
        $this->writeJson($fixturePath, $fixture);
        $gate = $root.'/go';
        $worker = $this->projectRoot.'/tests/fixtures/deployment-custody-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        try {
            for ($index = 0; $index < 2; ++$index) {
                $processes[$index] = proc_open([PHP_BINARY, $worker, $root, $fixturePath, $gate, (string) $index], $descriptors, $pipes[$index]);
                if (!is_resource($processes[$index])) {
                    throw new \RuntimeException('DEMO_CONTENTION_PROCESS_START_FAILED');
                }
            }
            touch($gate);
            $results = [];
            foreach ($processes as $index => $process) {
                $results[] = stream_get_contents($pipes[$index][1]);
                $stderr = stream_get_contents($pipes[$index][2]);
                fclose($pipes[$index][1]);
                fclose($pipes[$index][2]);
                if (0 !== proc_close($process) || '' !== $stderr) {
                    throw new \RuntimeException('DEMO_CONTENTION_PROCESS_FAILED');
                }
            }
            sort($results);
            if (['GA249_DELEGATE_MISSION_CUSTODY_CONFLICT', 'STORED'] !== $results) {
                throw new \RuntimeException('DEMO_SINGLE_WINNER_INVARIANT_FAILED');
            }

            return ['workers' => 2, 'winner_count' => 1, 'conflict_count' => 1, 'single_winner_proved' => true];
        } finally {
            $this->remove($root);
        }
    }

    private function fixture(): array
    {
        $prior = $this->seal(['schema' => 'imperium.garrison-persona-custody/v1', 'custody_id' => 'custody-demonstration-2', 'custody_state' => 'ADMITTED_HELD', 'available' => true, 'execution_authority' => false]);
        $deployed = $prior;
        unset($deployed['record_digest']);
        $deployed['custody_state'] = 'DELEGATE_MISSION_DEPLOYED_BOUND';
        $deployed['available'] = false;
        $deployed['operational_custodian'] = ['office' => 'mission', 'seat' => 'mission.delegate.demo', 'manifestation_id' => 'manifestation-demo-2'];
        $deployed = $this->seal($deployed);
        $closed = array_fill_keys(self::PROHIBITED, false);
        $transition = array_merge($closed, [
            'schema' => 'imperium.garrison-delegate-mission-operational-custody-transition/v1',
            'transition_id' => 'delegate-mission-operational-custody-transition-'.str_repeat('d', 20),
            'instance_id' => 'imperium-crash-demonstration-2',
            'source_deployment_authorization' => ['id' => 'delegate-mission-deployment-authorization-'.str_repeat('a', 20), 'digest' => str_repeat('a', 64)],
            'operational_custody' => ['id' => $deployed['custody_id'], 'digest' => $deployed['record_digest'], 'state' => $deployed['custody_state'], 'available' => false],
            'status' => 'DELEGATE_MISSION_DEPLOYED_CUSTODY_TRANSITIONED_PENDING_MISSION_ACTIVATION',
            'deployed' => true,
            'operational_use_permitted' => false,
        ]);

        return [
            'authorization_id' => $transition['source_deployment_authorization']['id'],
            'transition_id' => $transition['transition_id'],
            'prior' => $prior,
            'deployed' => $deployed,
            'transition' => $transition,
        ];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function read(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function sourceCommit(): string
    {
        $head = trim((string) file_get_contents($this->projectRoot.'/.git/HEAD'));
        if (str_starts_with($head, 'ref: ')) {
            $path = $this->projectRoot.'/.git/'.substr($head, 5);
            if (is_file($path)) {
                return trim((string) file_get_contents($path));
            }
        }
        return preg_match('/^[a-f0-9]{40}$/', $head) ? $head : 'UNRESOLVED';
    }

    private function evidenceDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory));
        if ('' === $directory || str_contains($directory, '..')) {
            throw new \InvalidArgumentException('DEMO_EVIDENCE_DIRECTORY_INVALID');
        }
        $absolute = str_starts_with($directory, '/') || preg_match('/^[A-Za-z]:\//', $directory)
            ? $directory : $this->projectRoot.'/'.$directory;
        if (!is_dir($absolute) && !mkdir($absolute, 0770, true) && !is_dir($absolute)) {
            throw new \RuntimeException('DEMO_EVIDENCE_DIRECTORY_CREATE_FAILED');
        }
        return rtrim($absolute, '/');
    }

    private function writeJson(string $path, array $record): void
    {
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) {
            throw new \RuntimeException('DEMO_EVIDENCE_DIRECTORY_CREATE_FAILED');
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('DEMO_EVIDENCE_WRITE_FAILED');
        }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
