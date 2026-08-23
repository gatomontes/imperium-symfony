<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CitadelOfficerActivationAuthorizationDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];
    private string $bindings;
    private string $assemblies;
    private string $qualifications;
    private string $approvals;
    private string $modelBindings;
    private string $attestations;
    private string $decisions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->bindings = $root.'/var/imperium/operational/occupancy';
        $this->assemblies = $root.'/var/imperium/offices/conscription/model-bound-operational-manifestation-assemblies';
        $this->qualifications = $root.'/var/imperium/offices/conscription/model-bound-operational-profile-qualifications';
        $this->approvals = $root.'/var/imperium/imperator/model-bound-profile-approval-decisions';
        $this->modelBindings = $root.'/var/imperium/offices/conscription/profile-model-bindings';
        $this->attestations = $root.'/var/imperium/offices/clavium/profile-model-access-attestations';
        $this->decisions = $root.'/var/imperium/imperator/citadel-officer-activation-authorization-decisions';
    }

    public function decide(string $bindingId, string $disposition, string $response, string $limitations, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^model-bound-operational-seat-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('I240_CITADEL_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $response = trim($response);
        $limitations = trim($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $response || '' === $limitations) {
            throw new \InvalidArgumentException('I241_CITADEL_ACTIVATION_DISPOSITION_INVALID');
        }

        $binding = $this->read($this->bindings.'/'.$bindingId.'.json', 'I242_CITADEL_BINDING_ABSENT');
        [$assembly, $qualification, $approval, $modelBinding, $attestation] = $this->chain($binding, $bindingId, $decidedAt);
        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'I245_CITADEL_ACTIVATION_DECISION_CONFLICT');
            if (($prior['source_seat_binding']['id'] ?? null) !== $bindingId) {
                continue;
            }
            if (($prior['disposition'] ?? null) === $disposition && ($prior['response'] ?? null) === $response && ($prior['limitations'] ?? null) === $limitations) {
                return $prior;
            }
            throw new \RuntimeException('I245_CITADEL_ACTIVATION_DECISION_CONFLICT');
        }

        $authorized = 'AUTHORIZED' === $disposition;
        $id = 'citadel-officer-activation-authorization-decision-'.substr(hash('sha256', CanonicalJson::encode([$bindingId, $binding['record_digest'], self::IMPERATOR_ID, $disposition, $response, $limitations, $decidedAt->format(DATE_ATOM)])), 0, 20);
        $authorityId = $authorized ? 'citadel-runtime-activation-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $bindingId, $binding['record_digest']])), 0, 20) : null;

        return $this->save($id, [
            'schema' => 'imperium.imperator-citadel-officer-activation-authorization-decision/v1',
            'decision_id' => $id,
            'instance_id' => $binding['instance_id'],
            'case_id' => $binding['case_id'],
            'case_digest' => $binding['case_digest'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_seat_binding' => ['id' => $bindingId, 'digest' => $binding['record_digest']],
            'source_assembly' => ['id' => $assembly['assembly_id'], 'digest' => $assembly['record_digest']],
            'source_qualification' => ['id' => $qualification['qualification_id'], 'digest' => $qualification['record_digest']],
            'source_profile_approval' => ['id' => $approval['decision_id'], 'digest' => $approval['record_digest']],
            'source_model_binding' => ['id' => $modelBinding['binding_id'], 'digest' => $modelBinding['record_digest']],
            'source_access_attestation' => ['id' => $attestation['attestation_id'], 'digest' => $attestation['record_digest']],
            'seat' => $binding['seat'],
            'manifestation_id' => $binding['manifestation_id'],
            'occupancy_generation' => $binding['occupancy_generation'],
            'manifestation' => $binding['manifestation'],
            'disposition' => $disposition,
            'response' => $response,
            'limitations' => $limitations,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'status' => $authorized ? 'MODEL_BOUND_MANIFESTATION_ACTIVATION_AUTHORIZED_PENDING_RUNTIME_ACTIVATION' : 'NON_AUTHORIZING_CITADEL_ACTIVATION_DISPOSITION_RECORDED',
            'activation_authorized' => $authorized,
            'runtime_activation_authority' => $authorized ? ['authority_id' => $authorityId, 'authority_single_use' => true, 'destination' => 'conscription.recruiter', 'purpose' => 'ACTIVATE_EXACT_BOUND_CITADEL_MANIFESTATION', 'consumed' => false] : null,
            'runtime_active' => false,
            'operational_use_permitted' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'provider_invocation_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_cognition_authority' => false,
            'sealed' => true,
        ]);
    }

    private function chain(array $binding, string $bindingId, \DateTimeImmutable $decidedAt): array
    {
        $assembly = $this->read($this->assemblies.'/'.($binding['source_assembly']['id'] ?? '').'.json', 'I243_CITADEL_ACTIVATION_CHAIN_ABSENT');
        $qualification = $this->read($this->qualifications.'/'.($binding['source_qualification']['id'] ?? '').'.json', 'I243_CITADEL_ACTIVATION_CHAIN_ABSENT');
        $approval = $this->read($this->approvals.'/'.($binding['source_imperator_approval']['id'] ?? '').'.json', 'I243_CITADEL_ACTIVATION_CHAIN_ABSENT');
        $modelBinding = $this->read($this->modelBindings.'/'.($binding['source_model_binding']['id'] ?? '').'.json', 'I243_CITADEL_ACTIVATION_CHAIN_ABSENT');
        $attestation = $this->read($this->attestations.'/'.($binding['source_access_attestation']['id'] ?? '').'.json', 'I243_CITADEL_ACTIVATION_CHAIN_ABSENT');
        $expiresAt = $attestation['provider_access_evidence']['expires_at'] ?? null;
        if (!$this->valid($binding) || !$this->valid($assembly) || !$this->valid($qualification) || !$this->valid($approval) || !$this->valid($modelBinding) || !$this->valid($attestation)
            || 'imperium.model-bound-operational-manifestation-seat-binding/v1' !== ($binding['schema'] ?? null)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || 'OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION' !== ($binding['status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null) || true !== ($binding['seat_bound'] ?? null)
            || 0 !== ($binding['prior_occupancy_generation'] ?? null) || 1 !== ($binding['occupancy_generation'] ?? null)
            || null !== ($binding['supersedes'] ?? null) || true === ($binding['supersession_authority'] ?? null)
            || true === ($binding['operational_use_permitted'] ?? null) || true === ($binding['deployment_authority'] ?? null)
            || true === ($binding['custody_transfer_authority'] ?? null) || true === ($binding['tool_use_authority'] ?? null)
            || true === ($binding['credential_use_authority'] ?? null) || true === ($binding['external_action_authority'] ?? null)
            || true === ($binding['execution_authority'] ?? null) || true !== ($binding['sealed'] ?? null)
            || 'imperium.conscription-model-bound-operational-manifestation-assembly/v1' !== ($assembly['schema'] ?? null)
            || 'OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_SEAT_BINDING' !== ($assembly['status'] ?? null)
            || 'imperium.conscription-model-bound-operational-profile-qualification/v1' !== ($qualification['schema'] ?? null)
            || 'PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY' !== ($qualification['status'] ?? null)
            || 'imperium.imperator-model-bound-profile-approval-decision/v1' !== ($approval['schema'] ?? null)
            || 'imperium.conscription-profile-model-binding/v1' !== ($modelBinding['schema'] ?? null)
            || 'imperium.clavium-profile-model-access-attestation/v1' !== ($attestation['schema'] ?? null)
            || ($binding['source_assembly']['digest'] ?? null) !== $assembly['record_digest']
            || ($binding['source_qualification']['digest'] ?? null) !== $qualification['record_digest']
            || ($binding['source_imperator_approval']['digest'] ?? null) !== $approval['record_digest']
            || ($binding['source_model_binding']['digest'] ?? null) !== $modelBinding['record_digest']
            || ($binding['source_access_attestation']['digest'] ?? null) !== $attestation['record_digest']
            || 'APPROVED' !== ($approval['disposition'] ?? null) || true !== ($approval['profile_approved'] ?? null)
            || ($binding['manifestation']['profile']['content_digest'] ?? null) !== ($modelBinding['sealed_profile']['content_digest'] ?? null)
            || 'ACCESS_AVAILABLE' !== ($attestation['status'] ?? null) || !is_string($expiresAt)
            || new \DateTimeImmutable($expiresAt) <= $decidedAt) {
            throw new \RuntimeException('I244_CITADEL_ACTIVATION_CHAIN_INVALID');
        }
        return [$assembly, $qualification, $approval, $modelBinding, $attestation];
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
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) {
            throw new \RuntimeException('I246_CITADEL_ACTIVATION_DECISION_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->decisions.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'I245_CITADEL_ACTIVATION_DECISION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('I245_CITADEL_ACTIVATION_DECISION_CONFLICT');
            }
            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('I246_CITADEL_ACTIVATION_DECISION_FAILED');
        }
        return $record;
    }
}
