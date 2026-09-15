<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson;
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;

/** Closed native history grammar. No generic act or nonce grants this competence. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class FreshEstablishmentRevocations
{
    public const STATE = 'imperium.fresh-institutional-establishment/v2';
    public const ACT = 'imperium.fresh-institutional-revocation-authorization/v1';
    public const RECEIPT = 'imperium.fresh-institutional-revocation/v1';
    public const DOMAIN = 'IMPERIUM_FRESH_INSTITUTIONS_REVOCATION_V1';
    public const EFFECT = 'REVOKE_FRESH_FORMATION_AUTHORIZATION';
    public const LIMIT = 32;

    public static function target(array $target, array $trust, ?string $root = null): array
    {
        self::shape($target, ['terms', 'operator']);
        return FormationFreshEstablishment::nativeAuthorizationReference($target['terms'], $target['operator'], $trust, $root);
    }

    public static function envelope(array $envelope, array $trust): array
    {
        FormationFreshEstablishment::bounded($envelope);
        self::shape($envelope, ['payload', 'signature']); $p = $envelope['payload'];
        self::shape($p, ['schema', 'domain', 'effect', 'target', 'target_ref', 'root_identity', 'instance_id', 'citadel_id',
            'operator_id', 'trust_fingerprint', 'expected_head', 'adapter_from', 'issued_at', 'expires_at', 'nonce', 'correlation', 'reason']);
        self::need($p['schema'] === self::ACT && $p['domain'] === self::DOMAIN && $p['effect'] === self::EFFECT, 'PURPOSE');
        $ref = self::target($p['target'], $trust); self::need(R::same($ref, $p['target_ref']), 'TARGET_REFERENCE');
        foreach (['root_identity', 'instance_id', 'citadel_id', 'operator_id'] as $key) {
            self::need($p[$key] === $p['target']['terms'][$key], 'IDENTITY');
        }
        self::need($p['trust_fingerprint'] === $trust['fingerprint']
            && in_array($p['adapter_from'], [FormationFreshEstablishment::STATE, self::STATE], true), 'ADAPTER');
        R::head($p['expected_head']); R::time($p['issued_at']); R::time($p['expires_at']);
        self::need($trust['not_before'] <= $p['issued_at'] && $p['issued_at'] < $p['expires_at']
            && $p['expires_at'] <= $trust['expires_at'] && $p['expires_at'] - $p['issued_at'] <= 3600, 'INTERVAL');
        self::need(is_string($p['nonce']) && preg_match('/^[a-f0-9]{48}$/D', $p['nonce']) === 1, 'NONCE');
        foreach (['correlation', 'reason'] as $key) {
            self::need(is_string($p[$key]) && trim($p[$key]) !== '' && strlen($p[$key]) <= 1024, 'TEXT');
        }
        $key = base64_decode($trust['public_key'], true); $signature = base64_decode($envelope['signature'], true);
        self::need(is_string($key) && strlen($key) === 32 && is_string($signature) && strlen($signature) === 64
            && sodium_crypto_sign_verify_detached($signature, CanonicalJson::encode($p), $key), 'SIGNATURE');
        return $p;
    }

    /** Authentication here is historical; current competence belongs to the producer. */
    public static function history(array $state): array
    {
        $extension = $state['fresh_institutions'];
        if ($extension['schema'] === FormationFreshEstablishment::STATE) { return []; }
        self::need($extension['schema'] === self::STATE && is_array($extension['revocations'])
            && count($extension['revocations']) >= 1 && count($extension['revocations']) <= self::LIMIT, 'HISTORY_BOUND');
        $trust = $state['onboarding']['trust']['body'] ?? []; $ordered = []; $founding = self::foundingOriginals($state);
        foreach ($extension['revocations'] as $nonce => $r) {
            self::shape($r, ['schema', 'id', 'sequence', 'envelope', 'recorded_at', 'record_digest']);
            $body = $r; unset($body['record_digest']);
            self::need($r['schema'] === self::RECEIPT && $r['record_digest'] === FormationJournal::digest($body)
                && $r['id'] === 'fresh-revocation-'.FormationJournal::digest($r['envelope']), 'RECEIPT');
            $p = self::envelope($r['envelope'], $trust);
            self::need($nonce === $p['nonce'] && is_int($r['sequence']) && $r['sequence'] >= 1
                && $r['sequence'] <= self::LIMIT && !isset($ordered[$r['sequence']]), 'SEQUENCE');
            R::time($r['recorded_at']); self::need($p['issued_at'] <= $r['recorded_at'] && $r['recorded_at'] < $p['expires_at'], 'RECORDED_TIME');
            foreach (['root_identity', 'instance_id', 'citadel_id'] as $key) {
                self::need($p[$key] === $extension['initialization']['terms'][$key], 'HISTORY_IDENTITY');
            }
            self::need(R::same(R::reference($p['target']['terms']['founding']['holder']), $extension['initialization']['terms']['holder_ref']), 'HISTORY_HOLDER');
            self::need(R::same($p['target']['terms']['founding'], $founding), 'HISTORY_FOUNDING_ORIGINALS');
            self::need($p['target']['terms']['formation_trust_fingerprint'] === ($state['trust']['fingerprint'] ?? null)
                && $p['target']['terms']['expected_head']['generation'] > $extension['initialization']['terms']['expected_head']['generation']
                && $p['target']['terms']['expected_head']['generation'] <= $p['expected_head']['generation'], 'HISTORY_TARGET_CONTEXT');
            $reservation = $extension['reservation'];
            self::need($reservation === null || $p['expected_head']['generation'] !== $reservation['terms']['expected_head']['generation'], 'HISTORY_OWNER_ORDER');
            if ($reservation !== null && $p['expected_head']['generation'] > $reservation['terms']['expected_head']['generation']) {
                // Revocations published after consumption must retain that consumed original.
                // An earlier revoked proposal may coexist with a subsequently authorized different proposal.
                self::need(R::same($p['target'], ['terms' => $reservation['terms'], 'operator' => $reservation['operator']]), 'HISTORY_RESERVED_TARGET');
            }
            $ordered[$r['sequence']] = $r;
        }
        ksort($ordered); $previous = null;
        foreach (array_values($ordered) as $index => $r) {
            $p = $r['envelope']['payload'];
            self::need($r['sequence'] === $index + 1 && $p['adapter_from'] === ($index === 0 ? FormationFreshEstablishment::STATE : self::STATE), 'ADAPTER_HISTORY');
            self::need($previous === null || $p['expected_head']['generation'] > $previous, 'HEAD_HISTORY');
            $previous = $p['expected_head']['generation'];
        }
        return $extension['revocations'];
    }

    /** Retained original joins, deliberately without renewed expiry/revocation competence. */
    private static function foundingOriginals(array $state): array
    {
        $s = $state['onboarding']; self::need(count($s['bindings']) === 1, 'HISTORY_HOLDER');
        $holder = R::record(array_values($s['bindings'])[0]['record']);
        $load = static function (array $ref) use ($s): array {
            $key = R::key($ref); $entry = $s['evidence'][$key] ?? $s['policies'][$key] ?? null;
            self::need(is_array($entry) && is_string($entry['raw'] ?? null), 'HISTORY_FOUNDING_ORIGINALS');
            $record = R::record($entry['record']);
            self::need(R::same(R::reference($record), $ref) && R::same(StrictJson::decode($entry['raw']), $record), 'HISTORY_FOUNDING_ORIGINALS');
            return $record;
        };
        $policy = $load($holder['body']['policy_ref']);
        return ['policy' => $policy, 'constitution' => $load($holder['body']['constitution_ref']),
            'command' => LedgerState::command($s, $holder['body']['command_ref']),
            'completion' => LedgerState::step($s, $policy, 'found-augur')['completion'], 'holder' => $holder];
    }

    public static function assertNotRevoked(array $state, array $target): void
    {
        $ref = self::target($target, $state['onboarding']['trust']['body']);
        foreach (self::history($state) as $r) {
            self::need(!R::same($ref, $r['envelope']['payload']['target_ref']), 'TARGET_REVOKED');
        }
    }
    private static function shape(array $v, array $keys): void { self::need(FormationJournal::keys($v, $keys), 'SHAPE'); }
    private static function need(bool $ok, string $code): void { if (!$ok) { throw new \RuntimeException('PPC8_REVOCATION_'.$code); } }
}
