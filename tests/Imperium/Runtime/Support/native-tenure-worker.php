<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationOwnerFrame, FormationPersonnel, FormationInstitution, FormationSignatures};
use App\Imperium\Runtime\Citadel\NativeAuthority\{NativeJournal, NativeTrust};
use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture, SyntheticFormationClock};

class_exists(CitadelFormationFixture::class);
[$script, $root, $inputFile, $channel] = $argv;
$input = json_decode((string) file_get_contents($inputFile), true, 512, JSON_THROW_ON_ERROR);
$clock = new SyntheticFormationClock(); $clock->at = $input['at'];
$barrier = static function (string $point) use ($input, $channel): void {
    if (($input['pause'] ?? null) !== $point) { return; }
    file_put_contents($channel.'.held', $point);
    $deadline = microtime(true) + 20;
    while (!file_exists($channel.'.release')) {
        if (microtime(true) > $deadline) { throw new RuntimeException('TEST_BARRIER_TIMEOUT'); }
        usleep(1000);
    }
};
file_put_contents($channel.'.attempt', $input['operation']);
try {
    $journal = new J($root);
    if ($input['operation'] === 'enroll') {
        (new NativeTrust(new NativeJournal($root, $barrier), $clock))->enroll($input['policy'], hash('sha256', base64_decode($input['policy']['public_key'])));
    } elseif ($input['operation'] === 'install') {
        (new OperatorRootPersonnelInstallationService($root, $barrier))->install($input['package']);
    } elseif (in_array($input['operation'], ['v0', 'state-required', 'state-direct'], true)) {
        $installer = new OperatorRootPersonnelInstallationService($root, $barrier);
        $required = new \App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService($installer);
        $store = new \App\Bootstrap\StateStore(($input['foreign_bootstrap'] ?? false) ? $root.'/foreign' : $root);
        if ($input['operation'] === 'v0') {
            $v0 = new \App\Imperium\Runtime\Bootstrap\V0ActivationService($required,
                new \App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService($root),
                new \App\Imperium\Runtime\Bootstrap\OperatorRootUpgradePlanningService($root), $store);
            (new \App\Bootstrap\MasterMason($store, $v0))->activate('synthetic-v0');
        } elseif ($input['operation'] === 'state-required') {
            $store->locked(fn() => $required->install('synthetic-v0'));
        } else {
            $store->locked(fn() => $installer->install($input['package']));
        }
    } elseif ($input['operation'] === 'candidate' || $input['operation'] === 'holder') {
        $personnel = new FormationPersonnel($journal, new FormationSignatures($journal, $clock), $clock, new FormationInstitution($root));
        $journal->inspect(static function (array $frame, FormationOwnerFrame $owner) use ($personnel, $input, $barrier, $channel): void {
            $barrier('current-owner');
            $result = $input['operation'] === 'holder'
                ? ($input['seat'] === 'courtyard.courtthane' ? $personnel->currentCourtthaneInOwner($owner) : $personnel->currentLocksmithInOwner($owner))
                : $personnel->candidateInOwner($owner, $frame['state'], $input['candidate'], $frame['state']['citadel_id'], $input['seat']);
            // This local completed observation is historical evidence only.
            file_put_contents($channel.'.observed', J::digest($result));
        });
    } else { throw new RuntimeException('TEST_UNKNOWN_OPERATION'); }
    echo "COMPLETED_OFFLINE\n";
    exit(0);
} catch (Throwable $e) {
    echo $e->getMessage()."\n";
    exit(1);
}
