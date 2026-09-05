<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__,3).'/tools/LocalIsolation.php';

final class LocalIsolationPackageTest extends TestCase
{
    public function testPublicVerifierReconstructsBytesAndRejectsTamperedReceiptAndManifest():void
    {
        $f=new ProtectedMissionFixture();$chain=$f->call('verify',['authorization_id'=>$f->id]);$repo=$chain['payload']['target']['repository'];
        $before=\LocalIsolation::manifest($repo);
        $inventory=\LocalIsolation::walk(\LocalIsolation::looseReader($repo),$chain['payload']['target']['commit'],$chain['payload']['paths']);
        foreach($f->call('issue',['authorization_id'=>$f->id])['capabilities'] as $cap)$f->call('consume',['capability'=>$cap]);
        $status=$f->call('status',['authorization_id'=>$f->id]);
        $verify=fn($s,$after)=>\LocalIsolation::verify($chain,$s,$f->trust,$inventory,$repo,$before,$after);
        self::assertSame('PUBLIC_RECEIPT_BYTES_AND_GENERATION_VERIFIED',$verify($status,$before)['result']);
        foreach(['generation','bytes','history','budget','manifest','receipt-alias','status-alias'] as $case){
            $bad=$status;$after=$before;
            if($case==='generation')$bad['receipt']['binding']['generation_id']=str_repeat('0',64);
            if($case==='bytes')$bad['receipt']['snapshot']['findings'][0]['bytes_base64']=base64_encode('substituted');
            if($case==='history')$bad['lifecycle']['history'][1]['capability']['payload']['from']='AUTHORIZED';
            if($case==='budget')$bad['receipt']['snapshot']['object_bytes_read']--;
            if($case==='manifest')$after['unexpected']='file';
            if($case==='receipt-alias')$bad['receipt']['mission_id']='unrelated-mission';
            if($case==='status-alias')$bad['authorization_id']='unrelated-authorization';
            try{$verify($bad,$after);self::fail('Tamper accepted: '.$case);}catch(\RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
    }
    public function testCompleteDisclosedDraftWorksWithoutOpeningUnsupportedPreparationDemands():void
    {
        $f=new ProtectedMissionFixture();$old=$f->call('verify',['authorization_id'=>$f->id]);$target=$old['payload']['target'];
        $inventory=\LocalIsolation::walk(\LocalIsolation::looseReader($target['repository']),$target['commit'],$old['payload']['paths']);
        $draft=\LocalIsolation::draft($inventory,$target['repository'],time()+600);
        self::assertSame(60,$draft['mission']['budget']['max_seconds']);self::assertCount(11,$draft['disclosures']);
        $id=$f->call('prepare',$draft)['challenge_id'];$payload=$f->call('export',['challenge_id'=>$id]);
        self::assertGreaterThan(12,$payload['dossier']['line_count']);
        $f->call('submit',['challenge_id'=>$id,'signature'=>$f->sign($payload)]);$aid=$f->call('derive',['challenge_id'=>$id])['authorization_id'];
        $chain=$f->call('verify',['authorization_id'=>$aid]);
        self::assertNull($chain['authorization']['preparation_authorities']['personnel_preparation']);
        self::assertNull($chain['authorization']['preparation_authorities']['tool_credential_data_preparation']);
        foreach($f->call('issue',['authorization_id'=>$aid])['capabilities'] as $cap)$f->call('consume',['capability'=>$cap]);
        self::assertSame('COMPLETED',$f->call('status',['authorization_id'=>$aid])['lifecycle']['state']);
        $draft['mission']['budget']['max_seconds']=900;
        try{$f->call('prepare',$draft);self::fail('900-second plan accepted');}catch(\RuntimeException $e){self::assertSame('PMA_BUDGET_INVALID',$e->getMessage());}
    }
}
