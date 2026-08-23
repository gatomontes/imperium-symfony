<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LegateRuntimeActivationService
{
    private string $authorizations;
    private string $bindings;
    private string $assemblies;
    private string $qualifications;
    private string $approvals;
    private string $modelBindings;
    private string $attestations;
    private string $activations;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private StateStore $bootstrap)
    {
        $this->authorizations = $root.'/var/imperium/imperator/citadel-legate-activation-authorization-decisions';
        $this->bindings = $root.'/var/imperium/operational/occupancy';
        $this->assemblies = $root.'/var/imperium/offices/conscription/model-bound-operational-manifestation-assemblies';
        $this->qualifications = $root.'/var/imperium/offices/conscription/model-bound-operational-profile-qualifications';
        $this->approvals = $root.'/var/imperium/imperator/model-bound-profile-approval-decisions';
        $this->modelBindings = $root.'/var/imperium/offices/conscription/profile-model-bindings';
        $this->attestations = $root.'/var/imperium/offices/clavium/profile-model-access-attestations';
        $this->activations = $root.'/var/imperium/operational/citadel-legate-runtime-activations';
    }

    public function activate(string $decisionId, \DateTimeImmutable $activatedAt): array
    {
        if (!preg_match('/^citadel-legate-activation-authorization-decision-[a-f0-9]{20}$/', $decisionId)) {
            throw new \InvalidArgumentException('R230_CITADEL_ACTIVATION_DECISION_ID_INVALID');
        }
        $decision = $this->read($this->authorizations.'/'.$decisionId.'.json', 'R231_CITADEL_ACTIVATION_DECISION_ABSENT');
        foreach (glob($this->activations.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'R237_CITADEL_RUNTIME_ACTIVATION_CONFLICT');
            if (($prior['source_activation_authorization']['id'] ?? null) !== $decisionId) {
                continue;
            }
            if (($prior['source_activation_authorization']['digest'] ?? null) !== ($decision['record_digest'] ?? null)) {
                throw new \RuntimeException('R237_CITADEL_RUNTIME_ACTIVATION_CONFLICT');
            }
            return $prior;
        }

        $binding = $this->source($this->bindings, $decision['source_seat_binding'] ?? [], 'R232_CITADEL_RUNTIME_ACTIVATION_CHAIN_ABSENT');
        $assembly = $this->source($this->assemblies, $decision['source_assembly'] ?? [], 'R232_CITADEL_RUNTIME_ACTIVATION_CHAIN_ABSENT');
        $qualification = $this->source($this->qualifications, $decision['source_qualification'] ?? [], 'R232_CITADEL_RUNTIME_ACTIVATION_CHAIN_ABSENT');
        $approval = $this->source($this->approvals, $decision['source_profile_approval'] ?? [], 'R232_CITADEL_RUNTIME_ACTIVATION_CHAIN_ABSENT');
        $modelBinding = $this->source($this->modelBindings, $decision['source_model_binding'] ?? [], 'R232_CITADEL_RUNTIME_ACTIVATION_CHAIN_ABSENT');
        $attestation = $this->source($this->attestations, $decision['source_access_attestation'] ?? [], 'R232_CITADEL_RUNTIME_ACTIVATION_CHAIN_ABSENT');
        [$instanceId, $recruiter] = $this->recruiter();
        $this->validate($decisionId, $decision, $binding, $assembly, $qualification, $approval, $modelBinding, $attestation, $instanceId, $activatedAt);
        $this->assertCurrentOccupancy($binding);

        $actor = ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']];
        $authority = $decision['runtime_activation_authority'];
        $activationId = 'citadel-legate-runtime-activation-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $binding['record_digest'], $actor, $activatedAt->format(DATE_ATOM)])), 0, 20);

        return $this->save($activationId, [
            'schema' => 'imperium.conscription-citadel-legate-runtime-activation/v1',
            'activation_id' => $activationId,
            'instance_id' => $instanceId,
            'officer_class' => 'LEGATE',
            'case_id' => $decision['case_id'],
            'case_digest' => $decision['case_digest'],
            'activator' => $actor,
            'source_activation_authorization' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_seat_binding' => ['id' => $binding['binding_id'], 'digest' => $binding['record_digest']],
            'source_assembly' => $decision['source_assembly'],
            'source_qualification' => $decision['source_qualification'],
            'source_profile_approval' => $decision['source_profile_approval'],
            'source_model_binding' => $decision['source_model_binding'],
            'source_access_attestation' => $decision['source_access_attestation'],
            'seat' => $binding['seat'],
            'manifestation_id' => $binding['manifestation_id'],
            'occupancy_generation' => $binding['occupancy_generation'],
            'manifestation' => $binding['manifestation'],
            'runtime_activation_authority' => ['id' => $authority['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'activated_at' => $activatedAt->format(DATE_ATOM),
            'status' => 'MODEL_BOUND_CITADEL_LEGATE_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION',
            'runtime_active' => true,
            'commission_intake_available' => true,
            'operational_use_permitted' => false,
            'autonomous_cognition_authority' => false,
            'governed_cognition_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'provider_invocation_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $decisionId, array $decision, array $binding, array $assembly, array $qualification, array $approval, array $modelBinding, array $attestation, string $instanceId, \DateTimeImmutable $activatedAt): void
    {
        $authority = $decision['runtime_activation_authority'] ?? [];
        $expiresAt = $attestation['provider_access_evidence']['expires_at'] ?? null;
        if (!$this->valid($decision) || 'imperium.imperator-citadel-legate-activation-authorization-decision/v1' !== ($decision['schema'] ?? null)
            || $decisionId !== ($decision['decision_id'] ?? null) || $instanceId !== ($decision['instance_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null) || true !== ($decision['activation_authorized'] ?? null)
            || 'MODEL_BOUND_CITADEL_LEGATE_ACTIVATION_AUTHORIZED_PENDING_RUNTIME_ACTIVATION' !== ($decision['status'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null) || false !== ($authority['consumed'] ?? null)
            || 'conscription.recruiter' !== ($authority['destination'] ?? null) || 'ACTIVATE_EXACT_BOUND_CITADEL_LEGATE_MANIFESTATION' !== ($authority['purpose'] ?? null)
            || true === ($decision['runtime_active'] ?? null) || true === ($decision['operational_use_permitted'] ?? null)
            || true === ($decision['provider_invocation_authority'] ?? null) || true === ($decision['execution_authority'] ?? null) || true !== ($decision['sealed'] ?? null)
            || !$this->valid($binding) || 'imperium.model-bound-operational-manifestation-seat-binding/v1' !== ($binding['schema'] ?? null)
            || 'OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION' !== ($binding['status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null) || true !== ($binding['seat_bound'] ?? null) || 1 !== ($binding['occupancy_generation'] ?? null)
            || ($decision['seat'] ?? null) !== ($binding['seat'] ?? null) || ($decision['manifestation_id'] ?? null) !== ($binding['manifestation_id'] ?? null)
            || !$this->valid($assembly) || !$this->valid($qualification) || !$this->valid($approval) || !$this->valid($modelBinding) || !$this->valid($attestation)
            || 'imperium.conscription-model-bound-operational-manifestation-assembly/v1' !== ($assembly['schema'] ?? null)
            || 'imperium.conscription-model-bound-operational-profile-qualification/v1' !== ($qualification['schema'] ?? null)
            || 'imperium.imperator-model-bound-profile-approval-decision/v1' !== ($approval['schema'] ?? null)
            || 'imperium.conscription-profile-model-binding/v1' !== ($modelBinding['schema'] ?? null)
            || 'imperium.clavium-profile-model-access-attestation/v1' !== ($attestation['schema'] ?? null)
            || 'ACCESS_AVAILABLE' !== ($attestation['status'] ?? null) || !is_string($expiresAt) || new \DateTimeImmutable($expiresAt) <= $activatedAt
            || ($binding['manifestation']['profile']['content_digest'] ?? null) !== ($modelBinding['sealed_profile']['content_digest'] ?? null)) {
            throw new \RuntimeException('R233_CITADEL_RUNTIME_ACTIVATION_CHAIN_INVALID');
        }
    }

    private function assertCurrentOccupancy(array $binding): void
    {
        foreach (glob($this->bindings.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'R234_CITADEL_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) !== $binding['seat'] || ($other['binding_id'] ?? null) === $binding['binding_id']) {
                continue;
            }
            if (in_array($other['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)) {
                throw new \RuntimeException('R234_CITADEL_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function source(string $directory, array $reference, string $error): array
    {
        $record = $this->read($directory.'/'.($reference['id'] ?? '').'.json', $error);
        if (!$this->valid($record) || ($reference['digest'] ?? null) !== ($record['record_digest'] ?? null)) {
            throw new \RuntimeException('R233_CITADEL_RUNTIME_ACTIVATION_CHAIN_INVALID');
        }
        return $record;
    }

    private function recruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) {
            throw new \RuntimeException('R235_CITADEL_RECRUITER_UNAVAILABLE');
        }
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null)) {
                return [(string) ($state['binding']['instance_id'] ?? ''), $recruiter];
            }
        }
        throw new \RuntimeException('R235_CITADEL_RECRUITER_UNAVAILABLE');
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->activations) && !mkdir($this->activations, 0770, true) && !is_dir($this->activations)) {
            throw new \RuntimeException('R236_CITADEL_RUNTIME_ACTIVATION_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->activations.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'R237_CITADEL_RUNTIME_ACTIVATION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('R237_CITADEL_RUNTIME_ACTIVATION_CONFLICT');
            }
            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('R236_CITADEL_RUNTIME_ACTIVATION_FAILED');
        }
        return $record;
    }
}
