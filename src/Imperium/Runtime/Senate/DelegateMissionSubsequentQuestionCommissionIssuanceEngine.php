<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSubsequentQuestionCommissionIssuanceEngine
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

    public function issue(string $jurisdiction, string $turnId, string $lordSpeakerBindingId, string $senatorBindingId, \DateTimeImmutable $issuedAt): array
    {
        $config = $this->config($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-testimony-turn-[a-f0-9]{20}$/', $turnId)) throw new \InvalidArgumentException($config['errors']['turn_id']);
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $lordSpeakerBindingId)) throw new \InvalidArgumentException($config['errors']['speaker_id']);
        if (!preg_match('/^senate-committee-'.$jurisdiction.'-binding-[a-f0-9]{20}$/', $senatorBindingId)) throw new \InvalidArgumentException($config['errors']['senator_id']);

        $turn = $this->read($this->turns.'/'.$turnId.'.json', $config['errors']['turn_absent']);
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', $config['errors']['speaker_absent']);
        $senator = $this->read($this->occupancy.'/'.$senatorBindingId.'.json', $config['errors']['senator_absent']);
        $custody = $this->read($this->custody.'/'.($turn['custody_lease']['custody_id'] ?? '').'.json', $config['errors']['custody_absent']);
        $this->validate($config, $turnId, $lordSpeakerBindingId, $senatorBindingId, $turn, $lordSpeaker, $senator, $custody);

        foreach (glob($this->commissions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, $config['errors']['conflict']);
            if (($prior['source_prior_testimony_turn']['id'] ?? null) === $turnId) {
                if (($prior['source_prior_testimony_turn']['digest'] ?? null) === $turn['record_digest']
                    && ($prior['issuer']['binding_id'] ?? null) === $lordSpeakerBindingId
                    && ($prior['recipient']['binding_id'] ?? null) === $senatorBindingId) return $prior;
                throw new \RuntimeException($config['errors']['conflict']);
            }
        }

        $issuer = ['seat' => 'senate.lord-speaker', 'officer_class' => OfficerClass::Legate->value, 'binding_id' => $lordSpeakerBindingId, 'binding_digest' => $lordSpeaker['record_digest'], 'manifestation_id' => $lordSpeaker['manifestation_id'], 'occupancy_generation' => $lordSpeaker['occupancy_generation']];
        $recipient = ['office' => 'senate', 'seat' => 'senate.committee.'.$jurisdiction, 'officer_class' => OfficerClass::Legate->value, 'binding_id' => $senatorBindingId, 'binding_digest' => $senator['record_digest'], 'manifestation_id' => $senator['manifestation_id'], 'occupancy_generation' => $senator['occupancy_generation'], 'acceptance_pending' => true];
        $commissionId = 'delegate-mission-profile-examination-question-commission-'.substr(hash('sha256', CanonicalJson::encode([$turnId, $turn['record_digest'], $issuer, $recipient, $jurisdiction, 2])), 0, 20);
        $record = [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-commission/v1',
            'commission_id' => $commissionId, 'instance_id' => $turn['instance_id'], 'officer_class' => OfficerClass::Delegate->value,
            'issuer' => $issuer, 'recipient' => $recipient,
            'source_prior_testimony_turn' => ['id' => $turnId, 'digest' => $turn['record_digest']],
        ];
        if ('usability' === $jurisdiction) $record['source_earlier_testimony_turn'] = $turn['source_prior_testimony_turn'];
        $record += [
            'source_prior_question' => $turn['source_question'], 'source_prior_commission' => $turn['source_commission'],
            'source_examination_opening' => $turn['source_examination_opening'], 'source_stand_admission' => $turn['source_stand_admission'],
            'source_profile_candidate' => $turn['source_profile_candidate'], 'source_reservation_disposition' => $turn['source_reservation_disposition'],
            'custody_lease' => $turn['custody_lease'], 'manifestation' => $turn['manifestation'], 'hearing_contract' => $turn['hearing_contract'],
            'jurisdiction' => $jurisdiction, 'question_sequence' => $config['sequence'], 'question_limit' => 1,
            'question_purpose' => 'EXAMINE_'.strtoupper($jurisdiction).'_OF_EXACT_SEALED_DELEGATE_PROFILE_CANDIDATE',
        ];
        $record[$config['prior_key']] = $this->priorTestimony($jurisdiction, $turnId, $turn);
        $record += [
            'next_question_commission_authority' => ['id' => $turn['next_question_commission_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance_disposition_authority' => [
                'authority_id' => 'delegate-mission-question-commission-acceptance-authority-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $senatorBindingId, $turn['hearing_contract']['subject']])), 0, 20),
                'authority_single_use' => true, 'authority_exercisable' => true,
                'holder' => ['seat' => 'senate.committee.'.$jurisdiction, 'binding_id' => $senatorBindingId, 'binding_digest' => $senator['record_digest']],
                'purpose' => 'ACCEPT_OR_REFUSE_ONE_BOUNDED_'.strtoupper($jurisdiction).'_QUESTION_COMMISSION', 'consumed' => false, 'continuing_authority' => false,
            ],
            'issued_at' => $issuedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_'.strtoupper($jurisdiction).'_QUESTION_COMMISSION_ISSUED_PENDING_'.strtoupper($jurisdiction).'_SENATOR_ACCEPTANCE',
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
        ];
        return $this->save($commissionId, $record, $config['errors']);
    }

    private function validate(array $config, string $turnId, string $speakerId, string $senatorId, array $turn, array $speaker, array $senator, array $custody): void
    {
        $authority = $turn['next_question_commission_authority'] ?? null;
        if (!$this->valid($turn) || !$this->valid($speaker) || !$this->valid($senator) || !$this->valid($custody)
            || 'imperium.senate-delegate-mission-profile-examination-testimony-turn/v1' !== ($turn['schema'] ?? null) || $turnId !== ($turn['turn_id'] ?? null)
            || $config['source_status'] !== ($turn['status'] ?? null) || $config['prior_jurisdiction'] !== ($turn['jurisdiction'] ?? null)
            || $config['prior_sequence'] !== ($turn['question_sequence'] ?? null) || true !== ($turn['testimony_response_sealed'] ?? null)
            || !is_array($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null) || $config['authority_purpose'] !== ($authority['purpose'] ?? null)
            || $config['jurisdiction'] !== ($authority['jurisdiction'] ?? null) || 1 !== ($authority['question_limit'] ?? null) || false !== ($authority['consumed'] ?? null)
            || 'senate.lord-speaker' !== ($speaker['seat'] ?? null) || $speakerId !== ($speaker['binding_id'] ?? null)
            || OfficerClass::Legate->value !== ($speaker['officer_class'] ?? null) || 'ACTIVE' !== ($speaker['status'] ?? null)
            || true !== ($speaker['binding_atomic'] ?? null) || true !== ($speaker['delegate_subsequent_question_commission_issuance_authority'] ?? null)
            || true === ($speaker['execution_authority'] ?? null)
            || 'imperium.senate-committee-occupancy/v1' !== ($senator['schema'] ?? null) || $senatorId !== ($senator['binding_id'] ?? null)
            || 'senate' !== ($senator['office'] ?? null) || 'senate.committee.'.$config['jurisdiction'] !== ($senator['seat'] ?? null)
            || OfficerClass::Legate->value !== ($senator['officer_class'] ?? null) || 'ACTIVE' !== ($senator['status'] ?? null)
            || true !== ($senator['binding_atomic'] ?? null) || true !== ($senator['delegate_question_commission_acceptance_disposition_authority'] ?? null)
            || true === ($senator['execution_authority'] ?? null)
            || ($turn['instance_id'] ?? null) !== ($speaker['instance_id'] ?? null) || ($turn['instance_id'] ?? null) !== ($senator['instance_id'] ?? null)
            || ($turn['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null) || ($turn['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || ['trust', 'security', 'usability'] !== ($turn['hearing_contract']['jurisdictions'] ?? null)
            || false !== ($turn['findings_authority'] ?? null) || false !== ($turn['deliberation_authority'] ?? null)
            || false !== ($turn['execution_authority'] ?? null) || true !== ($turn['sealed'] ?? null)) throw new \RuntimeException($config['errors']['chain']);
    }

    private function priorTestimony(string $jurisdiction, string $turnId, array $turn): array
    {
        if ('security' === $jurisdiction) return ['jurisdiction' => 'trust', 'turn_id' => $turnId, 'turn_digest' => $turn['record_digest'], 'sealed' => true];
        return [
            ['jurisdiction' => 'trust', 'turn_id' => $turn['source_prior_testimony_turn']['id'], 'turn_digest' => $turn['source_prior_testimony_turn']['digest'], 'sealed' => true],
            ['jurisdiction' => 'security', 'turn_id' => $turnId, 'turn_digest' => $turn['record_digest'], 'sealed' => true],
        ];
    }

    private function config(string $jurisdiction): array
    {
        if (!in_array($jurisdiction, ['security', 'usability'], true)) throw new \InvalidArgumentException('S791_DELEGATE_MISSION_SUBSEQUENT_QUESTION_JURISDICTION_INVALID');
        $base = 'security' === $jurisdiction ? 600 : 660;
        $upper = strtoupper($jurisdiction);
        $prior = 'security' === $jurisdiction ? 'trust' : 'security';
        return [
            'jurisdiction' => $jurisdiction, 'sequence' => 'security' === $jurisdiction ? 2 : 3,
            'prior_jurisdiction' => $prior, 'prior_sequence' => 'security' === $jurisdiction ? 1 : 2,
            'prior_key' => 'security' === $jurisdiction ? 'prior_testimony' : 'prior_testimony_turns',
            'source_status' => 'DELEGATE_MISSION_'.strtoupper($prior).'_TESTIMONY_RESPONSE_SEALED_PENDING_'.$upper.'_QUESTION_COMMISSION',
            'authority_purpose' => 'ISSUE_ONE_BOUNDED_'.$upper.'_QUESTION_COMMISSION',
            'errors' => [
                'turn_id' => 'S'.$base.'_DELEGATE_MISSION_TRUST_TESTIMONY_TURN_ID_INVALID',
                'speaker_id' => 'S'.($base + 1).'_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID',
                'senator_id' => 'S'.($base + 2).'_DELEGATE_MISSION_'.$upper.'_SENATOR_BINDING_ID_INVALID',
                'turn_absent' => 'S'.($base + 3).'_DELEGATE_MISSION_TRUST_TESTIMONY_TURN_ABSENT',
                'speaker_absent' => 'S'.($base + 4).'_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE',
                'senator_absent' => 'S'.($base + 5).'_DELEGATE_MISSION_'.$upper.'_SENATOR_UNAVAILABLE',
                'custody_absent' => 'S'.($base + 6).'_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT',
                'chain' => 'S'.($base + 7).'_DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_CHAIN_INVALID',
                'persistence' => 'S'.($base + 8).'_DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_PERSISTENCE_FAILED',
                'conflict' => 'S'.($base + 9).'_DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_CONFLICT',
            ],
        ];
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $id, array $record, array $errors): array
    {
        if (!is_dir($this->commissions) && !mkdir($this->commissions, 0770, true) && !is_dir($this->commissions)) throw new \RuntimeException($errors['persistence']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->commissions.'/'.$id.'.json';
        if (is_file($path)) { $prior = $this->read($path, $errors['conflict']); if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) throw new \RuntimeException($errors['conflict']); return $prior; }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException($errors['persistence']);
        return $record;
    }
}
