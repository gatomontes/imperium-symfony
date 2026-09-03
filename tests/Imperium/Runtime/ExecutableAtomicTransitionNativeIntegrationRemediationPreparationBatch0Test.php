<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Documentary only: no container bootstrap, transition store or authority action. */
final class ExecutableAtomicTransitionNativeIntegrationRemediationPreparationBatch0Test extends TestCase
{
    private const string INVENTORY = 'docs/executable-atomic-transition-native-integration-remediation-preparation-inventory-v1.md';
    private const string HANDOFF = 'docs/handoffs/executable-atomic-transition-native-integration-remediation-preparation-batch-0-complete.md';
    private const string MARKER = 'PREPARATION_BATCH_0_COMPLETE_NATIVE_INTEGRATION_GAPS_CLASSIFIED';

    public function testRequiredLedgerMatchesHandoffExactlyAndAdditionalPathsExist(): void
    {
        $ready = $this->read('docs/handoffs/executable-atomic-transition-native-integration-remediation-campaign-ready.md');
        self::assertStringContainsString('EXECUTABLE_ATOMIC_TRANSITION_NATIVE_INTEGRATION_REMEDIATION_CAMPAIGN_READY', $ready);
        preg_match_all('/^\d+\. `([^`]+)`$/m', $ready, $required);
        self::assertCount(22, $required[1]);
        $inventory = $this->read(self::INVENTORY);
        self::assertSame(1, preg_match('/<!-- REQUIRED_SOURCES_START -->(.*?)<!-- REQUIRED_SOURCES_END -->/s', $inventory, $ledger));
        preg_match_all('/^- `([^`]+)`$/m', $ledger[1], $actual);
        self::assertSame($required[1], $actual[1]);
        preg_match_all('/^- `([^`]+)`/m', $inventory, $paths);
        self::assertSame($paths[1], array_values(array_unique($paths[1])));
        foreach ($paths[1] as $path) {
            self::assertFileExists(dirname(__DIR__, 3).'/'.$path, $path);
        }
        foreach (['FutureInstanceImperatorPrincipalConstitutionService.php',
            'ImperatorPrincipalLifecycleReconstructionService.php',
            'ProviderBindingActivationReconciliationFixtureStore.php',
            'GovernedProviderExecutionCombinedAdmissionService.php'] as $source) {
            self::assertStringContainsString($source, $inventory);
        }
    }

    public function testFindingsUseClosedClassificationVocabularyAndPreserveAbsentJoins(): void
    {
        preg_match_all('/^\| (N\d{2}) \| ([^|]+) \| ([^|]+) \| (.+) \|$/m', $this->read(self::INVENTORY), $rows, PREG_SET_ORDER);
        self::assertCount(30, $rows);
        $classes = [];
        foreach ($rows as $index => $row) {
            self::assertSame(sprintf('N%02d', $index), $row[1]);
            self::assertContains($row[3], ['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY']);
            self::assertNotSame('', trim($row[4]));
            $classes[$row[1]] = $row[3];
        }
        foreach (['N01', 'N09', 'N12', 'N15', 'N18', 'N20', 'N24'] as $id) {
            self::assertSame('ABSENT', $classes[$id], $id);
        }
        foreach (['N03', 'N05', 'N06', 'N07', 'N13', 'N17', 'N21', 'N22', 'N25', 'N26'] as $id) {
            self::assertSame('EXISTS_FRAGMENTED', $classes[$id], $id);
        }
    }

    public function testSourceBoundaryStillDistinguishesNativeAndIsolatedAdmission(): void
    {
        $native = $this->read('src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3Contract.php');
        self::assertStringContainsString('imperium.la-cortine.governed-provider-execution-admission/v3', $native);
        self::assertStringContainsString("STATUS = 'NOT_IMPLEMENTED'", $native);
        self::assertStringContainsString("'execution_admitted' => false", $native);
        self::assertStringContainsString("'provider_invocation_permitted' => false", $native);
        self::assertStringContainsString('imperium.provider-successor-executable-admission/v3', $this->read('src/Imperium/Runtime/ProviderTransition/TransitionConsumer.php'));
        self::assertStringContainsString("- '../src/Imperium/Runtime/ProviderTransition/'", $this->read('config/services.yaml'));
        self::assertStringContainsString("'status' => 'BOUND_INACTIVE'", $this->read('src/Imperium/Runtime/LaCortine/ProviderImplementationBindingService.php'));
        self::assertStringContainsString('UNKNOWN_REPLAY_PROHIBITED', $this->read('src/Imperium/Runtime/ProviderTransition/TransitionReconstructor.php'));
    }

