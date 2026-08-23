<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionDecisionOpeningService
{
    private string $reconciliations;
    private string $occupancy;
    private string $openings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $base = $root.'/var/imperium/operational/';
        $this->reconciliations = $base.'legate-result-adoption-reconciliations';
        $this->occupancy = $base.'occupancy';
        $this->openings = $base.'legate-result-adoption-decision-openings';
    }

    public function open(string $reconciliationId, string $seneschalBindingId, \DateTimeImmutable $openedAt): array
    {
        if (!preg_match('/^legate-result-adoption-reconciliation-[a-f0-9]{20}$/', $reconciliationId)) throw new \InvalidArgumentException('CUR526_ADOPTION_RECONCILIATION_ID_INVALID');
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) throw new \InvalidArgumentException('CUR527_SENESCHAL_BINDING_ID_INVALID');
        $reconciliation = $this->read($this->reconciliations.'/'.$reconciliationId.'.json', 'CUR528_ADOPTION_RECONCILIATION_ABSENT');
        foreach (glob($this->openings.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR532_ADOPTION_DECISION_OPENING_CONFLICT');
            if (($prior['source_reconciliation']['id'] ?? null) === $reconciliationId) {
                if (($prior['source_reconciliation']['digest'] ?? null) !== ($reconciliation['record_digest'] ?? null) || ($prior['decision_maker']['binding_id'] ?? null) !== $seneschalBindingId) throw new \RuntimeException('CUR532_ADOPTION_DECISION_OPENING_CONFLICT');
                return $prior;
            }
        }
        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR529_SENESCHAL_OCCUPANCY_ABSENT');
        $this->validate($reconciliationId, $reconciliation, $seneschalBindingId, $seneschal);
        $this->assertSoleCurrentSeneschal($seneschal);

        $actor = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalBindingId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => $seneschal['manifestation_id'], 'occupancy_generation' => $seneschal['occupancy_generation']];
        $openingId = 'legate-result-adoption-decision-opening-'.substr(hash('sha256', CanonicalJson::encode([$reconciliationId, $reconciliation['record_digest'], $actor])), 0, 20);
        $authorityId = 'legate-result-adoption-decision-authority-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $actor])), 0, 20);
        return $this->save($openingId, [
            'schema' => 'imperium.legate-result-adoption-decision-opening/v1', 'opening_id' => $openingId,
            'instance_id' => $reconciliation['instance_id'], 'source_reconciliation' => ['id' => $reconciliationId, 'digest' => $reconciliation['record_digest']],
            'source_reconciliation_opening' => $reconciliation['source_reconciliation_opening'],
            'source_assessment_completion' => $reconciliation['source_assessment_completion'],
            'source_panel_readiness' => $reconciliation['source_panel_readiness'], 'source_issuance' => $reconciliation['source_issuance'],
            'source_evaluation_opening' => $reconciliation['source_evaluation_opening'], 'decision_maker' => $actor,
            'result' => $reconciliation['result'], 'contract' => $reconciliation['contract'],
            'assessment_references' => $reconciliation['assessment_references'], 'reconciliation' => $reconciliation['reconciliation'],
            'decision_contract' => [
                'allowed_dispositions' => ['ADOPTED', 'ADOPTED_WITH_LIMITATIONS', 'NOT_ADOPTED', 'UNRESOLVED'],
                'rationale_required' => true, 'assessment_references_must_remain_unchanged' => true,
                'reconciliation_must_remain_unchanged' => true, 'limitations_required_when_conditional' => true,
                'plan_amendment_authority_granted_by_disposition' => false, 'operational_use_authority_granted_by_disposition' => false,
                'action_authority_granted_by_disposition' => false, 'execution_authority_granted_by_disposition' => false,
            ],
            'adoption_decision_authority' => ['authority_id' => $authorityId, 'recipient' => $actor, 'single_use' => true, 'consumed' => false, 'exercisable' => true],
            'opened_at' => $openedAt->format(DATE_ATOM),
            'status' => 'OPERATIONAL_ADOPTION_DECISION_AUTHORITY_OPENED_PENDING_SENESCHAL_DISPOSITION',
            'adoption_disposition_authored' => false, 'result_operationally_adopted' => false,
            'planning_amendment_authority' => false, 'provider_invocation_authority' => false, 'credential_use_authority' => false,
            'tool_use_authority' => false, 'operational_use_permitted' => false, 'external_action_authority' => false,
            'execution_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validate(string$id,array$r,string$bindingId,array$s):void{if(!$this->valid($r)||'imperium.legate-result-adoption-reconciliation/v1'!==($r['schema']??null)||$id!==($r['reconciliation_id']??null)||'ADOPTION_ASSESSMENTS_RECONCILED_NO_DISPOSITION_PENDING_ADOPTION_DECISION_OPENING'!==($r['status']??null)||true!==($r['reconciliation_performed']??null)||true===($r['voting_performed']??null)||true===($r['aggregation_performed']??null)||true===($r['adoption_disposition_authored']??null)||true===($r['adoption_decision_authority']??null)||true===($r['result_operationally_adopted']??null)||$bindingId!==($r['reconciler']['binding_id']??null)||!$this->valid($s)||$bindingId!==($s['binding_id']??null)||($r['reconciler']['binding_digest']??null)!==($s['record_digest']??null)||'curia.seneschal'!==($s['seat']??null)||'ACTIVE'!==($s['status']??null)||true!==($s['sealed']??null))throw new \RuntimeException('CUR530_ADOPTION_DECISION_OPENING_CHAIN_INVALID');}
    private function assertSoleCurrentSeneschal(array$s):void{foreach(glob($this->occupancy.'/*.json')?:[]as$p){$x=$this->read($p,'CUR533_SENESCHAL_OCCUPANCY_CONFLICT');if('curia.seneschal'===($x['seat']??null)&&($x['binding_id']??null)!==($s['binding_id']??null)&&'ACTIVE'===($x['status']??null))throw new \RuntimeException('CUR533_SENESCHAL_OCCUPANCY_CONFLICT');}}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function valid(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}private function save(string$id,array$r):array{if(!is_dir($this->openings)&&!mkdir($this->openings,0770,true)&&!is_dir($this->openings))throw new \RuntimeException('CUR531_ADOPTION_DECISION_OPENING_PERSISTENCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->openings.'/'.$id.'.json';file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
