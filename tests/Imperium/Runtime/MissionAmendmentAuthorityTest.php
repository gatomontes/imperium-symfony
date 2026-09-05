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
}
