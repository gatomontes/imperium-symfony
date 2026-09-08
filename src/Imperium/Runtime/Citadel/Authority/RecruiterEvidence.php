<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Authority;

use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Clock;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Future owner-authorized private read; separate from the public Python collector. */
final readonly class RecruiterEvidence
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private Clock $clock) {}

    public function export(string $instance): array
    {
        AuthorityInput::id($instance);
        // Existing cooperating writers use this lock. It can create the lock/directory.
        $store = new StateStore($this->root);
        set_error_handler(static function (): never { throw new \RuntimeException('CAI010_PRIVATE_SOURCE_UNAVAILABLE'); });
        try {
            return $store->locked(function () use ($instance): array {
                $state = AuthorityInput::read($this->root.'/var/imperium/bootstrap-state.json');
                $projection = self::project($state, $instance);
                return AuthorityInput::seal(['schema' => 'imperium.recruiter-public-projection/v1',
                    'producer' => 'RecruiterEvidence::export/v1', 'observed_at' => $this->clock->now()->getTimestamp(),
                    'source' => $projection, 'source_projection_digest' => AuthorityInput::digest($projection),
                    'source_original_digest' => null, 'authenticated_provenance' => false,
                    'currentness' => 'UNVERIFIED_NATIVE_REVOCATION_AND_SUPERSESSION_SOURCE_REQUIRED',
                    'observation_boundary' => 'BOOTSTRAP_LOCK_COOPERATING_WRITERS_ONLY',
                    ...AuthorityInput::flags()]);
            });
        } catch (\Throwable $e) {
            // StateStore and filesystem exceptions may contain private paths. Do not chain them.
            $code = preg_match('/^CAI[0-9]{3}_[A-Z0-9_]+$/D', $e->getMessage()) ? $e->getMessage() : 'CAI010_PRIVATE_SOURCE_UNAVAILABLE';
            throw new \RuntimeException($code);
        } finally {
            restore_error_handler();
        }
    }

    private static function project(array $state, string $instance): array
    {
        AuthorityInput::require(($state['schema'] ?? null) === 'imperium.bootstrap-state/v1', 'CAI011_SOURCE_VERSION_UNSUPPORTED');
        AuthorityInput::require(($state['state'] ?? null) === 'CURIA_READY', 'CAI012_SOURCE_NOT_READY');
        AuthorityInput::require(($state['binding']['instance_id'] ?? null) === $instance, 'CAI013_INSTANCE_MISMATCH');
        $generation = $state['generation'] ?? null;
        AuthorityInput::require(is_int($generation) && $generation >= 4, 'CAI014_SOURCE_REVISION_INVALID');
        $events = $state['events'] ?? null;
        AuthorityInput::require(is_array($events) && array_is_list($events) && count($events) <= 4096, 'CAI014_SOURCE_REVISION_INVALID');
        $found = []; $previous = 0;
        foreach ($events as $event) {
            AuthorityInput::require(is_array($event), 'CAI014_SOURCE_REVISION_INVALID');
            $revision = $event['generation'] ?? null;
            AuthorityInput::require(is_int($revision) && $revision > $previous && $revision <= $generation, 'CAI014_SOURCE_REVISION_INVALID');
            $previous = $revision;
            if (in_array($event['transition'] ?? null, ['T03', 'T04'], true)) {
                $key = $event['transition'];
                AuthorityInput::require(!isset($found[$key]), 'CAI015_SUCCESSION_AMBIGUOUS');
                AuthorityInput::require(($event['result'] ?? null) === 'SUCCESS', 'CAI016_SUCCESSION_FAILED');
                $found[$key] = $event;
            }
        }
        AuthorityInput::require(isset($found['T03'], $found['T04']), 'CAI017_SUCCESSION_MISSING');
        AuthorityInput::require($previous === $generation && $found['T03']['generation'] < $found['T04']['generation'], 'CAI014_SOURCE_REVISION_INVALID');
        $t03 = $found['T03']['output'] ?? null; $t04 = $found['T04']['output'] ?? null;
        AuthorityInput::keys($t03, ['manifestation_id', 'seat', 'occupancy_generation', 'authority']);
        AuthorityInput::keys($t04, ['commission', 'retired', 'successor', 'qualification_packet', 'qualification_packet_digest']);
        $source = ['instance_id' => $instance, 'manifest_id' => $state['binding']['manifest_id'] ?? null,
            'state_generation' => $generation, 'transition_generation' => $found['T04']['generation'],
            'transition' => 'T04', 'predecessor' => $t03, ...$t04];
        self::validateSource($source);
        return $source;
    }

    private static function validateSource(array $s): void
    {
        AuthorityInput::keys($s, ['instance_id', 'manifest_id', 'state_generation', 'transition_generation', 'transition',
            'predecessor', 'commission', 'retired', 'successor', 'qualification_packet', 'qualification_packet_digest']);
        foreach (['instance_id', 'manifest_id'] as $key) { AuthorityInput::id($s[$key]); }
        AuthorityInput::require($s['transition'] === 'T04' && is_int($s['state_generation']) && is_int($s['transition_generation'])
            && $s['transition_generation'] >= 4 && $s['state_generation'] >= $s['transition_generation'], 'CAI014_SOURCE_REVISION_INVALID');
        $p = $s['predecessor']; $r = $s['retired']; $n = $s['successor']; $c = $s['commission']; $q = $s['qualification_packet'];
        AuthorityInput::keys($p, ['manifestation_id', 'seat', 'occupancy_generation', 'authority']);
        AuthorityInput::keys($r, ['manifestation_id', 'seat', 'occupancy_generation', 'disposition']);
        AuthorityInput::keys($n, ['manifestation_id', 'seat', 'occupancy_generation', 'authority', 'predecessor']);
        AuthorityInput::keys($c, ['id', 'digest', 'consumed']);
        AuthorityInput::keys($q, ['commission_id', 'commission_digest', 'candidate_id', 'profile', 'substrate', 'qualification_contract', 'checks']);
        AuthorityInput::keys($q['checks'], ['exact_profile_installation', 'declared_authority_restraint', 'version_and_provenance_preservation']);
        AuthorityInput::hex($c['digest']); AuthorityInput::hex($s['qualification_packet_digest']);
        AuthorityInput::require(AuthorityInput::digest($p) === AuthorityInput::digest(['manifestation_id' => $s['instance_id'].'.officer.provisional-recruiter.1',
            'seat' => 'conscription.recruiter', 'occupancy_generation' => 1, 'authority' => 'succession-only'])
            && AuthorityInput::digest($r) === AuthorityInput::digest(['manifestation_id' => $p['manifestation_id'], 'seat' => $p['seat'], 'occupancy_generation' => 1, 'disposition' => 'retired-after-succession'])
            && AuthorityInput::digest($n) === AuthorityInput::digest(['manifestation_id' => $s['instance_id'].'.officer.ordinary-recruiter.1', 'seat' => 'conscription.recruiter',
                'occupancy_generation' => 2, 'authority' => 'ordinary-recruiter', 'predecessor' => $p['manifestation_id']]), 'CAI018_SUCCESSION_CONTRADICTORY');
        AuthorityInput::require($c['id'] === 'primordial.recruiter-succession.1' && $c['consumed'] === true
            && $q['commission_id'] === $c['id'] && $q['commission_digest'] === $c['digest'] && $q['candidate_id'] === $n['manifestation_id']
            && $q['profile'] === 'conscription.recruiter.ordinary@1.0.0' && $q['substrate'] === 'generic-officer.ordinary-recruiter@1.0.0'
            && $q['qualification_contract'] === 'qualification.conscription.recruiter.ordinary.v1'
            && array_values($q['checks']) === [true, true, true]
            && AuthorityInput::digest($q) === $s['qualification_packet_digest'], 'CAI019_QUALIFICATION_MISMATCH');
    }

    public function inspect(array $packet): array
    {
        AuthorityInput::keys($packet, ['schema', 'producer', 'observed_at', 'source', 'source_projection_digest', 'source_original_digest',
            'authenticated_provenance', 'currentness', 'observation_boundary', 'live_ready', 'activation', 'execution_authority', 'record_digest']);
        AuthorityInput::intact($packet);
        AuthorityInput::require($packet['schema'] === 'imperium.recruiter-public-projection/v1' && $packet['producer'] === 'RecruiterEvidence::export/v1', 'CAI020_PROJECTION_VERSION_UNSUPPORTED');
        AuthorityInput::require(is_int($packet['observed_at']) && $packet['observed_at'] > 0
            && $packet['source_original_digest'] === null && $packet['authenticated_provenance'] === false
            && $packet['currentness'] === 'UNVERIFIED_NATIVE_REVOCATION_AND_SUPERSESSION_SOURCE_REQUIRED'
            && $packet['observation_boundary'] === 'BOOTSTRAP_LOCK_COOPERATING_WRITERS_ONLY'
            && $packet['live_ready'] === false && $packet['activation'] === false && $packet['execution_authority'] === false, 'CAI021_BORROWED_PROVENANCE_REFUSED');
        self::validateSource($packet['source']);
        AuthorityInput::require($packet['source_projection_digest'] === AuthorityInput::digest($packet['source']), 'CAI002_DIGEST_MISMATCH');
        return ['disposition' => 'STRUCTURAL_PROJECTION_ONLY_AUTHORITY_BLOCKED', 'projection_digest' => $packet['record_digest'],
            'authenticated_provenance' => false, 'currentness_verified' => false,
            'blockers' => ['NATIVE_RECRUITER_PROVENANCE_ISSUER_UNAVAILABLE', 'AUTHORITATIVE_REVOCATION_SUPERSESSION_RESOLVER_UNAVAILABLE'], ...AuthorityInput::flags()];
    }

    /** A fresh locked comparison cannot authenticate a historical export or confer current authority. */
    public function compareAtSource(array $packet, string $instance): array
    {
        $result = $this->inspect($packet);
        AuthorityInput::require($packet['source']['instance_id'] === $instance, 'CAI013_INSTANCE_MISMATCH');
        $now = $this->export($instance);
        AuthorityInput::require($packet['source_projection_digest'] === $now['source_projection_digest'], 'CAI022_STALE_SOURCE_PROJECTION');
        return [...$result, 'same_at_local_snapshot' => true, 'compared_at' => $now['observed_at']];
    }
}
