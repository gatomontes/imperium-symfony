<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectContinuationExclusivityRemediationBatch5Test extends TestCase
{
    public function testTerminalAuditResolvesEachFindingWithoutFakingFormalClosure(): void
    {
        $audit = $this->read('docs/canonical-native-effect-continuation-exclusivity-remediation-batch-5-blackquill-audit-v1.md');
        foreach (['BQ-CNE-01', 'BQ-CNE-02', 'BQ-CNE-03', 'BQ-CNE-04', 'Additional lock objection', 'RESOLVED_LOCALLY'] as $finding) {
            self::assertStringContainsString($finding, $audit, $finding);
        }
        foreach (['bureaucratic theater', 'clean merged Batch 4', 'no CI run', 'same workspace and agent', '`BLOCKED`'] as $limit) {
            self::assertStringContainsStringIgnoringCase($limit, $audit, $limit);
        }
    }

    public function testEvidenceLedgerSeparatesHistoricalLocalCiAndCurrentLocalRuns(): void
    {
        $ledger = json_decode($this->read('docs/canonical-native-effect-continuation-exclusivity-remediation-evidence-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($ledger['ci_result']);
        self::assertSame(48255, $ledger['historical_batch_6_evidence']['local']['assertions']);
        self::assertSame(48253, $ledger['historical_batch_6_evidence']['github_actions_run_33813014897']['assertions']);
        $results = array_column($ledger['local_runs'], 'result');
        self::assertContains('FAIL', $results);
        self::assertContains('OK', $results);
        self::assertContains('Batch 5 focused', array_column($ledger['local_runs'], 'stage'));
        self::assertContains('Final full repository', array_column($ledger['local_runs'], 'stage'));
        self::assertNotEmpty($ledger['provenance_limits']);
    }

    public function testAllBatchArtifactsAndStopsExist(): void
    {
        foreach (range(1, 4) as $batch) {
            $matches = glob($this->root().'/docs/canonical-native-effect-continuation-exclusivity-remediation-batch-'.$batch.'-*.md') ?: [];
            self::assertNotEmpty($matches, 'Batch '.$batch.' document');
            $handoffs = glob($this->root().'/docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-batch-'.$batch.'-complete.md') ?: [];
            self::assertCount(1, $handoffs, 'Batch '.$batch.' handoff');
        }
        $handoff = $this->read('docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-batch-5-local-audit-complete-formal-closure-blocked.md');
        foreach (['FORMAL_CLOSURE_BLOCKED', 'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED', 'No live authority is restored', 'php vendor/bin/phpunit tests'] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $handoff, $stop);
        }
    }

    public function testCorrectedRuntimeHasNoCallerAuthorityOrExternalProviderEdge(): void
    {
        $root = $this->root();
        $double = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectDoubleExecutionService.php');
        self::assertStringNotContainsString('array $authority', $double);
        self::assertStringContainsString('NativeEffectContinuationCapability $continuation', $double);
        self::assertStringContainsString("'receipt_input'", $double);
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $double, $forbidden);
        }
        foreach (glob($root.'/src/Command/*.php') ?: [] as $command) {
            self::assertStringNotContainsString('imperium:canonical-native-effect', (string) file_get_contents($command), basename($command));
        }
    }

    public function testLockOrderAndNoLockAcrossCallbackRemainExplicitlyProved(): void
    {
        $admission = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectAtomicAdmissionService.php');
        $positions = [
            strpos($admission, '$this->state->locked'),
            strpos($admission, "'canonical-native-effect-authority:"),
            strpos($admission, "'canonical-native-effect-tuple:"),
            strpos($admission, '$this->records->put(self::ADMISSIONS'),
            strpos($admission, '$this->continuations->issueForNewWinner'),
        ];
        foreach ($positions as $position) { self::assertIsInt($position); }
        self::assertSame($positions, array_values(array_unique($positions)));
        $sorted = $positions; sort($sorted);
        self::assertSame($sorted, $positions);
        $proof = $this->read('tests/Imperium/Runtime/CanonicalNativeEffectContinuationExclusivityRemediationBatch4Test.php');
        self::assertStringContainsString('testNoFilesystemContinuationLockIsHeldAcrossProviderDouble', $proof);
        self::assertStringContainsString('LOCK_EX | LOCK_NB', $proof);
    }

    private function read(string $path): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents($this->root().'/'.$path));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
