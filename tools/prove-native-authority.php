<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Tests\Imperium\Runtime\Support\{NativeAuthorityFixture as F,CitadelAuthorityFixture as Old};
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Garrison\{SubordinatePersonaCanonicalAdmissionService,GarrisonInventoryResponseService};

if($argc!==1)throw new RuntimeException('Synthetic proof accepts no arguments');
$f=new F();
try {
    $privateBefore=hash_file('sha256',$f->root.'/var/imperium/bootstrap-state.json');
    $originalBefore=hash_file('sha256',$f->root.'/var/imperium/offices/garrison/occupancy/'.Old::occupancy()['binding_id'].'.json');
    $signed=$f->ready();$p=$f->protocol();
    $recruiter=$p->resolve('conscription.recruiter');
    $admission=(new SubordinatePersonaCanonicalAdmissionService($f->root,$p))->admit(F::DELIVERY,Old::occupancy()['binding_id']);
    $inventory=(new GarrisonInventoryResponseService($f->root,$p))->respond(F::INQUIRY);
    $bad=$signed;$bad['decision']['payload']['domain']='CITADEL_MISSION_FORMATION';$bad['decision']['signature']=$f->signature($bad['decision']['payload']);
    $refusal=$f->command('apply',$bad);
    if($refusal[0]!==1||$admission['disposition']!=='ADMITTED'||count($inventory['inventory_records'])!==1)throw new RuntimeException('Synthetic proof invariant');
    $revoke=$f->sign('REVOKE_DECISION',['expected_head'=>$p->snapshot()['registry_head'],'target'=>$signed['decision']['payload']['nonce']]);
    if($f->command('apply',$revoke)[0]!==0)throw new RuntimeException('Synthetic revocation failed');
    $generation=$p->snapshot()['frame_generation'];$f->base->now+=8000;
    if($f->command('apply',$signed)[0]!==0||$p->admit(F::DELIVERY,Old::occupancy()['binding_id'])!==$admission
        ||$p->snapshot()['frame_generation']!==$generation)throw new RuntimeException('Synthetic recovery repeated effect');
    $frames=array_map(fn($path)=>A::read($path),glob($f->root.'/var/imperium/native-authority/*.json'));
    $result=['label'=>'SYNTHETIC_NATIVE_PROTOCOL_ONLY_NOT_INSTALLATION_EVIDENCE','real_keys_used'=>false,'real_installation_read'=>false,
        'private_bytes_unchanged'=>$privateBefore===hash_file('sha256',$f->root.'/var/imperium/bootstrap-state.json'),
        'original_occupancy_bytes_unchanged'=>$originalBefore===hash_file('sha256',$f->root.'/var/imperium/offices/garrison/occupancy/'.Old::occupancy()['binding_id'].'.json'),
        'recruiter_observation'=>$recruiter,'admission'=>$admission,'inventory'=>$inventory,'completed_recovery_without_new_frame'=>true,
        'command_di_outputs'=>$f->outputs,'public_frames'=>$frames,...A::flags()];
    $json=json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if(str_contains($json,Old::SECRET)||!$result['private_bytes_unchanged']||!$result['original_occupancy_bytes_unchanged'])throw new RuntimeException('Synthetic disclosure or mutation');
    echo $json,"\n";
}finally{$f->close();}
