<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use PHPUnit\Framework\TestCase;

final class JournalReviewerFreshReadTest extends TestCase
{
    public function testHistoricalBytesAndChainAreFreshlyCheckedAfterReuse():void
    {
        $root=sys_get_temp_dir().'/o4-review-'.bin2hex(random_bytes(8));
        mkdir($root,0700,true);
        try {
            $journal=new FormationJournal($root);
            for($i=1;$i<=3;$i++) {
                $journal->change(static function(array &$state)use($i):void {
                    $state=['onboarding'=>['evidence'=>['raw'=>str_repeat('synthetic-',800)],'acts'=>['raw'=>str_repeat('act-',1300)]],'sequence'=>$i];
                });
            }
            self::assertSame(3,$journal->read()['generation']);
            self::assertSame(3,$journal->read()['state']['sequence']);
            $directory=$root.'/var/imperium/citadel/formation';
            $path=$directory.'/000000000002.json';$original=file_get_contents($path);
            $frame=json_decode($original,true,512,JSON_THROW_ON_ERROR);
            $variants=[];
            $changed=$frame;$changed['state']['onboarding']['evidence']['raw']='changed';
            $variants[]=json_encode($changed,JSON_THROW_ON_ERROR);
            unset($changed['record_digest']);$changed['record_digest']=FormationJournal::digest($changed);
            $variants[]=json_encode($changed,JSON_THROW_ON_ERROR);
            $changed=$frame;$changed['previous_digest']=str_repeat('0',64);unset($changed['record_digest']);$changed['record_digest']=FormationJournal::digest($changed);
            $variants[]=json_encode($changed,JSON_THROW_ON_ERROR);
            $changed=$frame;$changed['generation']=4;unset($changed['record_digest']);$changed['record_digest']=FormationJournal::digest($changed);
            $variants[]=json_encode($changed,JSON_THROW_ON_ERROR);
            foreach($variants as $bytes) {
                file_put_contents($path,$bytes);
                try {$journal->read();self::fail('Corrupted chain was accepted');}
                catch(\RuntimeException $error) {self::assertSame('CMF003_JOURNAL_CHAIN_INVALID',$error->getMessage());}
                finally {file_put_contents($path,$original);}
                self::assertSame(3,$journal->read()['generation']);
            }
        } finally {
            $it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
            foreach($it as $file){$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());}
            rmdir($root);
        }
    }
}
