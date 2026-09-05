<?php
declare(strict_types=1);
namespace App\ProtectedMission;

final class InspectionProcess
{
    public static function run(string $root,array $payload):array
    {
        $base=$root.'/inspection-'.bin2hex(random_bytes(16));
        file_put_contents($base.'.input',json_encode($payload,JSON_THROW_ON_ERROR));
        $process=null;
        try {
            $deadline=microtime(true)+$payload['budget']['max_seconds'];
            $process=proc_open([PHP_BINARY,dirname(__DIR__,2).'/bin/protected-git-worker.php',$base.'.input'],
                [0=>['pipe','r'],1=>['file',$base.'.output','w'],2=>['file',$base.'.error','w']],$pipes,null,null,['bypass_shell'=>true]);
            if (!is_resource($process)) throw new \RuntimeException('PMA_INSPECTION_START_FAILED');
            fclose($pipes[0]);
            do {
                $status=proc_get_status($process);
                if (!$status['running']) break;
                if (microtime(true)>=$deadline) {proc_terminate($process);throw new \RuntimeException('PMA_INSPECTION_TIME_BUDGET');}
                usleep(1000);
            } while (true);
            proc_close($process);$process=null;
            if (filesize($base.'.output')>12000000) throw new \RuntimeException('PMA_INSPECTION_OUTPUT_LIMIT');
            $result=json_decode(file_get_contents($base.'.output'),true,128,JSON_THROW_ON_ERROR);
            if (($result['ok'] ?? false)!==true) throw new \RuntimeException($result['error'] ?? 'PMA_INSPECTION_FAILED');
            if (microtime(true)>=$deadline) throw new \RuntimeException('PMA_INSPECTION_TIME_BUDGET');
            return $result['snapshot'];
        } finally {
            if (is_resource($process)) {proc_terminate($process);proc_close($process);}
            foreach (['.input','.output','.error'] as $suffix) if (is_file($base.$suffix)) unlink($base.$suffix);
        }
    }
}
