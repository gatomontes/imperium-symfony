<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** Closed C2 syntax. Validation of this data never confers native authority. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ProfileFitnessContract
{
    public const DOMAIN = 'IMPERIUM_PROFILE_FITNESS_V1';
    public const CONTRACT = 'imperium.profile-fitness-contract/v1';
    public const JUDGMENT = 'imperium.profile-fitness-judgment/v1';
    public const EXERCISE = 'imperium.profile-fitness-exercise/v1';
    public const DELEGATION = 'imperium.profile-fitness-delegation/v1';
    public const REVOCATION = 'imperium.profile-fitness-revocation/v1';
    public const SEATS = ['courtyard.courtthane', 'clavium.locksmith'];
    public const DUTIES = ['exact_profile_binding', 'complete_predicate_evidence', 'contradictions_and_unknowns',
        'role_capacity_order', 'frozen_input_attribution', 'no_appointment_or_invocation', 'seat_authority_boundary', 'bounded_return'];
    public const OBLIGATIONS = [
        ['id' => 'seat_duties', 'producer_role' => 'guildhall', 'meaning' => 'The exact cognitive payload and limitations are suitable for the named Seat\'s duties under this role contract, assessed alongside the unchanged workload: Courtthane conducts the bounded Courtyard interview and returns its record; Locksmith handles only the separately authorized Clavium custody/release role. Neither acquires the other\'s duties or standing execution authority.'],
        ['id' => 'instruction_consistency', 'producer_role' => 'senate-consistency', 'meaning' => 'The exact payload, declared limitations, model constraints and fallback declarations contain no unresolved contradiction with the exact required duties and output contract.'],
        ['id' => 'authority_limits', 'producer_role' => 'senate-governance', 'meaning' => 'The exact payload requires the existing separate authorization and custody boundaries for actions; it does not treat cognition, interview output, designation, settings or this fitness finding as execution permission.'],
        ['id' => 'workload_practice', 'producer_role' => 'senate-practice', 'meaning' => 'Against the exact public workload, the candidate\'s specified behavior covers every required role duty and return obligation, with an explicit evidence-supported judgment; an untested or unresolved required duty is UNKNOWN, not PASS.'],
        ['id' => 'security_constraints', 'producer_role' => 'senate-security', 'meaning' => 'The payload and declared data/custody constraints satisfy the exact public-only scope, credential non-disclosure and role boundary requirements, with no unresolved blocking security finding.'],
        ['id' => 'qualification_complete', 'producer_role' => 'conscription', 'meaning' => 'Every criterion of this immutable Profile\'s own qualification contract is present and true in the exact current native qualification original; this confirms institutional qualification, not measured provider capability.'],
        ['id' => 'binding_constraints', 'producer_role' => 'conscription', 'meaning' => 'The exact PPC5 model/configuration binding, declared access requirement, constraints, fallbacks and limitations are consistent with the approved Seat/workload obligations; no unstated alternate model, configuration or fallback is used to satisfy them.'],
    ];

    public static function need(bool $ok, string $code): void
    { if (!$ok) { throw new \RuntimeException('PPC10_FITNESS_'.$code); } }
    public static function bounded(array $v): void
    { self::need(strlen(CanonicalJson::encode($v)) <= 262144, 'BYTE_LIMIT'); }
    public static function shape(array $v, array $fields): void
    { self::need(FormationJournal::keys($v, $fields), 'SHAPE'); }
    public static function seat(mixed $seat): void { self::need(in_array($seat, self::SEATS, true), 'SEAT'); }
    public static function nativeDigest(mixed $digest): void
    { self::need(is_string($digest) && preg_match('/\A[0-9a-f]{64}\z/', $digest) === 1, 'NATIVE_DIGEST'); }
    public static function nonce(mixed $nonce): void
    { self::need(is_string($nonce) && preg_match('/\A[0-9a-f]{48}\z/', $nonce) === 1, 'NONCE'); }
    public static function text(mixed $text, int $max = 8192): void
    { self::need(is_string($text) && trim($text) !== '' && strlen($text) <= $max && preg_match('//u', $text) === 1, 'TEXT'); }
    public static function refs(mixed $refs, int $max = 32): array
    {
        self::need(is_array($refs) && array_is_list($refs) && count($refs) > 0 && count($refs) <= $max, 'EVIDENCE_COUNT');
        $ordered = R::refs($refs); self::need(R::same($ordered, $refs), 'EVIDENCE_ORDER'); return $refs;
    }
    public static function nativeRef(string $schema, array $original): array
    { $digest = FormationJournal::digest($original); return ['schema' => $schema, 'id' => $digest, 'digest' => 'sha256:'.$digest]; }

    public static function contract(array $h): array
    {
        self::bounded($h); R::record($h); self::need($h['schema'] === self::CONTRACT, 'CONTRACT_DOMAIN');
        $b = $h['body']; self::shape($b, ['fitness_version', 'seat', 'profile_evidence_digest', 'profile_artifact_digest',
            'model_binding', 'requirements_ref', 'workload_ref', 'qualification_contract_digest', 'obligations']);
        self::need($b['fitness_version'] === 'ppc10-officer-fit-v1' && R::same($b['obligations'], self::OBLIGATIONS), 'OBLIGATIONS');
        self::seat($b['seat']); self::nativeDigest($b['profile_evidence_digest']);
        R::digest($b['profile_artifact_digest']); R::digest($b['qualification_contract_digest']);
        R::ref($b['requirements_ref']); R::ref($b['workload_ref']);
        self::shape($b['model_binding'], ['seal', 'binding_ref', 'configuration_ref', 'binding_generation']);
        foreach (['binding_ref', 'configuration_ref'] as $key) { R::ref($b['model_binding'][$key]); }
        self::shape($b['model_binding']['seal'], ['schema', 'id', 'digest']);
        R::id($b['model_binding']['seal']['id']); self::nativeDigest($b['model_binding']['seal']['digest']);
        self::need($b['model_binding']['seal']['schema'] === FormationModelPreparation::SEAL, 'SEAL_DOMAIN');
        self::need(R::integer($b['model_binding']['binding_generation']) > 0, 'BINDING_GENERATION');
        return $h;
    }

    public static function delegation(array $d): array
    {
        self::bounded($d); self::shape($d, ['domain', 'instance_id', 'citadel_id', 'actor', 'role', 'public_key', 'seat',
            'profile_evidence_digest', 'fitness_contract_ref', 'not_before', 'expires_at', 'nonce']);
        self::need($d['domain'] === self::DOMAIN && in_array($d['role'], array_column(self::OBLIGATIONS, 'producer_role'), true), 'DELEGATION_DOMAIN');
        R::id($d['instance_id']); R::id($d['citadel_id']); self::seat($d['seat']); self::nativeDigest($d['profile_evidence_digest']);
        R::ref($d['fitness_contract_ref']); self::need($d['fitness_contract_ref']['schema'] === self::CONTRACT, 'CONTRACT_DOMAIN');
        R::bytes($d['public_key'], 32); self::nonce($d['nonce']);
        self::need(R::time($d['not_before']) < R::time($d['expires_at']), 'INTERVAL');
        self::shape($d['actor'], ['instance_id', 'seat', 'manifestation_id', 'occupancy_generation', 'binding_id', 'binding_digest', 'installation_digest']);
        return $d;
    }

    public static function judgment(array $e): array
    {
        self::bounded($e); self::shape($e, ['payload', 'signature']); $p = $e['payload'];
        self::shape($p, ['schema', 'domain', 'delegation_ref', 'instance_id', 'citadel_id', 'seat', 'profile_evidence_digest',
            'fitness_contract_ref', 'obligation_id', 'result', 'evidence_refs', 'rationale', 'issued_at', 'expires_at', 'nonce']);
        self::need($p['schema'] === self::JUDGMENT && $p['domain'] === self::DOMAIN, 'JUDGMENT_DOMAIN');
        R::id($p['instance_id']); R::id($p['citadel_id']); self::seat($p['seat']); self::nativeDigest($p['profile_evidence_digest']);
        R::ref($p['delegation_ref']); R::ref($p['fitness_contract_ref']);
        self::need($p['delegation_ref']['schema'] === self::DELEGATION && $p['fitness_contract_ref']['schema'] === self::CONTRACT, 'REFERENCE_DOMAIN');
        self::need(in_array($p['obligation_id'], array_column(self::OBLIGATIONS, 'id'), true), 'OBLIGATION');
        self::need(in_array($p['result'], ['PASS', 'FAIL', 'UNKNOWN'], true), 'RESULT');
        self::refs($p['evidence_refs']); self::text($p['rationale']); self::nonce($p['nonce']);
        self::need(R::time($p['issued_at']) < R::time($p['expires_at']), 'INTERVAL'); R::bytes($e['signature'], 64);
        return $e;
    }

    public static function exercise(array $h): array
    {
        self::bounded($h); R::record($h); self::need($h['schema'] === self::EXERCISE, 'EXERCISE_DOMAIN');
        $b = $h['body']; self::shape($b, ['workload_ref', 'profile_ref', 'binding_ref', 'duties', 'limitations']);
        foreach (['workload_ref', 'profile_ref', 'binding_ref'] as $key) { R::ref($b[$key]); }
        self::text($b['limitations']);
        self::need(is_array($b['duties']) && array_is_list($b['duties']) && count($b['duties']) <= 64
            && array_column($b['duties'], 'id') === self::DUTIES, 'EXERCISE_COVERAGE');
        foreach ($b['duties'] as $d) {
            self::shape($d, ['id', 'observed_return', 'result', 'evidence_refs']); self::text($d['observed_return']);
            self::need(in_array($d['result'], ['PASS', 'FAIL', 'UNKNOWN'], true), 'EXERCISE_RESULT'); self::refs($d['evidence_refs']);
        }
        return $h;
    }
}
