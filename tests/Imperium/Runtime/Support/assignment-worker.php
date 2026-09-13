<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation {
    // Test-only process interruption at the actual fsynced-file publication point.
    function rename(string $from,string $to):bool {
        if(($GLOBALS['assignment_fault_mode']??null)==='before-publication'){exit(73);}
        $ok=\rename($from,$to);
        if($ok && ($GLOBALS['assignment_fault_mode']??null)==='after-publication'){exit(74);}
        return $ok;
    }
}
namespace {
require dirname(__DIR__,4).'/vendor/autoload.php';
use App\Tests\Imperium\Runtime\Support\AssignmentProcessContext;
use App\Imperium\Runtime\Onboarding\Ledger\{Recovery,CustodyCoordinator};
$root=$argv[1];$mode=$argv[2];$resolved=realpath($root);$temporary=realpath(sys_get_temp_dir());
$GLOBALS['assignment_fault_mode']=$mode;
if($resolved===false || $temporary===false || !str_starts_with(strtolower($resolved),strtolower($temporary.DIRECTORY_SEPARATOR).'imperium-o2-test-')){exit(91);}
$input=json_decode(file_get_contents($root.'/assignment-worker-input.json'),true,512,JSON_THROW_ON_ERROR);
[$store,$ledger,$settings]=AssignmentProcessContext::open($root,$input,$mode);
if($mode==='snapshot'){echo json_encode($settings->snapshot(),JSON_THROW_ON_ERROR);exit(0);}
if($mode==='resolve'){echo json_encode($settings->resolve('courtyard.courtthane'),JSON_THROW_ON_ERROR);exit(0);}
file_put_contents($root.'/assignment-ready-'.$mode,'ready');$deadline=microtime(true)+120;
while(!is_file($root.'/assignment-go')){if(microtime(true)>$deadline){exit(92);}usleep(10000);}
$q=$input['requests'][$mode]??$input['request'];if($mode==='different-id'){$q['command_id']='different-command-01';}
try{
    $raw=json_encode($q,JSON_THROW_ON_ERROR);
    $result=match($q['schema']){'imperium.assignment-change/v1'=>$ledger->replace($raw),'imperium.provider-onboarding-resume/v2'=>(new Recovery($ledger,new CustodyCoordinator($ledger)))->resume($raw),default=>$ledger->advance($raw)};
    echo $result['status']."\n";exit(0);
}catch(\Throwable $e){echo $e->getMessage()."\n";exit(23);}
}
