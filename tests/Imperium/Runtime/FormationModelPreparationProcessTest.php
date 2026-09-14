<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\ModelPreparationFixture as F;
use App\Imperium\Runtime\Citadel\Formation\FormationInstitution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormationModelPreparationProcessTest extends TestCase
{
    private array $processes = [];
    private function start(F $f, array $input, string $name): array
    {
        $c = $f->f->root.'/'.$name; $input['at'] = $f->f->clock->at;
        file_put_contents($c.'.json', json_encode($input, JSON_THROW_ON_ERROR));
        $p = proc_open([PHP_BINARY, __DIR__.'/Support/model-preparation-worker.php', $f->f->root, $c.'.json', $c],
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
        if ($dest = getenv('PPC5_PUBLIC_EVIDENCE')) {
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
        $h = fopen($f->f->root.'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock', 'rb');
        self::assertFalse(flock($h, LOCK_EX | LOCK_NB)); fclose($h);
    }
    private function current(F $f): array { return ['mode' => 'current', 'candidate' => $f->candidate, 'seat' => $f->seat]; }
    private function mutation(F $f, string $kind): array
    {
        if ($kind === 'revoke') {
            $nonce = $f->authorization['body']['decision']['payload']['nonce'];
            return ['mode' => 'revoke', 'nonce' => $nonce, 'decision' => $f->f->sign('REVOKE_DECISION', ['nonce' => $nonce])];
        }
        $r = (new FormationInstitution($f->f->root))->witness('conscription')['occupancy']; $next = $r; $next['status'] = 'RETIRED';
        return ['mode' => 'retire', 'path' => 'var/imperium/offices/conscription/occupancy/'.$r['binding_id'].'.json', 'digest' => $r['record_digest'], 'record' => $next];
    }
    public static function races(): iterable
    {
        foreach (['courtyard.courtthane', 'clavium.locksmith'] as $seat) {
            foreach (['revoke', 'retire'] as $kind) { foreach ([true, false] as $first) { yield [$seat, $kind, $first]; } }
        }
    }
    #[DataProvider('races')]
    public function testCurrentConsumerVersusRevocationAndTenureBothOrderings(string $seat, string $kind, bool $writerFirst): void
    {
        $f = new F($seat);
        try {
            $read = $this->current($f); $write = $this->mutation($f, $kind);
            self::assertSame([0, "OK\n"], $this->finish($this->start($f, $read, 'fresh-positive')));
            $one = $this->start($f, [...($writerFirst ? $write : $read), 'pause' => true], 'first'); $this->held($one, $f);
            $two = $this->start($f, $writerFirst ? $read : $write, 'second');
            self::assertTrue(proc_get_status($two[0])['running']); self::assertFileDoesNotExist($two[1].'.result');
            file_put_contents($one[1].'.release', 'release'); self::assertSame([0, "OK\n"], $this->finish($one));
            self::assertSame($writerFirst ? 1 : 0, $this->finish($two)[0]);
            self::assertSame(1, $this->finish($this->start($f, $read, 'fresh-current-refusal'))[0]);
            self::assertSame([0, "OK\n"], $this->finish($this->start($f, ['mode' => 'seal', 'envelope' => $f->sealingEnvelope], 'historical-replay')));
        } finally { $f->close(); }
    }

    public static function publicationRaces(): iterable
    {
        foreach (['revoke', 'retire', 'compete'] as $kind) { foreach ([true, false] as $first) { yield [$kind, $first]; } }
    }
    #[DataProvider('publicationRaces')]
    public function testOneUsePublicationRaces(string $kind, bool $writerFirst): void
    {
        $f = new F(finish: false);
        try {
            $seal = ['mode' => 'seal', 'envelope' => $f->sealingEnvelope];
            if ($kind === 'compete') {
                $p = $f->sealingEnvelope['payload']; $p['nonce'] = bin2hex(random_bytes(24));
                $write = ['mode' => 'seal', 'envelope' => $f->sealerSign($p)];
            } else { $write = $this->mutation($f, $kind); }
            $one = $this->start($f, [...($writerFirst ? $write : $seal), 'pause' => true], 'first'); $this->held($one, $f);
            $two = $this->start($f, $writerFirst ? $seal : $write, 'second');
            self::assertTrue(proc_get_status($two[0])['running']); self::assertFileDoesNotExist($two[1].'.result');
            file_put_contents($one[1].'.release', 'release'); self::assertSame([0, "OK\n"], $this->finish($one));
            self::assertSame($writerFirst || $kind === 'compete' ? 1 : 0, $this->finish($two)[0]);
            $s = $f->f->journal->read()['state']['model_preparation'];
            self::assertCount($writerFirst && $kind !== 'compete' ? 0 : 1, $s['seals']);
            self::assertCount(count($s['seals']), $s['consumed']);
        } finally { $f->close(); }
    }

    public static function interruption(): iterable { yield [false]; yield [true]; }

    public static function publicationPhases(): iterable { yield ['before']; yield ['after']; }
    #[DataProvider('publicationPhases')]
    public function testKilledAtActualJournalPublicationHasExactlyOneCompleteFact(string $phase): void
    {
        $f = new F(finish: false);
        try {
            $head = $f->head(); $input = ['mode' => 'seal', 'envelope' => $f->sealingEnvelope];
            $w = $this->start($f, [...$input, 'publication_phase' => $phase], 'publication-'.$phase); $this->held($w, $f);
            proc_terminate($w[0]); self::assertNotSame(0, $this->finish($w)[0]);
            $frame = $f->f->journal->read(); $s = $frame['state']['model_preparation'];
            self::assertSame($head['generation'] + ($phase === 'after' ? 1 : 0), $frame['generation']);
            self::assertCount($phase === 'after' ? 1 : 0, $s['seals']); self::assertCount(count($s['seals']), $s['consumed']);
            self::assertSame([0, "OK\n"], $this->finish($this->start($f, $input, 'retry-'.$phase)));
            $final = $f->f->journal->read(); self::assertSame($head['generation'] + 1, $final['generation']);
            self::assertCount(1, $final['state']['model_preparation']['seals']);
            self::assertCount(1, $final['state']['model_preparation']['consumed']);
        } finally { $f->close(); }
    }

    #[DataProvider('interruption')]
    public function testInterruptionAndExceptionReleaseWithoutPartialConsumption(bool $exception): void
    {
        $f = new F(finish: false);
        try {
            $head = $f->head();
            $input = ['mode' => 'seal', 'envelope' => $f->sealingEnvelope];
            $w = $this->start($f, [...$input, 'pause' => true, 'exception' => $exception], 'interruption'); $this->held($w, $f);
            if ($exception) { file_put_contents($w[1].'.release', 'release'); } else { proc_terminate($w[0]); }
            self::assertNotSame(0, $this->finish($w)[0]); self::assertSame($head, $f->head());
            self::assertSame([0, "OK\n"], $this->finish($this->start($f, $input, 'reconstruction')));
            $after = $f->head();
            self::assertSame([0, "OK\n"], $this->finish($this->start($f, $input, 'after-publication-replay')));
            self::assertSame($after, $f->head());
        } finally { $f->close(); }
    }
}
