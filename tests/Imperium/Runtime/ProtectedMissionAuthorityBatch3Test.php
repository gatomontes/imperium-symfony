<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\ProtectedMission\PublicTrust;
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;

final class ProtectedMissionAuthorityBatch3Test extends TestCase
{
    public function testRealCliCanonicalExportExternalSignerSubmitDeriveVerifyRoundTrip():void
    {
        $root=sys_get_temp_dir().'/imperium-protected-cli-'.bin2hex(random_bytes(12)); mkdir($root,0700,true);
        $pair=sodium_crypto_sign_keypair(); $public=sodium_crypto_sign_publickey($pair);
        $trust=['identity'=>'disposable-cli-operator','competence'=>PublicTrust::COMPETENCE,'public_key'=>base64_encode($public),'not_before'=>time()-1,'expires_at'=>time()+3600];
        self::assertSame(0,$this->cli($root,['--help'])[0]);
        self::assertSame(2,$this->cli($root,['unknown'])[0]);
        self::assertSame(2,$this->cli($root,['trust'])[0]);
        $enrollment=$this->cli($root,['enroll',hash('sha256',$public)],json_encode($trust)); self::assertSame(0,$enrollment[0],$enrollment[2]);
        $input=ProtectedMissionFixture::input();
        $prepared=$this->cli($root,['prepare'],json_encode($input)); self::assertSame(0,$prepared[0],$prepared[2]);
        $cid=json_decode($prepared[1],true)['challenge_id'];
        $export=$this->cli($root,['export',$cid]); self::assertSame(0,$export[0],$export[2]);
        self::assertSame($export[1],$this->cli($root,['export',$cid])[1]);
        $payload=json_decode($export[1],true,512,JSON_THROW_ON_ERROR);
        self::assertSame(CanonicalJson::encode($payload),$export[1]);
        self::assertSame($input['mission']['target']['commit'],$payload['target']['commit']);
        self::assertStringContainsString('protected_mission',CanonicalJson::encode($payload['dossier']['mission_plan']));
        $render=$this->cli($root,['render',$cid]);
        self::assertStringContainsString(hash('sha256',$export[1]),$render[1]);
        foreach ($payload['dossier']['lines'] as $line) self::assertStringContainsString($line['line_number'].'. '.$line['text'],$render[1]);
        self::assertSame(2,$this->cli($root,['derive',$cid])[0]);
        $payloadPath=$root.'/payload.json'; $responsePath=$root.'/response.json'; file_put_contents($payloadPath,$export[1]);
        $signer=dirname(__DIR__,3).'/tools/sign-protected-mission.php';
        $signed=$this->process([PHP_BINARY,$signer,$payloadPath,$responsePath],base64_encode(sodium_crypto_sign_secretkey($pair))."\n");
        self::assertSame(0,$signed[0],$signed[2]);
        self::assertStringNotContainsString(base64_encode(sodium_crypto_sign_secretkey($pair)),$signed[1].$signed[2].file_get_contents($responsePath));
        $submitted=$this->cli($root,['submit'],file_get_contents($responsePath)); self::assertSame(0,$submitted[0],$submitted[2]);
        self::assertSame(2,$this->cli($root,['submit'],file_get_contents($responsePath))[0]);
        $derived=$this->cli($root,['derive',$cid]); self::assertSame(0,$derived[0],$derived[2]);
        $id=json_decode($derived[1],true)['authorization_id'];
        self::assertMatchesRegularExpression('/^mission-authorization-[a-f0-9]{20}$/',$id);
        $before=hash_file('sha256',$root.'/authority.journal');
        $verified=$this->cli($root,['verify',$id]); self::assertSame(0,$verified[0],$verified[2]);
        self::assertSame($before,hash_file('sha256',$root.'/authority.journal'));
        $chain=json_decode($verified[1],true);
        self::assertSame($id,$chain['authorization']['authorization_id']);
        self::assertSame($chain['review']['record_digest'],$chain['authorization']['authority_source']['imperator_review']['digest']);
        self::assertArrayHasKey('operator_authenticity',$chain['review']);
        self::assertFalse($chain['authorization']['execution_authority']);
        self::assertSame('AUTHORIZED',json_decode($this->cli($root,['status',$id])[1],true)['lifecycle']['state']);
        self::assertSame([],glob($root.'/scratch-*'));
        file_put_contents($root.'/sanitized-ceremony-transcript.json',json_encode(['test_only'=>true,'same_user_processes'=>true,'deployment_isolation_measured'=>false,'root'=>$root,'challenge_id'=>$cid,'authorization_id'=>$id,'payload_sha256'=>hash('sha256',$export[1]),'commands'=>['help','enroll','prepare','export twice','render','external sign via stdin','submit','replay refused','derive','verify','status'],'execution_performed'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        sodium_memzero($pair);
    }

    public function testBadSignaturesAmendmentCancellationAndInvalidPlansLeaveNoAuthorityResidue():void
    {
        $f=new ProtectedMissionFixture(); $input=ProtectedMissionFixture::input();
        $cid=$f->call('prepare',$input)['challenge_id']; $payload=$f->call('export',['challenge_id'=>$cid]);
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try {$f->call('submit',['challenge_id'=>$cid,'signature'=>base64_encode(random_bytes(64))]);self::fail('forgery accepted');} catch(\RuntimeException $e){self::assertSame('PMA_SIGNATURE_OR_TRUST_INVALID',$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        self::assertSame('PENDING_NON_AUTHORIZING',$f->call('challenge-status',['challenge_id'=>$cid])['status']);
        $input['mission']['target']['commit']=str_repeat('c',40); $new=$f->call('prepare',$input)['challenge_id'];
        try {$f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($payload)]);self::fail('stale accepted');} catch(\RuntimeException $e){self::assertSame('PMA_CHALLENGE_INACTIVE',$e->getMessage());}
        self::assertSame('SUPERSEDED',$f->call('challenge-status',['challenge_id'=>$cid])['status']);
        $control=$f->control('cancel-challenge')['payload']; $control['challenge_id']=$new;
        $f->call('control',['payload'=>$control,'signature'=>$f->sign($control)]);
        self::assertSame('CANCELLED',$f->call('challenge-status',['challenge_id'=>$new])['status']);
        foreach (['paths'=>['../secret'],'permissions'=>['WRITE'],'expires_at'=>time()-1] as $field=>$bad) {
            $invalid=$input; $invalid['mission'][$field]=$bad; $before=hash_file('sha256',$f->root.'/authority.journal');
            try {$f->call('prepare',$invalid);self::fail('invalid accepted');} catch(\RuntimeException $e){self::assertContains($e->getMessage(),['PMA_PATH_INVALID','PMA_MISSION_INVALID']);}
            self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        }
        self::assertSame([],glob($f->root.'/scratch-*'));
    }
    private function cli(string $root,array $args,string $input=''):array {return $this->process([PHP_BINARY,__DIR__.'/Support/protected_mission_cli.php',$root,...$args],$input);}
    private function process(array $command,string $input=''):array
    {
        $p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        fwrite($pipes[0],$input);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);return [proc_close($p),$out,$err];
    }
}
