<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Pure recursive inspection of keys, scalar values and one decoding layer. */
final class AtomicTransitionEvidenceValueAwareSecretExclusionService
{
    private const array ATTACKS = [
        'SENSITIVE_KEY' => ['payload' => ['access_token' => 'opaque']],
        'CREDENTIAL_VALUE' => ['payload' => 'Bearer forbidden-secret'],
        'ENCODED_CREDENTIAL_VALUE' => ['payload' => 'QmVhcmVyIGZvcmJpZGRlbi1zZWNyZXQ='],
        'PROCESS_LOCAL_CAPABILITY_VALUE' => ['payload' => 'process-local-capability://provider/1'],
    ];

    public function prove(string $proofId, array $records): array
    {
        $recordDigests = [];
        foreach ($records as $record) {
            if (!is_array($record) || !$this->digest($record['record_digest'] ?? null)) {
                throw new \RuntimeException('PBL980_SECRET_SCAN_RECORD_INVALID');
            }
            $this->assertClean($record);
            $recordDigests[] = $record['record_digest'];
        }

        $vectorDigests = [];
        $codes = [];
        foreach (self::ATTACKS as $kind => $attack) {
            $vectorDigests[] = hash('sha256', CanonicalJson::encode($attack));
            try {
                $this->assertClean($attack);
                throw new \RuntimeException('PBL981_SECRET_ATTACK_NOT_REFUSED');
            } catch (\RuntimeException $caught) {
                if ('PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED' !== $caught->getMessage()) {
                    throw $caught;
                }
                $codes[] = $caught->getMessage();
            }
        }

        return $this->seal([
            'schema' => AtomicTransitionEvidenceSecretExclusionProofContract::SCHEMA,
            'proof_id' => $proofId,
            'scanned_record_digests' => $recordDigests,
            'attack_vector_kinds' => array_keys(self::ATTACKS),
            'attack_vector_digests' => $vectorDigests,
            'derived_refusal_codes' => $codes,
            'all_records_clean' => true,
            'all_attacks_refused' => true,
            'value_aware' => true,
            'read_only' => true,
            'status' => AtomicTransitionEvidenceSecretExclusionProofContract::STATUS,
            'sealed' => true,
        ]);
    }

    public function assertClean(mixed $value, ?string $key = null): void
    {
        if (null !== $key && preg_match('/(?:secret|token|credential|password|api[_-]?key|capability)/i', $key)) {
            throw new \RuntimeException('PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED');
        }
        if (is_object($value) || is_resource($value) || is_callable($value)) {
            throw new \RuntimeException('PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED');
        }
        if (is_array($value)) {
            foreach ($value as $childKey => $child) {
                $this->assertClean($child, is_string($childKey) ? $childKey : null);
            }
            return;
        }
        if (!is_string($value)) {
            return;
        }
        if ($this->credentialLike($value)) {
            throw new \RuntimeException('PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED');
        }
        $decoded = base64_decode($value, true);
        if (false !== $decoded && $decoded !== $value && $this->credentialLike($decoded)) {
            throw new \RuntimeException('PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED');
        }
    }

    private function credentialLike(string $value): bool
    {
        return (bool) preg_match('/(?:^Bearer\s|^sk-[A-Za-z0-9]|^gh[pousr]_|^AKIA[A-Z0-9]{12}|-----BEGIN [A-Z ]+PRIVATE KEY-----|process-local-(?:credential|capability):\/\/)/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
