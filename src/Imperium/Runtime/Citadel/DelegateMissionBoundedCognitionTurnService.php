<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderInvocationClaimService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionBoundedCognitionTurnService
{
    private string$a;
    private string$c;
    private string$b;
    private string$t;
    private string$r;
    private ProviderInvocationClaimService$claims;
    private ImmutableRecordStore$records;
    private RecordReferenceValidator$validator;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root,
    private DelegateMissionCognitionGateway$gateway,
    ?ProviderInvocationClaimService$claims=null,
    ?ImmutableRecordStore$records=null,
    ?RecordReferenceValidator$validator=null){
        $this->a=$root.'/var/imperium/offices/clavium/delegate-mission-provider-invocation-activations';
        $this->c=$root.'/var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions';
        $this->b=$root.'/var/imperium/offices/conscription/delegate-mission-model-bindings';
        $this->t=$root.'/var/imperium/offices/clavium/delegate-mission-model-access-attestations';
        $this->r=$root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns';
        $this->claims=$claims??new ProviderInvocationClaimService($root);
        $this->records=$records??new ImmutableRecordStore($root,
        new AtomicTransition($root));
        $this->validator=$validator??new RecordReferenceValidator($root);

    }

 public function execute(string$id,
    string$authorityId,
    \DateTimeImmutable$at):array{
        $a=$this->read($this->a.'/'.$id.'.json',
        'CT300_DELEGATE_ACTIVATION_ABSENT');
        foreach(glob($this->r.'/*.json')?:[]as$p){
            $x=$this->read($p,
            'CT309_DELEGATE_TURN_CONFLICT');
            if(($x['source_activation']['id']??null)===$id){
                if(!$this->ok($x)||
                ($x['source_activation']['digest']??null)!==($a['record_digest']??null)||
                ($x['turn_authority']['id']??null)!==$authorityId)throw new \RuntimeException('CT309_DELEGATE_TURN_CONFLICT');
                return$x;

            }

        }
        $c=$this->read($this->c.'/'.($a['target']['commission_id']??'').'.json',
        'CT301_DELEGATE_COMMISSION_ABSENT');
        $b=$this->source($this->b,
        $a['source_model_binding']??[],
        'CT304_DELEGATE_MODEL_BINDING_ABSENT');
        $t=$this->source($this->t,
        $a['source_access_attestation']??[],
        'CT305_DELEGATE_ACCESS_ATTESTATION_ABSENT');
        $auth=$a['bounded_cognition_turn_authority']??[];
        $lease=$a['credential_lease']??[];
        if(!$this->ok($a)||
        !$this->ok($c)||
        !$this->ok($b)||
        !$this->ok($t)||
        'DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN'!==($a['status']??null)||
        $authorityId!==($auth['authority_id']??null)||
        true!==($auth['authority_single_use']??null)||
        true!==($auth['authority_exercisable']??null)||
        false!==($auth['consumed']??null)||
        1!==($auth['maximum_turns']??null)||
        true!==($lease['authority_single_use']??null)||
        false!==($lease['consumed']??null)||
        new \DateTimeImmutable($lease['expires_at'])<=$at||
        true!==($a['provider_invocation_authority']??null)||
        true!==($a['credential_use_authority']??null)||
        true===($lease['credential_reference_disclosed']??null)||
        true===($lease['credential_possession_transferred']??null)||
        ($a['target']??null)!==($b['target']??null)||
        ($a['model']['provider_model_version']??null)!==($b['provider_model_version']??null)||
        ($a['model']['runtime_binding']??null)!==($b['runtime_binding']??null)||
        ($a['model']['configuration']??null)!==($b['configuration']??null)||
        ($t['model_binding']['digest']??null)!==$b['record_digest']||
        ($t['runtime_binding']??null)!==($b['runtime_binding']??null)||
        ($lease['provider']??null)!==($b['runtime_binding']['provider']??null)||
        ($a['target']['manifestation_id']??null)!==($c['manifestation_id']??null)||
        ($a['target']['occupancy_generation']??null)!==($c['occupancy_generation']??null)||
        ($b['source_commission']['digest']??null)!==$c['record_digest']||
        1!==($c['commission_contract']['turn_sequence']??null)||
        1!==($c['commission_contract']['maximum_iterations']??null)||
        'INTERNAL_REASONING_ONLY'!==($c['commission_contract']['cognition_mode']??null)||
        true===($c['commission_contract']['resource_release_allowed']??null)||
        true===($c['commission_contract']['provider_invocation_allowed']??null)||
        true===($c['commission_contract']['external_action_allowed']??null))throw new \RuntimeException('CT302_DELEGATE_TURN_CHAIN_INVALID');
        $claim=$this->claims->claim($id,
        $authorityId,
        $at);
        $payload=$this->gateway->invoke($claim,
        $a,
        $c);
        if(!$this->validPayload($payload))throw new \RuntimeException('CT303_DELEGATE_TURN_RESULT_INVALID');
        $rid='delegate-mission-bounded-cognition-turn-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $a['record_digest'],
        $authorityId,
        $payload])),
        0,
        20);
        $next='delegate-mission-cognition-result-disposition-authority-'.substr(hash('sha256',
        CanonicalJson::encode([$rid,
        $payload,
        $c['record_digest']])),
        0,
        20);
        return$this->save($rid,
        ['schema'=>'imperium.citadel-delegate-mission-bounded-cognition-turn/v1',
        'turn_id'=>$rid,
        'instance_id'=>$a['instance_id'],
        'source_invocation_claim'=>['id'=>$claim['claim_id'],
        'digest'=>$claim['record_digest']],
        'source_activation'=>['id'=>$id,
        'digest'=>$a['record_digest']],
        'source_commission'=>['id'=>$c['commission_id'],
        'digest'=>$c['record_digest']],
        'source_model_binding'=>$a['source_model_binding'],
        'source_access_attestation'=>$a['source_access_attestation'],
        'target'=>$a['target'],
        'model'=>$a['model'],
        'turn_authority'=>['id'=>$authorityId,
        'consumed'=>true,
        'continuing_authority'=>false],
        'credential_lease'=>['id'=>$lease['lease_id'],
        'consumed'=>true,
        'continuing_authority'=>false],
        'result'=>$payload,
        'performed_at'=>$at->format(DATE_ATOM),
        'status'=>'DELEGATE_MISSION_BOUNDED_COGNITION_TURN_COMPLETE_PENDING_CURIA_DISPOSITION',
        'provider_invoked'=>true,
        'cognition_performed'=>true,
        'maximum_turns_consumed'=>true,
        'curia_result_disposition_authority'=>['authority_id'=>$next,
        'authority_single_use'=>true,
        'authority_exercisable'=>true,
        'holder'=>'curia.seneschal',
        'consumed'=>false],
        'credential_use_authority'=>false,
        'provider_invocation_authority'=>false,
        'tool_use_authority'=>false,
        'perimeter_crossing_authority'=>false,
        'external_action_authority'=>false,
        'execution_authority'=>false,
        'continuing_turn_authority'=>false,
        'sealed'=>true]);

    }

 private function source(string$d,
    array$ref,
    string$e):array{
        return$this->validator->resolve($d,
        $ref,
        $e,
        'CT302_DELEGATE_TURN_CHAIN_INVALID');

    }
    private function validPayload(array$p):bool{
        return array_keys($p)===['disposition',
        'output',
        'evidence_references',
        'uncertainties',
        'stop_condition_triggered',
        'stop_rationale']&&
        in_array($p['disposition'],
        ['COMPLETED',
        'STOPPED',
        'FAILED'],
        true)&&
        is_string($p['output'])&&
        is_array($p['evidence_references'])&&
        is_array($p['uncertainties'])&&
        is_bool($p['stop_condition_triggered'])&&
        (null===$p['stop_rationale']||
        is_string($p['stop_rationale']));

    }
    private function read($p,
    $e):array{
        return$this->validator->read($p,
        $e);

    }
    private function ok(array$r):bool{
        return$this->validator->isIntact($r);

    }
    private function save($id,
    array$r):array{
        return$this->records->put('var/imperium/operational/delegate-mission-bounded-cognition-turns',
        $id,
        $r);

    }

}

