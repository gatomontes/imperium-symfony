<?php
declare(strict_types=1);
// Test-only subprocess; no production command accepts these substitutions.
namespace App\Tests\Imperium\Runtime\Support {
    require dirname(__DIR__,4).'/vendor/autoload.php';
    require_once __DIR__.'/CitadelFormationFixture.php';
    require_once __DIR__.'/FormationCustodyFixture.php';
    $root=$argv[1] ?? ''; FormationCustodyFixture::assertRoot($root);
    $stage=$argv[2] ?? ''; $token=$argv[3] ?? '';
    if (!preg_match('/^[a-z0-9-]{1,40}$/D',$token) || !in_array($stage,['normal','race','before-delivery','after-delivery','after-issue','before-dispatch','after-dispatch','after-envelope','after-response'],true)) { exit(64); }
    $GLOBALS['custody_synthetic_root']=$root; $GLOBALS['custody_synthetic_stage']=$stage;
    $input=json_decode((string)file_get_contents($root.'/custody-worker-input.json'),true,512,JSON_THROW_ON_ERROR);
    $clock=new SyntheticFormationClock(); $clock->at=$input['clock']; $c=new FormationCustodyFixture($root,$clock);
    $stop=static function(): never { exit(73); };
    match($stage) {
        'before-delivery'=>$c->wire->onPrepare=$stop,
        'after-delivery'=>$c->credentials->onIssue=$stop,
        'after-issue'=>$c->credentials->onConsume=$stop,
        'before-dispatch'=>$c->wire->beforeDispatch=$stop,
        'after-dispatch'=>$c->wire->afterDispatch=$stop,
        'after-response'=>$c->credentials->afterCallback=$stop,
        default=>null,
    };
    if ($stage==='race') {
        file_put_contents($root.'/ready-'.$token,'ready'); $deadline=microtime(true)+30;
        while(!is_file($root.'/release-workers')) { if(microtime(true)>$deadline) { exit(74); } usleep(10000); }
    }
    try {
        $result=$c->broker->invoke(...$input['call']);
        echo json_encode(['provenance'=>$result['provenance'],'response_sha256'=>hash('sha256',$result['response']),'counts'=>$c->counts()]); exit(0);
    } catch (\Throwable $e) { echo 'CUSTODY_REFUSED'; exit(2); }
}
namespace App\Imperium\Runtime\Persistence {
    // Fault after real immutable envelope publication, before custody receipt publication.
    function rename(string $from,string $to): bool
    {
        $result=\rename($from,$to);
        $prefix=str_replace('\\','/',$GLOBALS['custody_synthetic_root']).'/var/imperium/runtime/provider-response-envelopes/';
        if($result && $GLOBALS['custody_synthetic_stage']==='after-envelope' && str_starts_with(str_replace('\\','/',$to),$prefix)) { exit(73); }
        return $result;
    }
}
