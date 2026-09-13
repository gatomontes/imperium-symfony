<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationModelBoundProfileContract as Contract};

/** Real institutional producers; model specification is explicitly synthetic,
 * not an authenticated O4 binding identity or a current designation. */
final class ModelBoundFormationFixture
{
    public CitadelFormationFixture $f;
    public array $candidate;
    public array $oldCandidate;
    public array $artifact;
    public array $envelope;
    private array $delegates = [];

    public function __construct(public string $seat = 'courtyard.courtthane')
    {
        $this->f = new CitadelFormationFixture();
        $scope = $this->f->journal->read()['state']['citadel_id'];
        $old = $this->oldCandidate = $this->f->candidate($scope, $seat);
        $state = $this->f->journal->read()['state'];
        $artifact = $state['personnel_evidence'][$old['profile']]['payload']['content']['artifact'];
        $artifact['lineage']['supersedes'] = array_intersect_key($artifact, array_flip(['profile_id', 'profile_version', 'content_digest']));
        $artifact['profile_version'] = '1.1';
        $artifact['model_binding'] = ['provider_model_version' => 'synthetic/model/v1', 'authorization_id' => 'synthetic-unverified-model-authorization',
            'source_line' => ['line_number' => 1, 'line_digest' => hash('sha256', 'synthetic dossier line')],
            'configuration' => ['temperature' => 0], 'constraints' => ['Offline only'], 'fallbacks' => [], 'access_assertion_required' => true];
        unset($artifact['content_digest']);
        $artifact['content_digest'] = 'sha256:'.J::digest($artifact);
        $this->artifact = $artifact;
        $profile = $this->evidence('laboratorium', 'DERIVED_PROFILE', [$old['persona'], $old['suitability']], ['seat' => $seat, 'artifact' => $artifact], true);
        $this->envelope = $this->f->journal->read()['state']['personnel_evidence'][$profile];
        $findings = [];
        foreach (['consistency', 'governance', 'practice', 'security'] as $criterion) {
            $findings[$criterion] = $this->evidence('senate-'.$criterion, 'SENATOR_FINDING', [$profile],
                ['disposition' => 'PASS', 'rationale' => 'Synthetic exact version '.$criterion.' finding']);
        }
        $examination = $this->evidence('senate', 'EXAMINED_PROFILE', [$profile, ...array_values($findings)],
            ['disposition' => 'APPROVED', 'security_block' => false, 'findings' => $findings]);
        $approval = $this->f->sign('APPROVE_FORMATION_PROFILE', ['profile' => $profile, 'examination' => $examination, 'scope' => $scope, 'seat' => $seat]);
        $qualification = $this->evidence('conscription', 'QUALIFIED_MANIFESTATION', [$old['persona'], $old['suitability'], $profile, $examination],
            ['seat' => $seat, 'disposition' => 'QUALIFIED', 'profile_approval_digest' => J::digest($approval),
                'criteria_results' => array_fill_keys($artifact['qualification_contract']['criteria'], true)]);
        $this->candidate = ['persona' => $old['persona'], 'suitability' => $old['suitability'], 'profile' => $profile,
            'examination' => $examination, 'qualification' => $qualification, 'profile_approval' => $approval];
    }

    private function evidence(string $role, string $kind, array $sources, array $content, bool $versioned = false): string
    {
        $f = $this->f;
        $scope = $f->journal->read()['state']['citadel_id'];
        if (!isset($this->delegates[$role])) {
            $pair = sodium_crypto_sign_keypair();
            $terms = ['role' => $role, 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'scope' => $scope,
                'expires_at' => $f->clock->at + 3600, 'actor' => $f->personnel->authoritySource($role)];
            $ref = $f->personnel->delegate($terms, $f->sign('DELEGATE_PERSONNEL_EVIDENCE', $terms));
            $this->delegates[$role] = [$ref, sodium_crypto_sign_secretkey($pair)];
        }
        [$ref, $secret] = $this->delegates[$role];
        $payload = [...($versioned ? ['schema' => Contract::SCHEMA] : []), 'delegation' => $ref, 'scope' => $scope,
            'kind' => $kind, 'subject' => $this->artifact['profile_id'], 'sources' => $sources, 'content' => $content, 'expires_at' => $f->clock->at + 3600];
        $envelope = ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $secret))];
        return $f->run($versioned ? 'record-model-bound-profile-evidence' : 'record-personnel-evidence', ['envelope' => $envelope]);
    }

    public function assembly(): array
    {
        return $this->f->journal->inspect(fn(array $frame): array => $this->f->personnel->candidate($frame['state'], $this->candidate, $frame['state']['citadel_id'], $this->seat));
    }

    public function close(): void
    {
        foreach ($this->delegates as &$delegate) { sodium_memzero($delegate[1]); }
        unset($delegate);
        $this->f->close();
    }
}
