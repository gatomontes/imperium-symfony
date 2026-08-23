<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationTestimonyService
{
    private string $questions;
    private string $openings;
    private string $cases;
    private string $admissions;
    private string $commissions;
    private string $acceptances;
    private string $candidates;
    private string $custody;
    private string $turns;
    private string $readiness;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private ProfileExaminationTestimonyCognitionGateway $cognition,
    ) {
        $senate = $root.'/var/imperium/offices/senate';
        $this->questions = $senate.'/profile-examination-questions';
        $this->openings = $senate.'/profile-examination-testimony-openings';
        $this->cases = $senate.'/profile-examination-cases';
        $this->admissions = $senate.'/examination-stand-admissions';
        $this->commissions = $senate.'/profile-examination-commission-inbox';
        $this->acceptances = $senate.'/profile-examination-commission-acceptances';
        $this->turns = $senate.'/profile-examination-testimony-turns';
        $this->readiness = $senate.'/profile-examination-testimony-readiness';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
    }

    public function conduct(string $questionId): array
    {
        if (!preg_match('/^profile-examination-question-[a-f0-9]{20}$/', $questionId)) throw new \InvalidArgumentException('S223_PROFILE_EXAMINATION_QUESTION_ID_INVALID');
        foreach (glob($this->turns.'/profile-examination-testimony-turn-*.json') ?: [] as $path) {
            $existing = $this->read($path, 'S231_PROFILE_EXAMINATION_TESTIMONY_CONFLICT');
            if (!$this->valid($existing)) throw new \RuntimeException('S231_PROFILE_EXAMINATION_TESTIMONY_CONFLICT');
            if (($existing['source_question']['id'] ?? null) === $questionId) return ['turn' => $existing, 'readiness' => $this->ready($existing['case_id'])];
        }
        $question = $this->read($this->questions.'/'.$questionId.'.json', 'S224_PROFILE_EXAMINATION_QUESTION_ABSENT');
        $openingId = $question['source_testimony_opening']['id'] ?? null;
        $opening = is_string($openingId) ? $this->read($this->openings.'/'.$openingId.'.json', 'S225_PROFILE_EXAMINATION_TESTIMONY_OPENING_ABSENT') : [];
        $caseId = $question['case_id'] ?? null;
        $case = is_string($caseId) ? $this->read($this->cases.'/'.$caseId.'.json', 'S226_PROFILE_EXAMINATION_CASE_ABSENT') : [];
        $admissionId = $question['source_stand_admission']['id'] ?? null;
        $admission = is_string($admissionId) ? $this->read($this->admissions.'/'.$admissionId.'.json', 'S226_PROFILE_EXAMINATION_STAND_ADMISSION_ABSENT') : [];
        $commissionId = $question['source_commission']['id'] ?? null;
        $commission = is_string($commissionId) ? $this->read($this->commissions.'/'.$commissionId.'.json', 'S226_PROFILE_EXAMINATION_COMMISSION_ABSENT') : [];
        $acceptanceId = $question['source_acceptance']['id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptances.'/'.$acceptanceId.'.json', 'S226_PROFILE_EXAMINATION_ACCEPTANCE_ABSENT') : [];
        $candidateId = $question['profile_candidate']['candidate_id'] ?? null;
        $candidate = is_string($candidateId) ? $this->read($this->candidates.'/'.$candidateId.'.json', 'S227_PROFILE_CANDIDATE_ABSENT') : [];
        $custodyId = $question['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custody.'/'.$custodyId.'.json', 'S228_PROFILE_EXAMINATION_CUSTODY_ABSENT') : [];
        if (!$this->valid($question) || !$this->valid($opening) || !$this->valid($case) || !$this->valid($admission) || !$this->valid($commission) || !$this->valid($acceptance) || !$this->valid($candidate) || !$this->valid($custody)
            || 'PROFILE_EXAMINATION_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH' !== ($question['status'] ?? null)
            || true !== ($question['senator_question_authority_consumed'] ?? null) || false !== ($question['question_dispatched'] ?? null) || null !== ($question['testimony_answer'] ?? null)
            || true !== ($question['testimony_open'] ?? null) || false !== ($question['deliberation_open'] ?? null) || false !== ($question['senator_finding_authority_exercisable'] ?? null)
            || ($question['source_testimony_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
            || ($question['case_digest'] ?? null) !== ($case['record_digest'] ?? null) || ($opening['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || ($question['source_stand_admission']['digest'] ?? null) !== ($admission['record_digest'] ?? null) || ($case['source_stand_admission'] ?? null) !== ($question['source_stand_admission'] ?? null)
            || ($question['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null) || ($commission['case_id'] ?? null) !== $caseId
            || ($question['source_acceptance']['digest'] ?? null) !== ($acceptance['record_digest'] ?? null) || ($acceptance['case_id'] ?? null) !== $caseId
            || ($acceptance['source_commission']['id'] ?? null) !== $commissionId || ($acceptance['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || ($acceptance['senator'] ?? null) !== ($question['senator'] ?? null) || ($commission['recipient']['seat'] ?? null) !== ($question['senator']['seat'] ?? null)
            || true !== ($acceptance['recipient_acceptance'] ?? null)
            || 'PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING' !== ($opening['status'] ?? null)
            || ($question['manifestation'] ?? null) !== ($opening['manifestation'] ?? null) || ($question['manifestation'] ?? null) !== ($case['manifestation'] ?? null)
            || ($question['profile_candidate']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || ($question['persona_identity'] ?? null) !== ($candidate['persona'] ?? null) || ($question['profile_candidate']['profile_id'] ?? null) !== ($candidate['profile_id'] ?? null)
            || ($question['profile_candidate']['profile_version'] ?? null) !== ($candidate['profile_version'] ?? null)
            || ($question['manifestation']['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || CanonicalJson::encode($question['manifestation']['profile']['candidate_content'] ?? null) !== CanonicalJson::encode($candidate['profile'] ?? null)
            || CanonicalJson::encode($question['manifestation']['profile']['candidate_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || ($question['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || 'conscription.recruiter' !== ($question['return_destination'] ?? null) || false !== ($question['manifestation']['operational_use_permitted'] ?? null)
            || true === ($question['senate_disposition_authority'] ?? null) || true === ($question['profile_approval_authority'] ?? null) || true === ($question['execution_authority'] ?? null)
        ) throw new \RuntimeException('S228_PROFILE_EXAMINATION_TESTIMONY_CHAIN_INVALID');

        $answer = $this->cognition->answer($question, $question['manifestation']);
        $this->validateAnswer($answer);
        $turnId = 'profile-examination-testimony-turn-'.substr(hash('sha256', CanonicalJson::encode([$questionId, $question['record_digest'], $answer])), 0, 20);
        $turn = $this->save($this->turns, $turnId, [
            'schema' => 'imperium.senate-profile-examination-testimony-turn/v1', 'turn_id' => $turnId,
            'instance_id' => $question['instance_id'], 'case_id' => $caseId, 'case_digest' => $case['record_digest'],
            'source_stand_admission' => $question['source_stand_admission'], 'source_testimony_opening' => $question['source_testimony_opening'],
            'source_commission' => $question['source_commission'], 'source_acceptance' => $question['source_acceptance'],
            'source_question' => ['id' => $questionId, 'digest' => $question['record_digest']],
            'senator' => $question['senator'], 'jurisdiction' => $question['jurisdiction'],
            'manifestation' => $question['manifestation'], 'profile_candidate' => $question['profile_candidate'], 'persona_identity' => $question['persona_identity'],
            'custody_lease' => $question['custody_lease'], 'return_destination' => $question['return_destination'], 'defect_attribution_rubric' => $question['defect_attribution_rubric'],
            'question' => $question['question'], 'testimony' => $answer, 'question_dispatched_unchanged' => true, 'testimony_answer_sealed' => true,
            'status' => 'PROFILE_EXAMINATION_TESTIMONY_ANSWER_SEALED_PENDING_PANEL_COMPLETION',
            'senator_finding' => null, 'deliberation_open' => false, 'senator_finding_authority_exercisable' => false, 'senate_disposition_authority' => false,
            'profile_approval_authority' => false, 'profile_installation_authority' => false, 'seat_binding_authority' => false, 'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
        return ['turn' => $turn, 'readiness' => $this->ready($caseId)];
    }

    private function ready(string $caseId): ?array
    {
        $turns = [];
        foreach (glob($this->turns.'/profile-examination-testimony-turn-*.json') ?: [] as $path) {
            $turn = $this->read($path, 'S231_PROFILE_EXAMINATION_TESTIMONY_CONFLICT');
            if (!$this->valid($turn)) throw new \RuntimeException('S231_PROFILE_EXAMINATION_TESTIMONY_CONFLICT');
            if (($turn['case_id'] ?? null) === $caseId) $turns[$turn['jurisdiction']] = $turn;
        }
        foreach (['trust', 'security', 'usability'] as $jurisdiction) if (!isset($turns[$jurisdiction])) return null;
        ksort($turns);
        $id = 'profile-examination-testimony-readiness-'.substr(hash('sha256', CanonicalJson::encode([$caseId, array_column($turns, 'record_digest')])), 0, 20);
        return $this->save($this->readiness, $id, [
            'schema' => 'imperium.senate-profile-examination-testimony-readiness/v1', 'readiness_id' => $id,
            'instance_id' => reset($turns)['instance_id'], 'case_id' => $caseId, 'case_digest' => reset($turns)['case_digest'],
            'testimony_turns' => array_map(static fn(array $turn): array => ['jurisdiction' => $turn['jurisdiction'], 'turn_id' => $turn['turn_id'], 'turn_digest' => $turn['record_digest']], array_values($turns)),
            'status' => 'PROFILE_EXAMINATION_TESTIMONY_ANSWERS_SEALED_PENDING_FINDING_AUTHORITY_OPENING',
            'all_questions_dispatched_unchanged' => true, 'all_testimony_answers_sealed' => true,
            'deliberation_open' => false, 'senator_finding_authority_exercisable' => false, 'senate_disposition_authority' => false,
            'profile_approval_authority' => false, 'profile_installation_authority' => false, 'seat_binding_authority' => false, 'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function validateAnswer(array $answer): void
    {
        $keys = array_keys($answer); sort($keys, SORT_STRING);
        if (['answer','evidence_claims','refusals','uncertainties'] !== $keys) throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: FIELDS_INVALID');
        if (!is_string($answer['answer']) || '' === trim($answer['answer'])) throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: ANSWER_INVALID');
        foreach (['evidence_claims','refusals','uncertainties'] as $field) {
            if (!is_array($answer[$field]) || !array_is_list($answer[$field])) throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: '.strtoupper($field).'_TYPE_INVALID');
            foreach ($answer[$field] as $value) if (!is_string($value) || '' === trim($value)) throw new \RuntimeException('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: '.strtoupper($field).'_ITEM_INVALID');
        }
    }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $directory, string $id, array $record): array { if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('S230_PROFILE_EXAMINATION_TESTIMONY_FAILED'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $directory.'/'.$id.'.json'; if (is_file($path)) { $existing = $this->read($path, 'S231_PROFILE_EXAMINATION_TESTIMONY_CONFLICT'); if ($existing !== $record) throw new \RuntimeException('S231_PROFILE_EXAMINATION_TESTIMONY_CONFLICT'); return $existing; } $temporary = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('S230_PROFILE_EXAMINATION_TESTIMONY_FAILED'); } return $record; }
}
