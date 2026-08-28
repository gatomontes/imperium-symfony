<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionQuestionDispatchEngine
{
    private string $decisions;
    private string $questions;
    private string $custody;
    private string $occupancy;
    private string $dispatches;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->decisions = $senate.'/delegate-mission-profile-examination-question-dispatch-decisions';
        $this->questions = $senate.'/delegate-mission-profile-examination-questions';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $senate.'/occupancy';
        $this->dispatches = $senate.'/delegate-mission-profile-examination-question-dispatches';
    }

    public function dispatch(string $jurisdiction, string $decisionId, string $bailiffBindingId, \DateTimeImmutable $dispatchedAt): array
    {
        $c = $this->configuration($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-question-dispatch-decision-[a-f0-9]{20}$/', $decisionId)) throw new \InvalidArgumentException($c['errors'][0]);
        if (!preg_match('/^senate-bailiff-binding-[a-f0-9]{20}$/', $bailiffBindingId)) throw new \InvalidArgumentException($c['errors'][1]);
        $decision = $this->read($this->decisions.'/'.$decisionId.'.json', $c['errors'][2]);
        $authorityId = (string) ($decision['question_dispatch_authority']['authority_id'] ?? $decisionId);

        return DelegateMissionSenateAuthorityTransition::run($this->dispatches, $authorityId, fn (): array => $this->dispatchWhileLocked($jurisdiction, $decisionId, $bailiffBindingId, $dispatchedAt));
    }

    private function dispatchWhileLocked(string $jurisdiction, string $decisionId, string $bailiffBindingId, \DateTimeImmutable $dispatchedAt): array
    {
        $c = $this->configuration($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-question-dispatch-decision-[a-f0-9]{20}$/', $decisionId)) throw new \InvalidArgumentException($c['errors'][0]);
        if (!preg_match('/^senate-bailiff-binding-[a-f0-9]{20}$/', $bailiffBindingId)) throw new \InvalidArgumentException($c['errors'][1]);
        $d = $this->read($this->decisions.'/'.$decisionId.'.json', $c['errors'][2]);
        $q = $this->read($this->questions.'/'.($d['source_question']['id'] ?? '').'.json', $c['errors'][3]);
        $custody = $this->read($this->custody.'/'.($d['custody_lease']['custody_id'] ?? '').'.json', $c['errors'][4]);
        $bailiff = $this->read($this->occupancy.'/'.$bailiffBindingId.'.json', $c['errors'][5]);
        $this->validate($c, $decisionId, $bailiffBindingId, $d, $q, $custody, $bailiff);

        foreach (glob($this->dispatches.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, $c['errors'][9]);
            if (($prior['source_dispatch_decision']['id'] ?? null) === $decisionId) {
                if (($prior['source_dispatch_decision']['digest'] ?? null) === $d['record_digest'] && ($prior['bailiff']['binding_id'] ?? null) === $bailiffBindingId) return $prior;
                throw new \RuntimeException($c['errors'][9]);
            }
        }

        $actor = ['seat' => 'senate.bailiff', 'binding_id' => $bailiffBindingId, 'binding_digest' => $bailiff['record_digest'], 'manifestation_id' => $bailiff['manifestation_id'], 'occupancy_generation' => $bailiff['occupancy_generation']];
        $id = 'delegate-mission-profile-examination-question-dispatch-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $d['record_digest'], $actor, $d['question']])), 0, 20);
        $responseAuthority = ['authority_id' => 'delegate-mission-'.$jurisdiction.'-testimony-response-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $d['manifestation']['manifestation_id'], $d['question']])), 0, 20), 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => ['manifestation_id' => $d['manifestation']['manifestation_id'], 'purpose' => 'SENATE_EXAMINATION_ONLY'], 'purpose' => 'ANSWER_ONE_DISPATCHED_'.$c['upper'].'_EXAMINATION_QUESTION', 'consumed' => false, 'continuing_authority' => false];
        $record = ['schema' => 'imperium.senate-delegate-mission-profile-examination-question-dispatch/v1', 'dispatch_id' => $id, 'instance_id' => $d['instance_id'], 'officer_class' => $d['officer_class'], 'bailiff' => $actor, 'source_dispatch_decision' => ['id' => $decisionId, 'digest' => $d['record_digest']], 'source_question' => $d['source_question'], 'source_commission_disposition' => $d['source_commission_disposition'], 'source_commission' => $d['source_commission']];
        if ('trust' !== $jurisdiction) $record['source_prior_testimony_turn'] = $d['source_prior_testimony_turn'];
        if ('usability' === $jurisdiction) $record['source_earlier_testimony_turn'] = $d['source_earlier_testimony_turn'];
        $record += ['source_examination_opening' => $d['source_examination_opening'], 'source_stand_admission' => $d['source_stand_admission'], 'source_profile_candidate' => $d['source_profile_candidate'], 'source_reservation_disposition' => $d['source_reservation_disposition'], 'custody_lease' => $d['custody_lease'], 'manifestation' => $d['manifestation'], 'hearing_contract' => $d['hearing_contract'], $c['senator_key'] => $d[$c['senator_key']], 'jurisdiction' => $jurisdiction, 'question_sequence' => $c['sequence'], 'question' => $d['question'], 'question_dispatch_authority' => ['id' => $d['question_dispatch_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false], 'testimony_response_authority' => $responseAuthority, 'dispatched_at' => $dispatchedAt->format(DATE_ATOM), 'status' => 'DELEGATE_MISSION_'.$c['upper'].'_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE', 'question_dispatched' => true, 'question_dispatched_unchanged' => true, 'testimony_cognition_completed' => false, 'testimony_received' => false];
        foreach (['findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) $record[$field] = false;
        $record['sealed'] = true;
        return $this->save($c, $id, $record);
    }

    private function configuration(string $jurisdiction): array
    {
        [$base, $sequence] = match ($jurisdiction) { 'trust' => [58, 1], 'security' => [64, 2], 'usability' => [70, 3], default => throw new \InvalidArgumentException('S794_DELEGATE_MISSION_QUESTION_DISPATCH_JURISDICTION_INVALID') };
        $upper = strtoupper($jurisdiction); $prefix = 'S'.$base;
        return ['upper' => $upper, 'sequence' => $sequence, 'senator_key' => $jurisdiction.'_senator', 'authorized_status' => 'DELEGATE_MISSION_'.$upper.'_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH', 'dispatch_purpose' => 'DISPATCH_ONE_SEALED_'.$upper.'_EXAMINATION_QUESTION_UNCHANGED', 'errors' => [$prefix.'0_DELEGATE_MISSION_DISPATCH_DECISION_ID_INVALID', $prefix.'1_DELEGATE_MISSION_BAILIFF_BINDING_ID_INVALID', $prefix.'2_DELEGATE_MISSION_DISPATCH_DECISION_ABSENT', $prefix.'3_DELEGATE_MISSION_QUESTION_ABSENT', $prefix.'4_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT', $prefix.'5_DELEGATE_MISSION_BAILIFF_UNAVAILABLE', $prefix.'6_DELEGATE_MISSION_QUESTION_DISPATCH_CHAIN_INVALID', $prefix.'7_DELEGATE_MISSION_QUESTION_DISPATCH_PERSISTENCE_FAILED', $prefix.'8_DELEGATE_MISSION_QUESTION_DISPATCH_RESERVED', $prefix.'9_DELEGATE_MISSION_QUESTION_DISPATCH_CONFLICT']];
    }

    private function validate(array $c, string $decisionId, string $bindingId, array $d, array $q, array $custody, array $b): void
    {
        $a = $d['question_dispatch_authority'] ?? null;
        if (!$this->valid($d) || !$this->valid($q) || !$this->valid($custody) || !$this->valid($b) || 'imperium.senate-delegate-mission-profile-examination-question-dispatch-decision/v1' !== ($d['schema'] ?? null) || $decisionId !== ($d['decision_id'] ?? null) || 'AUTHORIZED' !== ($d['disposition'] ?? null) || $c['authorized_status'] !== ($d['status'] ?? null) || !is_array($a) || true !== ($a['authority_single_use'] ?? null) || true !== ($a['authority_exercisable'] ?? null) || false !== ($a['consumed'] ?? null) || $c['dispatch_purpose'] !== ($a['purpose'] ?? null) || ($a['holder']['binding_id'] ?? null) !== $bindingId || ($a['holder']['binding_digest'] ?? null) !== ($b['record_digest'] ?? null) || ($d['source_question']['id'] ?? null) !== ($q['question_id'] ?? null) || ($d['source_question']['digest'] ?? null) !== ($q['record_digest'] ?? null) || CanonicalJson::encode($d['question'] ?? null) !== CanonicalJson::encode($q['question'] ?? null) || ($d['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null) || ($d['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null) || 'senate.bailiff' !== ($b['seat'] ?? null) || 'ACTIVE' !== ($b['status'] ?? null) || true !== ($b['binding_atomic'] ?? null) || true !== ($b['delegate_examination_question_dispatch_authority'] ?? null) || true === ($b['execution_authority'] ?? null) || ($d['instance_id'] ?? null) !== ($b['instance_id'] ?? null) || false !== ($d['question_dispatched'] ?? null) || true !== ($d['sealed'] ?? null)) throw new \RuntimeException($c['errors'][6]);
    }

    private function read(string $p, string $e): array { if (!is_file($p)) throw new \RuntimeException($e); return json_decode((string) file_get_contents($p), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $r): bool { if (!DelegateMissionSenateAuthorityTransition::isExactOrHistorical($r)) return false; $digest = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($r))); }
    private function save(array $c, string $id, array $r): array
    {
        return DelegateMissionSenateAuthorityTransition::put($this->dispatches, $id, $r, self::class, $c['errors'][7], $c['errors'][9]);
    }
}
