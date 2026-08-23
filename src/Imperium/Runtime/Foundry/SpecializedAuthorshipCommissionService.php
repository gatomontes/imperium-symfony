<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;

final readonly class SpecializedAuthorshipCommissionService
{
    private string $caseDirectory;
    private string $acceptanceDirectory;
    private string $demandDirectory;
    private string $hagiographyInbox;
    private string $studiumInbox;

    public function __construct(string $projectDir)
    {
        $this->caseDirectory = $projectDir.'/var/imperium/offices/foundry/production-cases';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/foundry/acceptances';
        $this->demandDirectory = $projectDir.'/var/imperium/offices/foundry/inbox';
        $this->hagiographyInbox = $projectDir.'/var/imperium/offices/hagiography/inbox';
        $this->studiumInbox = $projectDir.'/var/imperium/offices/studium/inbox';
    }

    public function dispatch(string $caseId): array
    {
        if (!preg_match('/^persona-production-[a-f0-9]{20}$/', $caseId)) throw new \InvalidArgumentException('F80_CASE_INVALID: exact Persona production case identity is required.');
        $case = $this->read($this->caseDirectory.'/'.$caseId.'.json', 'F81_CASE_ABSENT');
        if (!$this->digestMatches($case) || $caseId !== ($case['case_id'] ?? null)
            || 'imperium.foundry-persona-production-case/v1' !== ($case['schema'] ?? null)
            || 'OPEN_PENDING_SPECIALIZED_INPUTS' !== ($case['status'] ?? null) || true !== ($case['construction_authority'] ?? null)
            || true === ($case['persona_selection_authority'] ?? null) || true === ($case['spawning_authority'] ?? null)
            || true === ($case['admission_authority'] ?? null) || true === ($case['seat_binding_authority'] ?? null)
            || true === ($case['execution_authority'] ?? null) || 'foundry.artificer' !== ($case['artificer']['seat'] ?? null)) {
            throw new \RuntimeException('F82_CASE_INVALID: exact open bounded Persona production case is required.');
        }
        $acceptanceId = $case['authorization_acceptance_id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'F83_CASE_CHAIN_INVALID') : [];
        $demandId = $case['source_demand_id'] ?? null;
        $demand = is_string($demandId) ? $this->read($this->demandDirectory.'/'.$demandId.'.json', 'F83_CASE_CHAIN_INVALID') : [];
        $authorizedReference = null;
        foreach ($acceptance['authorized_demands'] ?? [] as $reference) {
            if (is_array($reference) && $demandId === ($reference['demand_id'] ?? null)) {
                if (null !== $authorizedReference) throw new \RuntimeException('F83_CASE_CHAIN_INVALID: accepted authorization contains a duplicate demand identity.');
                $authorizedReference = $reference;
            }
        }
        if (!$this->digestMatches($acceptance) || !$this->digestMatches($demand)
            || ($case['authorization_acceptance_digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || ($case['source_demand_digest'] ?? null) !== ($demand['record_digest'] ?? null)
            || 'ACCEPTED_FOR_EXACT_CONSTRUCTION' !== ($acceptance['disposition'] ?? null)
            || ($case['profession'] ?? null) !== ($demand['profession'] ?? null)
            || !is_array($authorizedReference)
            || ($demand['profession'] ?? null) !== ($authorizedReference['profession'] ?? null)
            || ($demand['record_digest'] ?? null) !== ($authorizedReference['record_digest'] ?? null)) {
            throw new \RuntimeException('F83_CASE_CHAIN_INVALID: production case, demand, and accepted authorization do not agree.');
        }
        $common = [
            'schema' => 'imperium.specialized-authorship-commission/v1', 'issuer' => $case['artificer'],
            'instance_id' => $case['instance_id'], 'proceeding_id' => $case['proceeding_id'], 'production_case_id' => $caseId, 'production_case_digest' => $case['record_digest'],
            'source_demand_id' => $demandId, 'source_demand_digest' => $demand['record_digest'], 'profession' => $case['profession'],
            'queue_position' => $case['queue_position'], 'exemplar_criteria' => $case['exemplar_criteria'], 'team_composition' => $case['team_composition'], 'boundary_controls' => $case['boundary_controls'],
            'status' => 'ISSUED_PENDING_RECIPIENT', 'authorship_authority' => true, 'recipient_acceptance' => null,
            'persona_selection_authority' => false, 'persona_assembly_authority' => false, 'spawning_authority' => false,
            'admission_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false,
        ];
        $hagiographyId = 'authorship-hagiography-'.substr(hash('sha256', CanonicalJson::encode([$caseId, $case['record_digest'], 'hagiography'])), 0, 20);
        $studiumId = 'authorship-studium-'.substr(hash('sha256', CanonicalJson::encode([$caseId, $case['record_digest'], 'studium'])), 0, 20);
        $hagiography = $this->persist($this->hagiographyInbox, $hagiographyId, array_merge($common, [
            'commission_id' => $hagiographyId, 'office' => 'hagiography', 'target_seat' => 'hagiography.sanctographer',
            'authorship_class' => 'EVIDENCE_DERIVED_PERSONA_SECTIONS',
            'required_product' => 'Sanctographer-authenticated Hagiography Research Packet with attributable evidence-derived Persona sections',
            'forbidden_authorship' => ['governance doctrine', 'complete Persona', 'profession redefinition'],
        ]));
        $studium = $this->persist($this->studiumInbox, $studiumId, array_merge($common, [
            'commission_id' => $studiumId, 'office' => 'studium', 'target_seat' => 'studium.chancellor',
            'authorship_class' => 'PERSONA_GOVERNANCE_DOCTRINE_SECTIONS',
            'required_product' => 'Chancellor-authenticated Persona Governance Doctrine packet with attributable Notary-authored sections',
            'forbidden_authorship' => ['evidence-derived traits', 'complete Persona', 'profession redefinition'],
        ]));
        return ['case_id' => $caseId, 'artificer' => $case['artificer'], 'commissions' => [$hagiography, $studium]];
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(string $directory, string $commissionId, array $commission): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('Specialized Office inbox cannot be created.');
        $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission)); $path = $directory.'/'.$commissionId.'.json';
        if (is_file($path)) { $existing = $this->read($path, 'F84_COMMISSION_REPLAY_CONFLICT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($commission)) throw new \RuntimeException('F84_COMMISSION_REPLAY_CONFLICT: specialized commission identity is already bound differently.'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Specialized authorship commission cannot be committed atomically.'); }
        return $commission;
    }
}
