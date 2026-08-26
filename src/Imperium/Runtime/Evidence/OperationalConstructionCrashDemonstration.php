<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\DelegateMissionOperationalTransitionCoordinator;
use App\Imperium\Runtime\Conscription\OperationalTransitionFaultInjector;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalConstructionCrashDemonstration
{
    private const array CRASH_POINTS = [
        'BEFORE_QUALIFICATION_INDEXED',
        'QUALIFICATION_INDEXED',
        'BEFORE_ASSEMBLY_INDEXED',
        'ASSEMBLY_INDEXED',
        'BEFORE_BINDING_INDEXED',
        'BINDING_INDEXED',
    ];

    private const array PROHIBITED_AUTHORITY_FIELDS = [
        'deployment_authority',
        'custody_transfer_authority',
        'runtime_activation_authority',
        'cognition_authority',
        'provider_invocation_authority',
        'credential_use_authority',
        'tool_use_authority',
        'perimeter_crossing_authority',
        'external_action_authority',
        'execution_authority',
        'continuation_authority',
        'reuse_authority',
    ];

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot)
    {
    }

    public function run(string $evidenceDirectory, ?\DateTimeImmutable $startedAt = null): array
    {
        $startedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $sourceCommit = $this->sourceCommit();
        $fixture = $this->fixture();
        $fixtureDigest = hash('sha256', CanonicalJson::encode($fixture));
        $runId = 'oc-recovery-'.substr(hash('sha256', CanonicalJson::encode([
            $sourceCommit,
            $startedAt->format(DATE_ATOM),
            $fixtureDigest,
        ])), 0, 20);
        $cases = [];

        foreach (self::CRASH_POINTS as $crashPoint) {
            $cases[] = $this->runCase($runId, $crashPoint, $fixture);
        }
        $contention = $this->runContentionProof($runId);

        $finishedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $summary = [
            'schema' => 'imperium.sanitized-operational-construction-crash-demonstration-summary/v1',
            'demonstration' => 'operational-construction-recovery',
            'source_commit' => $sourceCommit,
            'cases_executed' => count($cases),
            'crash_boundaries_covered' => ['before-durable-index', 'after-durable-index'],
            'properties_proved' => [
                'ordered_immutable_records',
                'monotonic_generation',
                'deterministic_forward_recovery',
                'exact_replay_without_mutation',
                'single_winner_conflict_rejection',
                'inert_construction_boundary',
            ],
            'final_checkpoint_class' => 'inert-operational-construction-complete',
            'continuing_operational_authority' => false,
            'disposition' => 'PROVED',
        ];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $evidence = [
            'schema' => 'imperium.private-operational-construction-crash-demonstration-evidence/v1',
            'demonstration_id' => 'crash-demonstration-1',
            'run_id' => $runId,
            'started_at' => $startedAt->format(DATE_ATOM),
            'finished_at' => $finishedAt->format(DATE_ATOM),
            'source_commit' => $sourceCommit,
            'runtime' => ['php_version' => PHP_VERSION, 'sapi' => PHP_SAPI],
            'fixture' => ['fixture_id' => 'steps-44-46-deterministic-v1', 'fixture_digest' => $fixtureDigest],
            'cases' => $cases,
            'single_winner_contention' => $contention,
            'sanitized_summary' => $summary,
            'sanitized_summary_digest' => $summary['summary_digest'],
            'disposition' => 'PROVED',
        ];
        $evidence['evidence_record_digest'] = hash('sha256', CanonicalJson::encode($evidence));

        $directory = $this->absoluteEvidenceDirectory($evidenceDirectory);
        $this->writeJson($directory.'/'.$runId.'.private.json', $evidence);
        $this->writeJson($directory.'/'.$runId.'.sanitized.json', $summary);

        return [
            'run_id' => $runId,
            'private_evidence_file' => $directory.'/'.$runId.'.private.json',
            'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json',
            'summary' => $summary,
        ];
    }

    private function runCase(string $runId, string $crashPoint, array $fixture): array
    {
        $caseRoot = sys_get_temp_dir().'/imperium-'.$runId.'-'.strtolower($crashPoint);
        $this->remove($caseRoot);
        $fault = new class($crashPoint) implements OperationalTransitionFaultInjector {
            public function __construct(private string $selected) {}
            public function at(string $checkpoint): void
            {
                if ($checkpoint === $this->selected) {
                    throw new \RuntimeException('INJECTED_OPERATIONAL_TRANSITION_FAILURE');
                }
            }
        };
        $faulting = new DelegateMissionOperationalTransitionCoordinator($caseRoot, faults: $fault);
        $recovery = new DelegateMissionOperationalTransitionCoordinator($caseRoot);
        $stage = str_contains($crashPoint, 'QUALIFICATION') ? 0 : (str_contains($crashPoint, 'ASSEMBLY') ? 1 : 2);
        $methods = ['commitQualification', 'commitAssembly', 'commitBinding'];
        $records = [$fixture['qualification'], $fixture['assembly'], $fixture['binding']];
        $replayStages = [];

        try {
            for ($index = 0; $index < $stage; ++$index) {
                $replayStages[] = $this->commitAndReplay(
                    $recovery,
                    $caseRoot,
                    $methods[$index],
                    $records[$index],
                );
            }
            $preCrash = $this->snapshot($caseRoot);
            try {
                $faulting->{$methods[$stage]}($this->id($records[$stage]), $records[$stage]);
                throw new \RuntimeException('DEMO_EXPECTED_INJECTION_DID_NOT_OCCUR');
            } catch (\RuntimeException $error) {
                if ('INJECTED_OPERATIONAL_TRANSITION_FAILURE' !== $error->getMessage()) {
                    throw $error;
                }
                $failure = ['observed' => true, 'error' => $error->getMessage()];
            }
            $postCrash = $this->snapshot($caseRoot);
            $crashAssertions = $this->crashAssertions($crashPoint, $stage, $preCrash, $postCrash);
            for ($index = $stage; $index < count($methods); ++$index) {
                $replayStages[] = $this->commitAndReplay(
                    $recovery,
                    $caseRoot,
                    $methods[$index],
                    $records[$index],
                );
            }
            $recovered = $this->snapshot($caseRoot);
            $conflict = $fixture['qualification'];
            $conflict['demonstration_conflict_variant'] = true;
            unset($conflict['record_digest']);
            try {
                $recovery->commitQualification($conflict['qualification_id'], $conflict);
                throw new \RuntimeException('DEMO_CONFLICT_WAS_NOT_REJECTED');
            } catch (\RuntimeException $error) {
                if ('PST111_IMMUTABLE_RECORD_CONFLICT' !== $error->getMessage()) {
                    throw $error;
                }
                $conflictResult = ['rejected' => true, 'error' => $error->getMessage()];
            }
            $assertions = array_merge(
                $crashAssertions,
                $this->assertions($recovered, $records, $replayStages),
            );

            return [
                'crash_point' => $crashPoint,
                'pre_crash' => $preCrash,
                'injected_failure' => $failure,
                'post_crash' => $postCrash,
                'recovery' => ['resumed_forward' => true, 'final' => $recovered],
                'replay' => [
                    'exact' => true,
                    'stages' => $replayStages,
                    'generation_after' => $recovered['generation'],
                ],
                'conflict' => $conflictResult,
                'assertions' => $assertions,
                'disposition' => 'PROVED',
            ];
        } finally {
            $this->remove($caseRoot);
        }
    }

    private function commitAndReplay(
        DelegateMissionOperationalTransitionCoordinator $coordinator,
        string $root,
        string $method,
        array $record,
    ): array {
        $coordinator->{$method}($this->id($record), $record);
        $before = $this->snapshot($root);
        $coordinator->{$method}($this->id($record), $record);
        $after = $this->snapshot($root);
        $preserved = hash_equals($before['codex_digest'], $after['codex_digest'])
            && $before['generation'] === $after['generation'];
        if (!$preserved) {
            throw new \RuntimeException('DEMO_EXACT_REPLAY_MUTATED_CODEX');
        }

        return [
            'relation' => match ($method) {
                'commitQualification' => 'operational-profile-qualification',
                'commitAssembly' => 'operational-manifestation-assembly',
                'commitBinding' => 'operational-manifestation-seat-binding',
            },
            'generation' => $after['generation'],
            'digest_before' => $before['codex_digest'],
            'digest_after' => $after['codex_digest'],
            'preserved' => true,
        ];
    }

    private function assertions(array $snapshot, array $records, array $replayStages): array
    {
        $orderedIds = array_map(fn (array $record): string => $this->id($record), $records);
        $expectedDigests = array_column($records, 'record_digest');
        $authorityClosed = true;
        foreach ($records as $record) {
            foreach (self::PROHIBITED_AUTHORITY_FIELDS as $field) {
                $authorityClosed = $authorityClosed && true !== ($record[$field] ?? false);
            }
        }
        $assertions = [
            'final_generation_is_three' => 3 === $snapshot['generation'],
            'generation_monotonic' => [1, 2, 3] === $snapshot['folium_sequences'],
            'ordered_folium_identities_match' => $orderedIds === $snapshot['folium_ids'],
            'immutable_folium_digests_match' => $expectedDigests === $snapshot['folium_digests'],
            'exact_replay_preserved_each_current_codex' => [1, 2, 3] === array_column($replayStages, 'generation')
                && !in_array(false, array_column($replayStages, 'preserved'), true),
            'final_checkpoint_is_inert_binding' => 'DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION' === $snapshot['checkpoint'],
            'prohibited_authorities_absent_or_false' => $authorityClosed,
        ];
        if (in_array(false, $assertions, true)) {
            throw new \RuntimeException('DEMO_OPERATIONAL_CONSTRUCTION_INVARIANT_FAILED');
        }

        return $assertions;
    }

    private function crashAssertions(string $crashPoint, int $stage, array $preCrash, array $postCrash): array
    {
        $beforeIndex = str_starts_with($crashPoint, 'BEFORE_');
        $expectedPreCounts = [
            'qualification' => $stage >= 1 ? 1 : 0,
            'assembly' => $stage >= 2 ? 1 : 0,
            'binding' => 0,
        ];
        $expectedPostCounts = [
            'qualification' => 1,
            'assembly' => $stage >= 1 ? 1 : 0,
            'binding' => $stage >= 2 ? 1 : 0,
        ];
        $expectedPostGeneration = $beforeIndex ? $stage : $stage + 1;
        $assertions = [
            'pre_crash_stage_is_exact' => $expectedPreCounts === $preCrash['stored_folium_counts']
                && $stage === $preCrash['generation'],
            'interrupted_folium_is_durable' => $expectedPostCounts === $postCrash['stored_folium_counts'],
            'codex_boundary_is_exact' => $expectedPostGeneration === $postCrash['generation'],
        ];
        if (in_array(false, $assertions, true)) {
            throw new \RuntimeException('DEMO_CRASH_BOUNDARY_INVARIANT_FAILED');
        }

        return $assertions;
    }

    private function snapshot(string $root): array
    {
        $stored = [
            'qualification' => count(glob($root.'/var/imperium/offices/conscription/delegate-mission-operational-profile-qualifications/*.json') ?: []),
            'assembly' => count(glob($root.'/var/imperium/offices/conscription/delegate-mission-operational-manifestation-assemblies/*.json') ?: []),
            'binding' => count(glob($root.'/var/imperium/mission/occupancy/*.json') ?: []),
        ];
        $path = $root.'/var/imperium/codex-imperii.json';
        if (!is_file($path)) {
            return ['codex_present' => false, 'generation' => 0, 'checkpoint' => null, 'codex_digest' => null, 'folium_ids' => [], 'folium_digests' => [], 'folium_sequences' => [], 'stored_folium_counts' => $stored];
        }
        $codex = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return [
            'codex_present' => true,
            'generation' => $codex['generation'],
            'checkpoint' => $codex['current_checkpoint'],
            'codex_digest' => $codex['record_digest'],
            'folium_ids' => array_column($codex['folia'], 'folium_id'),
            'folium_digests' => array_column($codex['folia'], 'digest'),
            'folium_sequences' => array_column($codex['folia'], 'sequence'),
            'stored_folium_counts' => $stored,
        ];
    }

    private function runContentionProof(string $runId): array
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('DEMO_PROCESS_CONTENTION_UNAVAILABLE');
        }
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-contention';
        $this->remove($root);
        if (!mkdir($root, 0770, true) && !is_dir($root)) {
            throw new \RuntimeException('DEMO_CONTENTION_ROOT_CREATE_FAILED');
        }
        $gate = $root.'/go';
        $worker = $this->projectRoot.'/tests/fixtures/operational-folium-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        $pipes = [];
        try {
            for ($index = 0; $index < 2; ++$index) {
                $processes[$index] = proc_open([PHP_BINARY, $worker, $root, $gate, (string) $index], $descriptors, $pipes[$index]);
                if (!is_resource($processes[$index])) {
                    throw new \RuntimeException('DEMO_CONTENTION_PROCESS_START_FAILED');
                }
            }
            touch($gate);
            $results = [];
            foreach ($processes as $index => $process) {
                $stdout = stream_get_contents($pipes[$index][1]);
                $stderr = stream_get_contents($pipes[$index][2]);
                fclose($pipes[$index][1]);
                fclose($pipes[$index][2]);
                $exit = proc_close($process);
                if (0 !== $exit || '' !== $stderr) {
                    throw new \RuntimeException('DEMO_CONTENTION_PROCESS_FAILED');
                }
                $results[] = $stdout;
            }
            sort($results);
            $winnerCount = count(array_filter($results, static fn (string $result): bool => 'STORED' === $result));
            $loserCount = count(array_filter($results, static fn (string $result): bool => 'PST111_IMMUTABLE_RECORD_CONFLICT' === $result));
            if (1 !== $winnerCount || 1 !== $loserCount) {
                throw new \RuntimeException('DEMO_SINGLE_WINNER_INVARIANT_FAILED');
            }

            return [
                'workers' => 2,
                'winner_count' => $winnerCount,
                'conflict_count' => $loserCount,
                'single_winner_proved' => true,
            ];
        } finally {
            $this->remove($root);
        }
    }

    private function fixture(): array
    {
        $closed = array_fill_keys(self::PROHIBITED_AUTHORITY_FIELDS, false);
        $qualification = $this->seal(array_merge($closed, [
            'schema' => 'imperium.conscription-delegate-mission-operational-profile-qualification/v1',
            'qualification_id' => 'delegate-mission-operational-profile-qualification-'.str_repeat('a', 20),
            'instance_id' => 'imperium-crash-demonstration-1',
            'status' => 'DELEGATE_MISSION_PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY',
        ]));
        $assembly = $this->seal(array_merge($closed, [
            'schema' => 'imperium.conscription-delegate-mission-operational-manifestation-assembly/v1',
            'assembly_id' => 'delegate-mission-operational-manifestation-assembly-'.str_repeat('b', 20),
            'instance_id' => 'imperium-crash-demonstration-1',
            'source_qualification' => ['id' => $qualification['qualification_id'], 'digest' => $qualification['record_digest']],
            'status' => 'DELEGATE_MISSION_OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_MISSION_SEAT_BINDING',
        ]));
        $binding = $this->seal(array_merge($closed, [
            'schema' => 'imperium.delegate-mission-operational-manifestation-seat-binding/v1',
            'binding_id' => 'delegate-mission-operational-seat-binding-'.str_repeat('c', 20),
            'instance_id' => 'imperium-crash-demonstration-1',
            'source_assembly' => ['id' => $assembly['assembly_id'], 'digest' => $assembly['record_digest']],
            'occupancy_generation' => 1,
            'status' => 'DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION',
        ]));

        return compact('qualification', 'assembly', 'binding');
    }

    private function id(array $record): string
    {
        return $record['qualification_id'] ?? $record['assembly_id'] ?? $record['binding_id'];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function sourceCommit(): string
    {
        $head = trim((string) file_get_contents($this->projectRoot.'/.git/HEAD'));
        if (str_starts_with($head, 'ref: ')) {
            $reference = substr($head, 5);
            $path = $this->projectRoot.'/.git/'.$reference;
            if (is_file($path)) {
                return trim((string) file_get_contents($path));
            }
        }
        if (preg_match('/^[a-f0-9]{40}$/', $head)) {
            return $head;
        }

        return 'UNRESOLVED';
    }

    private function absoluteEvidenceDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory));
        if ('' === $directory || str_contains($directory, '..')) {
            throw new \InvalidArgumentException('DEMO_EVIDENCE_DIRECTORY_INVALID');
        }
        $absolute = str_starts_with($directory, '/') || preg_match('/^[A-Za-z]:\//', $directory)
            ? $directory
            : $this->projectRoot.'/'.$directory;
        if (!is_dir($absolute) && !mkdir($absolute, 0770, true) && !is_dir($absolute)) {
            throw new \RuntimeException('DEMO_EVIDENCE_DIRECTORY_CREATE_FAILED');
        }

        return rtrim($absolute, '/');
    }

    private function writeJson(string $path, array $payload): void
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($path, $json, LOCK_EX)) {
            throw new \RuntimeException('DEMO_EVIDENCE_WRITE_FAILED');
        }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
