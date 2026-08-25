<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSubsequentQuestionCommissionDispositionEngine
{
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

    public function decide(string $jurisdiction, string $commissionId, string $senatorBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        $c = $this->configuration($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-question-commission-[a-f0-9]{20}$/', $commissionId)) {
            throw new \InvalidArgumentException($c['errors'][0]);
        }
        if (!preg_match($c['binding_pattern'], $senatorBindingId)) {
            throw new \InvalidArgumentException($c['errors'][1]);
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, ['ACCEPTED', 'REFUSED'], true) || '' === $rationale) {
            throw new \InvalidArgumentException($c['errors'][2]);
        }

        $commission = $this->read($this->commissions.'/'.$commissionId.'.json', $c['errors'][3]);
        $opening = $this->read($this->openings.'/'.($commission['source_examination_opening']['id'] ?? '').'.json', $c['errors'][4]);
        $custody = $this->read($this->custody.'/'.($commission['custody_lease']['custody_id'] ?? '').'.json', $c['errors'][5]);
        $senator = $this->read($this->occupancy.'/'.$senatorBindingId.'.json', $c['errors'][6]);
        $this->validate($c, $commissionId, $senatorBindingId, $commission, $opening, $custody, $senator);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, $c['errors'][9]);
            if (($prior['source_commission']['id'] ?? null) === $commissionId) {
                if (($prior['source_commission']['digest'] ?? null) === $commission['record_digest']
                    && ($prior[$c['actor_key']]['binding_id'] ?? null) === $senatorBindingId
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException($c['errors'][9]);
            }
        }

        $actor = [
            'seat' => $c['seat'],
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $senatorBindingId,
            'binding_digest' => $senator['record_digest'],
            'manifestation_id' => $senator['manifestation_id'],
            'occupancy_generation' => $senator['occupancy_generation'],
        ];
        $accepted = 'ACCEPTED' === $disposition;
        $dispositionId = 'delegate-mission-profile-examination-question-commission-disposition-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $authorshipAuthority = $accepted ? [
            'authority_id' => 'delegate-mission-'.$jurisdiction.'-question-authorship-authority-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $commission['hearing_contract']['subject'], $jurisdiction, 1])), 0, 20),
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'holder' => ['seat' => $c['seat'], 'binding_id' => $senatorBindingId, 'binding_digest' => $senator['record_digest']],
            'purpose' => $c['authorship_purpose'],
            'jurisdiction' => $jurisdiction,
            'question_limit' => 1,
            'dispatch_included' => false,
            'consumed' => false,
            'continuing_authority' => false,
        ] : null;

        $record = [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-commission-disposition/v1',
            'disposition_id' => $dispositionId,
            'instance_id' => $commission['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            $c['actor_key'] => $actor,
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_prior_testimony_turn' => $commission['source_prior_testimony_turn'],
        ];
        if ('usability' === $jurisdiction) {
            $record['source_earlier_testimony_turn'] = $commission['source_earlier_testimony_turn'];
        }
        $record += [
            'source_examination_opening' => $commission['source_examination_opening'],
            'source_stand_admission' => $commission['source_stand_admission'],
            'source_profile_candidate' => $commission['source_profile_candidate'],
            'source_reservation_disposition' => $commission['source_reservation_disposition'],
            'custody_lease' => $commission['custody_lease'],
            'manifestation' => $commission['manifestation'],
            'hearing_contract' => $commission['hearing_contract'],
            'jurisdiction' => $jurisdiction,
            'question_sequence' => $c['sequence'],
            'question_limit' => 1,
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'recipient_acceptance_disposition_authority' => ['id' => $commission['recipient_acceptance_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance' => $accepted,
            'question_authorship_authority' => $authorshipAuthority,
            'status' => $accepted ? $c['accepted_status'] : $c['refused_status'],
        ];
        foreach (['question_cognition_completed', 'question_authored', 'question_dispatch_authority', 'question_dispatched', 'examination_cognition_authority', 'testimony_authority', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'operational_profile_installation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'cognition_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) {
            $record[$field] = false;
        }
        $record['sealed'] = true;

        return $this->save($c, $dispositionId, $record);
    }

    private function configuration(string $jurisdiction): array
    {
        return match ($jurisdiction) {
            'security' => $this->config('security', 61, 2),
            'usability' => $this->config('usability', 67, 3),
            default => throw new \InvalidArgumentException('S702_DELEGATE_MISSION_SUBSEQUENT_QUESTION_DISPOSITION_JURISDICTION_INVALID'),
        };
    }

    private function config(string $jurisdiction, int $errorFamily, int $sequence): array
    {
        $upper = strtoupper($jurisdiction);
        $prefix = 'S'.$errorFamily;
        return [
            'jurisdiction' => $jurisdiction, 'sequence' => $sequence, 'seat' => 'senate.committee.'.$jurisdiction,
            'actor_key' => $jurisdiction.'_senator', 'binding_pattern' => '/^senate-committee-'.$jurisdiction.'-binding-[a-f0-9]{20}$/',
            'acceptance_purpose' => 'ACCEPT_OR_REFUSE_ONE_BOUNDED_'.$upper.'_QUESTION_COMMISSION',
            'authorship_purpose' => 'AUTHOR_ONE_BOUNDED_'.$upper.'_EXAMINATION_QUESTION',
            'issued_status' => 'DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_ISSUED_PENDING_'.$upper.'_SENATOR_ACCEPTANCE',
            'accepted_status' => 'DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_ACCEPTED_PENDING_'.$upper.'_QUESTION_AUTHORSHIP',
            'refused_status' => 'DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY',
            'errors' => [$prefix.'0_DELEGATE_MISSION_QUESTION_COMMISSION_ID_INVALID', $prefix.'1_DELEGATE_MISSION_'.$upper.'_SENATOR_BINDING_ID_INVALID', $prefix.'2_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_INVALID', $prefix.'3_DELEGATE_MISSION_QUESTION_COMMISSION_ABSENT', $prefix.'4_DELEGATE_MISSION_EXAMINATION_OPENING_ABSENT', $prefix.'5_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT', $prefix.'6_DELEGATE_MISSION_'.$upper.'_SENATOR_UNAVAILABLE', $prefix.'7_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CHAIN_INVALID', $prefix.'8_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_PERSISTENCE_FAILED', $prefix.'9_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_CONFLICT'],
        ];
    }

    private function validate(array $c, string $commissionId, string $bindingId, array $commission, array $opening, array $custody, array $senator): void
    {
        $authority = $commission['recipient_acceptance_disposition_authority'] ?? null;
        if (!$this->valid($commission) || !$this->valid($opening) || !$this->valid($custody) || !$this->valid($senator)
            || 'imperium.senate-delegate-mission-profile-examination-question-commission/v1' !== ($commission['schema'] ?? null) || $commissionId !== ($commission['commission_id'] ?? null)
            || $c['issued_status'] !== ($commission['status'] ?? null) || $c['jurisdiction'] !== ($commission['jurisdiction'] ?? null) || 1 !== ($commission['question_limit'] ?? null)
            || true !== ($commission['recipient']['acceptance_pending'] ?? null) || null !== ($commission['recipient_acceptance'] ?? null) || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null) || $c['acceptance_purpose'] !== ($authority['purpose'] ?? null) || false !== ($authority['consumed'] ?? null)
            || ($commission['recipient']['binding_id'] ?? null) !== $bindingId || ($commission['recipient']['binding_digest'] ?? null) !== ($senator['record_digest'] ?? null)
            || ($authority['holder']['binding_id'] ?? null) !== $bindingId || ($authority['holder']['binding_digest'] ?? null) !== ($senator['record_digest'] ?? null)
            || ($commission['source_examination_opening']['id'] ?? null) !== ($opening['opening_id'] ?? null) || ($commission['source_examination_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
            || CanonicalJson::encode($commission['hearing_contract'] ?? null) !== CanonicalJson::encode($opening['hearing_contract'] ?? null) || 'DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION' !== ($opening['status'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null) || ($commission['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null) || ($commission['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null) || 'imperium.senate-committee-occupancy/v1' !== ($senator['schema'] ?? null)
            || $c['seat'] !== ($senator['seat'] ?? null) || OfficerClass::Legate->value !== ($senator['officer_class'] ?? null) || 'ACTIVE' !== ($senator['status'] ?? null) || true !== ($senator['binding_atomic'] ?? null)
            || true !== ($senator['delegate_question_commission_acceptance_disposition_authority'] ?? null) || true === ($senator['execution_authority'] ?? null) || ($commission['instance_id'] ?? null) !== ($senator['instance_id'] ?? null)
            || false !== ($commission['question_authorship_authority'] ?? null) || false !== ($commission['question_authored'] ?? null) || false !== ($commission['question_dispatched'] ?? null) || false !== ($commission['cognition_authority'] ?? null)
            || false !== ($commission['execution_authority'] ?? null) || true !== ($commission['sealed'] ?? null)) {
            throw new \RuntimeException($c['errors'][7]);
        }
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) { throw new \RuntimeException($error); }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(array $c, string $id, array $record): array
    {
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) { throw new \RuntimeException($c['errors'][8]); }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, $c['errors'][9]);
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) { throw new \RuntimeException($c['errors'][9]); }
            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) { throw new \RuntimeException($c['errors'][8]); }
        return $record;
    }
}
