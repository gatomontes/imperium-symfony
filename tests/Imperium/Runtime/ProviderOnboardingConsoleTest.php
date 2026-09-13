<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Command\{ProviderOnboardCommand,ProviderStatusCommand,ProviderResumeCommand};
use App\Imperium\Runtime\Onboarding\Console\{FixedGateway,PublicResult,Gateway};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\SystemClock;
use App\Tests\Imperium\Runtime\Support\{DeepSeekFixture,AssignmentFixture};
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

final class ProviderOnboardingConsoleTest extends TestCase
{
    private static function tree(string $root): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[substr($file->getPathname(),strlen($root))] = hash_file('sha256',$file->getPathname());
        }
        ksort($files); return $files;
    }

    private function runRequest(Gateway $gateway,string $root,array|string $q,bool $resume=false,string $format='json'): array
    {
        $path = $root.'/public-command.json';
        file_put_contents($path,is_string($q)?$q:json_encode($q,JSON_THROW_ON_ERROR));
        $tester = new CommandTester($resume?new ProviderResumeCommand($gateway):new ProviderOnboardCommand($gateway));
        $exit = $tester->execute(['public-request-file'=>$path,'--format'=>$format],['capture_stderr_separately'=>true]);
        return [$exit,$format === 'json'?json_decode($tester->getDisplay(),true,32,JSON_THROW_ON_ERROR):$tester->getDisplay(),$tester->getErrorOutput()];
    }

    public function testMissingOwnerDoesNotCreateLockDirectoryOrState(): void
    {
        $root = sys_get_temp_dir().'/o5-absent-'.bin2hex(random_bytes(8)); mkdir($root);
        try {
            $store = new AuthorityStore($root,new SystemClock(),'instance-test','citadel-test','operator-test',str_repeat('a',40));
            $command = new CommandTester(new ProviderStatusCommand(new FixedGateway($store)));
            self::assertSame(1,$command->execute(['sequence-id'=>'sequence-test','--format'=>'json'],['capture_stderr_separately'=>true]));
            self::assertSame([],self::tree($root));
            self::assertSame([],glob($root.'/*'));
            self::assertSame(['O5_EXISTING_OWNER_REQUIRED'],json_decode($command->getDisplay(),true)['reason_codes']);
        } finally { rmdir($root); }
    }

    public function testPreviewAndStatusArePureAndUseCanonicalIngress(): void
    {
        $f = new DeepSeekFixture();
        try {
            $gateway = new FixedGateway($f->f->store,$f->runtime);
            $f->keyHook = static function(): void { throw new \LogicException('credential must not be observed'); };
            $f->generationHook = static function(): void { throw new \LogicException('credential generation must not be observed'); };
            $q = $f->request(); $q['mode'] = 'preview';
            file_put_contents($f->f->root.'/public-command.json',json_encode($q,JSON_THROW_ON_ERROR));
            $before = self::tree($f->f->root);
            [$exit,$out] = $this->runRequest($gateway,$f->f->root,$q);
            self::assertSame(2,$exit,json_encode($out));
            self::assertSame('CONFIGURED',$out['status']);
            self::assertSame('unknown',$out['facts']['credential']['state']);
            self::assertFalse($out['effects']['new_effects_this_command']);
            self::assertNull($out['result_ref']);
            self::assertSame($before,self::tree($f->f->root));
            self::assertSame([],$f->requests);
            $status = new CommandTester(new ProviderStatusCommand($gateway));
            self::assertSame(1,$status->execute(['sequence-id'=>'sequence-test','--format'=>'json'],['capture_stderr_separately'=>true]));
            self::assertSame(['SEQUENCE_NOT_FOUND'],json_decode($status->getDisplay(),true)['reason_codes']);
            self::assertSame($before,self::tree($f->f->root));
            foreach (["\xEF\xBB\xBF{}",'{"schema":"x","schema":"x"}',str_repeat('[',33).'0'.str_repeat(']',33),str_repeat(' ',1048577),json_encode([...$q,'secret'=>'redaction-marker'],JSON_THROW_ON_ERROR)] as $raw) {
                [$exit,$out,$stderr] = $this->runRequest($gateway,$f->f->root,$raw);
                self::assertSame(1,$exit); self::assertSame('REFUSED',$out['status']);
                self::assertStringNotContainsString('redaction-marker',json_encode($out).$stderr);
            }
        } finally { $f->close(); }
    }

    public function testUnknownExceptionCannotEscapeInEitherFormat(): void
    {
        $gateway = new class implements Gateway {
            public function onboard(string $request): array { throw new \RuntimeException('private-key-redaction-marker'); }
            public function status(string $sequence): array { throw new \RuntimeException('private-key-redaction-marker'); }
            public function resume(string $request): array { throw new \RuntimeException('private-key-redaction-marker'); }
        };
        foreach (['human','json'] as $format) {
            $tester = new CommandTester(new ProviderStatusCommand($gateway));
            self::assertSame(1,$tester->execute(['sequence-id'=>'sequence-test','--format'=>$format],['capture_stderr_separately'=>true]));
            self::assertStringNotContainsString('private-key-redaction-marker',$tester->getDisplay().$tester->getErrorOutput());
            self::assertStringContainsString('O5_ONBOARDING_REFUSED',$tester->getErrorOutput());
        }
    }

    public function testUnknownDispatchAndResumeDoNotRedispatch(): void
    {
        $f = new DeepSeekFixture(respond:static function(): never { throw new \RuntimeException('interrupted'); });
        try {
            $f->ready(); $gateway = new FixedGateway($f->f->store,$f->runtime);
            $q = $f->request('access');
            [$exit,$out] = $this->runRequest($gateway,$f->f->root,$q);
            self::assertSame(3,$exit,json_encode($out)); self::assertSame('OUTCOME_UNKNOWN',$out['status']);
            self::assertSame(1,$out['effects']['exposure']['calls']); self::assertCount(1,$f->requests);
            [$exit,$replay] = $this->runRequest($gateway,$f->f->root,$q);
            self::assertSame(3,$exit); self::assertFalse($replay['effects']['new_effects_this_command']); self::assertCount(1,$f->requests);
            $resume = ['schema'=>'imperium.provider-onboarding-resume/v2','sequence_id'=>'sequence-test','command_id'=>'recognize-command-01',
                'expected_head'=>$replay['head'],'recognize_command_ref'=>$out['result_ref']];
            [$exit,$recognized] = $this->runRequest($gateway,$f->f->root,$resume,true);
            self::assertSame(3,$exit,json_encode($recognized)); self::assertFalse($recognized['effects']['new_effects_this_command']); self::assertCount(1,$f->requests);
            self::assertSame($out['sequence_head'],$recognized['sequence_head']);
            $resume['expected_head'] = $recognized['head'];
            [$exit,$conflict] = $this->runRequest($gateway,$f->f->root,$resume,true);
            self::assertSame(1,$exit); self::assertSame(['O2_COMMAND_CONFLICT'],$conflict['reason_codes']); self::assertCount(1,$f->requests);
        } finally { $f->close(); }
    }

    public function testFullOfflineJourneyAppliesPersistsRecognizesAndReplacesWholeSet(): void
    {
        $f = new AssignmentFixture(consoleComposition:true); $d = $f->fresh->d;
        try {
            (new \App\Imperium\Runtime\Onboarding\Assignment\AssignmentMigration($d->f->store))->migrate($d->f->head());
            $gateway = new FixedGateway($d->f->store,$d->runtime);
            foreach ([null,'configure','admit-evidence','access','select-base','map-base','found-augur','w1.attempt.0','w2.attempt.0','w3.attempt.0','apply-assignments'] as $step) {
                $q = $d->request($step); $preview = [...$q,'mode'=>'preview'];
                $before = $d->f->store->journal->read(); $calls = count($f->requests);
                $d->generationHook = static function(): void { throw new \LogicException('preview cannot observe credential generation'); };
                $d->keyHook = static function(): void { throw new \LogicException('preview cannot open credentials'); };
                [$previewExit,$planned] = $this->runRequest($gateway,$d->f->root,$preview);
                $d->generationHook = null; $d->keyHook = null;
                self::assertSame(2,$previewExit,($step ?? 'registration').': '.json_encode($planned));
                self::assertFalse($planned['effects']['new_effects_this_command']);
                self::assertSame($before,$d->f->store->journal->read()); self::assertCount($calls,$f->requests);
                if ($step === 'apply-assignments') {
                    self::assertSame('proposed',$planned['facts']['assignment']['state']);
                    self::assertStringContainsString('courtyard.courtthane',$planned['next_action']['explanation']);
                    self::assertStringContainsString('clavium.locksmith',$planned['next_action']['explanation']);
                }
                [$exit,$out] = $this->runRequest($gateway,$d->f->root,$q);
                self::assertSame($step === 'apply-assignments'?0:2,$exit,($step ?? 'registration').': '.json_encode($out));
                $d->last = $out['result_ref'];
                self::assertCount(14,$out); self::assertCount(7,$out['facts']); self::assertCount(4,$out['effects']);
                self::assertSame(array_fill(0,5,false),array_values($out['operational_flags']));
            }
            self::assertCount(4,$f->requests); self::assertCount(4,$out['effects']['claim_refs']);
            $settings = $f->settings(); $snapshot = $settings->snapshot(); self::assertCount(2,$snapshot['assignments']);
            self::assertSame('ASSIGNMENT_APPLIED',$out['status']);
            foreach ($snapshot['assignments'] as $tuple) { self::assertEquals($tuple,$settings->resolve($tuple['role'])); }
            $before = $d->f->store->journal->read();
            [$exit,$replay] = $this->runRequest($gateway,$d->f->root,$q);
            self::assertSame(0,$exit); self::assertFalse($replay['effects']['new_effects_this_command']); self::assertSame($before,$d->f->store->journal->read());
            [$humanExit,$human] = $this->runRequest($gateway,$d->f->root,$q,format:'human');
            self::assertSame($exit,$humanExit); self::assertStringContainsString('ASSIGNMENT_APPLIED',$human);
            $changed = $f->ledger()->replace($d->f::json($f->replacementRequest()));
            self::assertSame('ASSIGNMENTS_CHANGED',$changed['status']);
            $next = $settings->snapshot();
            foreach ($next['assignments'] as $tuple) { self::assertSame(2,$tuple['generation']); self::assertSame('deepseek-v4-pro',$tuple['model_id']); }
            $status = (new FixedGateway($d->f->store))->status('sequence-test');
            self::assertCount(2,$status['effects']['assignment_receipt_refs']); self::assertFalse($status['effects']['new_effects_this_command']);
            $before = self::tree($d->f->root);
            $process = new \Symfony\Component\Process\Process([PHP_BINARY,__DIR__.'/Support/provider-console-worker.php',$d->f->root,(string)$d->f->now]);
            $process->setTimeout(120); $process->run();
            self::assertSame(0,$process->getExitCode(),$process->getErrorOutput().$process->getOutput());
            self::assertSame($status,json_decode($process->getOutput(),true,32,JSON_THROW_ON_ERROR));
            self::assertSame($before,self::tree($d->f->root));
            $d->f->now += 1801;
            [$exit,$historical] = $this->runRequest($gateway,$d->f->root,$q);
            self::assertSame(1,$exit); self::assertSame('applied',$historical['facts']['assignment']['state']);
            self::assertFalse($historical['effects']['new_effects_this_command']); self::assertSame($next,$settings->snapshot()); self::assertCount(4,$f->requests);
        } finally { $f->close(); }
    }
}
