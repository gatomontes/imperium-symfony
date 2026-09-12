<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/vendor/autoload.php';
use App\Imperium\Runtime\Onboarding\DeepSeek\EnvelopeStore;
[$script,$root,$token,$mode]=$argv;
$envelope=json_decode(file_get_contents($root.'/input.json'),true,32,JSON_THROW_ON_ERROR);
if ($mode==='conflict') { $envelope['response']=' '.$envelope['response']; $envelope['metadata']['response_digest']=\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::hash($envelope['response']); }
$store=new EnvelopeStore($root.'/responses',static function(string $at)use($mode):void { if ($at===$mode) { exit(73); } });
file_put_contents($root.'/ready-'.$token,'ready');
$deadline=microtime(true)+20;
while (!is_file($root.'/go')) { if(microtime(true)>$deadline){exit(74);} usleep(10000); }
try { $store->retain($envelope); exit(0); } catch (\Throwable) { exit(2); }
