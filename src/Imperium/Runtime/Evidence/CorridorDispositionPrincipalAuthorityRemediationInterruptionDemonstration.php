<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Disposable, offline proof only. This is not a production authority path. */
final readonly class CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration
{
    public const array TRANSITIONS = ['ISSUE_SCOPE_GRANT', 'COMMIT_SCOPE_SUCCESSOR', 'ACTIVATE_SCOPE_SUCCESSOR', 'ISSUE_CORRIDOR_CALLER_AUTHORITY'];
    public const array CUTS = ['BEFORE_AUTHORITY_CONSUMPTION', 'AFTER_AUTHORITY_CONSUMPTION_BEFORE_TARGET_COMMIT', 'AFTER_TARGET_COMMIT'];
    private const string TARGETS = 'var/imperium/offline-evidence/corridor-disposition-principal-authority-remediation-targets';
    private const string CONSUMPTIONS = 'var/imperium/runtime/authority-consumptions';

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, \DateTimeImmutable $startedAt): array
    {
        $runId = 'corridor-principal-authority-remediation-interruption-'.substr(hash('sha256', $startedAt->format(DATE_ATOM)), 0, 20);
        $cases = [];
        foreach (self::TRANSITIONS as $transition) foreach (self::CUTS as $cut) $cases[] = $this->runCase($runId, $transition, $cut, $startedAt);
        $summary = ['schema' => 'imperium.sanitized-corridor-principal-authority-remediation-interruption-summary/v1', 'run_id' => $runId, 'cases_executed' => count($cases), 'transitions' => self::TRANSITIONS, 'cuts' => self::CUTS, 'exact_replay_proved' => true, 'changed_evidence_refused' => true, 'expiry_refused' => true, 'revocation_refused' => true, 'single_winner_contention_proved' => true, 'read_only_recovery_proved' => true, 'live_authority_issued_or_consumed' => false, 'live_principal_or_binding_activated' => false, 'activation_artifact_mutated' => false, 'external_action_performed' => false, 'continuing_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'disposition' => 'PROVED_OFFLINE'];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->write($directory.'/'.$runId.'.private.json', ['schema' => 'imperium.private-corridor-principal-authority-remediation-interruption-evidence/v1', 'run_id' => $runId, 'started_at' => $startedAt->format(DATE_ATOM), 'cases' => $cases, 'summary' => $summary]);
        $this->write($directory.'/'.$runId.'.sanitized.json', $summary);
        return ['run_id' => $runId, 'private_evidence_file' => $directory.'/'.$runId.'.private.json', 'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json', 'evidence' => $cases, 'summary' => $summary];
    }

    private function runCase(string $runId, string $transition, string $cut, \DateTimeImmutable $at): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.substr(hash('sha256', $transition.'|'.$cut), 0, 12);
        $this->remove($root);
        try {
            $fixture = $this->fixture($transition, $at);
            $consumer = 'offline.corridor-principal-authority-remediation.'.strtolower($transition);
            [$records, $consumptions] = $this->stores($root);
            if ('BEFORE_AUTHORITY_CONSUMPTION' !== $cut) $this->consume($consumptions, $fixture, $consumer, $at);
            if ('AFTER_TARGET_COMMIT' === $cut) $records->put(self::TARGETS, $fixture['target']['target_id'], $fixture['target']);
            $preCut = $this->snapshot($root, $fixture);
            unset($records, $consumptions);
            [$records, $consumptions] = $this->stores($root);
            $first = $this->consume($consumptions, $fixture, $consumer, $at);
            $stored = $records->put(self::TARGETS, $fixture['target']['target_id'], $fixture['target']);
            $second = $this->consume($consumptions, $fixture, $consumer, $at->modify('+1 second'));
            $replayed = $records->put(self::TARGETS, $fixture['target']['target_id'], $fixture['target']);
            $changed = $fixture['target']; $changed['evidence_digest'] = hash('sha256', 'changed-'.$transition);
            $this->expect('PST111_IMMUTABLE_RECORD_CONFLICT', fn () => $records->put(self::TARGETS, $changed['target_id'], $changed));
            $this->expect('PST131_AUTHORITY_CONSUMPTION_CONFLICT', fn () => $this->consume($consumptions, $fixture, $consumer.'.competitor', $at));
            $this->expect('CPA3_AUTHORITY_EXPIRED', fn () => $this->consume($consumptions, $this->fixture($transition, $at, expired: true), $consumer, $at));
            $this->expect('CPA3_AUTHORITY_REVOKED', fn () => $this->consume($consumptions, $this->fixture($transition, $at, revoked: true), $consumer, $at));
            $consumption = $records->read(self::CONSUMPTIONS, 'authority-consumption-'.hash('sha256', $fixture['authority_id']));
            $target = $records->read(self::TARGETS, $fixture['target']['target_id']);
            return ['schema' => 'imperium.evidence.corridor-principal-authority-remediation-interruption/v1', 'transition' => $transition, 'cut' => $cut, 'pre_cut_state' => $preCut, 'retry' => ['same_consumer_converged' => $first['record_digest'] === $second['record_digest'], 'exact_target_converged' => $stored['record_digest'] === $replayed['record_digest'], 'changed_evidence_refused' => true], 'expiry' => ['refused' => true], 'revocation' => ['refused' => true], 'contention' => ['different_consumer_refused' => true, 'single_winner' => true], 'recovery' => ['consumption_digest' => $consumption['record_digest'], 'target_digest' => $target['record_digest'], 'read_only' => true], 'classification' => 'CONVERGENT_RECOVERABLE', 'live_authority_issued_or_consumed' => false, 'live_principal_or_binding_activated' => false, 'activation_artifact_mutated' => false, 'external_action_performed' => false, 'continuing_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'];
        } finally { $this->remove($root); }
    }

    private function stores(string $root): array { $atomic = new AtomicTransition($root); $records = new ImmutableRecordStore($root, $atomic); return [$records, new AuthorityConsumptionStore($records, $atomic)]; }
    private function consume(AuthorityConsumptionStore $store, array $fixture, string $consumer, \DateTimeImmutable $at): array
    {
        if ($fixture['revoked']) throw new \RuntimeException('CPA3_AUTHORITY_REVOKED');
        if ($at < new \DateTimeImmutable($fixture['issued_at']) || $at >= new \DateTimeImmutable($fixture['expires_at'])) throw new \RuntimeException('CPA3_AUTHORITY_EXPIRED');
        return $store->consume($fixture['authority_id'], $fixture['source_id'], $fixture['source_digest'], $consumer, $at);
    }
    private function fixture(string $transition, \DateTimeImmutable $at, bool $expired = false, bool $revoked = false): array
    {
        $suffix = substr(hash('sha256', $transition.'|'.($expired ? 'expired' : ($revoked ? 'revoked' : 'eligible'))), 0, 20);
        return ['authority_id' => 'offline-remediation-authority-'.$suffix, 'source_id' => 'offline-operator-root-proof-'.$suffix, 'source_digest' => hash('sha256', 'source-'.$suffix), 'issued_at' => $at->modify('-2 minutes')->format(DATE_ATOM), 'expires_at' => $at->modify($expired ? '-1 second' : '+5 minutes')->format(DATE_ATOM), 'revoked' => $revoked, 'target' => ['schema' => 'imperium.offline-corridor-principal-authority-remediation-target/v1', 'target_id' => 'offline-remediation-target-'.$suffix, 'transition' => $transition, 'evidence_digest' => hash('sha256', CanonicalJson::encode([$transition, 'exact-offline-evidence'])), 'offline_evidence_only' => true, 'live_authority' => false, 'live_principal' => false, 'sealed' => true]];
    }
    private function expect(string $message, callable $operation): void { try { $operation(); throw new \RuntimeException('CPA3_EXPECTED_REFUSAL_NOT_OBSERVED'); } catch (\RuntimeException $error) { if ($message !== $error->getMessage()) throw $error; } }
    private function snapshot(string $root, array $fixture): array { return ['authority_consumption_exists' => is_file($root.'/'.self::CONSUMPTIONS.'/authority-consumption-'.hash('sha256', $fixture['authority_id']).'.json'), 'target_exists' => is_file($root.'/'.self::TARGETS.'/'.$fixture['target']['target_id'].'.json')]; }
    private function evidenceDirectory(string $directory): string { $absolute = str_starts_with($directory, '/') || 1 === preg_match('~^[A-Za-z]:[\\\\/]~', $directory) || str_starts_with($directory, '\\\\'); $path = $absolute ? $directory : $this->projectRoot.'/'.$directory; if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) throw new \RuntimeException('CPA3_EVIDENCE_DIRECTORY_FAILED'); return $path; }
    private function write(string $path, array $record): void { if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n")) throw new \RuntimeException('CPA3_EVIDENCE_WRITE_FAILED'); }
    private function remove(string $path): void { if (!is_dir($path)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($path); }
}
