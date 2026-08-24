<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionUsabilityTestimonyResponseService
{
    private string $dispatches; private string $questions; private string $candidates; private string $custody; private string $turns;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private ProfileExaminationTestimonyCognitionGateway $cognition,
    ) {
        $s = $root.'/var/imperium/offices/senate';
        $this->dispatches = $s.'/delegate-mission-profile-examination-question-dispatches';
        $this->questions = $s.'/delegate-mission-profile-examination-questions';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->turns = $s.'/delegate-mission-profile-examination-testimony-turns';
    }

    public function respond(string $dispatchId, \DateTimeImmutable $respondedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-question-dispatch-[a-f0-9]{20}$/', $dispatchId)) throw new \InvalidArgumentException('S710_DELEGATE_MISSION_QUESTION_DISPATCH_ID_INVALID');
        $d = $this->read($this->dispatches.'/'.$dispatchId.'.json', 'S711_DELEGATE_MISSION_QUESTION_DISPATCH_ABSENT');
        $questionId = $d['source_question']['id'] ?? '';
        $q = $this->read($this->questions.'/'.$questionId.'.json', 'S712_DELEGATE_MISSION_QUESTION_ABSENT');
        $candidateId = $d['source_profile_candidate']['id'] ?? '';
        $candidate = $this->read($this->candidates.'/'.$candidateId.'.json', 'S713_DELEGATE_MISSION_PROFILE_CANDIDATE_ABSENT');
        $custodyId = $d['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S714_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $securityTurnId = $d['source_prior_testimony_turn']['id'] ?? '';
        $securityTurn = $this->read($this->turns.'/'.$securityTurnId.'.json', 'S717_DELEGATE_MISSION_SECURITY_TESTIMONY_ABSENT');
        $trustTurnId = $d['source_earlier_testimony_turn']['id'] ?? '';
        $trustTurn = $this->read($this->turns.'/'.$trustTurnId.'.json', 'S717_DELEGATE_MISSION_TRUST_TESTIMONY_ABSENT');
        $this->validate($dispatchId, $d, $q, $candidate, $custody, $securityTurn, $trustTurn);
        foreach (glob($this->turns.'/*.json') ?: [] as $p) {
            $x = $this->read($p, 'S719_DELEGATE_MISSION_USABILITY_TESTIMONY_CONFLICT');
            if (($x['source_dispatch']['id'] ?? null) === $dispatchId) {
                if (($x['source_dispatch']['digest'] ?? null) === $d['record_digest']) return $x;
                throw new \RuntimeException('S719_DELEGATE_MISSION_USABILITY_TESTIMONY_CONFLICT');
            }
        }
        $answer = $this->cognition->answer($d, $d['manifestation']);
        $this->validateAnswer($answer);
        $id = 'delegate-mission-profile-examination-testimony-turn-'.substr(hash('sha256', CanonicalJson::encode([$dispatchId, $d['record_digest'], $answer])), 0, 20);
        $findingOpeningAuthority = [
            'authority_id' => 'delegate-mission-finding-phase-opening-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $trustTurn['record_digest'], $securityTurn['record_digest'], $d['hearing_contract']['subject']])), 0, 20),
            'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'senate.lord-speaker',
            'purpose' => 'OPEN_THREE_JURISDICTION_FINDING_AUTHORITIES',
            'consumed' => false, 'continuing_authority' => false,
        ];
        return $this->save($id, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-testimony-turn/v1', 'turn_id' => $id,
            'instance_id' => $d['instance_id'], 'officer_class' => $d['officer_class'],
            'source_dispatch' => ['id' => $dispatchId, 'digest' => $d['record_digest']], 'source_question' => $d['source_question'],
            'source_commission_disposition' => $d['source_commission_disposition'], 'source_commission' => $d['source_commission'],
            'source_prior_testimony_turn' => $d['source_prior_testimony_turn'],
            'source_earlier_testimony_turn' => $d['source_earlier_testimony_turn'],
            'source_examination_opening' => $d['source_examination_opening'], 'source_stand_admission' => $d['source_stand_admission'],
            'source_profile_candidate' => $d['source_profile_candidate'], 'source_reservation_disposition' => $d['source_reservation_disposition'],
            'custody_lease' => $d['custody_lease'], 'manifestation' => $d['manifestation'], 'hearing_contract' => $d['hearing_contract'],
            'usability_senator' => $d['usability_senator'], 'jurisdiction' => 'usability', 'question_sequence' => 3, 'question' => $d['question'], 'testimony' => $answer,
            'testimony_response_authority' => ['id' => $d['testimony_response_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'testimony_readiness' => [
                'jurisdictions' => ['trust', 'security', 'usability'],
                'turns' => [
                    ['jurisdiction' => 'trust', 'id' => $trustTurnId, 'digest' => $trustTurn['record_digest']],
                    ['jurisdiction' => 'security', 'id' => $securityTurnId, 'digest' => $securityTurn['record_digest']],
                    ['jurisdiction' => 'usability', 'id' => $id, 'digest_pending_record_seal' => true],
                ],
                'all_questions_dispatched_unchanged' => true,
                'all_responses_sealed' => true,
                'finding_authored' => false,
            ],
            'finding_phase_opening_authority' => $findingOpeningAuthority, 'next_question_commission_authority' => false,
            'responded_at' => $respondedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_USABILITY_TESTIMONY_RESPONSE_SEALED_PENDING_FINDING_AUTHORITY_OPENING',
            'question_dispatched_unchanged' => true, 'testimony_cognition_completed' => true, 'testimony_received' => true, 'testimony_response_sealed' => true,
            'findings_authority' => false, 'deliberation_authority' => false, 'profile_approval_authority' => false, 'profile_activation_authority' => false,
            'profile_installation_authority' => false, 'mission_seat_binding_authority' => false, 'deployment_authority' => false, 'operational_use_authority' => false,
            'provider_invocation_authority' => false, 'data_access_authority' => false, 'tool_use_authority' => false, 'credential_use_authority' => false,
            'perimeter_crossing_authority' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'mission_plan_amendment_authority' => false, 'follow_up_commission_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validate(string $dispatchId, array $d, array $q, array $candidate, array $custody, array $securityTurn, array $trustTurn): void
    {
        $a = $d['testimony_response_authority'] ?? null;
        if (!$this->valid($d) || !$this->valid($q) || !$this->valid($candidate) || !$this->valid($custody) || !$this->valid($securityTurn) || !$this->valid($trustTurn)
            || 'imperium.senate-delegate-mission-profile-examination-question-dispatch/v1' !== ($d['schema'] ?? null) || $dispatchId !== ($d['dispatch_id'] ?? null)
            || 'DELEGATE_MISSION_USABILITY_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE' !== ($d['status'] ?? null)
            || true !== ($d['question_dispatched'] ?? null) || true !== ($d['question_dispatched_unchanged'] ?? null) || false !== ($d['testimony_cognition_completed'] ?? null) || false !== ($d['testimony_received'] ?? null)
            || !is_array($a) || true !== ($a['authority_single_use'] ?? null) || true !== ($a['authority_exercisable'] ?? null) || false !== ($a['consumed'] ?? null)
            || 'ANSWER_ONE_DISPATCHED_USABILITY_EXAMINATION_QUESTION' !== ($a['purpose'] ?? null) || ($a['holder']['manifestation_id'] ?? null) !== ($d['manifestation']['manifestation_id'] ?? null)
            || ($d['source_question']['id'] ?? null) !== ($q['question_id'] ?? null) || ($d['source_question']['digest'] ?? null) !== ($q['record_digest'] ?? null)
            || CanonicalJson::encode($d['question'] ?? null) !== CanonicalJson::encode($q['question'] ?? null)
            || ($d['source_profile_candidate']['id'] ?? null) !== ($candidate['candidate_id'] ?? null) || ($d['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || ($d['manifestation']['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || ($d['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || ($d['source_prior_testimony_turn']['id'] ?? null) !== ($securityTurn['turn_id'] ?? null) || ($d['source_prior_testimony_turn']['digest'] ?? null) !== ($securityTurn['record_digest'] ?? null)
            || 'security' !== ($securityTurn['jurisdiction'] ?? null) || 2 !== ($securityTurn['question_sequence'] ?? null) || true !== ($securityTurn['testimony_response_sealed'] ?? null)
            || ($d['source_earlier_testimony_turn']['id'] ?? null) !== ($trustTurn['turn_id'] ?? null) || ($d['source_earlier_testimony_turn']['digest'] ?? null) !== ($trustTurn['record_digest'] ?? null)
            || 'trust' !== ($trustTurn['jurisdiction'] ?? null) || 1 !== ($trustTurn['question_sequence'] ?? null) || true !== ($trustTurn['testimony_response_sealed'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($d['manifestation']['purpose'] ?? null) || false !== ($d['manifestation']['operational_use_permitted'] ?? null)
            || true !== ($d['sealed'] ?? null)) throw new \RuntimeException('S715_DELEGATE_MISSION_USABILITY_TESTIMONY_CHAIN_INVALID');
    }
    private function validateAnswer(array $a): void
    {
        $keys = array_keys($a); sort($keys, SORT_STRING);
        if (['answer', 'evidence_claims', 'refusals', 'uncertainties'] !== $keys || !is_string($a['answer']) || '' === trim($a['answer'])) throw new \RuntimeException('S716_DELEGATE_MISSION_USABILITY_TESTIMONY_COGNITION_INVALID');
        foreach (['evidence_claims', 'refusals', 'uncertainties'] as $f) if (!is_array($a[$f]) || !array_is_list($a[$f]) || array_filter($a[$f], static fn($v): bool => !is_string($v) || '' === trim($v))) throw new \RuntimeException('S716_DELEGATE_MISSION_USABILITY_TESTIMONY_COGNITION_INVALID');
    }
    private function read(string $p, string $e): array { if (!is_file($p)) throw new \RuntimeException($e); return json_decode((string) file_get_contents($p), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $r): bool { $d = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($d) && hash_equals($d, hash('sha256', CanonicalJson::encode($r))); }
    private function save(string $id, array $r): array
    {
        if (!is_dir($this->turns) && !mkdir($this->turns, 0770, true) && !is_dir($this->turns)) throw new \RuntimeException('S718_DELEGATE_MISSION_USABILITY_TESTIMONY_PERSISTENCE_FAILED');
        $r['record_digest'] = hash('sha256', CanonicalJson::encode($r)); $p = $this->turns.'/'.$id.'.json';
        if (is_file($p)) { $x = $this->read($p, 'S719_DELEGATE_MISSION_USABILITY_TESTIMONY_CONFLICT'); if (CanonicalJson::encode($x) !== CanonicalJson::encode($r)) throw new \RuntimeException('S719_DELEGATE_MISSION_USABILITY_TESTIMONY_CONFLICT'); return $x; }
        if (false === file_put_contents($p, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S718_DELEGATE_MISSION_USABILITY_TESTIMONY_PERSISTENCE_FAILED');
        return $r;
    }
}
