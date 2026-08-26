<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Foundry;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaSpecificationService{
    private string$c;
    private string$s;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$p,
    private SubordinatePersonaSpecificationCognitionGateway$g){
        $this->c=$p.'/var/imperium/offices/foundry/subordinate-construction-cases';
        $this->s=$p.'/var/imperium/offices/foundry/subordinate-persona-specifications';

    }
    public function specify(string$id):array{
        if(!preg_match('/^subordinate-construction-case-[a-f0-9]{20}$/',
        $id))throw new \InvalidArgumentException('F110_SUBORDINATE_CASE_ID_INVALID');
        $c=$this->read($this->c.'/'.$id.'.json',
        'F111_SUBORDINATE_CASE_ABSENT');
        $gc=$c['originating_guildhall_commission_id']??null;
        $gd=$c['originating_guildhall_commission_digest']??null;
        if(!$this->ok($c)||
        'OPEN_PENDING_PERSONA_SPECIFICATION'!==($c['status']??null)||
        true!==($c['construction_authority']??null)||
        !is_array($c['subordinate_requirements']??null)||
        !is_string($gc)||
        !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/',
        $gc)||
        !is_string($gd)||
        ''===trim($gd))throw new \RuntimeException('F112_SUBORDINATE_CASE_INVALID');
        foreach(glob($this->s.'/subordinate-persona-specification-*.json')?:[]as$p){
            $o=$this->read($p,
            'F115_SPECIFICATION_REPLAY_CONFLICT');
            if($id===($o['case_id']??null)&&
            null===($o['supersedes']??null)&&
            $this->ok($o))return$o;

        }
        $d=$this->g->specify($c);
        $complete='PERSONA_SPECIFICATION_COMPLETE'===($d['disposition']??null);
        $sid='subordinate-persona-specification-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $c['record_digest'],
        $gc,
        $gd,
        $d])),
        0,
        20);
        return$this->save($sid,
        ['schema'=>'imperium.foundry-subordinate-persona-specification/v1',
        'specification_id'=>$sid,
        'specification_version'=>1,
        'supersedes'=>null,
        'revision_basis'=>null,
        'instance_id'=>$c['instance_id'],
        'case_id'=>$id,
        'case_digest'=>$c['record_digest'],
        'originating_guildhall_commission_id'=>$gc,
        'originating_guildhall_commission_digest'=>$gd,
        'queue_position'=>$c['queue_position'],
        'office'=>$c['office'],
        'subordinate_staff_class'=>$c['subordinate_staff_class'],
        'source_resolution_id'=>$c['source_resolution_id'],
        'source_resolution_digest'=>$c['source_resolution_digest'],
        'artificer'=>$c['artificer'],
        'inherited_requirements'=>$c['subordinate_requirements'],
        'specification'=>$d,
        'status'=>$complete?'SEALED_PENDING_PERSONA_CONSTRUCTION':'CLARIFICATION_REQUIRED',
        'persona_specification_complete'=>$complete,
        'construction_authority'=>$complete,
        'persona_selection_authority'=>false,
        'profile_approval_authority'=>false,
        'spawning_authority'=>false,
        'admission_authority'=>false,
        'seat_binding_authority'=>false,
        'execution_authority'=>false,
        'sealed'=>true]);

    }
    private function read(string$p,
    string$e):array{
        if(!is_file($p))throw new \RuntimeException($e);
        return json_decode((string)file_get_contents($p),
        true,
        512,
        JSON_THROW_ON_ERROR);

    }
    private function ok(array$r):bool{
        $d=$r['record_digest']??null;
        unset($r['record_digest']);
        return is_string($d)&&
        hash_equals($d,
        hash('sha256',
        CanonicalJson::encode($r)));

    }
    private function save(string$id,
    array$r):array{
        if(!is_dir($this->s)&&
        !mkdir($this->s,
        0770,
        true)&&
        !is_dir($this->s))throw new \RuntimeException('Foundry specification directory cannot be created.');
        $r['record_digest']=hash('sha256',
        CanonicalJson::encode($r));
        $p=$this->s.'/'.$id.'.json';
        $t=$p.'.tmp.'.bin2hex(random_bytes(6));
        if(false===file_put_contents($t,
        json_encode($r,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",
        LOCK_EX)||
        !rename($t,
        $p)){
            @unlink($t);
            throw new \RuntimeException('Specification cannot be committed atomically.');

        }
        return$r;

    }

}

