<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileCandidateDerivationReturnService
{
    private string $dispositions;
    private string $commissions;
    private string $custody;
    private string $occupancy;
    private string $candidates;
    private string $returns;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private ProfileElaborationCognitionGateway $cognition,
    ) {
        $this->dispositions = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-dispositions';
        $this->commissions = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-inbox';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $root.'/var/imperium/offices/laboratorium/occupancy';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->returns = $root.'/var/imperium/offices/conscription/delegate-mission-profile-candidate-return-inbox';
    }

    public function deriveAndReturn(string $dispositionId, \DateTimeImmutable $derivedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-derivation-commission-disposition-[a-f0-9]{20}$/', $dispositionId)) {
            throw new \InvalidArgumentException('L520_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_ID_INVALID');
        }

        $disposition = $this->read($this->dispositions.'/'.$dispositionId.'.json', 'L521_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_ABSENT');
        $commission = $this->source($disposition, 'source_commission', $this->commissions, 'imperium.conscription-laboratorium-delegate-mission-profile-derivation-commission-request/v1', 'request_id');
        $custodyId = $disposition['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'L522_DELEGATE_MISSION_PROFILE_DERIVATION_CUSTODY_ABSENT');
        $bindingId = $disposition['alchemist']['binding_id'] ?? '';
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'L523_DELEGATE_MISSION_PROFILE_DERIVATION_ALCHEMIST_UNAVAILABLE');
        $this->validate($dispositionId, $disposition, $commission, $custody, $binding);

        foreach (glob($this->candidates.'/*.json') ?: [] as $path) {
            $candidate = $this->read($path, 'L527_DELEGATE_MISSION_PROFILE_CANDIDATE_CONFLICT');
            if (($candidate['source_commission_disposition']['id'] ?? null) === $dispositionId) {
                if (($candidate['source_commission_disposition']['digest'] ?? null) !== $disposition['record_digest']) {
                    throw new \RuntimeException('L527_DELEGATE_MISSION_PROFILE_CANDIDATE_CONFLICT');
                }
                $return = $this->findReturn($candidate['candidate_id'], $candidate['record_digest']);

                return ['candidate' => $candidate, 'return' => $return];
            }
        }

        $elaboration = $this->cognition->elaborate($disposition, $commission);
        $this->validateElaboration($elaboration);
        $scope = $disposition['profile_scope'];
        $profile = [
            'target_kind' => 'MISSION_DELEGATE',
            'officer_class' => OfficerClass::Delegate->value,
            'persona' => $disposition['persona'],
            'profession' => $scope['profession'],
            'assignment' => [
                'mission_seat' => $scope['mission_seat'],
                'objective' => $scope['objective'],
                'scope' => $scope['scope'],
                'deliverables' => $scope['deliverables'],
                'required_inputs' => $scope['required_inputs'],
                'capability_requirements' => $scope['capability_requirements'],
                'expected_outcomes' => $scope['expected_outcomes'],
                'bounded_duration' => $scope['bounded_duration'],
            ],
            'resource_requirements' => [
                'data' => $scope['data_requirements'],
                'tools' => $scope['tool_requirements'],
                'credentials' => $scope['credential_requirements'],
                'perimeter' => $scope['perimeter_requirements'],
            ],
            'limitations' => [
                'constraints' => $scope['constraints'],
                'stop_conditions' => $scope['stop_conditions'],
                'imperator_personnel_use_limitations' => $commission['imperator_limitations'],
                'custody_bound' => true,
                'persona_substitution_permitted' => false,
                'mission_scope_mutation_permitted' => false,
            ],
            'termination' => [
                'return_conditions' => $scope['return_conditions'],
                'unbinding_conditions' => $scope['unbinding_conditions'],
                'custody_restoration_conditions' => $scope['custody_restoration_conditions'],
                'retirement_conditions' => $scope['retirement_conditions'],
            ],
            'governance' => [
                'profile_steward' => $scope['profile_steward'],
                'transformer' => $scope['prospective_deriver'],
                'prospective_examiner' => $scope['prospective_examiner'],
                'prospective_approver' => $scope['prospective_approver'],
            ],
            'elaboration' => $elaboration,
        ];
        $profileId = 'delegate-mission-profile-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], 1, $profile])), 0, 20);
        $candidateId = 'delegate-mission-profile-candidate-'.substr(hash('sha256', CanonicalJson::encode([$profileId, 1, $profile, $disposition['record_digest']])), 0, 20);
        $candidate = $this->save($this->candidates, $candidateId, [
            'schema' => 'imperium.laboratorium-delegate-mission-profile-candidate/v1',
            'candidate_id' => $candidateId,
            'profile_id' => $profileId,
            'profile_version' => 1,
            'supersedes' => null,
            'instance_id' => $disposition['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'alchemist' => $disposition['alchemist'],
            'source_commission_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
            'source_commission' => $disposition['source_commission'],
            'source_conscription_acceptance' => $disposition['source_conscription_acceptance'],
            'source_imperator_decision' => $disposition['source_imperator_decision'],
            'source_reservation_disposition' => $disposition['source_reservation_disposition'],
            'persona' => $disposition['persona'],
            'profile_scope' => $scope,
            'custody_lease' => $disposition['custody_lease'],
            'return_destination' => $disposition['return_destination'],
            'profile' => $profile,
            'profile_derivation_authority' => ['id' => $disposition['profile_derivation_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'profile_derivation_cognition_completed' => true,
            'profile_derived' => true,
            'profile_candidate_created' => true,
            'derived_at' => $derivedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED',
            'profile_approval_authority' => false,
            'profile_activation_authority' => false,
            'profile_installation_authority' => false,
            'profile_examination_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'operational_use_authority' => false,
            'cognition_authority' => false,
            'provider_invocation_authority' => false,
            'data_access_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'mission_plan_amendment_authority' => false,
            'follow_up_commission_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ], 'L524_DELEGATE_MISSION_PROFILE_CANDIDATE_PERSISTENCE_FAILED', 'L527_DELEGATE_MISSION_PROFILE_CANDIDATE_CONFLICT');

        $returnId = 'delegate-mission-profile-candidate-return-'.substr(hash('sha256', CanonicalJson::encode([$candidateId, $candidate['record_digest'], $disposition['return_destination']])), 0, 20);
        $return = $this->save($this->returns, $returnId, [
            'schema' => 'imperium.laboratorium-conscription-delegate-mission-profile-candidate-return/v1',
            'return_id' => $returnId,
            'instance_id' => $candidate['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'sender' => $candidate['alchemist'],
            'recipient' => ['office' => 'conscription', 'seat' => 'conscription.recruiter', 'intake_pending' => true],
            'source_profile_candidate' => ['id' => $candidateId, 'digest' => $candidate['record_digest']],
            'source_commission_disposition' => $candidate['source_commission_disposition'],
            'source_commission' => $candidate['source_commission'],
            'source_reservation_disposition' => $candidate['source_reservation_disposition'],
            'profile' => ['profile_id' => $profileId, 'profile_version' => 1, 'candidate_id' => $candidateId, 'candidate_digest' => $candidate['record_digest']],
            'persona' => $candidate['persona'],
            'profile_scope' => $candidate['profile_scope'],
            'custody_lease' => $candidate['custody_lease'],
            'profile_candidate_returned' => true,
            'profile_candidate_intake_disposition_authority' => [
                'authority_id' => 'delegate-mission-profile-candidate-intake-disposition-authority-'.substr(hash('sha256', CanonicalJson::encode([$returnId, $candidate['record_digest']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'conscription.recruiter',
                'purpose' => 'DECIDE_INTAKE_OF_ONE_EXACT_DELEGATE_MISSION_PROFILE_CANDIDATE',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'returned_at' => $derivedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_INTAKE',
            'profile_approval_authority' => false,
            'profile_activation_authority' => false,
            'profile_installation_authority' => false,
            'profile_examination_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'operational_use_authority' => false,
            'cognition_authority' => false,
            'provider_invocation_authority' => false,
            'data_access_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'mission_plan_amendment_authority' => false,
            'follow_up_commission_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ], 'L525_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_FAILED', 'L528_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_CONFLICT');

        return ['candidate' => $candidate, 'return' => $return];
    }

    private function validate(string $id, array $disposition, array $commission, array $custody, array $binding): void
    {
        $authority = $disposition['profile_derivation_authority'] ?? null;
        if (!$this->valid($disposition) || !$this->valid($custody) || !$this->valid($binding)
            || 'imperium.laboratorium-delegate-mission-profile-derivation-commission-disposition/v1' !== ($disposition['schema'] ?? null)
            || $id !== ($disposition['disposition_id'] ?? null)
            || 'ACCEPTED' !== ($disposition['disposition'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION' !== ($disposition['status'] ?? null)
            || true !== ($disposition['recipient_acceptance'] ?? null)
            || true !== ($disposition['profile_derivation_authority_exercisable'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'laboratorium.alchemist' !== ($authority['holder'] ?? null)
            || 'DERIVE_ONE_EXACT_DELEGATE_MISSION_PROFILE_CANDIDATE' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || hash('sha256', CanonicalJson::encode($disposition['profile_scope'] ?? null)) !== ($authority['profile_scope_digest'] ?? null)
            || true === ($disposition['profile_derived'] ?? null)
            || CanonicalJson::encode($disposition['profile_scope'] ?? null) !== CanonicalJson::encode($commission['profile_scope'] ?? null)
            || CanonicalJson::encode($disposition['persona'] ?? null) !== CanonicalJson::encode($commission['persona'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($disposition['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($disposition['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($authority['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || ($disposition['instance_id'] ?? null) !== ($custody['instance_id'] ?? null)
            || ($disposition['alchemist']['binding_id'] ?? null) !== ($binding['binding_id'] ?? null)
            || ($disposition['alchemist']['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || 'laboratorium.alchemist' !== ($binding['seat'] ?? null)
            || OfficerClass::Legate->value !== ($binding['officer_class'] ?? null)
            || 'ACTIVE' !== ($binding['status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null)
            || true === ($binding['execution_authority'] ?? null)
            || true !== ($disposition['sealed'] ?? null)
            || true !== ($commission['sealed'] ?? null)) {
            throw new \RuntimeException('L526_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        }
    }

    private function validateElaboration(array $elaboration): void
    {
        $expected = ['disposition', 'operating_posture', 'responsibilities', 'non_responsibilities', 'reasoning_priorities', 'evidence_discipline', 'tool_use_directives', 'input_handling', 'output_contract', 'escalation_conditions', 'uncertainty_behavior', 'failure_behavior', 'persona_adaptations'];
        $keys = array_keys($elaboration);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($keys !== $expected || 'PROFILE_ELABORATION_COMPLETE' !== ($elaboration['disposition'] ?? null) || !is_string($elaboration['operating_posture'] ?? null) || '' === trim($elaboration['operating_posture'])) {
            throw new \RuntimeException('L529_DELEGATE_MISSION_PROFILE_ELABORATION_INVALID');
        }
        foreach (array_diff($expected, ['disposition', 'operating_posture']) as $field) {
            if (!is_array($elaboration[$field]) || [] === $elaboration[$field]) {
                throw new \RuntimeException('L529_DELEGATE_MISSION_PROFILE_ELABORATION_INVALID');
            }
            foreach ($elaboration[$field] as $item) {
                if (!is_string($item) || '' === trim($item)) {
                    throw new \RuntimeException('L529_DELEGATE_MISSION_PROFILE_ELABORATION_INVALID');
                }
            }
        }
    }

    private function findReturn(string $candidateId, string $candidateDigest): array
    {
        foreach (glob($this->returns.'/*.json') ?: [] as $path) {
            $return = $this->read($path, 'L528_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_CONFLICT');
            if (($return['source_profile_candidate']['id'] ?? null) === $candidateId) {
                if (!$this->valid($return) || ($return['source_profile_candidate']['digest'] ?? null) !== $candidateDigest) {
                    throw new \RuntimeException('L528_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_CONFLICT');
                }

                return $return;
            }
        }
        throw new \RuntimeException('L528_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_CONFLICT');
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('L526_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'L526_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('L526_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        }

        return $result;
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

    private function save(string $directory, string $id, array $record, string $failure, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException($failure);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $directory.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, $conflict);
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException($conflict);
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException($failure);
        }

        return $record;
    }
}
