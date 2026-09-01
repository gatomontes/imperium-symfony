<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationCampaignReadyTest extends TestCase
{
    public function testSelectionQualifiesClosureAndAuthorizesPreparationOnly(): void
    {
        $selection = $this->document('docs/next-campaign-atomic-transition-evidence-derivation-remediation.md');
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-campaign-ready.md');
        $authority = 'Only Atomic Transition Evidence Derivation Remediation Preparation Batch 0 is authorized';

        self::assertStringContainsString($authority, $selection);
        self::assertStringContainsString($authority, $handoff);
        self::assertStringContainsString('CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT', $selection);
        self::assertStringContainsString('six batches including Preparation Batch 0', $selection);
    }

    public function testCampaignTargetsDerivedEvidenceRatherThanProofCheckboxes(): void
    {
        $selection = $this->document('docs/next-campaign-atomic-transition-evidence-derivation-remediation.md');
        foreach ([
            'boolean proof claims',
            'typed adversarial case',
            'expected deterministic result',
            '`ABSENT`, `PREPARED`, `COMMITTING`, `COMMITTED` and `INCOMPLETE`',
            'exact replay, changed-evidence and same-root contention evidence pairs',
            'partial-write and tamper cases',
            'finding, case digest, aggregate audit disposition and immutable read-only audit receipt',
            'value-aware exclusion',
            'terminal audit recomputation',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $selection);
        }
    }

    public function testReadyHandoffPreservesTheClosedRuntimePerimeter(): void
    {
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-campaign-ready.md');
        foreach ([
            'may not change runtime behavior or repair the audit service',
            'may not define or execute a live transition',
            'may not persist a journal',
            'acquire a live lock',
            'write or repair state',
            'issue or consume authority',
            'admit execution',
            'adopt a successor',
            'change binding state',
            'may not handle or resolve a credential or capability',
            'invoke a provider',
            'perform external I/O',
            'start a provider effect',
            'open Iron Gate or Lazaretto',
            'The provider binding remains `BOUND_INACTIVE`.',
            'Required v3 execution admission remains `NOT_IMPLEMENTED`.',
            '`UNKNOWN_REPLAY_PROHIBITED` remains binding.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
