<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Bootstrap\{OperatorRootPersonnelInstallationService, RequiredV0PersonnelInstallationService};
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationOwnerFrame, FormationInstitution};
use App\Imperium\Runtime\Citadel\NativeAuthority\{NativeBoundary, NativeJournal, NativeTrust};
use App\Tests\Imperium\Runtime\Support\ModelBoundFormationFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeTenureSynchronizationTest extends TestCase
{
    private array $processes = [];

    private function start(string $root, array $input, string $name): array
    {
        $channel = $root.'/'.$name;
        file_put_contents($channel.'.json', json_encode($input, JSON_THROW_ON_ERROR));
        $p = proc_open([PHP_BINARY, __DIR__.'/Support/native-tenure-worker.php', $root, $channel.'.json', $channel],
            [1 => ['file', $channel.'.out', 'w'], 2 => ['file', $channel.'.err', 'w']], $pipes);
        self::assertIsResource($p);
        $this->processes[] = $p;
        $this->waitFile($channel.'.attempt');
        return [$p, $channel];
    }

    private function waitFile(string $path): void
    {
        $deadline = microtime(true) + 20;
        while (!is_file($path) && microtime(true) < $deadline) { usleep(1000); clearstatcache(true, $path); }
        self::assertFileExists($path, 'Controlled process checkpoint must be reached');
    }

    private function finish(array $worker): array
    {
        [$p, $channel] = $worker;
        $deadline = microtime(true) + 20;
        do { $status = proc_get_status($p); if (!$status['running']) { break; } usleep(1000); } while (microtime(true) < $deadline);
        self::assertFalse($status['running'], 'Bounded entry point must not deadlock');
        $exit = $status['exitcode'];
        $close = proc_close($p);
        return [$exit < 0 ? $close : $exit, file_get_contents($channel.'.out').file_get_contents($channel.'.err')];
    }

    protected function tearDown(): void
    {
        foreach ($this->processes as $p) { if (is_resource($p)) { proc_terminate($p); proc_close($p); } }
    }

    private function policy(ModelBoundFormationFixture $x): array
    {
        $pair = sodium_crypto_sign_keypair();
        $public = base64_encode(sodium_crypto_sign_publickey($pair)); sodium_memzero($pair);
        return ['schema' => 'imperium.native-authority-enrollment/v1', 'domain' => NativeTrust::DOMAIN,
            'instance_id' => $x->f->journal->read()['state']['parent_instance_id'], 'public_key' => $public,
            'issuer_role' => NativeTrust::ROLE, 'effects' => NativeTrust::EFFECTS,
            'not_before' => $x->f->clock->at - 10, 'expires_at' => $x->f->clock->at + 7200, 'writer_boundary' => NativeTrust::BOUNDARY];
    }

    private function current(ModelBoundFormationFixture $x): array
    {
        return ['operation' => 'candidate', 'at' => $x->f->clock->at, 'candidate' => $x->candidate, 'seat' => $x->seat];
    }

    public static function orders(): iterable { yield 'native-first' => [true]; yield 'current-first' => [false]; }

    #[DataProvider('orders')]
    public function testRegistryAndAuthenticCurrentConsumerBothProcessOrders(bool $nativeFirst): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $native = ['operation' => 'enroll', 'at' => $x->f->clock->at, 'policy' => $this->policy($x)];
            $first = $this->start($x->f->root, $nativeFirst ? [...$native, 'pause' => 'before-commit'] : [...$this->current($x), 'pause' => 'current-owner'], 'first');
            $this->waitFile($first[1].'.held');
            // An independent nonblocking probe proves the actual Formation fence
            // is held, rather than inferring acquisition from elapsed sleep.
            $h = fopen($x->f->root.'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock', 'rb');
            self::assertFalse(flock($h, LOCK_EX | LOCK_NB)); fclose($h);
            $second = $this->start($x->f->root, $nativeFirst ? $this->current($x) : $native, 'second');
            self::assertTrue(proc_get_status($second[0])['running']);
            self::assertFileDoesNotExist($second[1].'.observed');
            file_put_contents($first[1].'.release', 'release');
            self::assertSame([0, "COMPLETED_OFFLINE\n"], $this->finish($first));
            [$exit, $output] = $this->finish($second);
            self::assertSame($nativeFirst ? 1 : 0, $exit);
            self::assertSame($nativeFirst ? "CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED\n" : "COMPLETED_OFFLINE\n", $output);
            $later = $this->start($x->f->root, $this->current($x), 'later');
            self::assertSame([1, "CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED\n"], $this->finish($later));
            if (!$nativeFirst) { self::assertFileExists($first[1].'.observed'); }
        } finally { $x->close(); }
    }

    public function testLiveFrameRejectsWrongRootEscapeAndExceptionThenReleases(): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $escaped = null;
            try {
                $x->f->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($x, &$escaped): void {
                    $escaped = $owner;
                    try { $owner->assertOwner(new J($x->f->root.'/foreign')); self::fail('Foreign owner accepted'); }
                    catch (\RuntimeException $e) { self::assertSame('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED', $e->getMessage()); }
                    try { serialize($owner); self::fail('Serialized live capability'); }
                    catch (\RuntimeException $e) { self::assertSame('PPC301_OWNER_FRAME_NOT_SERIALIZABLE', $e->getMessage()); }
                    throw new \RuntimeException('CONTROLLED_EXCEPTION');
                });
            } catch (\RuntimeException $e) { self::assertSame('CONTROLLED_EXCEPTION', $e->getMessage()); }
            try { $escaped->frame(); self::fail('Escaped frame remained current'); }
            catch (\RuntimeException $e) { self::assertSame('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED', $e->getMessage()); }
            self::assertSame([0, "COMPLETED_OFFLINE\n"], $this->finish($this->start($x->f->root, $this->current($x), 'after-exception')));
        } finally { $x->close(); }
    }

    public static function seats(): iterable { yield ['courtyard.courtthane']; yield ['clavium.locksmith']; }

    public static function unsupportedForms(): iterable
    {
        yield 'unknown-bootstrap' => ['bootstrap'];
        yield 'unproven-old-package' => ['package'];
        yield 'unknown-successor-status' => ['successor'];
    }

    #[DataProvider('unsupportedForms')]
    public function testUnsupportedFormsCannotUseUnchangedOldOccupancy(string $form): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            if ($form === 'bootstrap') {
                (new StateStore($x->f->root))->write(['schema' => 'synthetic-unsupported-successor', 'events' => []]);
            } elseif ($form === 'successor') {
                $atomic = new \App\Imperium\Runtime\Persistence\AtomicTransition($x->f->root);
                $x->f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner) =>
                    (new \App\Imperium\Runtime\Persistence\ImmutableRecordStore($x->f->root, $atomic))->putInOwner($owner, 'var/imperium/offices/garrison/occupancy', 'synthetic-successor',
                        ['schema' => 'synthetic-unsupported-successor/v1', 'seat' => 'garrison.constable', 'status' => 'CURRENT_ACTIVE']));
            } else {
                $paths = glob($x->f->root.'/var/imperium/operator-root/packages/*.json');
                self::assertCount(1, $paths);
                unlink($paths[0]); // Adverse old-state fixture, never completion migration.
            }
            self::assertSame([1, $form !== 'package' ? "CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED\n" : "PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED\n"],
                $this->finish($this->start($x->f->root, $this->current($x), 'unsupported')));
        } finally { $x->close(); }
    }

    public function testGenericStorageRefusesBeforeGuardAndOwnedRetirementRefusesCurrentUse(): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $root = $x->f->root;
            $record = (new FormationInstitution($root))->witness('garrison')['occupancy'];
            $path = 'var/imperium/offices/garrison/occupancy/'.$record['binding_id'].'.json';
            $next = $record; $next['status'] = 'RETIRED';
            $atomic = new \App\Imperium\Runtime\Persistence\AtomicTransition($root);
            $store = new \App\Imperium\Runtime\Persistence\MutableStateStore($root, $atomic);
            try {
                $store->compareAndSwapGuarded($path, $record['record_digest'], static function (): void { self::fail('Bare reserved guard invoked'); }, $next);
                self::fail('Bare reserved write accepted');
            } catch (\RuntimeException $e) { self::assertSame('PPC401_RESERVED_STORAGE_OWNER_REQUIRED', $e->getMessage()); }
            self::assertSame($record, $store->read($path));
            $x->f->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($store, $path, $record, $next, $root): void {
                $store->compareAndSwapGuardedInOwner($owner, $path, $record['record_digest'], function () use ($root): void {
                    $h = fopen($root.'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock', 'rb');
                    self::assertFalse(flock($h, LOCK_EX | LOCK_NB)); fclose($h);
                }, $next);
            });
            self::assertSame([1, "CMF121_INSTITUTION_UNAVAILABLE\n"], $this->finish($this->start($root, $this->current($x), 'generic-refusal')));
        } finally { $x->close(); }
    }

    #[DataProvider('seats')]
    public function testRealIndependentAppointmentCurrentConsumerAndHistoricalReplay(string $seat): void
    {
        $x = new ModelBoundFormationFixture($seat);
        try {
            $state = $x->f->journal->read()['state'];
            $effect = $seat === 'courtyard.courtthane' ? 'APPOINT_COURTTHANE' : 'APPOINT_FORMATION_LOCKSMITH';
            $terms = ['candidate' => $x->candidate, 'scope' => $state['citadel_id'], 'seat' => $seat, 'generation' => 1];
            $decision = $x->f->sign($effect, $terms);
            $appoint = fn() => $seat === 'courtyard.courtthane' ? $x->f->personnel->appointCourtthane($x->candidate, $decision) : $x->f->personnel->appointLocksmith($x->candidate, $decision);
            $holder = $appoint();
            self::assertSame('synthetic-unverified-model-authorization', $holder['profile_artifact']['model_binding']['authorization_id']);
            $input = [...$this->current($x), 'operation' => 'holder'];
            self::assertSame([0, "COMPLETED_OFFLINE\n"], $this->finish($this->start($x->f->root, $input, 'holder-positive')));
            $policy = $this->policy($x);
            (new NativeTrust(new NativeJournal($x->f->root), $x->f->clock))->enroll($policy, hash('sha256', base64_decode($policy['public_key'])));
            $before = $x->f->journal->read();
            try { $x->f->personnel->recordModelBoundProfile($x->envelope); self::fail('Stale model-bound ingress accepted'); }
            catch (\RuntimeException $e) { self::assertSame('CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED', $e->getMessage()); }
            self::assertSame($holder, $appoint(), 'Completed exact appointment recognition does not renew tenure');
            self::assertSame($before, $x->f->journal->read());
            self::assertSame([1, "CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED\n"], $this->finish($this->start($x->f->root, $input, 'holder-refused')));
        } finally { $x->close(); }
    }

    public function testPinnedStateStoreNestedRequiredInstallerUsesActualOwner(): void
    {
        $root = sys_get_temp_dir().'/imperium-tenure-nested-'.bin2hex(random_bytes(12)); mkdir($root);
        try {
            $store = new StateStore($root);
            $result = $store->locked(fn() => (new RequiredV0PersonnelInstallationService(new OperatorRootPersonnelInstallationService($root)))->install('synthetic-tenure'));
            self::assertSame('ALL_REQUIRED_V0_SEATS_OCCUPIED_PRE_OPERATIONAL', $result['status']);
            NativeBoundary::lock($root, function (FormationOwnerFrame $owner) use ($root): void {
                $owner->assertOwner(new J($root));
                self::assertSame([], (new NativeJournal($root))->inspect(fn(array $frame) => $frame['state']));
            });
        } finally { $this->remove($root); }
    }

    public static function interruptionPoints(): iterable
    {
        foreach (['package-intent', 'installation-written', 'placement-written', 'package-placements'] as $point) { yield $point => [$point]; }
    }

    public static function indirectRoutes(): iterable { yield ['v0']; yield ['state-required']; }

    public function testV0CannotConsumeAForeignInjectedBootstrapOwner(): void
    {
        $root = sys_get_temp_dir().'/imperium-tenure-foreign-'.bin2hex(random_bytes(12)); mkdir($root);
        try {
            self::assertSame([1, "PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED\n"], $this->finish($this->start($root,
                ['operation' => 'v0', 'at' => 1, 'foreign_bootstrap' => true], 'foreign-owner')));
            self::assertDirectoryDoesNotExist($root.'/foreign');
            self::assertDirectoryDoesNotExist($root.'/var/imperium/operator-root/installations');
        } finally { $this->remove($root); }
    }

    #[DataProvider('indirectRoutes')]
    public function testIndirectProductionEntryPointsInBoundedFreshProcess(string $route): void
    {
        $root = sys_get_temp_dir().'/imperium-tenure-v0-'.bin2hex(random_bytes(12)); mkdir($root);
        try {
            $worker = $this->start($root, ['operation' => $route, 'at' => 1, 'pause' => 'package-intent'], 'indirect');
            $this->waitFile($worker[1].'.held');
            $h = fopen($root.'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock', 'rb');
            self::assertFalse(flock($h, LOCK_EX | LOCK_NB)); fclose($h);
            file_put_contents($worker[1].'.release', 'release');
            self::assertSame([0, "COMPLETED_OFFLINE\n"], $this->finish($worker));
            self::assertSame([0, "COMPLETED_OFFLINE\n"], $this->finish($this->start($root, ['operation' => $route, 'at' => 1], 'replay')));
        } finally { $this->remove($root); }
    }

    #[DataProvider('interruptionPoints')]
    public function testInterruptedNativePackageNeverAuthenticatesSubset(string $point): void
    {
        $root = sys_get_temp_dir().'/imperium-tenure-partial-'.bin2hex(random_bytes(12)); mkdir($root);
        $personnel = [];
        foreach (FormationInstitution::SEATS as $seat) {
            [$office, $role] = explode('.', $seat, 2);
            $member = ['personnel_type' => 'OFFICER', 'office' => $office, 'role' => $role, 'seat' => $seat];
            foreach (['persona', 'profile', 'officer'] as $kind) { $member[$kind] = ['id' => 'synthetic-'.$seat.'-'.$kind, 'version' => '1']; }
            $personnel[] = $member;
        }
        $package = ['schema' => 'imperium.operator-root-personnel-package/v2', 'instance_id' => 'synthetic-partial', 'personnel' => $personnel];
        try {
            $worker = $this->start($root, ['operation' => 'install', 'at' => 1, 'package' => $package, 'pause' => $point], 'writer');
            $this->waitFile($worker[1].'.held');
            proc_terminate($worker[0]);
            [$exit] = $this->finish($worker); self::assertNotSame(0, $exit);
            // A new lock acquisition after termination must succeed and refuse
            // even if all individual placements happened before interruption.
            (new J($root))->inspect(function (array $frame, FormationOwnerFrame $owner) use ($root): void {
                foreach (array_keys(FormationInstitution::SEATS) as $role) {
                    try { (new FormationInstitution($root))->actorInOwner($owner, $role); self::fail('Partial package accepted'); }
                    catch (\RuntimeException $e) { self::assertContains($e->getMessage(), ['CMF121_INSTITUTION_UNAVAILABLE', 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED']); }
                }
            });
            try { (new OperatorRootPersonnelInstallationService($root))->install($package); self::fail('Unknown completion recovered'); }
            catch (\RuntimeException $e) { self::assertSame('B210_OPERATOR_ROOT_PARTIAL_INSTALLATION', $e->getMessage()); }
        } finally { $this->remove($root); }
    }

    private function remove(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($root);
    }
}
