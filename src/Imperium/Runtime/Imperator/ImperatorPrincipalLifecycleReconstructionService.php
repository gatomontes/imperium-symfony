<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Imperator;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class ImperatorPrincipalLifecycleReconstructionService
{
    private RecordReferenceValidator $validator; private ImperatorPrincipalProvenanceFixtureStore $contracts;
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root){$this->validator=new RecordReferenceValidator($root);$this->contracts=new ImperatorPrincipalProvenanceFixtureStore($root);}
    public function reconstruct(string $versionId, \DateTimeImmutable $at):array
    {
        $path=$this->root.'/'.FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS.'/'.$versionId.'.json';
        $principal=$this->validator->requireIntact($this->validator->read($path,'PPR700_PRINCIPAL_VERSION_ABSENT'),'PPR701_PRINCIPAL_VERSION_INVALID');
        if(ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS!==array_keys($principal)||ImperatorRuntimePrincipalVersionContract::SCHEMA!==($principal['schema']??null))throw new \RuntimeException('PPR701_PRINCIPAL_VERSION_INVALID');
        $matches=[];$directory=$this->root.'/'.ImperatorPrincipalProvenanceFixtureStore::LIFECYCLE_DISPOSITIONS;
        foreach(glob($directory.'/*.json')?:[]as$file){$d=$this->validator->requireIntact($this->validator->read($file,'PPR702_LIFECYCLE_DISPOSITION_INVALID'),'PPR702_LIFECYCLE_DISPOSITION_INVALID');try{$this->contracts->assertLifecycleDisposition($d);}catch(\RuntimeException){throw new \RuntimeException('PPR702_LIFECYCLE_DISPOSITION_INVALID');}if(($d['source_principal_version']['id']??null)===$versionId&&($d['source_principal_version']['digest']??null)===$principal['record_digest']&&new \DateTimeImmutable($d['effective_at'])<=$at)$matches[]=$d;}
        if(count($matches)>1)throw new \RuntimeException('PPR703_LIFECYCLE_DISPOSITION_CONFLICT');
        $status=$principal['status'];$source='PRINCIPAL_VERSION';
        if([]!==$matches){$status=match($matches[0]['disposition']){'ACTIVATE'=>'ACTIVE','SUSPEND'=>'SUSPENDED','RENEW','SUPERSEDE'=>'SUPERSEDED','REVOKE'=>'REVOKED','EXPIRE'=>'EXPIRED','RETIRE'=>'RETIRED'};$source='LIFECYCLE_DISPOSITION';}
        elseif(new \DateTimeImmutable($principal['lifecycle']['expires_at'])<=$at){$status='EXPIRED';$source='PRINCIPAL_EXPIRY';}
        return ['principal_version'=>$principal,'effective_status'=>$status,'status_source'=>$source,'effective_disposition'=>$matches[0]??null,'reconstructed_at'=>$at->format(DATE_ATOM),'read_only'=>true,'authority_created'=>false];
    }
}
