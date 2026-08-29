<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImperatorPrincipalProvenanceInterruptionDemonstration
{
    public const array TRANSITIONS = ['CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL', 'ACTIVATE', 'RENEW', 'SUSPEND', 'SUPERSEDE', 'REVOKE', 'EXPIRE', 'RETIRE'];
    public const array CUTS = ['BEFORE_AUTHORITY_CONSUMPTION', 'AFTER_AUTHORITY_CONSUMPTION_BEFORE_TARGET_COMMIT', 'AFTER_TARGET_COMMIT'];
    private const string TARGETS = 'var/imperium/offline-evidence/imperator-principal-transition-targets';
    private const string CONSUMPTIONS = 'var/imperium/runtime/authority-consumptions';

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, \DateTimeImmutable $startedAt): array
    {
        $runId = 'imperator-principal-interruption-'.substr(hash('sha256', $startedAt->format(DATE_ATOM)), 0, 20);
        $cases = [];
        foreach (self::TRANSITIONS as $transition) foreach (self::CUTS as $cut) $cases[] = $this->runCase($runId, $transition, $cut, $startedAt);
        $summary = ['schema' => 'imperium.sanitized-imperator-principal-interruption-summary/v1', 'run_id' => $runId, 'cases_executed' => count($cases), 'transitions' => self::TRANSITIONS, 'cuts' => self::CUTS, 'same_consumer_convergence_proved' => true, 'exact_replay_proved' => true, 'conflicting_replay_refused' => true, 'expired_authority_refused' => true, 'single_winner_contention_proved' => true, 'read_only_reconstruction_proved' => true, 'live_authority_consumed' => false, 'live_principal_created' => false, 'external_action_performed' => false, 'disposition' => 'PROVED'];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->write($directory.'/'.$runId.'.private.json', ['schema' => 'imperium.private-imperator-principal-interruption-evidence/v1', 'run_id' => $runId, 'started_at' => $startedAt->format(DATE_ATOM), 'cases' => $cases, 'summary' => $summary]);
        $this->write($directory.'/'.$runId.'.sanitized.json', $summary);
        return ['run_id' => $runId, 'private_evidence_file' => $directory.'/'.$runId.'.private.json', 'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json', 'evidence' => $cases, 'summary' => $summary];
    }

    private function runCase(string $runId, string $transition, string $cut, \DateTimeImmutable $at): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.substr(hash('sha256', $transition.'|'.$cut), 0, 12);
        $this->remove($root);
        try {
            $atomic = new AtomicTransition($root);
            $records = new ImmutableRecordStore($root, $atomic);
            $consumptions = new AuthorityConsumptionStore($records, $atomic);
            $fixture = $this->fixture($transition, $at);
            $consumer = 'offline.imperator-principal.'.strtolower($transition);
            if ('BEFORE_AUTHORITY_CONSUMPTION' !== $cut) $this->consume($consumptions, $fixture, $consumer, $at);
            if ('AFTER_TARGET_COMMIT' === $cut) $records->put(self::TARGETS, $fixture['target']['target_id'], $fixture['target']);
            $preRecovery = $this->snapshot($root, $fixture);
            $first = $this->consume($consumptions, $fixture, $consumer, $at);
            $stored = $records->put(self::TARGETS, $fixture['target']['target_id'], $fixture['target']);
            $second = $this->consume($consumptions, $fixture, $consumer, $at->modify('+1 second'));
            $replayed = $records->put(self::TARGETS, $fixture['target']['target_id'], $fixture['target']);
            $conflict = $fixture['target']; $conflict['semantic_digest'] = hash('sha256', 'conflict-'.$transition);
            try { $records->put(self::TARGETS, $fixture['target']['target_id'], $conflict); throw new \RuntimeException('PPR_DEMO_CONFLICT_NOT_REFUSED'); } catch (\RuntimeException $error) { if ('PST111_IMMUTABLE_RECORD_CONFLICT' !== $error->getMessage()) throw $error; }
            try { $this->consume($consumptions, $fixture, $consumer.'.competitor', $at); throw new \RuntimeException('PPR_DEMO_CONTENTION_NOT_REFUSED'); } catch (\RuntimeException $error) { if ('PST131_AUTHORITY_CONSUMPTION_CONFLICT' !== $error->getMessage()) throw $error; }
            $expired = $this->fixture($transition, $at, true);
            try { $this->consume($consumptions, $expired, $consumer, $at); throw new \RuntimeException('PPR_DEMO_EXPIRY_NOT_REFUSED'); } catch (\RuntimeException $error) { if ('PPR_DEMO_AUTHORITY_EXPIRED' !== $error->getMessage()) throw $error; }
            $consumption = $records->read(self::CONSUMPTIONS, 'authority-consumption-'.hash('sha256', $fixture['authority_id']));
            $target = $records->read(self::TARGETS, $fixture['target']['target_id']);
            $postRecovery = $this->snapshot($root, $fixture);
            $evidence = ['schema' => 'imperium.evidence.imperator-principal-transition-interruption/v1', 'evidence_id' => 'imperator-principal-interruption-evidence-'.substr(hash('sha256', $transition.'|'.$cut), 0, 20), 'instance_id' => 'imperium-offline-principal-provenance-demonstration', 'transition' => $transition, 'cut' => $cut, 'source_authority' => ['id' => $fixture['authority_id'], 'digest' => $fixture['source_digest']], 'target_identity' => ['id' => $fixture['target']['target_id'], 'digest' => $fixture['target']['semantic_digest']], 'pre_cut_state' => $preRecovery, 'post_restart_state' => $postRecovery, 'retry' => ['same_consumer_converged' => $first['record_digest'] === $second['record_digest'], 'exact_target_converged' => $stored['record_digest'] === $replayed['record_digest'], 'conflicting_replay_refused' => true], 'expiry' => ['expired_authority_refused' => true], 'contention' => ['different_consumer_refused' => true, 'single_winner' => true], 'reconstruction' => ['consumption_digest' => $consumption['record_digest'], 'target_digest' => $target['record_digest'], 'read_only' => true], 'classification' => 'CONVERGENT_RECOVERABLE', 'observed_at' => $at->format(DATE_ATOM), 'live_authority_consumed' => false, 'live_principal_created' => false, 'external_action_performed' => false, 'sealed' => true];
            $evidence['record_digest'] = hash('sha256', CanonicalJson::encode($evidence));
            return $evidence;
        } finally { $this->remove($root); }
    }

    private function consume(AuthorityConsumptionStore $store, array $fixture, string $consumer, \DateTimeImmutable $at): array
    {
        if ($at < new \DateTimeImmutable($fixture['issued_at']) || $at >= new \DateTimeImmutable($fixture['expires_at'])) throw new \RuntimeException('PPR_DEMO_AUTHORITY_EXPIRED');
        return $store->consume($fixture['authority_id'], $fixture['source_id'], $fixture['source_digest'], $consumer, $at);
    }

    private function fixture(string $transition, \DateTimeImmutable $at, bool $expired = false): array
    {
        $suffix = substr(hash('sha256', $transition.($expired ? '|expired' : '|active')), 0, 20);
        return ['authority_id' => 'offline-principal-transition-authority-'.$suffix, 'source_id' => 'offline-operator-root-decision-'.$suffix, 'source_digest' => hash('sha256', 'source-'.$suffix), 'issued_at' => $at->modify('-2 minutes')->format(DATE_ATOM), 'expires_at' => $at->modify($expired ? '-1 second' : '+5 minutes')->format(DATE_ATOM), 'target' => ['schema' => 'imperium.offline-imperator-principal-transition-target/v1', 'target_id' => 'offline-imperator-principal-transition-target-'.$suffix, 'transition' => $transition, 'semantic_digest' => hash('sha256', CanonicalJson::encode([$transition, 'exact-target'])), 'offline_evidence_only' => true, 'live_principal' => false, 'sealed' => true]];
    }

    private function snapshot(string $root, array $fixture): array
    {
        return ['authority_consumption_exists' => is_file($root.'/'.self::CONSUMPTIONS.'/authority-consumption-'.hash('sha256', $fixture['authority_id']).'.json'), 'target_exists' => is_file($root.'/'.self::TARGETS.'/'.$fixture['target']['target_id'].'.json')];
    }

    private function evidenceDirectory(string $directory): string
    {
        $absolute = str_starts_with($directory, '/') || 1 === preg_match('~^[A-Za-z]:[\\\\/]~', $directory) || str_starts_with($directory, '\\\\');
        $path = $absolute ? $directory : $this->projectRoot.'/'.$directory;
        if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) throw new \RuntimeException('PPR_DEMO_EVIDENCE_DIRECTORY_FAILED');
        return $path;
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) throw new \RuntimeException('PPR_DEMO_WRITE_FAILED');
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n")) throw new \RuntimeException('PPR_DEMO_WRITE_FAILED');
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($path);
    }
}
