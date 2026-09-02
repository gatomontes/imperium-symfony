<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Documentary preparation only: no application bootstrap or runtime actions. */
final class ProviderBindingSuccessorExecutableAtomicTransitionPreparationBatch0Test extends TestCase
{
    private const string INVENTORY = 'docs/provider-binding-successor-executable-atomic-transition-preparation-inventory-v1.md';
    private const string HANDOFF = 'docs/handoffs/provider-binding-successor-executable-atomic-transition-preparation-batch-0-complete.md';
    private const string MARKER = 'PREPARATION_BATCH_0_COMPLETE_EXECUTABLE_ATOMIC_TRANSITION_BOUNDARY_CLASSIFIED';

    public function testRequiredReadingLedgerExactlyMatchesTheReadyHandoff(): void
    {
        $ready = $this->read('docs/handoffs/provider-binding-successor-executable-atomic-transition-campaign-ready.md');
        preg_match_all('/^\d+\. `([^`]+)`$/m', str_replace("\r", '', $ready), $required);
        self::assertCount(19, $required[1]);
        $inventory = $this->read(self::INVENTORY);
        self::assertSame(1, preg_match('/<!-- REQUIRED_SOURCES_START -->(.*?)<!-- REQUIRED_SOURCES_END -->/s', $inventory, $ledger));
        preg_match_all('/^- `([^`]+)`$/m', $ledger[1], $actual);
        self::assertSame($required[1], $actual[1]);
        preg_match_all('/^- `([^`]+)`/m', $inventory, $paths);
        foreach ($paths[1] as $path) {
            self::assertFileExists(dirname(__DIR__, 3).'/'.$path, $path);
        }
    }

    public function testEveryFindingHasOneAllowedClassificationAndKeyGapsStayExplicit(): void
    {
        preg_match_all('/^\| (E\d{2}) \| ([^|]+) \| ([^|]+) \| (.+) \|$/m', $this->read(self::INVENTORY), $rows, PREG_SET_ORDER);
        self::assertCount(32, $rows);
        $classifications = [];
        foreach ($rows as $index => $row) {
            self::assertSame(sprintf('E%02d', $index), $row[1]);
            self::assertContains($row[3], ['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY']);
            self::assertNotSame('', trim($row[4]));
            $classifications[$row[1]] = $row[3];
        }
        foreach (['E00', 'E03', 'E07', 'E17', 'E22', 'E24'] as $id) {
            self::assertSame('ABSENT', $classifications[$id], $id);
        }
        foreach (['E02', 'E04', 'E09', 'E10', 'E12', 'E19', 'E21', 'E26', 'E27', 'E29'] as $id) {
            self::assertSame('EXISTS_FRAGMENTED', $classifications[$id], $id);
        }
        self::assertSame('DEFERRED_BOUNDARY', $classifications['E30']);
    }

    public function testInventoryRetainsExactInertWriteSetAndLockOrder(): void
    {
        // Read source, not the runtime container; these are documentary constant arrays.
        $journal = $this->read('src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract.php');
        $inventory = $this->read(self::INVENTORY);
        foreach (['REQUIRED_WRITE_SET_FIELDS' => 7, 'LOCK_ORDER' => 6] as $constant => $count) {
            self::assertSame(1, preg_match('/const array '. $constant .' = \[(.*?)\];/s', $journal, $body));
            preg_match_all("/'([^']+)'/", $body[1], $fields);
            self::assertCount($count, $fields[1]);
            $ordered = [];
            foreach ($fields[1] as $index => $field) {
                $ordered[] = ($index + 1).'. `'.$field.'`';
            }
            self::assertStringContainsString(implode("\n", $ordered), $inventory);
        }
        foreach (['CONTRACT_ONLY_NOT_OPENED', "'journal_opened'", "'combined_commit_performed'"] as $boundary) {
            self::assertStringContainsString($boundary, $journal);
        }
        self::assertStringContainsString("STATUS = 'NOT_IMPLEMENTED'", $this->read('src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3Contract.php'));
    }

    public function testCompletionConsumersRetainTheSameMarkerAndClosedPerimeter(): void
    {
        foreach ([self::INVENTORY, self::HANDOFF, 'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md'] as $path) {
            $document = $this->read($path);
            foreach ([self::MARKER, 'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
                self::assertStringContainsString($boundary, $document, $path);
            }
        }
        foreach (['docs/delegate-mission-flow.md', 'todo/blackquill-todos.md'] as $path) {
            self::assertStringContainsString(self::HANDOFF, $this->read($path));
            self::assertStringContainsString(self::INVENTORY, $this->read($path));
        }
        $handoff = preg_replace('/\s+/', ' ', $this->read(self::HANDOFF));
        foreach (['No Batch 1 implementation is authorized', 'new operator instruction',
            'persist a live journal', 'acquire a live transition lock', 'issue or consume authority',
            'admit v3 execution', 'adopt a successor', 'change provider binding',
            'create a live winner or receipt', 'handle credentials or capabilities',
            'invoke a provider', 'perform external I/O', 'start an effect', 'authorize retry',
            'open Iron Gate or Lazaretto', 'clean merged Batch 7 main'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    public function testSequenceDoesNotPromoteSnapshotProofToExecutableEvidence(): void
    {
        $inventory = preg_replace('/\s+/', ' ', $this->read(self::INVENTORY));
        foreach (['Eight planned stages remain', 'Batch 1', 'Batch 2', 'Batch 3', 'Batch 4',
            'Batch 5', 'Batch 6', 'Batch 7', 'Batch 8', 'snapshot contention only',
            'No physical power-loss durability is proved', 'No current directive authorizes repair',
            'No live instance record', 'PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED',
            'winner without receipt', 'short writes', 'revocation racing',
            'no decision may require the digest of the admission it is authorizing'] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
    }

    private function read(string $path): string
    {
        return str_replace("\r", '', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
