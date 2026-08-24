<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionFindingAuthorityOpeningService
{
    private string $turns; private string $occupancy; private string $custody; private string $openings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->turns = $senate.'/delegate-mission-profile-examination-testimony-turns';
        $this->occupancy = $senate.'/occupancy';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->openings = $senate.'/delegate-mission-profile-examination-finding-authority-openings';
    }

    public function open(string $usabilityTurnId, string $lordSpeakerBindingId, \DateTimeImmutable $openedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-testimony-turn-[a-f0-9]{20}$/', $usabilityTurnId)) throw new \InvalidArgumentException('S720_DELEGATE_MISSION_TESTIMONY_TURN_ID_INVALID');
        $usability = $this->read($this->turns.'/'.$usabilityTurnId.'.json', 'S721_DELEGATE_MISSION_USABILITY_TESTIMONY_ABSENT');
        $securityId = $usability['source_prior_testimony_turn']['id'] ?? '';
        $trustId = $usability['source_earlier_testimony_turn']['id'] ?? '';
        $security = $this->read($this->turns.'/'.$securityId.'.json', 'S722_DELEGATE_MISSION_SECURITY_TESTIMONY_ABSENT');
        $trust = $this->read($this->turns.'/'.$trustId.'.json', 'S723_DELEGATE_MISSION_TRUST_TESTIMONY_ABSENT');
        $lord = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S724_DELEGATE_MISSION_LORD_SPEAKER_UNAVAILABLE');
        $custodyId = $usability['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S725_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $turns = ['trust' => $trust, 'security' => $security, 'usability' => $usability];
        $this->validateBaseline($usabilityTurnId, $turns, $lordSpeakerBindingId, $lord, $custody);

        $authorities = [];
        foreach ($turns as $jurisdiction => $turn) {
            $senatorData = $turn[$jurisdiction.'_senator'] ?? null;
            $bindingId = is_array($senatorData) ? ($senatorData['binding_id'] ?? '') : '';
            $senator = $this->read($this->occupancy.'/'.$bindingId.'.json', 'S726_DELEGATE_MISSION_SENATOR_UNAVAILABLE');
            if (!$this->valid($senator) || $senatorData['binding_digest'] !== $senator['record_digest']
                || 'senate.committee.'.$jurisdiction !== ($senator['seat'] ?? null) || 'ACTIVE' !== ($senator['status'] ?? null)
                || true !== ($senator['binding_atomic'] ?? null) || true !== ($senator['senator_finding_authority'] ?? null)
                || true === ($senator['execution_authority'] ?? null) || ($turn['instance_id'] ?? null) !== ($senator['instance_id'] ?? null)) throw new \RuntimeException('S727_DELEGATE_MISSION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');
            $actor = ['seat' => $senator['seat'], 'binding_id' => $bindingId, 'binding_digest' => $senator['record_digest'], 'manifestation_id' => $senator['manifestation_id'], 'occupancy_generation' => $senator['occupancy_generation']];
            $turnId = $turn['turn_id'];
            $authorities[] = [
                'authority_id' => 'delegate-mission-senator-finding-authority-'.substr(hash('sha256', CanonicalJson::encode([$usabilityTurnId, $jurisdiction, $actor, $turn['record_digest']])), 0, 20),
                'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => $actor,
                'purpose' => 'AUTHOR_ONE_'.$jurisdiction.'_FINDING_FROM_OWN_TESTIMONY', 'jurisdiction' => $jurisdiction,
                'source_testimony_turn' => ['id' => $turnId, 'digest' => $turn['record_digest']],
                'consumed' => false, 'peer_findings_visible' => false, 'continuing_authority' => false,
            ];
        }
        $actor = ['seat' => 'senate.lord-speaker', 'binding_id' => $lordSpeakerBindingId, 'binding_digest' => $lord['record_digest'], 'manifestation_id' => $lord['manifestation_id'], 'occupancy_generation' => $lord['occupancy_generation']];
        $id = 'delegate-mission-profile-examination-finding-authority-opening-'.substr(hash('sha256', CanonicalJson::encode([$usabilityTurnId, $usability['record_digest'], $actor, array_column($authorities, 'authority_id')])), 0, 20);
        return $this->save($id, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-finding-authority-opening/v1', 'opening_id' => $id,
            'instance_id' => $usability['instance_id'], 'officer_class' => $usability['officer_class'],
            'source_testimony_readiness' => ['id' => $usabilityTurnId, 'digest' => $usability['record_digest']],
            'testimony_turns' => array_map(static fn(array $a): array => ['jurisdiction' => $a['jurisdiction'], 'id' => $a['source_testimony_turn']['id'], 'digest' => $a['source_testimony_turn']['digest']], $authorities),
            'lord_speaker' => $actor, 'source_examination_opening' => $usability['source_examination_opening'],
            'source_profile_candidate' => $usability['source_profile_candidate'], 'source_reservation_disposition' => $usability['source_reservation_disposition'],
            'custody_lease' => $usability['custody_lease'], 'manifestation' => $usability['manifestation'],
            'hearing_contract' => $usability['hearing_contract'], 'defect_attribution_rubric' => $usability['hearing_contract']['defect_attribution_rubric'],
            'finding_phase_opening_authority' => ['id' => $usability['finding_phase_opening_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'finding_authorities' => $authorities, 'opened_at' => $openedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_FINDING_AUTHORITIES_OPENED_PENDING_INDEPENDENT_SENATOR_FINDINGS',
            'senator_findings' => [], 'findings_authority' => true, 'deliberation_authority' => false,
            'profile_approval_authority' => false, 'profile_activation_authority' => false, 'profile_installation_authority' => false,
            'mission_seat_binding_authority' => false, 'deployment_authority' => false, 'operational_use_authority' => false,
            'provider_invocation_authority' => false, 'data_access_authority' => false, 'tool_use_authority' => false,
            'credential_use_authority' => false, 'perimeter_crossing_authority' => false, 'external_action_authority' => false,
            'execution_authority' => false, 'mission_plan_amendment_authority' => false, 'follow_up_commission_authority' => false,
            'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validateBaseline(string $usabilityTurnId, array $turns, string $lordBindingId, array $lord, array $custody): void
    {
        $u = $turns['usability']; $s = $turns['security']; $t = $turns['trust']; $authority = $u['finding_phase_opening_authority'] ?? null;
        if (!$this->valid($u) || !$this->valid($s) || !$this->valid($t) || !$this->valid($lord) || !$this->valid($custody)
            || $usabilityTurnId !== ($u['turn_id'] ?? null) || 'DELEGATE_MISSION_USABILITY_TESTIMONY_RESPONSE_SEALED_PENDING_FINDING_AUTHORITY_OPENING' !== ($u['status'] ?? null)
            || true !== ($u['testimony_readiness']['all_questions_dispatched_unchanged'] ?? null) || true !== ($u['testimony_readiness']['all_responses_sealed'] ?? null)
            || !is_array($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.lord-speaker' !== ($authority['holder'] ?? null) || 'OPEN_THREE_JURISDICTION_FINDING_AUTHORITIES' !== ($authority['purpose'] ?? null) || false !== ($authority['consumed'] ?? null)
            || ($u['source_prior_testimony_turn']['id'] ?? null) !== ($s['turn_id'] ?? null) || ($u['source_prior_testimony_turn']['digest'] ?? null) !== ($s['record_digest'] ?? null)
            || ($u['source_earlier_testimony_turn']['id'] ?? null) !== ($t['turn_id'] ?? null) || ($u['source_earlier_testimony_turn']['digest'] ?? null) !== ($t['record_digest'] ?? null)
            || ['trust', 'security', 'usability'] !== array_keys($turns) || ['trust', 'security', 'usability'] !== ($u['hearing_contract']['jurisdictions'] ?? null)
            || ['persona', 'profile_elaboration', 'profile_derivation_and_sealing', 'conscription_assembly', 'generic_officer_substrate', 'persona_profile_compatibility', 'insufficient_evidence'] !== ($u['hearing_contract']['defect_attribution_rubric'] ?? null)
            || $u['manifestation'] !== $s['manifestation'] || $u['manifestation'] !== $t['manifestation'] || $u['custody_lease'] !== $s['custody_lease'] || $u['custody_lease'] !== $t['custody_lease']
            || ($u['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || $lordBindingId !== ($lord['binding_id'] ?? null) || 'senate.lord-speaker' !== ($lord['seat'] ?? null) || 'ACTIVE' !== ($lord['status'] ?? null)
            || true !== ($lord['binding_atomic'] ?? null) || true !== ($lord['delegate_finding_phase_opening_authority'] ?? null) || true === ($lord['execution_authority'] ?? null)
            || ($u['instance_id'] ?? null) !== ($lord['instance_id'] ?? null)) throw new \RuntimeException('S727_DELEGATE_MISSION_FINDING_AUTHORITY_OPENING_CHAIN_INVALID');
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array { if (!is_dir($this->openings) && !mkdir($this->openings, 0770, true) && !is_dir($this->openings)) throw new \RuntimeException('S728_DELEGATE_MISSION_FINDING_AUTHORITY_OPENING_FAILED'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->openings.'/'.$id.'.json'; if (is_file($path)) { $existing = $this->read($path, 'S729_DELEGATE_MISSION_FINDING_AUTHORITY_OPENING_CONFLICT'); if ($existing !== $record) throw new \RuntimeException('S729_DELEGATE_MISSION_FINDING_AUTHORITY_OPENING_CONFLICT'); return $existing; } if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S728_DELEGATE_MISSION_FINDING_AUTHORITY_OPENING_FAILED'); return $record; }
}
