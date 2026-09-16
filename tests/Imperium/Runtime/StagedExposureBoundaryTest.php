<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\SharedExposure;
use App\Tests\Imperium\Runtime\Support\{OnboardingLedgerFixture as F,NativeAssignmentProof};
use PHPUnit\Framework\TestCase;

/** Source-dispatch probe only. Never publishes or claims a valid v5 migration. */
final class StagedExposureBoundaryTest extends TestCase
{
    public function testUnchangedFormationExposureGateOmitsV5BeforeAllChecks(): void
    {
        $f=new F();
        try {
            $f->ready(); $store=$f->f->store; $frame=$store->journal->read(); $before=$f->f->head();
            $maximum=['calls'=>1,'input_tokens'=>1,'output_tokens'=>1,'cost_microusd'=>1,'milliseconds'=>1];
            $call=static fn(array $state)=>SharedExposure::formation($state,'unmapped-session',$maximum,null,$store->journal,$store->clock);
            self::assertSame('O2_SHARED_BUDGET_SOURCE_UNRESOLVED',NativeAssignmentProof::refusal(fn()=>$call($frame['state'])));
            $probe=$frame['state']; $probe['onboarding']['schema']='imperium.onboarding-authority-state/v5';
            $call($probe); // Unsupported-version early return, NOT evidence of accepted v5 state.
            self::assertSame($before,$f->f->head()); self::assertSame($frame,$store->journal->read());
            $path=getenv('PPC10_PUBLIC_EVIDENCE'); if(is_string($path) && $path!=='') {
                if(!is_dir($path)) { mkdir($path,0700,true); }
                file_put_contents($path.'/exposure-dispatch-probe.json',json_encode(['schema'=>'imperium.ppc10-source-probe/v1',
                    'control'=>'O2_SHARED_BUDGET_SOURCE_UNRESOLVED','v5_local_value'=>'EARLY_RETURN','valid_v5_state'=>false,
                    'published'=>false,'before'=>$before,'after'=>$f->f->head(),'native_application_proved'=>false],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            }
        } finally { $f->close(); }
    }
}
