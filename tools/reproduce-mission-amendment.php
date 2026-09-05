<?php
declare(strict_types=1);
// Historical public-protocol reproduction. Run only against the preserved candidate.
require dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/tests/Imperium/Runtime/Support/ProtectedMissionFixture.php';
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use App\Bootstrap\CanonicalJson;
function proposal(ProtectedMissionFixture $f): array {
    $input=ProtectedMissionFixture::input();
    $input['mission']=$f->state['authorizations'][$f->id]['dossier']['mission_plan']['protected_mission'];
    return $input;
}
$f=new ProtectedMissionFixture();
$before=$f->call('status',['authorization_id'=>$f->id]);
$challenge=$f->call('prepare',proposal($f))['challenge_id'];
$after=$f->call('status',['authorization_id'=>$f->id]);
if ($before['currentness']!=='CURRENT' || $after['inactive']!=='amended') throw new RuntimeException('AM01 not reproduced');
$am01=['before'=>$before,'after'=>$after,'unsigned_challenge'=>$challenge];
$f=new ProtectedMissionFixture();
$caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
foreach (array_slice($caps,0,2) as $cap) $f->call('consume',['capability'=>$cap]);
$before=$f->call('status',['authorization_id'=>$f->id]);
$input=proposal($f); $input['mission']['paths']=['not-inspected.txt'];
$cid=$f->call('prepare',$input)['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
$f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);
$id=$f->call('derive',['challenge_id'=>$cid])['authorization_id'];
$caps=$f->call('issue',['authorization_id'=>$id])['capabilities'];
$f->call('consume',['capability'=>$caps[2]]);
$after=$f->call('status',['authorization_id'=>$id]);
if ($after['lifecycle']['state']!=='COMPLETED' || $after['receipt']['snapshot']['findings'][0]['path']!=='evidence.txt') throw new RuntimeException('AM02 not reproduced');
echo json_encode(['test_only'=>true,'public_protocol_only'=>true,'deployment_isolation_proved'=>false,
    'am01'=>$am01,'am02'=>['before'=>$before,'successor_paths'=>$p['paths'],'after'=>$after],
    'affected_hashes'=>['am01_before'=>hash('sha256',CanonicalJson::encode($am01['before'])),'am01_after'=>hash('sha256',CanonicalJson::encode($am01['after'])),'am02_before'=>hash('sha256',CanonicalJson::encode($before)),'am02_after'=>hash('sha256',CanonicalJson::encode($after))]],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
