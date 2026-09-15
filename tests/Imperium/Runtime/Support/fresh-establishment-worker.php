<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';
require __DIR__.'/ModelPreparationPublicationBarrier.php';
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationFreshEstablishment as P, FormationSignatures, FormationOwnerFrame};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Onboarding\Augur\BaseProjection;
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentClock, SyntheticAugurBaseEvidence};
$input = json_decode(file_get_contents($argv[1]), true, 64, JSON_THROW_ON_ERROR);
$clock = new FreshEstablishmentClock(); $clock->at = $input['now'];
$journal = new FormationJournal($input['root']);
$store = new AuthorityStore($journal, $clock, ...$input['identities']);
$log = static function (string $point) use ($input): void {
    file_put_contents($input['events'], json_encode(['point' => $point, 'pid' => getmypid(), 'utc' => gmdate('Y-m-d\TH:i:s\Z')])."\n", FILE_APPEND);
};
$checkpoint = static function (string $point) use ($input, $log): void {
    $log($point);
    if (($input['pause'] ?? '') === $point) {
        file_put_contents($input['ready'], $point); $deadline = microtime(true) + 45;
        while (!is_file($input['release'])) { if (microtime(true) >= $deadline) { throw new RuntimeException('PPC7_TEST_BARRIER_TIMEOUT'); } usleep(1000); }
    }
    if (($input['crash'] ?? '') === $point) { exit(73); }
};
$GLOBALS['ppc5_publication_barrier'] = static function (string $when, string $path) use ($checkpoint): void {
    if (str_contains(str_replace('\\', '/', $path), '/citadel/formation/') && str_ends_with($path, '.json')) { $checkpoint($when.'-journal-rename'); }
};
$protocol = new P($input['root'], $store, new BaseProjection(new SyntheticAugurBaseEvidence($input['pins'])), $checkpoint);
try {
    $log('started');
    $result = match ($input['operation']) {
        'reserve' => $protocol->reserve($input['terms'], $input['operator'], $input['formation']),
        'complete' => $protocol->complete($input['reference']),
        'revoke' => (function () use ($input, $journal, $clock, $checkpoint): array {
            // Pause at the real journal publication, while Formation ownership is held.
            (new FormationSignatures($journal, $clock))->revoke($input['revocation'], $input['nonce']); return ['revoked' => true];
        })(),
        'generic', 'seal' => \App\Imperium\Runtime\Citadel\NativeAuthority\NativeBoundary::lock($input['root'], static function () use ($input, $store, $checkpoint): array {
            $checkpoint('native-owner-acquired');
            return $input['operation'] === 'generic'
                ? (new \App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService($input['root']))->install($input['terms']['package'])
                : (new \App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService($input['root']))->seal($store->instance);
        }),
        'native-enroll' => (new \App\Imperium\Runtime\Citadel\NativeAuthority\NativeTrust(new \App\Imperium\Runtime\Citadel\NativeAuthority\NativeJournal($input['root'], static fn(string $point) => $checkpoint('native-'.$point)), $clock))->enroll($input['native_policy'], $input['fingerprint']),
        default => throw new RuntimeException('PPC7_TEST_OPERATION'),
    };
    echo json_encode(['result' => $result], JSON_THROW_ON_ERROR); $log('returned'); exit(0);
} catch (Throwable $e) { $log('refused:'.$e->getMessage()); fwrite(STDERR, $e::class.': '.$e->getMessage()); exit(1); }
