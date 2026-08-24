<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSecurityQuestionCommissionDispositionService
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

    public function decide(string $commissionId, string $securitySenatorBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-question-commission-[a-f0-9]{20}$/', $commissionId)) {
            throw new \InvalidArgumentException('S610_DELEGATE_MISSION_QUESTION_COMMISSION_ID_INVALID');
        }
        if (!preg_match('/^senate-committee-security-binding-[a-f0-9]{20}$/', $securitySenatorBindingId)) {
            throw new \InvalidArgumentException('S611_DELEGATE_MISSION_SECURITY_SENATOR_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('S612_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_INVALID');
        }

        $commission = $this->read($this->commissions.'/'.$commissionId.'.json', 'S613_DELEGATE_MISSION_QUESTION_COMMISSION_ABSENT');
        $openingId = $commission['source_examination_opening']['id'] ?? '';
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S614_DELEGATE_MISSION_EXAMINATION_OPENING_ABSENT');
        $custodyId = $commission['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S615_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $securitySenator = $this->read($this->occupancy.'/'.$securitySenatorBindingId.'.json', 'S616_DELEGATE_MISSION_SECURITY_SENATOR_UNAVAILABLE');
        $this->validate($commissionId, $securitySenatorBindingId, $commission, $opening, $custody, $securitySenator);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S619_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            if (($prior['source_commission']['id'] ?? null) === $commissionId) {
                if (($prior['source_commission']['digest'] ?? null) === $commission['record_digest']
                    && ($prior['security_senator']['binding_id'] ?? null) === $securitySenatorBindingId
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException('S619_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            }
        }

        $actor = [
            'seat' => 'senate.committee.security',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $securitySenatorBindingId,
            'binding_digest' => $securitySenator['record_digest'],
            'manifestation_id' => $securitySenator['manifestation_id'],
            'occupancy_generation' => $securitySenator['occupancy_generation'],
        ];
        $accepted = 'ACCEPTED' === $disposition;
        $dispositionId = 'delegate-mission-profile-examination-question-commission-disposition-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $authorshipAuthority = null;
        if ($accepted) {
            $authorshipAuthority = [
                'authority_id' => 'delegate-mission-security-question-authorship-authority-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $commission['hearing_contract']['subject'], 'security', 1])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => ['seat' => 'senate.committee.security', 'binding_id' => $securitySenatorBindingId, 'binding_digest' => $securitySenator['record_digest']],
                'purpose' => 'AUTHOR_ONE_BOUNDED_SECURITY_EXAMINATION_QUESTION',
                'jurisdiction' => 'security',
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
            'security_senator' => $actor,
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_prior_testimony_turn' => $commission['source_prior_testimony_turn'],
            'source_examination_opening' => $commission['source_examination_opening'],
            'source_stand_admission' => $commission['source_stand_admission'],
            'source_profile_candidate' => $commission['source_profile_candidate'],
            'source_reservation_disposition' => $commission['source_reservation_disposition'],
            'custody_lease' => $commission['custody_lease'],
            'manifestation' => $commission['manifestation'],
            'hearing_contract' => $commission['hearing_contract'],
            'jurisdiction' => 'security',
            'question_sequence' => 2,
            'question_limit' => 1,
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'recipient_acceptance_disposition_authority' => ['id' => $commission['recipient_acceptance_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance' => $accepted,
            'question_authorship_authority' => $authorshipAuthority,
            'status' => $accepted ? 'DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_ACCEPTED_PENDING_SECURITY_QUESTION_AUTHORSHIP' : 'DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY',
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

    private function validate(string $commissionId, string $securitySenatorBindingId, array $commission, array $opening, array $custody, array $securitySenator): void
    {
        $authority = $commission['recipient_acceptance_disposition_authority'] ?? null;
        if (!$this->valid($commission) || !$this->valid($opening) || !$this->valid($custody) || !$this->valid($securitySenator)
            || 'imperium.senate-delegate-mission-profile-examination-question-commission/v1' !== ($commission['schema'] ?? null)
            || $commissionId !== ($commission['commission_id'] ?? null)
            || 'DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_ISSUED_PENDING_SECURITY_SENATOR_ACCEPTANCE' !== ($commission['status'] ?? null)
            || 'security' !== ($commission['jurisdiction'] ?? null)
            || 1 !== ($commission['question_limit'] ?? null)
            || true !== ($commission['recipient']['acceptance_pending'] ?? null)
            || null !== ($commission['recipient_acceptance'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'ACCEPT_OR_REFUSE_ONE_BOUNDED_SECURITY_QUESTION_COMMISSION' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($commission['recipient']['binding_id'] ?? null) !== $securitySenatorBindingId
            || ($commission['recipient']['binding_digest'] ?? null) !== ($securitySenator['record_digest'] ?? null)
            || ($authority['holder']['binding_id'] ?? null) !== $securitySenatorBindingId
            || ($authority['holder']['binding_digest'] ?? null) !== ($securitySenator['record_digest'] ?? null)
            || ($commission['source_examination_opening']['id'] ?? null) !== ($opening['opening_id'] ?? null)
            || ($commission['source_examination_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
            || CanonicalJson::encode($commission['hearing_contract'] ?? null) !== CanonicalJson::encode($opening['hearing_contract'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION' !== ($opening['status'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($commission['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($commission['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || 'imperium.senate-committee-occupancy/v1' !== ($securitySenator['schema'] ?? null)
            || 'senate.committee.security' !== ($securitySenator['seat'] ?? null)
            || OfficerClass::Legate->value !== ($securitySenator['officer_class'] ?? null)
            || 'ACTIVE' !== ($securitySenator['status'] ?? null)
            || true !== ($securitySenator['binding_atomic'] ?? null)
            || true !== ($securitySenator['delegate_question_commission_acceptance_disposition_authority'] ?? null)
            || true === ($securitySenator['execution_authority'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($securitySenator['instance_id'] ?? null)
            || false !== ($commission['question_authorship_authority'] ?? null)
            || false !== ($commission['question_authored'] ?? null)
            || false !== ($commission['question_dispatched'] ?? null)
            || false !== ($commission['cognition_authority'] ?? null)
            || false !== ($commission['execution_authority'] ?? null)
            || true !== ($commission['sealed'] ?? null)) {
            throw new \RuntimeException('S617_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CHAIN_INVALID');
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
            throw new \RuntimeException('S618_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'S619_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('S619_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S618_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
