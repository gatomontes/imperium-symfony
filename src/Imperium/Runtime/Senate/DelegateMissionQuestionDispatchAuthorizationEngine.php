<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionQuestionDispatchAuthorizationEngine
{
    private string $questions;
    private string $admissions;
    private string $occupancy;
    private string $decisions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->questions = $senate.'/delegate-mission-profile-examination-questions';
        $this->admissions = $senate.'/delegate-mission-examination-stand-admission-dispositions';
        $this->occupancy = $senate.'/occupancy';
        $this->decisions = $senate.'/delegate-mission-profile-examination-question-dispatch-decisions';
    }

    public function decide(string $jurisdiction, string $questionId, string $lordSpeakerBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        $c = $this->configuration($jurisdiction);
        if (!preg_match('/^delegate-mission-profile-examination-question-[a-f0-9]{20}$/', $questionId)) throw new \InvalidArgumentException($c['errors'][0]);
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $lordSpeakerBindingId)) throw new \InvalidArgumentException($c['errors'][1]);
        $disposition = strtoupper(trim($disposition)); $rationale = trim($rationale);
        if (!in_array($disposition, ['AUTHORIZED', 'REFUSED'], true) || '' === $rationale) throw new \InvalidArgumentException($c['errors'][2]);

        $question = $this->read($this->questions.'/'.$questionId.'.json', $c['errors'][3]);
        $admission = $this->read($this->admissions.'/'.($question['source_stand_admission']['id'] ?? '').'.json', $c['errors'][4]);
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', $c['errors'][5]);
        $bailiffId = $admission['bailiff']['binding_id'] ?? '';
        $bailiff = $this->read($this->occupancy.'/'.$bailiffId.'.json', $c['errors'][6]);
        $this->validate($c, $questionId, $lordSpeakerBindingId, $question, $admission, $lordSpeaker, $bailiff);

        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, $c['errors'][9]);
            if (($prior['source_question']['id'] ?? null) === $questionId) {
                if (($prior['source_question']['digest'] ?? null) === $question['record_digest'] && ($prior['lord_speaker']['binding_id'] ?? null) === $lordSpeakerBindingId && ($prior['disposition'] ?? null) === $disposition && ($prior['rationale'] ?? null) === $rationale) return $prior;
                throw new \RuntimeException($c['errors'][9]);
            }
        }

        $authorized = 'AUTHORIZED' === $disposition;
        $actor = ['seat' => 'senate.lord-speaker', 'binding_id' => $lordSpeakerBindingId, 'binding_digest' => $lordSpeaker['record_digest'], 'manifestation_id' => $lordSpeaker['manifestation_id'], 'occupancy_generation' => $lordSpeaker['occupancy_generation']];
        $id = 'delegate-mission-profile-examination-question-dispatch-decision-'.substr(hash('sha256', CanonicalJson::encode([$questionId, $question['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $dispatchAuthority = $authorized ? ['authority_id' => 'delegate-mission-question-dispatch-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $bailiffId, $question['question']])), 0, 20), 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => ['seat' => 'senate.bailiff', 'binding_id' => $bailiffId, 'binding_digest' => $bailiff['record_digest']], 'purpose' => 'DISPATCH_ONE_SEALED_'.$c['upper'].'_EXAMINATION_QUESTION_UNCHANGED', 'consumed' => false, 'continuing_authority' => false] : null;

        $record = ['schema' => 'imperium.senate-delegate-mission-profile-examination-question-dispatch-decision/v1', 'decision_id' => $id, 'instance_id' => $question['instance_id'], 'officer_class' => $question['officer_class'], 'lord_speaker' => $actor, 'source_question' => ['id' => $questionId, 'digest' => $question['record_digest']], 'source_commission_disposition' => $question['source_commission_disposition'], 'source_commission' => $question['source_commission']];
        if ('trust' !== $jurisdiction) $record['source_prior_testimony_turn'] = $question['source_prior_testimony_turn'];
        if ('usability' === $jurisdiction) $record['source_earlier_testimony_turn'] = $question['source_earlier_testimony_turn'];
        $record += ['source_examination_opening' => $question['source_examination_opening'], 'source_stand_admission' => $question['source_stand_admission'], 'source_profile_candidate' => $question['source_profile_candidate'], 'source_reservation_disposition' => $question['source_reservation_disposition'], 'custody_lease' => $question['custody_lease'], 'manifestation' => $question['manifestation'], 'hearing_contract' => $question['hearing_contract'], $c['senator_key'] => $question[$c['senator_key']], 'jurisdiction' => $jurisdiction, 'question_sequence' => $c['sequence'], 'question' => $question['question'], 'question_dispatch_authorization_authority' => ['id' => $question['question_dispatch_authorization_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false], 'disposition' => $disposition, 'rationale' => $rationale, 'decided_at' => $decidedAt->format(DATE_ATOM), 'question_dispatch_authority' => $dispatchAuthority, 'status' => $authorized ? 'DELEGATE_MISSION_'.$c['upper'].'_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH' : 'DELEGATE_MISSION_'.$c['upper'].'_QUESTION_DISPATCH_REFUSED_NO_TESTIMONY_AUTHORITY'];
        foreach (['question_dispatched', 'testimony_authority', 'testimony_received', 'findings_authority', 'profile_approval_authority', 'profile_activation_authority', 'profile_installation_authority', 'manifestation_assembly_authority', 'mission_seat_binding_authority', 'deployment_authority', 'operational_use_authority', 'provider_invocation_authority', 'data_access_authority', 'tool_use_authority', 'credential_use_authority', 'perimeter_crossing_authority', 'external_action_authority', 'execution_authority', 'mission_plan_amendment_authority', 'follow_up_commission_authority', 'continuing_turn_authority'] as $field) $record[$field] = false;
        $record['sealed'] = true;
        return $this->save($c, $id, $record);
    }

    private function configuration(string $jurisdiction): array
    {
        [$base, $sequence] = match ($jurisdiction) { 'trust' => [57, 1], 'security' => [63, 2], 'usability' => [69, 3], default => throw new \InvalidArgumentException('S703_DELEGATE_MISSION_QUESTION_DISPATCH_AUTHORIZATION_JURISDICTION_INVALID') };
        $upper = strtoupper($jurisdiction); $prefix = 'S'.$base;
        return ['jurisdiction' => $jurisdiction, 'upper' => $upper, 'sequence' => $sequence, 'senator_key' => $jurisdiction.'_senator', 'question_status' => 'DELEGATE_MISSION_'.$upper.'_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION', 'decision_purpose' => 'DECIDE_DISPATCH_OF_ONE_SEALED_'.$upper.'_EXAMINATION_QUESTION', 'errors' => [$prefix.'0_DELEGATE_MISSION_QUESTION_ID_INVALID', $prefix.'1_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID', $prefix.'2_DELEGATE_MISSION_QUESTION_DISPATCH_DISPOSITION_INVALID', $prefix.'3_DELEGATE_MISSION_QUESTION_ABSENT', $prefix.'4_DELEGATE_MISSION_STAND_ADMISSION_ABSENT', $prefix.'5_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE', $prefix.'6_DELEGATE_MISSION_BAILIFF_UNAVAILABLE', $prefix.'7_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CHAIN_INVALID', $prefix.'8_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_PERSISTENCE_FAILED', $prefix.'9_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CONFLICT']];
    }

    private function validate(array $c, string $questionId, string $bindingId, array $q, array $a, array $l, array $b): void
    {
        $authority = $q['question_dispatch_authorization_authority'] ?? null;
        if (!$this->valid($q) || !$this->valid($a) || !$this->valid($l) || !$this->valid($b) || 'imperium.senate-delegate-mission-profile-examination-question/v1' !== ($q['schema'] ?? null) || $questionId !== ($q['question_id'] ?? null) || $c['question_status'] !== ($q['status'] ?? null) || true !== ($q['question_cognition_completed'] ?? null) || true !== ($q['question_authored'] ?? null) || false !== ($q['question_dispatched'] ?? null) || !is_array($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null) || 'senate.lord-speaker' !== ($authority['holder'] ?? null) || $c['decision_purpose'] !== ($authority['purpose'] ?? null) || false !== ($authority['consumed'] ?? null) || ($a['record_digest'] ?? null) !== ($q['source_stand_admission']['digest'] ?? null) || true !== ($a['proceeding_security_active'] ?? null) || ($l['binding_id'] ?? null) !== $bindingId || 'senate.lord-speaker' !== ($l['seat'] ?? null) || 'ACTIVE' !== ($l['status'] ?? null) || true !== ($l['binding_atomic'] ?? null) || true !== ($l['delegate_question_dispatch_authorization_disposition_authority'] ?? null) || true === ($l['execution_authority'] ?? null) || ($a['bailiff']['binding_id'] ?? null) !== ($b['binding_id'] ?? null) || ($a['bailiff']['binding_digest'] ?? null) !== ($b['record_digest'] ?? null) || 'senate.bailiff' !== ($b['seat'] ?? null) || 'ACTIVE' !== ($b['status'] ?? null) || true !== ($b['binding_atomic'] ?? null) || true !== ($b['delegate_examination_question_dispatch_authority'] ?? null) || true === ($b['execution_authority'] ?? null) || ($q['instance_id'] ?? null) !== ($l['instance_id'] ?? null) || ($q['instance_id'] ?? null) !== ($b['instance_id'] ?? null) || true !== ($q['sealed'] ?? null)) throw new \RuntimeException($c['errors'][7]);
    }

    private function read(string $p, string $e): array { if (!is_file($p)) throw new \RuntimeException($e); return json_decode((string) file_get_contents($p), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $r): bool { $d = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($d) && hash_equals($d, hash('sha256', CanonicalJson::encode($r))); }
    private function save(array $c, string $id, array $r): array
    {
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) throw new \RuntimeException($c['errors'][8]);
        $r['record_digest'] = hash('sha256', CanonicalJson::encode($r)); $p = $this->decisions.'/'.$id.'.json';
        if (is_file($p)) { $x = $this->read($p, $c['errors'][9]); if (CanonicalJson::encode($x) !== CanonicalJson::encode($r)) throw new \RuntimeException($c['errors'][9]); return $x; }
        if (false === file_put_contents($p, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException($c['errors'][8]);
        return $r;
    }
}
