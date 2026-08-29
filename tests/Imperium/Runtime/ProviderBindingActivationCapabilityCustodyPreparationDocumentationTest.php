<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCapabilityCustodyPreparationDocumentationTest extends TestCase
{
    public function testPreparationClassifiesActivationAndCrossProcessCustodyWithoutOpeningExecution(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/provider-binding-activation-capability-custody-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-capability-custody-preparation-batch-0-complete.md');

        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $inventory);
        }
        foreach (['Provider selection authority', 'Single-execution activation record or lease', 'Cross-process delivery of the exact already-issued capability', 'Atomic pre-I/O capability consumption', 'Crash recovery after delivery but before resolution', 'Capability reconstruction', 'Secret exclusion from durable corridor'] as $requirement) {
            self::assertStringContainsString($requirement, $inventory);
        }
        foreach (['PHP object identity', 'another broker instance', 'manufacture authority', 'one authoritative root', '`UNKNOWN_REPLAY_PROHIBITED`', 'Provider Execution Assurance remains paused'] as $proof) {
            self::assertNotFalse(stripos($inventory.$handoff, $proof), $proof);
        }
        foreach (['exact producer', 'Exact consumer', 'Non-authorities', 'Durable custodian feasibility gate', 'Only Batch 1'] as $boundary) {
            self::assertNotFalse(stripos($inventory.$handoff, $boundary), $boundary);
        }
        foreach (['Runtime behavior is unchanged', 'No binding activation', 'No capability or credential action occurred', 'external I/O', 'Iron Gate', 'Lazaretto', 'inbound webhook', 'sortie', 'revocation', 'telemetry', 'incident'] as $closed) {
            self::assertNotFalse(stripos($inventory.$handoff, $closed), $closed);
        }

        self::assertStringContainsString('Only Batch 1 is authorized', $handoff);
        self::assertStringContainsString('may not implement an issuer, custodian, transfer, delivery or consumption service', $handoff);
    }
}
