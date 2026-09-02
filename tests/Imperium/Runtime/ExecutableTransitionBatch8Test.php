<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer, TransitionContract};
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/ExecutableTransitionBatch1Test.php';

/** Terminal audit evidence: isolated success must not be reported as native adoption. */
final class ExecutableTransitionBatch8Test extends TestCase
{
    public function testPinnedProtocolSuccessDoesNotEstablishNativeAdmission(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            $grant = ExecutableTransitionBatch1Test::grant($directory); $pin = TransitionContract::digest($grant);
            $store = new TransitionStore($directory); $store->locked(fn () => $store->put('grant', $grant));
            $custody = new TransitionAuthority($store, $pin, static fn () => 150); $custody->issue();
            $commit = (new TransitionConsumer($store, $custody, static fn () => 150))->execute($pin);
            self::assertNotSame(GovernedProviderExecutionSuccessorAdmissionV3Contract::SCHEMA, $commit['records']['v3_admission']['schema']);
            self::assertSame('NOT_IMPLEMENTED', GovernedProviderExecutionSuccessorAdmissionV3Contract::STATUS);
            self::assertDirectoryDoesNotExist($directory.'/var/imperium/offices');
            $names = array_map('basename', glob($directory.'/*')); sort($names);
            self::assertSame(['authority.json', 'commit.json', 'domain.lock', 'grant.json', 'journal.json'], $names);
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public function testTerminalDecisionRefusesNativeClosureAndPreservesOptInBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['docs/provider-binding-successor-executable-atomic-transition-batch-8-terminal-audit-v1.md',
            'docs/handoffs/provider-binding-successor-executable-atomic-transition-terminal-audit-refused.md',
            'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md'] as $path) {
            $document = file_get_contents($root.'/'.$path);
            self::assertStringContainsString('EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT', $document);
            foreach (['BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
                self::assertStringContainsString($boundary, $document);
            }
        }
        self::assertStringContainsString("- '../src/Imperium/Runtime/ProviderTransition/'", file_get_contents($root.'/config/services.yaml'));
    }
}
