<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Guildhall;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final readonly class SubordinatePersonaGarrisonDeliveryService
{
    private string $receipts;private string $outbox;
    public function __construct(#[Autowire("%kernel.project_dir%")]string$p){$this->receipts=$p."/var/imperium/offices/guildhall/subordinate-persona-fulfillment-receipts";$this->outbox=$p."/var/imperium/offices/garrison/inbox/canonical-subordinate-persona-admissions";}
    public function deliver(string$receiptId):array
    {
        if(!preg_match('/^guildhall-persona-fulfillment-receipt-[a-f0-9]{20}$/',$receiptId))throw new \InvalidArgumentException("G79_FULFILLMENT_RECEIPT_ID_INVALID");$r=$this->read($this->receipts."/".$receiptId.".json","G80_GARRISON_FORWARDING_CHAIN_INVALID");
        if(!$this->ok($r)||"GUILDHALL_ACCEPTED_PENDING_GARRISON_FORWARDING"!==($r["status"]??null)||true!==($r["commission_fulfillment_accepted"]??null)||true!==($r["garrison_forwarding_ready"]??null)||false!==($r["candidate_substituted"]??null)||true===($r["admission_authority"]??null)||true===($r["execution_authority"]??null))throw new \RuntimeException("G80_GARRISON_FORWARDING_CHAIN_INVALID");
        $id="guildhall-garrison-persona-admission-delivery-".substr(hash("sha256",CanonicalJson::encode([$receiptId,$r["record_digest"],$r["candidate_id"],$r["candidate_digest"]])),0,20);
        return $this->save($id,["schema"=>"imperium.guildhall-garrison-persona-admission-delivery/v1","delivery_id"=>$id,"instance_id"=>$r["instance_id"],"sender"=>$r["guildmaster"],"recipient"=>["office"=>"garrison","seat"=>"garrison.constable"],"guildhall_fulfillment_receipt_id"=>$receiptId,"guildhall_fulfillment_receipt_digest"=>$r["record_digest"],"originating_guildhall_commission_id"=>$r["originating_guildhall_commission_id"],"originating_guildhall_commission_digest"=>$r["originating_guildhall_commission_digest"],"senate_confirmation_record_id"=>$r["senate_confirmation_record_id"],"senate_confirmation_record_digest"=>$r["senate_confirmation_record_digest"],"candidate_id"=>$r["candidate_id"],"candidate_digest"=>$r["candidate_digest"],"persona_name"=>$r["persona_name"],"persona_specification_version"=>$r["persona_specification_version"],"persona"=>$r["persona"],"review_target_lineage"=>$r["review_target_lineage"],"route_class"=>"CANONICAL_GUILDHALL_TO_GARRISON","requested_disposition"=>"DECIDE_EXACT_PERSONA_ADMISSION_AND_CUSTODY","status"=>"DELIVERED_PENDING_CONSTABLE_ADMISSION_DISPOSITION","recipient_acceptance"=>null,"admission_authority"=>false,"execution_authority"=>false,"sealed"=>true]);
    }
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function ok(array$r):bool{$d=$r["record_digest"]??null;unset($r["record_digest"]);return is_string($d)&&hash_equals($d,hash("sha256",CanonicalJson::encode($r)));}
    private function save(string$id,array$r):array{if(!is_dir($this->outbox)&&!mkdir($this->outbox,0770,true)&&!is_dir($this->outbox))throw new \RuntimeException("G81_GARRISON_FORWARDING_FAILED");$r["record_digest"]=hash("sha256",CanonicalJson::encode($r));$p=$this->outbox."/".$id.".json";if(is_file($p)){ $o=$this->read($p,"G82_GARRISON_FORWARDING_CONFLICT");if(CanonicalJson::encode($o)!==CanonicalJson::encode($r))throw new \RuntimeException("G82_GARRISON_FORWARDING_CONFLICT");return$o;}$t=$p.".tmp.".bin2hex(random_bytes(6));if(false===file_put_contents($t,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)||!rename($t,$p)){@unlink($t);throw new \RuntimeException("G81_GARRISON_FORWARDING_FAILED");}return$r;}
}
