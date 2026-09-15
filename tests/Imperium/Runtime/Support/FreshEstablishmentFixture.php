<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationInstitution, FormationSignatures, FormationPersonnel, FormationFreshEstablishment as Protocol};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore, Rules as R};
use App\Imperium\Runtime\Onboarding\Augur\AugurMigration;
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;

/** One additive real founding/establishment root; only external ports/content are synthetic. */
final class FreshEstablishmentFixture
{
    public AugurFreshFixture $fresh;
    public string $root;
    public FormationJournal $journal;
    public AuthorityStore $store;
    public FormationSignatures $signatures;
    public FormationPersonnel $personnel;
    public FreshEstablishmentClock $clock;
    public Protocol $protocol;
    public array $package;
    public array $holder;
    public array $initial;
    public array $terms;
    public array $operator;
    public array $formation;
    public ?array $reservation = null;
    public ?array $completion = null;
    private string $secret;
    private array $delegates = [];

    public function __construct(bool $complete = true, int $constitutionLifetime = 1800, bool $initialize = true, int $formationTrustLifetime = 10000)
    {
        $this->fresh = new AugurFreshFixture(constitutionLifetime: $constitutionLifetime, nativeConstitution: true);
        $d = $this->fresh->d; $a = $d->f;
        (new AugurMigration($a->store))->migrate($a->head());
        $this->fresh->ready(); $d->advance('select-base'); $d->advance('map-base');
        (new CommandLedger($a->store, $d->adapter, $d->adapter, $this->fresh->founding()))->advance($a::json($d->request('found-augur')));
        $this->root = $a->root; $this->journal = $a->store->journal;
        $this->holder = array_values($this->journal->read()['state']['onboarding']['bindings'])[0]['record'];
        $this->clock = new FreshEstablishmentClock(); $this->clock->at = $a->now;
        $this->store = new AuthorityStore($this->journal, $this->clock, $a->store->instance, $a->store->citadel, $a->store->operator, $a->store->sourceCommit);
        $this->signatures = new FormationSignatures($this->journal, $this->clock);
        $pair = sodium_crypto_sign_keypair(); $this->secret = sodium_crypto_sign_secretkey($pair); $public = sodium_crypto_sign_publickey($pair);
        $this->signatures->enrollPublicTrust(['public_key' => base64_encode($public), 'not_before' => $a->now - 1, 'expires_at' => $a->now + $formationTrustLifetime], hash('sha256', $public));
        $this->personnel = new FormationPersonnel($this->journal, $this->signatures, $this->clock, new FormationInstitution($this->root));
        $this->protocol = $this->service();
        $this->initial = ['schema' => Protocol::STATE, 'root_identity' => (new \App\Imperium\Runtime\Bootstrap\OperatorRootOwnership($this->root))->identity(),
            'instance_id' => $this->store->instance, 'citadel_id' => $this->store->citadel, 'holder_ref' => R::reference($this->holder), 'expected_head' => $this->head()];
        if (!$initialize) { return; }
        $this->protocol->initialize($this->initial, $this->sign('INITIALIZE_FRESH_FORMATION_INSTITUTIONS', $this->initial));
        $members = [];
        foreach (FormationInstitution::SEATS as $seat) {
            $office = explode('.', $seat)[0]; $role = substr($seat, strlen($office) + 1);
            $members[] = ['personnel_type' => 'OFFICER', 'office' => $office, 'role' => $role, 'seat' => $seat,
                'persona' => ['id' => 'public-fixture-persona-'.$seat, 'version' => '1'],
                'profile' => ['id' => 'public-fixture-profile-'.$seat, 'version' => '1'],
                'officer' => ['id' => 'public-fixture-officer-'.$seat, 'version' => '1']];
        }
        $this->package = ['schema' => 'imperium.operator-root-personnel-package/v3', 'instance_id' => $this->store->instance, 'personnel' => $members];
        $this->terms = $this->protocol->prepare($this->package, $a->now, $a->now + 600, bin2hex(random_bytes(24)), 'ppc7-offline', 'Separate exact post-FRESH institutional establishment');
        $this->resign();
        if ($complete) { $this->reserve(); $this->completion = $this->protocol->complete(Protocol::reference($this->reservation)); }
    }
    public function service(?\Closure $checkpoint = null): Protocol { return new Protocol($this->root, $this->store, $this->fresh->base, $checkpoint); }
    public function resign(): void {
        $payload = Protocol::operatorPayload($this->terms);
        $this->operator = ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $this->fresh->d->f->secret))];
        $this->formation = $this->sign(Protocol::EFFECT, $this->terms);
    }
    public function reserve(): array { return $this->reservation = $this->protocol->reserve($this->terms, $this->operator, $this->formation); }
    public function head(): array { $f = $this->journal->read(); return ['generation' => $f['generation'], 'digest' => $f['record_digest']]; }
    public function setTime(int $at): void { $this->clock->at = $at; $this->fresh->d->f->now = $at; }
    /** Public signed custody only; generated signing secrets are never serialized. */
    public function publicEvidence(string $name, array $record): void
    {
        $evidence = getenv('PPC7_PUBLIC_EVIDENCE');
        if (!is_string($evidence) || $evidence === '') { return; }
        $directory = $evidence.'/protocol-observations'; if (!is_dir($directory)) { mkdir($directory, 0700, true); }
        file_put_contents($directory.'/'.hash('sha256', $this->root.'|'.$name).'.json', json_encode([
            'name' => $name, 'root' => $this->root, 'observed_utc' => gmdate('c'), ...$record], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    public function sign(string $effect, mixed $object): array
    {
        $state = $this->journal->read()['state'];
        $payload = ['schema' => 'imperium.citadel-owner-decision/v1', 'citadel_id' => $state['citadel_id'],
            'trust_fingerprint' => $state['trust']['fingerprint'], 'effect' => $effect,
            'object_digest' => FormationJournal::digest($object), 'issued_at' => $this->clock->at,
            'expires_at' => min($this->clock->at + 3600, $state['trust']['expires_at']), 'nonce' => bin2hex(random_bytes(24))];
        return ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $this->secret))];
    }

    public function signPrepared(array $packet): array
    {
        $bytes = base64_decode($packet['signing_bytes_base64'], true);
        if ($bytes !== CanonicalJson::encode($packet['payload']) || hash('sha256', $bytes) !== $packet['signing_bytes_sha256']
            || FormationJournal::digest($packet['object']) !== $packet['payload']['object_digest']) {
            throw new \RuntimeException('Synthetic signing packet mismatch');
        }
        return ['payload' => $packet['payload'], 'signature' => base64_encode(sodium_crypto_sign_detached($bytes, $this->secret))];
    }

    public function candidate(string $scope, string $seat, string $suffix = 'one'): array
    {
        $identity = ['persona_id' => 'persona-'.$seat.'-'.$suffix, 'persona_version' => '1.0',
            'persona_digest' => 'sha256:'.hash('sha256', $seat.$suffix), 'admission_state' => 'admitted', 'evidence_record' => 'synthetic-admission-'.$suffix];
        $persona = $this->evidence('garrison', $scope, 'ADMITTED_PERSONA', $identity['persona_id'], [], ['identity' => $identity]);
        $suitable = $this->evidence('guildhall', $scope, 'SUITABLE_CANDIDATE', $identity['persona_id'], [$persona], ['seat' => $seat, 'rationale' => 'Synthetic Guildhall suitability determination.']);
        $artifact = ['contract_version' => '1.0.0', 'profile_id' => 'profile-'.$seat.'-'.$suffix, 'profile_version' => '1.0',
            'artifact_class' => 'officer', 'source_persona' => $identity, 'steward' => ['kind' => 'office', 'id' => 'laboratorium'],
            'target' => ['kind' => 'seat', 'id' => $seat],
            'transformation' => ['case_id' => 'synthetic-case-'.$suffix, 'specification_version' => '1', 'alchemist_disposition_id' => 'synthetic-derived-'.$suffix],
            'cognitive_payload' => ['instructions' => 'Synthetic '.$seat.' Profile: preserve intent and dissent; respect exact grants; never execute.'],
            'qualification_contract' => ['contract_id' => 'citadel-formation-officer/v1', 'criteria' => ['exact_identity', 'bounded_authority', 'return_contract']],
            'lineage' => ['derived_from' => $identity['persona_digest']],
            'digest_spec' => ['algorithm' => 'sha256', 'canonicalization' => 'rfc8785', 'omitted_fields' => ['content_digest']]];
        $artifact['content_digest'] = 'sha256:'.FormationJournal::digest($artifact);
        $profile = $this->evidence('laboratorium', $scope, 'DERIVED_PROFILE', $artifact['profile_id'], [$persona, $suitable], ['seat' => $seat, 'artifact' => $artifact]);
        $findings = [];
        foreach (['consistency', 'governance', 'practice', 'security'] as $criterion) {
            $findings[$criterion] = $this->evidence('senate-'.$criterion, $scope, 'SENATOR_FINDING', $artifact['profile_id'], [$profile],
                ['disposition' => 'PASS', 'rationale' => 'Synthetic '.$criterion.' examination of the exact Profile.']);
        }
        $examination = $this->evidence('senate', $scope, 'EXAMINED_PROFILE', $artifact['profile_id'], [$profile, ...array_values($findings)],
            ['disposition' => 'APPROVED', 'security_block' => false, 'findings' => $findings]);
        $approval = $this->sign('APPROVE_FORMATION_PROFILE', ['profile' => $profile, 'examination' => $examination, 'scope' => $scope, 'seat' => $seat]);
        $qualification = $this->evidence('conscription', $scope, 'QUALIFIED_MANIFESTATION', $artifact['profile_id'], [$persona, $suitable, $profile, $examination],
            ['seat' => $seat, 'disposition' => 'QUALIFIED', 'profile_approval_digest' => FormationJournal::digest($approval),
                'criteria_results' => array_fill_keys($artifact['qualification_contract']['criteria'], true)]);
        return ['persona' => $persona, 'suitability' => $suitable, 'profile' => $profile, 'examination' => $examination, 'qualification' => $qualification, 'profile_approval' => $approval];
    }


    private function evidence(string $role, string $scope, string $kind, string $subject, array $sources, array $content): string
    {
        $key = $role.'|'.$scope;
        if (!isset($this->delegates[$key])) {
            $pair = sodium_crypto_sign_keypair();
            $terms = ['role' => $role, 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'scope' => $scope,
                'expires_at' => $this->clock->at + 3600, 'actor' => $this->personnel->authoritySource($role)];
            $this->delegates[$key] = [$this->personnel->delegate($terms, $this->sign('DELEGATE_PERSONNEL_EVIDENCE', $terms)), sodium_crypto_sign_secretkey($pair)];
        }
        [$delegation, $secret] = $this->delegates[$key];
        $payload = ['delegation' => $delegation, 'scope' => $scope, 'kind' => $kind, 'subject' => $subject, 'sources' => $sources, 'content' => $content, 'expires_at' => $this->clock->at + 3600];
        return $this->personnel->record(['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $secret))]);
    }
    public function close(): void { sodium_memzero($this->secret); $this->fresh->close(); }
}
