<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;

/** Signed exact transition custody; every public value is covered by the signature. */
final readonly class MissionCapability
{
    public const string SCHEMA = 'imperium.canonical-mission-capability/v1';
    public const array FIELDS = [
        'schema', 'capability_id', 'authorization_id', 'authorization_digest', 'dossier_id',
        'dossier_digest', 'mission_id', 'mission_digest', 'action', 'actor', 'target', 'issuer',
        'required_state', 'resulting_state', 'not_before', 'expires_at', 'nonce', 'signature',
    ];

    private function __construct(private array $record) {}

    public static function fromArray(array $record): self
    {
        if (self::FIELDS !== array_keys($record) || self::SCHEMA !== ($record['schema'] ?? null)) {
            throw new \RuntimeException('MIS410_CAPABILITY_INVALID');
        }
        foreach (array_diff(self::FIELDS, ['not_before', 'expires_at']) as $field) {
            if (!is_string($record[$field]) || '' === trim($record[$field])) {
                throw new \RuntimeException('MIS410_CAPABILITY_INVALID');
            }
        }
        if (!is_int($record['not_before']) || !is_int($record['expires_at']) || $record['expires_at'] <= $record['not_before']
            || 1 !== preg_match('/^[a-f0-9]{64}$/', $record['authorization_digest'])
            || 1 !== preg_match('/^[a-f0-9]{64}$/', $record['dossier_digest'])
            || 1 !== preg_match('/^[a-f0-9]{64}$/', $record['mission_digest'])
            || 1 !== preg_match('/^[a-f0-9]{32}$/', $record['nonce'])
            || 1 !== preg_match('/^[a-f0-9]{64}$/', $record['signature'])) {
            throw new \RuntimeException('MIS410_CAPABILITY_INVALID');
        }
        return new self($record);
    }

    public function get(string $field): mixed { return $this->record[$field] ?? null; }
    public function toArray(): array { return $this->record; }
    public function unsigned(): array { $record = $this->record; $record['signature'] = ''; return $record; }
    public function canonicalUnsigned(): string { return CanonicalJson::encode($this->unsigned()); }
}
