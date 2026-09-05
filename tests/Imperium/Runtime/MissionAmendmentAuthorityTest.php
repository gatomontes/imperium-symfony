<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;

final class MissionAmendmentAuthorityTest extends TestCase
{
    private function input(ProtectedMissionFixture $f):array {
        $input=ProtectedMissionFixture::input();
        $input['mission']=$f->state['authorizations'][$f->id]['dossier']['mission_plan']['protected_mission'];
        return $input;
    }
    private function refuse(ProtectedMissionFixture $f,string $code,string $op,array $args):void {
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try {$f->call($op,$args);self::fail('Expected '.$code);} catch(\RuntimeException $e){self::assertSame($code,$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
    }
    public function testUnsignedAndInvalidProposalsPreserveAuthorityProgressAndPendingBytes():void {
        $f=new ProtectedMissionFixture();$cap=$f->call('issue',['authorization_id'=>$f->id])['capabilities'][0];
        $f->call('consume',['capability'=>$cap]);$before=$f->call('status',['authorization_id'=>$f->id]);
        $cid=$f->call('prepare',$this->input($f))['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
        $second=$f->call('prepare',$this->input($f))['challenge_id'];
        self::assertSame($p,$f->call('export',['challenge_id'=>$cid]));
        self::assertSame($before,$f->call('status',['authorization_id'=>$f->id]));
        self::assertSame($f->id,$p['activation']['expected_predecessor']['authorization_id']);
        foreach (['activation','dossier'] as $field) {
            $altered=$p;$altered[$field]=[];
            $this->refuse($f,'PMA_SIGNATURE_OR_TRUST_INVALID','submit',['challenge_id'=>$cid,'signature'=>$f->sign($altered)]);
        }
        $this->refuse($f,'PMA_SIGNATURE_OR_TRUST_INVALID','submit',['challenge_id'=>$second,'signature'=>base64_encode(random_bytes(64))]);
        self::assertSame($before,$f->call('status',['authorization_id'=>$f->id]));
    }
    public function testSignedReplacementHasOneWinnerAndStaleApprovalCannotDeactivateIt():void {
        $f=new ProtectedMissionFixture();$ids=[];
        foreach ([1,2] as $unused) {
            $cid=$f->call('prepare',$this->input($f))['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
            $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);$ids[]=$cid;
        }
        $id=$f->call('derive',['challenge_id'=>$ids[0]])['authorization_id'];
        self::assertNotSame($id,$f->id);
        $before=$f->call('status',['authorization_id'=>$id]);
        $this->refuse($f,'PMA_STALE_PREDECESSOR','derive',['challenge_id'=>$ids[1]]);
        $this->refuse($f,'PMA_CHALLENGE_NOT_APPROVED','derive',['challenge_id'=>$ids[0]]);
        self::assertSame($before,$f->call('status',['authorization_id'=>$id]));
        self::assertSame('CURRENT',$before['currentness']);
        self::assertSame('amended',$f->call('status',['authorization_id'=>$f->id])['inactive']);
    }

    public function testSuccessorRequiresFreshExecutionForIndependentPathBudgetAndRepositoryChanges():void {
        foreach (['paths','budget','repository'] as $change) {
            $f=new ProtectedMissionFixture();$caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
            foreach (array_slice($caps,0,2) as $cap) $f->call('consume',['capability'=>$cap]);
            $old=$f->call('status',['authorization_id'=>$f->id]);$input=$this->input($f);
            if ($change==='paths') $input['mission']['paths']=['amendment.txt'];
            elseif ($change==='budget') $input['mission']['budget']['max_bytes']=90000;
            else {$other=new ProtectedMissionFixture();$input['mission']['target']['repository']=$other->state['authorizations'][$other->id]['payload']['target']['repository'];}
            $cid=$f->call('prepare',$input)['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
            $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);$id=$f->call('derive',['challenge_id'=>$cid])['authorization_id'];
            $new=$f->call('status',['authorization_id'=>$id]);
            self::assertSame('AUTHORIZED',$new['lifecycle']['state']);self::assertSame([],$new['lifecycle']['history']);self::assertNull($new['receipt']);
            self::assertNotSame($old['binding']['generation_id'],$new['binding']['generation_id']);
            $fresh=$f->call('issue',['authorization_id'=>$id])['capabilities'];
            $this->refuse($f,'PMA_REQUIRED_STATE','consume',['capability'=>$fresh[2]]);
            $this->refuse($f,'PMA_AUTHORITY_INACTIVE','consume',['capability'=>$caps[2]]);
            foreach ($fresh as $cap) $f->call('consume',['capability'=>$cap]);
            $done=$f->call('status',['authorization_id'=>$id]);$historical=$f->call('status',['authorization_id'=>$f->id]);
            self::assertSame('COMPLETED',$done['lifecycle']['state']);
            self::assertSame($input['mission']['paths'],array_column($done['receipt']['snapshot']['findings'],'path'));
            self::assertSame($new['binding'],$done['receipt']['binding']);
            self::assertSame($new['binding'],$done['receipt']['snapshot']['binding']);
            self::assertSame($old['lifecycle'],$historical['lifecycle']);self::assertNull($historical['receipt']);self::assertFalse($historical['is_current']);
            $this->refuse($f,'PMA_TERMINAL','prepare',$input);
            // A fresh mission identity remains admissible, without inheriting the old state.
            $input['mission']['mission_id'].='-new';$cid=$f->call('prepare',$input)['challenge_id'];
            $p=$f->call('export',['challenge_id'=>$cid]);self::assertNull($p['activation']['expected_predecessor']);
            $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);$newId=$f->call('derive',['challenge_id'=>$cid])['authorization_id'];
            self::assertSame('AUTHORIZED',$f->call('status',['authorization_id'=>$newId])['lifecycle']['state']);
        }
    }

    public function testLegacyAndWrongGenerationRecordsRefuseRatherThanReinterpretingEvidence():void {
        $f=new ProtectedMissionFixture();unset($f->state['schema']);$f->save();
        $this->refuse($f,'PMA_STATE_SCHEMA_REQUIRES_OWNER_MIGRATION','issue',['authorization_id'=>$f->id]);
        foreach (['lifecycles','inspections','receipts'] as $section) {
            $f=new ProtectedMissionFixture();$caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
            foreach ($caps as $cap) $f->call('consume',['capability'=>$cap]);
            // Explicit disposable corruption: a receipt from another exact generation.
            $state=$this->readState($f);$state[$section][$f->id]['binding']['generation_id']=str_repeat('0',64);$f->state=$state;$f->save();
            if ($section==='inspections') {
                $state['lifecycles'][$f->id]['state']='INSPECTING';unset($state['lifecycles'][$f->id]['consumed_nonces'][$caps[2]['payload']['nonce']]);$f->state=$state;$f->save();
                $this->refuse($f,'PMA_GENERATION_BINDING_INVALID','consume',['capability'=>$caps[2]]);
            } else $this->refuse($f,'PMA_GENERATION_BINDING_INVALID','status',['authorization_id'=>$f->id]);
        }
    }
    private function readState(ProtectedMissionFixture $f):array {
        $h=fopen($f->root.'/authority.journal','rb');$state=[];
        while (($line=fgets($h))!==false) {$n=(int)explode(' ',$line)[0];$state=json_decode(fread($h,$n),true,512,JSON_THROW_ON_ERROR);}fclose($h);return $state;
    }

    public function testCapacityRefusesWithoutEvictingAuthorityOrApprovedChallenges():void {
        $f=new ProtectedMissionFixture();$caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
        $input=$this->input($f);$cid=$f->call('prepare',$input)['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
        $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);
        $before=$this->readState($f);unset($before['pending']);
        // Initial derived proposal plus approved replacement plus 62 unsigned records = 64.
        for($i=0;$i<62;$i++)$f->call('prepare',$input);
        $this->refuse($f,'PMA_PROPOSAL_CAPACITY','prepare',$input);
        $after=$this->readState($f);unset($after['pending']);self::assertSame($before,$after);
        self::assertSame('APPROVED_PENDING_DERIVATION',$f->call('challenge-status',['challenge_id'=>$cid])['status']);
        self::assertSame('ADMITTED',$f->call('consume',['capability'=>$caps[0]])['state']);
        $id=$f->call('derive',['challenge_id'=>$cid])['authorization_id'];self::assertSame('CURRENT',$f->call('status',['authorization_id'=>$id])['currentness']);
    }

    public function testTerminalCancellationFailedStateAndMixedSchemaCannotReopen():void {
        $f=new ProtectedMissionFixture();$input=$this->input($f);$cid=$f->call('prepare',$input)['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
        $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);$f->call('control',$f->control('cancel'));
        $this->refuse($f,'PMA_TERMINAL','derive',['challenge_id'=>$cid]);$this->refuse($f,'PMA_TERMINAL','prepare',$input);
        // Explicit disposable corruption tests: FAILED has no public producer in this bounded protocol.
        $f=new ProtectedMissionFixture();$input=$this->input($f);$f->state['lifecycles'][$f->id]['state']='FAILED';$f->save();
        $this->refuse($f,'PMA_TERMINAL','prepare',$input);
        $f=new ProtectedMissionFixture();$mid=$this->input($f)['mission']['mission_id'];$f->state['lifecycles'][$mid]=['state'=>'INSPECTING'];$f->save();
        $this->refuse($f,'PMA_STATE_SCHEMA_REQUIRES_OWNER_MIGRATION','status',['authorization_id'=>$f->id]);
    }
}
