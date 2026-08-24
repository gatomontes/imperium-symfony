<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileExaminationOpeningService
{
    private string $admissions;
    private string $deliveries;
    private string $candidates;
    private string $custody;
    private string $occupancy;
    private string $openings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->admissions = $root.'/var/imperium/offices/senate/delegate-mission-examination-stand-admission-dispositions';
        $this->deliveries = $root.'/var/imperium/offices/senate/delegate-mission-examination-manifestation-intake';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $root.'/var/imperium/offices/senate/occupancy';
        $this->openings = $root.'/var/imperium/offices/senate/delegate-mission-profile-examination-openings';
    }

    public function open(string $admissionDispositionId, string $lordSpeakerBindingId, \DateTimeImmutable $openedAt): array
    {
        if (!preg_match('/^delegate-mission-examination-stand-admission-disposition-[a-f0-9]{20}$/', $admissionDispositionId)) {
            throw new \InvalidArgumentException('S530_DELEGATE_MISSION_STAND_ADMISSION_ID_INVALID');
        }
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $lordSpeakerBindingId)) {
            throw new \InvalidArgumentException('S531_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID');
        }

        $admission = $this->read($this->admissions.'/'.$admissionDispositionId.'.json', 'S532_DELEGATE_MISSION_STAND_ADMISSION_ABSENT');
        $delivery = $this->source($admission, 'source_delivery', $this->deliveries, 'imperium.conscription-senate-delegate-mission-examination-manifestation-delivery/v1', 'delivery_id');
        $candidate = $this->source($admission, 'source_profile_candidate', $this->candidates, 'imperium.laboratorium-delegate-mission-profile-candidate/v1', 'candidate_id');
        $custodyId = $admission['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S533_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S534_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE');
        $this->validate($admissionDispositionId, $lordSpeakerBindingId, $admission, $delivery, $candidate, $custody, $lordSpeaker);

        foreach (glob($this->openings.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S539_DELEGATE_MISSION_EXAMINATION_OPENING_CONFLICT');
            if (($prior['source_stand_admission']['id'] ?? null) === $admissionDispositionId) {
                if (($prior['source_stand_admission']['digest'] ?? null) === $admission['record_digest']
                    && ($prior['lord_speaker']['binding_id'] ?? null) === $lordSpeakerBindingId) {
                    return $prior;
                }
                throw new \RuntimeException('S539_DELEGATE_MISSION_EXAMINATION_OPENING_CONFLICT');
            }
        }

        $actor = [
            'seat' => 'senate.lord-speaker',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $lordSpeakerBindingId,
            'binding_digest' => $lordSpeaker['record_digest'],
            'manifestation_id' => $lordSpeaker['manifestation_id'],
            'occupancy_generation' => $lordSpeaker['occupancy_generation'],
        ];
        $hearingContract = [
            'subject' => [
                'candidate_id' => $candidate['candidate_id'],
                'candidate_digest' => $candidate['record_digest'],
                'profile_id' => $candidate['profile_id'],
                'profile_version' => $candidate['profile_version'],
                'persona' => $candidate['persona'],
                'manifestation_id' => $admission['manifestation']['manifestation_id'],
            ],
            'jurisdictions' => ['trust', 'security', 'usability'],
            'defect_attribution_rubric' => [
                'persona',
                'profile_elaboration',
                'profile_derivation_and_sealing',
                'conscription_assembly',
                'generic_officer_substrate',
                'persona_profile_compatibility',
                'insufficient_evidence',
            ],
            'evidence_rules' => [
                'exact_sealed_candidate_and_authority_lineage_only' => true,
                'questions_and_responses_must_be_attributable_and_sealed' => true,
                'peer_findings_visible_during_question_authorship' => false,
                'insufficient_evidence_must_be_reported' => true,
                'operational_trial_permitted' => false,
            ],
            'question_limits' => [
                'maximum_questions_per_jurisdiction' => 1,
                'maximum_total_questions' => 3,
                'first_jurisdiction' => 'trust',
                'question_dispatch_requires_separate_authority' => true,
            ],
            'stop_conditions' => array_values(array_unique(array_merge(
                $candidate['profile_scope']['stop_conditions'],
                ['Proceeding security ceases', 'Custody or sealed lineage changes', 'Any examination authority expires or is withdrawn'],
            ))),
            'return_destination' => $admission['manifestation']['return_destination'],
            'return_required_on_stop_or_completion' => true,
        ];
        $openingId = 'delegate-mission-profile-examination-opening-'.substr(hash('sha256', CanonicalJson::encode([$admissionDispositionId, $admission['record_digest'], $actor, $hearingContract])), 0, 20);
        $firstQuestionAuthority = [
            'authority_id' => 'delegate-mission-first-question-commission-authority-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $hearingContract['subject'], 'trust'])), 0, 20),
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'holder' => 'senate.lord-speaker',
            'purpose' => 'ISSUE_ONE_BOUNDED_FIRST_QUESTION_COMMISSION',
            'jurisdiction' => 'trust',
            'question_limit' => 1,
            'consumed' => false,
            'continuing_authority' => false,
        ];

        return $this->save($openingId, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-opening/v1',
            'opening_id' => $openingId,
            'instance_id' => $admission['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'lord_speaker' => $actor,
            'source_stand_admission' => ['id' => $admissionDispositionId, 'digest' => $admission['record_digest']],
            'source_delivery' => $admission['source_delivery'],
            'source_profile_candidate' => ['id' => $candidate['candidate_id'], 'digest' => $candidate['record_digest']],
            'source_reservation_disposition' => $admission['source_reservation_disposition'],
            'custody_lease' => $admission['custody_lease'],
            'manifestation' => $admission['manifestation'],
            'hearing_contract' => $hearingContract,
            'senate_examination_opening_authority' => ['id' => $admission['senate_examination_opening_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'first_question_commission_authority' => $firstQuestionAuthority,
            'opened_at' => $openedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION',
            'examination_opened' => true,
            'bounded_hearing_contract_sealed' => true,
            'question_commission_issued' => false,
            'question_authored' => false,
            'question_dispatched' => false,
            'senate_examination_authority' => false,
            'examination_cognition_authority' => false,
            'testimony_authority' => false,
            'findings_authority' => false,
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

    private function validate(string $admissionId, string $bindingId, array $admission, array $delivery, array $candidate, array $custody, array $lordSpeaker): void
    {
        $authority = $admission['senate_examination_opening_authority'] ?? null;
        if (!$this->valid($admission) || !$this->valid($delivery) || !$this->valid($custody) || !$this->valid($lordSpeaker)
            || 'imperium.senate-delegate-mission-examination-stand-admission-disposition/v1' !== ($admission['schema'] ?? null)
            || $admissionId !== ($admission['disposition_id'] ?? null)
            || 'ADMITTED' !== ($admission['disposition'] ?? null)
            || 'DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_EXAMINATION_OPENING' !== ($admission['status'] ?? null)
            || true !== ($admission['stand_admission'] ?? null)
            || true !== ($admission['proceeding_security_active'] ?? null)
            || true === ($admission['examination_opened'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null)
            || 'OPEN_ONE_BOUNDED_DELEGATE_PROFILE_EXAMINATION' !== ($authority['purpose'] ?? null)
            || ($admission['manifestation']['manifestation_id'] ?? null) !== ($authority['manifestation_id'] ?? null)
            || ($candidate['record_digest'] ?? null) !== ($authority['candidate_digest'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || CanonicalJson::encode($admission['manifestation'] ?? null) !== CanonicalJson::encode($delivery['manifestation'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope'] ?? null) !== CanonicalJson::encode($delivery['profile_scope'] ?? null)
            || true !== ($candidate['sealed'] ?? null)
            || 'EXAMINATION_ONLY' !== ($admission['manifestation']['profile']['installation_class'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($admission['manifestation']['purpose'] ?? null)
            || false !== ($admission['manifestation']['mission_seat_bound'] ?? null)
            || false !== ($admission['manifestation']['operational_use_permitted'] ?? null)
            || false !== ($admission['manifestation']['cognition_permitted'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($admission['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($admission['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || 'imperium.senate-lord-speaker-occupancy/v1' !== ($lordSpeaker['schema'] ?? null)
            || $bindingId !== ($lordSpeaker['binding_id'] ?? null)
            || 'senate' !== ($lordSpeaker['office'] ?? null)
            || 'senate.lord-speaker' !== ($lordSpeaker['seat'] ?? null)
            || OfficerClass::Legate->value !== ($lordSpeaker['officer_class'] ?? null)
            || 'ACTIVE' !== ($lordSpeaker['status'] ?? null)
            || true !== ($lordSpeaker['binding_atomic'] ?? null)
            || true !== ($lordSpeaker['delegate_profile_examination_opening_authority'] ?? null)
            || true === ($lordSpeaker['execution_authority'] ?? null)
            || ($admission['instance_id'] ?? null) !== ($lordSpeaker['instance_id'] ?? null)) {
            throw new \RuntimeException('S535_DELEGATE_MISSION_EXAMINATION_OPENING_CHAIN_INVALID');
        }
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('S535_DELEGATE_MISSION_EXAMINATION_OPENING_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'S535_DELEGATE_MISSION_EXAMINATION_OPENING_CHAIN_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('S535_DELEGATE_MISSION_EXAMINATION_OPENING_CHAIN_INVALID');
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
        if (!is_dir($this->openings) && !mkdir($this->openings, 0770, true) && !is_dir($this->openings)) {
            throw new \RuntimeException('S537_DELEGATE_MISSION_EXAMINATION_OPENING_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->openings.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'S539_DELEGATE_MISSION_EXAMINATION_OPENING_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('S539_DELEGATE_MISSION_EXAMINATION_OPENING_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S537_DELEGATE_MISSION_EXAMINATION_OPENING_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
