<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Documentary graph evidence only. No runtime service or container is executed. */
final class ExecutableAtomicTransitionCanonicalConsumerIntegrationCorrectionPreparationBatch0Test extends TestCase
{
    private const string PREFIX = 'executable-atomic-transition-canonical-consumer-integration-correction';
    private const string MARKER = 'PREPARATION_BATCH_0_COMPLETE_CANONICAL_CONSUMER_BYPASS_CLASSIFIED';

    public function testReadingLedgerMatchesEveryRequiredSourceAndCoversSelectedCorridors(): void
    {
        $ready = $this->read('docs/handoffs/'.self::PREFIX.'-campaign-ready.md');
        self::assertStringContainsString('EXECUTABLE_ATOMIC_TRANSITION_CANONICAL_CONSUMER_INTEGRATION_CORRECTION_CAMPAIGN_READY', $ready);
        preg_match_all('/^\d+\. `([^`]+)`$/m', $ready, $required);
        $ledger = json_decode($this->read('docs/'.self::PREFIX.'-reading-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(20, $required[1]);
        self::assertSame($required[1], $ledger['required_sources']);
        $paths = array_column($ledger['sources'], 'path');
        self::assertSame($paths, array_values(array_unique($paths)));
        foreach ($ledger['sources'] as $source) {
            self::assertSame('FULLY_READ', $source['read_status']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $source['sha256']);
            self::assertGreaterThan(0, $source['lines']);
            // The local skill is reading provenance, not a portable repository dependency.
            if ('LOCAL_SKILL' !== $source['role']) {
                self::assertFileExists(dirname(__DIR__, 3).'/'.$source['path']);
            }
        }
        foreach ($required[1] as $path) {
            self::assertContains($path, $paths);
            self::assertSame('REQUIRED', $ledger['sources'][array_search($path, $paths, true)]['role']);
        }
        foreach (['src/Kernel.php', 'bin/console', 'config/services_sortie.yaml',
            'src/Command/AgentMailEmailSendCommand.php', 'src/Command/DeterministicHttpPostSmokeCommand.php',
            'src/Imperium/Runtime/LaCortine/DeterministicBoundaryExecutor.php',
            'src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php',
            'src/Imperium/Runtime/LaCortine/AgentMailIdempotencyHeaderAdapter.php',
            'src/Imperium/Runtime/LaCortine/DeterministicReceiptReconstructionService.php',
            'src/Imperium/Runtime/Citadel/DeepSeekDelegatePlatformAdapter.php',
            'src/Imperium/Runtime/Sortie/BrokeredSortieCognitionProviderInvoker.php'] as $path) {
            self::assertContains($path, $paths, $path);
        }
    }

    public function testFindingsAndReaderRowsUseClosedClassifications(): void
    {
        $inventory = $this->inventory();
        preg_match_all('/^\| (C\d{2}) \| ([^|]+) \| (.+) \|$/m', $inventory, $rows, PREG_SET_ORDER);
        self::assertCount(31, $rows);
        foreach ($rows as $index => $row) {
            self::assertSame(sprintf('C%02d', $index), $row[1]);
            self::assertContains($row[2], ['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY']);
            self::assertNotSame('', trim($row[3]));
        }
        foreach (['C05', 'C17', 'C20', 'C27', 'C28'] as $id) {
            self::assertStringContainsString('| '.$id.' | ABSENT |', $inventory);
        }
        foreach (['D' => 11, 'A' => 5, 'E' => 10] as $prefix => $count) {
            preg_match_all('/^\| '.$prefix.'\d{2} \|.*$/m', $inventory, $entries);
            self::assertCount($count, $entries[0]);
            foreach ($entries[0] as $entry) {
                self::assertMatchesRegularExpression('/\| (EXISTS_CANONICALLY|EXISTS_FRAGMENTED|ABSENT|DEFERRED_BOUNDARY) \|/', $entry);
            }
        }
    }

    public function testAllDirectDescriptorReadersAreInventoriedAndFullyRead(): void
    {
        $actual = [];
        $ledger = json_decode($this->read('docs/'.self::PREFIX.'-reading-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        $paths = array_column($ledger['sources'], 'path');
        $root = dirname(__DIR__, 3);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src', \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (str_contains($source, 'ProviderImplementationBindingService::BINDINGS')) {
                $actual[] = $file->getBasename('.php');
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                self::assertContains($relative, $paths);
                // The v1 inventory is historical; correction now guards ten effect-side readers.
                if ('GovernedToolResultReconstructionService' !== $file->getBasename('.php')) {
                    self::assertStringContainsString('NativeBindingReader', $source);
                    self::assertStringContainsString('->legacy(', $source);
                }
            }
        }
        preg_match_all('/^\| D\d{2} \| `([A-Za-z][A-Za-z0-9]*)::[A-Za-z]+\(\)` \|/m', $this->inventory(), $expected);
        $expected = $expected[1];
        sort($actual);
        sort($expected);
        self::assertCount(11, $expected);
        self::assertSame($expected, $actual);
    }

    public function testEffectCorridorEvidenceIncludesTheCallbackAndGenericBypasses(): void
    {
        $executor = $this->read('src/Imperium/Runtime/LaCortine/DeterministicBoundaryExecutor.php');
        foreach (['$this->ironGate->dispatch(', '$this->credentialBroker->consume(', '$transport->execute('] as $edge) {
            self::assertStringContainsString($edge, $executor);
        }
        $broker = $this->read('src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php');
        foreach (['DeterministicEffectStartJournalService::JOURNALS', 'DeterministicExecutionClaimService::CLAIMS',
            '$this->credentials->consume(', '$this->adapter->invoke(', '$providerCallback($request)', 'UNKNOWN_REPLAY_PROHIBITED'] as $edge) {
            self::assertStringContainsString($edge, $broker);
        }
        self::assertStringContainsString('CCI_EMAIL_REQUEST_HAS_NO_BINDING_ROOT', $executor);
        self::assertStringContainsString('NativeBindingReader', $broker);
        self::assertStringContainsString('$this->inspectClaim(', $broker);
        $request = $this->read('src/Imperium/Runtime/LaCortine/OutboundRequest.php');
        self::assertStringNotContainsString('bindingId', $request);
        self::assertStringNotContainsString('instanceId', $request);
        $command = $this->read('src/Command/AgentMailEmailSendCommand.php');
        self::assertStringContainsString('GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE', $command);
        self::assertStringContainsString('return Command::FAILURE;', $command);
        self::assertStringContainsString('inspect-claim', $command);
        $transport = $this->read('src/Imperium/Runtime/LaCortine/AgentMailEmailTransport.php');
        self::assertStringContainsString("'email.send' === \$operation", $transport);
        self::assertStringContainsString('CCI_EMAIL_TRANSPORT_HAS_NO_BINDING_ROOT', $transport);
        self::assertStringNotContainsString('AgentMailProviderRequestEncoder', $transport);
        foreach (['E03', 'E04', 'E05', 'A02', 'C17'] as $id) {
            self::assertStringContainsString('| '.$id.' |', $this->inventory());
        }
    }

    public function testWiringEvidenceDoesNotPromoteCommandSelfConsumption(): void
    {
        $services = $this->read('config/services.yaml');
        self::assertStringContainsString("resource: '../src/'", $services);
        self::assertStringContainsString("- '../src/Imperium/Runtime/ProviderTransition/'", $services);
        self::assertStringContainsString('NativeBindingReader', $services);
        $native = $this->read('src/Command/ImperiumNativeProviderTransitionCommand.php');
        self::assertStringContainsString('new NativeConsumer', $native);
        self::assertStringContainsString('new NativeState', $native);
        self::assertStringContainsString('new Application($kernel)', $this->read('bin/console'));
        foreach (['Real Kernel/container', 'Console Application', 'zero credential/transport/IronGate/Lazaretto calls',
            'not only direct construction', 'No production orchestrator', 'No Batch 1 implementation is authorized'] as $evidence) {
            self::assertStringContainsString($evidence, $this->inventory());
        }
    }

    public function testUnrelatedMeaningsHavePositiveCallGraphEvidence(): void
    {
        self::assertStringContainsString("OPERATION = 'deepseek.model.invoke'", $this->read('src/Imperium/Runtime/Citadel/DeepSeekDelegatePlatformAdapter.php'));
        self::assertStringContainsString("'sortie.'.DeepSeekDelegatePlatformAdapter::OPERATION", $this->read('src/Imperium/Runtime/Sortie/BrokeredSortieCognitionProviderInvoker.php'));
        self::assertStringContainsString("'http.post.json' === \$operation", $this->read('src/Imperium/Runtime/LaCortine/BearerJsonPostTransport.php'));
        self::assertStringContainsString('@App\Imperium\Runtime\Sortie\HttpGetSortieToolExecutor', $this->read('config/services_sortie.yaml'));
        foreach (['mission occupancy', 'manifest-derived', 'receipt binding', 'same binding', 'email.send'] as $evidence) {
            self::assertStringContainsStringIgnoringCase($evidence, $this->inventory());
        }
    }

    public function testStateReplayAndOrderedCorrectionObligationsRemainExplicit(): void
    {
        $inventory = $this->inventory();
        foreach (['BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED',
            'COMMITTED_NOT_CURRENT', 'BOUND_ACTIVE_FOR_EXACT_OPERATION', 'ambiguous',
            'agentmail-api-token', 'agentmail.api-key.v1', 'before publish', 'after publish',
            'physical power-loss', 'original descriptor remains immutable',
            'No decision may depend on the digest of its future', 'Four planned stages remain',
            'Batch 1 — canonical interpretation boundary', 'Batch 2 — established-consumer integration',
            'Batch 3 — application and adversarial proof', 'Batch 4 — separate terminal Blackquill audit',
            'clean merged Batch 3', 'PHPUnit must run after each subsequently authorized batch'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $inventory, $boundary);
        }
        self::assertStringContainsString("STATUS = 'NOT_IMPLEMENTED'", $this->read('src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3Contract.php'));
        self::assertStringContainsString("'status' => 'BOUND_INACTIVE'", $this->read('src/Imperium/Runtime/LaCortine/ProviderImplementationBindingService.php'));
    }

    public function testCompletionIsPublishedWithoutRestoringTerminalAcceptance(): void
    {
        $handoff = 'docs/handoffs/'.self::PREFIX.'-preparation-batch-0-complete.md';
        foreach ([$handoff, 'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md', 'docs/handoffs/README.md'] as $path) {
            $document = $this->read($path);
            self::assertStringContainsString(self::MARKER, $document, $path);
            self::assertStringContainsString('NATIVE_INTEGRATION_TERMINAL_AUDIT_REFUSED_CANONICAL_CONSUMER_NOT_INTEGRATED', $document);
            foreach (['BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
                self::assertStringContainsString($boundary, $document);
            }
            if ($path !== $handoff) {
                self::assertStringContainsString($handoff, $document);
            }
        }
    }

    private function inventory(): string
    {
        return $this->read('docs/'.self::PREFIX.'-preparation-inventory-v1.md');
    }

    private function read(string $path): string
    {
        return str_replace("\r", '', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
