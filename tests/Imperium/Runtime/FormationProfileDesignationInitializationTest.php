<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\Formation\FormationProfileDesignationInitialization as Initialization;
use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;
use PHPUnit\Framework\TestCase;

final class FormationProfileDesignationInitializationTest extends TestCase
{
    public function testExplicitOwnerInitializationIsEmptyAndHistoricalFrameIsUnchanged(): void
    {
        $f = new CitadelFormationFixture();
        try {
            $prior = $f->journal->read();
            $path = sprintf('%s/var/imperium/citadel/formation/%012d.json', $f->root, $prior['generation']);
            $bytes = file_get_contents($path);
            $head = ['generation' => $prior['generation'], 'digest' => $prior['record_digest']];
            $terms = ['schema' => Initialization::SCHEMA, 'citadel_id' => $prior['state']['citadel_id'], 'expected_head' => $head];
            $decision = $f->sign('INITIALIZE_FORMATION_PROFILE_DESIGNATIONS', $terms);
            $service = new Initialization($f->journal, $f->signatures);
            $receipt = $service->initialize($head, $decision);
            $after = $f->journal->read();
            self::assertSame($bytes, file_get_contents($path));
            self::assertSame($prior['generation'] + 1, $after['generation']);
            self::assertSame(['schema' => Initialization::SCHEMA, 'initialization' => $receipt, 'delegations' => [], 'events' => [], 'current' => []], $after['state']['profile_designations']);
            $unchanged = $after['state']; unset($unchanged['profile_designations']);
            self::assertSame($prior['state'], $unchanged);
            try { $service->initialize($head, $decision); self::fail('Stale initialization accepted'); }
            catch (\RuntimeException $e) { self::assertSame('PPC203_DESIGNATION_STALE_HEAD', $e->getMessage()); }
            try { $service->initialize(['generation' => $after['generation'], 'digest' => $after['record_digest']], $decision); self::fail('Duplicate initialization accepted'); }
            catch (\RuntimeException $e) { self::assertSame('PPC204_DESIGNATION_ALREADY_INITIALIZED', $e->getMessage()); }
            self::assertSame($after, $f->journal->read());
        } finally { $f->close(); }
    }

    public function testGenericOwnerActCannotInitialize(): void
    {
        $f = new CitadelFormationFixture();
        try {
            $prior = $f->journal->read(); $head = ['generation' => $prior['generation'], 'digest' => $prior['record_digest']];
            $terms = ['schema' => Initialization::SCHEMA, 'citadel_id' => $prior['state']['citadel_id'], 'expected_head' => $head];
            try { (new Initialization($f->journal, $f->signatures))->initialize($head, $f->sign('APPROVE_FORMATION_PROFILE', $terms)); self::fail('Generic act accepted'); }
            catch (\RuntimeException $e) { self::assertSame('CMF022_AUTHENTIC_EXACT_DECISION_REQUIRED', $e->getMessage()); }
            self::assertSame($prior, $f->journal->read());
        } finally { $f->close(); }
    }

    public function testTwoSignedInitializationsAtOneHeadPublishOnlyOne(): void
    {
        $f = new CitadelFormationFixture();
        try {
            $prior = $f->journal->read(); $head = ['generation' => $prior['generation'], 'digest' => $prior['record_digest']];
            $terms = ['schema' => Initialization::SCHEMA, 'citadel_id' => $prior['state']['citadel_id'], 'expected_head' => $head];
            $processes = [];
            for ($i = 0; $i < 2; ++$i) {
                $path = $f->root.'/public-initialize-'.$i.'.json';
                file_put_contents($path, json_encode(['operation' => 'initialize', 'at' => $f->clock->at, 'head' => $head,
                    'decision' => $f->sign('INITIALIZE_FORMATION_PROFILE_DESIGNATIONS', $terms)], JSON_THROW_ON_ERROR));
                $process = proc_open([PHP_BINARY, __DIR__.'/Support/model-bound-profile-worker.php', $f->root, $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::assertIsResource($process); $processes[] = [$process, $pipes];
            }
            $codes = []; $outputs = [];
            foreach ($processes as [$process, $pipes]) {
                $outputs[] = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
                $codes[] = proc_close($process);
            }
            sort($codes); self::assertSame([0, 1], $codes);
            self::assertStringContainsString('PPC203_DESIGNATION_STALE_HEAD', implode('', $outputs));
            $after = $f->journal->read(); self::assertSame($prior['generation'] + 1, $after['generation']);
            self::assertSame([], $after['state']['profile_designations']['events']);
            self::assertSame([], $after['state']['profile_designations']['current']);
        } finally { $f->close(); }
    }
}
