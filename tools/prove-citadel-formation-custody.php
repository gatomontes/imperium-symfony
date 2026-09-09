<?php
declare(strict_types=1);
/** Offline public producer: creates its own disposable authority; accepts no root/config/credential arguments. */
require dirname(__DIR__).'/vendor/autoload.php';
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture,FormationCustodyFixture};
use App\Imperium\Runtime\Citadel\Formation\FormationJournal as J;
use App\Command\CitadelFormationCommand;
use Symfony\Component\Console\Tester\CommandTester;

if ($argc !== 1) { fwrite(STDERR,"No arguments accepted.\n"); exit(64); }
$f=new CitadelFormationFixture();
try {
    $f->appoint(); $id=$f->receive()['intake_id']; $c=new FormationCustodyFixture($f->root,$f->clock);
    $terms=$f->terms($id,'interview'); $terms['transport']=FormationCustodyFixture::authorization();
    $sid=$f->grant($id,'interview',$terms); $before=$f->journal->read();
    $path=$f->root.'/public-proof-request.json'; file_put_contents($path,json_encode(['operation'=>'call','arguments'=>['sessionId'=>$sid,'attemptId'=>'public-custody-0001']]));
    $tester=new CommandTester(new CitadelFormationCommand($c->cognition,$f->personnel,$f->signatures,$f->formation));
    if($tester->execute(['request-file'=>$path]) !== 0) { throw new RuntimeException($tester->getDisplay()); }
    $record=json_decode($tester->getDisplay(),true,512,JSON_THROW_ON_ERROR)['result']; $after=$f->journal->read();
    if($c->cognition->recover($sid,'public-custody-0001') !== $record || $c->counts() !== ['issue'=>1,'consume'=>1,'dispatch'=>1]) { throw new RuntimeException('Offline recovery failed'); }
    echo json_encode(['schema'=>'imperium.synthetic-formation-custody-proof/v1','provenance'=>'GENERATED_OFFLINE_FIXTURE_ONLY',
        'source_file'=>(new ReflectionClass(J::class))->getFileName(),'before'=>$before,'after'=>$after,'record'=>$record,'effect_counts'=>$c->counts(),
        'deployment_approved'=>false,'enrollment_authorized'=>false,'live_ready'=>false,'activation'=>false,'execution_authority'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
} finally { $f->close(); }
