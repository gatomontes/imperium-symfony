<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/tools/LocalIsolation.php';
// Fresh real Git fixture, not the selected owner's snapshot or trust.
$root=$argv[1];mkdir($root.'/source',0700,true);mkdir($root.'/hooks');
$git=static function(array $args)use($root):string {
 $p=proc_open(['git','-C',$root.'/source','-c','core.hooksPath='.$root.'/hooks','-c','core.fsmonitor=false',...$args],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
 fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException($err);return trim($out);
};
$git(['init','--quiet']);
foreach(LocalIsolation::PATHS as $path){if(!is_dir(dirname($root.'/source/'.$path)))mkdir(dirname($root.'/source/'.$path),0700,true);file_put_contents($root.'/source/'.$path,"Disposable mechanical rehearsal file: $path\n");}
$git(['add','.']);$git(['-c','user.name=Disposable','-c','user.email=test@example.invalid','-c','commit.gpgsign=false','commit','--quiet','-m','Local isolation disposable rehearsal']);
$commit=$git(['rev-parse','HEAD']);$inventory=LocalIsolation::materialize($root.'/source',$root.'/target',$commit);
LocalIsolation::writeNew($root.'/inventory.json',$inventory);
LocalIsolation::writeNew($root.'/plan.json',LocalIsolation::draft($inventory,$root.'/target',time()+1800));
LocalIsolation::writeNew($root.'/before.json',LocalIsolation::manifest($root.'/target'));
echo "DISPOSABLE_GIT_FIXTURE_READY\n";
