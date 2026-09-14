<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationModelPreparation as Contract, FormationInstitution};

/** Actual bounded native acts with ephemeral fixture keys; external model facts remain unverified. */
final class ModelPreparationFixture
{
    public CitadelFormationFixture $f;
    public array $candidate;
    public array $oldCandidate;
    public array $artifact;
    public array $envelope;
    private array $delegates = [];
    public Contract $preparation;
    public array $context;
    public array $binding;
    public array $bindingTerms;
    public array $delegation;
    public array $authorization;
    public array $authorizationTerms;
    public array $sealingEnvelope;
    public array $seal;
    public array $correspondence;
    private string $sealerSecret;

    public function __construct(public string $seat = 'courtyard.courtthane', bool $finish = true)
    {
        $this->f = new CitadelFormationFixture();
        $scope = $this->f->journal->read()['state']['citadel_id'];
        $old = $this->oldCandidate = $this->f->candidate($scope, $seat);
        $state = $this->f->journal->read()['state'];
        $artifact = $state['personnel_evidence'][$old['profile']]['payload']['content']['artifact'];
        $f = $this->f;
        $this->preparation = new Contract($f->journal, $f->signatures, $f->clock, new FormationInstitution($f->root));
        $this->context = ['instance_id' => $state['parent_instance_id'], 'citadel_id' => $scope, 'seat' => $seat,
            'steward' => ['kind' => 'office', 'id' => 'laboratorium']];
        $initial = ['schema' => Contract::STATE, 'instance_id' => $state['parent_instance_id'], 'citadel_id' => $scope, 'expected_head' => $this->head()];
        $this->preparation->initialize($initial, $f->sign('INITIALIZE_FORMATION_MODEL_PREPARATION', $initial));
        $store = new \App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore($f->journal, $f->clock, $state['parent_instance_id'], $scope, 'fixture-operator', str_repeat('a', 40));
        $config = ['max_tokens' => 4096, 'stream' => false, 'temperature' => 0, 'thinking' => ['type' => 'disabled'],
            'response_format' => ['type' => 'json_object'], 'message_roles' => ['system', 'user']];
        $model = $seat === 'courtyard.courtthane' ? 'deepseek-v4-flash' : 'deepseek-v4-pro';
        $source = static function (string $kind, string $content) use ($store): array {
            return $store->make('imperium.bootstrap-source/v1', 'source-'.bin2hex(random_bytes(8)),
                ['kind' => $kind, 'content' => $content, 'content_digest' => 'sha256:'.hash('sha256', $content),
                    'limitations' => 'SYNTHETIC_UNVERIFIED_EXTERNAL_MODEL_FACTS; no account/access/cognition evidence']);
        };
        $spec = ['provider' => 'deepseek', 'model_id' => $model, 'model_version' => 'synthetic-v1',
            'provider_model_version' => 'deepseek/'.$model.'/synthetic-v1', 'configuration' => $config,
            'constraints' => ['Disposable offline proof only'], 'fallbacks' => [], 'access_assertion_required' => true];
        $this->bindingTerms = ['id' => 'native-binding-'.bin2hex(random_bytes(8)), 'context' => $this->context,
            'lineage_id' => Contract::lineageId($this->context, $spec), 'predecessor' => null, 'specification' => $spec,
            'binding_original' => $source('candidate-binding', $model), 'configuration_original' => $source('request-configuration', CanonicalJson::encode($config)),
            'supporting_originals' => [], 'expected_head' => $this->head()];
        $this->binding = $this->preparation->prepareBinding($this->bindingTerms);
        $pair = sodium_crypto_sign_keypair(); $this->sealerSecret = sodium_crypto_sign_secretkey($pair);
        $delegation = ['id' => 'sealing-delegation-'.bin2hex(random_bytes(8)), 'context' => $this->context,
            'binding' => Contract::reference($this->binding), 'actor' => $f->personnel->authoritySource('conscription'),
            'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'not_before' => $f->clock->at,
            'expires_at' => $f->clock->at + 1800, 'expected_head' => $this->head()];
        $this->delegation = $this->preparation->delegate($delegation, $f->sign('DELEGATE_FORMATION_MODEL_SEALING', $delegation));
        $terms = $this->authorizationTerms = ['id' => 'model-authorization-'.bin2hex(random_bytes(8)),
            'purpose' => 'ONE_EXACT_CONSCRIPTION_MODEL_SEAL', 'context' => $this->context, 'binding' => Contract::reference($this->binding),
            'source_line' => Contract::sourceLine($this->binding), 'source_profile' => $artifact, 'source_evidence' => $this->preparation->sourceOriginal($old['profile']),
            'delegation' => Contract::reference($this->delegation), 'not_before' => $f->clock->at, 'expires_at' => $f->clock->at + 1200,
            'expected_head' => $this->head(), 'nonce' => bin2hex(random_bytes(24)), 'correlation' => 'ppc5-offline', 'reason' => 'Separate bounded owner preparation act'];
        $this->authorization = $this->preparation->authorize($terms, $this->ownerSign($terms));
        $payload = $this->preparation->sealingPayload(Contract::reference($this->authorization), $this->head(), bin2hex(random_bytes(24)));
        $this->sealingEnvelope = $this->sealerSign($payload);
        if (!$finish) { return; }
        $this->finish();
    }

