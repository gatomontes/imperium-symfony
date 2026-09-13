<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission,Rules as R};
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

/** Executable incompatibility witness, not implementation of the missing port. */
final class InstitutionalAssignmentContractTest extends TestCase
{
    #[DataProvider('effects')]
    public function testRealSignatureCannotAdmitUnsupportedInstitutionalCompetence(string $effect):void
    {
        $f=new F();try{
            $f->enroll();[$policy]=$f->admitPolicy();$source=$f->source('public-original','Synthetic Profile original');
            $terms=$f->store->make('imperium.bootstrap-proposed-terms/v1','institutional-terms',[
                'effect'=>$effect,'terms'=>$source,'required_completed_refs'=>[]],[$source]);
            $act=$f->sign($terms,$effect,R::reference($policy));$head=$f->head();
            self::assertTrue(sodium_crypto_sign_verify_detached(base64_decode($act['signature']),$f::json($act['payload']),$f->public));
            try{(new Admission($f->store))->retain($f::json($act),$f::json($terms),[$f::json($f->sources[R::key($source)])]);self::fail('Frozen issuer scope widened');}
            catch(\RuntimeException $e){self::assertSame('O2_UNSUPPORTED_ISSUER_SOURCE',$e->getMessage());}
            self::assertSame($head,$f->head());
        }finally{$f->close();}
    }
    public static function effects():iterable
    {
        foreach(['APPROVE_STANDING_AUGUR_PROFILE','DESIGNATE_STANDING_AUGUR_PROFILE','QUALIFY_BIND_AUGUR'] as $effect){yield [$effect];}
    }
}
