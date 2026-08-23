<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LegateProviderInvocationActivationService
{
    private string $decisions;
    private string $runtimeActivations;
    private string $modelBindings;
    private string $attestations;
    private string $assertions;
    private string $occupancy;
    private string $activations;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->decisions = $root.'/var/imperium/operational/citadel-legate-cognition-turn-authorization-decisions';
        $this->runtimeActivations = $root.'/var/imperium/operational/citadel-legate-runtime-activations';
        $this->modelBindings = $root.'/var/imperium/offices/conscription/profile-model-bindings';
        $this->attestations = $root.'/var/imperium/offices/clavium/profile-model-access-attestations';
        $this->assertions = $root.'/var/imperium/offices/clavium/provider-access-assertions';
        $this->occupancy = $root.'/var/imperium/offices/clavium/occupancy';
        $this->activations = $root.'/var/imperium/offices/clavium/citadel-legate-provider-invocation-activations';
    }

    public function activate(string $decisionId, string $authorityId, string $locksmithBindingId, \DateTimeImmutable $activatedAt): array
    {
        if (!preg_match('/^citadel-legate-cognition-turn-authorization-decision-[a-f0-9]{20}$/', $decisionId)
            || !preg_match('/^citadel-legate-provider-invocation-activation-authority-[a-f0-9]{20}$/', $authorityId)) {
            throw new \InvalidArgumentException('CLV301_PROVIDER_INVOCATION_ACTIVATION_ID_INVALID');
        }
        $decision = $this->read($this->decisions.'/'.$decisionId.'.json', 'CLV302_COGNITION_TURN_AUTHORIZATION_ABSENT');
        foreach (glob($this->activations.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CLV309_PROVIDER_INVOCATION_ACTIVATION_CONFLICT');
            if (($prior['source_cognition_turn_authorization']['id'] ?? null) === $decisionId) {
                if (($prior['source_cognition_turn_authorization']['digest'] ?? null) !== ($decision['record_digest'] ?? null)) {
                    throw new \RuntimeException('CLV309_PROVIDER_INVOCATION_ACTIVATION_CONFLICT');
                }
                return $prior;
            }
        }
        $runtime = $this->source($this->runtimeActivations, $decision['source_runtime_activation'] ?? [], false);
        $binding = $this->source($this->modelBindings, $runtime['source_model_binding'] ?? [], false);
        $attestation = $this->source($this->attestations, $decision['source_access_attestation'] ?? [], false);
        $evidence = $attestation['provider_access_evidence'] ?? [];
        $assertion = $this->read($this->assertions.'/'.($evidence['assertion_id'] ?? '').'.json', 'CLV303_PROVIDER_ACCESS_ASSERTION_ABSENT');
        $locksmith = $this->read($this->occupancy.'/'.$locksmithBindingId.'.json', 'CLV304_LOCKSMITH_OCCUPANCY_ABSENT');
        $this->validate($decisionId, $decision, $authorityId, $runtime, $binding, $attestation, $assertion, $locksmithBindingId, $locksmith, $activatedAt);

        $actor = ['seat' => 'clavium.locksmith', 'binding_id' => $locksmithBindingId, 'binding_digest' => $locksmith['record_digest'], 'manifestation_id' => $locksmith['manifestation_id'], 'occupancy_generation' => $locksmith['occupancy_generation']];
        $activationId = 'citadel-legate-provider-invocation-activation-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $authorityId, $actor, $binding['record_digest'], $assertion['record_digest']])), 0, 20);
        $leaseId = 'citadel-legate-provider-credential-lease-'.substr(hash('sha256', CanonicalJson::encode([$activationId, $assertion['credential_ref'], $decision['target']])), 0, 20);
        $leaseExpiry = new \DateTimeImmutable($decision['expires_at']);
        foreach ([$attestation['provider_access_evidence']['expires_at'], $assertion['revalidation']['expires_at']] as $candidate) {
            $candidateExpiry = new \DateTimeImmutable($candidate);
            if ($candidateExpiry < $leaseExpiry) $leaseExpiry = $candidateExpiry;
        }
        $leaseExpiresAt = $leaseExpiry->format(DATE_ATOM);

        return $this->save($activationId, [
            'schema' => 'imperium.clavium-citadel-legate-provider-invocation-activation/v1',
            'activation_id' => $activationId,
            'instance_id' => $decision['instance_id'],
            'case_id' => $decision['case_id'],
            'case_digest' => $decision['case_digest'],
            'source_cognition_turn_authorization' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_runtime_activation' => $decision['source_runtime_activation'],
            'source_model_binding' => $runtime['source_model_binding'],
            'source_access_attestation' => $decision['source_access_attestation'],
            'source_provider_access_assertion' => ['id' => $assertion['assertion_id'], 'digest' => $assertion['record_digest']],
            'locksmith' => $actor,
            'target' => $decision['target'],
            'contract' => $decision['contract'],
            'model' => ['provider_model_version' => $binding['sealed_profile']['model_binding']['provider_model_version'], 'configuration' => $binding['sealed_profile']['model_binding']['configuration'] ?? []],
            'provider_invocation_activation_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
            'bounded_cognition_turn_authority' => $decision['bounded_cognition_turn_authority'],
            'credential_lease' => [
                'lease_id' => $leaseId,
                'authority_single_use' => true,
                'credential_reference_digest' => hash('sha256', $assertion['credential_ref']),
                'credential_reference_disclosed' => false,
                'credential_possession_transferred' => false,
                'scope' => ['model.invoke'],
                'provider' => $assertion['provider'],
                'expires_at' => $leaseExpiresAt,
                'consumed' => false,
            ],
            'activated_at' => $activatedAt->format(DATE_ATOM),
            'expires_at' => $leaseExpiresAt,
            'status' => 'CITADEL_LEGATE_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN',
            'commission_exercisable' => true,
            'governed_cognition_authority' => true,
            'provider_invocation_authority' => true,
            'credential_use_authority' => true,
            'provider_invoked' => false,
            'cognition_performed' => false,
            'operational_use_permitted' => false,
            'autonomous_cognition_authority' => false,
            'tool_use_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $decisionId, array $decision, string $authorityId, array $runtime, array $binding, array $attestation, array $assertion, string $locksmithBindingId, array $locksmith, \DateTimeImmutable $at): void
    {
        $activationAuthority = $decision['provider_invocation_activation_authority'] ?? [];
        $turnAuthority = $decision['bounded_cognition_turn_authority'] ?? [];
        $evidence = $attestation['provider_access_evidence'] ?? [];
        $providerModel = $binding['sealed_profile']['model_binding']['provider_model_version'] ?? null;
        $provider = is_string($providerModel) ? strstr($providerModel, '/', true) : false;
        if (!$this->valid($decision, false) || 'imperium.citadel-legate-cognition-turn-authorization-decision/v1' !== ($decision['schema'] ?? null) || $decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['decision'] ?? null) || 'CITADEL_LEGATE_COGNITION_TURN_AUTHORIZED_PENDING_PROVIDER_INVOCATION_ACTIVATION' !== ($decision['status'] ?? null)
            || true !== ($decision['governed_cognition_authority'] ?? null) || true !== ($decision['provider_invocation_activation_required'] ?? null)
            || $authorityId !== ($activationAuthority['authority_id'] ?? null) || true !== ($activationAuthority['authority_single_use'] ?? null) || false !== ($activationAuthority['consumed'] ?? null)
            || 'clavium.locksmith' !== ($activationAuthority['destination'] ?? null) || 'ACTIVATE_ONE_EXACT_PROVIDER_INVOCATION' !== ($activationAuthority['purpose'] ?? null)
            || true !== ($turnAuthority['authority_single_use'] ?? null) || 1 !== ($turnAuthority['maximum_turns'] ?? null) || false !== ($turnAuthority['consumed'] ?? null)
            || new \DateTimeImmutable($decision['expires_at']) <= $at || true === ($decision['provider_invocation_authority'] ?? null) || true !== ($decision['sealed'] ?? null)
            || !$this->valid($runtime, false) || true !== ($runtime['runtime_active'] ?? null) || true !== ($runtime['sealed'] ?? null)
            || !$this->valid($binding, false) || 'imperium.conscription-profile-model-binding/v1' !== ($binding['schema'] ?? null) || true !== ($binding['sealed'] ?? null)
            || false === $provider || $provider !== ($assertion['provider'] ?? null) || $provider !== ($evidence['provider'] ?? null)
            || !$this->valid($attestation, false) || 'ACCESS_AVAILABLE' !== ($attestation['status'] ?? null) || new \DateTimeImmutable($attestation['provider_access_evidence']['expires_at']) <= $at
            || ($attestation['model_binding']['binding_id'] ?? null) !== ($binding['binding_id'] ?? null) || ($attestation['model_binding']['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || !$this->valid($assertion, true) || ($evidence['assertion_digest'] ?? null) !== ($assertion['record_digest'] ?? null)
            || 'ACCESS_AVAILABLE' !== ($assertion['status'] ?? null) || !in_array('model.invoke', $assertion['scope'] ?? [], true) || new \DateTimeImmutable($assertion['revalidation']['expires_at']) <= $at
            || !is_string($assertion['credential_ref'] ?? null) || !str_starts_with($assertion['credential_ref'], 'clavium://')
            || !$this->valid($locksmith, false) || $locksmithBindingId !== ($locksmith['binding_id'] ?? null) || ($decision['instance_id'] ?? null) !== ($locksmith['instance_id'] ?? null)
            || 'clavium.locksmith' !== ($locksmith['seat'] ?? null) || 'ACTIVE' !== ($locksmith['status'] ?? null) || true !== ($locksmith['provider_invocation_activation_authority'] ?? null)
            || true === ($locksmith['credential_disclosure_authority'] ?? null) || true === ($locksmith['execution_authority'] ?? null)) {
            throw new \RuntimeException('CLV305_PROVIDER_INVOCATION_ACTIVATION_CHAIN_INVALID');
        }
    }

    private function source(string $directory, array $reference, bool $prefixed): array
    {
        $record = $this->read($directory.'/'.($reference['id'] ?? '').'.json', 'CLV306_PROVIDER_INVOCATION_SOURCE_ABSENT');
        if (!$this->valid($record, $prefixed) || ($reference['digest'] ?? null) !== ($record['record_digest'] ?? null)) throw new \RuntimeException('CLV305_PROVIDER_INVOCATION_ACTIVATION_CHAIN_INVALID');
        return $record;
    }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record, bool $prefixed): bool { $digest=$record['record_digest']??null; unset($record['record_digest']); return is_string($digest)&&hash_equals($digest,($prefixed?'sha256:':'').hash('sha256',CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array
    {
        if (!is_dir($this->activations)&&!mkdir($this->activations,0770,true)&&!is_dir($this->activations)) throw new \RuntimeException('CLV307_PROVIDER_INVOCATION_ACTIVATION_FAILED');
        $record['record_digest']=hash('sha256',CanonicalJson::encode($record));$path=$this->activations.'/'.$id.'.json';
        if(is_file($path)){if(CanonicalJson::encode($prior=$this->read($path,'CLV309_PROVIDER_INVOCATION_ACTIVATION_CONFLICT'))!==CanonicalJson::encode($record))throw new \RuntimeException('CLV309_PROVIDER_INVOCATION_ACTIVATION_CONFLICT');return $prior;}
        if(false===file_put_contents($path,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX))throw new \RuntimeException('CLV307_PROVIDER_INVOCATION_ACTIVATION_FAILED');return $record;
    }
}
