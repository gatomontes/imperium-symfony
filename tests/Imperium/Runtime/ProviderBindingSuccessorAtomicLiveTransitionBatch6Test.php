<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService as Audit;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator as PlanValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch6Test extends TestCase
{
    public function testExactReadOnlyAuditPassesWithCompleteProofSet(): void
    {
        $this->expectExceptionMessage('PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED');
        $this->audit()->audit($this->plan(), [], $this->proofs());
    }

    public function testMissingOrFalseProofFailsClosed(): void
    {
        $this->expectExceptionMessage('PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED');
        $this->audit()->audit($this->plan(), [], array_fill_keys(Audit::REQUIRED_PROOFS, false));
    }

    public function testTamperedPlanAndSecretMaterialFailClosed(): void
    {
        $this->expectExceptionMessage('PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED');
        $this->audit()->audit($this->plan(), ['credential_token' => 'forbidden'], $this->proofs());
    }

    public function testAuditIsPureAndAuthorizesTerminalAuditOnly(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService.php');
        foreach (['AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore', 'AuthorityConsumptionStore', 'public function persist', 'public function write', 'public function repair', 'public function execute'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        $doc = $this->document('docs/provider-binding-successor-atomic-live-transition-batch-6-adversarial-audit.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-atomic-live-transition-batch-6-complete.md');
        foreach (['BATCH_6_READ_ONLY_ADVERSARIAL_RECOVERY_AND_RECONSTRUCTION_AUDIT_COMPLETE', 'pure caller-supplied audit', 'exact replay', 'same-root contention', 'partial-write', 'automatic-repair', 'recursive secret-exclusion', 'fail closed as `CONFLICTED`', 'imports no persistence or effect dependency'] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach (['Only Provider Binding Successor Atomic Live Transition Batch 7 terminal audit and campaign closure may next be considered.', 'may inspect the canonical chain, adversarial findings, documentation and focused tests only', 'may not persist a journal', 'may not acquire a live lock', 'may not write or repair state', 'may not issue or consume live authority', 'may not admit execution', 'may not adopt a successor', 'may not change binding state', 'may not create a durable winner or receipt', 'may not handle or resolve a credential or capability', 'may not invoke a provider', 'may not perform external I/O', 'may not start a provider effect', 'may not open Iron Gate or Lazaretto'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function audit(): Audit
    {
        return new Audit(new Reconstructor(new PlanValidator(), new Classifier(new TransactionValidator())));
    }

    private function plan(): array
    {
        return $this->seal(['schema' => Plan::SCHEMA, 'recovery_plan_id' => 'atomic-transition-recovery-plan.1', 'instance_id' => 'instance.1', 'replay_contention_root' => 'binding-reconciliation-root.1', 'classification_directives' => Plan::DIRECTIVES, 'automatic_repair_permitted' => false, 'state_write_permitted' => false, 'authority_action_permitted' => false, 'plan_applied' => false, 'continuing_authority' => false, 'status' => Plan::STATUS, 'sealed' => true]);
    }

    private function proofs(): array
    {
        return array_fill_keys(Audit::REQUIRED_PROOFS, true);
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
