<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleDispositionContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalProvenanceFixtureStore;
use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativePrincipal;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationPreparationBatch0Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testDP01IsAnOrderingHazardButAcceptedBaseHasNoDecisionPublicationSurface(): void
    {
        $root = dirname(__DIR__, 3);
        self::assertFileDoesNotExist($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorizationService.php');
        self::assertFileDoesNotExist($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuancePublicationService.php');
        self::assertStringContainsString('CONTRACT_ONLY_NOT_CONSUMED_OR_PUBLISHED', (string) file_get_contents($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceConsumptionPublicationContract.php'));
        self::assertSame([], glob($this->root.'/var/imperium/runtime/canonical-native-effect-reconciliation-issuance-decisions/*.json') ?: []);
    }

    public function testIU01IsDeferredBecauseAcceptedBaseHasNoOperationalIssuanceCapability(): void
    {
        $root = dirname(__DIR__, 3);
        self::assertFileDoesNotExist($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceCapability.php');
        self::assertFileDoesNotExist($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorityResolver.php');
        self::assertStringContainsString('CONTRACT_ONLY_NOT_DELIVERED', (string) file_get_contents($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceCapabilityCustodyContract.php'));
        self::assertSame([], glob($this->root.'/var/imperium/runtime/canonical-native-effect-reconciliation-issuance-authorities/*.json') ?: []);
    }

    public function testCU01RealNativeRevocationCommitsBetweenResolutionAndStaleClaimPublication(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $result = $this->runCu01($admission['admission_id'], $at, function () use ($admission, $at): void {
            $commit = $this->state->get('transitions', $admission['native_root']);
            $chain = $this->state->get('authorities', $commit['authority_id']);
            $act = $this->act;
            $act['action'] = 'REVOKE';
            $act['act_id'] = 'cu01-native-revoke';
            (new NativePrincipal($this->state, static fn (): int => $at + 3))->lifecycle(
                $chain['principal']['id'],
                $this->sign($act),
            );
            self::assertNotNull($this->state->get('revocations', $chain['principal']['id']));
        });

        self::assertSame(0, $result['code'], $result['stderr'].' '.$result['stdout']);
        self::assertSame('CU01_CURRENTNESS_RESOLUTION_PASSED', $result['lines'][0]);
        self::assertSame('STALE_CLAIM_PUBLISHED', $result['payload']['result']);
        self::assertSame($at + 3, $result['payload']['consumed_at']);
    }

    public function testCU01RealSourceLifecycleWriterCommitsBetweenResolutionAndStaleClaimPublication(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $result = $this->runCu01($admission['admission_id'], $at, function () use ($at): void {
            $record = NativeState::seal([
                'schema' => ImperatorPrincipalLifecycleDispositionContract::SCHEMA,
                'disposition_id' => 'cu01-source-suspend',
                'instance_id' => $this->source['instance_id'],
                'operator_root' => $this->source['source_operator_root'],
                'source_principal_version' => NativeState::ref($this->source, 'principal_version_id'),
                'source_status' => 'ACTIVE',
                'disposition' => 'SUSPEND',
                'rationale' => 'Deterministic shared-exclusion preparation race.',
                'effective_at' => gmdate(DATE_ATOM, $at + 3),
                'successor_principal_version' => null,
                'authority_scope_changed' => false,
                'historical_attribution_preserved' => true,
                'caller_authority_issuance_permitted_after_effective_at' => false,
                'external_action_performed' => false,
                'sealed' => true,
            ]);
            $stored = (new ImperatorPrincipalProvenanceFixtureStore($this->root))->putLifecycleDisposition($record);
            self::assertSame('SUSPEND', $stored['disposition']);
        });

        self::assertSame(0, $result['code'], $result['stderr'].' '.$result['stdout']);
        self::assertSame('CU01_CURRENTNESS_RESOLUTION_PASSED', $result['lines'][0]);
        self::assertSame('STALE_CLAIM_PUBLISHED', $result['payload']['result']);
    }

    public function testPreparationArtifactsUseOnlyTheFiveAuthorizedClassifications(): void
    {
        $root = dirname(__DIR__, 3);
        $paths = [
            'docs/canonical-native-effect-reconciliation-shared-exclusion-lock-identity-inventory-v1.md',
            'docs/canonical-native-effect-reconciliation-shared-exclusion-call-graph-v1.md',
            'docs/canonical-native-effect-reconciliation-shared-exclusion-mutation-matrix-v1.md',
            'docs/canonical-native-effect-reconciliation-shared-exclusion-lock-order-deadlock-matrix-v1.md',
            'docs/canonical-native-effect-reconciliation-shared-exclusion-preparation-evidence-ledger-v1.json',
            'docs/handoffs/canonical-native-effect-reconciliation-shared-exclusion-remediation-preparation-batch-0-complete.md',
        ];
        $all = '';
        foreach ($paths as $path) {
            self::assertFileExists($root.'/'.$path, $path);
            $all .= (string) file_get_contents($root.'/'.$path);
        }
        foreach (['SHARED_EXCLUSION_PROVED', 'DISJOINT_LOCK_RACE_REPRODUCED', 'ORDERING_HAZARD', 'EXISTS_SEQUENTIAL_ONLY', 'DEFERRED_BOUNDARY'] as $classification) {
            self::assertStringContainsString($classification, $all, $classification);
        }
        foreach (['DP01', 'IU01', 'CU01', 'five remaining stages', 'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_SHARED_EXCLUSION_RACES_CLASSIFIED'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $all, $marker);
        }
    }

    private function sealedResponse(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $effectAuthority = $this->effectAuthority($native['root'], $at);
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit(
            $effectAuthority,
            $credentials->issue($effectAuthority, $effectAuthority['execution_boundary']['id'], $at),
            $at,
        );
        $execution = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $execution->execute(
            $outcome['admission_id'],
            $outcome->continuation,
            '{"to":["disposable@example.test"]}',
            'disposable-idempotency-key',
            $at,
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"shared-exclusion","thread_id":"shared-exclusion"}', 'observed_at' => $at, 'received_at' => $at],
        ));
        return [$outcome->admission, $at];
    }

    private function runCu01(string $admissionId, int $at, callable $mutate): array
    {
        $release = $this->root.'/cu01-release';
        $fixture = $this->root.'/cu01-fixture.json';
        file_put_contents($fixture, json_encode([
            'root' => $this->root,
            'admission_id' => $admissionId,
            'issue_at' => $at + 1,
            'resolve_at' => $at + 2,
            'use_at' => $at + 3,
            'expires_at' => $at + 100,
            'release_path' => $release,
        ], JSON_THROW_ON_ERROR));
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, __DIR__.'/Support/reconciliation_shared_exclusion_cu01_worker.php', $fixture],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $ready = trim((string) fgets($pipes[1]));
        self::assertSame('CU01_CURRENTNESS_RESOLUTION_PASSED', $ready);
        $mutate();
        file_put_contents($release, 'resume');
        $tail = trim((string) stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($process);
        $lines = [$ready, ...array_values(array_filter(explode("\n", $tail)))];
        $payload = json_decode($lines[1] ?? '{}', true, 32, JSON_THROW_ON_ERROR);
        return compact('code', 'lines', 'payload', 'stderr') + ['stdout' => implode("\n", $lines)];
    }
}