    public function finish(): void
    {
        $this->seal = $this->preparation->seal($this->sealingEnvelope);
        $artifact = $this->seal['body']['profile'];
        $old = $this->oldCandidate; $seat = $this->seat;
        $scope = $this->context['citadel_id'];
        $this->correspondence = ['seal' => Contract::reference($this->seal), 'binding_ref' => $this->binding['body']['binding_ref'],
            'configuration_ref' => $this->binding['body']['configuration_ref'], 'binding_generation' => $this->binding['body']['binding_generation']];
        $this->artifact = $artifact;
        $profile = $this->evidence('laboratorium', 'DERIVED_PROFILE', [$old['persona'], $old['suitability']], ['seat' => $seat, 'artifact' => $artifact, 'correspondence' => $this->correspondence], true);
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
        $payload = [...($versioned ? ['schema' => Contract::EVIDENCE] : []), 'delegation' => $ref, 'scope' => $scope,
            'kind' => $kind, 'subject' => $this->artifact['profile_id'], 'sources' => $sources, 'content' => $content, 'expires_at' => $f->clock->at + 3600];
        $envelope = ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $secret))];
        return $versioned ? $f->personnel->recordAuthorizedModelBoundProfile($envelope) : $f->personnel->record($envelope);
    }

    public function assembly(): array
    {
        return $this->f->journal->inspect(fn(array $frame, \App\Imperium\Runtime\Citadel\Formation\FormationOwnerFrame $owner): array => $this->f->personnel->authorizedModelCandidateInOwner($owner, $this->candidate, $frame['state']['citadel_id'], $this->seat));
    }

    public function head(): array
    {
        $frame = $this->f->journal->read(); return ['generation' => $frame['generation'], 'digest' => $frame['record_digest']];
    }

    public function ownerSign(array $terms, string $effect = 'AUTHORIZE_FORMATION_MODEL_PREPARATION'): array
    {
        $payload = $this->f->sign($effect, $terms)['payload'];
        $payload['nonce'] = $terms['nonce'];
        $bytes = CanonicalJson::encode($payload);
        return $this->f->signPrepared(['payload' => $payload, 'object' => $terms, 'signing_bytes_base64' => base64_encode($bytes), 'signing_bytes_sha256' => hash('sha256', $bytes)]);
    }

    public function sealerSign(array $payload): array
    {
        return ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $this->sealerSecret))];
    }

    public function close(): void
    {
        foreach ($this->delegates as &$delegate) { sodium_memzero($delegate[1]); }
        unset($delegate);
        sodium_memzero($this->sealerSecret);
        $this->f->close();
    }
}
