<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;

/** An evidence ingress, not an automatic selection or qualification policy.
 * Garrison, Guildhall, Laboratorium and Conscription remain distinct authors.
 */
final readonly class FormationPersonnel
{
    public function __construct(private FormationJournal $journal, private FormationSignatures $signatures, private Clock $clock, private FormationInstitution $institution) {}

    public function authoritySource(string $role): array { return $this->institution->actor($role); }

    public function delegate(array $delegation, array $decision): string
    {
        return $this->journal->change(function (array &$state) use ($delegation, $decision): string {
            $this->signatures->verify($state, $decision, 'DELEGATE_PERSONNEL_EVIDENCE', $delegation);
            $key = base64_decode($delegation['public_key'] ?? '', true);
            if (!FormationJournal::keys($delegation, ['role', 'public_key', 'scope', 'expires_at', 'actor'])
                || !isset(FormationInstitution::SEATS[$delegation['role']])
                || !is_string($key) || strlen($key) !== 32 || !is_string($delegation['scope'])
                || !is_int($delegation['expires_at']) || $delegation['expires_at'] > $decision['payload']['expires_at']) {
                throw new \RuntimeException('CMF040_PERSONNEL_DELEGATION_INVALID');
            }
            $actor = $this->institution->actor($delegation['role']);
            if (FormationJournal::digest($delegation['actor']) !== FormationJournal::digest($actor) || (isset($state['parent_instance_id']) && $state['parent_instance_id'] !== $actor['instance_id'])) {
                throw new \RuntimeException('CMF122_INSTITUTION_CHAIN_INVALID');
            }
            $state['parent_instance_id'] ??= $actor['instance_id'];
            $id = FormationJournal::digest($delegation);
            $state['personnel_delegations'][$id] = ['terms' => $delegation, 'decision' => $decision];
            return $id;
        });
    }

    public function record(array $envelope): string
    {
        return $this->journal->change(function (array &$state) use ($envelope): string {
            $payload = $envelope['payload'] ?? [];
            $delegation = $state['personnel_delegations'][$payload['delegation'] ?? ''] ?? null;
            if (!is_array($delegation)) { throw new \RuntimeException('CMF041_PERSONNEL_EVIDENCE_INVALID'); }
            $this->signatures->verify($state, $delegation['decision'], 'DELEGATE_PERSONNEL_EVIDENCE', $delegation['terms']);
            $terms = $delegation['terms'];
            if ($terms['actor'] !== $this->institution->actor($terms['role'])) { throw new \RuntimeException('CMF122_INSTITUTION_CHAIN_INVALID'); }
            $sig = base64_decode($envelope['signature'] ?? '', true);
            if (!FormationJournal::keys($envelope, ['payload', 'signature'])
                || !FormationJournal::keys($payload, ['delegation', 'scope', 'kind', 'subject', 'sources', 'content', 'expires_at'])
                || !is_string($sig) || strlen($sig) !== 64
                || !sodium_crypto_sign_verify_detached($sig, CanonicalJson::encode($payload), base64_decode($terms['public_key'], true))
                || $payload['scope'] !== $terms['scope'] || !is_int($payload['expires_at'])
                || $payload['expires_at'] <= $this->clock->now()->getTimestamp() || $payload['expires_at'] > $terms['expires_at']
                || !is_string($payload['subject']) || '' === $payload['subject']
                || !is_array($payload['content']) || !is_array($payload['sources'])
                || ($payload['kind'] ?? null) !== match ($terms['role']) {
                    'garrison' => 'ADMITTED_PERSONA', 'guildhall' => 'SUITABLE_CANDIDATE',
                    'laboratorium' => 'DERIVED_PROFILE', 'senate' => 'EXAMINED_PROFILE', 'conscription' => 'QUALIFIED_MANIFESTATION',
                    default => 'SENATOR_FINDING',
                }) {
                throw new \RuntimeException('CMF041_PERSONNEL_EVIDENCE_INVALID');
            }
            foreach ($payload['sources'] as $source) {
                if (!is_string($source) || !isset($state['personnel_evidence'][$source])) {
                    throw new \RuntimeException('CMF042_PERSONNEL_SOURCE_ABSENT');
                }
            }
            $id = FormationJournal::digest($envelope);
            $state['personnel_evidence'][$id] = $envelope;
            return $id;
        });
    }

    /** Resolve complete evidence, including exact Profile approval, before binding.
     * Approval cannot stand in for any Office's attributed finding.
     */
    public function candidate(array $state, array $candidate, string $scope, string $seat): array
    {
        if (!FormationJournal::keys($candidate, ['persona', 'suitability', 'profile', 'examination', 'qualification', 'profile_approval'])) {
            throw new \RuntimeException('CMF043_CANDIDATE_CHAIN_INVALID');
        }
        $chain = [];
        foreach (['persona' => 'ADMITTED_PERSONA', 'suitability' => 'SUITABLE_CANDIDATE', 'profile' => 'DERIVED_PROFILE', 'examination' => 'EXAMINED_PROFILE', 'qualification' => 'QUALIFIED_MANIFESTATION'] as $field => $kind) {
            $envelope = $state['personnel_evidence'][$candidate[$field]] ?? [];
            $p = $envelope['payload'] ?? [];
            $d = $state['personnel_delegations'][$p['delegation'] ?? ''] ?? [];
            if (($p['kind'] ?? null) !== $kind || ($p['scope'] ?? null) !== $scope
                || ($p['expires_at'] ?? 0) <= $this->clock->now()->getTimestamp()) {
                throw new \RuntimeException('CMF043_CANDIDATE_CHAIN_INVALID');
            }
            $this->signatures->verify($state, $d['decision'], 'DELEGATE_PERSONNEL_EVIDENCE', $d['terms']);
            if ($d['terms']['actor'] !== $this->institution->actor($d['terms']['role'])) { throw new \RuntimeException('CMF122_INSTITUTION_CHAIN_INVALID'); }
            $chain[$field] = $p;
        }
        $this->signatures->verify($state, $candidate['profile_approval'], 'APPROVE_FORMATION_PROFILE', ['profile' => $candidate['profile'], 'examination' => $candidate['examination'], 'scope' => $scope, 'seat' => $seat]);
        if ($chain['suitability']['sources'] !== [$candidate['persona']]
            || $chain['profile']['sources'] !== [$candidate['persona'], $candidate['suitability']]
            || $chain['examination']['sources'] !== [$candidate['profile'], ...array_values($chain['examination']['content']['findings'] ?? [])]
            || $chain['qualification']['sources'] !== [$candidate['persona'], $candidate['suitability'], $candidate['profile'], $candidate['examination']]
            || ($chain['suitability']['content']['seat'] ?? null) !== $seat
            || ($chain['profile']['content']['seat'] ?? null) !== $seat
            || ($chain['qualification']['content']['seat'] ?? null) !== $seat
            || ($chain['qualification']['content']['disposition'] ?? null) !== 'QUALIFIED'
            || ($chain['qualification']['content']['profile_approval_digest'] ?? null) !== FormationJournal::digest($candidate['profile_approval'])
            || ($chain['examination']['content']['disposition'] ?? null) !== 'APPROVED'
            || ($chain['examination']['content']['security_block'] ?? true) !== false) {
            throw new \RuntimeException('CMF043_CANDIDATE_CHAIN_INVALID');
        }
        $profile = $chain['profile']['content']['artifact'] ?? [];
        $persona = $chain['persona']['content']['identity'] ?? [];
        FormationProfileContract::validate($profile, $persona, $seat);
        $findings = $chain['examination']['content']['findings'] ?? [];
        if (!FormationJournal::keys($findings, ['consistency', 'governance', 'practice', 'security'])) {
            throw new \RuntimeException('CMF125_COMPLETE_EXAMINATION_REQUIRED');
        }
        foreach ($findings as $criterion => $findingId) {
            $finding = $state['personnel_evidence'][$findingId]['payload'] ?? [];
            $delegation = $state['personnel_delegations'][$finding['delegation'] ?? ''] ?? [];
            if (($finding['kind'] ?? null) !== 'SENATOR_FINDING' || ($finding['scope'] ?? null) !== $scope
                || ($finding['sources'] ?? null) !== [$candidate['profile']]
                || ($finding['expires_at'] ?? 0) <= $this->clock->now()->getTimestamp()
                || ($delegation['terms']['role'] ?? null) !== 'senate-'.$criterion
                || ($finding['content']['disposition'] ?? null) !== 'PASS'
                || !is_string($finding['content']['rationale'] ?? null) || trim($finding['content']['rationale']) === '') {
                throw new \RuntimeException('CMF125_COMPLETE_EXAMINATION_REQUIRED');
            }
            $this->signatures->verify($state, $delegation['decision'], 'DELEGATE_PERSONNEL_EVIDENCE', $delegation['terms']);
            if ($delegation['terms']['actor'] !== $this->institution->actor('senate-'.$criterion)) {
                throw new \RuntimeException('CMF122_INSTITUTION_CHAIN_INVALID');
            }
        }
        $checks = $chain['qualification']['content']['criteria_results'] ?? [];
        if (!FormationJournal::keys($checks, $profile['qualification_contract']['criteria']) || in_array(false, array_values($checks), true)) {
            throw new \RuntimeException('CMF126_QUALIFICATION_CRITERIA_UNMET');
        }
        foreach ($checks as $result) { if ($result !== true) { throw new \RuntimeException('CMF126_QUALIFICATION_CRITERIA_UNMET'); } }
        if (($chain['persona']['subject'] ?? null) !== ($persona['persona_id'] ?? null)
            || $chain['suitability']['subject'] !== $persona['persona_id']
            || $chain['profile']['subject'] !== $profile['profile_id']
            || $chain['qualification']['subject'] !== $profile['profile_id']) {
            throw new \RuntimeException('CMF043_CANDIDATE_CHAIN_INVALID');
        }
        $lifecycle = [];
        $prior = null;
        foreach (['candidate' => 'profile', 'under_examination' => 'examination', 'approved' => 'profile_approval'] as $to => $source) {
            $actor = $source === 'profile_approval'
                ? ['kind' => 'imperator', 'id' => $candidate['profile_approval']['payload']['trust_fingerprint']]
                : ['kind' => 'seat', 'id' => $state['personnel_delegations'][$chain[$source]['delegation']]['terms']['actor']['seat']];
            $attestation = ['contract_version' => '1.0.0',
                'attestation_id' => 'profile-attestation-'.FormationJournal::digest([$candidate[$source], $to]),
                'profile_ref' => array_intersect_key($profile, array_flip(['profile_id', 'profile_version', 'content_digest'])),
                'transition' => $prior === null ? ['to' => $to] : ['from' => $lifecycle[count($lifecycle) - 1]['transition']['to'], 'to' => $to, 'prior_attestation_id' => $prior],
                'actor' => $actor, 'issued_at' => (new \DateTimeImmutable('@'.$candidate['profile_approval']['payload']['issued_at']))->format(DATE_ATOM),
                'correlation_id' => $scope, 'reason' => 'Exact authenticated formation source: '.FormationJournal::digest($candidate[$source]),
                'examination_disposition_id' => $candidate['examination']];
            $attestation['record_digest'] = 'sha256:'.FormationJournal::digest($attestation);
            $prior = $attestation['attestation_id'];
            $lifecycle[] = $attestation;
        }
        $qualifier = $state['personnel_delegations'][$chain['qualification']['delegation']]['terms']['actor'];
        return \App\Imperium\Runtime\Conscription\FormationOfficerAssemblyService::assemble($profile, $candidate, $qualifier, $scope, $seat, $lifecycle);
    }

    public function appointCastellan(array $candidate, array $decision): array
    {
        throw new \RuntimeException('CY001_LEGACY_CASTELLAN_FRESH_USE_REFUSED');
    }

    public function appointCourtthane(array $candidate, array $decision): array
    {
        return $this->appoint($candidate, $decision, 'courtthane', 'courtyard.courtthane', 'APPOINT_COURTTHANE');
    }

    public function appointLocksmith(array $candidate, array $decision): array
    {
        return $this->appoint($candidate, $decision, 'locksmith', 'clavium.locksmith', 'APPOINT_FORMATION_LOCKSMITH');
    }

    private function appoint(array $candidate, array $decision, string $role, string $seat, string $effect): array
    {
        return $this->journal->change(function (array &$state) use ($candidate, $decision, $role, $seat, $effect): array {
            if (isset($state[$role]) && FormationJournal::digest($state[$role]['decision']) === FormationJournal::digest($decision)) {
                $this->signatures->verify($state, $decision, $effect, $state[$role]['terms']);
                return $state[$role];
            }
            $generation = ($state[$role]['generation'] ?? 0) + 1;
            $terms = ['candidate' => $candidate, 'scope' => $state['citadel_id'], 'seat' => $seat, 'generation' => $generation];
            $this->signatures->verify($state, $decision, $effect, $terms);
            $binding = $this->candidate($state, $candidate, $state['citadel_id'], $seat);
            if (isset($state['occupied_manifestations'][$binding['manifestation_id']])) {
                throw new \RuntimeException('CMF044_MANIFESTATION_ALREADY_OCCUPIED');
            }
            $binding += ['generation' => $generation, 'decision' => $decision, 'terms' => $terms];
            $state['occupied_manifestations'][$binding['manifestation_id']] = $seat;
            $state[$role] = $binding;
            return $binding;
        });
    }

    public function currentCastellan(array $state): array
    {
        throw new \RuntimeException('CY001_LEGACY_CASTELLAN_FRESH_USE_REFUSED');
    }

    public function currentCourtthane(array $state): array
    {
        $binding = $state['courtthane'] ?? throw new \RuntimeException(isset($state['castellan'])
            ? 'CY001_LEGACY_CASTELLAN_FRESH_USE_REFUSED' : 'CY002_QUALIFIED_APPOINTED_COURTTHANE_REQUIRED');
        $terms = ['candidate' => $binding['candidate'], 'scope' => $state['citadel_id'],
            'seat' => 'courtyard.courtthane', 'generation' => $binding['generation']];
        if (!is_int($binding['generation']) || $binding['generation'] < 1 || $binding['terms'] !== $terms) {
            throw new \RuntimeException('CY003_EXACT_COURTTHANE_BINDING_REQUIRED');
        }
        $this->signatures->verify($state, $binding['decision'], 'APPOINT_COURTTHANE', $terms);
        $expected = $this->candidate($state, $binding['candidate'], $state['citadel_id'], 'courtyard.courtthane')
            + ['generation' => $binding['generation'], 'decision' => $binding['decision'], 'terms' => $terms];
        if ($binding !== $expected || ($state['occupied_manifestations'][$binding['manifestation_id']] ?? null) !== 'courtyard.courtthane') {
            throw new \RuntimeException('CY003_EXACT_COURTTHANE_BINDING_REQUIRED');
        }
        return $binding;
    }

    public function currentLocksmith(array $state): array
    {
        $binding = $state['locksmith'] ?? throw new \RuntimeException('CMF046_QUALIFIED_APPOINTED_LOCKSMITH_REQUIRED');
        $this->signatures->verify($state, $binding['decision'], 'APPOINT_FORMATION_LOCKSMITH', $binding['terms']);
        $this->candidate($state, $binding['candidate'], $state['citadel_id'], 'clavium.locksmith');
        return $binding;
    }
}
