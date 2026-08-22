<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationFindingAuthorityOpeningService
{
    private string $readiness;
    private string $turns;
    private string $cases;
    private string $occupancy;
    private string $custody;
    private string $openings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->readiness = $senate.'/profile-examination-testimony-readiness';
        $this->turns = $senate.'/profile-examination-testimony-turns';
        $this->cases = $senate.'/profile-examination-cases';
        $this->occupancy = $senate.'/occupancy';
        $this->openings = $senate.'/profile-examination-finding-authority-openings';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
    }

    public function open(string $readinessId, string $lordSpeakerBindingId): array
    {
        if (!preg_match('/^profile-examination-testimony-readiness-[a-f0-9]{20}$/', $readinessId)) throw new \InvalidArgumentException('S232_PROFILE_EXAMINATION_TESTIMONY_READINESS_ID_INVALID');
        $readiness = $this->read($this->readiness.'/'.$readinessId.'.json', 'S233_PROFILE_EXAMINATION_TESTIMONY_READINESS_ABSENT');
        $caseId = $readiness['case_id'] ?? null;
        $case = is_string($caseId) ? $this->read($this->cases.'/'.$caseId.'.json', 'S234_PROFILE_EXAMINATION_CASE_ABSENT') : [];
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S235_LORD_SPEAKER_UNAVAILABLE');
        $custodyId = $case['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custody.'/'.$custodyId.'.json', 'S236_PROFILE_EXAMINATION_CUSTODY_ABSENT') : [];
        if (!$this->valid($readiness) || !$this->valid($case) || !$this->valid($lordSpeaker) || !$this->valid($custody)
            || 'imperium.senate-profile-examination-testimony-readiness/v1' !== ($readiness['schema'] ?? null)
            || 'PROFILE_EXAMINATION_TESTIMONY_ANSWERS_SEALED_PENDING_FINDING_AUTHORITY_OPENING' !== ($readiness['status'] ?? null)
            || true !== ($readiness['all_questions_dispatched_unchanged'] ?? null) || true !== ($readiness['all_testimony_answers_sealed'] ?? null)
            || false !== ($readiness['deliberation_open'] ?? null) || false !== ($readiness['senator_finding_authority_exercisable'] ?? null)
            || ($readiness['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || ($case['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || 'senate.lord-speaker' !== ($lordSpeaker['seat'] ?? null) || 'ACTIVE' !== ($lordSpeaker['status'] ?? null) || true !== ($lordSpeaker['binding_atomic'] ?? null)
            || true !== ($lordSpeaker['profile_examination_finding_phase_opening_authority'] ?? null) || true === ($lordSpeaker['execution_authority'] ?? null)
            || ($readiness['instance_id'] ?? null) !== ($lordSpeaker['instance_id'] ?? null)
        ) throw new \RuntimeException('S237_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');

        $references = $readiness['testimony_turns'] ?? null;
        if (!is_array($references) || 3 !== count($references)) throw new \RuntimeException('S237_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');
        $authorities = [];
        $baseline = null;
        $identityRecord = null;
        foreach ($references as $reference) {
            $turnId = $reference['turn_id'] ?? null;
            $turn = is_string($turnId) ? $this->read($this->turns.'/'.$turnId.'.json', 'S238_PROFILE_EXAMINATION_TESTIMONY_TURN_ABSENT') : [];
            $bindingId = $turn['senator']['binding_id'] ?? null;
            $senator = is_string($bindingId) ? $this->read($this->occupancy.'/'.$bindingId.'.json', 'S239_PROFILE_EXAMINATION_SENATOR_UNAVAILABLE') : [];
            $jurisdiction = $turn['jurisdiction'] ?? null;
            if (!$this->valid($turn) || !$this->valid($senator)
                || ($reference['turn_digest'] ?? null) !== ($turn['record_digest'] ?? null) || ($reference['jurisdiction'] ?? null) !== $jurisdiction
                || !in_array($jurisdiction, ['trust', 'security', 'usability'], true) || isset($authorities[$jurisdiction])
                || 'PROFILE_EXAMINATION_TESTIMONY_ANSWER_SEALED_PENDING_PANEL_COMPLETION' !== ($turn['status'] ?? null)
                || true !== ($turn['question_dispatched_unchanged'] ?? null) || true !== ($turn['testimony_answer_sealed'] ?? null) || null !== ($turn['senator_finding'] ?? null)
                || false !== ($turn['deliberation_open'] ?? null) || false !== ($turn['senator_finding_authority_exercisable'] ?? null)
                || $caseId !== ($turn['case_id'] ?? null) || ($turn['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
                || ($turn['manifestation'] ?? null) !== ($case['manifestation'] ?? null) || ($turn['custody_lease'] ?? null) !== ($case['custody_lease'] ?? null)
                || ($turn['defect_attribution_rubric'] ?? null) !== ($case['defect_attribution_rubric'] ?? null) || 'conscription.recruiter' !== ($turn['return_destination'] ?? null)
                || ($turn['senator']['seat'] ?? null) !== 'senate.committee.'.$jurisdiction || ($turn['senator']['binding_digest'] ?? null) !== ($senator['record_digest'] ?? null)
                || ($turn['senator']['manifestation_id'] ?? null) !== ($senator['manifestation_id'] ?? null) || ($turn['senator']['occupancy_generation'] ?? null) !== ($senator['occupancy_generation'] ?? null)
                || ($readiness['instance_id'] ?? null) !== ($senator['instance_id'] ?? null) || 'ACTIVE' !== ($senator['status'] ?? null) || true !== ($senator['binding_atomic'] ?? null)
                || true !== ($senator['senator_finding_authority'] ?? null) || true === ($senator['execution_authority'] ?? null)
            ) throw new \RuntimeException('S237_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');
            $identity = CanonicalJson::encode([$turn['manifestation'], $turn['profile_candidate'], $turn['persona_identity'], $turn['custody_lease'], $turn['return_destination'], $turn['defect_attribution_rubric']]);
            if (null !== $baseline && $baseline !== $identity) throw new \RuntimeException('S237_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');
            $baseline = $identity;
            $identityRecord ??= $turn;
            $authorities[$jurisdiction] = [
                'jurisdiction' => $jurisdiction, 'senator' => $turn['senator'],
                'source_commission' => $turn['source_commission'], 'source_acceptance' => $turn['source_acceptance'],
                'source_testimony_turn' => ['id' => $turnId, 'digest' => $turn['record_digest']],
                'senator_finding_authority_exercisable' => true, 'senator_finding' => null,
            ];
        }
        foreach (['trust', 'security', 'usability'] as $jurisdiction) if (!isset($authorities[$jurisdiction])) throw new \RuntimeException('S237_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');
        ksort($authorities);
        $actor = ['seat' => 'senate.lord-speaker', 'binding_id' => $lordSpeakerBindingId, 'binding_digest' => $lordSpeaker['record_digest'], 'manifestation_id' => $lordSpeaker['manifestation_id'], 'occupancy_generation' => $lordSpeaker['occupancy_generation']];
        $openingId = 'profile-examination-finding-authority-opening-'.substr(hash('sha256', CanonicalJson::encode([$readinessId, $readiness['record_digest'], $actor, array_column($authorities, 'source_testimony_turn')])), 0, 20);
        return $this->save($openingId, [
            'schema' => 'imperium.senate-profile-examination-finding-authority-opening/v1', 'opening_id' => $openingId,
            'instance_id' => $readiness['instance_id'], 'case_id' => $caseId, 'case_digest' => $case['record_digest'],
            'source_testimony_readiness' => ['id' => $readinessId, 'digest' => $readiness['record_digest']], 'lord_speaker' => $actor,
            'manifestation' => $identityRecord['manifestation'], 'profile_candidate' => $identityRecord['profile_candidate'],
            'persona_identity' => $identityRecord['persona_identity'], 'custody_lease' => $identityRecord['custody_lease'], 'return_destination' => $identityRecord['return_destination'],
            'defect_attribution_rubric' => $identityRecord['defect_attribution_rubric'], 'finding_authorities' => array_values($authorities),
            'status' => 'PROFILE_EXAMINATION_FINDING_AUTHORITIES_OPENED_PENDING_SENATOR_FINDINGS',
            'finding_phase_opening_authority_consumed' => true, 'senator_finding_authority_exercisable' => true,
            'senator_findings' => [], 'deliberation_open' => false, 'senate_disposition_authority' => false,
            'profile_approval_authority' => false, 'profile_installation_authority' => false, 'seat_binding_authority' => false, 'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array { if (!is_dir($this->openings) && !mkdir($this->openings, 0770, true) && !is_dir($this->openings)) throw new \RuntimeException('S240_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_FAILED'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->openings.'/'.$id.'.json'; if (is_file($path)) { $existing = $this->read($path, 'S241_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CONFLICT'); if ($existing !== $record) throw new \RuntimeException('S241_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_CONFLICT'); return $existing; } $temporary = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('S240_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_FAILED'); } return $record; }
}
