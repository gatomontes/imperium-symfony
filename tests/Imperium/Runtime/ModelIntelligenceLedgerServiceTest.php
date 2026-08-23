<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Oracle\ModelIntelligenceLedgerService;
use PHPUnit\Framework\TestCase;

final class ModelIntelligenceLedgerServiceTest extends TestCase
{
    public function testAugurSealsProvenanceBoundKnowledgeAccessAndAdmissibilityWithoutDownstreamAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-oracle-ledger-'.bin2hex(random_bytes(6));
        try {
            $service = new ModelIntelligenceLedgerService($root);
            $snapshot = $service->sealSnapshot('imperium-test', [$this->accessibleModel(), $this->knownButUnverifiedModel()], $this->augur());

            self::assertSame($snapshot, $service->sealSnapshot('imperium-test', [$this->accessibleModel(), $this->knownButUnverifiedModel()], $this->augur()));
            self::assertSame('imperium.oracle-model-intelligence-snapshot/v1', $snapshot['schema']);
            self::assertSame(1, $snapshot['snapshot_generation']);
            self::assertNull($snapshot['prior_snapshot']);
            self::assertSame('ORACLE_CANONICAL_CATALOGUE_SNAPSHOT_SEALED_NO_SELECTION_AUTHORITY', $snapshot['status']);
            self::assertSame(['knowledge', 'accessibility', 'admissibility'], $snapshot['classification_dimensions']);
            self::assertSame('oracle.augur', $snapshot['actor']['seat']);
            self::assertSame(['anthropic/claude-sonnet@4', 'openai/gpt-test@2026-08-01'], array_keys($snapshot['models']));

            $accessible = $snapshot['models']['openai/gpt-test@2026-08-01'];
            self::assertSame('KNOWN', $accessible['knowledge']['status']);
            self::assertSame('ACCESSIBLE', $accessible['accessibility']['status']);
            self::assertSame('clavium', $accessible['accessibility']['clavium_assertion']['issuer']['office']);
            self::assertSame('locksmith', $accessible['accessibility']['clavium_assertion']['issuer']['officer']);
            self::assertStringStartsWith('clavium://', $accessible['accessibility']['clavium_assertion']['credential_ref']);
            self::assertSame('ADMISSIBLE', $accessible['admissibility']['status']);

            $unverified = $snapshot['models']['anthropic/claude-sonnet@4'];
            self::assertSame('KNOWN', $unverified['knowledge']['status']);
            self::assertSame('UNVERIFIED', $unverified['accessibility']['status']);
            self::assertNull($unverified['accessibility']['clavium_assertion']);
            self::assertSame('UNEVALUATED', $unverified['admissibility']['status']);

            foreach (['model_research_authority', 'requirement_commission_authority', 'eligibility_authority', 'recommendation_authority', 'selection_authority', 'model_assignment_authority', 'profile_mutation_authority', 'credential_disclosure_authority', 'provider_invocation_authority', 'deployment_authority'] as $authority) {
                self::assertFalse($snapshot[$authority]);
            }
            self::assertFileExists($root.'/var/imperium/offices/oracle/model-intelligence-snapshots/'.$snapshot['snapshot_id'].'.json');

            $next = $service->sealSnapshot('imperium-test', [$this->accessibleModel()], $this->augur(), $snapshot['snapshot_id']);
            self::assertSame(2, $next['snapshot_generation']);
            self::assertSame(['id' => $snapshot['snapshot_id'], 'digest' => $snapshot['record_digest']], $next['prior_snapshot']);
            self::assertSame(['openai/gpt-test@2026-08-01'], array_keys($next['models']));
            self::assertFalse($next['selection_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testAccessibleClassificationRejectsWrongProviderAssertion(): void
    {
        $root = sys_get_temp_dir().'/imperium-oracle-ledger-invalid-'.bin2hex(random_bytes(6));
        $record = $this->accessibleModel();
        $record['accessibility']['clavium_assertion']['provider'] = 'another-provider';
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('OR11_CLAVIUM_ASSERTION_INVALID');
            (new ModelIntelligenceLedgerService($root))->sealSnapshot('imperium-test', [$record], $this->augur());
        } finally {
            $this->removeTree($root);
        }
    }

    private function accessibleModel(): array
    {
        $source = $this->source('openai-model-page', 'provider-documentation', 'https://provider.test/models/gpt-test');
        $assertion = [
            'schema' => 'imperium.clavium-provider-access-assertion/v1', 'assertion_id' => 'clavium-access-openai-1', 'instance_id' => 'imperium-test',
            'issuer' => ['office' => 'clavium', 'officer' => 'locksmith', 'seat' => 'clavium.locksmith', 'binding_id' => 'binding', 'manifestation_id' => 'manifestation', 'occupancy_generation' => 1],
            'provider' => 'openai', 'credential_ref' => 'clavium://providers/openai/default', 'scope' => ['model.invoke'],
            'observation' => ['method' => 'test', 'observed_at' => '2026-08-23T12:00:00+00:00', 'evidence' => ['non_empty_secret_present' => true]],
            'status' => 'ACCESS_AVAILABLE', 'checkpoint' => 'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY', 'restrictions' => [],
            'revalidation' => ['expires_at' => '2026-08-24T12:00:00+00:00', 'conditions' => ['expiry']],
            'credential_possession_transferred' => false, 'credential_use_authority' => false, 'credential_disclosure_authority' => false,
            'provider_invocation_authority' => false, 'model_admissibility_authority' => false, 'model_selection_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ];
        $assertion['record_digest'] = 'sha256:'.hash('sha256', CanonicalJson::encode($assertion));
        return [
            'provider' => 'openai', 'model_id' => 'gpt-test', 'model_version' => '2026-08-01',
            'knowledge_sources' => [$source],
            'claims' => [[
                'claim_id' => 'capability-structured-output', 'subject' => 'capability', 'value' => 'structured-output',
                'evidence_source_ids' => [$source['source_id']],
            ]],
            'accessibility' => ['status' => 'ACCESSIBLE', 'clavium_assertion' => $assertion],
            'admissibility' => ['status' => 'ADMISSIBLE', 'policy_refs' => ['imperium.model-policy/v1'],
                'evidence_source_ids' => [$source['source_id']], 'reasons' => []],
            'provenance' => null,
        ];
    }

    private function knownButUnverifiedModel(): array
    {
        $source = $this->source('anthropic-model-page', 'provider-documentation', 'https://provider.test/models/claude-sonnet');
        return [
            'provider' => 'anthropic', 'model_id' => 'claude-sonnet', 'model_version' => '4',
            'knowledge_sources' => [$source],
            'claims' => [],
            'accessibility' => ['status' => 'UNVERIFIED', 'clavium_assertion' => null],
            'admissibility' => ['status' => 'UNEVALUATED', 'policy_refs' => [], 'evidence_source_ids' => [], 'reasons' => []],
            'provenance' => null,
        ];
    }

    private function source(string $id, string $type, string $locator): array
    {
        return ['source_id' => $id, 'source_type' => $type, 'locator' => $locator,
            'observed_at' => '2026-08-23T12:00:00+00:00', 'content_digest' => 'sha256:'.hash('sha256', $locator)];
    }

    private function augur(): array
    {
        return ['instance_id' => 'imperium-test', 'office' => 'oracle', 'seat' => 'oracle.augur',
            'binding_id' => 'oracle-augur-binding-1234567890abcdef1234', 'manifestation_id' => 'manifestation-oracle-augur',
            'occupancy_generation' => 1, 'status' => 'ACTIVE', 'model_intelligence_stewardship_authority' => true,
            'model_selection_authority' => false];
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
