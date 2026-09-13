<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{BoundedFormationTransport,FormationEffectiveConfiguration,FormationWireAdapter,FormationPreparedOperation as O,FormationJournal as J};
use App\Imperium\Runtime\LaCortine\{CredentialBroker,CredentialCapability};

/** Offline infrastructure only. The model options are serialized into the exact
 * wire that dispatch consumes; the settings tuple is never their source.
 */
final class AssignmentConsumerTransport implements BoundedFormationTransport,FormationWireAdapter,FormationEffectiveConfiguration
{
    public array $configuration=['max_tokens'=>4096,'stream'=>false,'temperature'=>0,'thinking'=>['type'=>'disabled'],
        'response_format'=>['type'=>'json_object'],'message_roles'=>['system','user']];
    public array $calls=[];
    public function effectiveConfiguration(array $request,array $terms,?array $operation):array
    {
        if($operation===null){return $this->configuration;}
        $expected=$this->prepare($request,$terms);
        if(J::digest($operation)!==J::digest($expected)){throw new \RuntimeException('SYNTHETIC_EFFECTIVE_OPERATION_CHANGED');}
        return json_decode(base64_decode($operation['wire_bytes_base64'],true),true,512,JSON_THROW_ON_ERROR)['configuration'];
    }
    public function inspect(array $request,array $terms):array
    {
        if($terms['provider']!=='deepseek' || !in_array($terms['model'],['deepseek-v4-flash','deepseek-v4-pro'],true)
            || $terms['destination']!=='in-process:no-network'){throw new \RuntimeException('SYNTHETIC_OFFLINE_ONLY');}
        $input=strlen(CanonicalJson::encode($request));
        return ['calls'=>1,'input_tokens'=>$input,'output_tokens'=>4096,'cost_microusd'=>$input+4096,'milliseconds'=>1000];
    }
    private function wire(array $request,array $terms):string
    {return CanonicalJson::encode(['model'=>$terms['model'],'configuration'=>$this->configuration,'request'=>$request]);}
    public function prepare(array $request,array $terms):array
    {return O::build($request,$terms,$this->wire($request,$terms),$this->inspect($request,$terms));}
    public function invoke(array $claim,array $request,array $terms):array
    {
        if($claim['request_digest']!==J::digest($request) || $claim['maximum']!==$this->inspect($request,$terms)){throw new \RuntimeException('SYNTHETIC_CLAIM');}
        return $this->response($this->wire($request,$terms),$claim['maximum']);
    }
    public function dispatch(array $operation,mixed $authentication):array
    {
        if($authentication!=='synthetic-r1-auth' || $operation['destination']!=='in-process:no-network'){throw new \RuntimeException('SYNTHETIC_CONTEXT');}
        $wire=base64_decode($operation['wire_bytes_base64'],true);
        if(hash('sha256',$wire)!==$operation['wire_sha256']){throw new \RuntimeException('SYNTHETIC_WIRE');}
        return $this->response($wire,$operation['maximum'])+['operation_digest'=>J::digest($operation),'provenance'=>$operation['authorization']['adapter']];
    }
    private function response(string $wire,array $maximum):array
    {
        $this->calls[]=json_decode($wire,true,512,JSON_THROW_ON_ERROR);
        $response=json_encode(CitadelFormationFixture::understanding(),JSON_THROW_ON_ERROR);
        return ['response'=>$response,'provider_response_id'=>'synthetic-r1-'.count($this->calls),
            'usage'=>['calls'=>1,'input_tokens'=>$maximum['input_tokens'],'output_tokens'=>strlen($response),
                'cost_microusd'=>$maximum['input_tokens']+strlen($response),'milliseconds'=>1]];
    }
}

final class AssignmentConsumerCredentials implements CredentialBroker
{
    public int $issues=0;
    public int $consumptions=0;
    public ?\Closure $onConsume=null;
    public function issue(string $credentialRef,string $commissionId,string $operation,\DateTimeImmutable $expiresAt,int $maxUses=1):CredentialCapability
    {++$this->issues;return new CredentialCapability('synthetic-r1',$credentialRef,$commissionId,$operation,$expiresAt,$maxUses);}
    public function consume(CredentialCapability $capability,callable $providerOperation):mixed
    {++$this->consumptions;if($this->onConsume){($this->onConsume)();}return $providerOperation('synthetic-r1-auth');}
}
