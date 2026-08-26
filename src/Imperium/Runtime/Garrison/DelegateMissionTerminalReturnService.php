<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionTerminalReturnService
{
    private string$root;
    private string$a;
    private string$t;
    private string$b;
    private string$c;
    private string$o;
    private string$r;
    private RecordReferenceValidator$validator;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root,
    ?RecordReferenceValidator$validator=null){
        $this->root=$root;
        $this->a=$root.'/var/imperium/offices/curia/delegate-mission-return-authorizations';
        $this->t=$root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns';
        $this->b=$root.'/var/imperium/mission/occupancy';
        $this->c=$root.'/var/imperium/offices/garrison/custody';
        $this->o=$root.'/var/imperium/offices/garrison/occupancy';
        $this->r=$root.'/var/imperium/offices/garrison/delegate-mission-terminal-returns';
        $this->validator=$validator??new RecordReferenceValidator($root);

    }
    public function complete(string$id,
    string$authorityId,
    string$bindingId,
    \DateTimeImmutable$at):array{
        $a=$this->read($this->a.'/'.$id.'.json',
        'GA300_DELEGATE_RETURN_AUTHORIZATION_ABSENT');
        $coordinator=new DelegateMissionTerminalTransitionCoordinator($this->root);
        if(null!==($recovered=$coordinator->resumeForAuthorization($id))){
            if(!$this->ok($a)||
            ($a['garrison_terminal_return_authority']['authority_id']??null)!==$authorityId||
            ($recovered['source_return_authorization']['digest']??null)!==($a['record_digest']??null)||
            ($recovered['constable']['binding_id']??null)!==$bindingId)throw new \RuntimeException('GA309_DELEGATE_TERMINAL_RETURN_CONFLICT');
            return$recovered;

        }
        foreach(glob($this->r.'/*.json')?:[]as$p){
            $x=$this->read($p,
            'GA309_DELEGATE_TERMINAL_RETURN_CONFLICT');
            if(($x['source_return_authorization']['id']??null)===$id){
                if(!$this->ok($x)||
                ($x['source_return_authorization']['digest']??null)!==($a['record_digest']??null)||
                ($x['constable']['binding_id']??null)!==$bindingId||
                ($a['garrison_terminal_return_authority']['authority_id']??null)!==$authorityId)throw new \RuntimeException('GA309_DELEGATE_TERMINAL_RETURN_CONFLICT');
                return$x;

            }

        }
        $t=$this->source($this->t,
        $a['source_turn']??[],
        'GA301_DELEGATE_TURN_ABSENT');
        $b=$this->source($this->b,
        $a['source_binding']??[],
        'GA302_DELEGATE_BINDING_ABSENT');
        $c=$this->read($this->c.'/'.($a['operational_custody']['id']??'').'.json',
        'GA303_DELEGATE_CUSTODY_ABSENT');
        $o=$this->read($this->o.'/'.$bindingId.'.json',
        'GA304_DELEGATE_CONSTABLE_ABSENT');
        $auth=$a['garrison_terminal_return_authority']??[];
        if(!$this->ok($a)||
        !$this->ok($t)||
        !$this->ok($b)||
        !$this->ok($c)||
        !$this->ok($o)||
        'DELEGATE_MISSION_RETURN_AUTHORIZED_PENDING_GARRISON_TERMINAL_TRANSITION'!==($a['status']??null)||
        $bindingId!==($o['binding_id']??null)||
        ($a['instance_id']??null)!==($o['instance_id']??null)||
        'garrison.constable'!==($o['seat']??null)||
        'ACTIVE'!==($o['status']??null)||
        true!==($o['delegate_mission_terminal_return_authority']??null)||
        true===($o['execution_authority']??null)||
        $authorityId!==($auth['authority_id']??null)||
        true!==($auth['authority_single_use']??null)||
        true!==($auth['authority_exercisable']??null)||
        false!==($auth['consumed']??null)||
        'DELEGATE_MISSION_DEPLOYED_BOUND'!==($c['custody_state']??null)||
        false!==($c['available']??null)||
        ($a['operational_custody']['digest']??null)!==$c['record_digest']||
        ($a['target']['manifestation_id']??null)!==($c['operational_custodian']['manifestation_id']??null)||
        ($a['target']['manifestation_id']??null)!==$b['manifestation_id']||
        true!==($b['seat_bound']??null)||
        true!==($t['maximum_turns_consumed']??null)||
        true===($t['continuing_turn_authority']??null))throw new \RuntimeException('GA305_DELEGATE_TERMINAL_RETURN_CHAIN_INVALID');
        $actor=['seat'=>'garrison.constable',
        'binding_id'=>$bindingId,
        'binding_digest'=>$o['record_digest'],
        'manifestation_id'=>$o['manifestation_id'],
        'occupancy_generation'=>$o['occupancy_generation']];
        $restored=$c;
        unset($restored['record_digest'],
        $restored['operational_custodian'],
        $restored['source_deployment_authorization']);
        $restored['custody_state']='ADMITTED_HELD';
        $restored['available']=true;
        $restored['execution_authority']=false;
        $restored['last_retired_delegate_manifestation_id']=$b['manifestation_id'];
        $restored['record_digest']=hash('sha256',
        CanonicalJson::encode($restored));
        $unbound=$b;
        unset($unbound['record_digest']);
        $unbound['status']='DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED';
        $unbound['seat_bound']=false;
        $unbound['operational_use_permitted']=false;
        $unbound['deployment_authorization_pending']=false;
        $unbound['retired_at']=$at->format(DATE_ATOM);
        foreach(['deployment_authority',
        'custody_transfer_authority',
        'tool_use_authority',
        'credential_use_authority',
        'perimeter_crossing_authority',
        'external_action_authority',
        'execution_authority']as$f)$unbound[$f]=false;
        $unbound['record_digest']=hash('sha256',
        CanonicalJson::encode($unbound));
        $rid='delegate-mission-terminal-return-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $a['record_digest'],
        $t['record_digest'],
        $b['record_digest'],
        $c['record_digest'],
        $restored['record_digest'],
        $unbound['record_digest'],
        $actor])),
        0,
        20);
        $record=$this->save($rid,
        ['schema'=>'imperium.garrison-delegate-mission-terminal-return/v1',
        'terminal_id'=>$rid,
        'instance_id'=>$a['instance_id'],
        'source_return_authorization'=>['id'=>$id,
        'digest'=>$a['record_digest']],
        'source_turn'=>$a['source_turn'],
        'source_binding'=>['id'=>$b['binding_id'],
        'prior_digest'=>$b['record_digest'],
        'terminal_digest'=>$unbound['record_digest']],
        'constable'=>$actor,
        'target'=>$a['target'],
        'result'=>$a['result'],
        'termination_contract'=>$a['termination_contract'],
        'prior_custody'=>['id'=>$c['custody_id'],
        'digest'=>$c['record_digest'],
        'state'=>$c['custody_state'],
        'available'=>$c['available']],
        'restored_custody'=>['id'=>$restored['custody_id'],
        'digest'=>$restored['record_digest'],
        'state'=>$restored['custody_state'],
        'available'=>$restored['available']],
        'completed_at'=>$at->format(DATE_ATOM),
        'status'=>'DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL',
        'return_authority_consumed'=>true,
        'unbinding_authority_consumed'=>true,
        'custody_restoration_authority_consumed'=>true,
        'retirement_authority_consumed'=>true,
        'returned'=>true,
        'seat_bound'=>false,
        'custody_restored'=>true,
        'manifestation_retired'=>true,
        'credential_lease_active'=>false,
        'operational_use_authority'=>false,
        'provider_invocation_authority'=>false,
        'credential_use_authority'=>false,
        'tool_use_authority'=>false,
        'perimeter_crossing_authority'=>false,
        'external_action_authority'=>false,
        'execution_authority'=>false,
        'continuing_authority'=>false,
        'redeployment_authority'=>false,
        'reuse_authority'=>false,
        'sealed'=>true]);
        return$coordinator->run($id,
        $rid,
        $record,
        $c,
        $restored,
        $b,
        $unbound);

    }
    private function source(string$d,
    array$r,
    string$e):array{
        return$this->validator->resolve($d,
        $r,
        $e,
        'GA305_DELEGATE_TERMINAL_RETURN_CHAIN_INVALID');

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
        $r['record_digest']=hash('sha256',
        CanonicalJson::encode($r));
        return$r;

    }

}

