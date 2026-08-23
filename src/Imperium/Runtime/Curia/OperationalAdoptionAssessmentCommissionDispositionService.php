<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionAssessmentCommissionDispositionService
{
    private const DECISIONS = ['ACCEPTED', 'REFUSED'];

    private string $commissions;
    private string $issuances;
    private string $occupancy;
    private string $dispositions;
    private string $readiness;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->commissions = $root.'/var/imperium/operational/legate-result-adoption-assessment-commissions';
        $this->issuances = $root.'/var/imperium/operational/legate-result-adoption-assessment-issuances';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->dispositions = $root.'/var/imperium/operational/legate-result-adoption-assessment-commission-dispositions';
        $this->readiness = $root.'/var/imperium/operational/legate-result-adoption-assessment-panel-readiness';
    }

    public function decide(string $commissionId, string $curialisBindingId, string $decision, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^legate-result-adoption-assessment-commission-[a-f0-9]{20}$/', $commissionId)) throw new \InvalidArgumentException('CUR481_ASSESSMENT_COMMISSION_ID_INVALID');
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $curialisBindingId)) throw new \InvalidArgumentException('CUR482_CURIALIS_BINDING_ID_INVALID');
        $decision = strtoupper(trim($decision)); $rationale = trim($rationale);
        if (!in_array($decision, self::DECISIONS, true) || '' === $rationale) throw new \InvalidArgumentException('CUR483_ASSESSMENT_COMMISSION_DISPOSITION_INVALID');

        $commission = $this->read($this->commissions.'/'.$commissionId.'.json', 'CUR484_ASSESSMENT_COMMISSION_ABSENT');
        $issuance = $this->read($this->issuances.'/'.($commission['issuance_id'] ?? '').'.json', 'CUR485_ASSESSMENT_ISSUANCE_ABSENT');
        $occupant = $this->read($this->occupancy.'/'.$curialisBindingId.'.json', 'CUR486_CURIALIS_OCCUPANCY_ABSENT');
        $this->validate($commissionId, $commission, $issuance, $curialisBindingId, $occupant);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR489_ASSESSMENT_COMMISSION_DISPOSITION_CONFLICT');
            if (($prior['source_commission']['id'] ?? null) === $commissionId) {
                if (($prior['source_commission']['digest'] ?? null) !== $commission['record_digest'] || ($prior['recipient']['binding_id'] ?? null) !== $curialisBindingId || ($prior['decision'] ?? null) !== $decision || ($prior['rationale'] ?? null) !== $rationale) throw new \RuntimeException('CUR489_ASSESSMENT_COMMISSION_DISPOSITION_CONFLICT');
                return ['disposition' => $prior, 'panel_readiness' => $this->existingReadiness($issuance['issuance_id'])];
            }
        }
        $this->assertEvaluationStillOpen($issuance['issuance_id']);

        $recipient = ['seat' => $occupant['seat'], 'binding_id' => $curialisBindingId, 'binding_digest' => $occupant['record_digest'], 'manifestation_id' => $occupant['manifestation_id'], 'occupancy_generation' => $occupant['occupancy_generation'], 'officer_class' => $occupant['officer_class']];
        $dispositionId = 'legate-result-adoption-assessment-disposition-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $recipient, $decision, $rationale])), 0, 20);
        $disposition = $this->save($this->dispositions, $dispositionId, [
            'schema' => 'imperium.legate-result-adoption-assessment-commission-disposition/v1', 'disposition_id' => $dispositionId,
            'instance_id' => $commission['instance_id'], 'issuance_id' => $commission['issuance_id'],
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_evaluation_opening' => $commission['source_evaluation_opening'], 'recipient' => $recipient,
            'jurisdiction' => $commission['jurisdiction'], 'decision' => $decision, 'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'status' => 'ACCEPTED' === $decision ? 'ASSESSMENT_COMMISSION_ACCEPTED_PENDING_FULL_PANEL_READINESS_NO_ASSESSMENT_AUTHORITY' : 'ASSESSMENT_COMMISSION_REFUSED_EVALUATION_CLOSED_NO_AUTHORITY',
            'recipient_acceptance_sealed' => 'ACCEPTED' === $decision, 'commission_exercisable' => false,
            'assessment_authority' => false, 'governed_cognition_authority' => false, 'provider_invocation_authority' => false,
            'credential_use_authority' => false, 'tool_use_authority' => false, 'operational_use_permitted' => false,
            'result_operationally_adopted' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'continuing_turn_authority' => false, 'sealed' => true,
        ]);

        return ['disposition' => $disposition, 'panel_readiness' => $this->sealReadiness($issuance, $decidedAt)];
    }

    private function sealReadiness(array $issuance, \DateTimeImmutable $at): ?array
    {
        $found = [];
        foreach ($issuance['commissions'] as $jurisdiction => $reference) {
            foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
                $record = $this->read($path, 'CUR489_ASSESSMENT_COMMISSION_DISPOSITION_CONFLICT');
                if (($record['source_commission']['id'] ?? null) === $reference['id']) { $found[$jurisdiction] = $record; break; }
            }
        }
        if (count($found) < count($issuance['commissions'])) return null;
        $accepted = !in_array('REFUSED', array_column($found, 'decision'), true);
        $id = 'legate-result-adoption-assessment-panel-readiness-'.substr(hash('sha256', CanonicalJson::encode([$issuance['issuance_id'], array_map(static fn (array $r): string => $r['record_digest'], $found)])), 0, 20);
        $authorities = [];
        if ($accepted) foreach ($found as $jurisdiction => $record) $authorities[$jurisdiction] = ['authority_id' => 'adoption-assessment-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $jurisdiction, $record['recipient']])), 0, 20), 'recipient' => $record['recipient'], 'single_use' => true, 'consumed' => false, 'exercisable' => true];
        return $this->save($this->readiness, $id, [
            'schema' => 'imperium.legate-result-adoption-assessment-panel-readiness/v1', 'readiness_id' => $id,
            'instance_id' => $issuance['instance_id'], 'source_issuance' => ['id' => $issuance['issuance_id'], 'digest' => $issuance['record_digest']],
            'dispositions' => array_map(static fn (array $r): array => ['id' => $r['disposition_id'], 'digest' => $r['record_digest'], 'decision' => $r['decision']], $found),
            'assessment_authorities' => $authorities, 'all_commissions_accepted' => $accepted, 'evaluation_closed' => !$accepted,
            'status' => $accepted ? 'ADOPTION_ASSESSMENT_PANEL_ACCEPTED_AUTHORITIES_OPENED_PENDING_INDEPENDENT_ASSESSMENTS' : 'ADOPTION_ASSESSMENT_PANEL_INCOMPLETE_REFUSAL_EVALUATION_CLOSED_NO_AUTHORITY',
            'sealed_at' => $at->format(DATE_ATOM), 'result_operationally_adopted' => false, 'planning_amendment_authority' => false,
            'governed_cognition_authority' => false, 'provider_invocation_authority' => false, 'credential_use_authority' => false,
            'tool_use_authority' => false, 'external_action_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function validate(string $id, array $commission, array $issuance, string $bindingId, array $occupant): void
    {
        $reference = $issuance['commissions'][$commission['jurisdiction'] ?? ''] ?? [];
        if (!$this->valid($commission) || !$this->valid($issuance) || !$this->valid($occupant)
            || 'imperium.legate-result-adoption-assessment-commission/v1' !== ($commission['schema'] ?? null) || $id !== ($commission['commission_id'] ?? null)
            || 'ISSUED_PENDING_CURIALIS_ACCEPTANCE' !== ($commission['status'] ?? null) || true !== ($commission['recipient_acceptance_required'] ?? null)
            || true === ($commission['commission_exercisable'] ?? null) || true === ($commission['assessment_authority'] ?? null)
            || ($reference['id'] ?? null) !== $id || ($reference['digest'] ?? null) !== $commission['record_digest']
            || $bindingId !== ($commission['target']['binding_id'] ?? null) || $bindingId !== ($occupant['binding_id'] ?? null)
            || ($commission['target']['binding_digest'] ?? null) !== $occupant['record_digest'] || ($commission['instance_id'] ?? null) !== ($occupant['instance_id'] ?? null)
            || 'ACTIVE' !== ($occupant['status'] ?? null) || true !== ($occupant['sealed'] ?? null)) throw new \RuntimeException('CUR487_ASSESSMENT_COMMISSION_ACCEPTANCE_CHAIN_INVALID');
    }

    private function assertEvaluationStillOpen(string $issuanceId): void
    {
        foreach (glob($this->readiness.'/*.json') ?: [] as $path) { $r = $this->read($path, 'CUR490_ASSESSMENT_PANEL_ALREADY_CLOSED'); if (($r['source_issuance']['id'] ?? null) === $issuanceId && true === ($r['evaluation_closed'] ?? null)) throw new \RuntimeException('CUR490_ASSESSMENT_PANEL_ALREADY_CLOSED'); }
    }
    private function existingReadiness(string $issuanceId): ?array { foreach (glob($this->readiness.'/*.json') ?: [] as $path) { $r=$this->read($path,'CUR490_ASSESSMENT_PANEL_ALREADY_CLOSED'); if(($r['source_issuance']['id']??null)===$issuanceId)return$r;} return null; }
    private function read(string $path,string $error):array{if(!is_file($path))throw new \RuntimeException($error);return json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}
    private function valid(array $r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}
    private function save(string $dir,string $id,array $r):array{if(!is_dir($dir)&&!mkdir($dir,0770,true)&&!is_dir($dir))throw new \RuntimeException('CUR488_ASSESSMENT_DISPOSITION_PERSISTENCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$dir.'/'.$id.'.json';if(is_file($p)){ $x=$this->read($p,'CUR489_ASSESSMENT_COMMISSION_DISPOSITION_CONFLICT');if(CanonicalJson::encode($x)!==CanonicalJson::encode($r))throw new \RuntimeException('CUR489_ASSESSMENT_COMMISSION_DISPOSITION_CONFLICT');return$x;}file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
