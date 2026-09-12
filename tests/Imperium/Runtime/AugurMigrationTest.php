<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use App\Imperium\Runtime\Onboarding\Augur\AugurMigration;
use App\Imperium\Runtime\Onboarding\Ledger\{StateMigration,LedgerState};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use PHPUnit\Framework\TestCase;
final class AugurMigrationTest extends TestCase
{
    public function testExplicitMigrationPreservesOriginalsAndNeverDowngrades():void
    {
        $f=new F();try{$f->enroll();$old=(new StateMigration($f->store))->migrate($f->head());$before=$f->store->journal->read()['state']['onboarding'];
            $receipt=(new AugurMigration($f->store))->migrate($f->head());$head=$f->head();$s=$f->store->journal->read()['state']['onboarding'];
            self::assertSame($before,$receipt['body']['prior_state']);self::assertSame(R::hash($before),$receipt['body']['prior_digest']);LedgerState::validate($s);
            self::assertSame($old,(new StateMigration($f->store))->migrate($head));self::assertSame($receipt,(new AugurMigration($f->store))->migrate($head));self::assertSame($head,$f->head());
            $s['augur_migration']['body']['prior_state']['evidence']=[];$s['augur_migration']['body']['prior_digest']=R::hash($s['augur_migration']['body']['prior_state']);unset($s['augur_migration']['record_digest']);$s['augur_migration']=R::seal($s['augur_migration']);
            try{LedgerState::validate($s);self::fail('A rehashed invented predecessor is not valid history');}catch(\RuntimeException $e){self::assertStringStartsWith('O2_',$e->getMessage());}
        }finally{$f->close();}
    }
}
