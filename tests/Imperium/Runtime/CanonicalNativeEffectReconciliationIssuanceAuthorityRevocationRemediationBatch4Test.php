<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor;
use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityReconstructionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapability;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch4Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testMissingWrongAndCounterfeitIssuanceCustodyCannotReachPublication(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))
            ->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $issuer = new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver);

        try {
            $issuer->issue($authorization['decision'], $at + 2);
            self::fail('durable decision was accepted as process custody');
        } catch (\TypeError) {
            self::addToAssertionCount(1);
        }

        $real = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2);
        $counterfeit = new NativeEffectReconciliationIssuanceCapability(
            $real->capabilityId,
            $real->issuanceAuthorityId,
            $real->issuanceAuthorityDigest,
            $real->decisionId,
            $real->decisionDigest,
            $real->admissionId,
            $real->authorityId,
            $real->issuerId,
            $real->effectiveAt,
            $real->expiresAt,
            $real->runtimeProcessId,
            $real->processIncarnationBinding,
        );
        $this->fails('CNE644_RECONCILIATION_ISSUANCE_CAPABILITY_INVALID', fn () => $issuer->issue($counterfeit, $at + 2));
        self::assertSame([], glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
    }

    public function testConsumedCapabilityReplayRefusesWhileFreshExactRetryConverges(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))
            ->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $issuer = new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver);
        $capability = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2);
        $first = $issuer->issue($capability, $at + 2);
        $this->fails('CNE644_RECONCILIATION_ISSUANCE_CAPABILITY_INVALID', fn () => $issuer->issue($capability, $at + 3));

        $freshResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $retry = (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $freshResolver))->issue(
            $freshResolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 3),
            $at + 3,
        );
        self::assertSame('AUTHORIZED', $first['result']);
        self::assertSame('EXACT_RETRY_CONVERGED', $retry['result']);
        self::assertSame($first['authority'], $retry['authority']);
    }

    public function testCompleteReceiptReconstructionJoinsDecisionAuthorityAndBothConsumptionsReadOnly(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliationAuthority($admission, $at + 1, $at + 100);
        $claimResolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $claim = (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $claimResolver))->admit(
            $claimResolver->resolve($issued['authority']['authority_id'], $at + 2),
            $at + 2,
        );
        $receipt = (new NativeEffectForwardRecoveryService($this->state))->forwardComplete($claim['claim_id'], $at + 3);
        $before = $this->treeDigest();
        $proof = (new NativeEffectReconciliationAuthorityReconstructionService($this->state))->reconstruct($receipt['receipt_id']);

        self::assertSame($before, $this->treeDigest());
        self::assertSame($issued['authority']['authority_id'], $proof['issuance_decision']['target']['authority_id']);
        self::assertSame($proof['issuance_authority']['issuance_authority_id'], $proof['issuance_authority_consumption']['source']['id']);
        self::assertTrue($proof['issuance_authority_consumption']['consumed']);
        self::assertTrue($proof['read_only']);
        self::assertFalse($proof['provider_reinvoked']);
        self::assertFalse($proof['credential_resolved']);
        self::assertFalse($proof['continuing_authority']);
    }

    public function testTwoFreshProcessIssuersConvergeOnOneDurableIssuanceConsumption(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $fixture = $this->root.'/batch4-issuer-worker.json';
        file_put_contents($fixture, json_encode([
            'root' => $this->root,
            'admission_id' => $admission['admission_id'],
            'at' => $at + 1,
            'expires_at' => $at + 100,
        ], JSON_THROW_ON_ERROR));
        $results = $this->workers([['resolve-only', $fixture], ['resolve-only', $fixture]]);

        self::assertSame([0, 0], array_column($results, 'code'));
        self::assertCount(1, glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []);
    }

    public function testApplicationConstructionStillRequiresExplicitSharedCustodyAndHasNoExternalEdge(): void
    {
        $corridor = new CanonicalNativeEffectCorridor($this->state);
        $issuanceResolver = $corridor->reconciliationIssuanceAuthorityResolver();
        self::assertInstanceOf(NativeEffectReconciliationIssuanceAuthorityResolver::class, $issuanceResolver);
        self::assertInstanceOf(NativeEffectReconciliationAuthorityIssuanceService::class, $corridor->reconciliationAuthorityIssuer($issuanceResolver));

        $root = dirname(__DIR__, 3);
        $sources = implode("\n", array_map(
            static fn (string $path): string => (string) file_get_contents($path),
            glob($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliation*.php') ?: [],
        ));
        foreach (['HttpClient', 'curl_', 'CredentialBroker', 'AgentMail', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $sources, $forbidden);
        }
    }

    public function testBatchFourProofClosesEveryNamedCaseAndStatesTrustLimits(): void
    {
        $document = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-4-adversarial-proof-v1.md');
        foreach (['AUTH01', 'AUTH10', 'CUR08A', 'CUR08B', 'CUT01', 'CUT07', 'APP01', 'OS01', 'OS02', 'BND02', 'BND03', 'BATCH_4_COMPLETE_ADVERSARIAL_RECONSTRUCTION_PROOF'] as $marker) {
            self::assertStringContainsString($marker, $document, $marker);
        }
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
        $execution = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $execution->execute(
            $outcome['admission_id'],
            $outcome->continuation,
            '{"to":["disposable@example.test"]}',
            'disposable-idempotency-key',
            $at,
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"batch4-new","thread_id":"batch4-new"}', 'observed_at' => $at, 'received_at' => $at],
        ));
        return [$outcome->admission, $at];
    }

    private function treeDigest(): string
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) { $files[str_replace('\\', '/', $file->getPathname())] = hash_file('sha256', $file->getPathname()); }
        }
        ksort($files);
        return hash('sha256', json_encode($files, JSON_THROW_ON_ERROR));
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
