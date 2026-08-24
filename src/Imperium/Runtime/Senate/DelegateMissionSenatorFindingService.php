<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionSenatorFindingService
{
    private string $openings; private string $turns; private string $occupancy; private string $findings; private string $readiness;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private ProfileExaminationFindingCognitionGateway $cognition)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->openings = $senate.'/delegate-mission-profile-examination-finding-authority-openings';
        $this->turns = $senate.'/delegate-mission-profile-examination-testimony-turns';
        $this->occupancy = $senate.'/occupancy';
        $this->findings = $senate.'/delegate-mission-profile-examination-senator-findings';
        $this->readiness = $senate.'/delegate-mission-profile-examination-finding-readiness';
    }

    public function issue(string $openingId, string $jurisdiction, string $senatorBindingId, \DateTimeImmutable $authoredAt): array
    {
        if (!preg_match('/^delegate-mission-profile-examination-finding-authority-opening-[a-f0-9]{20}$/', $openingId)) throw new \InvalidArgumentException('S730_DELEGATE_MISSION_FINDING_OPENING_ID_INVALID');
        if (!in_array($jurisdiction, ['trust', 'security', 'usability'], true)) throw new \InvalidArgumentException('S731_DELEGATE_MISSION_FINDING_JURISDICTION_INVALID');
        foreach (glob($this->findings.'/*.json') ?: [] as $path) {
            $existing = $this->read($path, 'S739_DELEGATE_MISSION_FINDING_CONFLICT');
            if (($existing['source_finding_authority_opening']['id'] ?? null) === $openingId && ($existing['jurisdiction'] ?? null) === $jurisdiction) {
                if (!$this->valid($existing) || ($existing['senator']['binding_id'] ?? null) !== $senatorBindingId) throw new \RuntimeException('S739_DELEGATE_MISSION_FINDING_CONFLICT');
                return ['finding' => $existing, 'readiness' => $this->ready($openingId)];
            }
        }
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S732_DELEGATE_MISSION_FINDING_OPENING_ABSENT');
        $matches = array_values(array_filter($opening['finding_authorities'] ?? [], static fn(mixed $a): bool => is_array($a) && $jurisdiction === ($a['jurisdiction'] ?? null)));
        $authority = 1 === count($matches) ? $matches[0] : [];
        $turnId = $authority['source_testimony_turn']['id'] ?? '';
        $turn = $this->read($this->turns.'/'.$turnId.'.json', 'S733_DELEGATE_MISSION_FINDING_TESTIMONY_ABSENT');
        $senator = $this->read($this->occupancy.'/'.$senatorBindingId.'.json', 'S734_DELEGATE_MISSION_FINDING_SENATOR_UNAVAILABLE');
        $actor = ['seat' => $senator['seat'] ?? null, 'binding_id' => $senatorBindingId, 'binding_digest' => $senator['record_digest'] ?? null, 'manifestation_id' => $senator['manifestation_id'] ?? null, 'occupancy_generation' => $senator['occupancy_generation'] ?? null];
        if (!$this->valid($opening) || !$this->valid($turn) || !$this->valid($senator)
            || 'imperium.senate-delegate-mission-profile-examination-finding-authority-opening/v1' !== ($opening['schema'] ?? null)
            || 'DELEGATE_MISSION_FINDING_AUTHORITIES_OPENED_PENDING_INDEPENDENT_SENATOR_FINDINGS' !== ($opening['status'] ?? null)
            || true !== ($opening['findings_authority'] ?? null) || [] !== ($opening['senator_findings'] ?? null) || false !== ($opening['deliberation_authority'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null) || false !== ($authority['consumed'] ?? null)
            || $jurisdiction !== ($authority['jurisdiction'] ?? null) || $actor !== ($authority['holder'] ?? null)
            || ($authority['source_testimony_turn']['digest'] ?? null) !== ($turn['record_digest'] ?? null)
            || $jurisdiction !== ($turn['jurisdiction'] ?? null) || true !== ($turn['testimony_response_sealed'] ?? null) || true !== ($turn['question_dispatched_unchanged'] ?? null)
            || $opening['manifestation'] !== $turn['manifestation'] || $opening['custody_lease'] !== $turn['custody_lease'] || $opening['hearing_contract'] !== $turn['hearing_contract']
            || 'senate.committee.'.$jurisdiction !== ($senator['seat'] ?? null) || 'ACTIVE' !== ($senator['status'] ?? null) || true !== ($senator['binding_atomic'] ?? null)
            || true !== ($senator['senator_finding_authority'] ?? null) || true === ($senator['execution_authority'] ?? null)
            || ($opening['instance_id'] ?? null) !== ($senator['instance_id'] ?? null)) throw new \RuntimeException('S735_DELEGATE_MISSION_FINDING_CHAIN_INVALID');

        $evidenceReference = 'delegate-testimony:'.$jurisdiction.':'.$turn['record_digest'];
        $evidence = ['testimony_turn' => $turn, 'available_evidence_references' => [$evidenceReference], 'peer_findings' => []];
        $decision = $this->cognition->find($jurisdiction, $authority, $evidence);
        $this->validateDecision($decision, $opening['defect_attribution_rubric'], $evidenceReference);
        $id = 'delegate-mission-profile-examination-senator-finding-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $jurisdiction, $actor, $decision])), 0, 20);
        $blocking = 'security' === $jurisdiction && ('FAIL' === $decision['disposition'] || ('UNRESOLVED' === $decision['disposition'] && in_array($decision['severity'], ['HIGH', 'CRITICAL'], true)));
        $finding = $this->save($this->findings, $id, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-senator-finding/v1', 'finding_id' => $id,
            'instance_id' => $opening['instance_id'], 'officer_class' => $opening['officer_class'],
            'source_finding_authority_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'source_testimony_turn' => $authority['source_testimony_turn'], 'senator' => $actor, 'jurisdiction' => $jurisdiction,
            'source_profile_candidate' => $opening['source_profile_candidate'], 'custody_lease' => $opening['custody_lease'],
            'manifestation' => $opening['manifestation'], 'hearing_contract' => $opening['hearing_contract'],
            'defect_attribution_rubric' => $opening['defect_attribution_rubric'], 'decision' => $decision,
            'evidence_package_digest' => hash('sha256', CanonicalJson::encode($evidence)), 'mandatory_security_blocking_condition' => $blocking,
            'finding_authority' => ['id' => $authority['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'authored_at' => $authoredAt->format(DATE_ATOM), 'status' => 'DELEGATE_MISSION_SENATOR_FINDING_AUTHORED_SEALED_PENDING_PANEL_COMPLETION',
            'peer_findings_visible_at_authorship' => false, 'deliberation_authority' => false, 'senate_disposition_authority' => false,
            'profile_approval_authority' => false, 'profile_installation_authority' => false, 'mission_seat_binding_authority' => false,
            'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
        return ['finding' => $finding, 'readiness' => $this->ready($openingId)];
    }

    private function ready(string $openingId): ?array
    {
        $found = [];
        foreach (glob($this->findings.'/*.json') ?: [] as $path) {
            $finding = $this->read($path, 'S739_DELEGATE_MISSION_FINDING_CONFLICT');
            if (!$this->valid($finding)) throw new \RuntimeException('S739_DELEGATE_MISSION_FINDING_CONFLICT');
            if (($finding['source_finding_authority_opening']['id'] ?? null) === $openingId) {
                $jurisdiction = $finding['jurisdiction'] ?? null;
                if (!is_string($jurisdiction) || isset($found[$jurisdiction])) throw new \RuntimeException('S739_DELEGATE_MISSION_FINDING_CONFLICT');
                $found[$jurisdiction] = $finding;
            }
        }
        foreach (['trust', 'security', 'usability'] as $jurisdiction) if (!isset($found[$jurisdiction])) return null;
        $ordered = array_map(static fn(string $j): array => $found[$j], ['trust', 'security', 'usability']);
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S732_DELEGATE_MISSION_FINDING_OPENING_ABSENT');
        $id = 'delegate-mission-profile-examination-finding-readiness-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], array_column($ordered, 'record_digest')])), 0, 20);
        $deliberation = ['authority_id' => 'delegate-mission-deliberation-opening-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, array_column($ordered, 'record_digest')])), 0, 20), 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'senate.lord-speaker', 'purpose' => 'OPEN_FINDING_RECONCILIATION', 'consumed' => false, 'continuing_authority' => false];
        return $this->save($this->readiness, $id, [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-finding-readiness/v1', 'readiness_id' => $id,
            'instance_id' => $opening['instance_id'], 'officer_class' => $opening['officer_class'],
            'source_finding_authority_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'source_profile_candidate' => $opening['source_profile_candidate'], 'custody_lease' => $opening['custody_lease'],
            'manifestation' => $opening['manifestation'], 'hearing_contract' => $opening['hearing_contract'], 'defect_attribution_rubric' => $opening['defect_attribution_rubric'],
            'senator_findings' => array_map(static fn(array $f): array => ['jurisdiction' => $f['jurisdiction'], 'id' => $f['finding_id'], 'digest' => $f['record_digest'], 'disposition' => $f['decision']['disposition'], 'severity' => $f['decision']['severity'], 'mandatory_security_blocking_condition' => $f['mandatory_security_blocking_condition']], $ordered),
            'mandatory_security_blocking_condition' => $found['security']['mandatory_security_blocking_condition'],
            'deliberation_opening_authority' => $deliberation,
            'status' => 'DELEGATE_MISSION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING',
            'all_finding_authorities_consumed' => true, 'deliberation_authority' => false, 'reconciliation_authority' => false,
            'vote_authority' => false, 'aggregation_authority' => false, 'senate_disposition_authority' => false,
            'profile_approval_authority' => false, 'profile_installation_authority' => false, 'mission_seat_binding_authority' => false,
            'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function validateDecision(array $decision, array $rubric, string $evidenceReference): void
    {
        $keys = array_keys($decision); sort($keys, SORT_STRING);
        if (['attributed_defect', 'disposition', 'evidence_references', 'limitations', 'rationale', 'severity', 'uncertainty'] !== $keys) throw new \RuntimeException('S736_DELEGATE_MISSION_FINDING_COGNITION_INVALID');
        if (!in_array($decision['disposition'] ?? null, ['PASS', 'CONCERN', 'FAIL', 'UNRESOLVED'], true) || !in_array($decision['severity'] ?? null, ['NONE', 'LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true)) throw new \RuntimeException('S736_DELEGATE_MISSION_FINDING_COGNITION_INVALID');
        if (!is_string($decision['rationale'] ?? null) || '' === trim($decision['rationale']) || [$evidenceReference] !== ($decision['evidence_references'] ?? null)) throw new \RuntimeException('S736_DELEGATE_MISSION_FINDING_COGNITION_INVALID');
        foreach (['limitations', 'uncertainty'] as $field) if (!is_array($decision[$field] ?? null) || !array_is_list($decision[$field]) || array_filter($decision[$field], static fn($v): bool => !is_string($v) || '' === trim($v))) throw new \RuntimeException('S736_DELEGATE_MISSION_FINDING_COGNITION_INVALID');
        if ('PASS' === $decision['disposition']) { if (null !== $decision['attributed_defect'] || 'NONE' !== $decision['severity']) throw new \RuntimeException('S736_DELEGATE_MISSION_FINDING_COGNITION_INVALID'); }
        elseif (!in_array($decision['attributed_defect'] ?? null, $rubric, true)) throw new \RuntimeException('S736_DELEGATE_MISSION_FINDING_COGNITION_INVALID');
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $directory, string $id, array $record): array { if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('S738_DELEGATE_MISSION_FINDING_PERSISTENCE_FAILED'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $directory.'/'.$id.'.json'; if (is_file($path)) { $existing = $this->read($path, 'S739_DELEGATE_MISSION_FINDING_CONFLICT'); if ($existing !== $record) throw new \RuntimeException('S739_DELEGATE_MISSION_FINDING_CONFLICT'); return $existing; } if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('S738_DELEGATE_MISSION_FINDING_PERSISTENCE_FAILED'); return $record; }
}
