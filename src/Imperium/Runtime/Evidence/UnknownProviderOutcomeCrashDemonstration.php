<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DelegateMissionTurnRecoveryService;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderInvocationRecoveryAssessmentService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UnknownProviderOutcomeCrashDemonstration
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, ?\DateTimeImmutable $startedAt = null): array
    {
        $startedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $sourceCommit = $this->sourceCommit();
        $fixture = $this->fixture();
        $fixtureDigest = hash('sha256', CanonicalJson::encode($fixture));
        $runId = 'provider-recovery-'.substr(hash('sha256', CanonicalJson::encode([
            $sourceCommit, $startedAt->format(DATE_ATOM), $fixtureDigest,
        ])), 0, 20);
        $unknown = $this->unknownOutcomeCase($runId, $fixture);
        $recovery = $this->sealedResponseRecoveryCase($runId, $fixture);
        $summary = [
            'schema' => 'imperium.sanitized-unknown-provider-outcome-crash-demonstration-summary/v1',
            'demonstration' => 'unknown-provider-outcome-recovery',
            'source_commit' => $sourceCommit,
            'boundaries_exercised' => 6,
            'properties_proved' => [
                'automatic_replay_prohibited_after_claim',
                'in_flight_outcome_classified_unknown',
                'duplicate_provider_start_rejected',
                'sealed_response_required_for_forward_recovery',
                'single_recovery_authority_consumed',
                'provider_free_turn_persistence',
                'conflicting_recovery_authorization_rejected',
            ],
            'provider_reinvoked' => false,
            'continuing_operational_authority' => false,
            'disposition' => 'PROVED',
        ];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $evidence = [
            'schema' => 'imperium.private-unknown-provider-outcome-crash-demonstration-evidence/v1',
            'demonstration_id' => 'crash-demonstration-3',
            'run_id' => $runId,
            'started_at' => $startedAt->format(DATE_ATOM),
            'finished_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'source_commit' => $sourceCommit,
            'runtime' => ['php_version' => PHP_VERSION, 'sapi' => PHP_SAPI],
            'fixture' => ['fixture_id' => 'unknown-provider-outcome-deterministic-v1', 'fixture_digest' => $fixtureDigest],
            'unknown_outcome_case' => $unknown,
            'sealed_response_recovery_case' => $recovery,
            'sanitized_summary' => $summary,
            'sanitized_summary_digest' => $summary['summary_digest'],
            'disposition' => 'PROVED',
        ];
        $evidence['evidence_record_digest'] = hash('sha256', CanonicalJson::encode($evidence));
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->write($directory.'/'.$runId.'.private.json', $evidence);
        $this->write($directory.'/'.$runId.'.sanitized.json', $summary);

        return [
            'run_id' => $runId,
            'private_evidence_file' => $directory.'/'.$runId.'.private.json',
            'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json',
            'summary' => $summary,
        ];
    }

    private function unknownOutcomeCase(string $runId, array $fixture): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-unknown';
        $this->remove($root);
        try {
            $claim = $this->seedBase($root, $fixture);
            $assessment = new ProviderInvocationRecoveryAssessmentService($root);
            $afterClaim = $assessment->assess($claim['claim_id']);
            $journal = new ProviderInvocationJournalService($root);
            $inFlight = $journal->start($claim, new \DateTimeImmutable('2026-08-26T14:00:00+00:00'));
            $duringIo = $assessment->assess($claim['claim_id']);
            try {
                $journal->start($claim, new \DateTimeImmutable('2026-08-26T14:00:01+00:00'));
                throw new \RuntimeException('DEMO_DUPLICATE_PROVIDER_START_NOT_REJECTED');
            } catch (\RuntimeException $error) {
                if ('CLV412_PROVIDER_INVOCATION_ALREADY_STARTED' !== $error->getMessage()) throw $error;
            }
            $unknown = $journal->markUnknown($claim, new \DateTimeImmutable('2026-08-26T14:00:02+00:00'));
            $recorded = $assessment->assess($claim['claim_id']);
            $assertions = [
                'claim_without_journal_requires_governed_resolution' => 'CLAIMED_WITHOUT_JOURNAL_GOVERNED_RESOLUTION_REQUIRED' === $afterClaim['status'],
                'automatic_replay_false_after_claim' => false === $afterClaim['automatic_replay_permitted'],
                'external_io_start_durable' => 'INVOCATION_IN_FLIGHT' === $inFlight['status'] && true === $inFlight['external_io_started'],
                'in_flight_outcome_is_unknown' => 'PROVIDER_OUTCOME_UNKNOWN_GOVERNED_RESOLUTION_REQUIRED' === $duringIo['status'] && true === $duringIo['provider_outcome_may_be_unknown'],
                'automatic_replay_false_in_flight' => false === $duringIo['automatic_replay_permitted'],
                'duplicate_provider_start_rejected' => true,
                'unknown_outcome_recorded' => 'PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED' === $unknown['status'],
                'unknown_outcome_remains_governed' => 'UNKNOWN_OUTCOME_RECORDED_GOVERNED_RESOLUTION_REQUIRED' === $recorded['status'],
                'no_response_envelope_invented' => 0 === count(glob($root.'/var/imperium/runtime/provider-response-envelopes/*.json') ?: []),
                'no_turn_invented' => 0 === count(glob($root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns/*.json') ?: []),
            ];
            $this->requireAll($assertions, 'DEMO_UNKNOWN_OUTCOME_INVARIANT_FAILED');
            return [
                'boundaries' => ['AFTER_CLAIM', 'AFTER_EXTERNAL_IO_START', 'AFTER_UNKNOWN_OUTCOME_CLASSIFICATION'],
                'after_claim' => $afterClaim,
                'during_io' => $duringIo,
                'unknown_recorded' => $recorded,
                'provider_start_attempts' => 2,
                'provider_start_accepted' => 1,
                'assertions' => $assertions,
                'disposition' => 'PROVED',
            ];
        } finally {
            $this->remove($root);
        }
    }

    private function sealedResponseRecoveryCase(string $runId, array $fixture): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-sealed-response';
        $this->remove($root);
        try {
            $claim = $this->seedBase($root, $fixture);
            $journal = new ProviderInvocationJournalService($root);
            $journal->start($claim, new \DateTimeImmutable('2026-08-26T14:00:00+00:00'));
            $envelope = (new ProviderResponseEnvelopeService($root))->seal($claim, $fixture['response'], new \DateTimeImmutable('2026-08-26T14:00:01+00:00'));
            $assessment = (new ProviderInvocationRecoveryAssessmentService($root))->assess($claim['claim_id']);
            $service = new DelegateMissionTurnRecoveryService($root);
            try {
                $service->recover($fixture['authorization_id'], new \DateTimeImmutable('2026-08-26T14:00:30+00:00'));
                throw new \RuntimeException('DEMO_MISSING_RECOVERY_AUTHORIZATION_ACCEPTED');
            } catch (\RuntimeException $error) {
                if ('CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID' !== $error->getMessage()) throw $error;
            }
            $authorization = $this->seedAuthorization($root, $fixture, $claim, $envelope);
            $turn = $service->recover($authorization['authorization_id'], new \DateTimeImmutable('2026-08-26T14:01:00+00:00'));
            $replay = $service->recover($authorization['authorization_id'], new \DateTimeImmutable('2026-08-26T14:02:00+00:00'));
            $alternate = $authorization;
            unset($alternate['record_digest']);
            $alternate['authorization_id'] = 'provider-turn-recovery-'.str_repeat('e', 20);
            $alternate['recovery_authority']['authority_id'] = 'provider-turn-recovery-authority-alternate';
            $alternate = $this->write($root.'/var/imperium/runtime/provider-turn-recovery-authorizations/'.$alternate['authorization_id'].'.json', $alternate);
            try {
                $service->recover($alternate['authorization_id'], new \DateTimeImmutable('2026-08-26T14:03:00+00:00'));
                throw new \RuntimeException('DEMO_CONFLICTING_RECOVERY_NOT_REJECTED');
            } catch (\RuntimeException $error) {
                if ('CT332_DELEGATE_TURN_RECOVERY_CONFLICT' !== $error->getMessage()) throw $error;
            }
            $journalRecord = $this->read($root.'/var/imperium/runtime/provider-invocation-journal/'.$claim['claim_id'].'.json');
            $assertions = [
                'sealed_envelope_changes_recovery_classification' => 'RESPONSE_ENVELOPE_SEALED_PENDING_JOURNAL_AND_TURN_RECOVERY' === $assessment['status'],
                'automatic_provider_replay_remains_false' => false === $assessment['automatic_replay_permitted'] && false === $envelope['automatic_provider_replay_permitted'],
                'missing_recovery_authorization_rejected' => true,
                'journal_response_identity_sealed' => 'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING' === $journalRecord['status'],
                'one_recovery_authority_consumed' => 1 === count(glob($root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []),
                'one_turn_persisted' => 1 === count(glob($root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns/*.json') ?: []),
                'provider_not_reinvoked' => false === $turn['recovery']['provider_reinvoked'],
                'provider_invocation_authority_false' => false === $turn['provider_invocation_authority'],
                'exact_replay_returns_same_turn' => $turn === $replay,
                'conflicting_authorization_rejected' => true,
                'credential_material_absent' => false === $envelope['credential_material_present'],
            ];
            $this->requireAll($assertions, 'DEMO_PROVIDER_FORWARD_RECOVERY_INVARIANT_FAILED');
            return [
                'boundaries' => ['AFTER_RESPONSE_ENVELOPE_SEAL', 'AFTER_RECOVERY_AUTHORITY_CONSUMPTION', 'AFTER_TURN_PERSISTENCE'],
                'assessment' => $assessment,
                'envelope' => ['id' => $envelope['envelope_id'], 'digest' => $envelope['record_digest'], 'response_identity' => $envelope['provider_response_identity']],
                'authorization' => ['id' => $authorization['authorization_id'], 'digest' => $authorization['record_digest']],
                'turn' => ['id' => $turn['turn_id'], 'digest' => $turn['record_digest'], 'provider_reinvoked' => $turn['recovery']['provider_reinvoked']],
                'assertions' => $assertions,
                'disposition' => 'PROVED',
            ];
        } finally {
            $this->remove($root);
        }
    }

    private function seedBase(string $root, array $fixture): array
    {
        $commission = $this->write($root.'/var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions/'.$fixture['commission_id'].'.json', ['commission_id' => $fixture['commission_id']]);
        $activation = $this->write($root.'/var/imperium/offices/clavium/delegate-mission-provider-invocation-activations/'.$fixture['activation_id'].'.json', [
            'activation_id' => $fixture['activation_id'], 'instance_id' => 'imperium-crash-demonstration-3',
            'source_commission' => ['id' => $fixture['commission_id'], 'digest' => $commission['record_digest']],
            'source_model_binding' => ['id' => 'model-binding-synthetic', 'digest' => str_repeat('1', 64)],
            'source_access_attestation' => ['id' => 'access-attestation-synthetic', 'digest' => str_repeat('2', 64)],
            'target' => ['commission_id' => $fixture['commission_id'], 'manifestation_id' => 'manifestation-demo-3', 'occupancy_generation' => 1],
            'model' => ['runtime_binding' => ['provider' => 'synthetic-demonstration-provider']],
        ]);
        return $this->write($root.'/var/imperium/runtime/provider-invocations/'.$fixture['claim_id'].'.json', [
            'schema' => 'imperium.clavium-provider-invocation-claim/v1', 'claim_id' => $fixture['claim_id'],
            'source_activation' => ['id' => $fixture['activation_id'], 'digest' => $activation['record_digest']],
            'target' => $activation['target'], 'lease_consumption' => ['lease_id' => 'synthetic-consumed-lease', 'consumed' => true],
            'turn_authority_consumption' => ['authority_id' => 'synthetic-consumed-turn-authority', 'consumed' => true],
            'provider_request' => ['idempotency_key' => 'imperium-'.$fixture['claim_id'], 'external_io_started' => false],
            'recovery' => ['automatic_replay_permitted' => false, 'unknown_outcome_requires_governed_resolution' => true],
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO', 'credential_material_present' => false,
        ]);
    }

    private function seedAuthorization(string $root, array $fixture, array $claim, array $envelope): array
    {
        return $this->write($root.'/var/imperium/runtime/provider-turn-recovery-authorizations/'.$fixture['authorization_id'].'.json', [
            'authorization_id' => $fixture['authorization_id'],
            'claim' => ['id' => $claim['claim_id'], 'digest' => $claim['record_digest']],
            'response_envelope' => ['id' => $envelope['envelope_id'], 'digest' => $envelope['record_digest']],
            'recovery_authority' => ['authority_id' => 'provider-turn-recovery-authority-demo-3', 'authority_single_use' => true, 'authority_exercisable' => true, 'consumed' => false, 'expires_at' => '2026-08-26T16:00:00+00:00'],
            'status' => 'AUTHORIZED_PENDING_PROVIDER_TURN_FORWARD_RECOVERY', 'provider_invocation_authority' => false,
        ]);
    }

    private function fixture(): array
    {
        return [
            'claim_id' => 'provider-invocation-'.str_repeat('a', 20),
            'activation_id' => 'delegate-mission-provider-invocation-activation-'.str_repeat('b', 20),
            'commission_id' => 'delegate-mission-bounded-cognition-commission-'.str_repeat('c', 20),
            'authorization_id' => 'provider-turn-recovery-'.str_repeat('d', 20),
            'response' => json_encode(['disposition' => 'COMPLETED', 'output' => 'Recovered sealed result.', 'evidence_references' => [], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => null], JSON_THROW_ON_ERROR),
        ];
    }

    private function requireAll(array $assertions, string $error): void
    {
        if (in_array(false, $assertions, true)) throw new \RuntimeException($error);
    }

    private function write(string $path, array $record): array
    {
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) throw new \RuntimeException('DEMO_STORAGE_CREATE_FAILED');
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX);
        return $record;
    }

    private function read(string $path): array { return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }

    private function sourceCommit(): string
    {
        $head = trim((string) file_get_contents($this->projectRoot.'/.git/HEAD'));
        if (str_starts_with($head, 'ref: ')) { $path=$this->projectRoot.'/.git/'.substr($head,5); if (is_file($path)) return trim((string)file_get_contents($path)); }
        return preg_match('/^[a-f0-9]{40}$/', $head) ? $head : 'UNRESOLVED';
    }

    private function evidenceDirectory(string $directory): string
    {
        $directory=trim(str_replace('\\','/',$directory)); if(''===$directory||str_contains($directory,'..'))throw new \InvalidArgumentException('DEMO_EVIDENCE_DIRECTORY_INVALID');
        $absolute=str_starts_with($directory,'/')||preg_match('/^[A-Za-z]:\//',$directory)?$directory:$this->projectRoot.'/'.$directory;
        if(!is_dir($absolute)&&!mkdir($absolute,0770,true)&&!is_dir($absolute))throw new \RuntimeException('DEMO_EVIDENCE_DIRECTORY_CREATE_FAILED'); return rtrim($absolute,'/');
    }

    private function remove(string $path): void
    {
        if(!is_dir($path))return; foreach(array_diff(scandir($path)?:[],['.','..'])as$entry){$child=$path.'/'.$entry;is_dir($child)?$this->remove($child):unlink($child);}rmdir($path);
    }
}
