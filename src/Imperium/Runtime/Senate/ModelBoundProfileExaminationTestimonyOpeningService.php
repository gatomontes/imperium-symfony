<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;

final readonly class ModelBoundProfileExaminationTestimonyOpeningService
{
    private string $readiness;
    private string $cases;
    private string $acceptances;
    private string $occupancy;
    private string $openings;
    public function __construct(string $root)
    {
        $s = $root.'/var/imperium/offices/senate';
        $this->readiness = $s.'/model-bound-profile-examination-panel-readiness';
        $this->cases = $s.'/profile-examination-cases';
        $this->acceptances = $s.'/model-bound-profile-examination-commission-acceptances';
        $this->occupancy = $s.'/occupancy';
        $this->openings = $s.'/model-bound-profile-examination-testimony-openings';
    }
    public function open(string $readinessId,
    string $authorityId,
    string $lordSpeakerBindingId,
    \DateTimeImmutable $openedAt): array {
        $r = $this->read($this->readiness.'/'.$readinessId.'.json',
        'S253_MODEL_BOUND_PANEL_READINESS_ABSENT');
        $caseId = $r['case_id'] ?? '';
        $case = $this->read($this->cases.'/'.$caseId.'.json',
        'S254_MODEL_BOUND_EXAMINATION_CASE_ABSENT');
        $l = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json',
        'S255_MODEL_BOUND_EXAMINATION_LORD_SPEAKER_UNAVAILABLE');
        $authority = $r['testimony_opening_authority'] ?? [];
        if (!$this->ok($r) ||
        !$this->ok($case) ||
        !$this->ok($l) ||
        'imperium.senate-model-bound-profile-examination-panel-readiness/v1' !== ($r['schema'] ?? null) ||
        'PROFILE_EXAMINATION_PANEL_ACCEPTED_PENDING_TESTIMONY_OPENING' !== ($r['status'] ?? null) ||
        true !== ($r['panel_ready'] ?? null) ||
        false !== ($r['testimony_open'] ?? null) ||
        $authorityId !== ($authority['authority_id'] ?? null) ||
        true !== ($authority['authority_single_use'] ?? null) ||
        'imperium.senate-model-bound-profile-examination-case/v1' !== ($case['schema'] ?? null) ||
        ($r['case_digest'] ?? null) !== ($case['record_digest'] ?? null) ||
        ($r['subject_profile'] ?? null) !== ($case['subject_profile'] ?? null) ||
        ($r['evidence_chain'] ?? null) !== ($case['evidence_chain'] ?? null) ||
        'senate.lord-speaker' !== ($l['seat'] ?? null) ||
        'ACTIVE' !== ($l['status'] ?? null) ||
        true !== ($l['binding_atomic'] ?? null) ||
        true !== ($l['profile_examination_testimony_opening_authority'] ?? null) ||
        true === ($l['execution_authority'] ?? null) ||
        ($r['instance_id'] ?? null) !== ($l['instance_id'] ?? null)) throw new \RuntimeException('S256_MODEL_BOUND_TESTIMONY_OPENING_CHAIN_INVALID');
        $questions = [];
        foreach ($r['accepted_panel'] ?? [] as $x) {
            $id = $x['acceptance_id'] ?? '';
            $a = $this->read($this->acceptances.'/'.$id.'.json',
            'S257_MODEL_BOUND_EXAMINATION_ACCEPTANCE_ABSENT');
            if (!$this->ok($a) ||
            ($x['acceptance_digest'] ?? null) !== $a['record_digest'] ||
            $caseId !== ($a['case_id'] ?? null) ||
            ($x['seat'] ?? null) !== ($a['senator']['seat'] ?? null) ||
            true !== ($a['recipient_acceptance'] ?? null) ||
            true !== ($a['senator_question_authority'] ?? null) ||
            false !== ($a['senator_question_authority_exercisable'] ?? null) ||
            false !== ($a['senator_finding_authority'] ?? null)) throw new \RuntimeException('S256_MODEL_BOUND_TESTIMONY_OPENING_CHAIN_INVALID');
            $questions[] = ['seat' => $x['seat'],
            'acceptance_id' => $id,
            'acceptance_digest' => $a['record_digest'],
            'senator_question_authority_exercisable' => true,
            'senator_finding_authority' => false,
            'senator_finding_authority_exercisable' => false];
        }
        if (array_column($questions,
        'seat') !== ($case['panel'] ?? [])) throw new \RuntimeException('S256_MODEL_BOUND_TESTIMONY_OPENING_CHAIN_INVALID');
        $actor = ['seat' => 'senate.lord-speaker',
        'binding_id' => $lordSpeakerBindingId,
        'binding_digest' => $l['record_digest'],
        'manifestation_id' => $l['manifestation_id'],
        'occupancy_generation' => $l['occupancy_generation']];
        $id = 'model-bound-profile-examination-testimony-opening-'.substr(hash('sha256',
        CanonicalJson::encode([$readinessId,
        $r['record_digest'],
        $authorityId,
        $actor])),
        0,
        20);
        return $this->save($id,
        ['schema' => 'imperium.senate-model-bound-profile-examination-testimony-opening/v1',
        'opening_id' => $id,
        'instance_id' => $r['instance_id'],
        'case_id' => $caseId,
        'case_digest' => $case['record_digest'],
        'source_panel_readiness' => ['id' => $readinessId,
        'digest' => $r['record_digest']],
        'testimony_opening_authority' => ['id' => $authorityId,
        'consumed' => true,
        'continuing_authority' => false],
        'lord_speaker' => $actor,
        'subject_profile' => $case['subject_profile'],
        'evidence_chain' => $case['evidence_chain'],
        'question_authorities' => $questions,
        'opened_at' => $openedAt->format(DATE_ATOM),
        'status' => 'PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING',
        'testimony_open' => true,
        'deliberation_open' => false,
        'senator_question_authority_exercisable' => true,
        'senator_finding_authority' => false,
        'senator_finding_authority_exercisable' => false,
        'profile_approval_authority' => false,
        'profile_installation_authority' => false,
        'profile_activation_authority' => false,
        'operational_qualification_authority' => false,
        'manifestation_assembly_authority' => false,
        'seat_binding_authority' => false,
        'credential_use_authority' => false,
        'provider_invocation_authority' => false,
        'deployment_authority' => false,
        'execution_authority' => false,
        'sealed' => true]);
    }
    private function read(string $p,
    string $e): array {
        if (!is_file($p)) throw new \RuntimeException($e);
        return json_decode((string)file_get_contents($p),
        true,
        512,
        JSON_THROW_ON_ERROR);
    }
    private function ok(array $r): bool
    {
        $d = $r['record_digest'] ?? null;
        unset($r['record_digest']);
        return is_string($d) &&
        hash_equals($d,
        hash('sha256',
        CanonicalJson::encode($r)));
    }
    private function save(string $id,
    array $r): array {
        if (!is_dir($this->openings) &&
        !mkdir($this->openings,
        0770,
        true) &&
        !is_dir($this->openings)) throw new \RuntimeException('S258_MODEL_BOUND_TESTIMONY_OPENING_FAILED');
        $r['record_digest'] = hash('sha256',
        CanonicalJson::encode($r));
        $p = $this->openings.'/'.$id.'.json';
        if (is_file($p)) {
            if ($this->read($p,
            'S259_MODEL_BOUND_TESTIMONY_OPENING_CONFLICT') !== $r) throw new \RuntimeException('S259_MODEL_BOUND_TESTIMONY_OPENING_CONFLICT');
            return $r;
        }
        file_put_contents($p,
        json_encode($r,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",
        LOCK_EX);
        return $r;
    }
}
