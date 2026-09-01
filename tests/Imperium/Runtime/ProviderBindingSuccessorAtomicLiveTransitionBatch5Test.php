<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator as PlanValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch5Test extends TestCase
{
    public function testAbsentEvidenceProducesNoActionReadOnlyResult(): void
    {
        $result = $this->reconstructor()->reconstruct($this->plan(), []);

        self::assertSame('ABSENT', $result['classification']);
        self::assertSame('NO_ACTION', $result['directive']);
        foreach ([
            'automatic_repair_performed', 'state_write_performed',
            'authority_action_performed', 'provider_effect_started',
            'continuing_authority',
        ] as $flag) {
            self::assertFalse($result[$flag]);
        }
    }

    public function testPreparedEvidenceRefusesAutomaticRepair(): void
    {
        $result = $this->reconstructor()->reconstruct(
            $this->plan(),
            ['journal' => $this->journal()],
        );

        self::assertSame('PREPARED', $result['classification']);
        self::assertSame('REFUSE_AUTOMATIC_REPAIR', $result['directive']);
        self::assertFalse($result['evidence_complete']);
        self::assertFalse($result['automatic_repair_performed']);
    }

    public function testTamperedRecoveryPlanRefuses(): void
    {
        $plan = $this->plan();
        $plan['classification_directives']['PREPARED'] = 'REPAIR';
        $plan = $this->seal($plan);

        $this->expectExceptionMessage('PBL940_ATOMIC_TRANSITION_RECOVERY_PLAN_INVALID');
        (new PlanValidator())->assertPlan($plan);
    }

    public function testRecoveryBoundaryRemainsReadOnly(): void
    {
        self::assertNotContains(true, Plan::NON_AUTHORITIES);
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/';
        $source = '';
        foreach ([
            'ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator.php',
            'ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor.php',
        ] as $file) {
            $source .= (string) file_get_contents($root.$file);
        }
        foreach ([
            'AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'public function persist',
            'public function write', 'public function repair',
            'public function execute',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesAdversarialAuditNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-batch-5-read-only-reconstruction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-batch-5-complete.md',
        );
        foreach ([
            'BATCH_5_READ_ONLY_RECOVERY_PLAN_AND_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'ABSENT',
            'NO_ACTION',
            'PREPARED',
            'refuses automatic repair',
            'COMMITTING',
            'refuses partial state',
            'COMMITTED',
            'accepts exact read-only evidence',
            'INCOMPLETE',
            'explicit false action flags',
            'does not repair, replace, promote, persist, lock, consume, admit, adopt or transition anything',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach ([
            'Only Provider Binding Successor Atomic Live Transition Batch 6 read-only adversarial recovery and reconstruction audit may next be considered.',
            'may define pure caller-supplied audit proof only',
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

    private function reconstructor(): Reconstructor
    {
        return new Reconstructor(
            new PlanValidator(),
            new Classifier(new TransactionValidator()),
        );
    }

    private function plan(): array
    {
        return $this->seal([
            'schema' => Plan::SCHEMA,
            'recovery_plan_id' => 'atomic-transition-recovery-plan.1',
            'instance_id' => 'instance.1',
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'classification_directives' => Plan::DIRECTIVES,
            'automatic_repair_permitted' => false,
            'state_write_permitted' => false,
            'authority_action_permitted' => false,
            'plan_applied' => false,
            'continuing_authority' => false,
            'status' => Plan::STATUS,
            'sealed' => true,
        ]);
    }

    private function journal(): array
    {
        $target = fn (string $id, string $schema): array => [
            'id' => $id, 'schema' => $schema,
        ];

        return $this->seal([
            'schema' => Journal::SCHEMA,
            'journal_id' => 'atomic-transition-journal.1',
            'instance_id' => 'instance.1',
            'source_decision' => [
                'id' => 'decision.1',
                'digest' => str_repeat('a', 64),
                'schema' => 'decision/v1',
            ],
            'transition_authority' => [
                'id' => 'authority.1',
                'digest' => str_repeat('b', 64),
                'schema' => 'authority/v1',
            ],
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'canonical_lock_order' => Journal::LOCK_ORDER,
            'write_set' => [
                'authority_consumption' => $target('authority.1', 'consumption/v1'),
                'v3_admission' => $target('admission.1', 'admission/v3'),
                'adoption_join' => $target('join.1', 'join/v1'),
                'source_binding_transition' => $target('source.1', 'transition/v1'),
                'successor_binding_activation' => $target('successor.1', 'activation/v1'),
                'winner_target' => $target('winner.1', 'winner/v1'),
                'receipt_target' => $target('receipt.1', 'receipt/v1'),
            ],
            'recovery_states' => Journal::RECOVERY_STATES,
            'status' => Journal::STATUS,
            'journal_opened' => false,
            'combined_commit_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
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
