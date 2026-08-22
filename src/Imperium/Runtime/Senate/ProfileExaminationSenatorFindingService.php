<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationSenatorFindingService
{
    private string $openings;
    private string $turns;
    private string $occupancy;
    private string $findings;
    private string $readiness;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private ProfileExaminationFindingCognitionGateway $cognition)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->openings = $senate.'/profile-examination-finding-authority-openings';
        $this->turns = $senate.'/profile-examination-testimony-turns';
        $this->occupancy = $senate.'/occupancy';
        $this->findings = $senate.'/profile-examination-senator-findings';
        $this->readiness = $senate.'/profile-examination-finding-readiness';
    }

    public function issue(string $openingId, string $jurisdiction, string $senatorBindingId): array
    {
        if (!preg_match('/^profile-examination-finding-authority-opening-[a-f0-9]{20}$/', $openingId)) throw new \InvalidArgumentException('S243_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_ID_INVALID');
        if (!in_array($jurisdiction, ['trust', 'security', 'usability'], true)) throw new \InvalidArgumentException('S244_PROFILE_EXAMINATION_FINDING_JURISDICTION_INVALID');
        foreach (glob($this->findings.'/*.json') ?: [] as $path) {
            $existing = $this->read($path, 'S249_PROFILE_EXAMINATION_FINDING_CONFLICT');
            if (($existing['source_finding_authority_opening']['id'] ?? null) === $openingId && ($existing['jurisdiction'] ?? null) === $jurisdiction) {
                if (!$this->valid($existing) || ($existing['senator']['binding_id'] ?? null) !== $senatorBindingId) throw new \RuntimeException('S249_PROFILE_EXAMINATION_FINDING_CONFLICT');
                return ['finding' => $existing, 'readiness' => $this->ready($openingId)];
            }
        }
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S245_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_ABSENT');
        $matches = array_values(array_filter($opening['finding_authorities'] ?? [], static fn(mixed $authority): bool => is_array($authority) && $jurisdiction === ($authority['jurisdiction'] ?? null)));
        $authority = 1 === count($matches) ? $matches[0] : [];
        $turnId = $authority['source_testimony_turn']['id'] ?? null;
        $turn = is_string($turnId) ? $this->read($this->turns.'/'.$turnId.'.json', 'S246_PROFILE_EXAMINATION_FINDING_TESTIMONY_ABSENT') : [];
        $senator = $this->read($this->occupancy.'/'.$senatorBindingId.'.json', 'S247_PROFILE_EXAMINATION_FINDING_SENATOR_UNAVAILABLE');
        if (!$this->valid($opening) || !$this->valid($turn) || !$this->valid($senator)
            || 'imperium.senate-profile-examination-finding-authority-opening/v1' !== ($opening['schema'] ?? null)
            || 'PROFILE_EXAMINATION_FINDING_AUTHORITIES_OPENED_PENDING_SENATOR_FINDINGS' !== ($opening['status'] ?? null)
            || true !== ($opening['finding_phase_opening_authority_consumed'] ?? null) || true !== ($opening['senator_finding_authority_exercisable'] ?? null)
            || [] !== ($opening['senator_findings'] ?? null) || false !== ($opening['deliberation_open'] ?? null)
            || true !== ($authority['senator_finding_authority_exercisable'] ?? null) || null !== ($authority['senator_finding'] ?? null)
            || ($authority['source_testimony_turn']['digest'] ?? null) !== ($turn['record_digest'] ?? null)
            || $jurisdiction !== ($turn['jurisdiction'] ?? null) || 'PROFILE_EXAMINATION_TESTIMONY_ANSWER_SEALED_PENDING_PANEL_COMPLETION' !== ($turn['status'] ?? null)
            || true !== ($turn['question_dispatched_unchanged'] ?? null) || true !== ($turn['testimony_answer_sealed'] ?? null) || null !== ($turn['senator_finding'] ?? null)
            || ($opening['case_id'] ?? null) !== ($turn['case_id'] ?? null) || ($opening['case_digest'] ?? null) !== ($turn['case_digest'] ?? null)
            || ($opening['manifestation'] ?? null) !== ($turn['manifestation'] ?? null) || ($opening['profile_candidate'] ?? null) !== ($turn['profile_candidate'] ?? null)
            || ($opening['persona_identity'] ?? null) !== ($turn['persona_identity'] ?? null) || ($opening['custody_lease'] ?? null) !== ($turn['custody_lease'] ?? null)
            || ($opening['return_destination'] ?? null) !== ($turn['return_destination'] ?? null) || ($opening['defect_attribution_rubric'] ?? null) !== ($turn['defect_attribution_rubric'] ?? null)
            || ($authority['source_commission'] ?? null) !== ($turn['source_commission'] ?? null) || ($authority['source_acceptance'] ?? null) !== ($turn['source_acceptance'] ?? null)
            || ($authority['senator'] ?? null) !== ($turn['senator'] ?? null) || ($authority['senator'] ?? null) !== $this->actor($senator)
            || ($opening['instance_id'] ?? null) !== ($senator['instance_id'] ?? null) || 'ACTIVE' !== ($senator['status'] ?? null) || true !== ($senator['binding_atomic'] ?? null)
            || true !== ($senator['senator_finding_authority'] ?? null) || true === ($senator['execution_authority'] ?? null)
        ) throw new \RuntimeException('S248_PROFILE_EXAMINATION_FINDING_CHAIN_INVALID');

        $evidenceReference = 'testimony:'.$jurisdiction.':'.$turn['record_digest'];
        $evidence = ['testimony_turn' => $turn, 'available_evidence_references' => [$evidenceReference]];
        $decision = $this->cognition->find($jurisdiction, $authority, $evidence);
        $this->validateDecision($decision, $opening['defect_attribution_rubric'], $evidenceReference);
        $findingId = 'profile-examination-senator-finding-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $jurisdiction, $senatorBindingId, $decision])), 0, 20);
        $finding = $this->save($this->findings, $findingId, [
            'schema' => 'imperium.senate-profile-examination-senator-finding/v1', 'finding_id' => $findingId,
            'instance_id' => $opening['instance_id'], 'case_id' => $opening['case_id'], 'case_digest' => $opening['case_digest'],
            'source_finding_authority_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']], 'source_testimony_turn' => $authority['source_testimony_turn'],
            'source_commission' => $authority['source_commission'], 'source_acceptance' => $authority['source_acceptance'],
            'senator' => $authority['senator'], 'jurisdiction' => $jurisdiction,
            'manifestation' => $opening['manifestation'], 'profile_candidate' => $opening['profile_candidate'], 'persona_identity' => $opening['persona_identity'],
            'custody_lease' => $opening['custody_lease'], 'return_destination' => $opening['return_destination'], 'defect_attribution_rubric' => $opening['defect_attribution_rubric'],
            'decision' => $decision, 'evidence_package_digest' => hash('sha256', CanonicalJson::encode($evidence)),
            'status' => 'PROFILE_EXAMINATION_SENATOR_FINDING_AUTHORED_SEALED_PENDING_PANEL_COMPLETION',
            'senator_finding_authority_consumed' => true, 'attributable' => true, 'deliberation_open' => false,
            'senate_disposition_authority' => false, 'profile_approval_authority' => false, 'profile_installation_authority' => false,
            'seat_binding_authority' => false, 'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
        return ['finding' => $finding, 'readiness' => $this->ready($openingId)];
    }

    private function ready(string $openingId): ?array
    {
        $findings = [];
        foreach (glob($this->findings.'/*.json') ?: [] as $path) {
            $finding = $this->read($path, 'S249_PROFILE_EXAMINATION_FINDING_CONFLICT');
            if (!$this->valid($finding)) throw new \RuntimeException('S249_PROFILE_EXAMINATION_FINDING_CONFLICT');
            if (($finding['source_finding_authority_opening']['id'] ?? null) === $openingId) {
                $jurisdiction = $finding['jurisdiction'] ?? null;
                if (!is_string($jurisdiction) || isset($findings[$jurisdiction])) throw new \RuntimeException('S249_PROFILE_EXAMINATION_FINDING_CONFLICT');
                $findings[$jurisdiction] = $finding;
            }
        }
        foreach (['trust', 'security', 'usability'] as $jurisdiction) if (!isset($findings[$jurisdiction])) return null;
        ksort($findings);
        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'S245_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_ABSENT');
        if (!$this->valid($opening)) throw new \RuntimeException('S248_PROFILE_EXAMINATION_FINDING_CHAIN_INVALID');
        $id = 'profile-examination-finding-readiness-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], array_column($findings, 'record_digest')])), 0, 20);
        return $this->save($this->readiness, $id, [
            'schema' => 'imperium.senate-profile-examination-finding-readiness/v1', 'readiness_id' => $id,
            'instance_id' => $opening['instance_id'], 'case_id' => $opening['case_id'], 'case_digest' => $opening['case_digest'],
            'source_finding_authority_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'senator_findings' => array_map(static fn(array $finding): array => ['jurisdiction' => $finding['jurisdiction'], 'finding_id' => $finding['finding_id'], 'finding_digest' => $finding['record_digest']], array_values($findings)),
            'status' => 'PROFILE_EXAMINATION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING',
            'all_finding_authorities_consumed' => true, 'deliberation_open' => false, 'senate_disposition_authority' => false,
            'profile_approval_authority' => false, 'profile_installation_authority' => false, 'seat_binding_authority' => false,
            'deployment_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function validateDecision(array $decision, array $rubric, string $evidenceReference): void
    {
        $keys = array_keys($decision); sort($keys, SORT_STRING);
        if (['attributed_defect','disposition','evidence_references','limitations','rationale','severity','uncertainty'] !== $keys
            || !in_array($decision['disposition'] ?? null, ['PASS', 'CONCERN', 'FAIL', 'UNRESOLVED'], true)
            || !in_array($decision['severity'] ?? null, ['NONE', 'LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true)
            || !is_string($decision['rationale'] ?? null) || '' === trim($decision['rationale'])
            || [$evidenceReference] !== ($decision['evidence_references'] ?? null)
            || !is_array($decision['limitations'] ?? null) || !array_is_list($decision['limitations'])
            || !is_array($decision['uncertainty'] ?? null) || !array_is_list($decision['uncertainty'])
            || ('PASS' === $decision['disposition'] ? null !== ($decision['attributed_defect'] ?? null) : !in_array($decision['attributed_defect'] ?? null, $rubric, true))
            || ('PASS' === $decision['disposition'] && 'NONE' !== $decision['severity'])
        ) throw new \RuntimeException('S250_PROFILE_EXAMINATION_FINDING_INVALID');
        foreach (['limitations', 'uncertainty'] as $field) foreach ($decision[$field] as $value) if (!is_string($value) || '' === trim($value)) throw new \RuntimeException('S250_PROFILE_EXAMINATION_FINDING_INVALID');
    }

    private function actor(array $binding): array { return ['seat' => $binding['seat'], 'binding_id' => $binding['binding_id'], 'binding_digest' => $binding['record_digest'], 'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation']]; }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function save(string $directory, string $id, array $record): array { if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('S251_PROFILE_EXAMINATION_FINDING_FAILED'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $directory.'/'.$id.'.json'; if (is_file($path)) { $existing = $this->read($path, 'S249_PROFILE_EXAMINATION_FINDING_CONFLICT'); if ($existing !== $record) throw new \RuntimeException('S249_PROFILE_EXAMINATION_FINDING_CONFLICT'); return $existing; } $temporary = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('S251_PROFILE_EXAMINATION_FINDING_FAILED'); } return $record; }
}
