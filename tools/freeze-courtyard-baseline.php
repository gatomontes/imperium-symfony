<?php
declare(strict_types=1);
/** CY0 only: run against the exact preparation entry, before changing consumers. */
require dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/tests/Imperium/Runtime/Support/CitadelPublicationInterruption.php';
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture, FormationCustodyFixture, CitadelPublicationInterruption};
use App\Imperium\Runtime\Citadel\Formation\FormationJournal as J;

if ($argc !== 1 || !method_exists(App\Imperium\Runtime\Citadel\Formation\FormationPersonnel::class, 'currentCastellan')
    || method_exists(App\Imperium\Runtime\Citadel\Formation\FormationPersonnel::class, 'currentCourtthane')) { exit(64); }
$out = dirname(__DIR__).'/tests/Fixtures/courtyard-baseline';
if (is_dir($out)) { throw new RuntimeException('Frozen evidence already exists'); }
mkdir($out, 0700, true);
$manifest = ['entry_commit'=>'b1a8378668ecd9f4e4c1502411a2713111915535', 'entry_tree'=>'eae698493f2f679774c7e91975e05bbaf4cae039',
    'provenance'=>'PUBLIC_EPHEMERAL_SYNTHETIC_ONLY', 'fixtures'=>[]];
foreach (['child-publication', 'custody'] as $case) {
    $f = new CitadelFormationFixture();
    try {
        $f->appoint(); $id = $f->receive()['intake_id'];
        if ($case === 'child-publication') {
            $understanding=$f->understand($id); $f->draft($id); $review=$f->approve($f->present($id));
            $f->run('reserve-mission',['reviewId'=>$review['review_id']]);
            CitadelPublicationInterruption::$root=$f->root;
            try { $f->run('deliver-handoff',['intakeId'=>$id]); throw new RuntimeException('Missing interruption'); }
            catch (RuntimeException $e) { if (!str_contains($e->getMessage(),'SYNTHETIC_INTERRUPTION_AFTER_REAL_CHILD_RENAME')) { throw $e; } }
            $metadata=['intake_id'=>$id,'session_id'=>$understanding['claim']['session_id'],'attempt_id'=>$understanding['claim']['attempt_id'],
                'admitted'=>$understanding,'receipt_path'=>str_replace('\\','/',substr(CitadelPublicationInterruption::$publishedPath,strlen($f->root)+1))];
        } else {
            $c=new FormationCustodyFixture($f->root,$f->clock);
            $terms=$f->terms($id,'interview'); $terms['transport']=FormationCustodyFixture::authorization();
            $sid=$f->grant($id,'interview',$terms);
            $record=$c->cognition->call($sid,'frozen-custody-0001');
            $second=$f->receive('Independent pending old workflow.','frozen-pending-0001')['intake_id'];
            $pending=$f->grant($second,'interview'); $f->transport->unknown=true;
            try {$f->run('call',['sessionId'=>$pending,'attemptId'=>'frozen-unknown-0001']);} catch (RuntimeException $e) {
                if (!str_contains($e->getMessage(),'CMF059')) {throw $e;}
            }
            $metadata=['intake_id'=>$id,'session_id'=>$sid,'attempt_id'=>'frozen-custody-0001','admitted'=>$record,
                'pending_intake'=>$second,'pending_session'=>$pending,'unknown_attempt'=>'frozen-unknown-0001','counts'=>$c->counts()];
        }
        $files=[];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($f->root.'/var',FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && $file->getExtension()==='json') {
                $relative=str_replace('\\','/',substr($file->getPathname(),strlen($f->root)+1));
                $bytes=file_get_contents($file->getPathname());
                $files[$relative]=['sha256'=>hash('sha256',$bytes),'bytes_base64'=>base64_encode($bytes)];
            }
        }
        ksort($files);
        $bytes=gzencode(json_encode(['clock'=>$f->clock->at,'metadata'=>$metadata,'frame'=>$f->journal->read(),'files'=>$files],JSON_THROW_ON_ERROR),9);
        file_put_contents($out.'/'.$case.'.json.gz',$bytes);
        $manifest['fixtures'][$case]=['sha256'=>hash('sha256',$bytes),'bytes'=>strlen($bytes),'files'=>count($files),'frame_digest'=>$f->journal->read()['record_digest']];
    } finally { CitadelPublicationInterruption::$root=null; $f->close(); }
}
file_put_contents($out.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
echo json_encode($manifest,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
