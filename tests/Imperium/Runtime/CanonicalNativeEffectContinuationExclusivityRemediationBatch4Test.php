<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectSemanticIdentity;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch5Test.php';

final class CanonicalNativeEffectContinuationExclusivityRemediationBatch4Test extends CanonicalNativeEffectCorridorActivationBatch5Test
{
    public function testDistinctAuthoritiesInSeparateProcessesProduceOneTupleWinnerAndUnconsumedLoser(): void
    {
        [$winner, $at] = $this->authorityFixtureForRemediation();
        $competitor = $winner;
        $competitor['authority_id'] = 'native-effect-authority-process-competitor';
        $competitor['issuer'] = 'imperator.process-competitor-issuer/v1';
        $competitor = NativeState::seal($competitor);
        self::assertSame(NativeEffectSemanticIdentity::tupleId($winner), NativeEffectSemanticIdentity::tupleId($competitor));

        $first = $this->fixture(['authority' => $winner, 'at' => $at], 'authority-a');
        $second = $this->fixture(['authority' => $competitor, 'at' => $at], 'authority-b');
        $results = $this->runWorkers([['admit', $first], ['admit', $second]]);

        $codes = array_column($results, 'code'); sort($codes);
        self::assertSame([0, 3], $codes);
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::DISPOSITIONS.'/*.json') ?: []);
        self::assertStringContainsString('CNE306_EFFECT_TUPLE_ALREADY_WON', implode('', array_column($results, 'stdout')));
    }

    public function testAdmitAndExitFreshProcessCannotBeginFirstCallback(): void
    {
        [$authority, $at] = $this->authorityFixtureForRemediation();
        $marker = $this->root.'/fresh-process-callback-must-not-run.txt';
        $fixture = $this->fixture([
            'authority' => $authority, 'at' => $at,
            'payload' => '{"to":["disposable@example.test"]}',
            'idempotency_key' => 'disposable-idempotency-key',
            'unexpected_callback_marker' => $marker,
        ], 'admit-exit-first-callback');
        self::assertSame(72, $this->runWorkers([['admit-and-exit', $fixture]])[0]['code']);

        $this->addDurableContinuationMetadata($fixture, $authority);
        $attempt = $this->runWorkers([['first-callback-attempt', $fixture]])[0];
        self::assertSame(3, $attempt['code']);
        self::assertStringContainsString('CNE400_EFFECT_CONTINUATION_INVALID', $attempt['stdout']);
        self::assertFileDoesNotExist($marker);
        self::assertSame([], glob($this->root.'/'.NativeEffectDoubleExecutionService::CALLBACK_STARTS.'/*.json') ?: []);
    }

    public function testResponseSealedProcessLossForwardCompletesWithoutReinvocation(): void
    {
        [$authority, $at] = $this->authorityFixtureForRemediation();
        $marker = $this->root.'/forward-recovery-callback-must-not-run.txt';
        $fixture = $this->fixture([
            'authority' => $authority, 'at' => $at,
            'payload' => '{"to":["disposable@example.test"]}',
            'idempotency_key' => 'disposable-idempotency-key',
            'unexpected_callback_marker' => $marker,
        ], 'response-exit-forward');
        self::assertSame(74, $this->runWorkers([['response-exit', $fixture]])[0]['code']);
        $this->addDurableContinuationMetadata($fixture, $authority, true);
        $this->addForwardRecoveryClaim($fixture, $authority);

        $forward = $this->runWorkers([['forward-recover', $fixture]])[0];
        self::assertSame(0, $forward['code']);
        self::assertFileDoesNotExist($marker);
        self::assertCount(1, glob($this->root.'/'.NativeEffectDoubleExecutionService::RECEIPTS.'/*.json') ?: []);
    }

    public function testTamperedAdmissionAndAuthoritySubstitutionsFailClosed(): void
    {
        [$authority, $at] = $this->authorityFixtureForRemediation();
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit(
            $authority,
            $credentials->issue($authority, $authority['execution_boundary']['id'], $at),
            $at,
        );

        $oldDigest = $authority;
        $oldDigest['expected_return_contract'] = 'attacker/v1';
        $this->fails('CNE110_SEMANTIC_IDENTITY_AUTHORITY_INVALID', static fn () => NativeEffectSemanticIdentity::tupleId($oldDigest));

        $resealed = NativeState::seal($oldDigest);
        $secondCredential = $credentials->issue($resealed, $resealed['execution_boundary']['id'], $at);
        $this->fails('CNE302_EFFECT_AUTHORITY_ALREADY_USED', fn () => (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit($resealed, $secondCredential, $at));

        $path = $this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/'.$outcome['admission_id'].'.json';
        $record = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        $record['receipt_input']['expected_return_contract'] = 'tampered/v1';
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
        $calls = 0;
        $this->fails('PST113_IMMUTABLE_RECORD_TAMPERED', function () use ($continuations, $outcome, $at, &$calls): void {
            (new NativeEffectDoubleExecutionService($this->state, $continuations))->execute(
                $outcome['admission_id'], $outcome->continuation,
                '{"to":["disposable@example.test"]}', 'disposable-idempotency-key', $at,
                static function () use (&$calls): array { ++$calls; return []; },
            );
        });
        self::assertSame(0, $calls);
    }

    public function testPostWinnerCancellationCannotReopenOrReplaceTheTuple(): void
    {
        [$authority, $at] = $this->authorityFixtureForRemediation();
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $service = new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations);
        $service->admit($authority, $credentials->issue($authority, $authority['execution_boundary']['id'], $at), $at);

        $cancelled = $authority;
        $cancelled['cancellation_reference'] = ['id' => 'post-winner-cancellation', 'schema' => 'imperium.refusal/v1', 'digest' => str_repeat('d', 64)];
        $cancelled = NativeState::seal($cancelled);
        $candidate = $credentials->issue($cancelled, $cancelled['execution_boundary']['id'], $at);
        $this->fails('CNE302_EFFECT_AUTHORITY_ALREADY_USED', fn () => $service->admit($cancelled, $candidate, $at));
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
        self::assertSame([], glob($this->root.'/'.NativeEffectDoubleExecutionService::CALLBACK_STARTS.'/*.json') ?: []);
    }

    public function testWorkersAndProductionFacadeHaveNoCredentialNetworkOrCommandBypass(): void
    {
        $root = dirname(__DIR__, 3);
        $worker = (string) file_get_contents(__DIR__.'/Support/canonical_native_effect_worker.php');
        $corridor = (string) file_get_contents($root.'/src/Imperium/Runtime/NativeEffect/CanonicalNativeEffectCorridor.php');
        $double = (string) file_get_contents($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectDoubleExecutionService.php');
        foreach (['AgentMail', 'CredentialBroker', 'HttpClient', 'getenv(', '$_ENV', '$_SERVER', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $worker.$corridor.$double, $forbidden);
        }
        foreach (glob($root.'/src/Command/*.php') ?: [] as $command) {
            self::assertStringNotContainsString('canonical-native-effect', (string) file_get_contents($command), basename($command));
        }
        self::assertStringContainsString('NativeEffectContinuationCapabilityIssuer $continuations', $corridor);
        self::assertStringNotContainsString('array $authority', $double);
    }

    public function testNoFilesystemContinuationLockIsHeldAcrossProviderDouble(): void
    {
        [$authority, $at] = $this->authorityFixtureForRemediation();
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit(
            $authority,
            $credentials->issue($authority, $authority['execution_boundary']['id'], $at),
            $at,
        );
        $scope = 'canonical-native-effect-continuation:'.hash('sha256', $outcome['admission_id']);
        $path = $this->root.'/var/imperium/runtime/transition-locks/'.hash('sha256', $scope).'.lock';
        $lockWasFree = false;

        (new NativeEffectDoubleExecutionService($this->state, $continuations))->execute(
            $outcome['admission_id'], $outcome->continuation,
            '{"to":["disposable@example.test"]}', 'disposable-idempotency-key', $at,
            static function () use ($path, &$lockWasFree, $at): array {
                $handle = fopen($path, 'c+');
                self::assertIsResource($handle);
                $lockWasFree = flock($handle, LOCK_EX | LOCK_NB);
                if ($lockWasFree) { flock($handle, LOCK_UN); }
                fclose($handle);
                return ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m-lock","thread_id":"t-lock"}', 'observed_at' => $at, 'received_at' => $at];
            },
        );
        self::assertTrue($lockWasFree, 'Provider callback executed while the continuation filesystem lock was held.');
    }

    private function authorityFixtureForRemediation(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        return [$this->effectAuthority($native['root'], $at), $at];
    }

    private function fixture(array $data, string $name): string
    {
        $path = $this->root.'/'.$name.'.json';
        $data['root'] = $this->root;
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));
        return $path;
    }

    private function addDurableContinuationMetadata(string $fixture, array $authority, bool $afterExpiry = false): void
    {
        $data = json_decode((string) file_get_contents($fixture), true, 64, JSON_THROW_ON_ERROR);
        $id = NativeEffectSemanticIdentity::admissionId(NativeEffectSemanticIdentity::tupleId($authority));
        $admission = json_decode((string) file_get_contents($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/'.$id.'.json'), true, 64, JSON_THROW_ON_ERROR);
        $data['admission_id'] = $id;
        $data['continuation_metadata'] = [
            'capability_id' => 'native-effect-continuation-reconstructed-lookalike',
            'admission_id' => $id,
            'admission_digest' => $admission['record_digest'],
            'semantic_effect_tuple_id' => $admission['semantic_effect_tuple_id'],
            'authority_consumption_id' => $admission['authority_consumption_id'],
            'process_boundary_id' => $authority['execution_boundary']['id'],
            'expires_at' => $admission['expires_at'],
        ];
        if ($afterExpiry) { $data['at'] = $admission['expires_at'] + 100; }
        file_put_contents($fixture, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function addForwardRecoveryClaim(string $fixture, array $authority): void
    {
        $data = json_decode((string) file_get_contents($fixture), true, 64, JSON_THROW_ON_ERROR);
        $admissionId = NativeEffectSemanticIdentity::admissionId(NativeEffectSemanticIdentity::tupleId($authority));
        $admission = json_decode((string) file_get_contents($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/'.$admissionId.'.json'), true, 64, JSON_THROW_ON_ERROR);
        $at = $data['at'];
        $issued = $this->issueReconciliationAuthority($admission, $at, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $claim = (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit(
            $resolver->resolve($issued['authority']['authority_id'], $at),
            $at,
        );
        $data['forward_recovery_claim_id'] = $claim['claim_id'];
        file_put_contents($fixture, json_encode($data, JSON_THROW_ON_ERROR));
    }

    /** @param list<array{0: string, 1: string}> $specifications */
    private function runWorkers(array $specifications): array
    {
        $processes = [];
        foreach ($specifications as [$mode, $fixture]) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __DIR__.'/Support/canonical_native_effect_worker.php', $mode, $fixture], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $results[] = ['code' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
        }
        return $results;
    }
}
