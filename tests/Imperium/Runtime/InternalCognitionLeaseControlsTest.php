<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Governance\InternalCognitionLeaseControls;
use PHPUnit\Framework\TestCase;

final class InternalCognitionLeaseControlsTest extends TestCase
{
    public function testMetadataRecordsExactControlsWithoutInventingRevocationAuthority(): void
    {
        [$decision, $request] = $this->sources();
        $metadata = InternalCognitionLeaseControls::governance($decision, $request, '2026-08-27T18:10:00+00:00', '2026-08-27T18:15:00+00:00');
        self::assertSame('GOVERNANCE_COGNITION', $metadata['lease_family']);
        self::assertSame('DURABLE_INVOCATION_CLAIM', $metadata['freshness']['revalidation_checkpoint']);
        self::assertSame('UNASSIGNED_DEFERRED_BOUNDARY', $metadata['revocation']['status']);
        self::assertNull($metadata['revocation']['authority_reference']);
        self::assertFalse($metadata['revocation']['propagation_implemented']);
        self::assertFalse($metadata['revocation']['lease_closure_implemented']);
        self::assertFalse($metadata['metadata_authority_granted']);
        self::assertTrue(InternalCognitionLeaseControls::isExactGovernance($metadata, $decision, $request, '2026-08-27T18:10:00+00:00', '2026-08-27T18:15:00+00:00'));
        $metadata['scope']['target']['seat'] = 'curia.seneschal';
        self::assertFalse(InternalCognitionLeaseControls::isExactGovernance($metadata, $decision, $request, '2026-08-27T18:10:00+00:00', '2026-08-27T18:15:00+00:00'));
    }

    private function sources(): array
    {
        return [[
            'decision_id' => 'governance-provider-resource-decision-'.str_repeat('e', 20), 'record_digest' => str_repeat('f', 64),
            'instance_id' => 'imperium-test', 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => ['temperature' => 0.2],
        ], [
            'request_id' => 'governance-cognition-request-'.str_repeat('a', 20), 'record_digest' => str_repeat('b', 64),
            'cluster' => 'foundry', 'instance_id' => 'imperium-test', 'target' => ['seat' => 'foundry.artificer', 'purpose' => 'specify-persona'],
            'source_governance_authority' => ['id' => 'authority-test', 'digest' => str_repeat('c', 64)], 'input_digest' => str_repeat('d', 64),
        ]];
    }
}
