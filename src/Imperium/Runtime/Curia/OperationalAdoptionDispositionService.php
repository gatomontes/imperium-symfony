<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionDispositionService
{
    private const DISPOSITIONS = ['ADOPTED', 'ADOPTED_WITH_LIMITATIONS', 'NOT_ADOPTED', 'UNRESOLVED'];

    private string $openings;
    private string $occupancy;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $base = $root.'/var/imperium/operational/';
        $this->openings = $base.'legate-result-adoption-decision-openings';
        $this->occupancy = $base.'occupancy';
        $this->dispositions = $base.'legate-result-adoption-dispositions';
    }

    public function decide(string $openingId, string $authorityId, string $seneschalBindingId, string $disposition, string $rationale, array $limitations, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^legate-result-adoption-decision-opening-[a-f0-9]{20}$/', $openingId)) throw new \InvalidArgumentException('CUR534_ADOPTION_DECISION_OPENING_ID_INVALID');
        if (!preg_match('/^legate-result-adoption-decision-authority-[a-f0-9]{20}$/', $authorityId)) throw new \InvalidArgumentException('CUR535_ADOPTION_DECISION_AUTHORITY_ID_INVALID');
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) throw new \InvalidArgumentException('CUR536_SENESCHAL_BINDING_ID_INVALID');
        $disposition = strtoupper(trim($disposition)); $rationale = trim($rationale); $limitations = $this->strings($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale || ('ADOPTED_WITH_LIMITATIONS' === $disposition && [] === $limitations)) throw new \InvalidArgumentException('CUR537_ADOPTION_DISPOSITION_INVALID');

        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'CUR538_ADOPTION_DECISION_OPENING_ABSENT');
        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR542_ADOPTION_DISPOSITION_CONFLICT');
            if (($prior['source_decision_opening']['id'] ?? null) === $openingId) {
                if (($prior['source_decision_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null) || ($prior['decision_maker']['binding_id'] ?? null) !== $seneschalBindingId || ($prior['disposition'] ?? null) !== $disposition || ($prior['rationale'] ?? null) !== $rationale || ($prior['limitations'] ?? null) !== $limitations) throw new \RuntimeException('CUR542_ADOPTION_DISPOSITION_CONFLICT');
                return $prior;
            }
        }
        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR539_SENESCHAL_OCCUPANCY_ABSENT');
        $this->validate($openingId, $opening, $authorityId, $seneschalBindingId, $seneschal);
        $this->assertSoleCurrentSeneschal($seneschal);

        $actor = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalBindingId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => $seneschal['manifestation_id'], 'occupancy_generation' => $seneschal['occupancy_generation']];
        $id = 'legate-result-adoption-disposition-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $authorityId, $actor, $disposition, $rationale, $limitations])), 0, 20);
        $adopted = in_array($disposition, ['ADOPTED', 'ADOPTED_WITH_LIMITATIONS'], true);
        return $this->save($id, [
            'schema' => 'imperium.legate-result-adoption-disposition/v1', 'disposition_id' => $id,
            'instance_id' => $opening['instance_id'], 'source_decision_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'source_reconciliation' => $opening['source_reconciliation'], 'source_reconciliation_opening' => $opening['source_reconciliation_opening'],
            'source_assessment_completion' => $opening['source_assessment_completion'], 'source_panel_readiness' => $opening['source_panel_readiness'],
            'source_issuance' => $opening['source_issuance'], 'source_evaluation_opening' => $opening['source_evaluation_opening'],
            'decision_maker' => $actor, 'result' => $opening['result'], 'contract' => $opening['contract'],
            'assessment_references' => $opening['assessment_references'], 'reconciliation' => $opening['reconciliation'],
            'disposition' => $disposition, 'rationale' => $rationale, 'limitations' => $limitations,
            'decided_at' => $decidedAt->format(DATE_ATOM), 'status' => $this->status($disposition),
            'adoption_decision_authority' => ['id' => $authorityId, 'single_use' => true, 'consumed' => true, 'continuing_authority' => false],
            'adoption_disposition_authored' => true, 'adoption_lifecycle_closed' => true,
            'result_operationally_adopted' => $adopted, 'adoption_conditional' => 'ADOPTED_WITH_LIMITATIONS' === $disposition,
            'planning_amendment_authority' => false, 'follow_up_commission_authority' => false,
            'provider_invocation_authority' => false, 'credential_use_authority' => false, 'tool_use_authority' => false,
            'operational_use_permitted' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function status(string$d):string{return match($d){'ADOPTED'=>'OPERATIONAL_ADOPTION_DISPOSITION_ADOPTED_LIFECYCLE_CLOSED_NO_ACTION_AUTHORITY','ADOPTED_WITH_LIMITATIONS'=>'OPERATIONAL_ADOPTION_DISPOSITION_ADOPTED_WITH_LIMITATIONS_LIFECYCLE_CLOSED_NO_ACTION_AUTHORITY','NOT_ADOPTED'=>'OPERATIONAL_ADOPTION_DISPOSITION_NOT_ADOPTED_LIFECYCLE_CLOSED_NO_AUTHORITY',default=>'OPERATIONAL_ADOPTION_DISPOSITION_UNRESOLVED_LIFECYCLE_CLOSED_NO_AUTHORITY'};}
    private function strings(array$v):array{$o=[];foreach($v as$x){if(!is_string($x)||''===trim($x))throw new \InvalidArgumentException('CUR537_ADOPTION_DISPOSITION_INVALID');$o[]=trim($x);}return array_values($o);}
    private function validate(string$id,array$o,string$authorityId,string$bindingId,array$s):void{$a=$o['adoption_decision_authority']??[];if(!$this->valid($o)||'imperium.legate-result-adoption-decision-opening/v1'!==($o['schema']??null)||$id!==($o['opening_id']??null)||'OPERATIONAL_ADOPTION_DECISION_AUTHORITY_OPENED_PENDING_SENESCHAL_DISPOSITION'!==($o['status']??null)||$authorityId!==($a['authority_id']??null)||true!==($a['single_use']??null)||false!==($a['consumed']??null)||true!==($a['exercisable']??null)||$bindingId!==($a['recipient']['binding_id']??null)||true===($o['adoption_disposition_authored']??null)||true===($o['result_operationally_adopted']??null)||true===($o['planning_amendment_authority']??null)||true===($o['operational_use_permitted']??null)||true===($o['execution_authority']??null)||!$this->valid($s)||$bindingId!==($s['binding_id']??null)||($a['recipient']['binding_digest']??null)!==($s['record_digest']??null)||'curia.seneschal'!==($s['seat']??null)||'ACTIVE'!==($s['status']??null)||true!==($s['sealed']??null))throw new \RuntimeException('CUR540_ADOPTION_DISPOSITION_CHAIN_INVALID');}
    private function assertSoleCurrentSeneschal(array$s):void{foreach(glob($this->occupancy.'/*.json')?:[]as$p){$x=$this->read($p,'CUR543_SENESCHAL_OCCUPANCY_CONFLICT');if('curia.seneschal'===($x['seat']??null)&&($x['binding_id']??null)!==($s['binding_id']??null)&&'ACTIVE'===($x['status']??null))throw new \RuntimeException('CUR543_SENESCHAL_OCCUPANCY_CONFLICT');}}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function valid(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}private function save(string$id,array$r):array{if(!is_dir($this->dispositions)&&!mkdir($this->dispositions,0770,true)&&!is_dir($this->dispositions))throw new \RuntimeException('CUR541_ADOPTION_DISPOSITION_PERSISTENCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->dispositions.'/'.$id.'.json';file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
