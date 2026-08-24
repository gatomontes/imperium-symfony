<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileCandidateIntakeDispositionService
{
    private const array DISPOSITIONS = ['ACCEPTED', 'REFUSED'];

    private string $returns;
    private string $candidates;
    private string $derivationDispositions;
    private string $custody;
    private string $intakes;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private StateStore $bootstrap)
    {
        $this->returns = $root.'/var/imperium/offices/conscription/delegate-mission-profile-candidate-return-inbox';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->derivationDispositions = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-dispositions';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->intakes = $root.'/var/imperium/offices/conscription/delegate-mission-profile-candidate-intake-dispositions';
    }

    public function decide(string $returnId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-candidate-return-[a-f0-9]{20}$/', $returnId)) {
            throw new \InvalidArgumentException('R520_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('R521_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_DISPOSITION_INVALID');
        }

        $return = $this->read($this->returns.'/'.$returnId.'.json', 'R522_DELEGATE_MISSION_PROFILE_CANDIDATE_RETURN_ABSENT');
        $candidate = $this->source($return, 'source_profile_candidate', $this->candidates, 'imperium.laboratorium-delegate-mission-profile-candidate/v1', 'candidate_id');
        $derivation = $this->source($return, 'source_commission_disposition', $this->derivationDispositions, 'imperium.laboratorium-delegate-mission-profile-derivation-commission-disposition/v1', 'disposition_id');
        $custodyId = $return['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'R523_DELEGATE_MISSION_PROFILE_CANDIDATE_CUSTODY_ABSENT');
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $this->validate($returnId, $return, $candidate, $derivation, $custody, $instanceId);

        foreach (glob($this->intakes.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'R527_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CONFLICT');
            if (($prior['source_return']['id'] ?? null) === $returnId) {
                if (($prior['source_return']['digest'] ?? null) === $return['record_digest']
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException('R527_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CONFLICT');
            }
        }

        $actor = [
            'seat' => 'conscription.recruiter',
            'officer_class' => OfficerClass::Legate->value,
            'manifestation_id' => $recruiter['manifestation_id'],
            'occupancy_generation' => $recruiter['occupancy_generation'],
        ];
        $accepted = 'ACCEPTED' === $disposition;
        $id = 'delegate-mission-profile-candidate-intake-disposition-'.substr(hash('sha256', CanonicalJson::encode([$returnId, $return['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $preparationAuthority = null;
        if ($accepted) {
            $preparationAuthority = [
                'authority_id' => 'delegate-mission-profile-examination-preparation-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $candidate['record_digest'], $return['profile_scope']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'conscription.recruiter',
                'purpose' => 'PREPARE_ONE_EXACT_DELEGATE_PROFILE_EXAMINATION_HANDOFF',
                'candidate_digest' => $candidate['record_digest'],
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.conscription-delegate-mission-profile-candidate-intake-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $instanceId,
            'officer_class' => OfficerClass::Delegate->value,
            'actor' => $actor,
            'source_return' => ['id' => $returnId, 'digest' => $return['record_digest']],
            'source_profile_candidate' => ['id' => $candidate['candidate_id'], 'digest' => $candidate['record_digest']],
            'source_commission_disposition' => ['id' => $derivation['disposition_id'], 'digest' => $derivation['record_digest']],
            'source_reservation_disposition' => $return['source_reservation_disposition'],
            'profile' => $return['profile'],
            'persona' => $return['persona'],
            'profile_scope' => $return['profile_scope'],
            'custody_lease' => $return['custody_lease'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'profile_candidate_intake_disposition_authority' => ['id' => $return['profile_candidate_intake_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance' => $accepted,
            'examination_preparation_authority' => $preparationAuthority,
            'status' => $accepted ? 'DELEGATE_MISSION_PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_PREPARATION' : 'DELEGATE_MISSION_PROFILE_CANDIDATE_REFUSED_NO_AUTHORITY',
            'senate_intake_authority' => false,
            'senate_examination_authority' => false,
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

    private function validate(string $id, array $return, array $candidate, array $derivation, array $custody, string $instanceId): void
    {
        $authority = $return['profile_candidate_intake_disposition_authority'] ?? null;
        if (!$this->valid($return) || !$this->valid($custody)
            || 'imperium.laboratorium-conscription-delegate-mission-profile-candidate-return/v1' !== ($return['schema'] ?? null)
            || $id !== ($return['return_id'] ?? null)
            || $instanceId !== ($return['instance_id'] ?? null)
            || OfficerClass::Delegate->value !== ($return['officer_class'] ?? null)
            || 'conscription.recruiter' !== ($return['recipient']['seat'] ?? null)
            || true !== ($return['recipient']['intake_pending'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_INTAKE' !== ($return['status'] ?? null)
            || true !== ($return['profile_candidate_returned'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'conscription.recruiter' !== ($authority['holder'] ?? null)
            || 'DECIDE_INTAKE_OF_ONE_EXACT_DELEGATE_MISSION_PROFILE_CANDIDATE' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED' !== ($candidate['status'] ?? null)
            || true !== ($candidate['profile_derived'] ?? null)
            || true !== ($candidate['profile_candidate_created'] ?? null)
            || CanonicalJson::encode($return['profile_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || CanonicalJson::encode($return['persona'] ?? null) !== CanonicalJson::encode($candidate['persona'] ?? null)
            || CanonicalJson::encode($return['custody_lease'] ?? null) !== CanonicalJson::encode($candidate['custody_lease'] ?? null)
            || 'ACCEPTED' !== ($derivation['disposition'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope'] ?? null) !== CanonicalJson::encode($derivation['profile_scope'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($return['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($return['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || $instanceId !== ($custody['instance_id'] ?? null)
            || true === ($candidate['profile_approval_authority'] ?? null)
            || true === ($candidate['profile_activation_authority'] ?? null)
            || true === ($candidate['profile_installation_authority'] ?? null)
            || true === ($candidate['deployment_authority'] ?? null)
            || true === ($candidate['execution_authority'] ?? null)
            || true !== ($return['sealed'] ?? null)
            || true !== ($candidate['sealed'] ?? null)
            || true !== ($derivation['sealed'] ?? null)) {
            throw new \RuntimeException('R524_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CHAIN_INVALID');
        }
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) {
            throw new \RuntimeException('R525_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
        }
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter)
                && 'conscription.recruiter' === ($recruiter['seat'] ?? null)
                && 'ordinary-recruiter' === ($recruiter['authority'] ?? null)
                && 2 === ($recruiter['occupancy_generation'] ?? null)
                && is_string($recruiter['manifestation_id'] ?? null)) {
                return [(string) ($state['binding']['instance_id'] ?? ''), $recruiter];
            }
        }
        throw new \RuntimeException('R525_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('R524_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'R524_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CHAIN_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('R524_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CHAIN_INVALID');
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
        if (!is_dir($this->intakes) && !mkdir($this->intakes, 0770, true) && !is_dir($this->intakes)) {
            throw new \RuntimeException('R526_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->intakes.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'R527_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('R527_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('R526_DELEGATE_MISSION_PROFILE_CANDIDATE_INTAKE_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
