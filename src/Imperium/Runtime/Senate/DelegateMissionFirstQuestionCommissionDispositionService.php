<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionFirstQuestionCommissionDispositionService
{
    private const array DISPOSITIONS = ['ACCEPTED', 'REFUSED'];

    private string $commissions;
    private string $openings;
    private string $custody;
    private string $occupancy;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->commissions = $senate.'/delegate-mission-profile-examination-question-commissions';
        $this->openings = $senate.'/delegate-mission-profile-examination-openings';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $senate.'/occupancy';
        $this->dispositions = $senate.'/delegate-mission-profile-examination-question-commission-dispositions';
    }

    public function decide(string $commissionId, string $trustSenatorBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-question-commission-[a-f0-9]{20}$/', $commissionId)) {
            throw new \InvalidArgumentException('S550_DELEGATE_MISSION_QUESTION_COMMISSION_ID_INVALID');
        }
        if (!preg_match('/^senate-committee-trust-binding-[a-f0-9]{20}$/', $trustSenatorBindingId)) {
            throw new \InvalidArgumentException('S551_DELEGATE_MISSION_TRUST_SENATOR_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('S552_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_INVALID');
        }

        $commission = $this->read($this->commissions.'/'.$commissionId.'.json', 'S553_DELEGATE_MISSION_QUESTION_COMMISSION_ABSENT');
        $openingId = $commission['source_examination_opening']['id'] ?? '';
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S554_DELEGATE_MISSION_EXAMINATION_OPENING_ABSENT');
        $custodyId = $commission['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S555_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $trustSenator = $this->read($this->occupancy.'/'.$trustSenatorBindingId.'.json', 'S556_DELEGATE_MISSION_TRUST_SENATOR_UNAVAILABLE');
        $this->validate($commissionId, $trustSenatorBindingId, $commission, $opening, $custody, $trustSenator);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S559_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            if (($prior['source_commission']['id'] ?? null) === $commissionId) {
                if (($prior['source_commission']['digest'] ?? null) === $commission['record_digest']
                    && ($prior['trust_senator']['binding_id'] ?? null) === $trustSenatorBindingId
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException('S559_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            }
        }

        $actor = [
            'seat' => 'senate.committee.trust',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $trustSenatorBindingId,
            'binding_digest' => $trustSenator['record_digest'],
            'manifestation_id' => $trustSenator['manifestation_id'],
            'occupancy_generation' => $trustSenator['occupancy_generation'],
        ];
        $accepted = 'ACCEPTED' === $disposition;
        $dispositionId = 'delegate-mission-profile-examination-question-commission-disposition-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $authorshipAuthority = null;
        if ($accepted) {
            $authorshipAuthority = [
                'authority_id' => 'delegate-mission-trust-question-authorship-authority-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $commission['hearing_contract']['subject'], 'trust', 1])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => ['seat' => 'senate.committee.trust', 'binding_id' => $trustSenatorBindingId, 'binding_digest' => $trustSenator['record_digest']],
                'purpose' => 'AUTHOR_ONE_BOUNDED_TRUST_EXAMINATION_QUESTION',
                'jurisdiction' => 'trust',
                'question_limit' => 1,
                'dispatch_included' => false,
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($dispositionId, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-commission-disposition/v1',
            'disposition_id' => $dispositionId,
            'instance_id' => $commission['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'trust_senator' => $actor,
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_examination_opening' => $commission['source_examination_opening'],
            'source_stand_admission' => $commission['source_stand_admission'],
            'source_profile_candidate' => $commission['source_profile_candidate'],
            'source_reservation_disposition' => $commission['source_reservation_disposition'],
            'custody_lease' => $commission['custody_lease'],
            'manifestation' => $commission['manifestation'],
            'hearing_contract' => $commission['hearing_contract'],
            'jurisdiction' => 'trust',
            'question_limit' => 1,
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'recipient_acceptance_disposition_authority' => ['id' => $commission['recipient_acceptance_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance' => $accepted,
            'question_authorship_authority' => $authorshipAuthority,
            'status' => $accepted ? 'DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ACCEPTED_PENDING_TRUST_QUESTION_AUTHORSHIP' : 'DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY',
            'question_cognition_completed' => false,
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

    private function validate(string $commissionId, string $trustSenatorBindingId, array $commission, array $opening, array $custody, array $trustSenator): void
    {
        $authority = $commission['recipient_acceptance_disposition_authority'] ?? null;
        if (!$this->valid($commission) || !$this->valid($opening) || !$this->valid($custody) || !$this->valid($trustSenator)
            || 'imperium.senate-delegate-mission-profile-examination-question-commission/v1' !== ($commission['schema'] ?? null)
            || $commissionId !== ($commission['commission_id'] ?? null)
            || 'DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ISSUED_PENDING_TRUST_SENATOR_ACCEPTANCE' !== ($commission['status'] ?? null)
            || 'trust' !== ($commission['jurisdiction'] ?? null)
            || 1 !== ($commission['question_limit'] ?? null)
            || true !== ($commission['recipient']['acceptance_pending'] ?? null)
            || null !== ($commission['recipient_acceptance'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'ACCEPT_OR_REFUSE_ONE_BOUNDED_TRUST_QUESTION_COMMISSION' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($commission['recipient']['binding_id'] ?? null) !== $trustSenatorBindingId
            || ($commission['recipient']['binding_digest'] ?? null) !== ($trustSenator['record_digest'] ?? null)
            || ($authority['holder']['binding_id'] ?? null) !== $trustSenatorBindingId
            || ($authority['holder']['binding_digest'] ?? null) !== ($trustSenator['record_digest'] ?? null)
            || ($commission['source_examination_opening']['id'] ?? null) !== ($opening['opening_id'] ?? null)
            || ($commission['source_examination_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
            || CanonicalJson::encode($commission['hearing_contract'] ?? null) !== CanonicalJson::encode($opening['hearing_contract'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION' !== ($opening['status'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($commission['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($commission['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || 'imperium.senate-committee-occupancy/v1' !== ($trustSenator['schema'] ?? null)
            || 'senate.committee.trust' !== ($trustSenator['seat'] ?? null)
            || OfficerClass::Legate->value !== ($trustSenator['officer_class'] ?? null)
            || 'ACTIVE' !== ($trustSenator['status'] ?? null)
            || true !== ($trustSenator['binding_atomic'] ?? null)
            || true !== ($trustSenator['delegate_question_commission_acceptance_disposition_authority'] ?? null)
            || true === ($trustSenator['execution_authority'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($trustSenator['instance_id'] ?? null)
            || false !== ($commission['question_authorship_authority'] ?? null)
            || false !== ($commission['question_authored'] ?? null)
            || false !== ($commission['question_dispatched'] ?? null)
            || false !== ($commission['cognition_authority'] ?? null)
            || false !== ($commission['execution_authority'] ?? null)
            || true !== ($commission['sealed'] ?? null)) {
            throw new \RuntimeException('S557_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CHAIN_INVALID');
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
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) {
            throw new \RuntimeException('S558_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'S559_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('S559_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S558_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
