<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationBatch4Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    #[DataProvider('governedCutProvider')]
    public function testRealNativeMutationCannotInterleaveWithGovernedCut(string $mode, string $ready, string $result): void
    {
        [$admission, $at] = $this->sealedResponseForSharedCampaign('batch4-'.$mode);
        $trace = $this->race($mode, $ready, $admission, $at);
        self::assertSame(0, $trace['use_code'], $trace['use_stderr'].' '.$trace['use_tail']);
        self::assertSame(0, $trace['mutation_code'], $trace['mutation_stderr'].' '.$trace['mutation_tail']);
        self::assertSame($result, $trace['payload']['result']);
        self::assertSame('MUTATION_COMMITTED', trim($trace['mutation_tail']));
        self::assertFileExists($trace['marker']);
    }

    public static function governedCutProvider(): iterable
    {
        yield 'DP01' => ['dp01', 'DP01_CURRENTNESS_HELD', 'DECISION_PUBLISHED_BEFORE_MUTATION'];
        yield 'IU01' => ['iu01', 'IU01_CURRENTNESS_HELD', 'AUTHORITY_PUBLISHED_BEFORE_MUTATION'];
        yield 'CU01' => ['cu01', 'CU01_CURRENTNESS_HELD', 'CLAIM_PUBLISHED_BEFORE_MUTATION'];
    }

    public function testIssuanceInterruptionAfterConsumptionConvergesOnlyExactPublication(): void
    {
        [$admission, $at] = $this->sealedResponseForSharedCampaign('batch4-interruption');
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2);
        $service = new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver, static function (string $cut): void {
            if ('issuance_capability.consumed' === $cut) { throw new \RuntimeException('synthetic post-consumption loss'); }
        });
        $this->fails('synthetic post-consumption loss', fn () => $service->issue($capability, $at + 2));
        self::assertSame([], glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
        $fresh = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $retried = (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $fresh))->issue(
            $fresh->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2), $at + 2,
        );
        self::assertSame($authorization['decision']['target']['authority_id'], $retried['authority']['authority_id']);
    }

    public function testChangedValidityWindowConflictsWithEstablishedDeterministicTarget(): void
    {
        [$admission, $at] = $this->sealedResponseForSharedCampaign('batch4-conflict');
        $first = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 90);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2);
        $this->fails('PST111_IMMUTABLE_RECORD_CONFLICT', fn () => (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver))->issue($capability, $at + 2));
        self::assertSame($at + 100, $first['authority']['expires_at']);
    }

    public function testWindowsAndLinuxStorageIdentityLawIsExplicitlyPlatformBounded(): void
    {
        $normalized = str_replace('\\', '/', $this->state->root);
        $expected = hash('sha256', 'Windows' === PHP_OS_FAMILY ? strtolower($normalized) : $normalized);
        self::assertSame($expected, $this->state->identity());
        self::assertStringContainsString(PHP_OS_FAMILY, implode(' ', [PHP_OS_FAMILY, $this->state->identity()]));
    }

    private function race(string $mode, string $ready, array $admission, int $at): array
    {
        $release = $this->root.'/'.$mode.'-release';
        $marker = $this->root.'/'.$mode.'-mutation-committed';
        $useFixture = $this->root.'/'.$mode.'-use.json';
        file_put_contents($useFixture, json_encode(['mode' => $mode, 'root' => $this->root, 'admission_id' => $admission['admission_id'], 'issue_at' => $at + 1, 'resolve_at' => $at + 2, 'use_at' => $at + 3, 'expires_at' => $at + 100, 'release_path' => $release], JSON_THROW_ON_ERROR));
        $use = $this->start(__DIR__.'/Support/reconciliation_shared_exclusion_use_worker.php', $useFixture);
        self::assertSame($ready, trim((string) fgets($use['pipes'][1])));
        $commit = $this->state->get('transitions', $admission['native_root']);
        $chain = $this->state->get('authorities', $commit['authority_id']);
        $act = $this->act; $act['action'] = 'REVOKE'; $act['act_id'] = $mode.'-contending-revoke';
        $mutationFixture = $this->root.'/'.$mode.'-mutation.json';
        file_put_contents($mutationFixture, json_encode(['root' => $this->root, 'at' => $at + 3, 'principal_id' => $chain['principal']['id'], 'envelope' => $this->sign($act), 'marker_path' => $marker], JSON_THROW_ON_ERROR));
        $mutation = $this->start(__DIR__.'/Support/reconciliation_shared_exclusion_mutation_worker.php', $mutationFixture);
        self::assertSame('MUTATION_ATTEMPTING', trim((string) fgets($mutation['pipes'][1])));
        usleep(100000);
        self::assertFileDoesNotExist($marker, 'Mutation committed while governed cut held shared exclusion.');
        file_put_contents($release, 'resume');
        $useTail = trim((string) stream_get_contents($use['pipes'][1]));
        $useStderr = (string) stream_get_contents($use['pipes'][2]);
        foreach ($use['pipes'] as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
        $useCode = proc_close($use['process']);
        $mutationTail = trim((string) stream_get_contents($mutation['pipes'][1]));
        $mutationStderr = (string) stream_get_contents($mutation['pipes'][2]);
        foreach ($mutation['pipes'] as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
        $mutationCode = proc_close($mutation['process']);
        return ['use_code' => $useCode, 'mutation_code' => $mutationCode, 'use_tail' => $useTail, 'mutation_tail' => $mutationTail, 'use_stderr' => $useStderr, 'mutation_stderr' => $mutationStderr, 'payload' => json_decode($useTail, true, 32, JSON_THROW_ON_ERROR), 'marker' => $marker];
    }

    private function start(string $script, string $fixture): array
    {
        $pipes = [];
        $process = proc_open([PHP_BINARY, $script, $fixture], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]); unset($pipes[0]);
        return compact('process', 'pipes');
    }
}
