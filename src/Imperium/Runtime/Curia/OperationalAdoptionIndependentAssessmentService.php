<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionIndependentAssessmentService
{
    private const DISPOSITIONS = ['SUPPORTS_ADOPTION', 'SUPPORTS_WITH_LIMITATIONS', 'DOES_NOT_SUPPORT_ADOPTION', 'UNRESOLVED'];

    private string $readiness;
    private string $issuances;
    private string $commissions;
    private string $dispositions;
    private string $occupancy;
    private string $assessments;
    private string $completion;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $base = $root.'/var/imperium/operational/';
        $this->readiness = $base.'legate-result-adoption-assessment-panel-readiness';
        $this->issuances = $base.'legate-result-adoption-assessment-issuances';
        $this->commissions = $base.'legate-result-adoption-assessment-commissions';
        $this->dispositions = $base.'legate-result-adoption-assessment-commission-dispositions';
        $this->occupancy = $base.'occupancy';
        $this->assessments = $base.'legate-result-adoption-independent-assessments';
        $this->completion = $base.'legate-result-adoption-assessment-completions';
    }

    public function assess(string $readinessId, string $jurisdiction, string $authorityId, string $curialisBindingId, array $authoredAssessment, \DateTimeImmutable $assessedAt): array
    {
        if (!preg_match('/^legate-result-adoption-assessment-panel-readiness-[a-f0-9]{20}$/', $readinessId)) throw new \InvalidArgumentException('CUR491_ASSESSMENT_PANEL_READINESS_ID_INVALID');
        if (!preg_match('/^[A-Z][A-Z_]{2,127}$/', $jurisdiction)) throw new \InvalidArgumentException('CUR492_ASSESSMENT_JURISDICTION_INVALID');
        if (!preg_match('/^adoption-assessment-authority-[a-f0-9]{20}$/', $authorityId)) throw new \InvalidArgumentException('CUR493_ASSESSMENT_AUTHORITY_ID_INVALID');
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $curialisBindingId)) throw new \InvalidArgumentException('CUR494_CURIALIS_BINDING_ID_INVALID');
        $assessment = $this->normalizeAssessment($authoredAssessment);

        $panel = $this->read($this->readiness.'/'.$readinessId.'.json', 'CUR495_ASSESSMENT_PANEL_READINESS_ABSENT');
        $issuance = $this->source($this->issuances, $panel['source_issuance'] ?? [], 'CUR496_ASSESSMENT_ISSUANCE_ABSENT');
        $commission = $this->source($this->commissions, $issuance['commissions'][$jurisdiction] ?? [], 'CUR497_ASSESSMENT_COMMISSION_ABSENT');
        $acceptance = $this->acceptedDisposition($commission['commission_id']);
        $occupant = $this->read($this->occupancy.'/'.$curialisBindingId.'.json', 'CUR498_CURIALIS_OCCUPANCY_ABSENT');
        $this->validate($readinessId, $panel, $issuance, $commission, $acceptance, $jurisdiction, $authorityId, $curialisBindingId, $occupant);

        foreach (glob($this->assessments.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR503_INDEPENDENT_ASSESSMENT_CONFLICT');
            if (($prior['assessment_authority']['id'] ?? null) === $authorityId) {
                if (($prior['assessment_authority']['jurisdiction'] ?? null) !== $jurisdiction || ($prior['assessor']['binding_id'] ?? null) !== $curialisBindingId || ($prior['assessment'] ?? null) !== $assessment) throw new \RuntimeException('CUR503_INDEPENDENT_ASSESSMENT_CONFLICT');
                return ['assessment' => $prior, 'completion' => $this->existingCompletion($readinessId)];
            }
        }

        $assessor = ['seat' => $occupant['seat'], 'binding_id' => $curialisBindingId, 'binding_digest' => $occupant['record_digest'], 'manifestation_id' => $occupant['manifestation_id'], 'occupancy_generation' => $occupant['occupancy_generation'], 'officer_class' => $occupant['officer_class']];
        $assessmentId = 'legate-result-adoption-independent-assessment-'.substr(hash('sha256', CanonicalJson::encode([$readinessId, $jurisdiction, $authorityId, $assessor, $assessment])), 0, 20);
        $record = $this->save($this->assessments, $assessmentId, [
            'schema' => 'imperium.legate-result-adoption-independent-assessment/v1', 'assessment_id' => $assessmentId,
            'instance_id' => $panel['instance_id'], 'source_panel_readiness' => ['id' => $readinessId, 'digest' => $panel['record_digest']],
            'source_issuance' => $panel['source_issuance'], 'source_commission' => ['id' => $commission['commission_id'], 'digest' => $commission['record_digest']],
            'source_commission_acceptance' => ['id' => $acceptance['disposition_id'], 'digest' => $acceptance['record_digest']],
            'jurisdiction' => $jurisdiction, 'question' => $commission['question'], 'assessor' => $assessor,
            'assessment' => $assessment, 'assessed_at' => $assessedAt->format(DATE_ATOM),
            'status' => 'ADOPTION_INDEPENDENT_ASSESSMENT_SEALED_PENDING_PANEL_COMPLETION',
            'assessment_authority' => ['id' => $authorityId, 'jurisdiction' => $jurisdiction, 'single_use' => true, 'consumed' => true, 'continuing_authority' => false],
            'sibling_assessments_disclosed' => false, 'assessment_sealed' => true, 'result_operationally_adopted' => false,
            'planning_amendment_authority' => false, 'provider_invocation_authority' => false, 'credential_use_authority' => false,
            'tool_use_authority' => false, 'operational_use_permitted' => false, 'external_action_authority' => false,
            'execution_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);

        return ['assessment' => $record, 'completion' => $this->sealCompletion($panel, $issuance, $assessedAt)];
    }

    private function normalizeAssessment(array $assessment): array
    {
        $disposition = strtoupper(trim((string) ($assessment['disposition'] ?? ''))); $rationale = trim((string) ($assessment['rationale'] ?? ''));
        $evidence = $this->strings($assessment['evidence_references'] ?? null); $limitations = $this->strings($assessment['limitations'] ?? []); $uncertainties = $this->strings($assessment['uncertainties'] ?? []);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale || [] === $evidence || ($assessment !== array_intersect_key($assessment, array_flip(['disposition','rationale','evidence_references','limitations','uncertainties'])))) throw new \InvalidArgumentException('CUR499_AUTHORED_ASSESSMENT_INVALID');
        return ['disposition' => $disposition, 'rationale' => $rationale, 'evidence_references' => $evidence, 'limitations' => $limitations, 'uncertainties' => $uncertainties];
    }
    private function strings(mixed $values): array { if (!is_array($values)) throw new \InvalidArgumentException('CUR499_AUTHORED_ASSESSMENT_INVALID'); $out=[]; foreach($values as$v){if(!is_string($v)||''===trim($v))throw new \InvalidArgumentException('CUR499_AUTHORED_ASSESSMENT_INVALID');$out[]=trim($v);} return array_values($out); }

    private function validate(string $id,array $panel,array $issuance,array $commission,array $acceptance,string $jurisdiction,string $authorityId,string $bindingId,array $occupant):void
    {
        $authority=$panel['assessment_authorities'][$jurisdiction]??[];
        if(!$this->valid($panel)||'imperium.legate-result-adoption-assessment-panel-readiness/v1'!==($panel['schema']??null)||$id!==($panel['readiness_id']??null)||true!==($panel['all_commissions_accepted']??null)||true===($panel['evaluation_closed']??null)
            ||'ADOPTION_ASSESSMENT_PANEL_ACCEPTED_AUTHORITIES_OPENED_PENDING_INDEPENDENT_ASSESSMENTS'!==($panel['status']??null)||$authorityId!==($authority['authority_id']??null)||true!==($authority['single_use']??null)||false!==($authority['consumed']??null)||true!==($authority['exercisable']??null)
            ||$bindingId!==($authority['recipient']['binding_id']??null)||$jurisdiction!==($commission['jurisdiction']??null)||'ACCEPTED'!==($acceptance['decision']??null)||$bindingId!==($acceptance['recipient']['binding_id']??null)
            ||!$this->valid($occupant)||$bindingId!==($occupant['binding_id']??null)||($authority['recipient']['binding_digest']??null)!==($occupant['record_digest']??null)||'ACTIVE'!==($occupant['status']??null)||true!==($occupant['sealed']??null))throw new \RuntimeException('CUR500_INDEPENDENT_ASSESSMENT_CHAIN_INVALID');
    }

    private function sealCompletion(array $panel,array $issuance,\DateTimeImmutable $at):?array
    {
        $found=[];foreach(array_keys($issuance['commissions'])as$j){foreach(glob($this->assessments.'/*.json')?:[]as$p){$r=$this->read($p,'CUR503_INDEPENDENT_ASSESSMENT_CONFLICT');if(($r['source_panel_readiness']['id']??null)===$panel['readiness_id']&&($r['jurisdiction']??null)===$j){$found[$j]=$r;break;}}}if(count($found)<count($issuance['commissions']))return null;
        $id='legate-result-adoption-assessment-completion-'.substr(hash('sha256',CanonicalJson::encode([$panel['readiness_id'],array_map(static fn(array$r):string=>$r['record_digest'],$found)])),0,20);
        return $this->save($this->completion,$id,['schema'=>'imperium.legate-result-adoption-assessment-completion/v1','completion_id'=>$id,'instance_id'=>$panel['instance_id'],'source_panel_readiness'=>['id'=>$panel['readiness_id'],'digest'=>$panel['record_digest']],'assessments'=>array_map(static fn(array$r):array=>['id'=>$r['assessment_id'],'digest'=>$r['record_digest'],'jurisdiction'=>$r['jurisdiction']],$found),'completed_at'=>$at->format(DATE_ATOM),'status'=>'ADOPTION_INDEPENDENT_ASSESSMENTS_SEALED_PENDING_SENESCHAL_RECONCILIATION','assessment_contents_disclosed'=>false,'voting_performed'=>false,'aggregation_performed'=>false,'result_operationally_adopted'=>false,'planning_amendment_authority'=>false,'external_action_authority'=>false,'execution_authority'=>false,'sealed'=>true]);
    }
    private function acceptedDisposition(string$commissionId):array{foreach(glob($this->dispositions.'/*.json')?:[]as$p){$r=$this->read($p,'CUR501_COMMISSION_ACCEPTANCE_ABSENT');if(($r['source_commission']['id']??null)===$commissionId)return$r;}throw new \RuntimeException('CUR501_COMMISSION_ACCEPTANCE_ABSENT');}
    private function existingCompletion(string$id):?array{foreach(glob($this->completion.'/*.json')?:[]as$p){$r=$this->read($p,'CUR503_INDEPENDENT_ASSESSMENT_CONFLICT');if(($r['source_panel_readiness']['id']??null)===$id)return$r;}return null;}
    private function source(string$dir,array$ref,string$error):array{$r=$this->read($dir.'/'.($ref['id']??'').'.json',$error);if(!$this->valid($r)||($ref['digest']??null)!==$r['record_digest'])throw new \RuntimeException('CUR500_INDEPENDENT_ASSESSMENT_CHAIN_INVALID');return$r;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function valid(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}private function save(string$d,string$id,array$r):array{if(!is_dir($d)&&!mkdir($d,0770,true)&&!is_dir($d))throw new \RuntimeException('CUR502_INDEPENDENT_ASSESSMENT_PERSISTENCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$d.'/'.$id.'.json';if(is_file($p)){ $x=$this->read($p,'CUR503_INDEPENDENT_ASSESSMENT_CONFLICT');if(CanonicalJson::encode($x)!==CanonicalJson::encode($r))throw new \RuntimeException('CUR503_INDEPENDENT_ASSESSMENT_CONFLICT');return$x;}file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
