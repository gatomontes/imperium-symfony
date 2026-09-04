<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;

/** Deterministic, fixture-only execution of the canonical harmless reference mission. */
final class CanonicalRepositoryInspectionMission
{
    public const array TRANSITIONS = [
        ['PROPOSED', 'authorize', 'operator-authorizer', 'mission', 'AUTHORIZED'],
        ['AUTHORIZED', 'admit', 'mission-admission-controller', 'snapshot', 'ADMITTED'],
        ['ADMITTED', 'inspect', 'repository-inspector', 'snapshot', 'EXECUTING'],
        ['EXECUTING', 'assemble-evidence', 'evidence-assembler', 'mission', 'EVIDENCE_ASSEMBLED'],
        ['EVIDENCE_ASSEMBLED', 'complete', 'mission-dispositioner', 'mission', 'COMPLETED'],
    ];

    public function __construct(private readonly string $root) {}

    /** @param array{commit:string,files:array<string,string>} $snapshot */
    public function run(AcceptedMission $accepted, array $snapshot, int $at): array
    {
        $dossier = $accepted->dossier;
        $missionId = $dossier->missionId();
        if (is_file($this->statusPath($missionId))) {
            $existing = $this->status($missionId);
            if (MissionState::from($existing['state'])->terminal()) {
                throw new \RuntimeException('MIS304_MISSION_TERMINAL');
            }
            throw new \RuntimeException('MIS305_MISSION_ALREADY_ACTIVE');
        }
        $history = [];
        $consumptions = [];
        $state = MissionState::PROPOSED;
        $startedAt = $at;
        $this->project($dossier, $state, $history, $consumptions, 'awaiting authorization');

        try {
            $dossier->validateAt($at);
            if (($snapshot['commit'] ?? null) !== $dossier->targetSnapshot()) {
                throw new \RuntimeException('MIS306_TARGET_SNAPSHOT_MISMATCH');
            }
            $budget = $dossier->toArray()['resource_budget'];
            if (!is_array($snapshot['files'] ?? null) || count($snapshot['files']) > ($budget['max_files'] ?? -1)) {
                throw new \RuntimeException('MIS307_RESOURCE_BUDGET_EXCEEDED');
            }

            foreach (self::TRANSITIONS as [$required, $action, $actor, $targetKind, $next]) {
                if ($state->value !== $required) { throw new \LogicException('MIS308_LIFECYCLE_STATE_INVALID'); }
                $target = 'snapshot' === $targetKind ? $dossier->targetSnapshot() : $missionId;
                $capability = $accepted->capability($action, $actor, $target);
                $consumption = $accepted->consumer()->consume(
                    $capability, $missionId, $dossier->identity(), $action, $actor, $target, $at,
                );
                $consumptions[] = $consumption;
                $state = MissionState::from($next);
                $transition = [
                    'mission_id' => $missionId, 'dossier_identity' => $dossier->identity(),
                    'from' => $required, 'action' => $action, 'actor' => $actor,
                    'target' => $target, 'capability_nonce' => $capability->nonce,
                    'at' => $at, 'to' => $next,
                ];
                $transition['transition_id'] = 'mission-transition-'.hash('sha256', CanonicalJson::encode($transition));
                $history[] = $transition;

                if (MissionState::EXECUTING === $state) {
                    $this->project($dossier, $state, $history, $consumptions, 'inspection authorized');
                } elseif (MissionState::EVIDENCE_ASSEMBLED === $state) {
                    $findings = $this->inspect($dossier, $snapshot['files']);
                    if (count($findings) > ($budget['max_findings'] ?? -1)) {
                        throw new \RuntimeException('MIS307_RESOURCE_BUDGET_EXCEEDED');
                    }
                    $evidence = $this->evidence($dossier, $findings, count($snapshot['files']));
                    $this->writeRecord($missionId, 'evidence', $evidence['evidence_id'], $evidence);
                    $this->project($dossier, $state, $history, $consumptions, 'evidence assembled');
                } else {
                    $this->project($dossier, $state, $history, $consumptions, strtolower($next));
                }
            }

            $receipt = $this->receipt($dossier, $state, $history, $consumptions, $evidence, $startedAt, $at, 'success criteria satisfied');
            $this->writeRecord($missionId, 'receipts', $receipt['receipt_id'], $receipt);
            $this->project($dossier, $state, $history, $consumptions, 'terminal: completed', $receipt);
            return $receipt;
        } catch (\Throwable $error) {
            $terminal = match ($error->getMessage()) {
                'MIS106_MISSION_EXPIRED', 'MIS207_CAPABILITY_EXPIRED' => MissionState::EXPIRED,
                'MIS307_RESOURCE_BUDGET_EXCEEDED', 'MIS311_RECORD_WRITE_FAILED' => MissionState::ABORTED,
                default => MissionState::REFUSED,
            };
            $receipt = $this->receipt($dossier, $terminal, $history, $consumptions, null, $startedAt, $at, $error->getMessage());
            $this->writeRecord($missionId, 'receipts', $receipt['receipt_id'], $receipt);
            $this->project($dossier, $terminal, $history, $consumptions, 'terminal: '.strtolower($terminal->value), $receipt);
            return $receipt;
        }
    }

