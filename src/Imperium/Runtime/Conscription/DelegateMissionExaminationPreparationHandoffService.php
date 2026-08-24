<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionExaminationPreparationHandoffService
{
    private string $intakes;
    private string $candidates;
    private string $returns;
    private string $custody;
    private string $handoffs;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private StateStore $bootstrap)
    {
        $this->intakes = $root.'/var/imperium/offices/conscription/delegate-mission-profile-candidate-intake-dispositions';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->returns = $root.'/var/imperium/offices/conscription/delegate-mission-profile-candidate-return-inbox';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->handoffs = $root.'/var/imperium/offices/senate/delegate-mission-examination-preparation-inbox';
    }

    public function prepare(string $intakeDispositionId, \DateTimeImmutable $preparedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-candidate-intake-disposition-[a-f0-9]{20}$/', $intakeDispositionId)) {
            throw new \InvalidArgumentException('R530_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_ID_INVALID');
        }

        $intake = $this->read($this->intakes.'/'.$intakeDispositionId.'.json', 'R531_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_ABSENT');
        $candidate = $this->source($intake, 'source_profile_candidate', $this->candidates, 'imperium.laboratorium-delegate-mission-profile-candidate/v1', 'candidate_id');
        $return = $this->source($intake, 'source_return', $this->returns, 'imperium.laboratorium-conscription-delegate-mission-profile-candidate-return/v1', 'return_id');
        $custodyId = $intake['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'R532_DELEGATE_MISSION_EXAMINATION_PREPARATION_CUSTODY_ABSENT');
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $this->validate($intakeDispositionId, $intake, $candidate, $return, $custody, $instanceId, $recruiter);

        foreach (glob($this->handoffs.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'R535_DELEGATE_MISSION_EXAMINATION_PREPARATION_CONFLICT');
            if (($prior['source_intake_disposition']['id'] ?? null) === $intakeDispositionId) {
                if (($prior['source_intake_disposition']['digest'] ?? null) !== $intake['record_digest']) {
                    throw new \RuntimeException('R535_DELEGATE_MISSION_EXAMINATION_PREPARATION_CONFLICT');
                }

                return $prior;
            }
        }

        $requester = [
            'office' => 'conscription',
            'seat' => 'conscription.recruiter',
            'officer_class' => OfficerClass::Legate->value,
            'manifestation_id' => $recruiter['manifestation_id'],
            'occupancy_generation' => $recruiter['occupancy_generation'],
        ];
        $contract = [
            'assembler' => 'conscription.recruiter',
            'officer_class' => OfficerClass::Delegate->value,
            'persona' => $intake['persona'],
            'profile_candidate' => $intake['profile'],
            'substrate' => ['kind' => 'generic-officer', 'version' => 0, 'identity_contribution' => false, 'authority_contribution' => false],
            'purpose' => 'SENATE_EXAMINATION_ONLY',
            'target' => 'senate.stand',
            'return_destination' => 'conscription.recruiter',
            'operational_use_permitted' => false,
            'mission_seat_binding_permitted' => false,
        ];
        $id = 'delegate-mission-examination-preparation-handoff-'.substr(hash('sha256', CanonicalJson::encode([$intakeDispositionId, $intake['record_digest'], $requester, $contract])), 0, 20);

        return $this->save($id, [
            'schema' => 'imperium.conscription-senate-delegate-mission-examination-preparation-handoff/v1',
            'handoff_id' => $id,
            'instance_id' => $instanceId,
            'officer_class' => OfficerClass::Delegate->value,
            'requester' => $requester,
            'recipient' => ['office' => 'senate', 'seat' => 'senate.lord-speaker', 'intake_pending' => true],
            'source_intake_disposition' => ['id' => $intakeDispositionId, 'digest' => $intake['record_digest']],
            'source_profile_candidate' => ['id' => $candidate['candidate_id'], 'digest' => $candidate['record_digest']],
            'source_return' => ['id' => $return['return_id'], 'digest' => $return['record_digest']],
            'source_reservation_disposition' => $intake['source_reservation_disposition'],
            'profile' => $intake['profile'],
            'persona' => $intake['persona'],
            'profile_scope' => $intake['profile_scope'],
            'custody_lease' => $intake['custody_lease'],
            'requested_disposition' => 'DECIDE_EXACT_EXAMINATION_PREPARATION_HANDOFF_INTAKE',
            'examination_only_assembly_contract' => $contract,
            'examination_preparation_authority' => ['id' => $intake['examination_preparation_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'senate_intake_disposition_authority' => [
                'authority_id' => 'delegate-mission-senate-examination-preparation-intake-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $candidate['record_digest'], $contract])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'senate.lord-speaker',
                'purpose' => 'DECIDE_ONE_EXACT_DELEGATE_EXAMINATION_PREPARATION_HANDOFF',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'prepared_at' => $preparedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_EXAMINATION_PREPARATION_HANDED_OFF_PENDING_SENATE_INTAKE',
            'senate_intake_accepted' => false,
            'senate_examination_authority' => false,
            'examination_profile_installation_authority' => false,
            'examination_manifestation_assembly_authority' => false,
            'profile_approval_authority' => false,
            'profile_activation_authority' => false,
            'profile_installation_authority' => false,
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
        ]);
    }

    private function validate(string $id, array $intake, array $candidate, array $return, array $custody, string $instanceId, array $recruiter): void
    {
        $authority = $intake['examination_preparation_authority'] ?? null;
        if (!$this->valid($intake) || !$this->valid($custody)
            || 'imperium.conscription-delegate-mission-profile-candidate-intake-disposition/v1' !== ($intake['schema'] ?? null)
            || $id !== ($intake['disposition_id'] ?? null)
            || $instanceId !== ($intake['instance_id'] ?? null)
            || 'ACCEPTED' !== ($intake['disposition'] ?? null)
            || true !== ($intake['recipient_acceptance'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_PREPARATION' !== ($intake['status'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'conscription.recruiter' !== ($authority['holder'] ?? null)
            || 'PREPARE_ONE_EXACT_DELEGATE_PROFILE_EXAMINATION_HANDOFF' !== ($authority['purpose'] ?? null)
            || ($authority['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($intake['actor']['manifestation_id'] ?? null) !== ($recruiter['manifestation_id'] ?? null)
            || ($intake['actor']['occupancy_generation'] ?? null) !== ($recruiter['occupancy_generation'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED' !== ($candidate['status'] ?? null)
            || CanonicalJson::encode($intake['profile_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || CanonicalJson::encode($intake['persona'] ?? null) !== CanonicalJson::encode($candidate['persona'] ?? null)
            || CanonicalJson::encode($intake['custody_lease'] ?? null) !== CanonicalJson::encode($candidate['custody_lease'] ?? null)
            || ($return['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($intake['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($intake['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || true === ($intake['senate_examination_authority'] ?? null)
            || true === ($intake['manifestation_assembly_authority'] ?? null)
            || true === ($intake['execution_authority'] ?? null)
            || true !== ($intake['sealed'] ?? null)
            || true !== ($candidate['sealed'] ?? null)
            || true !== ($return['sealed'] ?? null)) {
            throw new \RuntimeException('R533_DELEGATE_MISSION_EXAMINATION_PREPARATION_CHAIN_INVALID');
        }
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) {
            throw new \RuntimeException('R534_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
        }
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null) && is_string($recruiter['manifestation_id'] ?? null)) {
                return [(string) ($state['binding']['instance_id'] ?? ''), $recruiter];
            }
        }
        throw new \RuntimeException('R534_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('R533_DELEGATE_MISSION_EXAMINATION_PREPARATION_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'R533_DELEGATE_MISSION_EXAMINATION_PREPARATION_CHAIN_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('R533_DELEGATE_MISSION_EXAMINATION_PREPARATION_CHAIN_INVALID');
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

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->handoffs) && !mkdir($this->handoffs, 0770, true) && !is_dir($this->handoffs)) {
            throw new \RuntimeException('R536_DELEGATE_MISSION_EXAMINATION_PREPARATION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->handoffs.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'R535_DELEGATE_MISSION_EXAMINATION_PREPARATION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('R535_DELEGATE_MISSION_EXAMINATION_PREPARATION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('R536_DELEGATE_MISSION_EXAMINATION_PREPARATION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
