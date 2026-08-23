<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionReconciliationService
{
    private string $openings;
    private string $occupancy;
    private string $reconciliations;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $base = $root.'/var/imperium/operational/';
        $this->openings = $base.'legate-result-adoption-reconciliation-openings';
        $this->occupancy = $base.'occupancy';
        $this->reconciliations = $base.'legate-result-adoption-reconciliations';
    }

    public function reconcile(string $openingId, string $authorityId, string $seneschalBindingId, array $authoredReconciliation, \DateTimeImmutable $reconciledAt): array
    {
        if (!preg_match('/^legate-result-adoption-reconciliation-opening-[a-f0-9]{20}$/', $openingId)) throw new \InvalidArgumentException('CUR516_RECONCILIATION_OPENING_ID_INVALID');
        if (!preg_match('/^legate-result-adoption-reconciliation-authority-[a-f0-9]{20}$/', $authorityId)) throw new \InvalidArgumentException('CUR517_RECONCILIATION_AUTHORITY_ID_INVALID');
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) throw new \InvalidArgumentException('CUR518_SENESCHAL_BINDING_ID_INVALID');

        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'CUR519_RECONCILIATION_OPENING_ABSENT');
        $reconciliation = $this->normalize($authoredReconciliation, $opening['admitted_assessments'] ?? []);
        foreach (glob($this->reconciliations.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR523_RECONCILIATION_CONFLICT');
            if (($prior['source_reconciliation_opening']['id'] ?? null) === $openingId) {
                if (($prior['source_reconciliation_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null) || ($prior['reconciler']['binding_id'] ?? null) !== $seneschalBindingId || ($prior['reconciliation'] ?? null) !== $reconciliation) throw new \RuntimeException('CUR523_RECONCILIATION_CONFLICT');
                return $prior;
            }
        }
        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR520_SENESCHAL_OCCUPANCY_ABSENT');
        $this->validate($openingId, $opening, $authorityId, $seneschalBindingId, $seneschal);
        $this->assertSoleCurrentSeneschal($seneschal);

        $actor = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalBindingId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => $seneschal['manifestation_id'], 'occupancy_generation' => $seneschal['occupancy_generation']];
        $id = 'legate-result-adoption-reconciliation-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $authorityId, $actor, $reconciliation])), 0, 20);
        return $this->save($id, [
            'schema' => 'imperium.legate-result-adoption-reconciliation/v1', 'reconciliation_id' => $id,
            'instance_id' => $opening['instance_id'], 'source_reconciliation_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'source_assessment_completion' => $opening['source_assessment_completion'], 'source_panel_readiness' => $opening['source_panel_readiness'],
            'source_issuance' => $opening['source_issuance'], 'source_evaluation_opening' => $opening['source_evaluation_opening'],
            'reconciler' => $actor, 'result' => $opening['result'], 'contract' => $opening['contract'],
            'assessment_references' => array_map(static fn (array $a): array => ['id' => $a['id'], 'digest' => $a['digest'], 'jurisdiction' => $a['jurisdiction']], $opening['admitted_assessments']),
            'reconciliation' => $reconciliation, 'reconciled_at' => $reconciledAt->format(DATE_ATOM),
            'status' => 'ADOPTION_ASSESSMENTS_RECONCILED_NO_DISPOSITION_PENDING_ADOPTION_DECISION_OPENING',
            'reconciliation_authority' => ['id' => $authorityId, 'single_use' => true, 'consumed' => true, 'continuing_authority' => false],
            'reconciliation_performed' => true, 'voting_performed' => false, 'aggregation_performed' => false,
            'adoption_disposition_authored' => false, 'adoption_decision_authority' => false,
            'result_operationally_adopted' => false, 'planning_amendment_authority' => false,
            'provider_invocation_authority' => false, 'credential_use_authority' => false, 'tool_use_authority' => false,
            'operational_use_permitted' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function normalize(array $value, array $admitted): array
    {
        $allowed=['assessment_references','summary','agreements','disagreements','limitations','risks','uncertainties'];
        if($value!==array_intersect_key($value,array_flip($allowed)))throw new \InvalidArgumentException('CUR521_AUTHORED_RECONCILIATION_INVALID');
        $summary=trim((string)($value['summary']??''));if(''===$summary)throw new \InvalidArgumentException('CUR521_AUTHORED_RECONCILIATION_INVALID');
        $expected=array_map(static fn(array$a):array=>['id'=>$a['id'],'digest'=>$a['digest'],'jurisdiction'=>$a['jurisdiction']],$admitted);
        if(($value['assessment_references']??null)!==$expected)throw new \InvalidArgumentException('CUR521_AUTHORED_RECONCILIATION_INVALID');
        return ['assessment_references'=>$expected,'summary'=>$summary,'agreements'=>$this->strings($value['agreements']??[]),'disagreements'=>$this->strings($value['disagreements']??[]),'limitations'=>$this->strings($value['limitations']??[]),'risks'=>$this->strings($value['risks']??[]),'uncertainties'=>$this->strings($value['uncertainties']??[])];
    }
    private function strings(mixed$v):array{if(!is_array($v))throw new \InvalidArgumentException('CUR521_AUTHORED_RECONCILIATION_INVALID');$o=[];foreach($v as$x){if(!is_string($x)||''===trim($x))throw new \InvalidArgumentException('CUR521_AUTHORED_RECONCILIATION_INVALID');$o[]=trim($x);}return array_values($o);}
    private function validate(string$id,array$o,string$authorityId,string$bindingId,array$s):void{$a=$o['reconciliation_authority']??[];if(!$this->valid($o)||'imperium.legate-result-adoption-reconciliation-opening/v1'!==($o['schema']??null)||$id!==($o['opening_id']??null)||'ADOPTION_ASSESSMENTS_ADMITTED_UNCHANGED_RECONCILIATION_AUTHORITY_OPENED_PENDING_SENESCHAL_RECONCILIATION'!==($o['status']??null)||$authorityId!==($a['authority_id']??null)||true!==($a['single_use']??null)||false!==($a['consumed']??null)||true!==($a['exercisable']??null)||$bindingId!==($a['recipient']['binding_id']??null)||false!==($o['reconciliation_performed']??null)||true===($o['voting_performed']??null)||true===($o['aggregation_performed']??null)||true===($o['result_operationally_adopted']??null)||!$this->valid($s)||$bindingId!==($s['binding_id']??null)||($a['recipient']['binding_digest']??null)!==($s['record_digest']??null)||'curia.seneschal'!==($s['seat']??null)||'ACTIVE'!==($s['status']??null)||true!==($s['sealed']??null))throw new \RuntimeException('CUR522_RECONCILIATION_CHAIN_INVALID');}
    private function assertSoleCurrentSeneschal(array$s):void{foreach(glob($this->occupancy.'/*.json')?:[]as$p){$x=$this->read($p,'CUR524_SENESCHAL_OCCUPANCY_CONFLICT');if('curia.seneschal'===($x['seat']??null)&&($x['binding_id']??null)!==($s['binding_id']??null)&&'ACTIVE'===($x['status']??null))throw new \RuntimeException('CUR524_SENESCHAL_OCCUPANCY_CONFLICT');}}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function valid(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}private function save(string$id,array$r):array{if(!is_dir($this->reconciliations)&&!mkdir($this->reconciliations,0770,true)&&!is_dir($this->reconciliations))throw new \RuntimeException('CUR525_RECONCILIATION_PERSISTENCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->reconciliations.'/'.$id.'.json';file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
