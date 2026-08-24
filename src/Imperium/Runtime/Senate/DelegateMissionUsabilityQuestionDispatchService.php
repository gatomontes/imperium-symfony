<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionUsabilityQuestionDispatchService
{
    private string $decisions; private string $questions; private string $custody; private string $occupancy; private string $dispatches;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $s = $root.'/var/imperium/offices/senate';
        $this->decisions = $s.'/delegate-mission-profile-examination-question-dispatch-decisions';
        $this->questions = $s.'/delegate-mission-profile-examination-questions';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $s.'/occupancy';
        $this->dispatches = $s.'/delegate-mission-profile-examination-question-dispatches';
    }

    public function dispatch(string $decisionId, string $bailiffBindingId, \DateTimeImmutable $dispatchedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-question-dispatch-decision-[a-f0-9]{20}$/', $decisionId)) throw new \InvalidArgumentException('S700_DELEGATE_MISSION_DISPATCH_DECISION_ID_INVALID');
        if (!preg_match('/^senate-bailiff-binding-[a-f0-9]{20}$/', $bailiffBindingId)) throw new \InvalidArgumentException('S701_DELEGATE_MISSION_BAILIFF_BINDING_ID_INVALID');
        $d = $this->read($this->decisions.'/'.$decisionId.'.json', 'S702_DELEGATE_MISSION_DISPATCH_DECISION_ABSENT');
        $questionId = $d['source_question']['id'] ?? '';
        $q = $this->read($this->questions.'/'.$questionId.'.json', 'S703_DELEGATE_MISSION_QUESTION_ABSENT');
        $custodyId = $d['custody_lease']['custody_id'] ?? '';
        $c = $this->read($this->custody.'/'.$custodyId.'.json', 'S704_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $b = $this->read($this->occupancy.'/'.$bailiffBindingId.'.json', 'S705_DELEGATE_MISSION_BAILIFF_UNAVAILABLE');
        $this->validate($decisionId, $bailiffBindingId, $d, $q, $c, $b);
        foreach (glob($this->dispatches.'/*.json') ?: [] as $p) {
            $x = $this->read($p, 'S709_DELEGATE_MISSION_QUESTION_DISPATCH_CONFLICT');
            if (($x['source_dispatch_decision']['id'] ?? null) === $decisionId) {
                if (($x['source_dispatch_decision']['digest'] ?? null) === $d['record_digest'] && ($x['bailiff']['binding_id'] ?? null) === $bailiffBindingId) return $x;
                throw new \RuntimeException('S709_DELEGATE_MISSION_QUESTION_DISPATCH_CONFLICT');
            }
        }
        $actor = ['seat' => 'senate.bailiff', 'binding_id' => $bailiffBindingId, 'binding_digest' => $b['record_digest'], 'manifestation_id' => $b['manifestation_id'], 'occupancy_generation' => $b['occupancy_generation']];
        $id = 'delegate-mission-profile-examination-question-dispatch-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $d['record_digest'], $actor, $d['question']])), 0, 20);
        $responseAuthority = [
            'authority_id' => 'delegate-mission-usability-testimony-response-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $d['manifestation']['manifestation_id'], $d['question']])), 0, 20),
            'authority_single_use' => true, 'authority_exercisable' => true,
            'holder' => ['manifestation_id' => $d['manifestation']['manifestation_id'], 'purpose' => 'SENATE_EXAMINATION_ONLY'],
            'purpose' => 'ANSWER_ONE_DISPATCHED_USABILITY_EXAMINATION_QUESTION',
            'consumed' => false, 'continuing_authority' => false,
        ];
        return $this->save($id, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-dispatch/v1', 'dispatch_id' => $id,
            'instance_id' => $d['instance_id'], 'officer_class' => $d['officer_class'], 'bailiff' => $actor,
            'source_dispatch_decision' => ['id' => $decisionId, 'digest' => $d['record_digest']], 'source_question' => $d['source_question'],
            'source_commission_disposition' => $d['source_commission_disposition'], 'source_commission' => $d['source_commission'],
            'source_prior_testimony_turn' => $d['source_prior_testimony_turn'],
            'source_earlier_testimony_turn' => $d['source_earlier_testimony_turn'],
            'source_examination_opening' => $d['source_examination_opening'], 'source_stand_admission' => $d['source_stand_admission'],
            'source_profile_candidate' => $d['source_profile_candidate'], 'source_reservation_disposition' => $d['source_reservation_disposition'],
            'custody_lease' => $d['custody_lease'], 'manifestation' => $d['manifestation'], 'hearing_contract' => $d['hearing_contract'],
            'usability_senator' => $d['usability_senator'], 'jurisdiction' => 'usability', 'question_sequence' => 3, 'question' => $d['question'],
            'question_dispatch_authority' => ['id' => $d['question_dispatch_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'testimony_response_authority' => $responseAuthority, 'dispatched_at' => $dispatchedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_USABILITY_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE',
            'question_dispatched' => true, 'question_dispatched_unchanged' => true, 'testimony_cognition_completed' => false, 'testimony_received' => false,
            'findings_authority' => false, 'profile_approval_authority' => false, 'profile_activation_authority' => false, 'profile_installation_authority' => false,
            'mission_seat_binding_authority' => false, 'deployment_authority' => false, 'operational_use_authority' => false,
            'provider_invocation_authority' => false, 'data_access_authority' => false, 'tool_use_authority' => false, 'credential_use_authority' => false,
            'perimeter_crossing_authority' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'mission_plan_amendment_authority' => false, 'follow_up_commission_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validate(string $decisionId, string $bindingId, array $d, array $q, array $c, array $b): void
    {
        $a = $d['question_dispatch_authority'] ?? null;
        if (!$this->valid($d) || !$this->valid($q) || !$this->valid($c) || !$this->valid($b)
            || 'imperium.senate-delegate-mission-profile-examination-question-dispatch-decision/v1' !== ($d['schema'] ?? null) || $decisionId !== ($d['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($d['disposition'] ?? null) || 'DELEGATE_MISSION_USABILITY_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH' !== ($d['status'] ?? null)
            || !is_array($a) || true !== ($a['authority_single_use'] ?? null) || true !== ($a['authority_exercisable'] ?? null) || false !== ($a['consumed'] ?? null)
            || 'DISPATCH_ONE_SEALED_USABILITY_EXAMINATION_QUESTION_UNCHANGED' !== ($a['purpose'] ?? null)
            || ($a['holder']['binding_id'] ?? null) !== $bindingId || ($a['holder']['binding_digest'] ?? null) !== ($b['record_digest'] ?? null)
            || ($d['source_question']['id'] ?? null) !== ($q['question_id'] ?? null) || ($d['source_question']['digest'] ?? null) !== ($q['record_digest'] ?? null)
            || CanonicalJson::encode($d['question'] ?? null) !== CanonicalJson::encode($q['question'] ?? null)
            || ($d['custody_lease']['custody_digest'] ?? null) !== ($c['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($c['custody_state'] ?? null) || true !== ($c['available'] ?? null)
            || 'senate.bailiff' !== ($b['seat'] ?? null) || 'ACTIVE' !== ($b['status'] ?? null) || true !== ($b['binding_atomic'] ?? null)
            || true !== ($b['delegate_examination_question_dispatch_authority'] ?? null) || true === ($b['execution_authority'] ?? null)
            || ($d['instance_id'] ?? null) !== ($b['instance_id'] ?? null) || false !== ($d['question_dispatched'] ?? null) || true !== ($d['sealed'] ?? null))
            throw new \RuntimeException('S706_DELEGATE_MISSION_QUESTION_DISPATCH_CHAIN_INVALID');
    }
    private function read(string $p, string $e): array { if (!is_file($p)) throw new \RuntimeException($e); return json_decode((string) file_get_contents($p), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $r): bool { $d = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($d) && hash_equals($d, hash('sha256', CanonicalJson::encode($r))); }
    private function save(string $id, array $r): array
    {
        if (!is_dir($this->dispatches) && !mkdir($this->dispatches, 0770, true) && !is_dir($this->dispatches)) throw new \RuntimeException('S707_DELEGATE_MISSION_QUESTION_DISPATCH_PERSISTENCE_FAILED');
        $r['record_digest'] = hash('sha256', CanonicalJson::encode($r)); $p = $this->dispatches.'/'.$id.'.json';
        if (is_file($p)) { $x = $this->read($p, 'S709_DELEGATE_MISSION_QUESTION_DISPATCH_CONFLICT'); if (CanonicalJson::encode($x) !== CanonicalJson::encode($r)) throw new \RuntimeException('S709_DELEGATE_MISSION_QUESTION_DISPATCH_CONFLICT'); return $x; }
        if (false === file_put_contents($p, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S707_DELEGATE_MISSION_QUESTION_DISPATCH_PERSISTENCE_FAILED');
        return $r;
    }
}
