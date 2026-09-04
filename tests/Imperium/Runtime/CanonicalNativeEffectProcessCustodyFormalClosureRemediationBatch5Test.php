<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch5Test extends TestCase
{
    public function testCanonicalConsumersPointToTheSeparatelySequencedAuditCandidate(): void
    {
        $canonical = $this->read('docs/delegate-mission-flow.md')
            .$this->read('docs/handoffs/README.md')
            .$this->read('todo/blackquill-todos.md');
        self::assertStringContainsString('CANONICAL_NATIVE_EFFECT_PROCESS_CUSTODY_FORMAL_CLOSURE_REMEDIATION_BATCH_5_CI_CANDIDATE', $canonical);
        self::assertStringContainsString('2368', $canonical);
        self::assertStringContainsString('50072', $canonical);
        self::assertStringContainsString('BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED', $canonical);
    }

    public function testAuditAndLedgerWithholdClosureUntilExactCiEvidenceExists(): void
    {
        $audit = $this->read('docs/canonical-native-effect-process-custody-formal-closure-remediation-batch-5-terminal-audit-candidate-v1.md');
        self::assertStringContainsString('TERMINAL_ACCEPTANCE_PENDING_SHA_BOUND_GITHUB_CI', $audit);
        foreach (['7eec2a3', '642b29e', 'f07b7d7', 'eda148d', 'e73d100', 'ce8fd9e', '96b3079', 'b66edd9', 'c00e02c', '83fc4d6'] as $commit) {
            self::assertStringContainsString($commit, $audit);
        }
        $ledger = json_decode($this->read('docs/canonical-native-effect-process-custody-formal-closure-remediation-evidence-ledger-v2.json'), true, 32, JSON_THROW_ON_ERROR);
        self::assertNull($ledger['github_ci']);
        self::assertSame('PENDING_SHA_BOUND_GITHUB_CI', $ledger['terminal_verdict']);
        self::assertFalse($ledger['live_effect']);
        self::assertSame('SUSPENDED', $ledger['batch_7']);
    }

    public function testTerminalCandidateAddsNoProviderOrCredentialEdge(): void
    {
        $source = '';
        foreach (glob($this->root().'/src/Imperium/Runtime/ProviderTransition/NativeEffect*.php') ?: [] as $path) {
            $source .= (string) file_get_contents($path);
        }
        foreach (['AgentMailEmailTransport', 'CredentialBroker', 'curl_exec', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    private function read(string $relative): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($this->root().'/'.$relative));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
