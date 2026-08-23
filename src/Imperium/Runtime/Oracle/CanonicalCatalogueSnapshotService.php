<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Bootstrap\CanonicalJson;

final readonly class CanonicalCatalogueSnapshotService
{
    private string $evidenceDirectory;
    private string $assertionDirectory;

    public function __construct(string $projectDir, private ModelIntelligenceLedgerService $ledger)
    {
        $this->evidenceDirectory = $projectDir.'/var/imperium/offices/oracle/admitted-model-evidence';
        $this->assertionDirectory = $projectDir.'/var/imperium/offices/clavium/provider-access-assertions';
    }

    public function seal(string $instanceId, array $entries, array $augurBinding, ?string $priorSnapshotId = null): array
    {
        if ([] === $entries) throw new \InvalidArgumentException('OR18_CANONICAL_CATALOGUE_EMPTY');
        $records=[];
        foreach($entries as$entry){
            if(!is_array($entry)||array_keys($entry)!==['evidence_id','clavium_assertion_id']||!$this->identifier($entry['evidence_id']))throw new \InvalidArgumentException('OR19_CATALOGUE_ENTRY_INVALID');
            $evidence=$this->read($this->evidenceDirectory.'/'.$entry['evidence_id'].'.json','OR20_ADMITTED_EVIDENCE_ABSENT');
            if(array_keys($evidence)!==['schema','evidence_id','instance_id','provider','model_id','model_version','knowledge_sources','claims','admissibility','research_lineage','status','admitted_by','model_research_authority','sealed','record_digest']
                ||!$this->digestMatches($evidence)||'imperium.oracle-admitted-model-evidence/v1'!==($evidence['schema']??null)
                ||$instanceId!==($evidence['instance_id']??null)||'EVIDENCE_ADMITTED'!==($evidence['status']??null)
                ||'oracle'!==($evidence['admitted_by']['office']??null)||!$this->identifier($evidence['admitted_by']['process']??null)
                ||true!==($evidence['sealed']??null)||true===($evidence['model_research_authority']??null)
            )throw new \RuntimeException('OR21_ADMITTED_EVIDENCE_INVALID');
            $assertion=null;$access='UNVERIFIED';$assertionId=$entry['clavium_assertion_id'];
            if(null!==$assertionId){
                if(!$this->identifier($assertionId))throw new \InvalidArgumentException('OR19_CATALOGUE_ENTRY_INVALID');
                $assertion=$this->read($this->assertionDirectory.'/'.$assertionId.'.json','OR22_CLAVIUM_ASSERTION_ABSENT');
                if($instanceId!==($assertion['instance_id']??null)||$evidence['provider']!==($assertion['provider']??null))throw new \RuntimeException('OR23_CATALOGUE_LINEAGE_MISMATCH');
                $access=['ACCESS_AVAILABLE'=>'ACCESSIBLE','ACCESS_RESTRICTED'=>'RESTRICTED','ACCESS_UNVERIFIED'=>'UNVERIFIED','ACCESS_UNAVAILABLE'=>'UNAVAILABLE'][$assertion['status']??'']??throw new \RuntimeException('OR24_CLAVIUM_STATUS_INVALID');
            }
            $records[]=['provider'=>$evidence['provider'],'model_id'=>$evidence['model_id'],'model_version'=>$evidence['model_version'],
                'knowledge_sources'=>$evidence['knowledge_sources'],'claims'=>$evidence['claims'],
                'accessibility'=>['status'=>$access,'clavium_assertion'=>$assertion],'admissibility'=>$evidence['admissibility'],'provenance'=>$evidence['research_lineage']];
        }
        return $this->ledger->sealSnapshot($instanceId,$records,$augurBinding,$priorSnapshotId);
    }

    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
    private function digestMatches(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,'sha256:'.hash('sha256',CanonicalJson::encode($r)));}
    private function identifier(mixed$v):bool{return is_string($v)&&1===preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:@\/-]*$/',$v)&&!str_contains($v,'..');}
}
