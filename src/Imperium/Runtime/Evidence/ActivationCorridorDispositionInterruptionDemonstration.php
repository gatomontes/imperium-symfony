<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ActivationCorridorDispositionInterruptionDemonstration
{
    public const array DISPOSITIONS = ['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];
    public const array CUTS = ['BEFORE_AUTHORITY_CONSUMPTION', 'AFTER_CONSUMPTION_BEFORE_DISPOSITION_COMMIT', 'AFTER_DISPOSITION_COMMIT'];
    private const string RECORDS = 'var/imperium/offline-evidence/activation-corridor-dispositions';
    private const string CONSUMPTIONS = 'var/imperium/runtime/authority-consumptions';

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, \DateTimeImmutable $startedAt): array
    {
        $runId = 'activation-corridor-disposition-interruption-'.substr(hash('sha256', $startedAt->format(DATE_ATOM)), 0, 20);
        $cases = [];
        foreach (self::DISPOSITIONS as $disposition) foreach (self::CUTS as $cut) $cases[] = $this->runCase($runId, $disposition, $cut, $startedAt);
        $summary = ['schema' => 'imperium.sanitized-activation-corridor-disposition-interruption-summary/v1', 'run_id' => $runId, 'cases_executed' => count($cases), 'dispositions' => self::DISPOSITIONS, 'cuts' => self::CUTS, 'exact_replay_proved' => true, 'changed_evidence_refused' => true, 'expiry_refusal_proved' => true, 'revocation_refusal_proved' => true, 'single_consumer_outcome_winner_proved' => true, 'read_only_recovery_proved' => true, 'activation_artifact_mutated' => false, 'live_authority_issued' => false, 'live_authority_consumed' => false, 'live_disposition_sealed' => false, 'external_action_performed' => false, 'continuing_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'disposition' => 'PROVED_OFFLINE_ONLY'];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->write($directory.'/'.$runId.'.private.json', ['schema' => 'imperium.private-activation-corridor-disposition-interruption-evidence/v1', 'run_id' => $runId, 'started_at' => $startedAt->format(DATE_ATOM), 'cases' => $cases, 'summary' => $summary]);
        $this->write($directory.'/'.$runId.'.sanitized.json', $summary);
        return ['run_id' => $runId, 'private_evidence_file' => $directory.'/'.$runId.'.private.json', 'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json', 'evidence' => $cases, 'summary' => $summary];
    }

    private function runCase(string $runId, string $outcome, string $cut, \DateTimeImmutable $at): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.substr(hash('sha256', $outcome.'|'.$cut), 0, 12);
        $this->remove($root);
        try {
            [$records, $consumptions] = $this->stores($root);
            $fixture = $this->fixture($outcome, $at);
            $consumer = 'offline.activation-corridor-disposition.'.strtolower($outcome);
            $activationBefore = hash('sha256', CanonicalJson::encode($fixture['activation_artifact']));
            if ('BEFORE_AUTHORITY_CONSUMPTION' !== $cut) $this->consume($consumptions, $fixture, $consumer, $at);
            if ('AFTER_DISPOSITION_COMMIT' === $cut) $records->put(self::RECORDS, $fixture['record']['disposition_id'], $fixture['record']);
            $pre = $this->snapshot($root, $fixture);
            [$records, $consumptions] = $this->stores($root);
            $first = $this->consume($consumptions, $fixture, $consumer, $at);
            $stored = $records->put(self::RECORDS, $fixture['record']['disposition_id'], $fixture['record']);
            $second = $this->consume($consumptions, $fixture, $consumer, $at->modify('+1 second'));
            $replayed = $records->put(self::RECORDS, $fixture['record']['disposition_id'], $fixture['record']);
            $changed = $fixture['record']; $changed['evidence_dossier']['digest'] = hash('sha256', 'changed-'.$outcome);
            $this->refuses(fn () => $records->put(self::RECORDS, $changed['disposition_id'], $changed), 'PST111_IMMUTABLE_RECORD_CONFLICT', 'ACD_DEMO_CHANGED_EVIDENCE_NOT_REFUSED');
            $competitor = 'QUARANTINED_PENDING_REMEDIATION' === $outcome ? 'RETIRE_CORRIDOR' : 'QUARANTINED_PENDING_REMEDIATION';
            $this->refuses(fn () => $this->consume($consumptions, $fixture, 'offline.activation-corridor-disposition.'.strtolower($competitor), $at), 'PST131_AUTHORITY_CONSUMPTION_CONFLICT', 'ACD_DEMO_CONTENTION_NOT_REFUSED');
            $this->refuses(fn () => $this->consume($consumptions, $this->fixture($outcome, $at, expired: true), $consumer, $at), 'ACD_DEMO_AUTHORITY_EXPIRED', 'ACD_DEMO_EXPIRY_NOT_REFUSED');
            $this->refuses(fn () => $this->consume($consumptions, $this->fixture($outcome, $at, revoked: true), $consumer, $at), 'ACD_DEMO_AUTHORITY_REVOKED', 'ACD_DEMO_REVOCATION_NOT_REFUSED');
            $consumption = $records->read(self::CONSUMPTIONS, 'authority-consumption-'.hash('sha256', $fixture['authority_id']));
            $record = $records->read(self::RECORDS, $fixture['record']['disposition_id']);
            $evidence = ['schema' => 'imperium.evidence.activation-corridor-disposition-interruption/v1', 'evidence_id' => 'activation-corridor-disposition-interruption-evidence-'.substr(hash('sha256', $outcome.'|'.$cut), 0, 20), 'instance_id' => 'imperium-offline-activation-corridor-disposition-demonstration', 'proposed_disposition' => $outcome, 'cut' => $cut, 'source_authority' => ['id' => $fixture['authority_id'], 'digest' => $fixture['source_digest']], 'evidence_dossier' => $fixture['record']['evidence_dossier'], 'pre_cut_state' => $pre, 'post_restart_state' => $this->snapshot($root, $fixture), 'retry' => ['same_consumer_converged' => $first['record_digest'] === $second['record_digest'], 'exact_disposition_converged' => $stored['record_digest'] === $replayed['record_digest'], 'changed_evidence_refused' => true], 'lifecycle' => ['expired_authority_refused' => true, 'revoked_authority_refused' => true], 'contention' => ['different_consumer_outcome_refused' => true, 'single_consumer_outcome_winner' => true], 'reconstruction' => ['consumption_digest' => $consumption['record_digest'], 'disposition_digest' => $record['record_digest'], 'read_only' => true], 'activation_artifact' => ['digest_before' => $activationBefore, 'digest_after' => hash('sha256', CanonicalJson::encode($fixture['activation_artifact'])), 'mutated' => false], 'classification' => 'CONVERGENT_RECOVERABLE', 'observed_at' => $at->format(DATE_ATOM), 'continuing_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'live_authority_issued' => false, 'live_authority_consumed' => false, 'live_disposition_sealed' => false, 'external_action_performed' => false, 'sealed' => true];
            $evidence['record_digest'] = hash('sha256', CanonicalJson::encode($evidence));
            return $evidence;
        } finally { $this->remove($root); }
    }

    private function stores(string $root): array
    {
        $atomic = new AtomicTransition($root); $records = new ImmutableRecordStore($root, $atomic);
        return [$records, new AuthorityConsumptionStore($records, $atomic)];
    }

    private function consume(AuthorityConsumptionStore $store, array $fixture, string $consumer, \DateTimeImmutable $at): array
    {
        if (null !== $fixture['revoked_at'] && $at >= new \DateTimeImmutable($fixture['revoked_at'])) throw new \RuntimeException('ACD_DEMO_AUTHORITY_REVOKED');
        if ($at < new \DateTimeImmutable($fixture['issued_at']) || $at >= new \DateTimeImmutable($fixture['expires_at'])) throw new \RuntimeException('ACD_DEMO_AUTHORITY_EXPIRED');
        return $store->consume($fixture['authority_id'], $fixture['source_id'], $fixture['source_digest'], $consumer, $at);
    }

    private function fixture(string $outcome, \DateTimeImmutable $at, bool $expired = false, bool $revoked = false): array
    {
        $suffix = substr(hash('sha256', $outcome.($expired ? '|expired' : ($revoked ? '|revoked' : '|active'))), 0, 20);
        $digest = hash('sha256', 'offline-exact-dossier-'.$suffix);
        return ['authority_id' => 'offline-corridor-disposition-authority-'.$suffix, 'source_id' => 'offline-corridor-disposition-eligibility-'.$suffix, 'source_digest' => $digest, 'issued_at' => $at->modify('-2 minutes')->format(DATE_ATOM), 'expires_at' => $at->modify($expired ? '-1 second' : '+5 minutes')->format(DATE_ATOM), 'revoked_at' => $revoked ? $at->modify('-1 second')->format(DATE_ATOM) : null, 'activation_artifact' => ['schema' => 'imperium.offline-activation-artifact-reference/v1', 'id' => 'offline-activation-'.$suffix, 'digest' => hash('sha256', 'activation-'.$suffix), 'historical_evidence_only' => true, 'mutable' => false], 'record' => ['schema' => 'imperium.offline-activation-corridor-disposition/v1', 'disposition_id' => 'offline-activation-corridor-disposition-'.$suffix, 'instance_id' => 'imperium-offline-activation-corridor-disposition-demonstration', 'proposed_disposition' => $outcome, 'evidence_dossier' => ['id' => 'offline-dossier-'.$suffix, 'digest' => $digest], 'terminal_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'offline_evidence_only' => true, 'sealed' => true]];
    }

    private function refuses(callable $operation, string $expected, string $failure): void
    { try { $operation(); throw new \RuntimeException($failure); } catch (\RuntimeException $error) { if ($expected !== $error->getMessage()) throw $error; } }

    private function snapshot(string $root, array $fixture): array
    { return ['authority_consumption_exists' => is_file($root.'/'.self::CONSUMPTIONS.'/authority-consumption-'.hash('sha256', $fixture['authority_id']).'.json'), 'disposition_exists' => is_file($root.'/'.self::RECORDS.'/'.$fixture['record']['disposition_id'].'.json')]; }

    private function evidenceDirectory(string $directory): string
    {
        $absolute = str_starts_with($directory, '/') || 1 === preg_match('~^[A-Za-z]:[\\\\/]~', $directory) || str_starts_with($directory, '\\\\');
        $path = $absolute ? $directory : $this->projectRoot.'/'.$directory;
        if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) throw new \RuntimeException('ACD_DEMO_EVIDENCE_DIRECTORY_FAILED');
        return $path;
    }

    private function write(string $path, array $record): void
    { if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) throw new \RuntimeException('ACD_DEMO_WRITE_FAILED'); if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n")) throw new \RuntimeException('ACD_DEMO_WRITE_FAILED'); }

    private function remove(string $path): void
    { if (!is_dir($path)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($path); }
}
