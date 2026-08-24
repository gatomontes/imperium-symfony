<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionFirstQuestionCommissionIssuanceService
{
    private string $openings;
    private string $occupancy;
    private string $commissions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->openings = $senate.'/delegate-mission-profile-examination-openings';
        $this->occupancy = $senate.'/occupancy';
        $this->commissions = $senate.'/delegate-mission-profile-examination-question-commissions';
    }

    public function issue(string $openingId, string $lordSpeakerBindingId, string $trustSenatorBindingId, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-opening-[a-f0-9]{20}$/', $openingId)) {
            throw new \InvalidArgumentException('S540_DELEGATE_MISSION_EXAMINATION_OPENING_ID_INVALID');
        }
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $lordSpeakerBindingId)) {
            throw new \InvalidArgumentException('S541_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID');
        }
        if (!preg_match('/^senate-committee-trust-binding-[a-f0-9]{20}$/', $trustSenatorBindingId)) {
            throw new \InvalidArgumentException('S542_DELEGATE_MISSION_TRUST_SENATOR_BINDING_ID_INVALID');
        }

        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S543_DELEGATE_MISSION_EXAMINATION_OPENING_ABSENT');
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S544_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE');
        $trustSenator = $this->read($this->occupancy.'/'.$trustSenatorBindingId.'.json', 'S545_DELEGATE_MISSION_TRUST_SENATOR_UNAVAILABLE');
        $this->validate($openingId, $lordSpeakerBindingId, $trustSenatorBindingId, $opening, $lordSpeaker, $trustSenator);

        foreach (glob($this->commissions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S549_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_CONFLICT');
            if (($prior['source_examination_opening']['id'] ?? null) === $openingId) {
                if (($prior['source_examination_opening']['digest'] ?? null) === $opening['record_digest']
                    && ($prior['issuer']['binding_id'] ?? null) === $lordSpeakerBindingId
                    && ($prior['recipient']['binding_id'] ?? null) === $trustSenatorBindingId) {
                    return $prior;
                }
                throw new \RuntimeException('S549_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_CONFLICT');
            }
        }

        $issuer = [
            'seat' => 'senate.lord-speaker',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $lordSpeakerBindingId,
            'binding_digest' => $lordSpeaker['record_digest'],
            'manifestation_id' => $lordSpeaker['manifestation_id'],
            'occupancy_generation' => $lordSpeaker['occupancy_generation'],
        ];
        $recipient = [
            'office' => 'senate',
            'seat' => 'senate.committee.trust',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $trustSenatorBindingId,
            'binding_digest' => $trustSenator['record_digest'],
            'manifestation_id' => $trustSenator['manifestation_id'],
            'occupancy_generation' => $trustSenator['occupancy_generation'],
            'acceptance_pending' => true,
        ];
        $commissionId = 'delegate-mission-profile-examination-question-commission-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $issuer, $recipient, 'trust'])), 0, 20);
        $acceptanceAuthority = [
            'authority_id' => 'delegate-mission-question-commission-acceptance-authority-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $trustSenatorBindingId, $opening['hearing_contract']['subject']])), 0, 20),
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'holder' => ['seat' => 'senate.committee.trust', 'binding_id' => $trustSenatorBindingId, 'binding_digest' => $trustSenator['record_digest']],
            'purpose' => 'ACCEPT_OR_REFUSE_ONE_BOUNDED_TRUST_QUESTION_COMMISSION',
            'consumed' => false,
            'continuing_authority' => false,
        ];

        return $this->save($commissionId, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-commission/v1',
            'commission_id' => $commissionId,
            'instance_id' => $opening['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'issuer' => $issuer,
            'recipient' => $recipient,
            'source_examination_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'source_stand_admission' => $opening['source_stand_admission'],
            'source_profile_candidate' => $opening['source_profile_candidate'],
            'source_reservation_disposition' => $opening['source_reservation_disposition'],
            'custody_lease' => $opening['custody_lease'],
            'manifestation' => $opening['manifestation'],
            'hearing_contract' => $opening['hearing_contract'],
            'jurisdiction' => 'trust',
            'question_limit' => 1,
            'question_purpose' => 'EXAMINE_TRUST_OF_EXACT_SEALED_DELEGATE_PROFILE_CANDIDATE',
            'first_question_commission_authority' => ['id' => $opening['first_question_commission_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance_disposition_authority' => $acceptanceAuthority,
            'issued_at' => $issuedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ISSUED_PENDING_TRUST_SENATOR_ACCEPTANCE',
            'recipient_acceptance' => null,
            'question_authorship_authority' => false,
            'question_cognition_authority' => false,
            'question_authored' => false,
            'question_dispatch_authority' => false,
            'question_dispatched' => false,
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

    private function validate(string $openingId, string $lordSpeakerBindingId, string $trustSenatorBindingId, array $opening, array $lordSpeaker, array $trustSenator): void
    {
        $authority = $opening['first_question_commission_authority'] ?? null;
        $contract = $opening['hearing_contract'] ?? null;
        if (!$this->valid($opening) || !$this->valid($lordSpeaker) || !$this->valid($trustSenator)
            || 'imperium.senate-delegate-mission-profile-examination-opening/v1' !== ($opening['schema'] ?? null)
            || $openingId !== ($opening['opening_id'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION' !== ($opening['status'] ?? null)
            || true !== ($opening['examination_opened'] ?? null)
            || true !== ($opening['bounded_hearing_contract_sealed'] ?? null)
            || !is_array($contract)
            || ['trust', 'security', 'usability'] !== ($contract['jurisdictions'] ?? null)
            || 1 !== ($contract['question_limits']['maximum_questions_per_jurisdiction'] ?? null)
            || 3 !== ($contract['question_limits']['maximum_total_questions'] ?? null)
            || 'trust' !== ($contract['question_limits']['first_jurisdiction'] ?? null)
            || true !== ($contract['question_limits']['question_dispatch_requires_separate_authority'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null)
            || 'ISSUE_ONE_BOUNDED_FIRST_QUESTION_COMMISSION' !== ($authority['purpose'] ?? null)
            || 'trust' !== ($authority['jurisdiction'] ?? null)
            || 1 !== ($authority['question_limit'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($opening['lord_speaker']['binding_id'] ?? null) !== $lordSpeakerBindingId
            || ($opening['lord_speaker']['binding_digest'] ?? null) !== ($lordSpeaker['record_digest'] ?? null)
            || 'senate.lord-speaker' !== ($lordSpeaker['seat'] ?? null)
            || OfficerClass::Legate->value !== ($lordSpeaker['officer_class'] ?? null)
            || 'ACTIVE' !== ($lordSpeaker['status'] ?? null)
            || true !== ($lordSpeaker['binding_atomic'] ?? null)
            || true !== ($lordSpeaker['delegate_first_question_commission_issuance_authority'] ?? null)
            || true === ($lordSpeaker['execution_authority'] ?? null)
            || 'imperium.senate-committee-occupancy/v1' !== ($trustSenator['schema'] ?? null)
            || $trustSenatorBindingId !== ($trustSenator['binding_id'] ?? null)
            || 'senate' !== ($trustSenator['office'] ?? null)
            || 'senate.committee.trust' !== ($trustSenator['seat'] ?? null)
            || OfficerClass::Legate->value !== ($trustSenator['officer_class'] ?? null)
            || 'ACTIVE' !== ($trustSenator['status'] ?? null)
            || true !== ($trustSenator['binding_atomic'] ?? null)
            || true !== ($trustSenator['delegate_question_commission_acceptance_disposition_authority'] ?? null)
            || true === ($trustSenator['execution_authority'] ?? null)
            || ($opening['instance_id'] ?? null) !== ($lordSpeaker['instance_id'] ?? null)
            || ($opening['instance_id'] ?? null) !== ($trustSenator['instance_id'] ?? null)
            || false !== ($opening['question_commission_issued'] ?? null)
            || false !== ($opening['question_authored'] ?? null)
            || false !== ($opening['question_dispatched'] ?? null)
            || false !== ($opening['cognition_authority'] ?? null)
            || false !== ($opening['execution_authority'] ?? null)
            || true !== ($opening['sealed'] ?? null)) {
            throw new \RuntimeException('S546_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_CHAIN_INVALID');
        }
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
        if (!is_dir($this->commissions) && !mkdir($this->commissions, 0770, true) && !is_dir($this->commissions)) {
            throw new \RuntimeException('S547_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->commissions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'S549_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('S549_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S547_DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
