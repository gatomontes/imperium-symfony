<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;

/** Public input only. No journal, credential broker, HTTP client or activation dependency. */
final readonly class FormationPreparation
{
    public function __construct(private Clock $clock) {}

    public function inspect(array $public): array
    {
        if (!FormationJournal::keys($public, ['schema', 'installation', 'trust', 'institutions', 'appointments', 'transport'])
            || $public['schema'] !== 'imperium.citadel-readiness-public/v1') {
            throw new \RuntimeException('CRP001_PUBLIC_EXPORT_SCHEMA_REQUIRED');
        }
        $rows = [];
        foreach (['installation', 'trust', 'institutions', 'appointments', 'transport'] as $key) {
            if ($public[$key] !== null && !is_array($public[$key])) { throw new \RuntimeException('CRP001_PUBLIC_EXPORT_SCHEMA_REQUIRED'); }
            $rows[$key] = ['status' => $public[$key] === null ? 'MISSING_OWNER_PUBLIC_EVIDENCE' : 'SUPPLIED_UNVERIFIED_OWNER_EVIDENCE',
                'digest' => $public[$key] === null ? null : FormationJournal::digest($public[$key])];
        }
        if ($public['trust'] !== null) {
            $trust = $public['trust'];
            $key = base64_decode($trust['public_key'] ?? '', true);
            $rows['trust']['byte_consistency'] = is_string($key) && strlen($key) === 32
                && ($trust['fingerprint'] ?? null) === hash('sha256', $key)
                && ($trust['competence'] ?? null) === 'CITADEL_MISSION_FORMATION'
                && ($trust['revoked'] ?? true) === false
                && is_int($trust['not_before'] ?? null) && is_int($trust['expires_at'] ?? null)
                && $trust['not_before'] <= $this->clock->now()->getTimestamp()
                && $trust['expires_at'] > $this->clock->now()->getTimestamp();
            if (!$rows['trust']['byte_consistency']) { $rows['trust']['status'] = 'INVALID_OR_EXPIRED_PUBLIC_TRUST'; }
        }
        return ['schema' => 'imperium.citadel-readiness-report/v1',
            'disposition' => 'READINESS_PREPARATION_COMPLETE_WITH_EXPLICIT_BLOCKERS',
            'input_digest' => FormationJournal::digest($public), 'rows' => $rows,
            'blockers' => ['OWNER_INSTALLATION_CUSTODY_AND_CURRENT_AUTHORITY_UNVERIFIED',
                'FORMATION_CUSTODY_IMPLEMENTED_DORMANT_LIVE_ADAPTER_UNAPPROVED', 'ENFORCEABLE_TRANSPORT_AND_PRICING_UNESTABLISHED',
                'SEPARATE_COMMISSIONING_AUTHORIZATION_REQUIRED'],
            'live_ready' => false, 'activation' => false, 'execution_authority' => false];
    }

    /** Serialize an exact prospective act, without asserting its substantive legitimacy. */
    public function prepare(array $request): array
    {
        if (!FormationJournal::keys($request, ['schema', 'citadel_id', 'trust_fingerprint', 'effect', 'object', 'expires_at', 'source_identity'])
            || $request['schema'] !== 'imperium.citadel-preparation-request/v1'
            || !is_string($request['citadel_id']) || !preg_match('/^citadel-[a-f0-9]{32}$/D', $request['citadel_id'])
            || !is_string($request['trust_fingerprint']) || !preg_match('/^[a-f0-9]{64}$/D', $request['trust_fingerprint'])
            || !is_array($request['object']) || !is_array($request['source_identity'])
            || !FormationJournal::keys($request['source_identity'], ['commit', 'tree', 'public_export_digest'])) {
            throw new \RuntimeException('CRP002_EXACT_PREPARATION_INPUT_REQUIRED');
        }
        foreach ($request['source_identity'] as $name => $value) {
            if (!is_string($value) || !preg_match($name === 'public_export_digest' ? '/^[a-f0-9]{64}$/D' : '/^[a-f0-9]{40}$/D', $value)) {
                throw new \RuntimeException('CRP002_EXACT_PREPARATION_INPUT_REQUIRED');
            }
        }
        $now = $this->clock->now()->getTimestamp();
        if (!is_int($request['expires_at']) || $request['expires_at'] <= $now) { throw new \RuntimeException('CRP003_FUTURE_EXPIRY_REQUIRED'); }
        $effect = $request['effect'];
        $object = $request['object'];
        $fields = match ($effect) {
            'DELEGATE_PERSONNEL_EVIDENCE' => ['role', 'public_key', 'scope', 'expires_at', 'actor'],
            'APPROVE_FORMATION_PROFILE' => ['profile', 'examination', 'scope', 'seat'],
            'APPOINT_COURTTHANE', 'APPOINT_FORMATION_LOCKSMITH' => ['candidate', 'scope', 'seat', 'generation'],
            'AUTHORIZE_INTERVIEW_SESSION', 'AUTHORIZE_EXACT_DRAFTING', 'AUTHORIZE_RECEIVING_ASSESSMENT' => ['source', 'provider', 'model', 'destination', 'pricing', 'per_call', 'total', 'visible_intakes', 'disclosure', 'expires_at'],
            'CONTROL_FORMATION_SESSION' => ['session_id', 'disposition'],
            'REVOKE_DECISION' => ['nonce'],
            'REPLY_TO_CITADEL' => ['intake_id', 'head', 'content', 'changed_intent'],
            'APPROVE_MISSION_AND_CONSTITUTION', 'REVIEW_MISSION_OBJECT', 'REVIEW_MISSION_REFUSE', 'REVIEW_MISSION_DEFER' => ['terms', 'line_digests', 'rationale'],
            default => throw new \RuntimeException('CRP004_EFFECT_UNSUPPORTED'),
        };
        if (str_starts_with((string) $effect, 'AUTHORIZE_') && array_key_exists('transport', $object)) {
            FormationPreparedOperation::authorization($object['transport']);
            $fields[] = 'transport';
        }
        if (!FormationJournal::keys($object, $fields)) { throw new \RuntimeException('CRP005_EXACT_OBJECT_FIELDS_REQUIRED'); }
        if (isset($object['expires_at']) && (!is_int($object['expires_at']) || $object['expires_at'] <= $now || $object['expires_at'] > $request['expires_at'])) {
            throw new \RuntimeException('CRP003_FUTURE_EXPIRY_REQUIRED');
        }
        if (str_starts_with($effect, 'AUTHORIZE_')) {
            foreach (['provider', 'model', 'destination', 'disclosure'] as $field) {
                if (!is_string($object[$field]) || trim($object[$field]) === '') { throw new \RuntimeException('CRP006_DISCLOSED_TERMS_REQUIRED'); }
            }
            if (!is_array($object['source']) || !$object['source'] || !is_array($object['pricing']) || !$object['pricing']
                || !is_array($object['visible_intakes']) || !array_is_list($object['visible_intakes'])) { throw new \RuntimeException('CRP006_DISCLOSED_TERMS_REQUIRED'); }
            SessionExposure::validate($object['per_call']); SessionExposure::validate($object['total']);
            if ($object['per_call']['calls'] !== 1) { throw new \RuntimeException('CRP006_DISCLOSED_TERMS_REQUIRED'); }
            foreach (SessionExposure::FIELDS as $field) {
                if ($object['per_call'][$field] > $object['total'][$field]) { throw new \RuntimeException('CRP006_DISCLOSED_TERMS_REQUIRED'); }
            }
        }
        if (in_array($effect, ['APPOINT_COURTTHANE', 'APPOINT_FORMATION_LOCKSMITH'], true)
            && ($object['scope'] !== $request['citadel_id'] || $object['seat'] !== ($effect === 'APPOINT_COURTTHANE' ? 'courtyard.courtthane' : 'clavium.locksmith')
                || !is_int($object['generation']) || $object['generation'] < 1)) { throw new \RuntimeException('CRP007_APPOINTMENT_SCOPE_INVALID'); }
        $payload = ['schema' => 'imperium.citadel-owner-decision/v1', 'citadel_id' => $request['citadel_id'],
            'trust_fingerprint' => $request['trust_fingerprint'], 'effect' => $effect,
            'object_digest' => FormationJournal::digest($object), 'issued_at' => $now,
            'expires_at' => $request['expires_at'], 'nonce' => bin2hex(random_bytes(24))];
        $bytes = CanonicalJson::encode($payload);
        return ['schema' => 'imperium.citadel-signing-packet/v1', 'status' => 'UNSIGNED_UNVERIFIED_PREPARATION_ONLY',
            'source_identity' => $request['source_identity'], 'source_identity_verified' => false,
            'object' => $object, 'object_canonical' => CanonicalJson::encode($object), 'payload' => $payload,
            'signing_bytes_base64' => base64_encode($bytes), 'signing_bytes_sha256' => hash('sha256', $bytes),
            'destination' => $object['destination'] ?? null, 'model' => $object['model'] ?? null,
            'disclosure' => $object['disclosure'] ?? null,
            'limitations' => ['Preparation is not approval or substantive authority validation.',
                'Source identity is review metadata, not an additional field in the accepted signed payload.',
                'Runtime consumers revalidate exact source, authority, expiry and revocation.',
                'Tariff reservations do not guarantee remote billing ceilings; timeout does not prove cancellation.'],
            'live_ready' => false, 'activation' => false, 'execution_authority' => false];
    }

    /** Join an externally produced public signature to the exact prepared bytes. No signing. */
    public function assemble(array $input): array
    {
        if (!FormationJournal::keys($input, ['packet', 'public_key', 'signature'])) { throw new \RuntimeException('CRP010_SIGNED_PACKET_INVALID'); }
        $packet = $input['packet'];
        $key = base64_decode($input['public_key'], true); $sig = base64_decode($input['signature'], true);
        $bytes = base64_decode($packet['signing_bytes_base64'] ?? '', true);
        $payload = $packet['payload'] ?? [];
        if (($packet['schema'] ?? null) !== 'imperium.citadel-signing-packet/v1'
            || !is_string($key) || strlen($key) !== 32 || !is_string($sig) || strlen($sig) !== 64
            || ($payload['trust_fingerprint'] ?? null) !== hash('sha256', $key)
            || $bytes !== CanonicalJson::encode($payload) || hash('sha256', $bytes) !== ($packet['signing_bytes_sha256'] ?? null)
            || FormationJournal::digest($packet['object'] ?? null) !== ($payload['object_digest'] ?? null)
            || !is_int($payload['expires_at'] ?? null) || $payload['expires_at'] <= $this->clock->now()->getTimestamp()
            || !sodium_crypto_sign_verify_detached($sig, $bytes, $key)) { throw new \RuntimeException('CRP010_SIGNED_PACKET_INVALID'); }
        return ['schema' => 'imperium.citadel-assembled-decision/v1', 'decision' => ['payload' => $payload, 'signature' => $input['signature']],
            'object' => $packet['object'], 'signature_byte_consistency' => true, 'enrollment_and_authority_verified' => false,
            'activation' => false, 'execution_authority' => false];
    }
}
