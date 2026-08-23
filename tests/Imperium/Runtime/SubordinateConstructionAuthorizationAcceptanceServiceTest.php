<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\SubordinateConstructionAuthorizationAcceptanceService;
use PHPUnit\Framework\TestCase;
final class SubordinateConstructionAuthorizationAcceptanceServiceTest extends TestCase
{
    public function testOccupiedArtificerAcceptsExactGuildhallCommission():void
    {
        $root=sys_get_temp_dir()."/imperium-subordinate-acceptance-".bin2hex(random_bytes(6));$ref=["resolution_id"=>"hagiography-subordinate-resolution-".str_repeat("a",20),"office"=>"hagiography","subordinate_staff_class"=>"Chronicler"];$id="guildhall-subordinate-construction-commission-".str_repeat("b",20);$commission=["schema"=>"imperium.guildhall-subordinate-construction-commission/v1","commission_id"=>$id,"instance_id"=>"imperium-test","authorization_act_id"=>"act","authorization_act_digest"=>"act-digest","authorized_resolutions"=>[$ref],"requester"=>["office"=>"guildhall"],"recipient"=>["office"=>"foundry","seat"=>"foundry.artificer"],"status"=>"COMMISSIONED_PENDING_FOUNDRY_ACCEPTANCE","recipient_acceptance"=>null,"construction_authority"=>true,"construction_authority_exercisable"=>false,"persona_selection_authority"=>false,"admission_authority"=>false,"execution_authority"=>false,"sealed"=>true];$this->write($root."/var/imperium/offices/foundry/inbox/subordinate-construction-commissions",$id,$commission);$bindingId="foundry-artificer-binding-".str_repeat("d",20);$binding=["schema"=>"imperium.foundry-artificer-occupancy/v1","binding_id"=>$bindingId,"instance_id"=>"imperium-test","office"=>"foundry","seat"=>"foundry.artificer","manifestation_id"=>"artificer","occupancy_generation"=>1,"status"=>"ACTIVE","binding_atomic"=>true,"execution_authority"=>false];$this->write($root."/var/imperium/offices/foundry/occupancy",$bindingId,$binding);try{$service=new SubordinateConstructionAuthorizationAcceptanceService($root);$a=$service->accept($id,$bindingId);self::assertSame($a,$service->accept($id,$bindingId));self::assertSame($id,$a["guildhall_commission_id"]);self::assertSame("ACCEPTED_FOR_EXACT_SUBORDINATE_CONSTRUCTION",$a["disposition"]);self::assertTrue($a["construction_authority_exercisable"]);self::assertFalse($a["execution_authority"]);}finally{$this->remove($root);}
    }
    private function write(string$d,string$id,array&$r):void{mkdir($d,0770,true);$r["record_digest"]=hash("sha256",CanonicalJson::encode($r));file_put_contents($d."/".$id.".json",json_encode($r,JSON_THROW_ON_ERROR));}private function remove(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],[".",".."])as$e){$c=$p."/".$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
