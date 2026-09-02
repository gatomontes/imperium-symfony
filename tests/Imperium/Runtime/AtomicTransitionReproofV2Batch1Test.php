<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\ReproofV2\Contract;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionReproofV2Batch1Test extends TestCase
{
    public function testContractsAreVersionedFiniteAndAuthorityEmpty(): void
    {
        self::assertCount(11, Contract::SCHEMAS);
        self::assertSame(array_keys(Contract::SCHEMAS), array_keys(Contract::FIELDS));
        foreach (Contract::SCHEMAS as $name => $schema) {
            self::assertStringEndsWith('/v2', $schema);
            self::assertSame('schema', Contract::FIELDS[$name][0]);
            self::assertSame('record_digest', array_last(Contract::FIELDS[$name]));
            self::assertSame(Contract::FIELDS[$name], array_values(array_unique(Contract::FIELDS[$name])));
        }
        self::assertSame([false], array_values(array_unique(Contract::AUTHORITIES)));
        self::assertCount(8, Contract::CASES);
        self::assertCount(8, Contract::DOMAINS);
        self::assertSame(Contract::CASES, array_values(array_unique(Contract::CASES)));
        $methods = (new \ReflectionClass(Contract::class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        self::assertSame([], $methods);
    }

    public function testRequiredCustodyAndAcyclicBindingsAreSpecified(): void
    {
        $doc = file_get_contents(dirname(__DIR__, 3).'/docs/atomic-transition-reproof-v2-contracts.md');
        foreach (['externally approved', 'preexisting reservation always', 'Ed25519',
            'never normalized', 'no automatic retry', 'V1 remains refused',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT'] as $text) {
            self::assertStringContainsString($text, $doc);
        }
        self::assertContains('input_root', Contract::FIELDS['origin']);
        self::assertNotContains('receipt_digest', Contract::FIELDS['origin']);
        self::assertNotContains('report_digest', Contract::FIELDS['receipt']);
        self::assertSame(['candidate', 'report', 'identity', 'attestation'], Contract::PUBLIC_RECORDS);
    }
}
