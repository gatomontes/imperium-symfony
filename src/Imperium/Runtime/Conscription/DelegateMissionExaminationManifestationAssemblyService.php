<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionExaminationManifestationAssemblyService
{
    private string $authorizations;
    private string $candidates;
    private string $custody;
    private string $deliveries;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private StateStore $bootstrap)
    {
        $this->authorizations = $root.'/var/imperium/offices/senate/delegate-mission-examination-preparation-intake-dispositions';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->deliveries = $root.'/var/imperium/offices/senate/delegate-mission-examination-manifestation-intake';
    }

    public function assemble(string $authorizationDispositionId, \DateTimeImmutable $assembledAt): array
    {
        if (!preg_match('/^delegate-mission-examination-preparation-intake-disposition-[a-f0-9]{20}$/', $authorizationDispositionId)) {
            throw new \InvalidArgumentException('R540_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_AUTHORIZATION_ID_INVALID');
        }

        $authorization = $this->read($this->authorizations.'/'.$authorizationDispositionId.'.json', 'R541_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_AUTHORIZATION_ABSENT');
        $candidate = $this->source($authorization, 'source_profile_candidate', $this->candidates, 'imperium.laboratorium-delegate-mission-profile-candidate/v1', 'candidate_id');
        $custodyId = $authorization['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'R542_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_CUSTODY_ABSENT');
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $this->validate($authorizationDispositionId, $authorization, $candidate, $custody, $instanceId, $recruiter);

        foreach (glob($this->deliveries.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'R545_DELEGATE_MISSION_EXAMINATION_MANIFESTATION_CONFLICT');
            if (($prior['source_authorization']['id'] ?? null) === $authorizationDispositionId) {
                if (($prior['source_authorization']['digest'] ?? null) !== $authorization['record_digest']) {
                    throw new \RuntimeException('R545_DELEGATE_MISSION_EXAMINATION_MANIFESTATION_CONFLICT');
                }

                return $prior;
            }
        }

        $assembler = [
            'seat' => 'conscription.recruiter',
            'officer_class' => OfficerClass::Legate->value,
            'manifestation_id' => $recruiter['manifestation_id'],
            'occupancy_generation' => $recruiter['occupancy_generation'],
        ];
        $contract = $authorization['examination_only_assembly_contract'];
        $manifestationId = 'delegate-mission-examination-manifestation-'.substr(hash('sha256', CanonicalJson::encode([$authorization['record_digest'], $candidate['record_digest'], $contract])), 0, 20);
        $manifestation = [
            'manifestation_id' => $manifestationId,
            'instance_id' => $instanceId,
            'officer_class' => OfficerClass::Delegate->value,
            'persona' => $authorization['persona'],
            'profile' => [
                'profile_id' => $candidate['profile_id'],
                'profile_version' => $candidate['profile_version'],
                'candidate_id' => $candidate['candidate_id'],
                'candidate_digest' => $candidate['record_digest'],
                'installation_class' => 'EXAMINATION_ONLY',
                'candidate_content' => $candidate['profile'],
                'candidate_scope' => $candidate['profile_scope'],
            ],
            'substrate' => $contract['substrate'],
            'purpose' => 'SENATE_EXAMINATION_ONLY',
            'target' => 'senate.stand',
            'return_destination' => 'conscription.recruiter',
            'mission_seat_bound' => false,
            'operational_use_permitted' => false,
            'cognition_permitted' => false,
            'credentials_granted' => false,
            'tool_access_granted' => false,
            'data_access_granted' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
        ];
        $deliveryId = 'delegate-mission-examination-manifestation-delivery-'.substr(hash('sha256', CanonicalJson::encode([$authorizationDispositionId, $authorization['record_digest'], $manifestation])), 0, 20);

        return $this->save($deliveryId, [
            'schema' => 'imperium.conscription-senate-delegate-mission-examination-manifestation-delivery/v1',
            'delivery_id' => $deliveryId,
            'instance_id' => $instanceId,
            'officer_class' => OfficerClass::Delegate->value,
            'assembler' => $assembler,
            'recipient' => ['office' => 'senate', 'seat' => 'senate.bailiff', 'surface' => 'senate.stand.intake', 'acceptance_pending' => true],
            'source_authorization' => ['id' => $authorizationDispositionId, 'digest' => $authorization['record_digest']],
            'source_handoff' => $authorization['source_handoff'],
            'source_profile_candidate' => ['id' => $candidate['candidate_id'], 'digest' => $candidate['record_digest']],
            'source_intake_disposition' => $authorization['source_intake_disposition'],
            'source_return' => $authorization['source_return'],
            'source_reservation_disposition' => $authorization['source_reservation_disposition'],
            'profile_scope' => $authorization['profile_scope'],
            'custody_lease' => $authorization['custody_lease'],
            'assembly_contract' => $contract,
            'manifestation' => $manifestation,
            'examination_only_assembly_authority' => ['id' => $authorization['examination_only_assembly_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'examination_profile_installed' => true,
            'examination_manifestation_assembled' => true,
            'examination_manifestation_delivered' => true,
            'senate_stand_intake_disposition_authority' => [
                'authority_id' => 'delegate-mission-senate-stand-intake-authority-'.substr(hash('sha256', CanonicalJson::encode([$deliveryId, $manifestationId, $candidate['record_digest']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'senate.bailiff',
                'purpose' => 'DECIDE_INTAKE_OF_ONE_EXAMINATION_ONLY_DELEGATE_MANIFESTATION',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'assembled_at' => $assembledAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE',
            'senate_stand_accepted' => false,
            'senate_examination_authority' => false,
            'profile_approval_authority' => false,
            'profile_activation_authority' => false,
            'profile_installation_authority' => false,
            'operational_profile_installation_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'mission_seat_binding_authority' => false,
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

    private function validate(string $id, array $authorization, array $candidate, array $custody, string $instanceId, array $recruiter): void
    {
        $authority = $authorization['examination_only_assembly_authority'] ?? null;
        $contract = $authorization['examination_only_assembly_contract'] ?? null;
        if (!$this->valid($authorization) || !$this->valid($custody)
            || 'imperium.senate-delegate-mission-examination-preparation-intake-disposition/v1' !== ($authorization['schema'] ?? null)
            || $id !== ($authorization['disposition_id'] ?? null)
            || $instanceId !== ($authorization['instance_id'] ?? null)
            || 'ACCEPTED' !== ($authorization['disposition'] ?? null)
            || true !== ($authorization['senate_intake_accepted'] ?? null)
            || 'DELEGATE_MISSION_EXAMINATION_PREPARATION_ACCEPTED_PENDING_CONSCRIPTION_ASSEMBLY' !== ($authorization['status'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'conscription.recruiter' !== ($authority['holder'] ?? null)
            || 'ASSEMBLE_AND_DELIVER_ONE_EXAMINATION_ONLY_DELEGATE_MANIFESTATION' !== ($authority['purpose'] ?? null)
            || ($authority['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($authorization['recipient']['manifestation_id'] ?? null) !== ($recruiter['manifestation_id'] ?? null)
            || ($authorization['recipient']['occupancy_generation'] ?? null) !== ($recruiter['occupancy_generation'] ?? null)
            || !is_array($contract)
            || 'conscription.recruiter' !== ($contract['assembler'] ?? null)
            || 'generic-officer' !== ($contract['substrate']['kind'] ?? null)
            || 0 !== ($contract['substrate']['version'] ?? null)
            || false !== ($contract['substrate']['identity_contribution'] ?? null)
            || false !== ($contract['substrate']['authority_contribution'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($contract['purpose'] ?? null)
            || 'senate.stand' !== ($contract['target'] ?? null)
            || false !== ($contract['operational_use_permitted'] ?? null)
            || false !== ($contract['mission_seat_binding_permitted'] ?? null)
            || CanonicalJson::encode($authorization['profile_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || CanonicalJson::encode($authorization['persona'] ?? null) !== CanonicalJson::encode($candidate['persona'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($authorization['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($authorization['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || true === ($authorization['senate_examination_authority'] ?? null)
            || true === ($authorization['execution_authority'] ?? null)
            || true !== ($authorization['sealed'] ?? null)
            || true !== ($candidate['sealed'] ?? null)) {
            throw new \RuntimeException('R543_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_CHAIN_INVALID');
        }
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) {
            throw new \RuntimeException('R544_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
        }
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null) && is_string($recruiter['manifestation_id'] ?? null)) {
                return [(string) ($state['binding']['instance_id'] ?? ''), $recruiter];
            }
        }
        throw new \RuntimeException('R544_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('R543_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'R543_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_CHAIN_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('R543_DELEGATE_MISSION_EXAMINATION_ASSEMBLY_CHAIN_INVALID');
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
        if (!is_dir($this->deliveries) && !mkdir($this->deliveries, 0770, true) && !is_dir($this->deliveries)) {
            throw new \RuntimeException('R546_DELEGATE_MISSION_EXAMINATION_MANIFESTATION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->deliveries.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'R545_DELEGATE_MISSION_EXAMINATION_MANIFESTATION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('R545_DELEGATE_MISSION_EXAMINATION_MANIFESTATION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('R546_DELEGATE_MISSION_EXAMINATION_MANIFESTATION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
