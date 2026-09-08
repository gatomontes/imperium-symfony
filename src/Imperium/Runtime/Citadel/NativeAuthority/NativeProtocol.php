<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;

use App\Imperium\Runtime\Citadel\Authority\{AuthorityInput as A, GarrisonAuthorityRequest, RecruiterEvidence};
use App\Imperium\Runtime\Clock;

/** Native institutional acts only; never a formation delegation or candidate judgment. */
final readonly class NativeProtocol
{
    public function __construct(public NativeJournal $journal, private Clock $clock, private GarrisonAuthorityRequest $requests, private RecruiterEvidence $recruiter) {}
    public function snapshot(): array
    {
        return $this->journal->inspect(fn (array $f) => ['schema' => 'imperium.native-authority-observation/v1',
            'observed_at' => $this->now(), 'frame_generation' => $f['generation'], 'frame_digest' => $f['record_digest'],
            'registry_head' => $f['state']['head'] ?? null, 'registry_revision' => $f['state']['revision'] ?? 0,
            'roster' => $f['state']['roster'] ?? [], 'garrison_revision' => $f['state']['garrison_revision'] ?? null,
            'issuer_revoked' => $f['state']['issuer_revoked'] ?? null, 'revoked_decisions' => array_keys($f['state']['revoked'] ?? []),
            'observation_only_not_indefinite_currentness' => true, ...A::flags()]);
    }
    public function prepare(array $input): array
    {
        A::keys($input, ['effect', 'object', 'issued_at', 'expires_at', 'nonce']);
        return $this->journal->inspect(function (array $f) use ($input): array {
            $t = $f['state']['trust'] ?? []; A::require($t !== [], 'NAT012_TRUST_ABSENT');
            A::require(in_array($input['effect'], NativeTrust::EFFECTS, true) && is_array($input['object']), 'NAT020_EFFECT_UNSUPPORTED');
            $this->shape($input['effect'], $input['object']);
            A::require(is_int($input['issued_at']) && is_int($input['expires_at']) && $input['expires_at'] > $input['issued_at']
                && is_string($input['nonce']) && preg_match('/^[a-f0-9]{48}$/D', $input['nonce']), 'NAT021_INPUT_INVALID');
            return ['schema' => 'imperium.native-unsigned-decision/v1', 'payload' => [
                'schema' => 'imperium.native-institutional-decision/v1', 'domain' => NativeTrust::DOMAIN,
                'instance_id' => $t['instance_id'], 'trust_fingerprint' => $t['fingerprint'], 'issuer_role' => NativeTrust::ROLE,
                'effect' => $input['effect'], 'object_digest' => A::digest($input['object']), 'issued_at' => $input['issued_at'],
                'expires_at' => $input['expires_at'], 'nonce' => $input['nonce']], 'signature' => null, ...A::flags()];
        });
    }
    public function assemble(array $input): array
    {
        A::keys($input, ['object', 'payload', 'signature']);
        $decision = ['payload' => $input['payload'], 'signature' => $input['signature']];
        $this->journal->inspect(fn (array $f) => NativeTrust::verify($f['state'], $decision, $input['object'], $this->now()));
        $this->shape($input['payload']['effect'], $input['object']);
        return ['object_digest' => A::digest($input['object']), 'decision' => $decision];
    }
    public function apply(array $input): array
    {
        A::keys($input, ['object', 'decision']); $o = $input['object']; $d = $input['decision'];
        A::require(is_array($o) && is_array($d), 'NAT021_INPUT_INVALID');
        return $this->journal->change(function (array &$s) use ($input, $o, $d): array {
            $nonce = $d['payload']['nonce'] ?? null; A::require(is_string($nonce), 'NAT021_INPUT_INVALID');
            if (isset($s['acts'][$nonce])) {
                $old = $s['acts'][$nonce];
                A::require($old['input_digest'] === A::digest($input), 'NAT022_CONFLICTING_REPLAY');
                NativeTrust::verify($s, $d, $o, $old['accepted_at'], true); A::intact($old['result']);
                return $old['result']; // Retained completed effect, not authority renewed now.
            }
            $p = NativeTrust::verify($s, $d, $o, $this->now());
            $this->shape($p['effect'], $o);
            A::require(array_key_exists('expected_head', $o) && $o['expected_head'] === $s['head'], 'NAT023_STALE_REGISTRY_HEAD');
            $effect = $p['effect'];
            if (in_array($effect, ['ADOPT_ROSTER', 'SUPERSEDE_RECRUITER', 'RETIRE_ROSTER'], true)) {
                $result = $this->rosterAct($s, $effect, $o, $p);
            } elseif ($effect === 'REVISE_GARRISON') {
                $result = $this->revise($s, $o, $p);
            } else {
                A::keys($o, ['expected_head', 'target']);
                if ($effect === 'REVOKE_DECISION') {
                    A::require(is_string($o['target']) && preg_match('/^[a-f0-9]{48}$/D', $o['target']), 'NAT024_REVOCATION_INVALID');
                    $s['revoked'][$o['target']] = $nonce;
                } else {
                    A::require($effect === 'REVOKE_ISSUER' && $o['target'] === $s['trust']['fingerprint'], 'NAT024_REVOCATION_INVALID');
                    $s['issuer_revoked'] = true;
                }
                $result = ['target' => $o['target']];
            }
            $result = A::seal(['schema' => 'imperium.native-institutional-act/v1', 'effect' => $effect,
                'instance_id' => $s['trust']['instance_id'], 'registry_revision' => $s['revision'] + 1, 'previous_head' => $s['head'],
                'decision_digest' => A::digest($d), 'decision_nonce' => $nonce, 'object_digest' => A::digest($o),
                'accepted_at' => $this->now(), 'result' => $result, 'authority_consumed' => true, ...A::flags()]);
            $s['revision']++; $s['head'] = $result['record_digest'];
            $s['acts'][$nonce] = ['input_digest' => A::digest($input), 'decision' => $d, 'object' => $o,
                'accepted_at' => $this->now(), 'result' => $result];
            return $result;
        });
    }
    private function rosterAct(array &$s, string $effect, array $o, array $p): array
    {
        A::keys($o, ['expected_head', 'seat', 'actor', 'occupancy_generation', 'prior_roster', 'evidence', 'effective_at', 'expires_at']);
        A::require(in_array($o['seat'], ['conscription.recruiter', 'garrison.constable'], true), 'NAT025_SEAT_UNSUPPORTED');
        A::id($o['actor']); $this->interval($s, $o);
        A::require(is_int($o['occupancy_generation']) && $o['occupancy_generation'] > 0, 'NAT026_GENERATION_INVALID');
        $prior = $s['roster'][$o['seat']] ?? null;
        A::require($o['prior_roster'] === ($prior['record_digest'] ?? null), 'NAT027_STALE_ROSTER');
        if ($effect === 'ADOPT_ROSTER') {
            A::require($prior === null, 'NAT028_DUPLICATE_ADOPTION');
            if ($o['seat'] === 'conscription.recruiter') {
                $this->recruiter->inspect($o['evidence']); $source = $o['evidence']['source'];
                A::require($source['instance_id'] === $s['trust']['instance_id'] && $source['successor']['manifestation_id'] === $o['actor']
                    && $source['successor']['occupancy_generation'] === $o['occupancy_generation'], 'NAT029_EVIDENCE_MISMATCH');
            } else {
                $original = $this->original($s); A::require(A::digest($original) === A::digest($o['evidence'])
                    && $original['manifestation_id'] === $o['actor'] && $original['occupancy_generation'] === $o['occupancy_generation'], 'NAT029_EVIDENCE_MISMATCH');
            }
            $provenance = 'OWNER_ATTESTED_RETAINED_EVIDENCE_NOT_AUTHENTICATED_HISTORICAL_PRODUCER';
        } elseif ($effect === 'SUPERSEDE_RECRUITER') {
            $this->current($s, 'conscription.recruiter');
            A::require($o['seat'] === 'conscription.recruiter' && $o['actor'] !== $prior['actor']
                && $o['occupancy_generation'] === $prior['occupancy_generation'] + 1, 'NAT026_GENERATION_INVALID');
            A::keys($o['evidence'], ['source_id', 'source_digest']); A::id($o['evidence']['source_id']); A::hex($o['evidence']['source_digest']);
            $provenance = 'PROSPECTIVE_OWNER_SUCCESSION_NO_HISTORICAL_QUALIFICATION_INFERRED';
        } else {
            A::require($prior !== null && $prior['status'] === 'ACTIVE' && $o['actor'] === $prior['actor']
                && $o['occupancy_generation'] === $prior['occupancy_generation'] && $o['evidence'] === null, 'NAT030_RETIREMENT_INVALID');
            $provenance = $prior['provenance'];
        }
        $record = A::seal(['schema' => 'imperium.native-roster-revision/v1', 'instance_id' => $s['trust']['instance_id'],
            'seat' => $o['seat'], 'actor' => $o['actor'], 'occupancy_generation' => $o['occupancy_generation'],
            'roster_revision' => ($prior['roster_revision'] ?? 0) + 1, 'previous_digest' => $o['prior_roster'],
            'status' => $effect === 'RETIRE_ROSTER' ? 'RETIRED' : 'ACTIVE', 'evidence_digest' => A::digest($o['evidence']),
            'provenance' => $provenance, 'historical_producer_authenticated' => false, 'decision_nonce' => $p['nonce'],
            'effective_at' => $o['effective_at'], 'expires_at' => $o['expires_at'], ...A::flags()]);
        $s['roster'][$o['seat']] = $record; return $record;
    }
    private function revise(array &$s, array $o, array $p): array
    {
        A::keys($o, ['expected_head', 'roster_digest', 'prior_revision', 'request', 'occupancy']);
        $roster = $this->current($s, 'garrison.constable'); $original = $this->original($s);
        $this->requests->inspect(['request' => $o['request'], 'occupancy' => $o['occupancy']]);
        A::require($o['roster_digest'] === $roster['record_digest'] && A::digest($o['occupancy']) === A::digest($original)
            && $o['prior_revision'] === ($s['garrison_revision']['record_digest'] ?? null), 'NAT031_STALE_AUTHORITY_REVISION');
        $terms = $o['request']['terms'];
        A::require(!isset($s['request_replays'][$terms['request_nonce']]), 'NAT043_REQUEST_REPLAY_CONFLICT');
        $expectedPrior = $s['garrison_revision'] === null ? null : ['id' => $s['garrison_revision']['revision_id'], 'digest' => $s['garrison_revision']['record_digest']];
        A::require($terms['prior_revision'] === $expectedPrior && $terms['manifestation_id'] === $roster['actor']
            && $terms['occupancy_generation'] === $roster['occupancy_generation'], 'NAT031_STALE_AUTHORITY_REVISION');
        $this->interval($s, $terms);
        $record = A::seal(['schema' => 'imperium.native-garrison-authority-revision/v1', 'revision_id' => 'native-garrison-revision-'.$p['nonce'],
            'authority_revision' => ($s['garrison_revision']['authority_revision'] ?? 0) + 1, 'previous_digest' => $o['prior_revision'],
            'roster_digest' => $o['roster_digest'], 'binding_digest' => $original['record_digest'], 'actor' => $roster['actor'],
            'occupancy_generation' => $roster['occupancy_generation'], 'extension' => GarrisonAuthorityRequest::EXTENSION,
            'decision_nonce' => $p['nonce'], 'effective_at' => $terms['effective_at'], 'expires_at' => $terms['expires_at'], ...A::flags()]);
        $s['request_replays'][$terms['request_nonce']] = $o['request']['record_digest'];
        $s['garrison_revision'] = $record; return $record;
    }
    public function resolve(string $seat): array
    {
        return $this->journal->inspect(function (array $f) use ($seat): array {
            $r = $this->current($f['state'], $seat);
            if ($seat === 'garrison.constable') { $this->original($f['state']); }
            return ['roster' => $r, 'observed_at' => $this->now(), 'registry_head' => $f['state']['head'],
                'frame_digest' => $f['record_digest'], 'current_at_locked_observation_only' => true, ...A::flags()];
        });
    }
    private function current(array $s, string $seat): array
    {
        $t = $s['trust'] ?? []; $r = $s['roster'][$seat] ?? null;
        A::require($t !== [] && !$s['issuer_revoked'] && $this->now() >= $t['not_before'] && $this->now() < $t['expires_at']
            && is_array($r) && $r['status'] === 'ACTIVE' && !isset($s['revoked'][$r['decision_nonce']])
            && $this->now() >= $r['effective_at'] && $this->now() < $r['expires_at'], 'NAT032_CURRENT_ROSTER_REQUIRED');
        A::intact($r); return $r;
    }
    private function original(array $s): array
    {
        $files = glob($this->journal->root.'/var/imperium/offices/garrison/occupancy/*.json') ?: [];
        A::require(count($files) === 1, 'NAT033_UNIQUE_ORIGINAL_REQUIRED'); $o = A::read($files[0]);
        $this->requests->prepare(['schema' => 'imperium.garrison-authority-preparation/v1', 'occupancy' => $o,
            'prior_revision' => null, 'effective_at' => 1, 'expires_at' => 2, 'request_nonce' => str_repeat('0', 48)]);
        A::require($o['instance_id'] === $s['trust']['instance_id'] && basename($files[0]) === $o['binding_id'].'.json', 'NAT029_EVIDENCE_MISMATCH');
        if (isset($s['roster']['garrison.constable'])) {
            A::require($s['roster']['garrison.constable']['evidence_digest'] === A::digest($o), 'NAT029_EVIDENCE_MISMATCH');
        }
        return $o;
    }
    private function interval(array $s, array $o): void
    {
        A::require(is_int($o['effective_at']) && is_int($o['expires_at']) && $o['effective_at'] > 0
            && $o['effective_at'] <= $this->now() && $o['expires_at'] > $this->now()
            && $o['expires_at'] <= $s['trust']['expires_at'], 'NAT034_EFFECT_INTERVAL_INVALID');
    }
    public function admit(string $deliveryId, string $bindingId): array
    {
        A::require(preg_match('/^guildhall-garrison-persona-admission-delivery-[a-f0-9]{20}$/D', $deliveryId) === 1, 'NAT035_DELIVERY_INVALID');
        return $this->journal->change(function (array &$s) use ($deliveryId, $bindingId): array {
            $d = A::read($this->journal->root.'/var/imperium/offices/garrison/inbox/canonical-subordinate-persona-admissions/'.$deliveryId.'.json');
            A::intact($d);
            if (isset($s['admissions'][$deliveryId])) {
                $old = $s['admissions'][$deliveryId];
                A::require($old['delivery_digest'] === $d['record_digest'] && $old['binding_id'] === $bindingId, 'NAT022_CONFLICTING_REPLAY');
                foreach (['roster_nonce', 'authority_nonce'] as $key) {
                    $act = $s['acts'][$old[$key]] ?? null; A::require(is_array($act), 'NAT042_RETAINED_AUTHORITY_INVALID');
                    NativeTrust::verify($s, $act['decision'], $act['object'], $act['accepted_at'], true); A::intact($act['result']);
                    $witness = $act['result']['result'];
                    A::require($old['accepted_at'] >= $witness['effective_at'] && $old['accepted_at'] < $witness['expires_at'], 'NAT042_RETAINED_AUTHORITY_INVALID');
                    $digestKey = $key === 'roster_nonce' ? 'roster_digest' : 'authority_revision_digest';
                    A::require($witness['record_digest'] === $old['disposition']['constable'][$digestKey], 'NAT042_RETAINED_AUTHORITY_INVALID');
                    if (isset($s['revoked'][$old[$key]])) {
                        A::require($s['acts'][$s['revoked'][$old[$key]]]['result']['registry_revision'] > $old['observed_registry_revision'], 'NAT042_RETAINED_AUTHORITY_INVALID');
                    }
                }
                foreach ($s['acts'] as $act) {
                    if ($act['result']['effect'] === 'REVOKE_ISSUER') A::require($act['result']['registry_revision'] > $old['observed_registry_revision'], 'NAT042_RETAINED_AUTHORITY_INVALID');
                }
                A::intact($old['disposition']); A::intact($old['custody']); return $old['disposition'];
            }
            $r = $this->current($s, 'garrison.constable'); $o = $this->original($s); $v = $s['garrison_revision'];
            A::require($bindingId === $o['binding_id'] && is_array($v) && $v['roster_digest'] === $r['record_digest']
                && $v['binding_digest'] === $o['record_digest'] && !isset($s['revoked'][$v['decision_nonce']]), 'NAT036_EFFECTIVE_ADMISSION_AUTHORITY_REQUIRED');
            $this->interval($s, $v); A::intact($v);
            $effective = $o; unset($effective['record_digest']);
            foreach (GarrisonAuthorityRequest::EXTENSION as $power => $scope) { $effective[$power] = true; }
            $effective = A::seal($effective);
            $records = NativeAdmission::build($deliveryId, $d, $effective, $o, $r, $v);
            foreach ($s['admissions'] as $previous) {
                A::require($previous['custody']['persona_id'] !== $records['custody']['persona_id'], 'NAT037_CANDIDATE_ALREADY_ADMITTED');
            }
            $s['admissions'][$deliveryId] = ['delivery_digest' => $d['record_digest'], 'binding_id' => $bindingId,
                'roster_nonce' => $r['decision_nonce'], 'authority_nonce' => $v['decision_nonce'], 'accepted_at' => $this->now(),
                'observed_registry_revision' => $s['revision'], ...$records];
            return $records['disposition'];
        });
    }
    public function inventory(string $inquiryId): array
    {
        A::require(preg_match('/^garrison-inquiry-[a-f0-9]{20}$/D', $inquiryId) === 1, 'NAT038_INQUIRY_INVALID');
        return $this->journal->inspect(function (array $f) use ($inquiryId): array {
            $s = $f['state']; $r = $this->current($s, 'garrison.constable'); $o = $this->original($s);
            A::require(($o['inventory_response_authority'] ?? null) === true && $r['actor'] === $o['manifestation_id'], 'NAT039_INVENTORY_POWER_REQUIRED');
            $q = A::read($this->journal->root.'/var/imperium/offices/garrison/inbox/'.$inquiryId.'.json'); A::intact($q);
            A::require(($q['inquiry_id'] ?? null) === $inquiryId && ($q['instance_id'] ?? null) === $s['trust']['instance_id']
                && in_array($q['status'] ?? null, ['CONSTABLE_ACTIVATION_REQUIRED', 'DELIVERED_PENDING_CONSTABLE_RESPONSE'], true), 'NAT038_INQUIRY_INVALID');
            foreach ($q as $key => $value) {
                if (str_ends_with((string) $key, '_authority') || in_array($key, ['authoritative_inventory_response'], true)) { A::require($value === false, 'NAT038_INQUIRY_INVALID'); }
            }
            $custody = array_values(array_map(fn ($a) => $a['custody'], $s['admissions']));
            foreach (glob($this->journal->root.'/var/imperium/offices/garrison/custody/*.json') ?: [] as $path) {
                $c = A::read($path); A::intact($c);
                A::require(($c['schema'] ?? null) === 'imperium.garrison-persona-custody/v1' && ($c['custody_state'] ?? null) === 'ADMITTED_HELD', 'NAT040_CUSTODY_INVALID');
                $custody[] = $c;
            }
            $ids = array_column($custody, 'persona_id'); A::require(count($ids) === count(array_unique($ids)), 'NAT040_CUSTODY_INVALID');
            usort($custody, fn ($a, $b) => $a['persona_id'] <=> $b['persona_id']);
            return A::seal(['schema' => 'imperium.native-garrison-inventory-response/v1', 'inquiry_id' => $inquiryId,
                'source_inquiry_digest' => $q['record_digest'], 'instance_id' => $s['trust']['instance_id'],
                'responder' => ['occupancy_digest' => $o['record_digest'], 'roster_digest' => $r['record_digest'], 'actor' => $r['actor']],
                'inventory_records' => $custody, 'authoritative_inventory_response' => true,
                'registry_head' => $s['head'], 'frame_digest' => $f['record_digest'], 'observed_at' => $this->now(), ...A::flags()]);
        });
    }
    private function now(): int { return $this->clock->now()->getTimestamp(); }
    private function shape(string $effect, array $o): void
    {
        $keys = match ($effect) {
            'ADOPT_ROSTER', 'SUPERSEDE_RECRUITER', 'RETIRE_ROSTER' => ['expected_head', 'seat', 'actor', 'occupancy_generation', 'prior_roster', 'evidence', 'effective_at', 'expires_at'],
            'REVISE_GARRISON' => ['expected_head', 'roster_digest', 'prior_revision', 'request', 'occupancy'],
            'REVOKE_DECISION', 'REVOKE_ISSUER' => ['expected_head', 'target'],
            default => throw new \RuntimeException('NAT020_EFFECT_UNSUPPORTED'),
        };
        A::keys($o, $keys);
    }
}