    public function status(string $missionId): array
    {
        $path = $this->statusPath($missionId);
        if (!is_file($path)) { throw new \RuntimeException('MIS309_MISSION_NOT_FOUND'); }
        return json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
    }

    private function inspect(MissionDossier $dossier, array $files): array
    {
        ksort($files, SORT_STRING);
        $findings = [];
        foreach ($files as $path => $content) {
            foreach (preg_split('/\R/', $content) ?: [] as $offset => $line) {
                if (str_contains($line, 'AUTHORITY_FROM_LINEAGE')) {
                    $finding = [
                        'mission_id' => $dossier->missionId(), 'target_snapshot' => $dossier->targetSnapshot(),
                        'source_location' => $path.':'.($offset + 1),
                        'violated_doctrine' => 'readable lineage is provenance, never authority',
                        'supporting_evidence' => trim($line),
                        'recommended_disposition' => 'REFUSE_SELF_ISSUANCE_REQUIRE_OPERATOR_CAPABILITY',
                    ];
                    $finding['finding_id'] = 'mission-finding-'.hash('sha256', CanonicalJson::encode($finding));
                    $findings[] = $finding;
                }
            }
        }
        return $findings;
    }

    private function evidence(MissionDossier $dossier, array $findings, int $filesInspected): array
    {
        $evidence = [
            'mission_id' => $dossier->missionId(), 'dossier_identity' => $dossier->identity(),
            'target_snapshot' => $dossier->targetSnapshot(), 'files_inspected' => $filesInspected, 'findings' => $findings,
            'target_modified' => false, 'external_io_performed' => false,
        ];
        $evidence['evidence_id'] = 'mission-evidence-'.hash('sha256', CanonicalJson::encode($evidence));
        return $evidence;
    }

    private function receipt(MissionDossier $dossier, MissionState $state, array $history, array $consumptions, ?array $evidence, int $startedAt, int $endedAt, string $reason): array
    {
        $receipt = [
            'mission_id' => $dossier->missionId(), 'dossier_identity' => $dossier->identity(),
            'target_snapshot' => $dossier->targetSnapshot(), 'final_state' => $state->value,
            'transition_history' => $history, 'consumed_capabilities' => $consumptions,
            'evidence_references' => null === $evidence ? [] : [$evidence['evidence_id']],
            'budget_use' => ['elapsed_seconds' => $endedAt - $startedAt, 'files' => $evidence['files_inspected'] ?? 0, 'findings' => null === $evidence ? 0 : count($evidence['findings'])],
            'disposition_reason' => $reason,
        ];
        $receipt['receipt_id'] = 'mission-receipt-'.hash('sha256', CanonicalJson::encode($receipt));
        return $receipt;
    }

    private function project(MissionDossier $dossier, MissionState $state, array $history, array $consumptions, string $reason, ?array $receipt = null): void
    {
        $granted = array_map(static fn (array $grant): string => $grant['action'], $dossier->toArray()['permitted_acts']);
        $used = array_map(static fn (array $item): string => $item['action'], $consumptions);
        $projection = [
            'mission_id' => $dossier->missionId(), 'dossier_identity' => $dossier->identity(),
            'target_snapshot' => $dossier->targetSnapshot(), 'state' => $state->value,
            'current_authorized_act' => $state->terminal() ? null : ($granted[count($used)] ?? null),
            'consumed_capabilities' => $consumptions, 'remaining_acts' => array_values(array_diff($granted, $used)),
            'transition_history' => $history, 'stop_reason' => $reason,
            'terminal_disposition' => $state->terminal() ? $state->value : null,
            'terminal_receipt_id' => $receipt['receipt_id'] ?? null,
        ];
        $this->writeJson($this->statusPath($dossier->missionId()), $projection);
    }

    private function writeRecord(string $missionId, string $kind, string $id, array $record): void
    {
        $this->writeJson($this->root.'/var/imperium/runtime/missions/'.$missionId.'/'.$kind.'/'.$id.'.json', $record);
    }

    private function statusPath(string $missionId): string
    {
        if (!preg_match('/^mission-[a-z0-9-]+$/', $missionId)) { throw new \InvalidArgumentException('MIS310_MISSION_ID_INVALID'); }
        return $this->root.'/var/imperium/runtime/missions/'.$missionId.'/status.json';
    }

    private function writeJson(string $path, array $record): void
    {
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) {
            throw new \RuntimeException('MIS311_RECORD_WRITE_FAILED');
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('MIS311_RECORD_WRITE_FAILED');
        }
    }
}
