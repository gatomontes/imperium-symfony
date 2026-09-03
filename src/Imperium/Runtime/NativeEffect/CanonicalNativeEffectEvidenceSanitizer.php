<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\NativeEffect;

use App\Bootstrap\CanonicalJson;

/** Pure allowlist projection for retained live-trial evidence. It performs no I/O. */
final class CanonicalNativeEffectEvidenceSanitizer
{
    public const string SCHEMA = 'imperium.sanitized-canonical-native-effect-live-trial-evidence/v1';
    public const string REQUIRED_AUTHORIZATION_MARKER_DIGEST = '372ae47a7aa92ea4ba631a8bb58c13b81c9d27d672adad7220822e4c833104d5';
    public const array REFERENCE_NAMES = [
        'native_transition', 'native_receipt', 'effect_authority', 'effect_admission',
        'callback_start', 'response_envelope', 'raw_result', 'lazaretto_admission', 'receipt',
    ];

    public function sanitize(array $evidence): array
    {
        $this->exactKeys($evidence, [
            'source_commit', 'runtime', 'operation', 'destination_digest', 'payload_digest',
            'idempotency_key_digest', 'authorization_marker_digest', 'references',
            'provider_outcome', 'timing',
        ]);
        if (!$this->hex($evidence['source_commit'], 40)
            || !is_array($evidence['runtime'])
            || array_keys($evidence['runtime']) !== ['php_version', 'os_family']
            || !is_string($evidence['runtime']['php_version'])
            || !in_array($evidence['runtime']['os_family'], ['Windows', 'Linux', 'BSD', 'Darwin'], true)
            || 'email.send' !== $evidence['operation']
            || !$this->hex($evidence['destination_digest'])
            || !$this->hex($evidence['payload_digest'])
            || !$this->hex($evidence['idempotency_key_digest'])
            || self::REQUIRED_AUTHORIZATION_MARKER_DIGEST !== $evidence['authorization_marker_digest']) {
            throw new \RuntimeException('CNE600_SANITIZED_EVIDENCE_INPUT_INVALID');
        }

        $references = $evidence['references'];
        if (!is_array($references) || array_keys($references) !== self::REFERENCE_NAMES) {
            throw new \RuntimeException('CNE601_SANITIZED_REFERENCE_SET_INVALID');
        }
        foreach ($references as $reference) {
            if (!is_array($reference) || array_keys($reference) !== ['schema', 'id_digest', 'record_digest']
                || !is_string($reference['schema']) || '' === $reference['schema']
                || !$this->hex($reference['id_digest']) || !$this->hex($reference['record_digest'])) {
                throw new \RuntimeException('CNE601_SANITIZED_REFERENCE_SET_INVALID');
            }
        }

        $outcome = $evidence['provider_outcome'];
        if (!is_array($outcome) || array_keys($outcome) !== ['classification', 'http_status', 'message_id_digest', 'thread_id_digest']
            || !in_array($outcome['classification'], ['ACCEPTED', 'REJECTED', 'UNKNOWN'], true)
            || (!is_null($outcome['http_status']) && (!is_int($outcome['http_status']) || $outcome['http_status'] < 100 || $outcome['http_status'] > 599))
            || !$this->nullableDigest($outcome['message_id_digest']) || !$this->nullableDigest($outcome['thread_id_digest'])) {
            throw new \RuntimeException('CNE602_SANITIZED_OUTCOME_INVALID');
        }

        $timing = $evidence['timing'];
        if (!is_array($timing) || array_keys($timing) !== ['effect_started_at', 'provider_observed_at', 'provider_received_at', 'receipt_bound_at']) {
            throw new \RuntimeException('CNE603_SANITIZED_TIMING_INVALID');
        }
        $times = array_values($timing);
        if (array_filter($times, static fn (mixed $time): bool => !is_int($time))) {
            throw new \RuntimeException('CNE603_SANITIZED_TIMING_INVALID');
        }
        for ($index = 1; $index < count($times); ++$index) {
            if ($times[$index] < $times[$index - 1]) {
                throw new \RuntimeException('CNE603_SANITIZED_TIMING_INVALID');
            }
        }

        $sanitized = [
            'schema' => self::SCHEMA,
            'source_commit' => $evidence['source_commit'],
            'runtime' => $evidence['runtime'],
            'operation' => $evidence['operation'],
            'request_commitments' => [
                'destination_digest' => $evidence['destination_digest'],
                'payload_digest' => $evidence['payload_digest'],
                'idempotency_key_digest' => $evidence['idempotency_key_digest'],
                'authorization_marker_digest' => $evidence['authorization_marker_digest'],
            ],
            'references' => $references,
            'provider_outcome' => $outcome,
            'timing' => $timing,
            'evidence_limits' => [
                'local_callback_lineage_proved' => true,
                'remote_cryptographic_authorship_proved' => false,
                'provider_side_idempotency_guarantee' => 'UNVERIFIED',
                'automatic_retry_permitted' => false,
                'continuing_authority' => false,
                'sensitive_material_retained' => false,
            ],
            'sanitized' => true,
        ];
        $this->assertNoSensitiveMaterial($sanitized);
        $sanitized['record_digest'] = hash('sha256', CanonicalJson::encode($sanitized));
        return $sanitized;
    }

    public function assertNoSensitiveMaterial(array $candidate): void
    {
        $walk = function (mixed $value, string $key = '') use (&$walk): void {
            if (preg_match('/(?:credential|secret|token|api[_-]?key|authorization[_-]?header|headers|body|environment|private[_-]?path)/i', $key)) {
                throw new \RuntimeException('CNE604_SENSITIVE_EVIDENCE_REFUSED');
            }
            if (is_array($value)) {
                foreach ($value as $childKey => $child) { $walk($child, (string) $childKey); }
                return;
            }
            if (is_string($value) && (preg_match('/Bearer\s|AGENTMAIL_API_KEY|[A-Z]:\\\\|\/var\/imperium|@[a-z0-9.-]+\.[a-z]{2,}/i', $value))) {
                throw new \RuntimeException('CNE604_SENSITIVE_EVIDENCE_REFUSED');
            }
        };
        $walk($candidate);
    }

    private function exactKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            throw new \RuntimeException('CNE600_SANITIZED_EVIDENCE_INPUT_INVALID');
        }
    }

    private function hex(mixed $value, int $length = 64): bool
    {
        return is_string($value) && 1 === preg_match('/^[a-f0-9]{'.$length.'}$/', $value);
    }

    private function nullableDigest(mixed $value): bool
    {
        return null === $value || $this->hex($value);
    }
}
