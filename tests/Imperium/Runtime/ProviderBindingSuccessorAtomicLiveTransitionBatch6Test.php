<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditResultContract as Result;
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
        $result = $this->audit()->audit($this->plan(), [], $this->proofs());

        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        self::assertSame('PASSED', $result['classification']);
        self::assertSame('binding-reconciliation-root.1', $result['audited_root']);
        self::assertTrue($result['read_only']);
        foreach (array_slice($result, 5) as $action) {
            self::assertFalse($action);
        }
    }

    public function testMissingOrFalseProofFailsClosed(): void
    {
        $proofs = $this->proofs();
        $proofs['partial_write_refusal_proved'] = false;
        $result = $this->audit()->audit($this->plan(), [], $proofs);

        self::assertSame('CONFLICTED', $result['classification']);
        self::assertStringContainsString('PBL951_ADVERSARIAL_PROOF_FAILED', $result['findings'][0]);
    }

    public function testTamperedPlanAndSecretMaterialFailClosed(): void
    {
        $plan = $this->plan();
        $plan['classification_directives']['ABSENT'] = 'REPAIR';
        $plan = $this->seal($plan);
        self::assertSame('CONFLICTED', $this->audit()->audit($plan, [], $this->proofs())['classification']);

        $result = $this->audit()->audit($this->plan(), ['credential_token' => 'forbidden'], $this->proofs());
        self::assertSame('CONFLICTED', $result['classification']);
        self::assertStringContainsString('PBL953_SECRET_OR_CAPABILITY_MATERIAL_PRESENT', $result['findings'][0]);
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
