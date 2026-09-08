<?php
declare(strict_types=1);
namespace App\Tests\SourceReview;

use App\SourceReview\{Proposal, Transport, Result, SnapshotStore};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SourceReviewTest extends TestCase
{
    private string $root;
    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-source-review-'.bin2hex(random_bytes(8)); }
    protected function tearDown(): void { $this->remove($this->root); }
    private function remove(string $p): void { if (is_dir($p) && !is_link($p)) { foreach (scandir($p) as $f) { if ($f !== '.' && $f !== '..') { $this->remove($p.'/'.$f); } } rmdir($p); } elseif (file_exists($p) || is_link($p)) { unlink($p); } }
    private function transport(string $disposition = 'NO_ACTIONABLE_DEFECT_FOUND'): Transport
    {
        return new class($disposition) implements Transport {
            public array $calls = []; public bool $fail = false; public ?string $response = null;
            public function __construct(private string $disposition) {}
            public function send(string $secret, string $payload): string {
                $this->calls[] = $payload;
                if ($this->fail) { throw new \RuntimeException('ambiguous transport failure'); }
                return $this->response ?? json_encode(['disposition' => $this->disposition, 'finding' => null, 'rationale' => 'Synthetic result.'], JSON_THROW_ON_ERROR);
            }
        };
    }
    private function ready(Fixture $f): array
    {
        $p = Proposal::validate($f->snapshots->prepare($f->input()));
        $a = $f->workflow->authorize($p['proposal_id'], Proposal::digest($p), $f->transition, $f->seneschal);
        $l = $f->workflow->lease($a['decision_id'], $f->locksmith);
        return [$p, $a, $l];
    }
    public function testPreparationIsLocalAndExactApprovedPayloadIsDeliveredOnce(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t); [$p, $a, $l] = $this->ready($f);
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
        // Changing the original does not change the sealed snapshot or cause a second read.
        file_put_contents($this->root.'/input/Service.php', 'changed after snapshot');
        $r = $f->workflow->execute($a['authorization_id'], $l['lease_id']);
        self::assertSame([$p['payload']], $t->calls);
        self::assertSame('NO_ACTIONABLE_DEFECT_FOUND', $r['output']['review']['disposition']);
        self::assertFalse($r['output']['review']['reproduction_executed']);
        foreach (['SYNTHETIC-SECRET', 'INTERNAL-MANIFESTATION', 'authorization_id', 'capability', 'signature'] as $secret) { self::assertStringNotContainsString($secret, $t->calls[0]); }
        self::assertSame($r, $f->workflow->execute($a['authorization_id'], $l['lease_id']));
        self::assertCount(1, $t->calls);
        self::assertSame('RESULT_VALIDATED_STATIC_ONLY', $f->workflow->status($a['authorization_id'])['progress']['result']['status']);
    }
    public function testMissingAuthorityNeverResolvesCredentialOrCallsProvider(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t);
        try { $f->workflow->execute('bounded-execution-authorization-aaaaaaaaaaaaaaaaaaaa', 'absent-lease'); self::fail(); } catch (\RuntimeException) {}
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
    }
    public function testSnapshotMutationIsRejectedBeforeClaim(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t); [$p, $a, $l] = $this->ready($f);
        $path = $this->root.'/'.SnapshotStore::DIRECTORY.'/'.$p['proposal_id'].'.json';
        file_put_contents($path, str_replace('sum', 'product', file_get_contents($path)));
        try { $f->workflow->execute($a['authorization_id'], $l['lease_id']); self::fail(); } catch (\RuntimeException) {}
        self::assertSame([], $t->calls); self::assertSame(0, $f->credentials->calls);
    }
    public function testUnknownTransportOutcomeCannotRetry(): void
    {
        $t = $this->transport(); $t->fail = true; $f = new Fixture($this->root, $t); [$p, $a, $l] = $this->ready($f);
        for ($i = 0; $i < 2; ++$i) { try { $f->workflow->execute($a['authorization_id'], $l['lease_id']); self::fail(); } catch (\RuntimeException) {} }
        self::assertCount(1, $t->calls);
        self::assertSame('PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED', $f->workflow->status($a['authorization_id'])['progress']['journal']['status']);
    }
    public function testMalformedResultIsErrorNotNoFinding(): void
    {
        $t = $this->transport(); $t->response = 'not JSON'; $f = new Fixture($this->root, $t); [$p, $a, $l] = $this->ready($f);
        try { $f->workflow->execute($a['authorization_id'], $l['lease_id']); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_RESULT_INVALID', $e->getMessage()); }
        self::assertSame('RESULT_INVALID', $f->workflow->status($a['authorization_id'])['progress']['result']['status']);
    }
    public function testFindingAndInsufficientInput(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t); [$p, $a, $l] = $this->ready($f);
        $finding = ['path' => 'Service.php', 'start_line' => 3, 'end_line' => 3, 'triggering_input' => 'add(2, 3)', 'expected_behavior' => '5', 'actual_behavior' => '-1 by static evaluation', 'cause' => 'Subtracts b.', 'impact' => 'Wrong sum.', 'suggested_correction' => 'Use addition.', 'regression_case' => 'Unexecuted: assert add(2,3) equals 5.'];
        $t->response = json_encode(['disposition' => 'FINDING', 'finding' => $finding, 'rationale' => 'Static hypothesis.']);
        self::assertSame($finding, $f->workflow->execute($a['authorization_id'], $l['lease_id'])['output']['review']['finding']);
        self::assertSame('INSUFFICIENT_INPUT', Result::parse('{"disposition":"INSUFFICIENT_INPUT","finding":null,"rationale":"Missing dependency."}', $p)['disposition']);
        foreach ([['path' => 'Other.php'], ['start_line' => 0], ['end_line' => 4]] as $change) {
            try { Result::parse(json_encode(['disposition' => 'FINDING', 'finding' => array_replace($finding, $change), 'rationale' => 'Test']), $p); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_RESULT_INVALID', $e->getMessage()); }
        }
    }
    public function testLimitsAndPaths(): void
    {
        $file = ['path' => 'Service.php', 'bytes_base64' => base64_encode('hello')];
        $bad = [array_fill(0, 31, $file), [$file, $file], [['path' => '../escape', 'bytes_base64' => '']], [['path' => 'file', 'bytes_base64' => base64_encode(str_repeat('a', 131073))]], [['path' => 'file', 'bytes_base64' => base64_encode(str_repeat('a', 31000))]], [['path' => 'file', 'bytes_base64' => base64_encode("\xFF")]]];
        foreach ($bad as $files) { try { Proposal::build($files, 'behavior', 'PHP', null); self::fail(); } catch (\RuntimeException) {} }
        $p = Proposal::build([$file], 'behavior', 'PHP', null);
        self::assertSame(4000, json_decode($p['payload'], true)['max_tokens']); self::assertSame(0, $p['transport']['retries']);
        try { Proposal::preflight($p, new \DateTimeImmutable()); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_CURRENT_PRICING_REQUIRED', $e->getMessage()); }
    }

    public function testTwoProcessesHaveOnlyOneProviderWinner(): void
    {
        $f = new Fixture($this->root, $this->transport()); [$p, $a, $l] = $this->ready($f);
        $children = [];
        for ($i = 0; $i < 2; ++$i) {
            $process = proc_open([PHP_BINARY, __DIR__.'/dispatch-worker.php', $this->root, $a['authorization_id'], $l['lease_id']], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process); fclose($pipes[0]); $children[] = [$process, $pipes];
        }
        foreach ($children as [$process, $pipes]) {
            $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
            self::assertSame(0, proc_close($process)); self::assertSame('', $err); self::assertContains($out, ['COMPLETED', 'REFUSED_NO_REPLAY']);
        }
        self::assertSame(hash('sha256', $p['payload'])."\n", file_get_contents($this->root.'/provider-calls.txt'));
        self::assertSame('INSUFFICIENT_INPUT', $f->workflow->status($a['authorization_id'])['progress']['result']['review']['disposition']);
    }

    public function testWireOptionsAndHttpErrorsNeverRetry(): void
    {
        $calls = [];
        $client = new \Symfony\Component\HttpClient\MockHttpClient(function ($method, $url, $options) use (&$calls) {
            $calls[] = [$method, $url, $options];
            return new \Symfony\Component\HttpClient\Response\MockResponse('unavailable', ['http_code' => 503]);
        });
        $transport = new \App\SourceReview\HttpTransport($client);
        $p = Proposal::build([['path' => 'file.txt', 'bytes_base64' => base64_encode('text')]], 'behavior', 'PHP', null);
        try { $transport->send('synthetic', $p['payload']); self::fail(); } catch (\RuntimeException) {}
        self::assertCount(1, $calls); self::assertSame('POST', $calls[0][0]); self::assertSame(Proposal::ENDPOINT, $calls[0][1]);
        self::assertSame($p['payload'], $calls[0][2]['body']); self::assertEquals(120, $calls[0][2]['max_duration']); self::assertSame(0, $calls[0][2]['max_redirects']);
    }

    public function testChangedSettingsAndApprovalDigestCannotAuthorize(): void
    {
        $t = $this->transport(); $f = new Fixture($this->root, $t); $p = Proposal::validate($f->snapshots->prepare($f->input()));
        try { $f->workflow->authorize($p['proposal_id'], str_repeat('0', 64), $f->transition, $f->seneschal); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_APPROVAL_DIGEST_MISMATCH', $e->getMessage()); }
        $p['payload'] = str_replace('4000', '8000', $p['payload']);
        try { Proposal::validate($p); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_PROPOSAL_CHANGED', $e->getMessage()); }
        self::assertCount(0, $t->calls);
    }

    public function testBehaviorCostAndEncodingLimits(): void
    {
        $file = ['path' => 'source.txt', 'bytes_base64' => base64_encode('source')];
        foreach ([str_repeat('b', 32000), "\xEF\xBB\xBFbehavior", "binary\0behavior"] as $behavior) {
            try { Proposal::build([$file], $behavior, 'PHP', null); self::fail(); } catch (\RuntimeException) {}
        }
        $price = ['version' => 'synthetic', 'source' => 'fixture', 'valid_until' => '2099-01-01T00:00:00+00:00', 'input_microusd_per_token' => 1000000, 'output_microusd_per_token' => 1000000];
        try { Proposal::build([$file], 'behavior', 'PHP', $price); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_COST_LIMIT', $e->getMessage()); }
        self::assertSame(3, Proposal::lines("one\r\ntwo\r\nthree\r\n"));
    }

    public function testRemoteAndLinkedInputsAreRefused(): void
    {
        foreach (['https://example.test/input.json', 'php://input', '//server/share/file', 'C:/file:stream'] as $path) {
            try { SnapshotStore::readText($path, 100); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_LOCAL_FILE_REQUIRED', $e->getMessage()); }
        }
        mkdir($this->root, 0770, true); file_put_contents($this->root.'/target', 'text');
        if (@link($this->root.'/target', $this->root.'/hardlink')) {
            try { SnapshotStore::readText($this->root.'/hardlink', 100); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_REGULAR_FILE_REQUIRED', $e->getMessage()); }
        }
        if (@symlink($this->root.'/target', $this->root.'/symlink')) {
            try { SnapshotStore::readText($this->root.'/symlink', 100); self::fail(); } catch (\RuntimeException $e) { self::assertSame('SR_LINK_REFUSED', $e->getMessage()); }
        }
    }

    public function testProductionContainerDiscoversCommandWithoutProviderInvocation(): void
    {
        $kernel = new \App\Tests\Imperium\Runtime\Support\CanonicalNativeEffectCorridorKernel($this->root.'/kernel');
        try {
            $app = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
            $command = $app->find('imperium:source-review');
            self::assertSame('imperium:source-review', $command->getName());
            $f = new Fixture($this->root.'/input-fixture', $this->transport(), false);
            $tester = new \Symfony\Component\Console\Tester\CommandTester($command);
            self::assertSame(0, $tester->execute(['action' => 'prepare', 'values' => [$f->input()]]));
            self::assertArrayHasKey('proposal_digest_for_approval', json_decode($tester->getDisplay(), true, 64, JSON_THROW_ON_ERROR));
        } finally { $kernel->shutdown(); }
    }
}
