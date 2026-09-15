<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Bootstrap\{OperatorRootOwnership, OperatorRootPersonnelInstallationService};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore, Rules as R};
use App\Imperium\Runtime\Onboarding\Augur\{BaseProjection, FreshProducer, NativeConstitutionEvidence};
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;

/** One separately authorized exact native package after genuine FRESH founding. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FormationFreshEstablishment
{
    public const STATE = 'imperium.fresh-institutional-establishment/v1';
    public const TERMS = 'imperium.fresh-institutional-establishment-terms/v1';
    public const RESERVATION = 'imperium.fresh-institutional-reservation/v1';
    public const COMPLETE = 'imperium.fresh-institutional-completion/v1';
    public const ACT = 'imperium.fresh-institutional-operator-authorization/v1';
    public const EFFECT = 'ESTABLISH_FRESH_FORMATION_INSTITUTIONS';
    public const DOMAIN = 'IMPERIUM_FRESH_INSTITUTIONS_V1';

    public function __construct(private string $root, private AuthorityStore $store,
        private BaseProjection $base, private ?\Closure $checkpoint = null) {}

    public function initialize(array $terms, array $decision): array
    {
        self::bounded([$terms, $decision]);
        return $this->store->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($terms, $decision): array {
            $owner->assertOwner(new FormationJournal($this->root));
            self::need(!array_key_exists('fresh_institutions', $state), 'ALREADY_INITIALIZED');
            self::shape($terms, ['schema', 'root_identity', 'instance_id', 'citadel_id', 'holder_ref', 'expected_head']);
            self::need($terms['schema'] === self::STATE && R::same($terms['expected_head'], $head), 'INITIALIZATION_HEAD');
            $this->identity($state, $terms); $this->quiet($state);
            $founding = $this->founding($state);
            self::need(R::same($terms['holder_ref'], R::reference($founding['holder'])), 'INITIALIZATION_HOLDER');
            $this->signatures()->verify($state, $decision, 'INITIALIZE_FRESH_FORMATION_INSTITUTIONS', $terms);
            FreshInstitutionPackage::scan($this->root, null, false);
            $state['parent_instance_id'] = $this->store->instance;
            $state['fresh_institutions'] = ['schema' => self::STATE, 'initialization' => ['terms' => $terms, 'decision' => $decision],
                'reservation' => null, 'completion' => null, 'preparations' => []];
            // Current signature acceptance must also satisfy retained-history
            // invariants before this one-use state can become durable.
            self::history($state);
            return $state['fresh_institutions'];
        });
    }

    /** Read-only preparation supplies complete originals; it grants no authority. */
    public function prepare(array $package, int $notBefore, int $expiresAt, string $nonce, string $correlation, string $reason): array
    {
        self::bounded($package);
        return $this->store->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($package, $notBefore, $expiresAt, $nonce, $correlation, $reason): array {
            $owner->assertOwner(new FormationJournal($this->root));
            self::history($frame['state']); $this->quiet($frame['state']);
            $s = $this->store->state($frame['state']); $trust = $this->store->currentTrust($s);
            $terms = ['schema' => self::TERMS, 'purpose' => self::EFFECT, 'root_identity' => (new OperatorRootOwnership($this->root))->identity(),
                'instance_id' => $this->store->instance, 'citadel_id' => $this->store->citadel, 'operator_id' => $this->store->operator,
                'operator_trust_fingerprint' => $trust['fingerprint'], 'formation_trust_fingerprint' => $frame['state']['trust']['fingerprint'],
                'founding' => $this->founding($frame['state']), 'package' => $package,
                'expected_head' => ['generation' => $frame['generation'], 'digest' => $frame['record_digest']],
                'not_before' => $notBefore, 'expires_at' => $expiresAt, 'nonce' => $nonce, 'correlation' => $correlation, 'reason' => $reason];
            self::terms($terms, $this->root); return $terms;
        });
    }

    /** Public input preparation; only the subsequent authentic native act can adapt state. */
    public function prepareRevocation(array $target, int $issuedAt, int $expiresAt, string $nonce, string $correlation, string $reason): array
    {
        self::bounded([$target, $issuedAt, $expiresAt, $nonce, $correlation, $reason]);
        return $this->store->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($target, $issuedAt, $expiresAt, $nonce, $correlation, $reason): array {
            $head = ['generation' => $frame['generation'], 'digest' => $frame['record_digest']];
            $this->revocationTarget($owner, $frame['state'], $head, $target);
            $t = $target['terms'];
            return ['schema' => FreshEstablishmentRevocations::ACT, 'domain' => FreshEstablishmentRevocations::DOMAIN,
                'effect' => FreshEstablishmentRevocations::EFFECT, 'target' => $target,
                'target_ref' => FreshEstablishmentRevocations::target($target, $frame['state']['onboarding']['trust']['body']),
                'root_identity' => $t['root_identity'], 'instance_id' => $t['instance_id'], 'citadel_id' => $t['citadel_id'],
                'operator_id' => $t['operator_id'], 'trust_fingerprint' => $t['operator_trust_fingerprint'], 'expected_head' => $head,
                'adapter_from' => $frame['state']['fresh_institutions']['schema'], 'issued_at' => $issuedAt, 'expires_at' => $expiresAt,
                'nonce' => $nonce, 'correlation' => $correlation, 'reason' => $reason];
        });
    }

    public function revokeAuthorization(array $envelope): array
    {
        self::bounded($envelope);
        return $this->store->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($envelope): array {
            $owner->assertOwner(new FormationJournal($this->root)); $extension = self::history($state);
            $history = FreshEstablishmentRevocations::history($state);
            $payload = FreshEstablishmentRevocations::envelope($envelope, $state['onboarding']['trust']['body']);
            if (isset($history[$payload['nonce']])) {
                self::need(R::same($envelope, $history[$payload['nonce']]['envelope']), 'REVOCATION_NONCE_CONFLICT');
                return $history[$payload['nonce']];
            }
            self::need(count($history) < FreshEstablishmentRevocations::LIMIT, 'REVOCATION_HISTORY_FULL');
            self::need(R::same($head, $payload['expected_head']) && $payload['adapter_from'] === $extension['schema'], 'REVOCATION_HEAD');
            $this->revocationTarget($owner, $state, $head, $payload['target']);
            $now = $this->store->now();
            self::need($payload['issued_at'] <= $now && $now < $payload['expires_at'], 'REVOCATION_CURRENT');
            $receipt = self::seal(['schema' => FreshEstablishmentRevocations::RECEIPT,
                'id' => 'fresh-revocation-'.FormationJournal::digest($envelope), 'sequence' => count($history) + 1,
                'envelope' => $envelope, 'recorded_at' => $now]);
            $state['fresh_institutions']['schema'] = FreshEstablishmentRevocations::STATE;
            $state['fresh_institutions']['revocations'] = [...$history, $payload['nonce'] => $receipt];
            self::history($state);
            ($this->checkpoint)?->__invoke('revocation-ready');
            return $receipt;
        });
    }

    private function revocationTarget(FormationOwnerFrame $owner, array $state, array $head, array $target): void
    {
        $owner->assertOwner($this->store->journal); $owner->assertOwner(new FormationJournal($this->root));
        $extension = self::history($state); $trust = $this->store->currentTrust($this->store->state($state));
        FreshEstablishmentRevocations::target($target, $trust, $this->root);
        $this->identity($state, $target['terms']);
        self::need($target['terms']['operator_id'] === $this->store->operator, 'REVOCATION_OPERATOR');
        self::need($target['terms']['formation_trust_fingerprint'] === ($state['trust']['fingerprint'] ?? null), 'REVOCATION_FORMATION_IDENTITY');
        if ($extension['reservation'] === null) {
            self::need(R::same($target['terms']['expected_head'], $head), 'REVOCATION_TARGET_HEAD');
        } else {
            self::need(R::same($target, ['terms' => $extension['reservation']['terms'], 'operator' => $extension['reservation']['operator']]), 'REVOCATION_TARGET_ORIGINAL');
        }
        if ($extension['completion'] === null) {
            $this->quiet($state);
            self::need(R::same($target['terms']['founding'], $this->founding($state)), 'REVOCATION_FOUNDING_ORIGINALS');
        }
    }

    /** Historical exact native target, with no dependency on O2 admitted-act nonces. */
    public static function nativeAuthorizationReference(array $terms, array $operator, array $trust, ?string $root = null): array
    {
        self::terms($terms, $root); self::operatorSignature(['terms' => $terms, 'operator' => $operator], $trust);
        self::need($trust['not_before'] <= $terms['not_before'] && $terms['expires_at'] <= $trust['expires_at'], 'OPERATOR_HISTORY_INTERVAL');
        $digest = FormationJournal::digest(['terms' => $terms, 'operator' => $operator]);
        return ['schema' => self::ACT, 'id' => 'native-establishment-act-'.$digest, 'digest' => $digest];
    }

    public static function operatorPayload(array $terms): array
    {
        return ['schema' => self::ACT, 'domain' => self::DOMAIN, 'effect' => self::EFFECT,
            'terms_digest' => FormationJournal::digest($terms), 'trust_fingerprint' => $terms['operator_trust_fingerprint'],
            'issuer' => ['kind' => 'operator', 'id' => $terms['operator_id']], 'nonce' => $terms['nonce']];
    }

    public function reserve(array $terms, array $operator, array $formation): array
    {
        self::bounded([$terms, $operator, $formation]); self::terms($terms, $this->root);
        return $this->store->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($terms, $operator, $formation): array {
            $owner->assertOwner(new FormationJournal($this->root)); $extension = self::history($state);
            $record = self::seal(['schema' => self::RESERVATION, 'id' => 'fresh-establishment-'.FormationJournal::digest([$terms, $operator, $formation]),
                'terms' => $terms, 'operator' => $operator, 'formation' => $formation]);
            if ($extension['reservation'] !== null) {
                self::need(R::same($record, $extension['reservation']), 'RESERVATION_CONFLICT');
                if ($extension['completion'] === null) {
                    FreshEstablishmentRevocations::assertNotRevoked($state, ['terms' => $terms, 'operator' => $operator]);
                }
                return $extension['completion'] ?? $record;
            }
            FreshEstablishmentRevocations::assertNotRevoked($state, ['terms' => $terms, 'operator' => $operator]);
            self::need(R::same($head, $terms['expected_head']), 'RESERVATION_HEAD');
            $this->authorize($owner, $state, $record); FreshInstitutionPackage::layout($this->root, $record);
            FreshInstitutionPackage::scan($this->root, null, false);
            $state['fresh_institutions']['reservation'] = $record;
            self::history($state);
            ($this->checkpoint)?->__invoke('reservation-ready');
            return $record;
        });
    }

    public function complete(array $reference): array
    {
        self::bounded($reference);
        return $this->store->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($reference): array {
            $owner->assertOwner(new FormationJournal($this->root)); $extension = self::history($state);
            self::need($extension['reservation'] !== null && R::same($reference, self::reference($extension['reservation'])), 'RESERVATION_REFERENCE');
            if ($extension['completion'] !== null) { return $extension['completion']; }
            $native = (new OperatorRootOwnership($this->root))->establishInOwner($owner, $this, $this->checkpoint);
            // Native publication can outlive a time-bound grant or founding source.
            // A failed completion decision must retain valid PENDING custody.
            $this->authorize($owner, $state, $extension['reservation']);
            $completedAt = $this->store->now();
            self::need($extension['reservation']['terms']['not_before'] <= $completedAt
                && $completedAt < $extension['reservation']['terms']['expires_at'], 'COMPLETION_INTERVAL');
            $completion = self::seal(['schema' => self::COMPLETE, 'id' => 'fresh-completion-'.$extension['reservation']['id'],
                'reservation_ref' => $reference, 'native_package' => $native, 'completed_at' => $completedAt, 'expected_head' => $head]);
            $state['fresh_institutions']['completion'] = $completion;
            self::history($state);
            ($this->checkpoint)?->__invoke('completion-ready');
            return $completion;
        });
    }

    /** Fixed internal writer check. A retained array cannot stand in for this live owner. */
    public function authorizedPendingInOwner(FormationOwnerFrame $owner, string $root): array
    {
        $owner->assertOwner($this->store->journal); $owner->assertOwner(new FormationJournal($this->root));
        $owner->assertOwner(new FormationJournal($root)); $state = $owner->frame()['state']; $extension = self::history($state);
        self::need($extension['reservation'] !== null && $extension['completion'] === null, 'PENDING_REQUIRED');
        $this->authorize($owner, $state, $extension['reservation']); return $extension['reservation'];
    }

    /** Consumed establishment authorization is history, not a continuing tenure lease. */
    public static function completedInOwner(string $root, FormationOwnerFrame $owner): array
    {
        $owner->assertOwner(new FormationJournal($root)); $state = $owner->frame()['state']; $extension = self::history($state);
        self::need($extension['reservation'] !== null && $extension['completion'] !== null, 'COMPLETE_REQUIRED');
        $reservation = $extension['reservation'];
        self::need($reservation['terms']['root_identity'] === (new OperatorRootOwnership($root))->identity(), 'ROOT_IDENTITY');
        $layout = FreshInstitutionPackage::verify($root, $reservation);
        self::need(R::same($extension['completion']['native_package'], $layout['completion']), 'COMPLETE_NATIVE_JOIN');
        return $layout;
    }

    private function authorize(FormationOwnerFrame $owner, array $state, array $record): void
    {
        $owner->assertOwner($this->store->journal); $terms = $record['terms'];
        $this->identity($state, $terms); $this->quiet($state);
        self::need($terms['operator_id'] === $this->store->operator, 'OPERATOR_IDENTITY');
        $s = $this->store->state($state); $trust = $this->store->currentTrust($s);
        self::need($terms['operator_trust_fingerprint'] === $trust['fingerprint']
            && $terms['formation_trust_fingerprint'] === ($state['trust']['fingerprint'] ?? null), 'TRUST_IDENTITY');
        self::operatorSignature($record, $trust);
        $now = $this->store->now();
        self::need($trust['not_before'] <= $terms['not_before'] && $terms['not_before'] <= $now && $now < $terms['expires_at']
            && $terms['expires_at'] <= $trust['expires_at'], 'OPERATOR_CURRENT');
        FreshEstablishmentRevocations::assertNotRevoked($state, ['terms' => $terms, 'operator' => $record['operator']]);
        $decision = $this->signatures()->verify($state, $record['formation'], self::EFFECT, $terms);
        self::need($terms['expires_at'] <= $decision['expires_at'], 'FORMATION_INTERVAL');
        self::need(R::same($terms['founding'], $this->founding($state)), 'FOUNDING_ORIGINALS');
    }

    private function founding(array $state): array
    {
        $s = $this->store->state($state); self::need(count($s['bindings']) === 1, 'FRESH_HOLDER_REQUIRED');
        $holder = array_values($s['bindings'])[0]['record'];
        $producer = new FreshProducer(new OperatorRootOwnership($this->root), $this->base, new NativeConstitutionEvidence());
        $holder = $producer->current($this->store, $s, R::reference($holder));
        $policy = $this->store->checkSource($s, $holder['body']['policy_ref']);
        $step = LedgerState::step($s, $policy, 'found-augur');
        return ['policy' => $policy, 'constitution' => $this->store->checkSource($s, $holder['body']['constitution_ref']),
            'command' => LedgerState::command($s, $holder['body']['command_ref']), 'completion' => $step['completion'], 'holder' => $holder];
    }
    private function identity(array $state, array $terms): void
    {
        self::need($terms['root_identity'] === (new OperatorRootOwnership($this->root))->identity()
            && $terms['instance_id'] === $this->store->instance && $terms['citadel_id'] === $this->store->citadel
            && ($state['citadel_id'] ?? null) === $this->store->citadel
            && ($state['parent_instance_id'] ?? $this->store->instance) === $this->store->instance, 'IDENTITY');
    }
    private function quiet(array $state): void
    {
        self::need(array_diff(array_keys($state), ['citadel_id', 'parent_instance_id', 'trust', 'onboarding', 'revoked_decisions', 'fresh_institutions']) === [], 'AFFECTED_STATE');
        $onboarding = $this->store->state($state);
        self::need($onboarding['source_fences'] === [], 'AFFECTED_CUSTODY');
        foreach ($onboarding['claims'] as $claim) {
            self::need($claim['settled'] !== null && count($claim['custody']) === 5, 'AFFECTED_CUSTODY');
        }
        foreach (['var/imperium/native-authority', 'var/imperium/bootstrap-state.json', 'var/imperium/operator-root/operationalization-seal.json'] as $path) {
            self::need(!file_exists($this->root.'/'.$path) && !is_link($this->root.'/'.$path), 'SUCCESSOR');
        }
    }
    private function signatures(): FormationSignatures { return new FormationSignatures($this->store->journal, $this->store->clock); }

    public static function history(array $state): array
    {
        $extension = $state['fresh_institutions'] ?? [];
        self::bounded($extension, 8388608);
        $keys = ['schema', 'initialization', 'reservation', 'completion', 'preparations'];
        if (($extension['schema'] ?? null) === FreshEstablishmentRevocations::STATE) { $keys[] = 'revocations'; }
        self::shape($extension, $keys);
        self::need(in_array($extension['schema'], [self::STATE, FreshEstablishmentRevocations::STATE], true), 'STATE_SCHEMA');
        self::need(is_array($extension['preparations']) && count($extension['preparations']) <= 2, 'PREPARATION_BOUND');
        foreach ($extension['preparations'] as $kind => $proof) {
            self::need(in_array($kind, ['model_preparation', 'profile_designations'], true) && $extension['completion'] !== null, 'PREPARATION_SCOPE');
            self::shape($proof, ['schema', 'kind', 'initialization', 'custody_digest', 'expected_head', 'record_digest']); self::intact($proof);
            self::need($proof['schema'] === FreshInstitutionalPreparation::SCHEMA && $proof['kind'] === $kind
                && R::same($proof['initialization'], $state[$kind]['initialization'] ?? null), 'PREPARATION_ORIGINAL');
            $effect = $kind === 'model_preparation' ? 'INITIALIZE_FORMATION_MODEL_PREPARATION' : 'INITIALIZE_FORMATION_PROFILE_DESIGNATIONS';
            self::formationSignature($state, $proof['initialization']['decision'], $effect, $proof['initialization']['terms']);
        }
        $initial = $extension['initialization']; self::shape($initial, ['terms', 'decision']);
        self::shape($initial['terms'], ['schema', 'root_identity', 'instance_id', 'citadel_id', 'holder_ref', 'expected_head']);
        self::need($initial['terms']['schema'] === self::STATE && $initial['terms']['citadel_id'] === ($state['citadel_id'] ?? null)
            && $initial['terms']['instance_id'] === ($state['parent_instance_id'] ?? null), 'INITIALIZATION_IDENTITY');
        self::formationSignature($state, $initial['decision'], 'INITIALIZE_FRESH_FORMATION_INSTITUTIONS', $initial['terms']);
        FreshEstablishmentRevocations::history($state);
        $record = $extension['reservation'];
        if ($record === null) { self::need($extension['completion'] === null, 'COMPLETION_WITHOUT_RESERVATION'); return $extension; }
        self::shape($record, ['schema', 'id', 'terms', 'operator', 'formation', 'record_digest']); self::intact($record);
        self::need($record['schema'] === self::RESERVATION && $record['id'] === 'fresh-establishment-'.FormationJournal::digest([$record['terms'], $record['operator'], $record['formation']]), 'RESERVATION_ID');
        self::terms($record['terms']);
        foreach (['root_identity', 'instance_id', 'citadel_id'] as $field) { self::need($record['terms'][$field] === $initial['terms'][$field], 'RESERVATION_IDENTITY'); }
        self::need(R::same(R::reference($record['terms']['founding']['holder']), $initial['terms']['holder_ref']), 'RESERVATION_HOLDER');
        self::operatorSignature($record, $state['onboarding']['trust']['body'] ?? []);
        self::formationSignature($state, $record['formation'], self::EFFECT, $record['terms']);
        $completion = $extension['completion'];
        if ($completion !== null) {
            self::shape($completion, ['schema', 'id', 'reservation_ref', 'native_package', 'completed_at', 'expected_head', 'record_digest']); self::intact($completion);
            self::need($completion['schema'] === self::COMPLETE && $completion['id'] === 'fresh-completion-'.$record['id']
                && R::same($completion['reservation_ref'], self::reference($record))
                && is_int($completion['completed_at']) && $record['terms']['not_before'] <= $completion['completed_at']
                && $completion['completed_at'] < $record['terms']['expires_at'], 'COMPLETION_ORIGINAL');
        }
        return $extension;
    }
    private static function terms(array $terms, ?string $root = null): void
    {
        self::bounded($terms);
        self::shape($terms, ['schema', 'purpose', 'root_identity', 'instance_id', 'citadel_id', 'operator_id', 'operator_trust_fingerprint',
            'formation_trust_fingerprint', 'founding', 'package', 'expected_head', 'not_before', 'expires_at', 'nonce', 'correlation', 'reason']);
        self::need($terms['schema'] === self::TERMS && $terms['purpose'] === self::EFFECT, 'TERMS_PURPOSE');
        R::digest($terms['root_identity']);
        R::digest($terms['operator_trust_fingerprint']);
        self::need(is_string($terms['formation_trust_fingerprint']) && preg_match('/^[a-f0-9]{64}$/D', $terms['formation_trust_fingerprint']) === 1, 'TRUST_FINGERPRINT');
        foreach (['instance_id', 'citadel_id', 'operator_id', 'correlation', 'reason'] as $field) {
            self::need(is_string($terms[$field]) && trim($terms[$field]) !== '' && strlen($terms[$field]) <= 1024, 'TERMS_TEXT');
        }
        self::need(is_string($terms['nonce']) && preg_match('/^[a-f0-9]{48}$/D', $terms['nonce']) === 1, 'TERMS_NONCE');
        self::need(is_int($terms['not_before']) && is_int($terms['expires_at']) && $terms['not_before'] >= 0
            && $terms['not_before'] < $terms['expires_at'] && $terms['expires_at'] - $terms['not_before'] <= 3600, 'TERMS_INTERVAL');
        R::time($terms['not_before']); R::time($terms['expires_at']);
        R::head($terms['expected_head']); self::shape($terms['founding'], ['policy', 'constitution', 'command', 'completion', 'holder']);
        self::need(($terms['package']['instance_id'] ?? null) === $terms['instance_id'], 'PACKAGE_INSTANCE');
        if ($root !== null) { (new OperatorRootPersonnelInstallationService($root))->establishmentRecords($terms['package']); }
    }
    private static function operatorSignature(array $record, array $trust): void
    {
        $envelope = $record['operator']; self::shape($envelope, ['payload', 'signature']);
        self::need(R::same($envelope['payload'], self::operatorPayload($record['terms']))
            && ($trust['fingerprint'] ?? null) === $record['terms']['operator_trust_fingerprint']
            && R::same($trust['issuer'] ?? null, $envelope['payload']['issuer']) && ($trust['competence'] ?? null) === 'OPERATOR_BOOTSTRAP_POLICY', 'OPERATOR_SIGNATURE_SCOPE');
        self::signature($envelope, $trust['public_key'] ?? '');
    }
    private static function formationSignature(array $state, array $envelope, string $effect, array $terms): void
    {
        self::shape($envelope, ['payload', 'signature']); $payload = $envelope['payload'];
        self::shape($payload, ['schema', 'citadel_id', 'trust_fingerprint', 'effect', 'object_digest', 'issued_at', 'expires_at', 'nonce']);
        self::need($payload['schema'] === 'imperium.citadel-owner-decision/v1' && $payload['citadel_id'] === ($state['citadel_id'] ?? null)
            && $payload['trust_fingerprint'] === ($state['trust']['fingerprint'] ?? null) && $payload['effect'] === $effect
            && $payload['object_digest'] === FormationJournal::digest($terms), 'FORMATION_SIGNATURE_SCOPE');
        self::need(($state['trust']['competence'] ?? null) === 'CITADEL_MISSION_FORMATION'
            && is_int($payload['issued_at']) && is_int($payload['expires_at'])
            && $payload['issued_at'] >= $state['trust']['not_before'] && $payload['issued_at'] < $payload['expires_at']
            && $payload['expires_at'] <= $state['trust']['expires_at']
            && is_string($payload['nonce']) && preg_match('/^[a-f0-9]{48}$/D', $payload['nonce']) === 1, 'FORMATION_HISTORY');
        self::signature($envelope, $state['trust']['public_key'] ?? '');
    }
    private static function signature(array $envelope, string $key): void
    {
        $public = base64_decode($key, true); $signature = base64_decode($envelope['signature'], true);
        self::need(is_string($public) && strlen($public) === 32 && is_string($signature) && strlen($signature) === 64
            && sodium_crypto_sign_verify_detached($signature, CanonicalJson::encode($envelope['payload']), $public), 'SIGNATURE');
    }
    public static function reference(array $record): array { return ['schema' => $record['schema'], 'id' => $record['id'], 'digest' => $record['record_digest']]; }
    private static function seal(array $record): array { return [...$record, 'record_digest' => FormationJournal::digest($record)]; }
    private static function intact(array $record): void { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); self::need($digest === FormationJournal::digest($record), 'RECORD_DIGEST'); }
    private static function shape(array $value, array $keys): void { self::need(FormationJournal::keys($value, $keys), 'SHAPE'); }
    public static function bounded(array $value, int $bytes = 4194304): void
    {
        $count = 0;
        $walk = static function (mixed $item, int $depth) use (&$walk, &$count): void {
            self::need(++$count <= 100000 && $depth <= 32, 'STRUCTURE_BOUND');
            if (is_array($item)) { foreach ($item as $child) { $walk($child, $depth + 1); } }
            else { self::need(is_null($item) || is_scalar($item), 'JSON_VALUE'); }
        };
        $walk($value, 0); self::need(strlen(CanonicalJson::encode($value)) <= $bytes, 'BYTE_BOUND');
    }
    private static function need(bool $ok, string $code): void { if (!$ok) { throw new \RuntimeException('PPC7_'.$code); } }
}
