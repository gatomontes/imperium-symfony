<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionInertTransactionSeam as Seam;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as Validator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch3Test extends TestCase
{
    public function testExactContractsValidateThroughInertSeam(): void
    {
        [$journal, $winner, $receipt] = $this->fixture();

        self::assertSame(
            'VALID_CONTRACT_ONLY_NO_TRANSACTION_PERFORMED',
            (new Seam(new Validator()))->classify($journal, $winner, $receipt),
        );
        self::assertFalse($journal['journal_opened']);
        self::assertFalse($winner['combined_commit_performed']);
        self::assertFalse($receipt['combined_commit_observed']);
    }

    public function testChangedCanonicalLockOrderRefuses(): void
    {
        [$journal] = $this->fixture();
        $journal['canonical_lock_order'][0] = 'transition_authority';
        $journal = $this->seal($journal);

        $this->expectExceptionMessage('PBL900_ATOMIC_TRANSITION_JOURNAL_INVALID');
        (new Validator())->assertJournal($journal);
    }

    public function testPartialWinnerClaimRefuses(): void
    {
        [$journal, $winner] = $this->fixture();
        $winner['authority_consumed'] = true;
        $winner = $this->seal($winner);

        $this->expectExceptionMessage('PBL910_ATOMIC_TRANSITION_WINNER_INVALID');
        (new Validator())->assertWinner($winner, $journal);
    }

    public function testProviderEffectReceiptClaimRefuses(): void
    {
        [$journal, $winner, $receipt] = $this->fixture();
        $receipt['provider_effect_started'] = true;
        $receipt = $this->seal($receipt);

        $this->expectExceptionMessage('PBL920_ATOMIC_TRANSITION_RECEIPT_INVALID');
        (new Validator())->assertReceipt($receipt, $winner, $journal);
    }

    public function testWriteSetIsAcyclicAndSeamHasNoPersistenceDependency(): void
    {
        [$journal] = $this->fixture();
        foreach ($journal['write_set'] as $target) {
            self::assertSame(['id', 'schema'], array_keys($target));
            self::assertArrayNotHasKey('digest', $target);
        }

        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/';
        $source = '';
        foreach ([
            'ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionReceiptContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator.php',
            'ProviderBindingSuccessorAtomicLiveTransitionInertTransactionSeam.php',
        ] as $file) {
            $source .= (string) file_get_contents($root.$file);
        }
        foreach ([
            'AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'public function commit',
            'public function persist', 'public function recover',
            'public function execute',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        self::assertNotContains(true, Journal::NON_AUTHORITIES);
        self::assertNotContains(true, Winner::NON_AUTHORITIES);
        self::assertNotContains(true, Receipt::NON_AUTHORITIES);
    }

    public function testDocumentationAuthorizesDisposableProofNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-batch-3-transaction-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-batch-3-complete.md',
        );
        foreach ([
            'BATCH_3_INERT_EXACT_ROOT_JOURNAL_LOCK_WRITESET_RECOVERY_WINNER_AND_RECEIPT_CONTRACTS_COMPLETE',
            'canonical lock order',
            'value-shaped targets without future record digests',
            'ABSENT',
            'PREPARED',
            'COMMITTING',
            'COMMITTED',
            'REFUSED',
            'VALID_CONTRACT_ONLY_NO_TRANSACTION_PERFORMED',
            'imports no persistence primitive',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach ([
            'Only Provider Binding Successor Atomic Live Transition Batch 4 disposable caller-supplied interruption, same-root contention, exact replay, changed evidence, partial-write and recovery-classification proof may next be considered.',
            'may provide caller-supplied in-memory proof and pure classification only',
            'may not persist a journal',
            'may not acquire a live lock',
            'may not consume live authority',
            'may not admit execution',
            'may not adopt a successor',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $root = 'binding-reconciliation-root.1';
        $ref = fn (string $id, string $digit, string $schema): array => [
            'id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema,
        ];
        $target = fn (string $id, string $schema): array => [
            'id' => $id, 'schema' => $schema,
        ];
        $writeSet = [
            'authority_consumption' => $target('authority.1', 'authority-consumption/v1'),
            'v3_admission' => $target('admission.1', 'admission/v3'),
            'adoption_join' => $target('join.1', 'adoption-join/v1'),
            'source_binding_transition' => $target('source-binding.1', 'binding-transition/v1'),
            'successor_binding_activation' => $target('successor-binding.1', 'binding-activation/v1'),
            'winner_target' => $target('winner.1', Winner::SCHEMA),
            'receipt_target' => $target('receipt.1', Receipt::SCHEMA),
        ];

        $journal = $this->seal([
            'schema' => Journal::SCHEMA,
            'journal_id' => 'atomic-transition-journal.1',
            'instance_id' => 'instance.1',
            'source_decision' => $ref('decision.1', 'a', 'decision/v1'),
            'transition_authority' => $ref('authority.1', 'b', 'authority/v1'),
            'replay_contention_root' => $root,
            'canonical_lock_order' => Journal::LOCK_ORDER,
            'write_set' => $writeSet,
            'recovery_states' => Journal::RECOVERY_STATES,
            'status' => Journal::STATUS,
            'journal_opened' => false,
            'combined_commit_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        $winner = $this->seal([
            'schema' => Winner::SCHEMA,
            'winner_id' => 'winner.1',
            'instance_id' => 'instance.1',
            'transaction_journal' => $this->reference($journal, 'journal_id'),
            'source_decision' => $journal['source_decision'],
            'transition_authority' => $journal['transition_authority'],
            'v3_admission' => $ref('admission.1', 'c', 'admission/v3'),
            'adoption_join' => $ref('join.1', 'd', 'adoption-join/v1'),
            'source_binding_transition' => $ref('source-binding.1', 'e', 'binding-transition/v1'),
            'successor_binding_activation' => $ref('successor-binding.1', 'f', 'binding-activation/v1'),
            'replay_contention_root' => $root,
            'authority_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'source_binding_deactivated' => false,
            'successor_binding_activated' => false,
            'combined_commit_performed' => false,
            'continuing_authority' => false,
            'status' => Winner::STATUS,
            'sealed' => true,
        ]);

        $receipt = $this->seal([
            'schema' => Receipt::SCHEMA,
            'receipt_id' => 'receipt.1',
            'instance_id' => 'instance.1',
            'combined_winner' => $this->reference($winner, 'winner_id'),
            'transaction_journal' => $this->reference($journal, 'journal_id'),
            'replay_contention_root' => $root,
            'combined_commit_observed' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'status' => Receipt::STATUS,
            'sealed' => true,
        ]);

        return [$journal, $winner, $receipt];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
