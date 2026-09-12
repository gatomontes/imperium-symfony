<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\AugurReviewerMappingFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\AssessmentGroups;
use PHPUnit\Framework\TestCase;

final class AugurReviewerMappingLimitsTest extends TestCase
{
    public function testApprovedZeroOutputMappingCannotReachCognitionPost():void
    {
        $f=new F();
        try {
            $d=$f->fresh->d;
            $map=Policy::content($d->f->store->checkSource($d->f->store->journal->read()['state']['onboarding'],$d->policy['body']['candidate_bindings'][0]['mapping_ref']),'runtime-binding-map');
            self::assertSame(0,$map['mappings'][0]['supported_limits']['output_tokens']);
            $reason=null;
            try {$f->ready();$d->advance('w1.attempt.0');}
            catch(\RuntimeException|\InvalidArgumentException $e){$reason=$e->getMessage();}
            $s=$d->f->store->journal->read()['state']['onboarding'];
            $posts=array_values(array_filter($f->requests,static fn(array $r):bool=>$r['method']==='POST'));
            $claims=array_values(array_filter($s['claims'],static fn(array $c):bool=>$c['record']['body']['authority_source']['kind']==='assessment'));
            $proof=['mapping_output_limit'=>0,'post_count'=>count($posts),'refusal'=>$reason,
                'reserved_output_tokens'=>$claims[0]['maximum']['output_tokens']??null,
                'actual_output_tokens'=>$claims[0]['custody'][4]['body']['response_envelope']['metadata']['usage']['output_tokens']??null,
                'outcome'=>AssessmentGroups::outcome($s,$d->policy,'w1.attempt.0')['classification']??null];
            fwrite(STDERR,json_encode($proof,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
            self::assertSame([], $posts, 'An approved runtime mapping with zero output support must prevent cognition POST.');
        } finally {$f->close();}
    }
}
