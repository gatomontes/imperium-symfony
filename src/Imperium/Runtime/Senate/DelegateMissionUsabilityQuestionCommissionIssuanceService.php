<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionUsabilityQuestionCommissionIssuanceService
{
    private string $turns;
    private string $occupancy;
    private string $custody;
    private string $commissions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->turns = $senate.'/delegate-mission-profile-examination-testimony-turns';
        $this->occupancy = $senate.'/occupancy';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->commissions = $senate.'/delegate-mission-profile-examination-question-commissions';
    }

    public function issue(string $turnId, string $lordSpeakerBindingId, string $usabilitySenatorBindingId, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-testimony-turn-[a-f0-9]{20}$/', $turnId)) throw new \InvalidArgumentException('S660_DELEGATE_MISSION_TRUST_TESTIMONY_TURN_ID_INVALID');
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $lordSpeakerBindingId)) throw new \InvalidArgumentException('S661_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID');
        if (!preg_match('/^senate-committee-usability-binding-[a-f0-9]{20}$/', $usabilitySenatorBindingId)) throw new \InvalidArgumentException('S662_DELEGATE_MISSION_USABILITY_SENATOR_BINDING_ID_INVALID');

        $turn = $this->read($this->turns.'/'.$turnId.'.json', 'S663_DELEGATE_MISSION_TRUST_TESTIMONY_TURN_ABSENT');
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S664_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE');
        $usabilitySenator = $this->read($this->occupancy.'/'.$usabilitySenatorBindingId.'.json', 'S665_DELEGATE_MISSION_USABILITY_SENATOR_UNAVAILABLE');
        $custodyId = $turn['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S666_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $this->validate($turnId, $lordSpeakerBindingId, $usabilitySenatorBindingId, $turn, $lordSpeaker, $usabilitySenator, $custody);

        foreach (glob($this->commissions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S669_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_CONFLICT');
            if (($prior['source_prior_testimony_turn']['id'] ?? null) === $turnId) {
                if (($prior['source_prior_testimony_turn']['digest'] ?? null) === $turn['record_digest']
                    && ($prior['issuer']['binding_id'] ?? null) === $lordSpeakerBindingId
                    && ($prior['recipient']['binding_id'] ?? null) === $usabilitySenatorBindingId) return $prior;
                throw new \RuntimeException('S669_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_CONFLICT');
            }
        }

        $issuer = [
            'seat' => 'senate.lord-speaker', 'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $lordSpeakerBindingId, 'binding_digest' => $lordSpeaker['record_digest'],
            'manifestation_id' => $lordSpeaker['manifestation_id'], 'occupancy_generation' => $lordSpeaker['occupancy_generation'],
        ];
        $recipient = [
            'office' => 'senate', 'seat' => 'senate.committee.usability', 'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $usabilitySenatorBindingId, 'binding_digest' => $usabilitySenator['record_digest'],
            'manifestation_id' => $usabilitySenator['manifestation_id'], 'occupancy_generation' => $usabilitySenator['occupancy_generation'],
            'acceptance_pending' => true,
        ];
        $commissionId = 'delegate-mission-profile-examination-question-commission-'.substr(hash('sha256', CanonicalJson::encode([$turnId, $turn['record_digest'], $issuer, $recipient, 'usability', 2])), 0, 20);
        $acceptanceAuthority = [
            'authority_id' => 'delegate-mission-question-commission-acceptance-authority-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $usabilitySenatorBindingId, $turn['hearing_contract']['subject']])), 0, 20),
            'authority_single_use' => true, 'authority_exercisable' => true,
            'holder' => ['seat' => 'senate.committee.usability', 'binding_id' => $usabilitySenatorBindingId, 'binding_digest' => $usabilitySenator['record_digest']],
            'purpose' => 'ACCEPT_OR_REFUSE_ONE_BOUNDED_USABILITY_QUESTION_COMMISSION', 'consumed' => false, 'continuing_authority' => false,
        ];

        return $this->save($commissionId, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-commission/v1',
            'commission_id' => $commissionId, 'instance_id' => $turn['instance_id'], 'officer_class' => OfficerClass::Delegate->value,
            'issuer' => $issuer, 'recipient' => $recipient,
            'source_prior_testimony_turn' => ['id' => $turnId, 'digest' => $turn['record_digest']],
            'source_earlier_testimony_turn' => $turn['source_prior_testimony_turn'],
            'source_prior_question' => $turn['source_question'], 'source_prior_commission' => $turn['source_commission'],
            'source_examination_opening' => $turn['source_examination_opening'], 'source_stand_admission' => $turn['source_stand_admission'],
            'source_profile_candidate' => $turn['source_profile_candidate'], 'source_reservation_disposition' => $turn['source_reservation_disposition'],
            'custody_lease' => $turn['custody_lease'], 'manifestation' => $turn['manifestation'], 'hearing_contract' => $turn['hearing_contract'],
            'jurisdiction' => 'usability', 'question_sequence' => 3, 'question_limit' => 1,
            'question_purpose' => 'EXAMINE_USABILITY_OF_EXACT_SEALED_DELEGATE_PROFILE_CANDIDATE',
            'prior_testimony_turns' => [
                ['jurisdiction' => 'trust', 'turn_id' => $turn['source_prior_testimony_turn']['id'], 'turn_digest' => $turn['source_prior_testimony_turn']['digest'], 'sealed' => true],
                ['jurisdiction' => 'security', 'turn_id' => $turnId, 'turn_digest' => $turn['record_digest'], 'sealed' => true],
            ],
            'next_question_commission_authority' => ['id' => $turn['next_question_commission_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance_disposition_authority' => $acceptanceAuthority, 'issued_at' => $issuedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_ISSUED_PENDING_USABILITY_SENATOR_ACCEPTANCE',
            'recipient_acceptance' => null, 'question_authorship_authority' => false, 'question_cognition_authority' => false,
            'question_authored' => false, 'question_dispatch_authority' => false, 'question_dispatched' => false,
            'examination_cognition_authority' => false, 'testimony_cognition_authority' => false, 'testimony_authority' => false,
            'findings_authority' => false, 'deliberation_authority' => false,
            'profile_approval_authority' => false, 'profile_activation_authority' => false, 'profile_installation_authority' => false,
            'operational_profile_installation_authority' => false, 'manifestation_assembly_authority' => false, 'seat_binding_authority' => false,
            'mission_seat_binding_authority' => false, 'deployment_authority' => false, 'operational_use_authority' => false,
            'cognition_authority' => false, 'provider_invocation_authority' => false, 'data_access_authority' => false,
            'tool_use_authority' => false, 'credential_use_authority' => false, 'perimeter_crossing_authority' => false,
            'external_action_authority' => false, 'execution_authority' => false, 'mission_plan_amendment_authority' => false,
            'follow_up_commission_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validate(string $turnId, string $lordSpeakerBindingId, string $usabilitySenatorBindingId, array $turn, array $lordSpeaker, array $usabilitySenator, array $custody): void
    {
        $authority = $turn['next_question_commission_authority'] ?? null;
        if (!$this->valid($turn) || !$this->valid($lordSpeaker) || !$this->valid($usabilitySenator) || !$this->valid($custody)
            || 'imperium.senate-delegate-mission-profile-examination-testimony-turn/v1' !== ($turn['schema'] ?? null) || $turnId !== ($turn['turn_id'] ?? null)
            || 'DELEGATE_MISSION_SECURITY_TESTIMONY_RESPONSE_SEALED_PENDING_USABILITY_QUESTION_COMMISSION' !== ($turn['status'] ?? null)
            || 'security' !== ($turn['jurisdiction'] ?? null) || 2 !== ($turn['question_sequence'] ?? null) || true !== ($turn['testimony_response_sealed'] ?? null)
            || !is_array($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null) || 'ISSUE_ONE_BOUNDED_USABILITY_QUESTION_COMMISSION' !== ($authority['purpose'] ?? null)
            || 'usability' !== ($authority['jurisdiction'] ?? null) || 1 !== ($authority['question_limit'] ?? null) || false !== ($authority['consumed'] ?? null)
            || 'senate.lord-speaker' !== ($lordSpeaker['seat'] ?? null) || $lordSpeakerBindingId !== ($lordSpeaker['binding_id'] ?? null)
            || OfficerClass::Legate->value !== ($lordSpeaker['officer_class'] ?? null) || 'ACTIVE' !== ($lordSpeaker['status'] ?? null)
            || true !== ($lordSpeaker['binding_atomic'] ?? null) || true !== ($lordSpeaker['delegate_subsequent_question_commission_issuance_authority'] ?? null)
            || true === ($lordSpeaker['execution_authority'] ?? null)
            || 'imperium.senate-committee-occupancy/v1' !== ($usabilitySenator['schema'] ?? null) || $usabilitySenatorBindingId !== ($usabilitySenator['binding_id'] ?? null)
            || 'senate' !== ($usabilitySenator['office'] ?? null) || 'senate.committee.usability' !== ($usabilitySenator['seat'] ?? null)
            || OfficerClass::Legate->value !== ($usabilitySenator['officer_class'] ?? null) || 'ACTIVE' !== ($usabilitySenator['status'] ?? null)
            || true !== ($usabilitySenator['binding_atomic'] ?? null) || true !== ($usabilitySenator['delegate_question_commission_acceptance_disposition_authority'] ?? null)
            || true === ($usabilitySenator['execution_authority'] ?? null)
            || ($turn['instance_id'] ?? null) !== ($lordSpeaker['instance_id'] ?? null) || ($turn['instance_id'] ?? null) !== ($usabilitySenator['instance_id'] ?? null)
            || ($turn['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null) || ($turn['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || ['trust', 'security', 'usability'] !== ($turn['hearing_contract']['jurisdictions'] ?? null)
            || false !== ($turn['findings_authority'] ?? null) || false !== ($turn['deliberation_authority'] ?? null)
            || false !== ($turn['execution_authority'] ?? null) || true !== ($turn['sealed'] ?? null)) throw new \RuntimeException('S667_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_CHAIN_INVALID');
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array
    {
        if (!is_dir($this->commissions) && !mkdir($this->commissions, 0770, true) && !is_dir($this->commissions)) throw new \RuntimeException('S668_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_PERSISTENCE_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->commissions.'/'.$id.'.json';
        if (is_file($path)) { $prior = $this->read($path, 'S669_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_CONFLICT'); if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) throw new \RuntimeException('S669_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_CONFLICT'); return $prior; }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S668_DELEGATE_MISSION_USABILITY_QUESTION_COMMISSION_PERSISTENCE_FAILED');
        return $record;
    }
}
