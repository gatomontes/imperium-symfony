<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationTestimonyOpeningService
{
    private string $readiness;
    private string $cases;
    private string $acceptances;
    private string $occupancy;
    private string $openings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->readiness = $senate.'/profile-examination-panel-readiness';
        $this->cases = $senate.'/profile-examination-cases';
        $this->acceptances = $senate.'/profile-examination-commission-acceptances';
        $this->occupancy = $senate.'/occupancy';
        $this->openings = $senate.'/profile-examination-testimony-openings';
    }

    public function open(string $readinessId, string $lordSpeakerBindingId): array
    {
        if (!preg_match('/^profile-examination-panel-readiness-[a-f0-9]{20}$/', $readinessId)) {
            throw new \InvalidArgumentException('S207_PROFILE_EXAMINATION_PANEL_READINESS_ID_INVALID');
        }

        $readiness = $this->read($this->readiness.'/'.$readinessId.'.json', 'S208_PROFILE_EXAMINATION_PANEL_READINESS_ABSENT');
        $caseId = $readiness['case_id'] ?? null;
        $case = is_string($caseId) ? $this->read($this->cases.'/'.$caseId.'.json', 'S209_PROFILE_EXAMINATION_CASE_ABSENT') : [];
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S210_LORD_SPEAKER_UNAVAILABLE');

        if (!$this->valid($readiness)
            || !$this->valid($case)
            || !$this->valid($lordSpeaker)
            || 'imperium.senate-profile-examination-panel-readiness/v1' !== ($readiness['schema'] ?? null)
            || 'PROFILE_EXAMINATION_PANEL_ACCEPTED_PENDING_TESTIMONY_OPENING' !== ($readiness['status'] ?? null)
            || true !== ($readiness['panel_ready'] ?? null)
            || false !== ($readiness['testimony_open'] ?? null)
            || false !== ($readiness['deliberation_open'] ?? null)
            || false !== ($readiness['senator_question_authority_exercisable'] ?? null)
            || false !== ($readiness['senator_finding_authority_exercisable'] ?? null)
            || 'PROFILE_EXAMINATION_OPENED_PENDING_SENATOR_ACCEPTANCE' !== ($case['status'] ?? null)
            || ($readiness['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || 'senate.lord-speaker' !== ($lordSpeaker['seat'] ?? null)
            || 'ACTIVE' !== ($lordSpeaker['status'] ?? null)
            || true !== ($lordSpeaker['binding_atomic'] ?? null)
            || true !== ($lordSpeaker['profile_examination_testimony_opening_authority'] ?? null)
            || true === ($lordSpeaker['execution_authority'] ?? null)
            || ($readiness['instance_id'] ?? null) !== ($lordSpeaker['instance_id'] ?? null)
        ) {
            throw new \RuntimeException('S211_PROFILE_EXAMINATION_TESTIMONY_OPENING_CHAIN_INVALID');
        }

        $acceptedPanel = $readiness['accepted_panel'] ?? null;
        if (!is_array($acceptedPanel) || count($acceptedPanel) !== count($case['panel'] ?? [])) {
            throw new \RuntimeException('S211_PROFILE_EXAMINATION_TESTIMONY_OPENING_CHAIN_INVALID');
        }

        $questionAuthorities = [];
        foreach ($acceptedPanel as $accepted) {
            $acceptanceId = $accepted['acceptance_id'] ?? null;
            $acceptance = is_string($acceptanceId)
                ? $this->read($this->acceptances.'/'.$acceptanceId.'.json', 'S212_PROFILE_EXAMINATION_COMMISSION_ACCEPTANCE_ABSENT')
                : [];
            $seat = $accepted['seat'] ?? null;
            if (!$this->valid($acceptance)
                || ($accepted['acceptance_digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
                || $caseId !== ($acceptance['case_id'] ?? null)
                || $seat !== ($acceptance['senator']['seat'] ?? null)
                || !in_array($seat, $case['panel'] ?? [], true)
                || true !== ($acceptance['recipient_acceptance'] ?? null)
                || false !== ($acceptance['senator_question_authority_exercisable'] ?? null)
                || false !== ($acceptance['senator_finding_authority_exercisable'] ?? null)
            ) {
                throw new \RuntimeException('S211_PROFILE_EXAMINATION_TESTIMONY_OPENING_CHAIN_INVALID');
            }
            $questionAuthorities[] = [
                'seat' => $seat,
                'acceptance_id' => $acceptanceId,
                'acceptance_digest' => $acceptance['record_digest'],
                'senator_question_authority_exercisable' => true,
                'senator_finding_authority_exercisable' => false,
            ];
        }

        usort($questionAuthorities, static fn (array $left, array $right): int => $left['seat'] <=> $right['seat']);
        $actor = [
            'seat' => 'senate.lord-speaker',
            'binding_id' => $lordSpeakerBindingId,
            'binding_digest' => $lordSpeaker['record_digest'],
            'manifestation_id' => $lordSpeaker['manifestation_id'],
            'occupancy_generation' => $lordSpeaker['occupancy_generation'],
        ];
        $openingId = 'profile-examination-testimony-opening-'.substr(hash('sha256', CanonicalJson::encode([$readinessId, $readiness['record_digest'], $actor])), 0, 20);

        return $this->save($openingId, [
            'schema' => 'imperium.senate-profile-examination-testimony-opening/v1',
            'opening_id' => $openingId,
            'instance_id' => $readiness['instance_id'],
            'case_id' => $caseId,
            'case_digest' => $case['record_digest'],
            'source_panel_readiness' => ['id' => $readinessId, 'digest' => $readiness['record_digest']],
            'lord_speaker' => $actor,
            'manifestation' => $case['manifestation'],
            'custody_lease' => $case['custody_lease'],
            'defect_attribution_rubric' => $case['defect_attribution_rubric'],
            'question_authorities' => $questionAuthorities,
            'disposition' => 'OPENED_EXACT_PROFILE_EXAMINATION_TESTIMONY',
            'status' => 'PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING',
            'profile_examination_testimony_opening_authority_consumed' => true,
            'testimony_open' => true,
            'deliberation_open' => false,
            'senator_question_authority_exercisable' => true,
            'senator_finding_authority_exercisable' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
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

    private function save(string $openingId, array $record): array
    {
        if (!is_dir($this->openings) && !mkdir($this->openings, 0770, true) && !is_dir($this->openings)) {
            throw new \RuntimeException('S213_PROFILE_EXAMINATION_TESTIMONY_OPENING_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->openings.'/'.$openingId.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'S214_PROFILE_EXAMINATION_TESTIMONY_OPENING_CONFLICT');
            if ($existing !== $record) throw new \RuntimeException('S214_PROFILE_EXAMINATION_TESTIMONY_OPENING_CONFLICT');
            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S213_PROFILE_EXAMINATION_TESTIMONY_OPENING_FAILED');
        }
        return $record;
    }
}
