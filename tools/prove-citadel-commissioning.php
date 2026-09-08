<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Tests\Imperium\Runtime\Support\{NativeAuthorityFixture as F, CitadelAuthorityFixture as Old};
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Garrison\{SubordinatePersonaCanonicalAdmissionService, GarrisonInventoryResponseService};

if ($argc !== 1) throw new RuntimeException('Synthetic rehearsal accepts no arguments');
$f = new F();
try {
    $f->base->now = time();
    $f->policy['not_before'] = $f->base->now - 10;
    $f->policy['expires_at'] = $f->base->now + 7200;
    $f->projection = $f->base->recruiter()->export('synthetic-authority-test');
    $private = hash_file('sha256', $f->root.'/var/imperium/bootstrap-state.json');
    $original = hash_file('sha256', $f->root.'/var/imperium/offices/garrison/occupancy/'.$f->occupancy['binding_id'].'.json');
    $inputs = [];
    $invoke = function (string $operation, array $arguments, int $expected) use ($f, &$inputs): array {
        $start = gmdate('c');
        $result = $f->command($operation, $arguments);
        $inputs[] = ['command' => $operation === 'enroll' ? 'imperium:native-authority:enroll' : 'imperium:native-authority',
            'operation' => $operation, 'public_input' => $arguments, 'native_exit' => $result[0],
            'started_utc' => $start, 'ended_utc' => gmdate('c')];
        if ($result[0] !== $expected) throw new RuntimeException('Synthetic command exit mismatch');
        return $result[1];
    };
    $invoke('enroll', $f->policy, 0);
    $act = function (string $effect, array $object) use ($f, $invoke): array {
        $prepared = $invoke('prepare', ['effect'=>$effect, 'object'=>$object, 'issued_at'=>$f->base->now,
            'expires_at'=>$f->base->now+60, 'nonce'=>bin2hex(random_bytes(24))], 2);
        $assembled = $invoke('assemble', ['object'=>$object, 'payload'=>$prepared['payload'],
            'signature'=>$f->signature($prepared['payload'])], 2);
        if ($assembled['object_digest'] !== A::digest($object)) throw new RuntimeException('Object digest mismatch');
        $signed = ['object'=>$object, 'decision'=>$assembled['decision']];
        $invoke('apply', $signed, 0);
        return $signed;
    };
    foreach (['conscription.recruiter','garrison.constable'] as $seat) {
        $invoke('snapshot', [], 2);
        $act('ADOPT_ROSTER', $f->adoption($seat));
        $invoke('resolve', ['seat'=>$seat], 0);
    }
    $requestInput = $f->base->requestInput();
    $request = $f->base->command('garrison-request', $requestInput);
    if ($request[0] !== 2) throw new RuntimeException('Synthetic Garrison preparation failed');
    $revision = $f->revision(); $revision['request'] = $request[1];
    $signed = $act('REVISE_GARRISON', $revision);
    $admission = $invoke('admit', ['delivery_id'=>F::DELIVERY,'binding_id'=>$f->occupancy['binding_id']], 0);
    $p = $f->protocol();
    if ((new SubordinatePersonaCanonicalAdmissionService($f->root,$p))->admit(F::DELIVERY,$f->occupancy['binding_id']) !== $admission)
        throw new RuntimeException('Consumer recovery mismatch');
    $inventory = $invoke('inventory', ['inquiry_id'=>F::INQUIRY], 0);
    if ((new GarrisonInventoryResponseService($f->root,$p))->respond(F::INQUIRY) !== $inventory)
        throw new RuntimeException('Inventory consumer mismatch');
    $act('REVOKE_DECISION', ['expected_head'=>$p->snapshot()['registry_head'],'target'=>$signed['decision']['payload']['nonce']]);
    $generation = $p->snapshot()['frame_generation']; $f->base->now += 8000;
    $invoke('apply', $signed, 0);
    $recovered = $invoke('admit', ['delivery_id'=>F::DELIVERY,'binding_id'=>$f->occupancy['binding_id']], 0);
    if ($recovered !== $admission || $p->snapshot()['frame_generation'] !== $generation) throw new RuntimeException('Recovery repeated effect');
    $result = ['label'=>'SYNTHETIC_NATIVE_PROTOCOL_ONLY_NOT_INSTALLATION_EVIDENCE',
        'real_keys_used'=>false,'real_installation_read'=>false,
        'private_bytes_unchanged'=>$private === hash_file('sha256',$f->root.'/var/imperium/bootstrap-state.json'),
        'original_occupancy_bytes_unchanged'=>$original === hash_file('sha256',$f->root.'/var/imperium/offices/garrison/occupancy/'.$f->occupancy['binding_id'].'.json'),
        'completed_recovery_without_new_frame'=>true,'policy'=>$f->policy,'public_fingerprint'=>$f->fingerprint(),
        'public_projection'=>$f->projection,'public_occupancy'=>$f->occupancy,'public_request_input'=>$requestInput,
        'admission'=>$admission,'inventory'=>$inventory,'command_di_outputs'=>$f->outputs,
        'garrison_command_di_outputs'=>$f->base->outputs,'exact_public_inputs'=>$inputs,
        'public_frames'=>array_map(fn($path)=>A::read($path),glob($f->root.'/var/imperium/native-authority/*.json')), ...A::flags()];
    $json = json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if (str_contains($json,Old::SECRET) || !$result['private_bytes_unchanged'] || !$result['original_occupancy_bytes_unchanged'])
        throw new RuntimeException('Synthetic preservation failed');
    echo $json,"\n";
} finally { $f->close(); }
