<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
$root=$argv[1];$resolved=realpath($root);$temporary=realpath(sys_get_temp_dir());
if($resolved===false || $temporary===false || !str_starts_with(strtolower($resolved),strtolower($temporary.DIRECTORY_SEPARATOR).'imperium-o2-test-')){exit(91);}
file_put_contents($root.'/native-ready','ready');$deadline=microtime(true)+120;
while(!is_file($root.'/native-go')){if(microtime(true)>$deadline){exit(92);}usleep(10000);}
file_put_contents($root.'/native-attempted','attempted');
try{(new App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService(new App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService($root)))->install('changed-instance');echo "NATIVE_PUBLISHED\n";exit(0);}
catch(RuntimeException $e){echo $e->getMessage()."\n";exit($e->getMessage()==='B225_FRESH_ROOT_OWNED'?23:24);}
