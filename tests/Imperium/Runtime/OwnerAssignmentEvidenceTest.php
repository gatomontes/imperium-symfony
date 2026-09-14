<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Onboarding\Assignment\{OwnerAssignmentEvidence,AssignmentEvidence,PersistentSettings,AssignmentRule};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Citadel\Formation\FormationOwnerFrame;
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use App\Tests\Imperium\Runtime\Support\AssignmentFixture;
use PHPUnit\Framework\TestCase;

final class OwnerAssignmentEvidenceTest extends TestCase
{
    public function testActualApplicationAndRoleResolutionReceiveLiveOwnerAndWholePair():void
    {
        $f=new AssignmentFixture();
        try {
            $f->assessed(); $d=$f->fresh->d; $store=$d->f->store;
            $evidence=new class($f->evidence(),$store) implements OwnerAssignmentEvidence {
                public int $calls=0; public bool $refuseLocksmith=false; public ?FormationOwnerFrame $retained=null;
                public function __construct(private AssignmentEvidence $generic,private AuthorityStore $expected) {}
                public function verify(array $p,array $a,array $o,array $r):void { throw new \RuntimeException('DETACHED_SPY_CALL'); }
                public function verifyInOwner(AuthorityStore $store,FormationOwnerFrame $owner,array $p,array $a,array $o,array $r):void {
                    TestCase::assertSame($this->expected,$store); $owner->assertOwner($store->journal);
                    TestCase::assertSame(AssignmentRule::ROLES,array_column($a,'role')); $this->retained=$owner; ++$this->calls;
                    if($this->refuseLocksmith) { throw new \RuntimeException('INVALID_LOCKSMITH_IN_WHOLE_PAIR'); }
                    $this->generic->verify($p,$a,$o,$r);
                }
            };
            $ledger=new CommandLedger($store,$f->adapter,$f->adapter,$f->fresh->founding(),$evidence);
            $ledger->advance($d->f::json($d->request('apply-assignments'))); self::assertSame(1,$evidence->calls);
            $settings=new PersistentSettings($store,$f->adapter,$evidence); $snapshot=$settings->snapshot();
            self::assertEquals($snapshot['assignments'][0],$settings->resolve(AssignmentRule::ROLES[0])); self::assertSame(2,$evidence->calls);
            try { $evidence->retained->assertOwner($store->journal); self::fail('Expired owner'); } catch(\RuntimeException $e) { self::assertStringContainsString('LIVE_SAME_ROOT_OWNER',$e->getMessage()); }
            $evidence->refuseLocksmith=true;
            try { $settings->resolve(AssignmentRule::ROLES[0]); self::fail('Whole pair must refuse'); } catch(\RuntimeException $e) { self::assertSame('INVALID_LOCKSMITH_IN_WHOLE_PAIR',$e->getMessage()); }
            self::assertEquals($snapshot,$settings->snapshot());
        } finally { $f->close(); }
    }
}
