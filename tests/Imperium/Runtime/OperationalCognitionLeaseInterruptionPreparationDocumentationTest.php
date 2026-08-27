<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class OperationalCognitionLeaseInterruptionPreparationDocumentationTest extends TestCase
{
    public function testBatchZeroClassifiesCompletePreparationWithoutOpeningRuntimeAuthority(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/operational-cognition-lease-interruption-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/operational-cognition-lease-interruption-preparation-batch-0-complete.md');

        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $inventory);
        }
        foreach ([
            'source `imperium.curia-bounded-execution-authorization/v1`',
            'binding digest, Manifestation, and occupancy generation',
            '`oca-cognition-authority:<sha256 authorityId>`',
            '`oca-lease:<sha256 leaseId>`',
            'min(request.expires_at, decision.expires_at, lease.expires_at)',
            'DENY_DURABLE_OPERATIONAL_INVOCATION_CLAIM_FOR_EXACT_LEASE',
            '`claim_created=false`',
            '`credential_resolved=false`',
            '`provider_journal_created=false`',
            '`network_access_performed=false`',
            'mechanically scan intact claims',
        ] as $proof) {
            self::assertStringContainsString($proof, $inventory);
        }
        foreach (['Iron Gate', 'Lazaretto', 'sorties', 'telemetry', 'containment', 'incidents', 'credential-platform work'] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
        self::assertStringContainsString('No step is authorized by this inventory.', $inventory);
        self::assertStringContainsString('Runtime implementation remains unopened.', $handoff);
        self::assertStringContainsString('Batch 1 is not authorized by this handoff', $handoff);
    }
}
