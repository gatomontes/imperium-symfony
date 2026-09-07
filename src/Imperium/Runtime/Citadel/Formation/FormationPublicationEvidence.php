<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;

/** Verifies a fact in trusted custody. Never issues, renews, or consumes authority.
 * The timestamp is admissible only with the retained journal frame AND the exact
 * receipt written by the serialized child publisher, not with caller-supplied proof.
 */
final class FormationPublicationEvidence
{
    public static function verify(FormationJournal $journal, array $frame, array $reservation, array $proof, int $now): void
    {
        $prepared = $reservation['prepared'] ?? [];
        $state = $frame['state'];
        $id = $prepared['packet']['intake_id'] ?? '';
        $original = $state['reservations'][$id] ?? [];
        // DELIVERED may be observed by a concurrent recognizer after it read its receipt.
        $fence = $reservation;
        $fence['status'] = 'EFFECT_UNCERTAIN_IDENTITY_FENCED';
        unset($fence['reconciliation']);
        if (!FormationJournal::keys($proof, ['schema', 'authority_generation', 'authority_frame_digest', 'authorized_at', 'reservation_digest', 'prepared_digest', 'institutions'])
            || $proof['schema'] !== 'imperium.citadel-child-publication/v1'
            || $proof['authority_generation'] !== $frame['generation'] || $proof['authority_frame_digest'] !== $frame['record_digest']
            || !is_int($proof['authorized_at']) || $proof['authorized_at'] > $now
            || $proof['reservation_digest'] !== FormationJournal::digest($original)
            || FormationJournal::digest($fence) !== FormationJournal::digest($original)
            || $proof['prepared_digest'] !== FormationJournal::digest($prepared)
            || ($original['status'] ?? null) !== 'EFFECT_UNCERTAIN_IDENTITY_FENCED'
            || !in_array($reservation['status'] ?? null, ['EFFECT_UNCERTAIN_IDENTITY_FENCED', 'DELIVERED'], true)) {
            throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: retained fence or frame mismatch');
        }
        $review = $state['reviews'][$original['review_id']] ?? [];
        $constitution = $prepared['constitution'];
        $terms = $constitution['terms'];
        if ($original['approval_digest'] !== FormationJournal::digest($review)
            || ($review['disposition'] ?? null) !== 'APPROVE'
            || $review['terms'] !== $terms || $review['decision'] !== $constitution['decision']
            || $constitution['signed_review'] !== ['terms' => $terms, 'line_digests' => $review['line_digests'], 'rationale' => $review['rationale']]
            || $terms['expires_at'] <= $proof['authorized_at']
            || $terms['citadel_id'] !== $state['citadel_id'] || $terms['parent_instance_id'] !== $state['parent_instance_id']
            || $prepared['mission_id'] !== $original['mission_id'] || $prepared['curia_id'] !== $original['curia_id']
            || $terms['mission_id'] !== $prepared['mission_id'] || $terms['curia_id'] !== $prepared['curia_id']
            || $original['source_citadel_id'] !== $state['citadel_id']) {
            throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: original approval mismatch');
        }
        // A private historical clock is used only for signature verification here.
        // New-effect consumers retain their ordinary current-clock verifier.
        $clock = new class($proof['authorized_at']) implements Clock {
            public function __construct(private int $at) {}
            public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->at); }
        };
        $signatures = new FormationSignatures($journal, $clock);
        $signatures->verify($state, $constitution['decision'], 'APPROVE_MISSION_AND_CONSTITUTION', $constitution['signed_review']);
        foreach ($terms['appointments'] as $seat => $candidate) {
            $signatures->verify($state, $candidate['profile_approval'], 'APPROVE_FORMATION_PROFILE', [
                'profile' => $candidate['profile'], 'examination' => $candidate['examination'], 'scope' => $terms['mission_id'], 'seat' => $seat]);
        }
        $evidence = $prepared['packet']['personnel_evidence'];
        if ($evidence['public_trust'] !== $state['trust'] || $evidence['parent_instance_id'] !== $state['parent_instance_id']) {
            throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: untrusted evidence source');
        }
        // Reverify all retained public institutional signatures and exact native sources.
        // Their later expiry/succession cannot erase the publisher's historical observation.
        foreach ($evidence['delegations'] as $key => $delegation) {
            if (($state['personnel_delegations'][$key] ?? null) !== $delegation
                || $key !== FormationJournal::digest($delegation['terms'])) {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: delegation mismatch');
            }
            $d = $delegation['terms'];
            // Prior cognition may legitimately predate current personnel delegations.
            // Only appointment sources must still be current at child publication.
            $issued = $delegation['decision']['payload']['issued_at'];
            $sig = base64_decode($delegation['decision']['signature'], true);
            $public = base64_decode($state['trust']['public_key'], true);
            if (!is_string($sig) || strlen($sig) !== 64
                || !sodium_crypto_sign_verify_detached($sig, CanonicalJson::encode($delegation['decision']['payload']), $public)
                || $delegation['decision']['payload']['effect'] !== 'DELEGATE_PERSONNEL_EVIDENCE'
                || $delegation['decision']['payload']['object_digest'] !== FormationJournal::digest($d)
                || $issued > $proof['authorized_at']) {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: institutional signature');
            }
        }
        foreach ($evidence['evidence'] as $key => $envelope) {
            $payload = $envelope['payload'];
            $delegation = $evidence['delegations'][$payload['delegation']]['terms'] ?? [];
            $signature = base64_decode($envelope['signature'], true);
            $public = base64_decode($delegation['public_key'] ?? '', true);
            if (($state['personnel_evidence'][$key] ?? null) !== $envelope || $key !== FormationJournal::digest($envelope)
                || !is_string($signature) || strlen($signature) !== 64 || !is_string($public) || strlen($public) !== 32
                || !sodium_crypto_sign_verify_detached($signature, CanonicalJson::encode($payload), $public)) {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: personnel evidence signature');
            }
        }
        foreach ($terms['appointments'] as $candidate) {
            $sources = array_map(static fn (string $key): string => $candidate[$key], ['persona', 'suitability', 'profile', 'examination', 'qualification']);
            $sources = [...$sources, ...array_values($evidence['evidence'][$candidate['examination']]['payload']['content']['findings'])];
            foreach ($sources as $source) {
                $p = $evidence['evidence'][$source]['payload'];
                $d = $evidence['delegations'][$p['delegation']];
                $signatures->verify($state, $d['decision'], 'DELEGATE_PERSONNEL_EVIDENCE', $d['terms']);
                if ($p['expires_at'] <= $proof['authorized_at'] || $p['expires_at'] > $d['terms']['expires_at']) {
                    throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: expired appointment evidence');
                }
                self::institution($proof['institutions'][$d['terms']['role']] ?? [], $d['terms']['actor']);
            }
        }
    }

    private static function institution(array $witness, array $actor): void
    {
        $record = $witness['occupancy'] ?? [];
        $source = $witness['installation'] ?? [];
        foreach ([$record, $source] as $value) {
            $digest = $value['record_digest'] ?? null;
            unset($value['record_digest']);
            if ($digest !== FormationJournal::digest($value)) {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: native witness corrupt');
            }
        }
        $binding = $record;
        unset($binding['record_digest'], $binding['source_installation_digest']);
        if (($witness['actor'] ?? null) !== $actor || ($record['status'] ?? null) !== 'ACTIVE'
            || ($source['schema'] ?? null) !== 'imperium.operator-root-personnel-installation-record/v2'
            || ($source['provenance'] ?? null) !== 'OPERATOR_ROOT_INSTALLATION'
            || FormationJournal::digest($binding) !== FormationJournal::digest($source['binding'] ?? [])
            || ($record['source_installation_digest'] ?? null) !== $source['record_digest']
            || $actor['binding_digest'] !== $record['record_digest'] || $actor['installation_digest'] !== $source['record_digest']
            || $actor['instance_id'] !== $record['instance_id'] || $actor['seat'] !== $record['seat']
            || $actor['manifestation_id'] !== $record['manifestation_id'] || $actor['binding_id'] !== $record['binding_id']
            || $actor['occupancy_generation'] !== $record['occupancy_generation']) {
            throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: native witness mismatch');
        }
    }
}
