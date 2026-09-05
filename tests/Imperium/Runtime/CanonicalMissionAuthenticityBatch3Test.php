<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\CanonicalMissionTransitionService;
use App\Imperium\Runtime\Mission\GitRepositorySnapshotAdapter;
use App\Imperium\Runtime\Mission\MissionCapabilityIssuanceService;
use App\Imperium\Runtime\Mission\MissionLifecycleStore;
use App\Tests\Imperium\Runtime\Support\CanonicalMissionAuthorizationFixture;
use App\Tests\Imperium\Runtime\Support\ConsumerProcess;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionAuthenticityBatch3Test extends TestCase
{
    public function testAdapterReadsAndVerifiesExactCommitTreeBlobAndBytesFromGit(): void
    {
        $repository = $this->root();
        try {
            $this->git($repository, ['init']);
            $this->git($repository, ['config', 'user.email', 'canonical-mission@example.invalid']);
            $this->git($repository, ['config', 'user.name', 'Canonical Mission Test']);
            file_put_contents($repository.'/evidence.txt', "authentic committed bytes\n");
            $this->git($repository, ['add', 'evidence.txt']);
            $this->git($repository, ['commit', '-m', 'Synthetic snapshot']);
            $commit = trim($this->git($repository, ['rev-parse', 'HEAD']));
            $tree = trim($this->git($repository, ['rev-parse', 'HEAD^{tree}']));
            $blob = trim($this->git($repository, ['rev-parse', 'HEAD:evidence.txt']));

            file_put_contents($repository.'/evidence.txt', "uncommitted substituted bytes\n");
            $snapshot = (new GitRepositorySnapshotAdapter($repository))->inspect($commit, ['evidence.txt']);

            self::assertSame($commit, $snapshot['commit_id']);
            self::assertTrue($snapshot['commit_verified']);
            self::assertSame($tree, $snapshot['tree_id']);
            self::assertTrue($snapshot['tree_verified']);
            self::assertSame($blob, $snapshot['blobs'][0]['blob_id']);
            self::assertSame("authentic committed bytes\n", $snapshot['blobs'][0]['bytes']);
            self::assertSame(hash('sha256', "authentic committed bytes\n"), $snapshot['blobs'][0]['content_sha256']);
            self::assertNotSame((string) file_get_contents($repository.'/evidence.txt'), $snapshot['blobs'][0]['bytes']);
        } finally { $this->remove($repository); }
    }

    public function testDurableConsumptionIsRequiredStateBoundAndSurvivesNewServiceInstances(): void
    {
        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $at = new \DateTimeImmutable('2026-09-04T12:02:00+00:00');
            $capabilities = (new MissionCapabilityIssuanceService($root))->issue($fixture['authorizationId'], $at);
            $service = new CanonicalMissionTransitionService($root);
            $admitted = $service->consume($capabilities[0], $fixture['authorizationId'], $at);
            self::assertSame('ADMITTED', $admitted['state']);
            self::assertCount(1, $admitted['transition_history']);

            $this->fails('MIS420_CAPABILITY_CONSUMED', fn () => (new CanonicalMissionTransitionService($root))->consume(
                $capabilities[0], $fixture['authorizationId'], $at,
            ));
            $this->fails('MIS421_MISSION_REQUIRED_STATE_MISMATCH', fn () => (new CanonicalMissionTransitionService($root))->consume(
                $capabilities[2], $fixture['authorizationId'], $at,
            ));
            $stored = (new MissionLifecycleStore($root))->readMission($fixture['mission']['mission_id']);
            self::assertSame('ADMITTED', $stored['state']);
            self::assertCount(1, $stored['consumed_nonces']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $stored['record_digest']);
        } finally { $this->remove($root); }
    }

    public function testTwoIndependentProcessesHaveExactlyOneDurableConsumptionWinner(): void
    {
        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $at = new \DateTimeImmutable('2026-09-04T12:02:00+00:00');
            $capability = (new MissionCapabilityIssuanceService($root))->issue($fixture['authorizationId'], $at)[0];
            $encoded = base64_encode(json_encode($capability->toArray(), JSON_THROW_ON_ERROR));
            $gate = $root.'/start.gate';
            $worker = __DIR__.'/Support/canonical_mission_transition_worker.php';
            $arguments = [PHP_BINARY, $worker, $root, $fixture['authorizationId'], $encoded, $at->format(DATE_ATOM), $gate];
            $first = new ConsumerProcess($arguments, $root, 'first');
            $second = new ConsumerProcess($arguments, $root, 'second');
            $first->start(); $second->start();
            file_put_contents($gate, 'start');
            $first->wait(); $second->wait();
            $outcomes = [trim($first->getOutput()), trim($second->getOutput())];
            sort($outcomes);

            self::assertSame(['CONSUMED', 'MIS420_CAPABILITY_CONSUMED'], $outcomes);
            $stored = (new MissionLifecycleStore($root))->readMission($fixture['mission']['mission_id']);
            self::assertSame('ADMITTED', $stored['state']);
            self::assertCount(1, $stored['transition_history']);
            self::assertCount(1, $stored['consumed_nonces']);
            self::assertStringContainsString('proc_open', (string) file_get_contents(dirname(__DIR__, 3).'/tests/Imperium/Runtime/Support/ConsumerProcess.php'));
        } finally { $this->remove($root); }
    }

    private function git(string $repository, array $arguments): string
    {
        $process = proc_open(
            ['git', '-C', $repository, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );
        if (!is_resource($process)) { throw new \RuntimeException('Git process failed to start'); }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
        if (0 !== proc_close($process) || false === $output) { throw new \RuntimeException('Git failed: '.$error); }
        return $output;
    }

    private function root(): string
    {
        $root = sys_get_temp_dir().'/imperium-canonical-auth-b3-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);
        return $root;
    }

    private function fails(string $message, callable $call): void
    {
        try { $call(); self::fail('Expected '.$message); }
        catch (\RuntimeException $error) { self::assertSame($message, $error->getMessage()); }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            if (is_dir($child)) { $this->remove($child); }
            else { @chmod($child, 0660); unlink($child); }
        }
        @chmod($path, 0770);
        rmdir($path);
    }
}
