<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;

final class ProtectedMissionAuthorityBatch5AuditTest extends TestCase
{
    public function testMalformedCompleteJournalAndForgedAuthorizationFlagsRefuseWithoutWrites():void
    {
        $f=new ProtectedMissionFixture();file_put_contents($f->root.'/authority.journal',"corrupt-complete-header\n",FILE_APPEND);
        $this->refuses($f,'PMA_JOURNAL_CORRUPT','issue',['authorization_id'=>$f->id]);
        $f=new ProtectedMissionFixture();$a=&$f->state['authorizations'][$f->id]['authorization'];$a['execution_authority']=true;
        unset($a['record_digest']);$a['record_digest']=hash('sha256',CanonicalJson::encode($a));$f->save();
        $this->refuses($f,'PMA_CHAIN_INVALID','issue',['authorization_id'=>$f->id]);
        self::assertStringNotContainsString('issuer_secret',file_get_contents($f->root.'/authority.journal'));
    }
    public function testSignedRotationRetiresOldTrustPendingChallengesAndAuthority():void
    {
        $f=new ProtectedMissionFixture();$pending=$f->call('prepare',ProtectedMissionFixture::input())['challenge_id'];
        $pair=sodium_crypto_sign_keypair();$public=sodium_crypto_sign_publickey($pair);
        $payload=$f->control('rotate-trust')['payload'];
        $payload['new_trust']=['identity'=>'disposable-successor','competence'=>\App\ProtectedMission\PublicTrust::COMPETENCE,'public_key'=>base64_encode($public),'not_before'=>time()-1,'expires_at'=>time()+600];
        $payload['new_fingerprint']=hash('sha256',$public);
        $f->call('control',['payload'=>$payload,'signature'=>$f->sign($payload)]);
        self::assertSame('disposable-successor',$f->call('trust')['identity']);
        self::assertSame('SUPERSEDED',$f->call('challenge-status',['challenge_id'=>$pending])['status']);
        $this->refuses($f,'PMA_SIGNATURE_OR_TRUST_INVALID','issue',['authorization_id'=>$f->id]);
        self::assertSame('PMA_SIGNATURE_OR_TRUST_INVALID',$f->call('status',['authorization_id'=>$f->id])['currentness']);
        sodium_memzero($pair);
    }
    public function testPendingExpiryAndNonCanonicalSigningRefuseWithoutApproval():void
    {
        $f=new ProtectedMissionFixture();$input=ProtectedMissionFixture::input();$input['mission']['expires_at']=time()+1;
        $cid=$f->call('prepare',$input)['challenge_id'];$payload=$f->call('export',['challenge_id'=>$cid]);
        while(time()<$input['mission']['expires_at'])usleep(1000);
        $this->refuses($f,'PMA_CHALLENGE_INACTIVE','submit',['challenge_id'=>$cid,'signature'=>$f->sign($payload)]);
        self::assertSame('EXPIRED',$f->call('challenge-status',['challenge_id'=>$cid])['status']);
        self::assertNull($f->call('challenge-status',['challenge_id'=>$cid])['authorization_id']);
        $file=$f->root.'/noncanonical.json';$out=$f->root.'/must-not-exist.json';file_put_contents($file,CanonicalJson::encode($payload)."\n");
        $process=proc_open([PHP_BINARY,dirname(__DIR__,3).'/tools/sign-protected-mission.php',$file,$out],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        fclose($pipes[0]);stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);
        self::assertSame(2,proc_close($process));self::assertSame("PMA_SIGN_REFUSED\n",$error);self::assertFileDoesNotExist($out);
    }
    private function refuses(ProtectedMissionFixture $f,string $code,string $operation,array $args):void
    {
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try{$f->call($operation,$args);self::fail('Expected '.$code);}catch(\RuntimeException $e){self::assertSame($code,$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
    }
}
