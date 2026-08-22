<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationQuestionAuthorshipService
{
    private string $openings;
    private string $cases;
    private string $acceptances;
    private string $commissions;
    private string $occupancy;
    private string $questions;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private ProfileExaminationQuestionCognitionGateway $cognition,
    ) {
        $senate = $root.'/var/imperium/offices/senate';
        $this->openings = $senate.'/profile-examination-testimony-openings';
        $this->cases = $senate.'/profile-examination-cases';
        $this->acceptances = $senate.'/profile-examination-commission-acceptances';
        $this->commissions = $senate.'/profile-examination-commission-inbox';
        $this->occupancy = $senate.'/occupancy';
        $this->questions = $senate.'/profile-examination-questions';
    }

    public function author(string $openingId, string $acceptanceId, string $bindingId): array
    {
        if (!preg_match('/^profile-examination-testimony-opening-[a-f0-9]{20}$/', $openingId)) throw new \InvalidArgumentException('S215_PROFILE_EXAMINATION_TESTIMONY_OPENING_ID_INVALID');
        if (!preg_match('/^profile-examination-commission-acceptance-[a-f0-9]{20}$/', $acceptanceId)) throw new \InvalidArgumentException('S216_PROFILE_EXAMINATION_COMMISSION_ACCEPTANCE_ID_INVALID');
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S217_PROFILE_EXAMINATION_TESTIMONY_OPENING_ABSENT');
        $caseId = $opening['case_id'] ?? null;
        $case = is_string($caseId) ? $this->read($this->cases.'/'.$caseId.'.json', 'S218_PROFILE_EXAMINATION_CASE_ABSENT') : [];
        $acceptance = $this->read($this->acceptances.'/'.$acceptanceId.'.json', 'S219_PROFILE_EXAMINATION_COMMISSION_ACCEPTANCE_ABSENT');
        $commissionId = $acceptance['source_commission']['id'] ?? null;
        $commission = is_string($commissionId) ? $this->read($this->commissions.'/'.$commissionId.'.json', 'S219_PROFILE_EXAMINATION_COMMISSION_ABSENT') : [];
        $senator = $this->read($this->occupancy.'/'.$bindingId.'.json', 'S219_PROFILE_EXAMINATION_SENATOR_UNAVAILABLE');
        $seat = $senator['seat'] ?? null;
        $authority = null;
        foreach ($opening['question_authorities'] ?? [] as $candidate) if (($candidate['seat'] ?? null) === $seat) $authority = $candidate;

        if (!$this->valid($opening) || !$this->valid($case) || !$this->valid($acceptance) || !$this->valid($commission) || !$this->valid($senator)
            || 'PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING' !== ($opening['status'] ?? null)
            || true !== ($opening['testimony_open'] ?? null) || false !== ($opening['deliberation_open'] ?? null)
            || true !== ($opening['senator_question_authority_exercisable'] ?? null) || false !== ($opening['senator_finding_authority_exercisable'] ?? null)
            || ($opening['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || 'PROFILE_EXAMINATION_OPENED_PENDING_SENATOR_ACCEPTANCE' !== ($case['status'] ?? null)
            || ($opening['manifestation'] ?? null) !== ($case['manifestation'] ?? null) || ($opening['custody_lease'] ?? null) !== ($case['custody_lease'] ?? null)
            || !is_array($opening['manifestation']['profile'] ?? null) || !is_array($opening['manifestation']['persona'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($opening['manifestation']['purpose'] ?? null) || 'senate.stand' !== ($opening['manifestation']['target'] ?? null)
            || 'conscription.recruiter' !== ($opening['manifestation']['return_destination'] ?? null) || false !== ($opening['manifestation']['operational_use_permitted'] ?? null)
            || ($opening['defect_attribution_rubric'] ?? null) !== ($case['defect_attribution_rubric'] ?? null)
            || ($acceptance['case_id'] ?? null) !== $caseId || ($acceptance['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || true !== ($acceptance['recipient_acceptance'] ?? null) || ($acceptance['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || ($commission['case_id'] ?? null) !== $caseId || ($commission['recipient']['seat'] ?? null) !== $seat
            || ($commission['manifestation_id'] ?? null) !== ($opening['manifestation']['manifestation_id'] ?? null)
            || ($commission['defect_attribution_rubric'] ?? null) !== ($opening['defect_attribution_rubric'] ?? null)
            || !is_array($authority) || ($authority['acceptance_id'] ?? null) !== $acceptanceId || ($authority['acceptance_digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || true !== ($authority['senator_question_authority_exercisable'] ?? null) || false !== ($authority['senator_finding_authority_exercisable'] ?? null)
            || 'ACTIVE' !== ($senator['status'] ?? null) || true !== ($senator['binding_atomic'] ?? null) || true !== ($senator['senator_question_authority'] ?? null)
            || ($acceptance['senator']['binding_id'] ?? null) !== $bindingId || ($acceptance['senator']['binding_digest'] ?? null) !== ($senator['record_digest'] ?? null)
            || ($acceptance['senator']['manifestation_id'] ?? null) !== ($senator['manifestation_id'] ?? null) || ($acceptance['senator']['occupancy_generation'] ?? null) !== ($senator['occupancy_generation'] ?? null)
            || true === ($senator['execution_authority'] ?? null) || ($opening['instance_id'] ?? null) !== ($senator['instance_id'] ?? null)
        ) throw new \RuntimeException('S219_PROFILE_EXAMINATION_QUESTION_CHAIN_INVALID');

        foreach (glob($this->questions.'/profile-examination-question-*.json') ?: [] as $path) {
            $existing = $this->read($path, 'S222_PROFILE_EXAMINATION_QUESTION_CONFLICT');
            if (!$this->valid($existing)) throw new \RuntimeException('S222_PROFILE_EXAMINATION_QUESTION_CONFLICT');
            if (($existing['source_acceptance']['id'] ?? null) === $acceptanceId) return $existing;
        }

        $jurisdiction = substr((string) $seat, strlen('senate.committee.'));
        if (!in_array($jurisdiction, ['trust', 'security', 'usability'], true)) throw new \RuntimeException('S219_PROFILE_EXAMINATION_QUESTION_CHAIN_INVALID');
        $authored = $this->cognition->authorQuestion($jurisdiction, $commission, $opening);
        if (['purpose', 'question'] !== array_keys($authored) || !is_string($authored['purpose']) || '' === trim($authored['purpose']) || !is_string($authored['question']) || '' === trim($authored['question'])) throw new \RuntimeException('S220_PROFILE_EXAMINATION_QUESTION_COGNITION_INVALID');
        $actor = ['seat' => $seat, 'binding_id' => $bindingId, 'binding_digest' => $senator['record_digest'], 'manifestation_id' => $senator['manifestation_id'], 'occupancy_generation' => $senator['occupancy_generation']];
        $questionId = 'profile-examination-question-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $acceptanceId, $acceptance['record_digest'], $actor, $authored])), 0, 20);
        return $this->save($questionId, [
            'schema' => 'imperium.senate-profile-examination-question/v1', 'question_id' => $questionId,
            'instance_id' => $opening['instance_id'], 'case_id' => $caseId, 'case_digest' => $case['record_digest'],
            'source_stand_admission' => $case['source_stand_admission'],
            'source_testimony_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_acceptance' => ['id' => $acceptanceId, 'digest' => $acceptance['record_digest']],
            'senator' => $actor, 'jurisdiction' => $jurisdiction,
            'manifestation' => $opening['manifestation'],
            'profile_candidate' => $opening['manifestation']['profile'],
            'persona_identity' => $opening['manifestation']['persona'],
            'custody_lease' => $opening['custody_lease'],
            'return_destination' => $opening['manifestation']['return_destination'],
            'defect_attribution_rubric' => $opening['defect_attribution_rubric'],
            'question' => $authored, 'status' => 'PROFILE_EXAMINATION_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH',
            'senator_question_authority_consumed' => true, 'question_dispatched' => false, 'testimony_answer' => null,
            'testimony_open' => true, 'deliberation_open' => false, 'senator_finding_authority_exercisable' => false,
            'senate_disposition_authority' => false, 'profile_approval_authority' => false, 'profile_installation_authority' => false,
            'seat_binding_authority' => false, 'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array { if (!is_dir($this->questions) && !mkdir($this->questions, 0770, true) && !is_dir($this->questions)) throw new \RuntimeException('S221_PROFILE_EXAMINATION_QUESTION_FAILED'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->questions.'/'.$id.'.json'; if (is_file($path)) { $existing = $this->read($path, 'S222_PROFILE_EXAMINATION_QUESTION_CONFLICT'); if ($existing !== $record) throw new \RuntimeException('S222_PROFILE_EXAMINATION_QUESTION_CONFLICT'); return $existing; } if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S221_PROFILE_EXAMINATION_QUESTION_FAILED'); return $record; }
}
