<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as Validator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch4Test extends TestCase
{
    public function testEveryInterruptionCutClassifiesExactly(): void
    {
        [$journal, $winner, $receipt] = $this->fixture();
        $classifier = new Classifier(new Validator());
        $sets = [
            'BEFORE_JOURNAL' => [],
            'AFTER_JOURNAL' => ['journal' => $journal],
            'AFTER_WINNER' => ['journal' => $journal, 'winner' => $winner],
            'AFTER_RECEIPT' => [
                'journal' => $journal, 'winner' => $winner, 'receipt' => $receipt,
            ],
        ];

        foreach ($sets as $cut => $evidence) {
            $classifier->assertInterruptionCut($cut, $evidence);
            self::assertSame(
                Classifier::INTERRUPTION_CLASSIFICATIONS[$cut],
                $classifier->classify($evidence),
            );
        }
    }

    public function testExactReplayConverges(): void
    {
        $evidence = $this->completeEvidence();

        self::assertSame(
            'EXACT_REPLAY',
            (new Classifier(new Validator()))->compare($evidence, $evidence),
        );
    }

    public function testChangedEvidenceUnderSameJournalRefuses(): void
    {
        $left = $this->completeEvidence();
        $right = $left;
        $right['journal']['source_decision']['digest'] = str_repeat('9', 64);
        $right = $this->rebind($right);

        self::assertSame(
            'CHANGED_EVIDENCE_REFUSED',
            (new Classifier(new Validator()))->compare($left, $right),
        );
    }

    public function testCompetingJournalUnderSameRootRefuses(): void
    {
        $left = $this->completeEvidence();
        $right = $left;
        $right['journal']['journal_id'] = 'atomic-transition-journal.2';
        $right = $this->rebind($right);

        self::assertSame(
            'SAME_ROOT_CONTENTION_REFUSED',
            (new Classifier(new Validator()))->compare($left, $right),
        );
    }

    public function testPartialWriteShapeIsIncompleteAndInvalidCutRefuses(): void
    {
        [$journal, , $receipt] = $this->fixture();
        $classifier = new Classifier(new Validator());
        $partial = ['journal' => $journal, 'receipt' => $receipt];

        self::assertSame('INCOMPLETE', $classifier->classify($partial));

        $this->expectExceptionMessage(
            'PBL930_ATOMIC_TRANSITION_INTERRUPTION_CLASSIFICATION_INVALID',
        );
        $classifier->assertInterruptionCut('AFTER_RECEIPT', $partial);
    }

    public function testProofIsPureAndDocumentationAuthorizesReconstructionOnly(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/LaCortine/'
            .'ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier.php',
        );
        foreach ([
            'AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'public function persist',
            'public function write', 'public function recover',
            'public function execute',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        $doc = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-batch-4-disposable-proof.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-batch-4-complete.md',
        );
        foreach ([
            'BATCH_4_DISPOSABLE_INTERRUPTION_CONTENTION_REPLAY_PARTIAL_WRITE_AND_RECOVERY_CLASSIFICATION_PROOF_COMPLETE',
            'before journal is `ABSENT`',
            'after journal is `PREPARED`',
            'after winner is `COMMITTING`',
            'after receipt is `COMMITTED`',
            'EXACT_REPLAY',
            'CHANGED_EVIDENCE_REFUSED',
            'SAME_ROOT_CONTENTION_REFUSED',
            'caller-supplied in-memory classifications',
            'imports no persistence',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach ([
            'Only Provider Binding Successor Atomic Live Transition Batch 5 read-only recovery-plan and aggregate reconstruction contracts with pure validation may next be considered.',
            'may define read-only contracts, pure validators and caller-supplied reconstruction only',
            'may not persist a journal',
            'may not acquire a live lock',
            'may not write or repair state',
            'may not issue or consume live authority',
            'may not admit execution',
            'may not adopt a successor',
            'may not change binding state',
            'may not create a durable winner or receipt',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function completeEvidence(): array
    {
        [$journal, $winner, $receipt] = $this->fixture();

        return ['journal' => $journal, 'winner' => $winner, 'receipt' => $receipt];
    }

    private function rebind(array $evidence): array
    {
        $evidence['journal'] = $this->seal($evidence['journal']);
        $evidence['winner']['transaction_journal'] =
            $this->reference($evidence['journal'], 'journal_id');
        $evidence['winner']['source_decision'] =
            $evidence['journal']['source_decision'];
        $evidence['winner'] = $this->seal($evidence['winner']);
        $evidence['receipt']['transaction_journal'] =
            $this->reference($evidence['journal'], 'journal_id');
        $evidence['receipt']['combined_winner'] =
            $this->reference($evidence['winner'], 'winner_id');
        $evidence['receipt'] = $this->seal($evidence['receipt']);

        return $evidence;
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
