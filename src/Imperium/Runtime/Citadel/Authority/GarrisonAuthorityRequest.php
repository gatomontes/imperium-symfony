<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Authority;

use App\Imperium\Runtime\Clock;

/** Unsigned preparation only. No issuer, trust enrollment, revision store or effect. */
final readonly class GarrisonAuthorityRequest
{
    public const EXTENSION = [
        'persona_admission_disposition_authority' => 'admit or refuse exact immutable Persona packages under Garrison doctrine only',
        'custody_registration_authority' => 'register custody only after the same occupied Constable renders an attributable admission disposition',
    ];
    public const POWERS = ['seat_binding_authority', 'inventory_response_authority', 'persona_admission_disposition_authority',
        'custody_registration_authority', 'persona_reservation_disposition_authority', 'profile_derivation_handoff_disposition_authority',
        'selection_authority', 'execution_authority'];

    public function __construct(private Clock $clock) {}

    public function prepare(array $input): array
    {
        AuthorityInput::keys($input, ['schema', 'occupancy', 'prior_revision', 'effective_at', 'expires_at', 'request_nonce']);
        AuthorityInput::require($input['schema'] === 'imperium.garrison-authority-preparation/v1');
        $o = $input['occupancy']; AuthorityInput::require(is_array($o)); AuthorityInput::intact($o);
        AuthorityInput::require(($o['schema'] ?? null) === 'imperium.garrison-constable-occupancy/v1'
            && ($o['seat'] ?? null) === 'garrison.constable' && ($o['office'] ?? null) === 'garrison'
            && ($o['status'] ?? null) === 'ACTIVE' && ($o['binding_atomic'] ?? null) === true
            && is_int($o['occupancy_generation'] ?? null) && $o['occupancy_generation'] > 0
            && ($o['selection_authority'] ?? null) === false && ($o['execution_authority'] ?? null) === false, 'CAI030_OCCUPANCY_UNSUPPORTED');
        foreach (['instance_id', 'manifestation_id', 'binding_id'] as $key) { AuthorityInput::id($o[$key] ?? null); }
        AuthorityInput::require(1 === preg_match('/^garrison-constable-binding-[a-f0-9]{20}$/D', $o['binding_id']), 'CAI030_OCCUPANCY_UNSUPPORTED');
        foreach ($o as $key => $value) {
            if (str_ends_with((string) $key, '_authority')) {
                AuthorityInput::require(in_array($key, self::POWERS, true) && is_bool($value), 'CAI031_UNKNOWN_POWER');
            }
        }
        $observed = [];
        foreach (self::POWERS as $power) { $observed[$power] = $o[$power] ?? null; }
        $prior = $input['prior_revision'];
        if ($prior !== null) {
            AuthorityInput::keys($prior, ['id', 'digest']); AuthorityInput::id($prior['id']); AuthorityInput::hex($prior['digest']);
        }
        AuthorityInput::require(is_int($input['effective_at']) && is_int($input['expires_at'])
            && $input['effective_at'] > 0 && $input['expires_at'] > $input['effective_at'], 'CAI032_REVISION_TIME_INVALID');
        AuthorityInput::require(is_string($input['request_nonce']) && 1 === preg_match('/^[a-f0-9]{48}$/D', $input['request_nonce']), 'CAI033_REPLAY_ID_INVALID');
        $terms = ['instance_id' => $o['instance_id'], 'seat' => $o['seat'], 'manifestation_id' => $o['manifestation_id'],
            'occupancy_generation' => $o['occupancy_generation'], 'binding_id' => $o['binding_id'], 'binding_digest' => $o['record_digest'],
            'prior_revision' => $prior, 'requested_extension' => self::EXTENSION,
            'observed_existing_powers' => $observed, 'other_powers_policy' => 'PRESERVE_ORIGINAL_NO_NEW_POWERS',
            'effective_at' => $input['effective_at'], 'expires_at' => $input['expires_at'], 'request_nonce' => $input['request_nonce']];
        return AuthorityInput::seal(['schema' => 'imperium.garrison-authority-request/v1',
            'request_id' => 'garrison-authority-request-'.AuthorityInput::digest($terms), 'terms' => $terms,
            'status' => 'UNSIGNED_UNVERIFIED_REQUEST_ONLY', 'issuer' => null, 'decision' => null,
            'prior_revision_verified' => false, 'source_currentness_verified' => false, ...AuthorityInput::flags()]);
    }

    /** Supplied original bytes must still match. This is not a current-occupancy read. */
    public function inspect(array $input): array
    {
        AuthorityInput::keys($input, ['request', 'occupancy']);
        $request = $input['request']; AuthorityInput::require(is_array($request)); AuthorityInput::intact($request);
        $terms = $request['terms'] ?? [];
        $rebuilt = $this->prepare(['schema' => 'imperium.garrison-authority-preparation/v1', 'occupancy' => $input['occupancy'],
            'prior_revision' => $terms['prior_revision'] ?? null, 'effective_at' => $terms['effective_at'] ?? null,
            'expires_at' => $terms['expires_at'] ?? null, 'request_nonce' => $terms['request_nonce'] ?? null]);
        AuthorityInput::require(AuthorityInput::digest($rebuilt) === AuthorityInput::digest($request), 'CAI034_REQUEST_OR_PREDECESSOR_MISMATCH');
        $now = $this->clock->now()->getTimestamp();
        return ['disposition' => 'UNSIGNED_REQUEST_VERIFIED_AUTHORITY_BLOCKED', 'request_id' => $request['request_id'],
            'time_status' => $now < $terms['effective_at'] ? 'NOT_YET_EFFECTIVE' : ($now >= $terms['expires_at'] ? 'EXPIRED' : 'WITHIN_REQUESTED_WINDOW_ONLY'),
            'blockers' => ['NATIVE_GARRISON_REVISION_ISSUER_AND_COMPETENCE_UNAVAILABLE',
                'TRUSTED_CURRENT_REVISION_AND_REVOCATION_SOURCE_UNAVAILABLE', 'ATOMIC_AUTHORITY_CONSUMPTION_AND_EFFECT_PROTOCOL_UNAVAILABLE'],
            ...AuthorityInput::flags()];
    }

    /** No incoming key/decision can bootstrap its own authority; no persistence occurs. */
    public function verifyRevision(array $input): array
    {
        AuthorityInput::keys($input, ['request', 'occupancy', 'decision']);
        $result = $this->inspect(['request' => $input['request'], 'occupancy' => $input['occupancy']]);
        AuthorityInput::require($input['decision'] === null || is_array($input['decision']), 'CAI035_DECISION_FORMAT_UNSUPPORTED');
        return [...$result, 'disposition' => 'REFUSED_NO_AUTHENTIC_NATIVE_REVISION_ISSUER',
            'decision_accepted' => false, 'revision_written' => false, 'authority_consumed' => false];
    }

    /** Dormant consumer seam: never returns an effective revised occupant. */
    public function resolveForAdmission(array $input): never
    {
        $this->verifyRevision($input);
        throw new \RuntimeException('CAI036_AUTHENTIC_NATIVE_REVISION_RESOLVER_UNAVAILABLE');
    }
}
