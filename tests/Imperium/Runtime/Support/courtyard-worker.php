<?php
declare(strict_types=1);
// Generated-root subprocess only; production commands accept no such overrides.
namespace App\Tests\Imperium\Runtime\Support {
    require dirname(__DIR__,4).'/vendor/autoload.php';
    require_once __DIR__.'/CitadelFormationFixture.php';
    $root=$argv[1] ?? ''; FormationCustodyFixture::assertRoot($root);
    $stage=$argv[2] ?? ''; $token=$argv[3] ?? ''; $name=$argv[4] ?? '';
    if (!preg_match('/^[a-z0-9-]{1,40}$/D',$token) || !in_array($name,['imperium:courtyard:formation','imperium:citadel:formation','imperium:courtyard:intake','imperium:citadel:intake'],true)
        || !in_array($stage,['normal','race','after-dispatch','after-envelope','after-child'],true)) { exit(64); }
    $GLOBALS['courtyard_root']=$root; $GLOBALS['courtyard_stage']=$stage;
    $input=json_decode(file_get_contents($root.'/worker-'.$token.'.json'),true,512,JSON_THROW_ON_ERROR);
    $clock=new SyntheticFormationClock(); $clock->at=$input['clock']; $c=new FormationCustodyFixture($root,$clock);
    $c->wire->response=CitadelFormationFixture::understanding();
    if ($stage==='race') {
        // Keep interview open so the losing different attempt reaches the budget boundary.
        $c->wire->response['disposition']='QUESTION'; $c->wire->response['question']='What is the remaining constraint?';
        $c->wire->response['ready_to_request_drafting']=false;
        $barrier=static function() use($root,$token): void {
            file_put_contents($root.'/ready-'.$token,'ready'); $deadline=microtime(true)+30;
            while (!is_file($root.'/release-workers')) { if(microtime(true)>$deadline) {exit(74);} usleep(10000); }
        };
        if (str_ends_with($name,':intake')) { $barrier(); } else { $c->wire->onPrepare=$barrier; }
    }
    if ($stage==='after-dispatch') { $c->wire->afterDispatch=static function(): never {exit(73);}; }
    $app=CourtyardApplication::build($c,$clock);
    try {
        if (str_ends_with($name,':intake')) {
            $tester=new \Symfony\Component\Console\Tester\CommandTester($app->find($name));
            $status=$tester->execute(['submission-id'=>'courtyard-race-intake-01','request-file'=>$root.'/intake.txt']);
            echo $tester->getDisplay(); exit($status);
        }
        $result=CourtyardApplication::run($app,$root,$name,$input['operation'],$input['arguments']);
        echo json_encode($result,JSON_THROW_ON_ERROR); exit(0);
    } catch (\Throwable $e) { echo $e->getMessage(); exit(1); }
}
namespace App\Imperium\Runtime\Persistence {
    function rename(string $from,string $to): bool
    {
        $result=\rename($from,$to); $path=str_replace('\\','/',$to); $prefix=str_replace('\\','/',$GLOBALS['courtyard_root']);
        if ($result && (($GLOBALS['courtyard_stage']==='after-envelope' && str_starts_with($path,$prefix.'/var/imperium/runtime/provider-response-envelopes/'))
            || ($GLOBALS['courtyard_stage']==='after-child' && str_starts_with($path,$prefix.'/var/imperium/citadel/children/') && str_contains($path,'/var/imperium/curia/handoffs/')))) { exit(73); }
        return $result;
    }
}
