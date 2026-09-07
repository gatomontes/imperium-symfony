<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationSignatures, LegacyFormationGuard};
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\SystemClock;

/** Explicit synthetic historical inventories for legacy mechanics tests only.
 * No production fixture exemption, environment switch, private key file or CLI.
 */
final class HistoricalCuriaFixture
{
    private static array $keys = [];

    public static function admit(string $root, array $record, string $kind): void
    {
        $journal = new FormationJournal($root);
        if (!isset(self::$keys[$root])) {
            $pair = sodium_crypto_sign_keypair();
            self::$keys[$root] = sodium_crypto_sign_secretkey($pair);
            $public = sodium_crypto_sign_publickey($pair);
            (new FormationSignatures($journal, new SystemClock()))->enrollPublicTrust([
                'public_key' => base64_encode($public), 'not_before' => time() - 5, 'expires_at' => time() + 86400,
            ], hash('sha256', $public));
        }
        $state = $journal->read()['state'];
        $inventory = ['source' => 'Explicit synthetic historical evidence; not a live installation or fresh production approval.',
            'records' => [['kind' => $kind, 'digest' => FormationJournal::digest($record)]]];
        $payload = ['schema' => 'imperium.citadel-owner-decision/v1', 'citadel_id' => $state['citadel_id'],
            'trust_fingerprint' => $state['trust']['fingerprint'], 'effect' => 'REGISTER_HISTORICAL_FORMATION_EVIDENCE',
            'object_digest' => FormationJournal::digest($inventory), 'issued_at' => time(), 'expires_at' => time() + 3600, 'nonce' => bin2hex(random_bytes(24))];
        (new LegacyFormationGuard($root))->registerHistoricalInventory($inventory,
            ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), self::$keys[$root]))]);
    }

    public static function persist(ProceedingStore $store, array $proceeding): array
    {
        self::admit(self::root($store), $proceeding, 'proceeding');
        return $store->persist($proceeding);
    }

    public static function appendTurn(ProceedingStore $store, string $id, string $responseId, int $sequence, array $turn): array
    {
        $record = $turn;
        $record['sequence'] = $sequence;
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        self::admit(self::root($store), $record, 'turn');
        return $store->appendTurn($id, $responseId, $sequence, $turn);
    }

    private static function root(ProceedingStore $store): string
    {
        $directory = (new \ReflectionProperty(ProceedingStore::class, 'directory'))->getValue($store);
        return substr($directory, 0, -strlen('/var/imperium/curia/proceedings'));
    }
}
