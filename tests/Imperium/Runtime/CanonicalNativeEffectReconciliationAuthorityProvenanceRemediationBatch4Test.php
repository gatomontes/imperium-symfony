<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityClaimDerivationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityReconstructionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch4Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testIssuerInterruptionLeavesAnUnresolvableOrphanAndFreshMissionCannotRewriteIt(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $checkpoint = static function (string $cut): void {
            if ('authority.published' === $cut) { throw new \RuntimeException('synthetic issuance cut'); }
        };
        $this->fails('synthetic issuance cut', fn () => $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100, $checkpoint));
        self::assertCount(1, glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
        self::assertSame([], glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::ISSUANCES.'/*.json') ?: []);

        $authority = json_decode((string) file_get_contents((glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: [])[0]), true, 64, JSON_THROW_ON_ERROR);
        $this->fails('PST112_IMMUTABLE_RECORD_ABSENT', fn () => (new NativeEffectReconciliationAuthorityResolver($this->state))->resolve($authority['authority_id'], $at + 1));
        $this->fails('PST111_IMMUTABLE_RECORD_CONFLICT', fn () => $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100));
        $this->fails('PST112_IMMUTABLE_RECORD_ABSENT', fn () => (new NativeEffectReconciliationAuthorityResolver($this->state))->resolve($authority['authority_id'], $at + 2));
    }

    public function testCapabilityInterruptionBeforeClaimPublicationLeavesNoDurableConsumption(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $derivation = new NativeEffectReconciliationAuthorityClaimDerivationService($this->state, $resolver, static function (string $cut): void {
            if ('capability.consumed' === $cut) { throw new \RuntimeException('synthetic derivation cut'); }
        });
        $this->fails('synthetic derivation cut', fn () => $derivation->derive($capability, $at + 2));
        self::assertSame([], glob($this->root.'/'.NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS.'/*.json') ?: []);

        $fresh = new NativeEffectReconciliationAuthorityResolver($this->state);
        $claim = (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $fresh))->admit(
            $fresh->resolve($issued['authority']['authority_id'], $at + 3),
            $at + 3,
        );
        self::assertSame($issued['authority']['authority_id'], $claim['authority_consumption']['authority_id']);
    }

    public function testClaimConsumptionInterruptionResumesWithoutASecondCallbackAndReconstructsToRoot(): void
    {
        [$admission, $at, $calls] = $this->sealedResponse();
        $claim = $this->claim($admission, $at + 1, $at + 100);
        $cut = new NativeEffectForwardRecoveryService($this->state, static function (string $point): void {
            if ('claim.consumed' === $point) { throw new \RuntimeException('synthetic receipt cut'); }
        });
        $this->fails('synthetic receipt cut', fn () => $cut->forwardComplete($claim['claim_id'], $at + 2));
        self::assertSame([], glob($this->root.'/'.NativeEffectDoubleExecutionService::RECEIPTS.'/*.json') ?: []);

        $receipt = (new NativeEffectForwardRecoveryService($this->state))->forwardComplete($claim['claim_id'], $at + 3);
        $proof = (new NativeEffectReconciliationAuthorityReconstructionService($this->state))->reconstruct($receipt['receipt_id']);
        self::assertSame($receipt, $proof['receipt']);
        self::assertSame($claim, $proof['forward_recovery_claim']);
        self::assertSame($claim['authority_consumption'], $proof['authority_consumption']);
        self::assertSame($proof['native_principal']['root_act'], $proof['operator_root_act']);
        self::assertTrue($proof['read_only']);
        self::assertFalse($proof['provider_reinvoked']);
        self::assertFalse($proof['credential_resolved']);
        self::assertSame(1, $calls());
    }

    public function testFreshProcessesResolveBeforeConsumptionAndOnlyOneCompetingCapabilityWins(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $fixture = $this->root.'/reconciliation-worker.json';
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        file_put_contents($fixture, json_encode([
            'root' => $this->root,
            'admission_id' => $admission['admission_id'],
            'authority_id' => $issued['authority']['authority_id'],
            'at' => $at + 1,
            'expires_at' => $at + 100,
        ], JSON_THROW_ON_ERROR));

        $resolved = $this->workers([['resolve-only', $fixture], ['resolve-only', $fixture]]);
        self::assertSame([0, 0], array_column($resolved, 'code'));
        self::assertSame([], glob($this->root.'/'.NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS.'/*.json') ?: []);

        $contenders = $this->workers([['derive', $fixture], ['derive', $fixture]]);
        $codes = array_column($contenders, 'code'); sort($codes);
        self::assertSame([0, 3], $codes);
        self::assertCount(1, glob($this->root.'/'.NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS.'/*.json') ?: []);
        self::assertStringContainsString('CNE623_RECONCILIATION_AUTHORITY_CONSUMED', implode('', array_column($contenders, 'stdout')));
    }

    public function testReconstructionSourceIsStrictlyReadOnlyAndNoProviderCapable(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityReconstructionService.php');
        foreach (['->put(', '->consume(', '->bind(', 'CredentialBroker', 'AgentMail', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        foreach (['receipt', 'claim_consumption', 'forward_recovery_claim', 'authority_consumption', 'reconciliation_authority', 'authority_issuance', 'native_authority', 'native_principal', 'operator_root_act'] as $join) {
            self::assertStringContainsString("'".$join."'", $source, $join);
        }
    }

    public function testProofDocumentNamesEveryRequiredAdversarialClass(): void
    {
        $proof = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-authority-provenance-remediation-batch-4-adversarial-application-process-proof-v1.md');
        foreach (['FORGE', 'ROOT', 'LINEAGE', 'CUSTODY', 'CONSUMPTION', 'CUT', 'PROCESS', 'CONTAINER', 'REPLAY', 'NO_PROVIDER', 'BATCH_5_SEPARATELY_SEQUENCED_TERMINAL_AUDIT_NEXT'] as $marker) {
            self::assertStringContainsString($marker, $proof, $marker);
        }
    }

    private function claim(array $admission, int $at, int $expiresAt): array
    {
        $issued = $this->issueReconciliation($admission['admission_id'], $at, $expiresAt);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        return (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit(
            $resolver->resolve($issued['authority']['authority_id'], $at),
            $at,
        );
    }

    private function sealedResponse(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit(
            $authority,
            $credentials->issue($authority, $authority['execution_boundary']['id'], $at),
            $at,
        );
        $calls = 0;
        $execution = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($execution, $outcome, $at, &$calls): void {
            $execution->execute(
                $outcome['admission_id'],
                $outcome->continuation,
                '{"to":["disposable@example.test"]}',
                'disposable-idempotency-key',
                $at,
                static function () use ($at, &$calls): array {
                    ++$calls;
                    return ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"batch4","thread_id":"batch4"}', 'observed_at' => $at, 'received_at' => $at];
                },
            );
        });
        return [$outcome->admission, $at, static fn (): int => $calls];
    }

    /** @param list<array{0: string, 1: string}> $specifications */
    private function workers(array $specifications): array
    {
        $running = [];
        foreach ($specifications as [$mode, $fixture]) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __DIR__.'/Support/reconciliation_authority_worker.php', $mode, $fixture], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $running[] = [$process, $pipes];
        }
        $results = [];
        foreach ($running as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $results[] = ['code' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
        }
        return $results;
    }
}
