<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleDispositionContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalProvenanceFixtureStore;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeState;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch3Test.php';

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch4Test extends CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch3Test
{
    #[DataProvider('lifecycleRefusals')]
    public function testResolvedCapabilityRefusesEachDistinctSourceLifecycleDisposition(string $disposition, string $refusal): void
    {
        [$admission, $at] = $this->sealedResponseWithPrincipal();
        [$resolver, $capability] = $this->reconciliationCapability($admission['admission_id'], $at + 1, $at + 100);
        $this->publishLifecycleDisposition($disposition, $at + 2);

        $this->fails($refusal, fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public static function lifecycleRefusals(): iterable
    {
        yield 'suspend' => ['SUSPEND', 'REFUSED_SOURCE_SUSPENDED'];
        yield 'supersede' => ['SUPERSEDE', 'REFUSED_SOURCE_SUPERSEDED'];
        yield 'revoke' => ['REVOKE', 'REFUSED_SOURCE_REVOKED'];
        yield 'expire' => ['EXPIRE', 'REFUSED_SOURCE_EXPIRED'];
        yield 'retire' => ['RETIRE', 'REFUSED_SOURCE_RETIRED'];
    }

    public function testResolvedV3SourceCapabilityRefusesWhenLifecycleRequiresMigration(): void
    {
        $this->source['schema'] = ImperatorRuntimePrincipalVersionV3Contract::SCHEMA;
        $this->source['authority_scope']['provider_executor_principal_activation_decision_authority'] = true;
        $this->act['preserved_scope'] = $this->source['authority_scope'];
        $this->source = NativeState::seal($this->source);
        $this->write(NativeState::SOURCES['principal'].'/'.$this->source['principal_version_id'].'.json', $this->source);
        [$admission, $at] = $this->sealedResponseWithPrincipal();
        [$resolver, $capability] = $this->reconciliationCapability($admission['admission_id'], $at + 1, $at + 100);
        $this->publishLifecycleDisposition('SUSPEND', $at + 2);

        $this->fails('REFUSED_SOURCE_MIGRATION_REQUIRED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public function testSourceGenerationAdvanceUsesDistinctSupersessionRefusal(): void
    {
        [$admission, $at] = $this->sealedResponseWithPrincipal();
        [$resolver, $capability] = $this->reconciliationCapability($admission['admission_id'], $at + 1, $at + 100);
        $successor = $this->source;
        $successor['principal_generation']++;
        $successor['principal_version_id'] = 'imperator-principal-generation-'.$successor['principal_generation'];
        $successor['lifecycle']['constituted_at'] = gmdate(DATE_ATOM, $at + 2);
        $successor['lifecycle']['effective_at'] = gmdate(DATE_ATOM, $at + 2);
        $successor = NativeState::seal($successor);
        $this->write(NativeState::SOURCES['principal'].'/'.$successor['principal_version_id'].'.json', $successor);

        $this->fails('REFUSED_SOURCE_SUPERSEDED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public function testBatchFourCurrentnessPathCannotReachCredentialProviderOrNetwork(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthoritySourceResolver.php');
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    private function publishLifecycleDisposition(string $disposition, int $at): void
    {
        $successor = 'SUPERSEDE' === $disposition
            ? ['id' => 'imperator-successor-version', 'digest' => str_repeat('a', 64), 'schema' => $this->source['schema']]
            : null;
        $record = NativeState::seal([
            'schema' => ImperatorPrincipalLifecycleDispositionContract::SCHEMA,
            'disposition_id' => 'reconciliation-source-'.strtolower($disposition),
            'instance_id' => $this->source['instance_id'],
            'operator_root' => ['id' => 'operator-root-test', 'digest' => str_repeat('b', 64), 'schema' => 'imperium.operator-root/v1'],
            'source_principal_version' => NativeState::ref($this->source, 'principal_version_id'),
            'source_status' => 'ACTIVE',
            'disposition' => $disposition,
            'rationale' => 'Batch 4 present-tense reconciliation currentness proof.',
            'effective_at' => gmdate(DATE_ATOM, $at),
            'successor_principal_version' => $successor,
            'authority_scope_changed' => false,
            'historical_attribution_preserved' => true,
            'caller_authority_issuance_permitted_after_effective_at' => false,
            'external_action_performed' => false,
            'sealed' => true,
        ]);
        (new ImperatorPrincipalProvenanceFixtureStore($this->root))->putLifecycleDisposition($record);
    }
}
