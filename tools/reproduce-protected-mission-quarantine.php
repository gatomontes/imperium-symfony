<?php
declare(strict_types=1);

// Isolated adverse evidence only. Never part of production acceptance or trust enrollment.
require dirname(__DIR__).'/vendor/autoload.php';
$sha = '8df34679beab0ba8699a68fdd458570bf658c4c8';
$root = sys_get_temp_dir().'/imperium-protected-quarantine-'.bin2hex(random_bytes(12));
mkdir($root, 0700, true);
foreach (['CanonicalMissionPlan', 'AuthenticatedMissionAuthorization', 'MissionCapability', 'MissionLifecycleStore', 'MissionCapabilityKeyStore'] as $class) {
    $process = proc_open(['git', 'show', $sha.':src/Imperium/Runtime/Mission/'.$class.'.php'], [1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__), null, ['bypass_shell'=>true]);
    $source = stream_get_contents($pipes[1]); fclose($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process)!==0) throw new RuntimeException($error);
    file_put_contents($root.'/'.$class.'.php', $source);
    require $root.'/'.$class.'.php';
}
$plan = App\Imperium\Runtime\Mission\CanonicalMissionPlan::fromMissionPlan(['canonical_mission'=>[
    'schema'=>'imperium.canonical-mission-plan/v1','mission_id'=>'disposable-forged-mission','mission_kind'=>'test-only',
    'target_repository'=>'not-accessed','target_commit'=>str_repeat('a',40),'target_tree'=>str_repeat('b',40),
    'inspection_paths'=>['test.txt'],'requested_permissions'=>['test'],'prohibitions'=>['real execution'],
    'budget'=>['max_files'=>1,'max_bytes'=>1,'max_findings'=>1,'max_seconds'=>1],
    'expires_at'=>'2026-01-01T00:00:00+00:00','success_criteria'=>['none'],'failure_criteria'=>['none'],'evidence_requirements'=>['none'],
    'lifecycle_transitions'=>[['action'=>'forge','actor'=>'untrusted','target'=>'test','from'=>'AUTHORIZED','to'=>'COMPLETED']],
]]);
$authorization = new App\Imperium\Runtime\Mission\AuthenticatedMissionAuthorization('invented',str_repeat('c',64),'invented',1,str_repeat('d',64),'invented',str_repeat('e',64),'invented','2020-01-01T00:00:00+00:00',$plan);
$record=[];
foreach (App\Imperium\Runtime\Mission\MissionCapability::FIELDS as $field) $record[$field]='invented';
$record=array_replace($record,['schema'=>App\Imperium\Runtime\Mission\MissionCapability::SCHEMA,'authorization_digest'=>str_repeat('c',64),'dossier_digest'=>str_repeat('d',64),'mission_digest'=>$plan->digest(),'nonce'=>bin2hex(random_bytes(16)),'signature'=>str_repeat('0',64),'not_before'=>1,'expires_at'=>2,'required_state'=>'AUTHORIZED','resulting_state'=>'COMPLETED']);
$result=(new App\Imperium\Runtime\Mission\MissionLifecycleStore($root))->consume(App\Imperium\Runtime\Mission\MissionCapability::fromArray($record),$authorization,new DateTimeImmutable());
if ($result['state']!=='COMPLETED') throw new RuntimeException('Counterexample did not reproduce');
$keys=new App\Imperium\Runtime\Mission\MissionCapabilityKeyStore($root);
$key=$keys->initialize();
if (strlen($key)!==32 || $keys->existing()!==$key) throw new RuntimeException('Extraction did not reproduce');
echo json_encode(['source'=>$sha,'test_root'=>$root,'forged_unsigned_expired_DTO_consumed'=>true,'raw_issuer_key_extractable'=>true,'key_value_disclosed'=>false,'real_mission_executed'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
// Exact owned test secret only; retain source and adverse lifecycle evidence.
sodium_memzero($key);
unlink($root.'/var/imperium/runtime/canonical-mission/capability-issuer.key');
