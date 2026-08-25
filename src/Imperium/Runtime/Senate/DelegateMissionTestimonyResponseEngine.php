<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionTestimonyResponseEngine
{
    private string $dispatches;
    private string $questions;
    private string $candidates;
    private string $custody;
    private string $turns;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private ProfileExaminationTestimonyCognitionGateway $cognition)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->dispatches = $senate.'/delegate-mission-profile-examination-question-dispatches';
        $this->questions = $senate.'/delegate-mission-profile-examination-questions';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->turns = $senate.'/delegate-mission-profile-examination-testimony-turns';
    }

    public function respond(string $jurisdiction, string $dispatchId, \DateTimeImmutable $respondedAt): array
    {
        $c = $this->configuration($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-question-dispatch-[a-f0-9]{20}$/', $dispatchId)) throw new \InvalidArgumentException($c['errors'][0]);
        $dispatch = $this->read($this->dispatches.'/'.$dispatchId.'.json', $c['errors'][1]);
        $question = $this->read($this->questions.'/'.($dispatch['source_question']['id'] ?? '').'.json', $c['errors'][2]);
        $candidate = $this->read($this->candidates.'/'.($dispatch['source_profile_candidate']['id'] ?? '').'.json', $c['errors'][3]);
        $custody = $this->read($this->custody.'/'.($dispatch['custody_lease']['custody_id'] ?? '').'.json', $c['errors'][4]);
        $securityTurn = $trustTurn = null;
        if ('usability' === $jurisdiction) {
            $securityTurn = $this->read($this->turns.'/'.($dispatch['source_prior_testimony_turn']['id'] ?? '').'.json', $c['errors'][7].'_DELEGATE_MISSION_SECURITY_TESTIMONY_ABSENT');
            $trustTurn = $this->read($this->turns.'/'.($dispatch['source_earlier_testimony_turn']['id'] ?? '').'.json', $c['errors'][7].'_DELEGATE_MISSION_TRUST_TESTIMONY_ABSENT');
        }
        $this->validate($c, $dispatchId, $dispatch, $question, $candidate, $custody, $securityTurn, $trustTurn);
        foreach (glob($this->turns.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, $c['errors'][9]);
            if (($prior['source_dispatch']['id'] ?? null) === $dispatchId) {
                if (($prior['source_dispatch']['digest'] ?? null) === $dispatch['record_digest']) return $prior;
                throw new \RuntimeException($c['errors'][9]);
            }
        }

        $answer = $this->cognition->answer($dispatch, $dispatch['manifestation']);
        $this->validateAnswer($c, $answer);
        $id = 'delegate-mission-profile-examination-testimony-turn-'.substr(hash('sha256', CanonicalJson::encode([$dispatchId, $dispatch['record_digest'], $answer])), 0, 20);
        $record = ['schema' => 'imperium.senate-delegate-mission-profile-examination-testimony-turn/v1', 'turn_id' => $id, 'instance_id' => $dispatch['instance_id'], 'officer_class' => $dispatch['officer_class'], 'source_dispatch' => ['id' => $dispatchId, 'digest' => $dispatch['record_digest']], 'source_question' => $dispatch['source_question'], 'source_commission_disposition' => $dispatch['source_commission_disposition'], 'source_commission' => $dispatch['source_commission']];
        if ('trust' !== $jurisdiction) $record['source_prior_testimony_turn'] = $dispatch['source_prior_testimony_turn'];
        if ('usability' === $jurisdiction) $record['source_earlier_testimony_turn'] = $dispatch['source_earlier_testimony_turn'];
        $record += ['source_examination_opening' => $dispatch['source_examination_opening'], 'source_stand_admission' => $dispatch['source_stand_admission'], 'source_profile_candidate' => $dispatch['source_profile_candidate'], 'source_reservation_disposition' => $dispatch['source_reservation_disposition'], 'custody_lease' => $dispatch['custody_lease'], 'manifestation' => $dispatch['manifestation'], 'hearing_contract' => $dispatch['hearing_contract'], $c['senator_key'] => $dispatch[$c['senator_key']], 'jurisdiction' => $jurisdiction, 'question_sequence' => $c['sequence'], 'question' => $dispatch['question'], 'testimony' => $answer, 'testimony_response_authority' => ['id' => $dispatch['testimony_response_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false]];
        if ('usability' === $jurisdiction) {
            $openingAuthority = ['authority_id' => 'delegate-mission-finding-phase-opening-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $trustTurn['record_digest'], $securityTurn['record_digest'], $dispatch['hearing_contract']['subject']])), 0, 20), 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'senate.lord-speaker', 'purpose' => 'OPEN_THREE_JURISDICTION_FINDING_AUTHORITIES', 'consumed' => false, 'continuing_authority' => false];
            $record += ['testimony_readiness' => ['jurisdictions' => ['trust', 'security', 'usability'], 'turns' => [['jurisdiction' => 'trust', 'id' => $dispatch['source_earlier_testimony_turn']['id'], 'digest' => $trustTurn['record_digest']], ['jurisdiction' => 'security', 'id' => $dispatch['source_prior_testimony_turn']['id'], 'digest' => $securityTurn['record_digest']], ['jurisdiction' => 'usability', 'id' => $id, 'digest_pending_record_seal' => true]], 'all_questions_dispatched_unchanged' => true, 'all_responses_sealed' => true, 'finding_authored' => false], 'finding_phase_opening_authority' => $openingAuthority, 'next_question_commission_authority' => false, 'responded_at' => $respondedAt->format(DATE_ATOM), 'status' => 'DELEGATE_MISSION_USABILITY_TESTIMONY_RESPONSE_SEALED_PENDING_FINDING_AUTHORITY_OPENING'];
        } else {
            $next = $c['next_jurisdiction']; $nextUpper = strtoupper($next);
            $nextAuthority = ['authority_id' => 'delegate-mission-'.$next.'-question-commission-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $dispatch['hearing_contract']['subject'], $next])), 0, 20), 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'senate.lord-speaker', 'purpose' => 'ISSUE_ONE_BOUNDED_'.$nextUpper.'_QUESTION_COMMISSION', 'jurisdiction' => $next, 'question_limit' => 1, 'consumed' => false, 'continuing_authority' => false];
            $record += ['next_question_commission_authority' => $nextAuthority, 'responded_at' => $respondedAt->format(DATE_ATOM), 'status' => 'DELEGATE_MISSION_'.$c['upper'].'_TESTIMONY_RESPONSE_SEALED_PENDING_'.$nextUpper.'_QUESTION_COMMISSION'];
        }
        $record += ['question_dispatched_unchanged' => true, 'testimony_cognition_completed' => true, 'testimony_received' => true, 'testimony_response_sealed' => true];
        foreach (['findings_authority', 'deliberation_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) $record[$field] = false;
        $record['sealed'] = true;
        return $this->save($c, $id, $record);
    }

    private function configuration(string $jurisdiction): array
    {
        [$base, $sequence, $next] = match ($jurisdiction) { 'trust' => [59, 1, 'security'], 'security' => [65, 2, 'usability'], 'usability' => [71, 3, null], default => throw new \InvalidArgumentException('S795_DELEGATE_MISSION_TESTIMONY_RESPONSE_JURISDICTION_INVALID') };
        $upper = strtoupper($jurisdiction); $prefix = 'S'.$base;
        return ['upper' => $upper, 'sequence' => $sequence, 'next_jurisdiction' => $next, 'senator_key' => $jurisdiction.'_senator', 'dispatch_status' => 'DELEGATE_MISSION_'.$upper.'_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE', 'response_purpose' => 'ANSWER_ONE_DISPATCHED_'.$upper.'_EXAMINATION_QUESTION', 'errors' => [$prefix.'0_DELEGATE_MISSION_QUESTION_DISPATCH_ID_INVALID', $prefix.'1_DELEGATE_MISSION_QUESTION_DISPATCH_ABSENT', $prefix.'2_DELEGATE_MISSION_QUESTION_ABSENT', $prefix.'3_DELEGATE_MISSION_PROFILE_CANDIDATE_ABSENT', $prefix.'4_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT', $prefix.'5_DELEGATE_MISSION_'.$upper.'_TESTIMONY_CHAIN_INVALID', $prefix.'6_DELEGATE_MISSION_'.$upper.'_TESTIMONY_COGNITION_INVALID', $prefix.'7', $prefix.'8_DELEGATE_MISSION_'.$upper.'_TESTIMONY_PERSISTENCE_FAILED', $prefix.'9_DELEGATE_MISSION_'.$upper.'_TESTIMONY_CONFLICT']];
    }

    private function validate(array $c, string $dispatchId, array $d, array $q, array $candidate, array $custody, ?array $securityTurn, ?array $trustTurn): void
    {
        $a = $d['testimony_response_authority'] ?? null;
        $invalid = !$this->valid($d) || !$this->valid($q) || !$this->valid($candidate) || !$this->valid($custody) || 'imperium.senate-delegate-mission-profile-examination-question-dispatch/v1' !== ($d['schema'] ?? null) || $dispatchId !== ($d['dispatch_id'] ?? null) || $c['dispatch_status'] !== ($d['status'] ?? null) || true !== ($d['question_dispatched'] ?? null) || true !== ($d['question_dispatched_unchanged'] ?? null) || false !== ($d['testimony_cognition_completed'] ?? null) || false !== ($d['testimony_received'] ?? null) || !is_array($a) || true !== ($a['authority_single_use'] ?? null) || true !== ($a['authority_exercisable'] ?? null) || false !== ($a['consumed'] ?? null) || $c['response_purpose'] !== ($a['purpose'] ?? null) || ($a['holder']['manifestation_id'] ?? null) !== ($d['manifestation']['manifestation_id'] ?? null) || ($d['source_question']['id'] ?? null) !== ($q['question_id'] ?? null) || ($d['source_question']['digest'] ?? null) !== ($q['record_digest'] ?? null) || CanonicalJson::encode($d['question'] ?? null) !== CanonicalJson::encode($q['question'] ?? null) || ($d['source_profile_candidate']['id'] ?? null) !== ($candidate['candidate_id'] ?? null) || ($d['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null) || ($d['manifestation']['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null) || ($d['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null) || 'SENATE_EXAMINATION_ONLY' !== ($d['manifestation']['purpose'] ?? null) || false !== ($d['manifestation']['operational_use_permitted'] ?? null) || true !== ($d['sealed'] ?? null);
        if (null !== $securityTurn && null !== $trustTurn) $invalid = $invalid || !$this->valid($securityTurn) || !$this->valid($trustTurn) || ($d['source_prior_testimony_turn']['id'] ?? null) !== ($securityTurn['turn_id'] ?? null) || ($d['source_prior_testimony_turn']['digest'] ?? null) !== ($securityTurn['record_digest'] ?? null) || 'security' !== ($securityTurn['jurisdiction'] ?? null) || 2 !== ($securityTurn['question_sequence'] ?? null) || true !== ($securityTurn['testimony_response_sealed'] ?? null) || ($d['source_earlier_testimony_turn']['id'] ?? null) !== ($trustTurn['turn_id'] ?? null) || ($d['source_earlier_testimony_turn']['digest'] ?? null) !== ($trustTurn['record_digest'] ?? null) || 'trust' !== ($trustTurn['jurisdiction'] ?? null) || 1 !== ($trustTurn['question_sequence'] ?? null) || true !== ($trustTurn['testimony_response_sealed'] ?? null);
        if ($invalid) throw new \RuntimeException($c['errors'][5]);
    }

    private function validateAnswer(array $c, array $answer): void
    {
        $keys = array_keys($answer); sort($keys, SORT_STRING);
        if (['answer', 'evidence_claims', 'refusals', 'uncertainties'] !== $keys || !is_string($answer['answer']) || '' === trim($answer['answer'])) throw new \RuntimeException($c['errors'][6]);
        foreach (['evidence_claims', 'refusals', 'uncertainties'] as $field) if (!is_array($answer[$field]) || !array_is_list($answer[$field]) || array_filter($answer[$field], static fn($value): bool => !is_string($value) || '' === trim($value))) throw new \RuntimeException($c['errors'][6]);
    }

    private function read(string $p, string $e): array { if (!is_file($p)) throw new \RuntimeException($e); return json_decode((string) file_get_contents($p), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $r): bool { $digest = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($r))); }
    private function save(array $c, string $id, array $r): array
    {
        if (!is_dir($this->turns) && !mkdir($this->turns, 0770, true) && !is_dir($this->turns)) throw new \RuntimeException($c['errors'][8]);
        $r['record_digest'] = hash('sha256', CanonicalJson::encode($r)); $p = $this->turns.'/'.$id.'.json';
        if (is_file($p)) { $x = $this->read($p, $c['errors'][9]); if (CanonicalJson::encode($x) !== CanonicalJson::encode($r)) throw new \RuntimeException($c['errors'][9]); return $x; }
        if (false === file_put_contents($p, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException($c['errors'][8]);
        return $r;
    }
}
