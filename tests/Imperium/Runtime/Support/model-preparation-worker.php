<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationOwnerFrame as Owner, FormationPersonnel, FormationInstitution, FormationSignatures, FormationModelPreparation};
use App\Imperium\Runtime\Persistence\{AtomicTransition, MutableStateStore};
use App\Imperium\Runtime\Clock;
[$script, $root, $input, $channel] = $argv;
$in = json_decode(file_get_contents($input), true, 512, JSON_THROW_ON_ERROR);
if (isset($in['publication_phase'])) {
    require __DIR__.'/ModelPreparationPublicationBarrier.php';
    $GLOBALS['ppc5_publication_barrier'] = static function (string $phase, string $to) use ($in, $root, $channel): void {
        if ($phase !== $in['publication_phase'] || !str_starts_with(str_replace('\\', '/', $to), str_replace('\\', '/', $root).'/var/imperium/citadel/formation/')) { return; }
        file_put_contents($channel.'.held', $phase); $end = microtime(true) + 30;
        while (!file_exists($channel.'.release')) {
            if (microtime(true) > $end) { throw new RuntimeException('PUBLICATION_BARRIER_TIMEOUT'); }
            usleep(1000); clearstatcache(true, $channel.'.release');
        }
    };
}
$barrier = static function () use ($in, $channel): void {
    if (!($in['pause'] ?? false)) { return; }
    file_put_contents($channel.'.held', 'held'); $end = microtime(true) + 30;
    while (!file_exists($channel.'.release')) {
        if (microtime(true) > $end) { throw new RuntimeException('BARRIER_TIMEOUT'); }
        usleep(1000); clearstatcache(true, $channel.'.release');
    }
    if ($in['exception'] ?? false) { throw new RuntimeException('CONTROLLED_EXCEPTION'); }
};
$clock = new class($in['at'], $barrier) implements Clock {
    private bool $observed = false;
    public function __construct(private int $at, private Closure $barrier) {}
    public function now(): DateTimeImmutable {
        if (!$this->observed) { $this->observed = true; ($this->barrier)(); }
        return new DateTimeImmutable('@'.$this->at);
    }
};
$j = new J($root); $signatures = new FormationSignatures($j, $clock); $institution = new FormationInstitution($root);
$preparation = new FormationModelPreparation($j, $signatures, $clock, $institution);
file_put_contents($channel.'.attempt', $in['mode']);
try {
    if ($in['mode'] === 'seal') {
        $result = $preparation->seal($in['envelope']);
    } elseif ($in['mode'] === 'current') {
        $personnel = new FormationPersonnel($j, $signatures, $clock, $institution);
        $result = $j->inspect(fn(array $frame, Owner $owner) => $personnel->authorizedModelCandidateInOwner($owner, $in['candidate'], $frame['state']['citadel_id'], $in['seat']));
    } elseif ($in['mode'] === 'revoke') {
        $signatures->revoke($in['decision'], $in['nonce']); $result = ['revoked' => true];
    } elseif ($in['mode'] === 'retire') {
        $m = new MutableStateStore($root, new AtomicTransition($root));
        $result = Owner::run($root, fn(Owner $owner) => $m->compareAndSwapGuardedInOwner($owner, $in['path'], $in['digest'], $barrier, $in['record']));
    } else { throw new RuntimeException('UNKNOWN_MODE'); }
    file_put_contents($channel.'.result', json_encode($result, JSON_THROW_ON_ERROR));
    echo "OK\n"; exit(0);
} catch (Throwable $e) { echo $e->getMessage()."\n"; exit(1); }
