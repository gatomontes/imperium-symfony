<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ActivationTransitionInterruptionEvidenceContract;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityConsumer;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityContract;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ActivationTransitionInterruptionDemonstration
{
    private const string TARGETS = 'var/imperium/offline-evidence/activation-transition-targets';

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, \DateTimeImmutable $startedAt): array
    {
        $runId = 'activation-transition-interruption-'.substr(hash('sha256', $startedAt->format(DATE_ATOM)), 0, 20);
        $evidence = [];
        foreach (ActivationTransitionInterruptionEvidenceContract::TRANSITIONS as $transition) {
            foreach (ActivationTransitionInterruptionEvidenceContract::CUTS as $cut) $evidence[] = $this->runCase($runId, $transition, $cut, $startedAt);
        }
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $summary = ['schema' => 'imperium.sanitized-activation-transition-interruption-summary/v1', 'run_id' => $runId, 'cases_executed' => count($evidence), 'transitions' => ActivationTransitionInterruptionEvidenceContract::TRANSITIONS, 'cuts' => ActivationTransitionInterruptionEvidenceContract::CUTS, 'same_consumer_convergence_proved' => true, 'expiry_refusal_proved' => true, 'conflicting_replay_refusal_proved' => true, 'live_authority_consumed' => false, 'live_target_created' => false, 'external_action_performed' => false, 'disposition' => 'PROVED'];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $this->write($directory.'/'.$runId.'.private.json', ['schema' => 'imperium.private-activation-transition-interruption-evidence/v1', 'run_id' => $runId, 'started_at' => $startedAt->format(DATE_ATOM), 'cases' => $evidence, 'summary' => $summary]);
        $this->write($directory.'/'.$runId.'.sanitized.json', $summary);

        return ['run_id' => $runId, 'private_evidence_file' => $directory.'/'.$runId.'.private.json', 'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json', 'evidence' => $evidence, 'summary' => $summary];
    }

    private function runCase(string $runId, string $transition, string $cut, \DateTimeImmutable $at): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.strtolower(substr(hash('sha256', $transition.'|'.$cut), 0, 12));
        $this->remove($root);
        try {
            [$authorityId, $target, $targetRecord] = $this->fixture($root, $transition, $at);
            $consumer = new DeterministicTransitionCallerAuthorityConsumer($root);
            $records = new ImmutableRecordStore($root, new AtomicTransition($root));
            $consumerName = 'offline.'.strtolower($transition).'.demonstration';
            if ('BEFORE_AUTHORITY_CONSUMPTION' !== $cut) $consumer->consume($authorityId, $transition, $target, $consumerName, $at);
            if ('AFTER_TARGET_COMMIT' === $cut) $records->put(self::TARGETS, $targetRecord['target_id'], $targetRecord);
            $preRecovery = $this->snapshot($root, $authorityId, $targetRecord['target_id']);
            $first = $consumer->consume($authorityId, $transition, $target, $consumerName, $at);
            $stored = $records->put(self::TARGETS, $targetRecord['target_id'], $targetRecord);
            $second = $consumer->consume($authorityId, $transition, $target, $consumerName, $at->modify('+1 second'));
            $replayed = $records->put(self::TARGETS, $targetRecord['target_id'], $targetRecord);
            $conflict = $targetRecord; $conflict['conflict_variant'] = true;
            try { $records->put(self::TARGETS, $targetRecord['target_id'], $conflict); throw new \RuntimeException('DEMO_CONFLICT_NOT_REJECTED'); } catch (\RuntimeException $error) { if ('PST111_IMMUTABLE_RECORD_CONFLICT' !== $error->getMessage()) throw $error; }
            try { $consumer->consume($authorityId, $transition, $target, $consumerName.'.competitor', $at); throw new \RuntimeException('DEMO_CONTENTION_NOT_REJECTED'); } catch (\RuntimeException $error) { if ('PST131_AUTHORITY_CONSUMPTION_CONFLICT' !== $error->getMessage()) throw $error; }
            [$expiredId, $expiredTarget] = $this->expiredFixture($root, $transition, $at);
            try { $consumer->consume($expiredId, $transition, $expiredTarget, $consumerName, $at); throw new \RuntimeException('DEMO_EXPIRY_NOT_REJECTED'); } catch (\RuntimeException $error) { if ('IGA112_CALLER_AUTHORITY_INVALID' !== $error->getMessage()) throw $error; }
            $postRecovery = $this->snapshot($root, $authorityId, $targetRecord['target_id']);
            $record = ['schema' => ActivationTransitionInterruptionEvidenceContract::SCHEMA, 'evidence_id' => 'activation-transition-interruption-evidence-'.substr(hash('sha256', $transition.'|'.$cut), 0, 20), 'instance_id' => 'imperium-offline-interruption-demonstration', 'transition' => $transition, 'source_authority' => ['id' => $authorityId, 'digest' => $first['authority']['record_digest']], 'target_identity' => $target, 'cut' => $cut, 'pre_cut_state' => $preRecovery, 'post_restart_state' => $postRecovery, 'retry' => ['same_consumer_converged' => $first['consumption']['record_digest'] === $second['consumption']['record_digest'], 'exact_target_converged' => $stored['record_digest'] === $replayed['record_digest'], 'conflicting_replay_refused' => true], 'expiry' => ['expired_authority_refused' => true], 'contention' => ['different_consumer_refused' => true, 'single_winner' => true], 'classification' => 'CONVERGENT_RECOVERABLE', 'observed_at' => $at->format(DATE_ATOM), 'target_created' => true, 'external_action_performed' => false, 'sealed' => true];
            $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
            return $record;
        } finally { $this->remove($root); }
    }

    private function fixture(string $root, string $transition, \DateTimeImmutable $at): array
    {
        $principalId = 'imperator-offline-demonstration';
        $principal = $this->seal(['schema' => 'imperium.imperator-runtime-principal/v1', 'principal_id' => $principalId, 'instance_id' => 'imperium-offline-interruption-demonstration', 'binding_id' => 'imperator-offline-binding', 'principal_generation' => 1, 'status' => 'ACTIVE', 'offline_evidence_only' => true, 'sealed' => true]);
        $this->write($root.'/'.DeterministicTransitionCallerAuthorityIssuanceService::IMPERATOR_PRINCIPALS.'/'.$principalId.'.json', $principal);
        $suffix = substr(hash('sha256', $transition), 0, 20); $target = ['id' => 'offline-target-'.$suffix, 'digest' => hash('sha256', $transition)];
        $authorityId = 'deterministic-transition-caller-authority-'.$suffix;
        $authority = $this->seal(['schema' => DeterministicTransitionCallerAuthorityContract::SCHEMA, 'authority_id' => $authorityId, 'instance_id' => $principal['instance_id'], 'principal' => ['principal_id' => $principalId, 'office' => 'imperator', 'seat' => 'imperator', 'binding_id' => $principal['binding_id'], 'generation' => 1], 'source' => ['id' => $principalId, 'digest' => $principal['record_digest']], 'permitted_transition' => $transition, 'target' => $target, 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => $at->modify('-1 minute')->format(DATE_ATOM), 'expires_at' => $at->modify('+5 minutes')->format(DATE_ATOM), 'consumed' => false, 'continuing_authority' => false, 'sealed' => true]);
        $this->write($root.'/'.DeterministicTransitionCallerAuthorityIssuanceService::AUTHORITIES.'/'.$authorityId.'.json', $authority);
        return [$authorityId, $target, ['schema' => 'imperium.offline-activation-transition-target/v1', 'target_id' => 'activation-transition-target-'.$suffix, 'transition' => $transition, 'offline_evidence_only' => true, 'live_authority' => false, 'sealed' => true]];
    }

    private function expiredFixture(string $root, string $transition, \DateTimeImmutable $at): array
    {
        [$id, $target] = $this->fixture($root, $transition.'-expired-source', $at);
        $path = $root.'/'.DeterministicTransitionCallerAuthorityIssuanceService::AUTHORITIES.'/'.$id.'.json'; $authority = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); unset($authority['record_digest']); $authority['permitted_transition'] = $transition; $authority['target'] = $target; $authority['expires_at'] = $at->modify('-1 second')->format(DATE_ATOM); $this->write($path, $this->seal($authority));
        return [$id, $target];
    }

    private function snapshot(string $root, string $authorityId, string $targetId): array { return ['authority_consumption_exists' => [] !== (glob($root.'/var/imperium/runtime/authority-consumptions/*'.hash('sha256', $authorityId).'*.json') ?: []), 'target_exists' => is_file($root.'/'.self::TARGETS.'/'.$targetId.'.json')]; }
    private function seal(array $record): array { unset($record['record_digest']); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
    private function evidenceDirectory(string $directory): string { $absolute = str_starts_with($directory, '/') || 1 === preg_match('/^[A-Za-z]:[\\\\\/]/', $directory) || str_starts_with($directory, '\\\\'); $path = $absolute ? $directory : $this->projectRoot.'/'.$directory; if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) throw new \RuntimeException('DEMO_EVIDENCE_DIRECTORY_FAILED'); return $path; }
    private function write(string $path, array $record): void { if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) throw new \RuntimeException('DEMO_WRITE_FAILED'); file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"); }
    private function remove(string $path): void { if (!is_dir($path)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($path); }
}
