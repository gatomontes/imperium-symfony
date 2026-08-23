<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionReconciliationOpeningService
{
    private string $completions;
    private string $readiness;
    private string $issuances;
    private string $evaluationOpenings;
    private string $assessments;
    private string $occupancy;
    private string $reconciliationOpenings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $base = $root.'/var/imperium/operational/';
        $this->completions = $base.'legate-result-adoption-assessment-completions';
        $this->readiness = $base.'legate-result-adoption-assessment-panel-readiness';
        $this->issuances = $base.'legate-result-adoption-assessment-issuances';
        $this->evaluationOpenings = $base.'legate-result-adoption-evaluation-openings';
        $this->assessments = $base.'legate-result-adoption-independent-assessments';
        $this->occupancy = $base.'occupancy';
        $this->reconciliationOpenings = $base.'legate-result-adoption-reconciliation-openings';
    }

    public function open(string $completionId, string $seneschalBindingId, \DateTimeImmutable $openedAt): array
    {
        if (!preg_match('/^legate-result-adoption-assessment-completion-[a-f0-9]{20}$/', $completionId)) throw new \InvalidArgumentException('CUR504_ASSESSMENT_COMPLETION_ID_INVALID');
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) throw new \InvalidArgumentException('CUR505_SENESCHAL_BINDING_ID_INVALID');

        $completion = $this->read($this->completions.'/'.$completionId.'.json', 'CUR506_ASSESSMENT_COMPLETION_ABSENT');
        $panel = $this->source($this->readiness, $completion['source_panel_readiness'] ?? [], 'CUR507_PANEL_READINESS_ABSENT');
        $issuance = $this->source($this->issuances, $panel['source_issuance'] ?? [], 'CUR508_ASSESSMENT_ISSUANCE_ABSENT');
        $evaluation = $this->source($this->evaluationOpenings, $issuance['source_evaluation_opening'] ?? [], 'CUR509_EVALUATION_OPENING_ABSENT');
        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR510_SENESCHAL_OCCUPANCY_ABSENT');
        $admitted = $this->assessments($completion);
        $this->validate($completionId, $completion, $panel, $issuance, $evaluation, $seneschalBindingId, $seneschal, $admitted);
        $this->assertSoleCurrentSeneschal($seneschal);

        foreach (glob($this->reconciliationOpenings.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR514_RECONCILIATION_OPENING_CONFLICT');
            if (($prior['source_assessment_completion']['id'] ?? null) === $completionId) {
                if (($prior['source_assessment_completion']['digest'] ?? null) !== $completion['record_digest'] || ($prior['presiding_seneschal']['binding_id'] ?? null) !== $seneschalBindingId) throw new \RuntimeException('CUR514_RECONCILIATION_OPENING_CONFLICT');
                return $prior;
            }
        }

        $presiding = ['seat' => 'curia.seneschal', 'binding_id' => $seneschalBindingId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => $seneschal['manifestation_id'], 'occupancy_generation' => $seneschal['occupancy_generation']];
        $openingId = 'legate-result-adoption-reconciliation-opening-'.substr(hash('sha256', CanonicalJson::encode([$completionId, $completion['record_digest'], $presiding, array_map(static fn (array $r): string => $r['record_digest'], $admitted)])), 0, 20);
        $authorityId = 'legate-result-adoption-reconciliation-authority-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $presiding])), 0, 20);

        return $this->save($openingId, [
            'schema' => 'imperium.legate-result-adoption-reconciliation-opening/v1', 'opening_id' => $openingId,
            'instance_id' => $completion['instance_id'], 'source_assessment_completion' => ['id' => $completionId, 'digest' => $completion['record_digest']],
            'source_panel_readiness' => $completion['source_panel_readiness'], 'source_issuance' => $panel['source_issuance'],
            'source_evaluation_opening' => $issuance['source_evaluation_opening'], 'presiding_seneschal' => $presiding,
            'result' => $evaluation['result'], 'contract' => $evaluation['contract'],
            'admitted_assessments' => array_map(static fn (array $r): array => ['id' => $r['assessment_id'], 'digest' => $r['record_digest'], 'jurisdiction' => $r['jurisdiction'], 'assessment' => $r['assessment']], $admitted),
            'reconciliation_contract' => [
                'purpose' => 'EXPLAIN_AGREEMENT_DISAGREEMENT_LIMITATIONS_RISK_AND_UNCERTAINTY_WITHOUT_ADOPTING',
                'all_assessments_must_remain_unchanged' => true, 'all_jurisdictions_must_be_addressed' => true,
                'voting_prohibited' => true, 'aggregation_prohibited' => true, 'assessment_rewriting_prohibited' => true,
                'adoption_disposition_prohibited' => true, 'planning_amendment_prohibited' => true, 'action_authorization_prohibited' => true,
            ],
            'reconciliation_authority' => ['authority_id' => $authorityId, 'recipient' => $presiding, 'single_use' => true, 'consumed' => false, 'exercisable' => true],
            'opened_at' => $openedAt->format(DATE_ATOM),
            'status' => 'ADOPTION_ASSESSMENTS_ADMITTED_UNCHANGED_RECONCILIATION_AUTHORITY_OPENED_PENDING_SENESCHAL_RECONCILIATION',
            'reconciliation_performed' => false, 'voting_performed' => false, 'aggregation_performed' => false,
            'result_operationally_adopted' => false, 'planning_amendment_authority' => false, 'provider_invocation_authority' => false,
            'credential_use_authority' => false, 'tool_use_authority' => false, 'operational_use_permitted' => false,
            'external_action_authority' => false, 'execution_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function assessments(array $completion): array
    {
        $out=[]; foreach($completion['assessments']??[]as$ref){$record=$this->source($this->assessments,$ref,'CUR511_INDEPENDENT_ASSESSMENT_ABSENT');$out[$record['jurisdiction']]=$record;} return $out;
    }
    private function validate(string$id,array$c,array$p,array$i,array$e,string$bindingId,array$s,array$a):void
    {
        if(!$this->valid($c)||'imperium.legate-result-adoption-assessment-completion/v1'!==($c['schema']??null)||$id!==($c['completion_id']??null)||'ADOPTION_INDEPENDENT_ASSESSMENTS_SEALED_PENDING_SENESCHAL_RECONCILIATION'!==($c['status']??null)
            ||true===($c['assessment_contents_disclosed']??null)||true===($c['voting_performed']??null)||true===($c['aggregation_performed']??null)||3!==count($a)
            ||array_keys($i['commissions']??[])!==array_keys($a)||$bindingId!==($e['presiding_seneschal']['binding_id']??null)||!$this->valid($s)||$bindingId!==($s['binding_id']??null)
            ||($e['presiding_seneschal']['binding_digest']??null)!==($s['record_digest']??null)||'curia.seneschal'!==($s['seat']??null)||'ACTIVE'!==($s['status']??null)||true!==($s['sealed']??null))throw new \RuntimeException('CUR512_RECONCILIATION_OPENING_CHAIN_INVALID');
    }
    private function assertSoleCurrentSeneschal(array$s):void{foreach(glob($this->occupancy.'/*.json')?:[]as$p){$o=$this->read($p,'CUR515_SENESCHAL_OCCUPANCY_CONFLICT');if('curia.seneschal'===($o['seat']??null)&&($o['binding_id']??null)!==($s['binding_id']??null)&&'ACTIVE'===($o['status']??null))throw new \RuntimeException('CUR515_SENESCHAL_OCCUPANCY_CONFLICT');}}
    private function source(string$d,array$r,string$e):array{$x=$this->read($d.'/'.($r['id']??'').'.json',$e);if(!$this->valid($x)||($r['digest']??null)!==$x['record_digest'])throw new \RuntimeException('CUR512_RECONCILIATION_OPENING_CHAIN_INVALID');return$x;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function valid(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}private function save(string$id,array$r):array{if(!is_dir($this->reconciliationOpenings)&&!mkdir($this->reconciliationOpenings,0770,true)&&!is_dir($this->reconciliationOpenings))throw new \RuntimeException('CUR513_RECONCILIATION_OPENING_PERSISTENCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->reconciliationOpenings.'/'.$id.'.json';file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
