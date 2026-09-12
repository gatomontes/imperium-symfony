<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore, Rules as R, Policy};
use App\Imperium\Runtime\Onboarding\Ledger\{PreparedOperation,SourceAuthority};

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AccessAdapter implements PreparedOperation, SourceAuthority
{
    public function __construct(private ?array $grant = null,
        private EvidenceVerifier $evidence = new MissingEvidence(), private ?KeySource $keys = null) {}

    /** Infrastructure identity only; authority still comes from verify() and retained originals. */
    public function requireDeliverySource(?KeySource $source): void
    {
        R::require($source === $this->keys, 'DEEPSEEK_DELIVERY_SOURCE_MISMATCH');
    }

    private function terms(): array
    {
        R::require($this->grant !== null, 'DEEPSEEK_GRANT_MISSING'); R::record($this->grant);
        $g=Policy::content($this->grant,'deepseek-access-grant');
        R::object($g,['schema','configuration_ref','executor','credential_operation','account_evidence_ref','maximum','expires_at']);
        R::require($g['schema'] === 'imperium.deepseek-access-grant/v1', 'DEEPSEEK_GRANT_SCHEMA');
        R::object($g['executor'],['kind','adapter_ref','deployment_custody_ref','credential_binding_ref']);
        R::require($g['executor']['kind'] === 'infrastructure', 'DEEPSEEK_EXECUTOR');
        foreach (['adapter_ref','deployment_custody_ref','credential_binding_ref'] as $k) { R::ref($g['executor'][$k]); }
        R::ref($g['configuration_ref']); R::ref($g['account_evidence_ref']); R::text($g['credential_operation']); R::time($g['expires_at']);
        R::require(R::same($g['maximum'],['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>10000]), 'DEEPSEEK_MAXIMUM');
        return $g;
    }
    public function prepare(array $terms): array
    {
        $g=$this->terms();
        R::record($terms); R::object($terms['body'],['effect','terms','required_completed_refs']);
        R::require($terms['schema'] === 'imperium.bootstrap-proposed-terms/v1'
            && $terms['body']['effect'] === 'AUTHORIZE_BOOTSTRAP_ACCESS'
            && R::same($terms['body']['terms'],R::reference($this->grant)), 'DEEPSEEK_TERMS');
        return ['schema'=>'imperium.bootstrap-prepared-operation/v1','wire'=>'',
            'destination'=>'https://api.deepseek.com:443/models','method'=>'GET','provider'=>'deepseek',
            'model'=>'access-listing-only','configuration_ref'=>$g['configuration_ref'],
            'credential_operation'=>$g['credential_operation'],'adapter'=>Wire::ADAPTER,'maximum'=>$g['maximum'],
            'expires_at'=>$g['expires_at'],'authority_source'=>['kind'=>'access','grant_ref'=>R::reference($this->grant),'executor'=>$g['executor']]];
    }
    public function verify(AuthorityStore $store, array $state, array $policy, string $effect, array $terms, array $operation): void
    {
        R::require($effect === 'AUTHORIZE_BOOTSTRAP_ACCESS', 'O3_B1_PRODUCER_MISSING');
        $s=$store->state($state); $g=$this->terms();
        R::require(R::same($store->checkSource($s,R::reference($this->grant)),$this->grant)
            && R::same($store->checkSource($s,R::reference($terms)),$terms)
            && R::same($this->prepare($terms),$operation), 'DEEPSEEK_ORIGINAL');
        $x=$g['executor'];
        R::require(R::same($x['adapter_ref'],$policy['body']['adapter_ref'])
            && R::same($x['credential_binding_ref'],$policy['body']['credential_ref'])
            && $store->now() < $g['expires_at'] && $g['expires_at'] <= $policy['body']['expires_at'], 'DEEPSEEK_POLICY_BINDING');
        $adapter=Policy::content($store->checkSource($s,$x['adapter_ref']),'adapter-description');
        R::object($adapter,['schema','adapter']);
        R::require(R::same($adapter,['schema'=>'imperium.deepseek-adapter/v1','adapter'=>Wire::ADAPTER]), 'DEEPSEEK_ADAPTER');
        $custody=Policy::content($store->checkSource($s,$x['deployment_custody_ref']),'deployment-custody');
        R::object($custody,['schema','custody','credential_binding_ref']);
        R::require($custody['schema'] === 'imperium.deepseek-custody/v1' && $custody['custody'] === Runtime::CUSTODY
            && R::same($custody['credential_binding_ref'],$x['credential_binding_ref']), 'DEEPSEEK_CUSTODY');
        $binding=Policy::content($store->checkSource($s,$x['credential_binding_ref']),'credential-binding');
        R::object($binding,['authentication','provider','opaque_binding']);
        R::require($binding['authentication'] === 'api-key' && $binding['provider'] === 'deepseek'
            && $this->keys !== null && $this->keys->generation() === $binding['opaque_binding'], 'DEEPSEEK_KEY_CURRENT');
        $configuration=Policy::content($store->checkSource($s,$g['configuration_ref']),'request-configuration');
        R::require(R::same($configuration,Wire::CONFIGURATION), 'DEEPSEEK_CONFIGURATION');
        foreach ($policy['body']['candidate_bindings'] as $candidate) {
            R::require(R::same($candidate['configuration_ref'],$g['configuration_ref']), 'DEEPSEEK_CONFIGURATION_BINDING');
        }
        $account=$store->checkSource($s,$g['account_evidence_ref']);
        $this->evidence->access($account,$g,$store->now());
    }
    public function validateResponse(AuthorityStore $store,array $state,array $policy,string $effect,array $operation,array $envelope): string
    {
        R::require($effect === 'AUTHORIZE_BOOTSTRAP_ACCESS' && $operation['adapter'] === Wire::ADAPTER, 'O3_B1_PRODUCER_MISSING');
        $mapped=ProviderResponse::listing($envelope['response'],$envelope['metadata']['usage']['milliseconds']);
        R::require($envelope['metadata']['provider_response_id'] === $mapped['identity']
            && R::same($envelope['metadata']['usage'],$mapped['usage'])
            && $envelope['operation_digest'] === R::hash($operation)
            && $envelope['metadata']['provenance'] === Wire::ADAPTER, 'DEEPSEEK_RESPONSE_ATTRIBUTION');
        return 'SUCCEEDED';
    }
    public function select(AuthorityStore $store,array $state,array $policy,array $step): array
    {
        throw new \RuntimeException('O3_AUTHENTIC_SELECTION_PROJECTION_MISSING');
    }
}
