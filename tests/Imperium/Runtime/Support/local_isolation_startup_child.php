<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';
use App\ProtectedMission\WindowsPowerShell;
$p=proc_open([WindowsPowerShell::EXECUTABLE,'-NoProfile','-NonInteractive','-EncodedCommand',$argv[1]],
    [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,WindowsPowerShell::environment(),['bypass_shell'=>true]);
fclose($pipes[0]);echo stream_get_contents($pipes[1]);fclose($pipes[1]);
$error=stream_get_contents($pipes[2]);fclose($pipes[2]);
if($error!=='')fwrite(STDERR,'CHILD_STDERR_PRESENT');
exit(proc_close($p));
