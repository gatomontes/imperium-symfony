<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;

final readonly class ProviderAccessAssertionService
{
    private const STATUSES = ['ACCESS_AVAILABLE', 'ACCESS_UNAVAILABLE', 'ACCESS_UNVERIFIED', 'ACCESS_RESTRICTED'];
    private string $directory;

    public function __construct(string $projectDir, private ProviderAccessProbe $probe)
    {
        $this->directory = $projectDir.'/var/imperium/offices/clavium/provider-access-assertions';
    }

    public function assert(string $provider, string $credentialRef, array $scope, \DateTimeImmutable $observedAt, \DateTimeImmutable $expiresAt, array $locksmithBinding): array
    {
        if (!$this->identifier($provider) || !str_starts_with($credentialRef, 'clavium://') || !$this->stringList($scope)
            || $expiresAt <= $observedAt) throw new \InvalidArgumentException('CL01_ACCESS_ASSERTION_REQUEST_INVALID');
        $scope=array_values(array_unique($scope));
        if (!$this->digestMatches($locksmithBinding) || 'imperium.clavium-locksmith-occupancy/v1' !== ($locksmithBinding['schema'] ?? null)
            || 'clavium' !== ($locksmithBinding['office'] ?? null) || 'clavium.locksmith' !== ($locksmithBinding['seat'] ?? null)
            || 'ACTIVE' !== ($locksmithBinding['status'] ?? null) || true !== ($locksmithBinding['provider_access_assertion_authority'] ?? null)
            || true === ($locksmithBinding['credential_disclosure_authority'] ?? null) || true === ($locksmithBinding['provider_invocation_authority'] ?? null)
            || true === ($locksmithBinding['execution_authority'] ?? null)
        ) throw new \RuntimeException('CL02_LOCKSMITH_AUTHORITY_INVALID');

        $observation = $this->probe->observe($provider, $credentialRef, $scope);
        if (array_keys($observation) !== ['status', 'method', 'evidence', 'restrictions']
            || !in_array($observation['status'] ?? null, self::STATUSES, true)
            || !is_string($observation['method']) || '' === trim($observation['method'])
            || !is_array($observation['evidence']) || !$this->safeEvidence($observation['evidence'])
            || !$this->stringList($observation['restrictions'], true)
            || ('ACCESS_RESTRICTED' === $observation['status'] && [] === $observation['restrictions'])
        ) throw new \RuntimeException('CL03_ACCESS_OBSERVATION_INVALID');

        $issuer = ['office' => 'clavium', 'officer' => 'locksmith', 'seat' => 'clavium.locksmith',
            'binding_id' => $locksmithBinding['binding_id'], 'manifestation_id' => $locksmithBinding['manifestation_id'],
            'occupancy_generation' => $locksmithBinding['occupancy_generation']];
        $id='clavium-provider-access-'.substr(hash('sha256',CanonicalJson::encode([$locksmithBinding['instance_id'],$issuer,$provider,$credentialRef,$scope,$observedAt->format(\DateTimeInterface::ATOM),$expiresAt->format(\DateTimeInterface::ATOM),$observation])),0,20);
        return $this->persist($id,[
            'schema'=>'imperium.clavium-provider-access-assertion/v1','assertion_id'=>$id,'instance_id'=>$locksmithBinding['instance_id'],
            'issuer'=>$issuer,'provider'=>$provider,'credential_ref'=>$credentialRef,'scope'=>$scope,
            'observation'=>['method'=>$observation['method'],'observed_at'=>$observedAt->format(\DateTimeInterface::ATOM),'evidence'=>$observation['evidence']],
            'status'=>$observation['status'],'checkpoint'=>'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY','restrictions'=>$observation['restrictions'],
            'revalidation'=>['expires_at'=>$expiresAt->format(\DateTimeInterface::ATOM),'conditions'=>['credential rotation','scope change','provider configuration change','expiry']],
            'credential_possession_transferred'=>false,'credential_use_authority'=>false,'credential_disclosure_authority'=>false,
            'provider_invocation_authority'=>false,'model_admissibility_authority'=>false,'model_selection_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }

    private function identifier(mixed$value):bool{return is_string($value)&&1===preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:@\/-]*$/',$value);}
    private function stringList(mixed$v,bool$empty=false):bool{return is_array($v)&&($empty||[]!==$v)&&[]===array_filter($v,static fn($x)=>!is_string($x)||''===trim($x));}
    private function safeEvidence(array$v):bool{foreach($v as$k=>$x){if(is_string($k)&&in_array(strtolower($k),['secret','token','api_key','apikey','password','credential'],true))return false;if(is_array($x)){if(!$this->safeEvidence($x))return false;}elseif(!(is_bool($x)||is_int($x)||is_float($x)||null===$x))return false;}return true;}
    private function digestMatches(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
    private function persist(string$id,array$r):array{$r['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));if(!is_dir($this->directory)&&!mkdir($this->directory,0770,true)&&!is_dir($this->directory))throw new \RuntimeException('CL04_ACCESS_ASSERTION_PERSISTENCE_FAILED');$p=$this->directory.'/'.$id.'.json';if(is_file($p)){$x=$this->read($p,'CL05_ACCESS_ASSERTION_REPLAY_CONFLICT');if(CanonicalJson::encode($x)!==CanonicalJson::encode($r))throw new \RuntimeException('CL05_ACCESS_ASSERTION_REPLAY_CONFLICT');return$x;}if(false===file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX))throw new \RuntimeException('CL04_ACCESS_ASSERTION_PERSISTENCE_FAILED');return$r;}
}
