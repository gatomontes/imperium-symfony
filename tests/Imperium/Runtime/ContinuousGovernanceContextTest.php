<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Governance\ContinuousGovernanceContext;
use PHPUnit\Framework\TestCase;

final class ContinuousGovernanceContextTest extends TestCase
{
    public function testAdvisoryClassificationReferencesNativePrincipalsWithoutGrantingAuthority(): void
    {
        $context = ContinuousGovernanceContext::advisoryCognition([
            'instance_id' => 'imperium-test',
            'seat' => 'foundry.artificer',
            'purpose' => 'specify-persona',
            'input_digest' => str_repeat('a', 64),
            'source' => ['id' => 'native-authority-test', 'digest' => str_repeat('b', 64)],
        ]);

        self::assertSame('ADVISORY_COGNITION', $context['governance_tier']);
        self::assertSame('INTERNAL_REVERSIBLE', $context['consequence_class']);
        self::assertSame(['INSTANCE', 'OFFICE', 'SEAT'], array_column($context['runtime_principal_references'], 'principal_kind'));
        self::assertSame(['imperium-test', 'foundry', 'foundry.artificer'], array_column($context['runtime_principal_references'], 'principal_id'));
        self::assertFalse($context['principal_identity_inferred']);
        foreach (['authority_granted', 'authority_consumed', 'credential_authority', 'tool_authority', 'network_authority', 'perimeter_authority', 'external_action_authority', 'execution_authority', 'continuation_authority', 'revocation_authority', 'incident_authority'] as $field) {
            self::assertFalse($context[$field], $field.' must remain false.');
        }
        self::assertTrue(ContinuousGovernanceContext::isExactAdvisoryCognition($context, [
            'instance_id' => 'imperium-test', 'seat' => 'foundry.artificer', 'purpose' => 'specify-persona',
            'input_digest' => str_repeat('a', 64), 'source' => ['id' => 'native-authority-test', 'digest' => str_repeat('b', 64)],
        ]));
        $context['execution_authority'] = true;
        self::assertFalse(ContinuousGovernanceContext::isExactAdvisoryCognition($context, [
            'instance_id' => 'imperium-test', 'seat' => 'foundry.artificer', 'purpose' => 'specify-persona',
            'input_digest' => str_repeat('a', 64), 'source' => ['id' => 'native-authority-test', 'digest' => str_repeat('b', 64)],
        ]));
    }

    public function testIncompleteNativeIdentityFailsStopped(): void
    {
        $this->expectExceptionMessage('CAG101_CONTINUOUS_GOVERNANCE_CONTEXT_INVALID');
        ContinuousGovernanceContext::advisoryCognition([
            'instance_id' => 'imperium-test',
            'seat' => 'foundry.artificer',
            'purpose' => 'specify-persona',
            'input_digest' => str_repeat('a', 64),
            'source' => ['id' => 'native-authority-test'],
        ]);
    }
}
