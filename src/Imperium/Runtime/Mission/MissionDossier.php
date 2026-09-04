<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;

/** Immutable, deterministic statement of mission intent and authority bounds. */
final readonly class MissionDossier
{
    public const string SCHEMA = 'imperium.operator.mission-dossier/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'mission_id', 'mission_kind', 'mission_version', 'operator_identity',
        'target_snapshot', 'requested_acts', 'permitted_acts', 'prohibited_acts',
        'success_criteria', 'evidence_requirements', 'time_budget_seconds',
        'resource_budget', 'issued_at', 'expires_at', 'terminal_disposition_rules',
        'authorization_provenance',
    ];

    private array $canonical;
    private string $identity;

    private function __construct(array $canonical)
    {
        $this->canonical = $canonical;
        $this->identity = 'mission-dossier-'.hash('sha256', CanonicalJson::encode($canonical));
    }

    public static function fromArray(array $input): self
    {
        if (self::REQUIRED_FIELDS !== array_keys($input)) {
            throw new \InvalidArgumentException('MIS101_DOSSIER_FIELDS_INVALID');
        }
        foreach (['schema', 'mission_id', 'mission_kind', 'operator_identity', 'target_snapshot'] as $field) {
            if (!is_string($input[$field]) || '' === trim($input[$field])) {
                throw new \InvalidArgumentException('MIS102_DOSSIER_VALUE_INVALID');
            }
        }
        if (self::SCHEMA !== $input['schema']
            || !preg_match('/^mission-[a-z0-9][a-z0-9-]{7,120}$/', $input['mission_id'])
            || !preg_match('/^[a-f0-9]{40}$/', $input['target_snapshot'])
            || !is_int($input['mission_version']) || $input['mission_version'] < 1
            || !is_int($input['issued_at']) || !is_int($input['expires_at'])
            || $input['expires_at'] <= $input['issued_at']
            || !is_int($input['time_budget_seconds']) || $input['time_budget_seconds'] < 1
            || $input['expires_at'] - $input['issued_at'] > $input['time_budget_seconds']) {
            throw new \InvalidArgumentException('MIS102_DOSSIER_VALUE_INVALID');
        }
        foreach (['requested_acts', 'permitted_acts', 'prohibited_acts', 'success_criteria', 'evidence_requirements', 'resource_budget', 'terminal_disposition_rules', 'authorization_provenance'] as $field) {
            if (!is_array($input[$field]) || [] === $input[$field]) {
                throw new \InvalidArgumentException('MIS103_DOSSIER_SCOPE_INVALID');
            }
        }
        foreach ($input['permitted_acts'] as $grant) {
            if (!is_array($grant) || ['action', 'actor', 'target'] !== array_keys($grant)
                || !self::nonEmptyStrings($grant)) {
                throw new \InvalidArgumentException('MIS103_DOSSIER_SCOPE_INVALID');
            }
            if (!in_array($grant['action'], $input['requested_acts'], true)
                || in_array($grant['action'], $input['prohibited_acts'], true)) {
                throw new \InvalidArgumentException('MIS104_DOSSIER_OVERBROAD');
            }
        }
        if (($input['authorization_provenance']['source'] ?? null) !== 'operator-mission-order'
            || !is_string($input['authorization_provenance']['grant_id'] ?? null)
            || '' === $input['authorization_provenance']['grant_id']) {
            throw new \InvalidArgumentException('MIS105_AUTHORIZATION_PROVENANCE_INVALID');
        }

        return new self($input);
    }

    public function missionId(): string { return $this->canonical['mission_id']; }
    public function operatorIdentity(): string { return $this->canonical['operator_identity']; }
    public function targetSnapshot(): string { return $this->canonical['target_snapshot']; }
    public function issuedAt(): int { return $this->canonical['issued_at']; }
    public function expiresAt(): int { return $this->canonical['expires_at']; }
    public function identity(): string { return $this->identity; }
    public function toArray(): array { return $this->canonical; }
    public function canonicalJson(): string { return CanonicalJson::encode($this->canonical); }

    public function permits(string $action, string $actor, string $target): bool
    {
        return in_array(['action' => $action, 'actor' => $actor, 'target' => $target], $this->canonical['permitted_acts'], true);
    }

    public function validateAt(int $at): void
    {
        if ($at < $this->issuedAt() || $at >= $this->expiresAt()) {
            throw new \RuntimeException('MIS106_MISSION_EXPIRED');
        }
    }

    private static function nonEmptyStrings(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_string($value) || '' === trim($value)) { return false; }
        }
        return true;
    }
}

