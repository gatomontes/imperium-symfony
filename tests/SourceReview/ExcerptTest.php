<?php

declare(strict_types=1);

namespace App\Tests\SourceReview;

use App\SourceReview\{Proposal, Result, Selection, SnapshotStore, Transport};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExcerptTest extends TestCase
{
    private string $root;

    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-excerpt-'.bin2hex(random_bytes(8)); }
    protected function tearDown(): void { $this->remove($this->root); }
    private function remove(string $p): void
    {
        if (is_dir($p) && !is_link($p)) {
            foreach (scandir($p) as $f) { if ($f !== '.' && $f !== '..') { $this->remove($p.'/'.$f); } }
            rmdir($p);
        } elseif (file_exists($p) || is_link($p)) { unlink($p); }
    }

    private function selection(string $bytes = "one\r\ntwo\r\nOMITTED\r\nfour", array $ranges = [[2, 2], [4, 4]]): array
    {
        return Selection::derive(['path' => 'Service.php', 'sha256' => hash('sha256', $bytes), 'ranges' => $ranges], $bytes);
    }

    private function transport(): Transport
    {
        return new class implements Transport {
            public array $calls = [];
            public function send(string $secret, string $payload): string
            {
                $this->calls[] = $payload;
                return '{"disposition":"INSUFFICIENT_INPUT","finding":null,"rationale":"Synthetic offline response."}';
            }
        };
    }

    private function input(Fixture $f): string
    {
        $path = $f->input();
        $input = json_decode(file_get_contents($path), true);
        $bytes = "<?php\r\nOMITTED-CONTENT-MUST-NOT-LEAVE\r\nreturn 7;\r\n";
        file_put_contents(dirname($path).'/Service.php', $bytes);
        $input['files'] = [['path' => 'Service.php', 'sha256' => hash('sha256', $bytes), 'ranges' => [[1, 1], [3, 3]]]];
        file_put_contents($path, json_encode($input, JSON_THROW_ON_ERROR));
        return $path;
    }

    public function testExactCrlfOriginalCoordinatesAndVisibleOmissions(): void
    {
        $file = $this->selection();
        self::assertSame("two\r\n", base64_decode($file['segments'][0]['bytes_base64']));
        self::assertSame('four', base64_decode($file['segments'][1]['bytes_base64']));
        $p = Proposal::build([$file], 'provisional behavior', 'PHP', null);
        self::assertSame('imperium.source-review-proposal/v2', $p['schema']);
        self::assertSame([[1, 1], [3, 3]], $p['manifest'][0]['omitted_ranges']);
        self::assertSame(1, $p['manifest'][0]['segments'][1]['segment_start_line']);
        self::assertSame(4, $p['manifest'][0]['segments'][1]['start_line']);
        $data = json_decode(json_decode($p['payload'], true)['messages'][1]['content'], true);
        self::assertSame("two\r\n", $data['files'][0]['segments'][0]['content']);
        self::assertSame($file['original_sha256'], $data['files'][0]['original_sha256']);
        self::assertStringNotContainsString('OMITTED', $p['payload']);
        self::assertSame($p, Proposal::validate($p));
    }

    public static function invalidRanges(): iterable
    {
        yield 'empty' => [[]]; yield 'zero' => [[[0, 1]]]; yield 'negative' => [[[-1, 1]]];
        yield 'reversed' => [[[3, 2]]]; yield 'beyond eof' => [[[1, 5]]];
        yield 'overlap' => [[[1, 2], [2, 3]]]; yield 'unsorted' => [[[3, 3], [1, 1]]];
        yield 'duplicate' => [[[1, 1], [1, 1]]]; yield 'float' => [[[1.0, 2]]];
        yield 'string' => [[['1', 2]]]; yield 'shape' => [[[1, 2, 3]]];
        yield 'bounded segment count' => [array_fill(0, 31, [1, 1])];
    }

    #[DataProvider('invalidRanges')]
    public function testInvalidRangesAreRefused(array $ranges): void
    {
        $this->expectExceptionMessage('SR_RANGE_INVALID');
        $this->selection(ranges: $ranges);
    }

    public function testIncorrectOriginalHashRefusesPreparationWithoutRecordOrIo(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t, false); $path = $this->input($f);
        $input = json_decode(file_get_contents($path), true);
        $input['files'][0]['sha256'] = str_repeat('0', 64);
        file_put_contents($path, json_encode($input));
        try { $f->snapshots->prepare($path); self::fail(); }
        catch (\RuntimeException $e) { self::assertSame('SR_ORIGINAL_HASH_MISMATCH', $e->getMessage()); }
        self::assertSame([], glob($this->root.'/'.SnapshotStore::DIRECTORY.'/*.json') ?: []);
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
    }

    public function testMappingsIdentityHashesAndBytesAreBoundToApproval(): void
    {
        $p = Proposal::build([$this->selection()], 'behavior', 'PHP', null);
        $mutations = [
            static function (array &$p): void { $p['manifest'][0]['segments'][0]['segment_start_line'] = 2; },
            static function (array &$p): void { $p['manifest'][0]['segments'][0]['sha256'] = str_repeat('a', 64); },
            static function (array &$p): void { $p['manifest'][0]['omitted_ranges'] = []; },
            static function (array &$p): void { $p['files'][0]['original_sha256'] = str_repeat('a', 64); },
            static function (array &$p): void { $p['files'][0]['segments'][0]['start_line'] = 1; $p['files'][0]['segments'][0]['end_line'] = 1; },
            static function (array &$p): void { $p['files'][0]['segments'][0]['bytes_base64'] = base64_encode("new\r\n"); },
            static function (array &$p): void { $p['payload'] .= ' '; },
        ];
        foreach ($mutations as $mutate) {
            $changed = $p; $mutate($changed);
            self::assertNotSame(Proposal::digest($p), Proposal::digest($changed));
            try { Proposal::validate($changed); self::fail('Changed selection accepted'); }
            catch (\RuntimeException $e) { self::assertSame('SR_PROPOSAL_CHANGED', $e->getMessage()); }
        }
        $changed = $p['files']; $changed[0]['segments'][0]['start_line'] = 1; $changed[0]['segments'][0]['end_line'] = 1;
        $rebuilt = Proposal::build($changed, 'behavior', 'PHP', null);
        self::assertNotSame($p['proposal_id'], $rebuilt['proposal_id']);
        self::assertNotSame($p['payload_digest'], $rebuilt['payload_digest']);
    }

    public static function citations(): iterable
    {
        yield 'first segment' => [2, 2, true]; yield 'last segment' => [4, 4, true];
        yield 'omitted prefix' => [1, 1, false]; yield 'omitted middle' => [3, 3, false];
        yield 'cross gap' => [2, 4, false]; yield 'outside original' => [5, 5, false];
        yield 'reversed' => [4, 2, false];
    }

    #[DataProvider('citations')]
    public function testFindingsUseSelectedOriginalLines(int $start, int $end, bool $allowed): void
    {
        $p = Proposal::build([$this->selection()], 'behavior', 'PHP', null);
        $finding = ['path' => 'Service.php', 'start_line' => $start, 'end_line' => $end,
            'triggering_input' => 'synthetic', 'expected_behavior' => 'synthetic', 'actual_behavior' => 'synthetic',
            'cause' => 'synthetic', 'impact' => 'synthetic', 'suggested_correction' => 'synthetic', 'regression_case' => 'unexecuted'];
        if (!$allowed) { $this->expectExceptionMessage('SR_RESULT_INVALID'); }
        $r = Result::parse(json_encode(['disposition' => 'FINDING', 'finding' => $finding, 'rationale' => 'Static only.']), $p);
        if ($allowed) { self::assertSame($finding, $r['finding']); self::assertFalse($r['reproduction_executed']); }
    }

    public function testSealedSelectionDeliveredExactlyOnceWithoutOriginalFiles(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t); $path = $this->input($f);
        $p = Proposal::validate($f->snapshots->prepare($path));
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
        $a = $f->workflow->authorize($p['proposal_id'], Proposal::digest($p), $f->transition, $f->seneschal);
        $lease = $f->workflow->lease($a['decision_id'], $f->locksmith);
        unlink(dirname($path).'/Service.php'); unlink(dirname($path).'/behavior.txt'); unlink($path);
        $result = $f->workflow->execute($a['authorization_id'], $lease['lease_id']);
        self::assertSame([$p['payload']], $t->calls);
        self::assertStringNotContainsString('OMITTED-CONTENT-MUST-NOT-LEAVE', $t->calls[0]);
        self::assertSame($p['manifest_digest'], $result['output']['provenance']['manifest_digest']);
        self::assertSame($result, $f->workflow->execute($a['authorization_id'], $lease['lease_id']));
        self::assertCount(1, $t->calls);
    }

    public function testNullPricingRefusesBeforeAuthorityOrDispatch(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t, false); $path = $this->input($f);
        $input = json_decode(file_get_contents($path), true); $input['pricing'] = null;
        file_put_contents($path, json_encode($input)); $p = Proposal::validate($f->snapshots->prepare($path));
        try { $f->workflow->authorize($p['proposal_id'], Proposal::digest($p), 'absent', 'absent'); self::fail(); }
        catch (\RuntimeException $e) { self::assertSame('SR_CURRENT_PRICING_REQUIRED', $e->getMessage()); }
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
    }

    public function testAlteredStoredMappingAndWrongApprovalCannotDispatch(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t); $path = $this->input($f);
        $p = Proposal::validate($f->snapshots->prepare($path));
        try { $f->workflow->authorize($p['proposal_id'], str_repeat('0', 64), $f->transition, $f->seneschal); self::fail(); }
        catch (\RuntimeException $e) { self::assertSame('SR_APPROVAL_DIGEST_MISMATCH', $e->getMessage()); }
        $a = $f->workflow->authorize($p['proposal_id'], Proposal::digest($p), $f->transition, $f->seneschal);
        $lease = $f->workflow->lease($a['decision_id'], $f->locksmith);
        $recordPath = $this->root.'/'.SnapshotStore::DIRECTORY.'/'.$p['proposal_id'].'.json';
        $record = json_decode(file_get_contents($recordPath), true);
        $record['manifest'][0]['segments'][1]['start_line'] = 2;
        file_put_contents($recordPath, json_encode($record));
        try { $f->workflow->execute($a['authorization_id'], $lease['lease_id']); self::fail(); }
        catch (\RuntimeException) {}
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
    }

    public function testTruncatedSegmentCannotClaimAdditionalOriginalLines(): void
    {
        $file = $this->selection(); $file['segments'][0]['bytes_base64'] = base64_encode('two');
        $this->expectExceptionMessage('SR_SEGMENT_LINES_INVALID');
        Proposal::build([$file], 'behavior', 'PHP', null);
    }

    public function testOriginalAndSelectedLimitsRemainEnforced(): void
    {
        try { $this->selection(str_repeat('a', 131073), [[1, 1]]); self::fail(); }
        catch (\RuntimeException $e) { self::assertSame('SR_INPUT_SIZE', $e->getMessage()); }
        $file = $this->selection(str_repeat('a', 31000), [[1, 1]]);
        try { Proposal::build([$file], 'behavior', 'PHP', null); self::fail(); }
        catch (\RuntimeException $e) { self::assertSame('SR_INPUT_TOKEN_LIMIT', $e->getMessage()); }
        $file = $this->selection(str_repeat('a', 70000), [[1, 1]]); $other = $file; $other['path'] = 'Other.php';
        try { Proposal::build([$file, $other], 'behavior', 'PHP', null); self::fail(); }
        catch (\RuntimeException $e) { self::assertSame('SR_SOURCE_LIMIT', $e->getMessage()); }
    }

    public function testDuplicateOriginalPathsAreRefused(): void
    {
        $f = new Fixture($this->root, $this->transport(), false); $path = $this->input($f);
        $input = json_decode(file_get_contents($path), true); $input['files'][] = 'service.php';
        file_put_contents($path, json_encode($input));
        $this->expectExceptionMessage('SR_DUPLICATE_PATH'); $f->snapshots->prepare($path);
    }

    public function testWholeFileGoldenProposalRemainsIdenticalToPriorImplementation(): void
    {
        // Produced by the unmodified Proposal class at 20e2c566, not this implementation.
        $p = Proposal::build([['path' => 'file.txt', 'bytes_base64' => base64_encode("one\r\ntwo\r\n")]], 'behavior', 'PHP', null);
        self::assertSame('imperium.source-review-proposal/v1', $p['schema']);
        self::assertSame('source-review-c5c073063c5c1b2288e2129b19e0740fd845c8a67079bcffeca4a3dfff35903c', $p['proposal_id']);
        self::assertSame('9e0ed1eca0b164a4027d3229a985b76307bb816b9677cddd79469dfd3b204a97', Proposal::digest($p));
        self::assertSame($p, Proposal::validate($p));
    }

    public function testWholeRangeAndMixedWholeFileInputs(): void
    {
        $bytes = "one\r\ntwo\r\n";
        $file = $this->selection($bytes, [[1, 2]]);
        $p = Proposal::build([$file, ['path' => 'Context.txt', 'bytes_base64' => base64_encode('context')]], 'behavior', 'PHP', null);
        self::assertSame('whole_file', $p['manifest'][0]['selection']);
        self::assertSame([], $p['manifest'][0]['omitted_ranges']);
        self::assertSame($bytes, base64_decode($p['files'][0]['segments'][0]['bytes_base64']));
        $file['original_sha256'] = str_repeat('a', 64);
        $this->expectExceptionMessage('SR_ORIGINAL_HASH_MISMATCH'); Proposal::build([$file], 'behavior', 'PHP', null);
    }
}
