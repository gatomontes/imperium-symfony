<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\DesignationFixture as F;
use App\Imperium\Runtime\Citadel\Formation\FormationInstitution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormationProfileDesignationProcessTest extends TestCase
{
    private array $processes = [];
    private function start(F $f, array $input, string $name): array
    {
        $c = $f->m->f->root.'/'.$name; $input['at'] = $f->m->f->clock->at;
        file_put_contents($c.'.json', json_encode($input, JSON_THROW_ON_ERROR));
        $p = proc_open([PHP_BINARY, __DIR__.'/Support/designation-worker.php', $f->m->f->root, $c.'.json', $c],
            [1 => ['file', $c.'.out', 'w'], 2 => ['file', $c.'.err', 'w']], $pipes);
        self::assertIsResource($p); $this->processes[] = $p; $this->waitFile($c.'.attempt');
        return [$p, $c];
    }
    private function waitFile(string $path): void
    {
        $end = microtime(true) + 30;
        while (!is_file($path) && microtime(true) < $end) { usleep(1000); clearstatcache(true, $path); }
        self::assertFileExists($path);
    }
    private function finish(array $w): array
    {
        [$p, $c] = $w; $end = microtime(true) + 30;
        do { $s = proc_get_status($p); if (!$s['running']) { break; } usleep(1000); } while (microtime(true) < $end);
        self::assertFalse($s['running'], 'No deadlock within bounded wait');
        $exit = $s['exitcode']; $closed = proc_close($p); $exit = $exit < 0 ? $closed : $exit;
        $out = file_get_contents($c.'.out').file_get_contents($c.'.err');
        if ($dest = getenv('PPC6_PUBLIC_EVIDENCE')) {
            if (!is_dir($dest)) { mkdir($dest, 0770, true); }
            file_put_contents($dest.'/process-events.jsonl', json_encode(['checkpoint' => basename($c), 'input' => json_decode(file_get_contents($c.'.json'), true),
                'native_exit' => $exit, 'output' => $out, 'utc' => gmdate('c')], JSON_THROW_ON_ERROR)."\n", FILE_APPEND);
        }
        return [$exit, $out];
    }
    protected function tearDown(): void
    {
        foreach ($this->processes as $p) { if (is_resource($p)) { proc_terminate($p); proc_close($p); } }
    }
    private function held(array $w, F $f): void
    {
        $this->waitFile($w[1].'.held');
        $h = fopen($f->m->f->root.'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock', 'rb');
        self::assertFalse(flock($h, LOCK_EX | LOCK_NB)); fclose($h);
    }
    public static function races():iterable {
        foreach(\App\Imperium\Runtime\Citadel\Formation\FormationProfileDesignation::TARGETS as $seat) {
            foreach(['retire','revoke'] as $kind) { foreach([false,true] as $first) { yield [$seat,$kind,$first]; } }
        }
    }
    #[DataProvider('races')]
    public function testCurrentAndNativeMutationBothOrders(string $seat,string $kind,bool $first):void {
        $f=new F($seat);
        try {
            $f->service->publish($f->envelope(),$f->m->candidate);
            $read=['mode'=>'current','seat'=>$seat];
            if($kind==='retire') {
                $r=(new FormationInstitution($f->m->f->root))->witness('laboratorium')['occupancy']; $next=$r; $next['status']='RETIRED';
                $write=['mode'=>'retire','path'=>'var/imperium/offices/laboratorium/occupancy/'.$r['binding_id'].'.json','digest'=>$r['record_digest'],'record'=>$next];
            } else { $write=['mode'=>'publish','envelope'=>$f->envelope($f->current()['event'],\App\Imperium\Runtime\Citadel\Formation\FormationProfileDesignation::REVOKE),'candidate'=>$f->m->candidate]; }
            self::assertSame([0,"OK\n"],$this->finish($this->start($f,$read,'fresh')));
            $one=$this->start($f,[...($first?$write:$read),'pause'=>true],'first'); $this->held($one,$f);
            $two=$this->start($f,$first?$read:$write,'second'); self::assertTrue(proc_get_status($two[0])['running']);
            file_put_contents($one[1].'.release','release'); self::assertSame([0,"OK\n"],$this->finish($one));
            self::assertSame($first?1:0,$this->finish($two)[0]);
            self::assertSame(1,$this->finish($this->start($f,$read,'fresh-refused'))[0]);
        } finally { $f->close(); }
    }
    public static function publications():iterable {
        foreach(\App\Imperium\Runtime\Citadel\Formation\FormationProfileDesignation::TARGETS as $seat) {
            foreach(['before','after'] as $phase) { foreach([false,true] as $exception) { yield [$seat,$phase,$exception]; } }
        }
    }
    #[DataProvider('publications')]
    public function testSuccessorRealRenameTerminationAndException(string $seat,string $phase,bool $exception):void {
        $f=new F($seat);
        try {
            $first=$f->service->publish($f->envelope(),$f->m->candidate); $f->successor();
            $e=$f->envelope($first); $head=$f->m->head(); $input=['mode'=>'publish','envelope'=>$e,'candidate'=>$f->m->candidate];
            $w=$this->start($f,[...$input,'publication_phase'=>$phase,'publication_exception'=>$exception],'publication');
            if(!$exception) { $this->held($w,$f); proc_terminate($w[0]); }
            self::assertNotSame(0,$this->finish($w)[0]);
            $frame=$f->m->f->journal->read(); self::assertSame($head['generation']+($phase==='after'?1:0),$frame['generation']);
            self::assertCount($phase==='after'?2:1,$frame['state']['profile_designations']['events']);
            self::assertSame([0,"OK\n"],$this->finish($this->start($f,$input,'replay')));
            self::assertSame($head['generation']+1,$f->m->head()['generation']);
            self::assertCount(2,$f->current()['event']['attestations']);
        } finally { $f->close(); }
    }
    public function testCompetingSignedHeadsAndFreshExpiry():void {
        $f=new F('courtyard.courtthane');
        try {
            $a=$f->envelope(); $b=$f->envelope();
            $one=$this->start($f,['mode'=>'publish','envelope'=>$a,'candidate'=>$f->m->candidate,'pause'=>true],'first'); $this->held($one,$f);
            $two=$this->start($f,['mode'=>'publish','envelope'=>$b,'candidate'=>$f->m->candidate],'second');
            file_put_contents($one[1].'.release','release'); self::assertSame(0,$this->finish($one)[0]); self::assertSame(1,$this->finish($two)[0]);
            self::assertCount(1,$f->m->f->journal->read()['state']['profile_designations']['events']);
            $f->m->f->clock->at+=201; self::assertSame(1,$this->finish($this->start($f,['mode'=>'current','seat'=>$f->seat],'fresh-expired'))[0]);
        } finally { $f->close(); }
    }
}
