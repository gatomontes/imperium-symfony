<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionJurisdictionQuestionAuthorshipEngine
{
    private string $dispositions;
    private string $commissions;
    private string $openings;
    private string $custody;
    private string $occupancy;
    private string $questions;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private ProfileExaminationQuestionCognitionGateway $cognition,
    ) {
        $senate = $root.'/var/imperium/offices/senate';
        $this->dispositions = $senate.'/delegate-mission-profile-examination-question-commission-dispositions';
        $this->commissions = $senate.'/delegate-mission-profile-examination-question-commissions';
        $this->openings = $senate.'/delegate-mission-profile-examination-openings';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $senate.'/occupancy';
        $this->questions = $senate.'/delegate-mission-profile-examination-questions';
    }

    public function author(string $jurisdiction, string $dispositionId, string $bindingId, \DateTimeImmutable $authoredAt): array
    {
        $config = $this->config($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-question-commission-disposition-[a-f0-9]{20}$/', $dispositionId)) {
            throw new \InvalidArgumentException($config['errors']['disposition_id']);
        }
        if (!preg_match('/^senate-committee-'.$jurisdiction.'-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException($config['errors']['binding_id']);
        }

        $disposition = $this->read($this->dispositions.'/'.$dispositionId.'.json', $config['errors']['disposition_absent']);
        $commission = $this->read($this->commissions.'/'.($disposition['source_commission']['id'] ?? '').'.json', $config['errors']['commission_absent']);
        $opening = $this->read($this->openings.'/'.($disposition['source_examination_opening']['id'] ?? '').'.json', $config['errors']['opening_absent']);
        $custody = $this->read($this->custody.'/'.($disposition['custody_lease']['custody_id'] ?? '').'.json', $config['errors']['custody_absent']);
        $senator = $this->read($this->occupancy.'/'.$bindingId.'.json', $config['errors']['senator_absent']);
        $this->validate($config, $dispositionId, $bindingId, $disposition, $commission, $opening, $custody, $senator);

        foreach (glob($this->questions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, $config['errors']['conflict']);
            if (($prior['source_commission_disposition']['id'] ?? null) === $dispositionId) {
                if (($prior['source_commission_disposition']['digest'] ?? null) === $disposition['record_digest']
                    && ($prior[$config['actor_key']]['binding_id'] ?? null) === $bindingId) {
                    return $prior;
                }
                throw new \RuntimeException($config['errors']['conflict']);
            }
        }

        $authored = $this->cognition->authorQuestion($jurisdiction, $commission, $opening);
        if (['purpose', 'question'] !== array_keys($authored)
            || !is_string($authored['purpose']) || '' === trim($authored['purpose'])
            || !is_string($authored['question']) || '' === trim($authored['question'])) {
            throw new \RuntimeException($config['errors']['cognition']);
        }
        $authored = ['purpose' => trim($authored['purpose']), 'question' => trim($authored['question'])];
        $actor = [
            'seat' => 'senate.committee.'.$jurisdiction,
            'binding_id' => $bindingId,
            'binding_digest' => $senator['record_digest'],
            'manifestation_id' => $senator['manifestation_id'],
            'occupancy_generation' => $senator['occupancy_generation'],
        ];
        $questionId = 'delegate-mission-profile-examination-question-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], $actor, $authored])), 0, 20);
        $record = [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question/v1',
            'question_id' => $questionId,
            'instance_id' => $disposition['instance_id'],
            'officer_class' => $disposition['officer_class'],
            $config['actor_key'] => $actor,
            'source_commission_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
            'source_commission' => $disposition['source_commission'],
        ];
        foreach ($config['prior_sources'] as $source) {
            $record[$source] = $disposition[$source];
        }
        $record += [
            'source_examination_opening' => $disposition['source_examination_opening'],
            'source_stand_admission' => $disposition['source_stand_admission'],
            'source_profile_candidate' => $disposition['source_profile_candidate'],
            'source_reservation_disposition' => $disposition['source_reservation_disposition'],
            'custody_lease' => $disposition['custody_lease'],
            'manifestation' => $disposition['manifestation'],
            'hearing_contract' => $disposition['hearing_contract'],
            'jurisdiction' => $jurisdiction,
            'question_sequence' => $config['sequence'],
            'question' => $authored,
            'question_authorship_authority' => ['id' => $disposition['question_authorship_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'question_dispatch_authorization_authority' => [
                'authority_id' => 'delegate-mission-question-dispatch-authorization-authority-'.substr(hash('sha256', CanonicalJson::encode([$questionId, $authored, $opening['hearing_contract']['subject']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'senate.lord-speaker',
                'purpose' => 'DECIDE_DISPATCH_OF_ONE_SEALED_'.strtoupper($jurisdiction).'_EXAMINATION_QUESTION',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'authored_at' => $authoredAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_'.strtoupper($jurisdiction).'_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION',
            'question_cognition_completed' => true,
            'question_authored' => true,
            'question_dispatch_authority' => false,
            'question_dispatched' => false,
            'testimony_authority' => false,
            'testimony_received' => false,
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
        ];

        return $this->save($questionId, $record, $config['errors']);
    }

    private function validate(array $config, string $dispositionId, string $bindingId, array $disposition, array $commission, array $opening, array $custody, array $senator): void
    {
        $authority = $disposition['question_authorship_authority'] ?? null;
        if (!$this->valid($disposition) || !$this->valid($commission) || !$this->valid($opening) || !$this->valid($custody) || !$this->valid($senator)
            || 'imperium.senate-delegate-mission-profile-examination-question-commission-disposition/v1' !== ($disposition['schema'] ?? null)
            || $dispositionId !== ($disposition['disposition_id'] ?? null)
            || 'ACCEPTED' !== ($disposition['disposition'] ?? null)
            || true !== ($disposition['recipient_acceptance'] ?? null)
            || $config['accepted_status'] !== ($disposition['status'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || $config['authority_purpose'] !== ($authority['purpose'] ?? null)
            || $config['jurisdiction'] !== ($authority['jurisdiction'] ?? null)
            || 1 !== ($authority['question_limit'] ?? null)
            || false !== ($authority['dispatch_included'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($authority['holder']['binding_id'] ?? null) !== $bindingId
            || ($authority['holder']['binding_digest'] ?? null) !== ($senator['record_digest'] ?? null)
            || ($disposition[$config['actor_key']]['binding_id'] ?? null) !== $bindingId
            || ($disposition[$config['actor_key']]['binding_digest'] ?? null) !== ($senator['record_digest'] ?? null)
            || ($disposition['source_commission']['id'] ?? null) !== ($commission['commission_id'] ?? null)
            || ($disposition['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || ($disposition['source_examination_opening']['id'] ?? null) !== ($opening['opening_id'] ?? null)
            || ($disposition['source_examination_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
            || CanonicalJson::encode($disposition['hearing_contract'] ?? null) !== CanonicalJson::encode($opening['hearing_contract'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || ($disposition['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($disposition['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'senate.committee.'.$config['jurisdiction'] !== ($senator['seat'] ?? null)
            || 'ACTIVE' !== ($senator['status'] ?? null)
            || true !== ($senator['binding_atomic'] ?? null)
            || true !== ($senator['senator_question_authority'] ?? null)
            || true === ($senator['execution_authority'] ?? null)
            || ($disposition['instance_id'] ?? null) !== ($senator['instance_id'] ?? null)
            || false !== ($disposition['question_cognition_completed'] ?? null)
            || false !== ($disposition['question_authored'] ?? null)
            || false !== ($disposition['question_dispatched'] ?? null)
            || true !== ($disposition['sealed'] ?? null)) {
            throw new \RuntimeException($config['errors']['chain']);
        }
    }

    private function config(string $jurisdiction): array
    {
        $base = match ($jurisdiction) {'trust' => 560, 'security' => 620, 'usability' => 680, default => throw new \InvalidArgumentException('S790_DELEGATE_MISSION_QUESTION_JURISDICTION_INVALID')};
        $upper = strtoupper($jurisdiction);
        return [
            'jurisdiction' => $jurisdiction,
            'actor_key' => $jurisdiction.'_senator',
            'sequence' => ['trust' => 1, 'security' => 2, 'usability' => 3][$jurisdiction],
            'prior_sources' => ['trust' => [], 'security' => ['source_prior_testimony_turn'], 'usability' => ['source_prior_testimony_turn', 'source_earlier_testimony_turn']][$jurisdiction],
            'accepted_status' => 'trust' === $jurisdiction ? 'DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ACCEPTED_PENDING_TRUST_QUESTION_AUTHORSHIP' : 'DELEGATE_MISSION_'.$upper.'_QUESTION_COMMISSION_ACCEPTED_PENDING_'.$upper.'_QUESTION_AUTHORSHIP',
            'authority_purpose' => 'AUTHOR_ONE_BOUNDED_'.$upper.'_EXAMINATION_QUESTION',
            'errors' => [
                'disposition_id' => 'S'.$base.'_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_ID_INVALID',
                'binding_id' => 'S'.($base + 1).'_DELEGATE_MISSION_'.$upper.'_SENATOR_BINDING_ID_INVALID',
                'disposition_absent' => 'S'.($base + 2).'_DELEGATE_MISSION_QUESTION_COMMISSION_DISPOSITION_ABSENT',
                'commission_absent' => 'S'.($base + 3).'_DELEGATE_MISSION_QUESTION_COMMISSION_ABSENT',
                'opening_absent' => 'S'.($base + 4).'_DELEGATE_MISSION_EXAMINATION_OPENING_ABSENT',
                'custody_absent' => 'S'.($base + 5).'_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT',
                'senator_absent' => 'S'.($base + 6).'_DELEGATE_MISSION_'.$upper.'_SENATOR_UNAVAILABLE',
                'cognition' => 'S'.($base + 7).'_DELEGATE_MISSION_'.$upper.'_QUESTION_COGNITION_INVALID',
                'chain' => 'S'.($base + 8).'_DELEGATE_MISSION_'.$upper.'_QUESTION_AUTHORSHIP_CHAIN_INVALID',
                'conflict' => 'S'.($base + 9).'_DELEGATE_MISSION_'.$upper.'_QUESTION_CONFLICT',
                'persistence' => 'S'.($base + 9).'_DELEGATE_MISSION_'.$upper.'_QUESTION_PERSISTENCE_FAILED',
            ],
        ];
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record, array $errors): array
    {
        if (!is_dir($this->questions) && !mkdir($this->questions, 0770, true) && !is_dir($this->questions)) throw new \RuntimeException($errors['persistence']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->questions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, $errors['conflict']);
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) throw new \RuntimeException($errors['conflict']);
            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException($errors['persistence']);
        return $record;
    }
}
