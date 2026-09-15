<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FormationInstitution, FormationOwnerFrame, FreshInstitutionPackage};
use PHPUnit\Framework\TestCase;
abstract class FreshEstablishmentCrashCase extends TestCase
{
    protected function proveCrash(string $operation, string $point, ?int $placement = null): void
    {
        $f = new FreshEstablishmentFixture(false); $processes = new FreshEstablishmentProcesses($f);
        try {
            if ($operation === 'complete') { $f->reserve(); }
            if ($placement !== null) { $point .= ':'.array_keys(FreshInstitutionPackage::layout($f->root, $f->reservation)['files'])[$placement]; }
            $child = $processes->start($operation, ['crash' => $point]); $result = $processes->finish($child);
            self::assertSame(73, $result['native_exit'], $result['stderr']);
            $state = $f->journal->read()['state'];
            if ($operation === 'complete' && $point === 'after-journal-rename') {
                self::assertNotNull($state['fresh_institutions']['completion']);
            } else {
                self::assertNull($state['fresh_institutions']['completion']);
                foreach (FormationInstitution::SEATS as $role => $seat) {
                    $refused = false;
                    try { (new FormationInstitution($f->root))->actor($role); }
                    catch (\RuntimeException $e) { $refused = true; self::assertNotSame('', $e->getMessage()); }
                    self::assertTrue($refused, 'Incomplete authority: '.$seat);
                }
            }
            // A new process resumes only the exact reserved bytes with fresh real owners.
            if ($operation === 'reserve') {
                $f->reservation = $f->reserve();
                self::assertSame($f->reservation, $f->journal->read()['state']['fresh_institutions']['reservation']);
            }
            $recovery = $processes->finish($processes->start('complete'));
            self::assertSame(0, $recovery['native_exit'], $recovery['stderr']);
            $complete = $f->journal->read()['state']['fresh_institutions']['completion'];
            self::assertSame(P::reference($f->reservation), $complete['reservation_ref']);
            self::assertCount(1, $f->journal->read()['state']['onboarding']['bindings']);
            $f->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($f): void {
                foreach (FormationInstitution::SEATS as $role => $seat) { self::assertSame($seat, (new FormationInstitution($f->root))->actorInOwner($owner, $role)['seat']); }
            });
            $head = $f->head(); self::assertSame($complete, $f->protocol->complete(P::reference($f->reservation))); self::assertSame($head, $f->head());
        } finally { $processes->close(); $f->close(); }
    }
}
