<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R, StrictJson};

/** PPC5 permanent-seat preparation only. No designation, appointment or application. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FormationModelPreparation
{
    public const STATE = 'imperium.formation-model-preparation-state/v1';
    public const BINDING = 'imperium.formation-model-specification-binding/v1';
    public const DELEGATION = 'imperium.formation-model-sealing-delegation/v1';
    public const AUTHORIZATION = 'imperium.formation-model-preparation-authorization/v1';
    public const SEAL = 'imperium.formation-model-preparation-seal/v1';
    public const LINE = 'imperium.formation-model-specification-line/v1';
    public const SOURCE = 'imperium.formation-model-source-profile/v1';
    public const EVIDENCE = 'imperium.formation-model-bound-profile-evidence/v2';
    public const MAX = 256;
    public const LIFETIME = 3600;

    public function __construct(private FormationJournal $journal, private FormationSignatures $signatures,
        private Clock $clock, private FormationInstitution $institution) {}

    public function initialize(array $terms, array $decision): array
    {
        return $this->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($terms, $decision): array {
            self::shape($terms, ['schema', 'instance_id', 'citadel_id', 'expected_head']);
            if (isset($state['model_preparation'])) {
                $s = $this->extension($state);
                self::need(R::same($s['initialization'], ['terms' => $terms, 'decision' => $decision]), 'CHANGED_REPLAY');
                return $s['initialization'];
            }
            self::need($terms['schema'] === self::STATE && !isset($state['model_preparation']), 'INITIALIZATION');
            foreach (['sessions', 'claims', 'reservations'] as $field) { self::need(($state[$field] ?? []) === [], 'IN_FLIGHT'); }
            foreach (['claims', 'source_fences'] as $field) { self::need(($state['onboarding'][$field] ?? []) === [], 'IN_FLIGHT'); }
            $actor = $this->institution->actorInOwner($owner, 'conscription');
            self::need($terms['instance_id'] === $actor['instance_id'] && $terms['citadel_id'] === ($state['citadel_id'] ?? null)
                && ($state['parent_instance_id'] ?? $actor['instance_id']) === $actor['instance_id'], 'IDENTITY');
            self::head($terms['expected_head'], $head);
            $this->signatures->verify($state, $decision, 'INITIALIZE_FORMATION_MODEL_PREPARATION', $terms);
            $state['parent_instance_id'] = $actor['instance_id'];
            $initial = ['terms' => $terms, 'decision' => $decision];
            $state['model_preparation'] = ['schema' => self::STATE, 'initialization' => $initial,
                'bindings' => [], 'lineages' => [], 'delegations' => [], 'authorizations' => [], 'seals' => [], 'consumed' => [], 'nonces' => []];
            return $initial;
        });
    }

    /** Factual candidate only. Generation is calculated from retained native lineage. */
    public function prepareBinding(array $terms): array
    {
        return $this->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($terms): array {
            self::shape($terms, ['id', 'context', 'lineage_id', 'predecessor', 'specification', 'binding_original', 'configuration_original', 'supporting_originals', 'expected_head']);
            $s = $this->extension($state);
            if (isset($s['bindings'][$terms['id']])) {
                $old = self::lookup($s['bindings'], self::reference($s['bindings'][$terms['id']]), self::BINDING);
                $input = $old['body']; unset($input['binding_generation'], $input['binding_ref'], $input['configuration_ref']);
                self::need(R::same(['id' => $old['id'], ...$input], $terms), 'CHANGED_REPLAY');
                return $old;
            }
            self::head($terms['expected_head'], $head);
            $this->context($state, $terms['context']);
            $actor = $this->institution->actorInOwner($owner, 'conscription');
            self::need($actor['instance_id'] === $terms['context']['instance_id'], 'IDENTITY');
            self::id($terms['id']); self::id($terms['lineage_id']);
            self::need($terms['lineage_id'] === self::lineageId($terms['context'], $terms['specification']), 'LINEAGE_ID');
            self::need(!isset($s['bindings'][$terms['id']]), 'IDENTITY_REUSE');
            $prior = $s['lineages'][$terms['lineage_id']] ?? null;
            self::need(R::same($prior, $terms['predecessor']), 'PREDECESSOR');
            $generation = 1;
            if ($prior !== null) {
                $old = self::lookup($s['bindings'], $prior, self::BINDING);
                self::need(R::same($old['body']['context'], $terms['context']), 'LINEAGE_CONTEXT');
                $generation = $old['body']['binding_generation'] + 1;
            }
            self::need($generation <= self::MAX, 'GENERATION_BOUND');
            $this->originals($terms['context'], $terms['specification'], $terms['binding_original'], $terms['configuration_original'], $terms['supporting_originals']);
            $body = $terms; unset($body['id']);
            $body['binding_generation'] = $generation;
            $body['binding_ref'] = R::reference($terms['binding_original']);
            $body['configuration_ref'] = R::reference($terms['configuration_original']);
            $record = self::record(self::BINDING, $terms['id'], $body);
            self::room($s['bindings']); $s['bindings'][$record['id']] = $record;
            $s['lineages'][$terms['lineage_id']] = self::reference($record);
            $state['model_preparation'] = $s;
            return $record;
        });
    }

    public function delegate(array $terms, array $decision): array
    {
        return $this->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($terms, $decision): array {
            $s = $this->extension($state);
            self::shape($terms, ['id', 'context', 'binding', 'actor', 'public_key', 'not_before', 'expires_at', 'expected_head']);
            if (($old = self::replay($s['delegations'], self::DELEGATION, $terms, $decision)) !== null) { return $old; }
            self::head($terms['expected_head'], $head);
            $this->delegationTerms($owner, $state, $s, $terms);
            $this->ownerDecision($state, $s, $decision, 'DELEGATE_FORMATION_MODEL_SEALING', $terms);
            $record = self::record(self::DELEGATION, $terms['id'], ['terms' => $terms, 'decision' => $decision]);
            self::room($s['delegations']); self::need(!isset($s['delegations'][$record['id']]), 'IDENTITY_REUSE');
            $s['delegations'][$record['id']] = $record; $state['model_preparation'] = $s;
            return $record;
        });
    }

    public function authorize(array $terms, array $decision): array
    {
        return $this->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($terms, $decision): array {
            $s = $this->extension($state);
            if (($old = self::replay($s['authorizations'], self::AUTHORIZATION, $terms, $decision)) !== null) { return $old; }
            self::head($terms['expected_head'] ?? [], $head);
            $this->authorizationTerms($owner, $state, $s, $terms);
            $this->ownerDecision($state, $s, $decision, 'AUTHORIZE_FORMATION_MODEL_PREPARATION', $terms);
            self::need(!isset($s['authorizations'][$terms['id']]), 'IDENTITY_REUSE');
            $record = self::record(self::AUTHORIZATION, $terms['id'], ['terms' => $terms, 'decision' => $decision]);
            self::room($s['authorizations']); $s['authorizations'][$record['id']] = $record;
            $state['model_preparation'] = $s;
            return $record;
        });
    }

    /** Factual typed original of retained personnel bytes, not continuing competence. */
    public function sourceOriginal(string $evidenceId): array
    {
        return $this->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($evidenceId): array {
            $owner->assertOwner($this->journal); $this->extension($frame['state']);
            $envelope = $frame['state']['personnel_evidence'][$evidenceId] ?? [];
            self::need(FormationJournal::digest($envelope) === $evidenceId, 'SOURCE_PROFILE_ORIGINAL');
            return self::record(self::SOURCE, 'source-profile-'.$evidenceId, ['envelope' => $envelope]);
        });
    }

    /** Signing preparation is public bytes, never a currentness certificate. */
    public function sealingPayload(array $authorization, array $expectedHead, string $nonce): array
    {
        return $this->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($authorization, $expectedHead, $nonce): array {
            self::head($expectedHead, ['generation' => $frame['generation'], 'digest' => $frame['record_digest']]);
            $s = $this->extension($frame['state']);
            $a = self::lookup($s['authorizations'], $authorization, self::AUTHORIZATION);
            $this->currentAuthorization($owner, $frame['state'], $s, $a);
            self::need(!isset($s['consumed'][$a['id']]), 'CONSUMED');
            return $this->payload($a, $s, $expectedHead, $nonce);
        });
    }

    public function seal(array $envelope): array
    {
        return $this->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($envelope): array {
            self::shape($envelope, ['payload', 'signature']); $s = $this->extension($state);
            $p = $envelope['payload']; self::need(is_array($p), 'SEAL');
            $a = self::lookup($s['authorizations'], $p['authorization'] ?? [], self::AUTHORIZATION);
            if (isset($s['consumed'][$a['id']])) {
                $old = self::lookup($s['seals'], $s['consumed'][$a['id']], self::SEAL);
                self::need(R::same($old['body']['envelope'], $envelope), 'CHANGED_REPLAY');
                return $old; // Exact historical fact only; no renewed current verification.
            }
            self::head($p['expected_head'] ?? [], $head);
            $this->currentAuthorization($owner, $state, $s, $a);
            $expected = $this->payload($a, $s, $head, $p['nonce'] ?? '');
            self::need(R::same($expected, $p), 'SEAL_BYTES');
            foreach ($s['seals'] as $existing) {
                $oldProfile = $existing['body']['profile'];
                self::need($oldProfile['profile_id'] !== $p['profile']['profile_id']
                    || $oldProfile['profile_version'] !== $p['profile']['profile_version'], 'PROFILE_VERSION_REUSE');
            }
            $d = self::lookup($s['delegations'], $a['body']['terms']['delegation'], self::DELEGATION);
            self::signature($envelope, $d['body']['terms']['public_key']);
            self::need(!isset($s['nonces'][$p['nonce']]), 'NONCE_REUSE'); self::room($s['nonces']);
            $record = self::record(self::SEAL, 'seal-'.FormationJournal::digest($envelope), ['envelope' => $envelope, 'profile' => $p['profile']]);
            self::room($s['seals']); $s['seals'][$record['id']] = $record;
            $s['consumed'][$a['id']] = self::reference($record);
            $s['nonces'][$p['nonce']] = FormationJournal::digest($envelope);
            // One existing journal publication commits consumption and complete seal together.
            $state['model_preparation'] = $s;
            return $record;
        });
    }

    /** Internal, fixed verifier. Reads fresh custody through the supplied live frame. */
    public function verifyInOwner(FormationOwnerFrame $owner, array $profile, string $seat, array $correspondence): void
    {
        $state = $this->journal->readInOwner($owner)['state']; $s = $this->extension($state);
        self::shape($correspondence, ['seal', 'binding_ref', 'configuration_ref', 'binding_generation']);
        $seal = self::lookup($s['seals'], $correspondence['seal'], self::SEAL);
        $p = $seal['body']['envelope']['payload'];
        $a = self::lookup($s['authorizations'], $p['authorization'], self::AUTHORIZATION);
        $this->currentAuthorization($owner, $state, $s, $a);
        $b = self::lookup($s['bindings'], $a['body']['terms']['binding'], self::BINDING);
        self::need(R::same($s['consumed'][$a['id']] ?? null, self::reference($seal))
            && R::same($p, $this->payload($a, $s, $p['expected_head'], $p['nonce']))
            && R::same($seal['body']['profile'], $profile) && R::same($p['profile'], $profile)
            && $b['body']['context']['seat'] === $seat, 'CORRESPONDENCE');
        foreach (['binding_ref', 'configuration_ref', 'binding_generation'] as $field) {
            self::need(R::same($correspondence[$field], $b['body'][$field]), 'CORRESPONDENCE');
        }
        $d = self::lookup($s['delegations'], $a['body']['terms']['delegation'], self::DELEGATION);
        self::signature($seal['body']['envelope'], $d['body']['terms']['public_key']);
    }

    public static function reference(array $record): array
    {
        return ['schema' => $record['schema'], 'id' => $record['id'], 'digest' => $record['record_digest']];
    }

    public static function sourceLine(array $binding): array
    {
        $body = $binding['body'];
        $line = ['schema' => self::LINE, 'line_number' => 1, 'binding' => self::reference($binding),
            'specification' => $body['specification'], 'binding_ref' => $body['binding_ref'],
            'configuration_ref' => $body['configuration_ref'], 'binding_generation' => $body['binding_generation']];
        return [...$line, 'line_digest' => FormationJournal::digest($line)];
    }

    public static function lineageId(array $context, array $specification): string
    {
        return 'lineage-'.FormationJournal::digest([$context, $specification['provider'], $specification['model_id']]);
    }

    private function extension(array $state): array
    {
        $s = $state['model_preparation'] ?? [];
        self::shape($s, ['schema', 'initialization', 'bindings', 'lineages', 'delegations', 'authorizations', 'seals', 'consumed', 'nonces']);
        self::need($s['schema'] === self::STATE, 'STATE_VERSION');
        foreach (['bindings', 'lineages', 'delegations', 'authorizations', 'seals', 'consumed', 'nonces'] as $map) {
            self::need(is_array($s[$map]) && count($s[$map]) <= self::MAX, 'STATE_BOUND');
        }
        return $s;
    }

    private function context(array $state, array $c): void
    {
        self::shape($c, ['instance_id', 'citadel_id', 'seat', 'steward']);
        self::need($c['instance_id'] === ($state['parent_instance_id'] ?? null) && $c['citadel_id'] === ($state['citadel_id'] ?? null)
            && in_array($c['seat'], FormationModelBoundProfileContract::TARGETS, true)
            && $c['steward'] === ['kind' => 'office', 'id' => 'laboratorium'], 'CONTEXT');
    }

    private function originals(array $c, array $spec, array $binding, array $configuration, array $ancestors): void
    {
        self::need(array_is_list($ancestors) && count($ancestors) <= 64
            && strlen(CanonicalJson::encode($ancestors)) <= 1048576, 'O4_ANCESTOR_BOUND');
        $known = [];
        // All supplied ancestry must precede its use. No recursion, unresolved or forward refs.
        foreach ([...$ancestors, $configuration, $binding] as $h) {
            self::need(is_array($h) && is_array($h['sources'] ?? null) && count($h['sources']) <= 64
                && strlen(CanonicalJson::encode($h)) <= 131072, 'O4_WRAPPER_BOUND');
            R::record($h);
            self::need($h['instance_id'] === $c['instance_id'] && $h['citadel_id'] === $c['citadel_id']
                && $h['created_at'] <= $this->clock->now()->getTimestamp(), 'O4_ORIGINAL');
            $unsigned = $h; unset($unsigned['record_digest']);
            self::need($h['record_digest'] === 'sha256:'.FormationJournal::digest($unsigned), 'DIGEST_CONVERSION');
            foreach ($h['sources'] as $ref) {
                self::need(isset($known[R::key($ref)]) && R::same(R::reference($known[R::key($ref)]), $ref), 'O4_SOURCE_REF');
            }
            $key = R::key(R::reference($h)); self::need(!isset($known[$key]), 'O4_DUPLICATE_ORIGINAL');
            $known[$key] = $h;
        }
        self::shape($spec, ['provider', 'model_id', 'model_version', 'provider_model_version', 'configuration', 'constraints', 'fallbacks', 'access_assertion_required']);
        self::need($spec['provider'] === 'deepseek' && in_array($spec['model_id'], ['deepseek-v4-flash', 'deepseek-v4-pro'], true)
            && $spec['access_assertion_required'] === true, 'SPECIFICATION');
        foreach (['model_version', 'provider_model_version'] as $field) { self::text($spec[$field]); }
        foreach (['constraints', 'fallbacks'] as $field) {
            self::need(is_array($spec[$field]) && array_is_list($spec[$field]) && count($spec[$field]) <= 64, 'SPECIFICATION');
            foreach ($spec[$field] as $v) { self::text($v); }
            self::need(count(array_unique($spec[$field])) === count($spec[$field]), 'SPECIFICATION');
        }
        $contents = [];
        foreach (['candidate-binding' => $binding, 'request-configuration' => $configuration] as $kind => $h) {
            self::need(is_array($h['sources'] ?? null) && count($h['sources']) <= 64
                && strlen(CanonicalJson::encode($h)) <= 131072, 'O4_WRAPPER_BOUND');
            R::record($h); self::shape($h['body'], ['kind', 'content', 'content_digest', 'limitations']);
            self::need($h['schema'] === 'imperium.bootstrap-source/v1' && $h['instance_id'] === $c['instance_id']
                && $h['citadel_id'] === $c['citadel_id'] && $h['created_at'] <= $this->clock->now()->getTimestamp()
                && $h['body']['kind'] === $kind && is_string($h['body']['content']) && strlen($h['body']['content']) <= 65536
                && $h['body']['content_digest'] === 'sha256:'.hash('sha256', $h['body']['content']), 'O4_ORIGINAL');
            self::text($h['body']['limitations']);
            // StrictJson and native canonical hashing agree on the accepted input domain.
            $unsigned = $h; unset($unsigned['record_digest']);
            self::need($h['record_digest'] === 'sha256:'.FormationJournal::digest($unsigned), 'DIGEST_CONVERSION');
            $contents[$kind] = $kind === 'request-configuration' ? StrictJson::decode($h['body']['content']) : $h['body']['content'];
        }
        // Existing selected-policy candidate originals use their literal model alias.
        self::need($contents['candidate-binding'] === $spec['model_id'], 'MODEL_ORIGINAL');
        $fixed = ['max_tokens' => 4096, 'stream' => false, 'temperature' => 0, 'thinking' => ['type' => 'disabled'],
            'response_format' => ['type' => 'json_object'], 'message_roles' => ['system', 'user']];
        self::need(R::same($contents['request-configuration'], $fixed) && R::same($spec['configuration'], $fixed), 'CONFIGURATION');
    }

    private function currentBinding(array $state, array $s, array $ref): array
    {
        $b = self::lookup($s['bindings'], $ref, self::BINDING); $v = $b['body'];
        self::shape($v, ['context', 'lineage_id', 'predecessor', 'specification', 'binding_original', 'configuration_original', 'supporting_originals', 'expected_head', 'binding_generation', 'binding_ref', 'configuration_ref']);
        $this->context($state, $v['context']);
        self::need(R::same($s['lineages'][$v['lineage_id']] ?? null, $ref), 'STALE_BINDING');
        $this->originals($v['context'], $v['specification'], $v['binding_original'], $v['configuration_original'], $v['supporting_originals']);
        self::need(R::same($v['binding_ref'], R::reference($v['binding_original']))
            && R::same($v['configuration_ref'], R::reference($v['configuration_original'])), 'ORIGINAL_REF');
        $cursor = $b; $seen = [];
        for ($n = 0; ; ++$n) {
            self::need($n < self::MAX && !isset($seen[$cursor['id']]), 'LINEAGE_BOUND'); $seen[$cursor['id']] = true;
            $body = $cursor['body']; $g = $body['binding_generation'];
            self::need(is_int($g) && $g >= 1 && $g <= self::MAX, 'GENERATION');
            if ($body['predecessor'] === null) { self::need($g === 1, 'GENERATION'); break; }
            $prior = self::lookup($s['bindings'], $body['predecessor'], self::BINDING);
            self::need($prior['body']['binding_generation'] === $g - 1 && $prior['body']['lineage_id'] === $v['lineage_id']
                && R::same($prior['body']['context'], $v['context']), 'PREDECESSOR');
            $cursor = $prior;
        }
        return $b;
    }

    private function delegationTerms(FormationOwnerFrame $owner, array $state, array $s, array $t): void
    {
        self::shape($t, ['id', 'context', 'binding', 'actor', 'public_key', 'not_before', 'expires_at', 'expected_head']);
        self::id($t['id']); $this->context($state, $t['context']); $this->validity($t);
        $b = $this->currentBinding($state, $s, $t['binding']);
        self::need(R::same($b['body']['context'], $t['context'])
            && R::same($t['actor'], $this->institution->actorInOwner($owner, 'conscription'))
            && $t['actor']['instance_id'] === $t['context']['instance_id'], 'SEALER_TENURE');
        R::bytes($t['public_key'], 32);
    }

    private function authorizationTerms(FormationOwnerFrame $owner, array $state, array $s, array $t): void
    {
        self::shape($t, ['id', 'purpose', 'context', 'binding', 'source_line', 'source_profile', 'source_evidence', 'delegation', 'not_before', 'expires_at', 'expected_head', 'nonce', 'correlation', 'reason']);
        self::id($t['id']); self::nonce($t['nonce']); self::text($t['correlation']); self::text($t['reason']);
        self::need($t['purpose'] === 'ONE_EXACT_CONSCRIPTION_MODEL_SEAL', 'PURPOSE');
        $this->context($state, $t['context']); $this->validity($t);
        $b = $this->currentBinding($state, $s, $t['binding']);
        self::need(R::same($b['body']['context'], $t['context']) && R::same($t['source_line'], self::sourceLine($b)), 'SOURCE_LINE');
        $d = self::lookup($s['delegations'], $t['delegation'], self::DELEGATION);
        $this->delegationTerms($owner, $state, $s, $d['body']['terms']);
        $this->signatures->verify($state, $d['body']['decision'], 'DELEGATE_FORMATION_MODEL_SEALING', $d['body']['terms']);
        self::need(R::same($d['body']['terms']['binding'], $t['binding']) && $t['expires_at'] <= $d['body']['terms']['expires_at'], 'DELEGATION');
        $profile = $t['source_profile'];
        if (isset($profile['model_binding'])) {
            FormationModelBoundProfileContract::validate($profile, $profile['source_persona'], $t['context']['seat']);
        } else { FormationProfileContract::validate($profile, $profile['source_persona'] ?? [], $t['context']['seat']); }
        self::need(strlen(CanonicalJson::encode($profile)) <= 131072, 'PROFILE_BOUND');
        self::need(is_array($t['source_evidence']), 'SOURCE_PROFILE_ORIGINAL');
        $source = $t['source_evidence']; self::shape($source, ['schema', 'id', 'body', 'record_digest']);
        self::need(strlen(CanonicalJson::encode($source)) <= 262144, 'SOURCE_PROFILE_BOUND');
        self::shape($source['body'], ['envelope']); $evidence = $source['body']['envelope'];
        $evidenceId = FormationJournal::digest($evidence);
        self::need(R::same($source, self::record(self::SOURCE, 'source-profile-'.$evidenceId, ['envelope' => $evidence]))
            && R::same($state['personnel_evidence'][$evidenceId] ?? null, $evidence), 'SOURCE_PROFILE_ORIGINAL');
        $ep = $evidence['payload'] ?? [];
        $ed = $state['personnel_delegations'][$ep['delegation'] ?? ''] ?? [];
        self::need(($ep['kind'] ?? null) === 'DERIVED_PROFILE' && ($ed['terms']['role'] ?? null) === 'laboratorium'
            && ($ep['scope'] ?? null) === $t['context']['citadel_id']
            && R::same($ep['content']['artifact'] ?? null, $profile)
            && ($ep['content']['seat'] ?? null) === $t['context']['seat']
            && ($ep['subject'] ?? null) === $profile['profile_id']
            && ($ep['expires_at'] ?? 0) > $this->clock->now()->getTimestamp()
            && ($ep['expires_at'] ?? PHP_INT_MAX) <= ($ed['terms']['expires_at'] ?? 0), 'SOURCE_PROFILE_ORIGINAL');
        $this->signatures->verify($state, $ed['decision'], 'DELEGATE_PERSONNEL_EVIDENCE', $ed['terms']);
        self::need(R::same($ed['terms']['actor'], $this->institution->actorInOwner($owner, 'laboratorium')), 'SOURCE_PROFILE_TENURE');
        self::signature($evidence, $ed['terms']['public_key']);
        self::nextVersion($profile['profile_version']);
    }

    private function currentAuthorization(FormationOwnerFrame $owner, array $state, array $s, array $a): void
    {
        self::shape($a['body'], ['terms', 'decision']);
        $this->authorizationTerms($owner, $state, $s, $a['body']['terms']);
        $this->signatures->verify($state, $a['body']['decision'], 'AUTHORIZE_FORMATION_MODEL_PREPARATION', $a['body']['terms']);
        self::need($a['body']['terms']['expires_at'] <= $a['body']['decision']['payload']['expires_at'], 'VALIDITY');
    }

    private function ownerDecision(array $state, array &$s, array $decision, string $effect, array $terms): void
    {
        $p = $this->signatures->verify($state, $decision, $effect, $terms);
        self::need($terms['expires_at'] <= $p['expires_at'] && !isset($s['nonces'][$p['nonce']]), 'DECISION_REUSE');
        self::room($s['nonces']); $s['nonces'][$p['nonce']] = FormationJournal::digest($decision);
        if (isset($terms['nonce'])) {
            self::need($terms['nonce'] === $p['nonce'], 'NONCE');
        }
    }

    private function payload(array $a, array $s, array $head, string $nonce): array
    {
        self::nonce($nonce); R::head($head); $t = $a['body']['terms'];
        $b = self::lookup($s['bindings'], $t['binding'], self::BINDING); $spec = $b['body']['specification'];
        $profile = $t['source_profile']; unset($profile['content_digest']);
        $profile['profile_version'] = self::nextVersion($t['source_profile']['profile_version']);
        $profile['lineage']['supersedes'] = array_intersect_key($t['source_profile'], array_flip(['profile_id', 'profile_version', 'content_digest']));
        $profile['model_binding'] = ['provider_model_version' => $spec['provider_model_version'], 'authorization_id' => $a['id'],
            'source_line' => ['line_number' => 1, 'line_digest' => $t['source_line']['line_digest']],
            'configuration' => $spec['configuration'], 'constraints' => $spec['constraints'], 'fallbacks' => $spec['fallbacks'], 'access_assertion_required' => true];
        $profile['content_digest'] = 'sha256:'.FormationJournal::digest($profile);
        return ['schema' => 'imperium.formation-model-sealing-act/v1', 'domain' => 'IMPERIUM_FORMATION_MODEL_SEALING_V1',
            'effect' => 'SEAL_AUTHORIZED_FORMATION_MODEL_PROFILE', 'authorization' => self::reference($a),
            'delegation' => $t['delegation'], 'context' => $t['context'], 'binding' => $t['binding'], 'source_line' => $t['source_line'],
            'source_evidence' => self::reference($t['source_evidence']),
            'source_profile' => $t['source_profile'], 'profile' => $profile, 'expected_head' => $head, 'nonce' => $nonce];
    }

    private function validity(array $t): void
    {
        $now = $this->clock->now()->getTimestamp();
        self::need(is_int($t['not_before']) && is_int($t['expires_at']) && $t['not_before'] > 0
            && $t['not_before'] <= $now && $now < $t['expires_at'] && $t['expires_at'] <= 253402300799
            && $t['expires_at'] - $t['not_before'] <= self::LIFETIME, 'VALIDITY');
    }

    private static function nextVersion(string $v): string
    {
        self::need(preg_match('/\A(0|[1-9][0-9]{0,5})\.(0|[1-9][0-9]{0,5})(?:\.(0|[1-9][0-9]{0,5}))?\z/', $v, $m) === 1
            && (int)($m[2] ?? self::MAX) < 999999, 'PROFILE_VERSION');
        return $m[1].'.'.((int)$m[2] + 1).(isset($m[3]) ? '.0' : '');
    }

    private static function signature(array $envelope, string $public): void
    {
        $key = R::bytes($public, 32); $signature = R::bytes($envelope['signature'], 64);
        self::need(sodium_crypto_sign_verify_detached($signature, CanonicalJson::encode($envelope['payload']), $key), 'SIGNATURE');
    }

    private static function record(string $schema, string $id, array $body): array
    {
        self::id($id); $r = ['schema' => $schema, 'id' => $id, 'body' => $body];
        return [...$r, 'record_digest' => FormationJournal::digest($r)];
    }

    private static function lookup(array $map, array $ref, string $schema): array
    {
        self::shape($ref, ['schema', 'id', 'digest']); self::id($ref['id']);
        $r = $map[$ref['id']] ?? [];
        self::shape($r, ['schema', 'id', 'body', 'record_digest']);
        self::need($ref['schema'] === $schema && $r['schema'] === $schema && R::same(self::reference($r), $ref)
            && R::same($r, self::record($schema, $ref['id'], $r['body'])), 'ORIGINAL');
        return $r;
    }

    private static function replay(array $map, string $schema, array $terms, array $decision): ?array
    {
        if (!isset($map[$terms['id'] ?? ''])) { return null; }
        $old = self::lookup($map, self::reference($map[$terms['id']]), $schema);
        self::need(R::same($old['body'], ['terms' => $terms, 'decision' => $decision]), 'CHANGED_REPLAY');
        return $old;
    }

    private static function head(array $given, array $head): void { R::head($given); self::need(R::same($given, $head), 'STALE_HEAD'); }
    private static function shape(array $v, array $keys): void { self::need(FormationJournal::keys($v, $keys), 'SHAPE'); }
    private static function id(string $v): void { self::need(preg_match('/\A[a-z0-9][a-z0-9._-]{7,79}\z/', $v) === 1, 'ID'); }
    private static function nonce(string $v): void { self::need(preg_match('/\A[a-f0-9]{48}\z/', $v) === 1, 'NONCE'); }
    private static function text(string $v): void { self::need(trim($v) !== '' && strlen($v) <= 4096, 'TEXT'); }
    private static function room(array $map): void { self::need(count($map) < self::MAX, 'STATE_BOUND'); }
    private static function need(bool $ok, string $code): void { if (!$ok) { throw new \RuntimeException('PPC5_'.$code); } }
}
