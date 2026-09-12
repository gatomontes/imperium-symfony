<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Bootstrap;

use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};

/** Common publication fence. Locked methods never acquire another lock. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class OperatorRootOwnership
{
    private FormationJournal $journal;
    private string $root;
    public function __construct(string $root)
    {
        $this->root=realpath($root) ?: $root;
        $this->journal=new FormationJournal($root);
    }
    public function identity():string
    {
        $path=str_replace('\\','/',$this->root);
        if(PHP_OS_FAMILY==='Windows'){$path=strtolower($path);}
        return R::hash(['imperium.operator-root-owner/v1',$path]);
    }
    public function native(callable $publication):mixed
    {
        return $this->journal->inspect(function(array $frame)use($publication):mixed{
            if(isset($frame['state']['onboarding'])){
                $s=$frame['state']['onboarding'];
                R::require(in_array($s['schema']??null,['imperium.onboarding-authority-state/v1','imperium.onboarding-authority-state/v2','imperium.onboarding-authority-state/v3'],true),'ROOT_STATE_VERSION');
                if($s['schema']!=='imperium.onboarding-authority-state/v1'){\App\Imperium\Runtime\Onboarding\Ledger\LedgerState::validate($s);}
                if(($s['bindings']??[])!==[]){throw new \RuntimeException('B225_FRESH_ROOT_OWNED');}
            }
            return $publication();
        });
    }
    /** Called only by the FRESH producer inside its owning changeAtHead callback. */
    public function vacant(AuthorityStore $store):void
    {
        $this->assertStore($store);
        foreach([$this->root.'/var/imperium/operator-root',$this->root.'/var/imperium/offices',$this->root.'/var/imperium/imperator/founding-augur-model-assignments'] as $directory){
            if(!file_exists($directory) && !is_link($directory)){continue;}
            R::require(is_dir($directory) && !is_link($directory),'ROOT_AMBIGUOUS');
            $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST);$count=0;
            foreach($iterator as $file){
                // Native partials, seals and even unknown files are evidence against a FRESH root.
                R::require(++$count<=256 && $iterator->getDepth()<=8,'ROOT_SCAN_LIMIT');
                R::require($file->isDir() && !$file->isLink(),'ROOT_NOT_FRESH');
            }
        }
    }
    public function assertStore(AuthorityStore $store):void
    {
        R::require($this->journal->sameOwner($store->journal),'ROOT_OWNER_MISMATCH');
    }
}