    public function testInventoryRetainsActualWriteSetAndNativeShapeMismatch(): void
    {
        $contract = $this->read('src/Imperium/Runtime/ProviderTransition/TransitionContract.php');
        self::assertSame(1, preg_match('/const array WRITE_SET = \[(.*?)\];/s', $contract, $body));
        preg_match_all("/'([^']+)'/", $body[1], $fields);
        self::assertCount(7, $fields[1]);
        $ordered = [];
        foreach ($fields[1] as $index => $field) {
            $ordered[] = ($index + 1).'. `'.$field.'`';
        }
        $inventory = $this->read(self::INVENTORY);
        self::assertStringContainsString(implode("\n", $ordered), $inventory);
        foreach (['principal_activation_id', 'provider_implementation.provider_id',
            'six-field', 'current-generation', '65536-byte/32-level',
            'absent grant with orphan/pending records'] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
        self::assertStringContainsString("'principal_activation_id'", $this->read('src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationContract.php'));
        self::assertStringContainsString("sealedArtifact(\$principalActivation, 'activation_id'", $this->read('src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciliationContractValidator.php'));
    }

    public function testDirectDescriptorReaderInventoryMatchesSourceSearch(): void
    {
        $actual = [];
        $root = dirname(__DIR__, 3).'/src';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            if (str_contains($source, 'ProviderImplementationBindingService::BINDINGS')) {
                $actual[] = $file->getBasename('.php');
            }
        }
        preg_match_all('/^\| `([A-Za-z][A-Za-z0-9]*)::[a-zA-Z]+\(\)` \|/m', $this->read(self::INVENTORY), $readers);
        $expected = $readers[1];
        sort($actual);
        sort($expected);
        self::assertCount(11, $expected);
        self::assertSame($actual, $expected);
    }

    public function testCompletionConsumersPreserveScopeAndControllingRefusal(): void
    {
        foreach ([self::INVENTORY, self::HANDOFF, 'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md', 'docs/handoffs/README.md'] as $path) {
            $document = $this->read($path);
            foreach ([self::MARKER, 'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $marker) {
                self::assertStringContainsString($marker, $document, $path);
            }
        }
        foreach (['docs/delegate-mission-flow.md', 'todo/blackquill-todos.md', 'docs/handoffs/README.md'] as $path) {
            self::assertStringContainsString(self::HANDOFF, $this->read($path));
        }
        $handoff = preg_replace('/\s+/', ' ', $this->read(self::HANDOFF));
        foreach (['EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT',
            'constant-only scope widening', 'schema relabeling', 'configured hashes as provenance',
            'fixture promotion', 'unread binding projections', 'No Batch 1 implementation is authorized',
            'create or activate a principal or successor', 'issue or consume authority', 'write transition state',
            'implement v3 admission', 'change binding interpretation', 'provision a live grant',
            'handle credentials or capabilities', 'invoke a provider', 'perform external I/O',
            'authorize retry', 'open Iron Gate or Lazaretto'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    public function testFutureSequenceSeparatesNativeSourcesIntegrationAndProof(): void
    {
        $inventory = preg_replace('/\s+/', ' ', $this->read(self::INVENTORY));
        foreach (['Seven planned stages remain', 'Batch 1 — native principal competence',
            'Batch 2 — native successor provenance', 'Batch 3 — canonical v3 admission',
            'Batch 4 — native atomic integration', 'Batch 5 — native contention',
            'Batch 6 — reconstruction', 'Batch 7 — separate terminal Blackquill audit',
            'clean merged Batch 6 main', 'No decision may require the digest of the admission it is authorizing',
            'Native lifecycle/revocation/generation writers must participate',
            'no live conversion, automatic copy, reseal, reset',
            'process termination does not prove physical power-loss durability',
            'PHPUnit must run after each subsequently authorized batch'] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
    }

    private function read(string $path): string
    {
        return str_replace("\r", '', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
