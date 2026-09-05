<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;

/** Exact execution object embedded in an Operator-approved Mission Plan. */
final readonly class CanonicalMissionPlan
{
    public const string SCHEMA = 'imperium.canonical-mission-plan/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'mission_id', 'mission_kind', 'target_repository', 'target_commit', 'target_tree',
        'inspection_paths', 'requested_permissions', 'prohibitions', 'budget', 'expires_at', 'success_criteria',
        'failure_criteria', 'evidence_requirements', 'lifecycle_transitions',
    ];

    private string $digest;

    private function __construct(private array $record)
    {
        $this->digest = hash('sha256', CanonicalJson::encode($record));
    }

    public static function fromMissionPlan(array $missionPlan): self
    {
        $record = $missionPlan['canonical_mission'] ?? null;
        if (!is_array($record) || self::REQUIRED_FIELDS !== array_keys($record)
            || self::SCHEMA !== ($record['schema'] ?? null)
            || !self::text($record['mission_id'] ?? null)
            || !self::text($record['mission_kind'] ?? null)
            || !self::text($record['target_repository'] ?? null)
            || 1 !== preg_match('/^[a-f0-9]{40}$/', $record['target_commit'] ?? '')
            || 1 !== preg_match('/^[a-f0-9]{40}$/', $record['target_tree'] ?? '')
            || !self::validInspectionPaths($record['inspection_paths'] ?? null)
            || !self::stringList($record['requested_permissions'] ?? null)
            || !self::stringList($record['prohibitions'] ?? null)
            || !self::stringList($record['success_criteria'] ?? null)
            || !self::stringList($record['failure_criteria'] ?? null)
            || !self::stringList($record['evidence_requirements'] ?? null)
            || !self::validBudget($record['budget'] ?? null)
            || !self::validTransitions($record['lifecycle_transitions'] ?? null)) {
            throw new \RuntimeException('MIS400_CANONICAL_MISSION_PLAN_INVALID');
        }
        try {
            $expiry = new \DateTimeImmutable($record['expires_at']);
        } catch (\Throwable) {
            throw new \RuntimeException('MIS400_CANONICAL_MISSION_PLAN_INVALID');
        }
        if ($expiry->format(DATE_ATOM) !== $record['expires_at']) {
            throw new \RuntimeException('MIS400_CANONICAL_MISSION_PLAN_INVALID');
        }

        return new self($record);
    }

    public function id(): string { return $this->record['mission_id']; }
    public function digest(): string { return $this->digest; }
    public function targetCommit(): string { return $this->record['target_commit']; }
    public function targetTree(): string { return $this->record['target_tree']; }
    public function inspectionPaths(): array { return $this->record['inspection_paths']; }
    public function expiresAt(): \DateTimeImmutable { return new \DateTimeImmutable($this->record['expires_at']); }
    public function toArray(): array { return $this->record; }

    private static function text(mixed $value): bool
    {
        return is_string($value) && '' !== trim($value);
    }

    private static function stringList(mixed $values): bool
    {
        if (!is_array($values) || [] === $values || !array_is_list($values)) { return false; }
        foreach ($values as $value) {
            if (!self::text($value)) { return false; }
        }
        return count($values) === count(array_unique($values));
    }

    private static function validBudget(mixed $budget): bool
    {
        if (!is_array($budget) || ['max_files', 'max_bytes', 'max_findings', 'max_seconds'] !== array_keys($budget)) {
            return false;
        }
        foreach ($budget as $value) {
            if (!is_int($value) || $value < 1) { return false; }
        }
        return true;
    }

    private static function validInspectionPaths(mixed $paths): bool
    {
        if (!self::stringList($paths)) { return false; }
        foreach ($paths as $path) {
            if (str_starts_with($path, '-') || str_contains($path, "\0") || str_contains($path, '\\') || str_contains($path, '..')) {
                return false;
            }
        }
        return true;
    }

    private static function validTransitions(mixed $transitions): bool
    {
        if (!is_array($transitions) || [] === $transitions || !array_is_list($transitions)) { return false; }
        foreach ($transitions as $transition) {
            if (!is_array($transition)
                || ['action', 'actor', 'target', 'from', 'to'] !== array_keys($transition)
                || !self::text($transition['action'] ?? null)
                || !self::text($transition['actor'] ?? null)
                || !self::text($transition['target'] ?? null)
                || !self::text($transition['from'] ?? null)
                || !self::text($transition['to'] ?? null)) {
                return false;
            }
        }
        return true;
    }
}
