<?php
declare(strict_types=1);
namespace App\ProtectedMission;

/** Reads loose SHA-1 Git objects directly. No Git process, config, hook or lazy fetch can execute. */
final class OfflineGitInspector
{
    private int $used=0;
    private float $deadline;
    private string $objects;
    public function inspect(array $payload):array
    {
        $budget=$payload['budget']; $paths=$payload['paths'];
        if (count($paths)>$budget['max_files'] || count($paths)>$budget['max_findings']) throw new \RuntimeException('PMA_INSPECTION_BUDGET');
        $requested=$payload['target']['repository'];
        if (!is_string($requested) || str_contains($requested,"://") || str_starts_with($requested,'\\\\') || str_starts_with($requested,'//')) throw new \RuntimeException('PMA_LOCAL_FILESYSTEM_REQUIRED');
        $repository=realpath($requested);
        if ($repository===false || !is_dir($repository.'/.git/objects') || is_link($repository.'/.git') || is_link($repository.'/.git/objects')) throw new \RuntimeException('PMA_LOOSE_GIT_REPOSITORY_REQUIRED');
        $this->objects=realpath($repository.'/.git/objects');
        $this->deadline=microtime(true)+$budget['max_seconds']; $this->used=0;
        $commit=$this->object($payload['target']['commit'],'commit',$budget['max_bytes']);
        if (!preg_match('/^tree ([a-f0-9]{40})\n/',$commit,$match) || $match[1]!==$payload['target']['tree']) throw new \RuntimeException('PMA_GIT_TREE_MISMATCH');
        $root=$this->object($match[1],'tree',$budget['max_bytes']); $findings=[];
        foreach ($paths as $path) {
            $tree=$root; $parts=explode('/',$path); $entry=null;
            foreach ($parts as $index=>$part) {
                $entry=$this->entry($tree,$part);
                if ($index<count($parts)-1) {
                    if ($entry['mode']!=='40000') throw new \RuntimeException('PMA_GIT_PATH_INVALID');
                    $tree=$this->object($entry['id'],'tree',$budget['max_bytes']);
                }
            }
            if (!in_array($entry['mode'],['100644','100755'],true)) throw new \RuntimeException('PMA_GIT_NON_REGULAR_FILE');
            $bytes=$this->object($entry['id'],'blob',$budget['max_bytes']);
            $findings[]=['path'=>$path,'blob_id'=>$entry['id'],'bytes_base64'=>base64_encode($bytes),'byte_length'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)];
        }
        $this->checkTime();
        return ['commit_id'=>$payload['target']['commit'],'commit_verified'=>true,'tree_id'=>$payload['target']['tree'],'tree_verified'=>true,
            'findings'=>$findings,'object_bytes_read'=>$this->used,'git_network_or_subprocess_used'=>false,'target_mutation_performed'=>false,
            'adapter'=>'offline-loose-sha1-v1'];
    }
    private function object(string $id,string $type,int $maximum):string
    {
        $this->checkTime();
        if (!preg_match('/^[a-f0-9]{40}$/D',$id)) throw new \RuntimeException('PMA_GIT_OBJECT_INVALID');
        $path=$this->objects.'/'.substr($id,0,2).'/'.substr($id,2);
        $resolved=realpath($path);
        if ($resolved===false) throw new \RuntimeException('PMA_LOOSE_OBJECT_ABSENT_NO_FETCH');
        $prefix=str_replace('\\','/',$this->objects).'/';
        if (!str_starts_with(str_replace('\\','/',$resolved),$prefix) || is_link(dirname($path)) || is_link($path)) throw new \RuntimeException('PMA_GIT_OBJECT_PATH_ESCAPE');
        $remaining=$maximum-$this->used;
        if ($remaining<1 || filesize($resolved)>$remaining+1024) throw new \RuntimeException('PMA_INSPECTION_BUDGET');
        $compressed=file_get_contents($resolved,false,null,0,$remaining+1025);
        $raw=@gzuncompress($compressed,$remaining+128);
        if (!is_string($raw)) throw new \RuntimeException('PMA_GIT_OBJECT_OR_BUDGET_INVALID');
        $nul=strpos($raw,"\0");
        if ($nul===false || substr($raw,0,$nul)!==$type.' '.(strlen($raw)-$nul-1) || sha1($raw)!==$id) throw new \RuntimeException('PMA_GIT_OBJECT_INVALID');
        $bytes=substr($raw,$nul+1);
        if (strlen($bytes)>$remaining) throw new \RuntimeException('PMA_INSPECTION_BUDGET');
        $this->used+=strlen($bytes); $this->checkTime();
        return $bytes;
    }
    private function entry(string $tree,string $name):array
    {
        $offset=0;
        while ($offset<strlen($tree)) {
            $space=strpos($tree,' ',$offset); $nul=strpos($tree,"\0",$offset);
            if ($space===false || $nul===false || $space>$nul || $nul+21>strlen($tree)) throw new \RuntimeException('PMA_GIT_TREE_INVALID');
            $mode=substr($tree,$offset,$space-$offset); $path=substr($tree,$space+1,$nul-$space-1); $id=bin2hex(substr($tree,$nul+1,20));
            if ($path===$name) return ['mode'=>$mode,'id'=>$id];
            $offset=$nul+21;
        }
        throw new \RuntimeException('PMA_GIT_PATH_ABSENT');
    }
    private function checkTime():void { if (microtime(true)>=$this->deadline) throw new \RuntimeException('PMA_INSPECTION_TIME_BUDGET'); }
}
