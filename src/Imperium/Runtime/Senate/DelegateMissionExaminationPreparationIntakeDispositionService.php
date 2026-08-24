<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionExaminationPreparationIntakeDispositionService
{
    private const array DISPOSITIONS = ['ACCEPTED', 'REFUSED'];

    private string $handoffs;
    private string $candidates;
    private string $custody;
    private string $occupancy;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->handoffs = $root.'/var/imperium/offices/senate/delegate-mission-examination-preparation-inbox';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $root.'/var/imperium/offices/senate/occupancy';
        $this->dispositions = $root.'/var/imperium/offices/senate/delegate-mission-examination-preparation-intake-dispositions';
    }

    public function decide(string $handoffId, string $bindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-examination-preparation-handoff-[a-f0-9]{20}$/', $handoffId)) {
            throw new \InvalidArgumentException('S510_DELEGATE_MISSION_EXAMINATION_PREPARATION_HANDOFF_ID_INVALID');
        }
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('S511_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('S512_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_INVALID');
        }

        $handoff = $this->read($this->handoffs.'/'.$handoffId.'.json', 'S513_DELEGATE_MISSION_EXAMINATION_PREPARATION_HANDOFF_ABSENT');
        $candidate = $this->source($handoff, 'source_profile_candidate', $this->candidates, 'imperium.laboratorium-delegate-mission-profile-candidate/v1', 'candidate_id');
        $custodyId = $handoff['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S514_DELEGATE_MISSION_EXAMINATION_PREPARATION_CUSTODY_ABSENT');
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'S515_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE');
        $this->validate($handoffId, $bindingId, $handoff, $candidate, $custody, $binding);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S519_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_CONFLICT');
            if (($prior['source_handoff']['id'] ?? null) === $handoffId) {
                if (($prior['source_handoff']['digest'] ?? null) === $handoff['record_digest']
                    && ($prior['lord_speaker']['binding_id'] ?? null) === $bindingId
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException('S519_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_CONFLICT');
            }
        }

        $accepted = 'ACCEPTED' === $disposition;
        $id = 'delegate-mission-examination-preparation-intake-disposition-'.substr(hash('sha256', CanonicalJson::encode([$handoffId, $handoff['record_digest'], $bindingId, $binding['record_digest'], $disposition, $rationale])), 0, 20);
        $assemblyAuthority = null;
        if ($accepted) {
            $assemblyAuthority = [
                'authority_id' => 'delegate-mission-examination-only-assembly-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $candidate['record_digest'], $handoff['examination_only_assembly_contract']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'conscription.recruiter',
                'purpose' => 'ASSEMBLE_AND_DELIVER_ONE_EXAMINATION_ONLY_DELEGATE_MANIFESTATION',
                'candidate_digest' => $candidate['record_digest'],
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.senate-delegate-mission-examination-preparation-intake-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $handoff['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'lord_speaker' => [
                'seat' => 'senate.lord-speaker',
                'officer_class' => OfficerClass::Legate->value,
                'binding_id' => $bindingId,
                'binding_digest' => $binding['record_digest'],
                'manifestation_id' => $binding['manifestation_id'],
                'occupancy_generation' => $binding['occupancy_generation'],
            ],
            'recipient' => $handoff['requester'],
            'source_handoff' => ['id' => $handoffId, 'digest' => $handoff['record_digest']],
            'source_profile_candidate' => ['id' => $candidate['candidate_id'], 'digest' => $candidate['record_digest']],
            'source_intake_disposition' => $handoff['source_intake_disposition'],
            'source_return' => $handoff['source_return'],
            'source_reservation_disposition' => $handoff['source_reservation_disposition'],
            'profile' => $handoff['profile'],
            'persona' => $handoff['persona'],
            'profile_scope' => $handoff['profile_scope'],
            'custody_lease' => $handoff['custody_lease'],
            'examination_only_assembly_contract' => $handoff['examination_only_assembly_contract'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'senate_intake_disposition_authority' => ['id' => $handoff['senate_intake_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'senate_intake_accepted' => $accepted,
            'examination_only_assembly_authority' => $assemblyAuthority,
            'status' => $accepted ? 'DELEGATE_MISSION_EXAMINATION_PREPARATION_ACCEPTED_PENDING_CONSCRIPTION_ASSEMBLY' : 'DELEGATE_MISSION_EXAMINATION_PREPARATION_REFUSED_NO_AUTHORITY',
            'examination_manifestation_assembled' => false,
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

    private function validate(string $handoffId, string $bindingId, array $handoff, array $candidate, array $custody, array $binding): void
    {
        $authority = $handoff['senate_intake_disposition_authority'] ?? null;
        $contract = $handoff['examination_only_assembly_contract'] ?? null;
        if (!$this->valid($handoff) || !$this->valid($custody) || !$this->valid($binding)
            || 'imperium.conscription-senate-delegate-mission-examination-preparation-handoff/v1' !== ($handoff['schema'] ?? null)
            || $handoffId !== ($handoff['handoff_id'] ?? null)
            || 'senate.lord-speaker' !== ($handoff['recipient']['seat'] ?? null)
            || true !== ($handoff['recipient']['intake_pending'] ?? null)
            || 'DELEGATE_MISSION_EXAMINATION_PREPARATION_HANDED_OFF_PENDING_SENATE_INTAKE' !== ($handoff['status'] ?? null)
            || true === ($handoff['senate_intake_accepted'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null)
            || 'DECIDE_ONE_EXACT_DELEGATE_EXAMINATION_PREPARATION_HANDOFF' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || !is_array($contract)
            || 'conscription.recruiter' !== ($contract['assembler'] ?? null)
            || OfficerClass::Delegate->value !== ($contract['officer_class'] ?? null)
            || 'generic-officer' !== ($contract['substrate']['kind'] ?? null)
            || 0 !== ($contract['substrate']['version'] ?? null)
            || false !== ($contract['substrate']['identity_contribution'] ?? null)
            || false !== ($contract['substrate']['authority_contribution'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($contract['purpose'] ?? null)
            || 'senate.stand' !== ($contract['target'] ?? null)
            || false !== ($contract['operational_use_permitted'] ?? null)
            || false !== ($contract['mission_seat_binding_permitted'] ?? null)
            || CanonicalJson::encode($handoff['profile_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || CanonicalJson::encode($handoff['persona'] ?? null) !== CanonicalJson::encode($candidate['persona'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($handoff['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($handoff['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || !in_array($binding['schema'] ?? null, ['imperium.senate-lord-speaker-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || 'senate' !== ($binding['office'] ?? null)
            || 'senate.lord-speaker' !== ($binding['seat'] ?? null)
            || OfficerClass::Legate->value !== ($binding['officer_class'] ?? null)
            || 'ACTIVE' !== ($binding['status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null)
            || ($handoff['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || true !== ($binding['delegate_examination_preparation_intake_disposition_authority'] ?? null)
            || true === ($binding['execution_authority'] ?? null)
            || true === ($handoff['senate_examination_authority'] ?? null)
            || true === ($handoff['examination_manifestation_assembly_authority'] ?? null)
            || true === ($handoff['execution_authority'] ?? null)
            || true !== ($handoff['sealed'] ?? null)
            || true !== ($candidate['sealed'] ?? null)) {
            throw new \RuntimeException('S516_DELEGATE_MISSION_EXAMINATION_PREPARATION_INTAKE_INVALID');
        }
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('S516_DELEGATE_MISSION_EXAMINATION_PREPARATION_INTAKE_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'S516_DELEGATE_MISSION_EXAMINATION_PREPARATION_INTAKE_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('S516_DELEGATE_MISSION_EXAMINATION_PREPARATION_INTAKE_INVALID');
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
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) {
            throw new \RuntimeException('S517_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'S519_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('S519_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S517_DELEGATE_MISSION_EXAMINATION_PREPARATION_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
