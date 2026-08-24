<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionUsabilityQuestionDispatchAuthorizationService
{
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
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

    public function decide(string $questionId, string $lordSpeakerBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-question-[a-f0-9]{20}$/', $questionId)) throw new \InvalidArgumentException('S690_DELEGATE_MISSION_QUESTION_ID_INVALID');
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $lordSpeakerBindingId)) throw new \InvalidArgumentException('S691_DELEGATE_MISSION_LORD_SPEAKER_BINDING_ID_INVALID');
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) throw new \InvalidArgumentException('S692_DELEGATE_MISSION_QUESTION_DISPATCH_DISPOSITION_INVALID');

        $question = $this->read($this->questions.'/'.$questionId.'.json', 'S693_DELEGATE_MISSION_QUESTION_ABSENT');
        $admissionId = $question['source_stand_admission']['id'] ?? '';
        $admission = $this->read($this->admissions.'/'.$admissionId.'.json', 'S694_DELEGATE_MISSION_STAND_ADMISSION_ABSENT');
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S695_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE');
        $bailiffId = $admission['bailiff']['binding_id'] ?? '';
        $bailiff = $this->read($this->occupancy.'/'.$bailiffId.'.json', 'S696_DELEGATE_MISSION_BAILIFF_UNAVAILABLE');
        $this->validate($questionId, $lordSpeakerBindingId, $question, $admission, $lordSpeaker, $bailiff);

        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S699_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CONFLICT');
            if (($prior['source_question']['id'] ?? null) === $questionId) {
                if (($prior['source_question']['digest'] ?? null) === $question['record_digest'] && ($prior['lord_speaker']['binding_id'] ?? null) === $lordSpeakerBindingId && ($prior['disposition'] ?? null) === $disposition && ($prior['rationale'] ?? null) === $rationale) return $prior;
                throw new \RuntimeException('S699_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CONFLICT');
            }
        }

        $authorized = 'AUTHORIZED' === $disposition;
        $actor = ['seat' => 'senate.lord-speaker', 'binding_id' => $lordSpeakerBindingId, 'binding_digest' => $lordSpeaker['record_digest'], 'manifestation_id' => $lordSpeaker['manifestation_id'], 'occupancy_generation' => $lordSpeaker['occupancy_generation']];
        $id = 'delegate-mission-profile-examination-question-dispatch-decision-'.substr(hash('sha256', CanonicalJson::encode([$questionId, $question['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $dispatchAuthority = $authorized ? [
            'authority_id' => 'delegate-mission-question-dispatch-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $bailiffId, $question['question']])), 0, 20),
            'authority_single_use' => true, 'authority_exercisable' => true,
            'holder' => ['seat' => 'senate.bailiff', 'binding_id' => $bailiffId, 'binding_digest' => $bailiff['record_digest']],
            'purpose' => 'DISPATCH_ONE_SEALED_USABILITY_EXAMINATION_QUESTION_UNCHANGED',
            'consumed' => false, 'continuing_authority' => false,
        ] : null;

        return $this->save($id, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-question-dispatch-decision/v1',
            'decision_id' => $id, 'instance_id' => $question['instance_id'], 'officer_class' => $question['officer_class'],
            'lord_speaker' => $actor,
            'source_question' => ['id' => $questionId, 'digest' => $question['record_digest']],
            'source_commission_disposition' => $question['source_commission_disposition'], 'source_commission' => $question['source_commission'],
            'source_prior_testimony_turn' => $question['source_prior_testimony_turn'],
            'source_earlier_testimony_turn' => $question['source_earlier_testimony_turn'],
            'source_examination_opening' => $question['source_examination_opening'], 'source_stand_admission' => $question['source_stand_admission'],
            'source_profile_candidate' => $question['source_profile_candidate'], 'source_reservation_disposition' => $question['source_reservation_disposition'],
            'custody_lease' => $question['custody_lease'], 'manifestation' => $question['manifestation'], 'hearing_contract' => $question['hearing_contract'],
            'usability_senator' => $question['usability_senator'], 'jurisdiction' => 'usability', 'question_sequence' => 3, 'question' => $question['question'],
            'question_dispatch_authorization_authority' => ['id' => $question['question_dispatch_authorization_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'disposition' => $disposition, 'rationale' => $rationale, 'decided_at' => $decidedAt->format(DATE_ATOM),
            'question_dispatch_authority' => $dispatchAuthority,
            'status' => $authorized ? 'DELEGATE_MISSION_USABILITY_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH' : 'DELEGATE_MISSION_USABILITY_QUESTION_DISPATCH_REFUSED_NO_TESTIMONY_AUTHORITY',
            'question_dispatched' => false, 'testimony_authority' => false, 'testimony_received' => false, 'findings_authority' => false,
            'profile_approval_authority' => false, 'profile_activation_authority' => false, 'profile_installation_authority' => false,
            'manifestation_assembly_authority' => false, 'mission_seat_binding_authority' => false, 'deployment_authority' => false,
            'operational_use_authority' => false, 'provider_invocation_authority' => false, 'data_access_authority' => false, 'tool_use_authority' => false,
            'credential_use_authority' => false, 'perimeter_crossing_authority' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'mission_plan_amendment_authority' => false, 'follow_up_commission_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validate(string $questionId, string $bindingId, array $q, array $a, array $l, array $b): void
    {
        $authority = $q['question_dispatch_authorization_authority'] ?? null;
        if (!$this->valid($q) || !$this->valid($a) || !$this->valid($l) || !$this->valid($b)
            || 'imperium.senate-delegate-mission-profile-examination-question/v1' !== ($q['schema'] ?? null) || $questionId !== ($q['question_id'] ?? null)
            || 'DELEGATE_MISSION_USABILITY_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION' !== ($q['status'] ?? null)
            || true !== ($q['question_cognition_completed'] ?? null) || true !== ($q['question_authored'] ?? null) || false !== ($q['question_dispatched'] ?? null)
            || !is_array($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null) || 'DECIDE_DISPATCH_OF_ONE_SEALED_USABILITY_EXAMINATION_QUESTION' !== ($authority['purpose'] ?? null) || false !== ($authority['consumed'] ?? null)
            || ($a['record_digest'] ?? null) !== ($q['source_stand_admission']['digest'] ?? null) || true !== ($a['proceeding_usability_active'] ?? null)
            || ($l['binding_id'] ?? null) !== $bindingId || 'senate.lord-speaker' !== ($l['seat'] ?? null) || 'ACTIVE' !== ($l['status'] ?? null) || true !== ($l['binding_atomic'] ?? null)
            || true !== ($l['delegate_question_dispatch_authorization_disposition_authority'] ?? null) || true === ($l['execution_authority'] ?? null)
            || ($a['bailiff']['binding_id'] ?? null) !== ($b['binding_id'] ?? null) || ($a['bailiff']['binding_digest'] ?? null) !== ($b['record_digest'] ?? null)
            || 'senate.bailiff' !== ($b['seat'] ?? null) || 'ACTIVE' !== ($b['status'] ?? null) || true !== ($b['binding_atomic'] ?? null)
            || true !== ($b['delegate_examination_question_dispatch_authority'] ?? null) || true === ($b['execution_authority'] ?? null)
            || ($q['instance_id'] ?? null) !== ($l['instance_id'] ?? null) || ($q['instance_id'] ?? null) !== ($b['instance_id'] ?? null) || true !== ($q['sealed'] ?? null))
            throw new \RuntimeException('S697_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CHAIN_INVALID');
    }

    private function read(string $p, string $e): array { if (!is_file($p)) throw new \RuntimeException($e); return json_decode((string) file_get_contents($p), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $r): bool { $d = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($d) && hash_equals($d, hash('sha256', CanonicalJson::encode($r))); }
    private function save(string $id, array $r): array
    {
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) throw new \RuntimeException('S698_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_PERSISTENCE_FAILED');
        $r['record_digest'] = hash('sha256', CanonicalJson::encode($r)); $p = $this->decisions.'/'.$id.'.json';
        if (is_file($p)) { $x = $this->read($p, 'S699_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CONFLICT'); if (CanonicalJson::encode($x) !== CanonicalJson::encode($r)) throw new \RuntimeException('S699_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_CONFLICT'); return $x; }
        if (false === file_put_contents($p, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S698_DELEGATE_MISSION_QUESTION_DISPATCH_DECISION_PERSISTENCE_FAILED');
        return $r;
    }
}
