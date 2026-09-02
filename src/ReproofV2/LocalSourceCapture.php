<?php

declare(strict_types=1);

namespace App\ReproofV2;

/** Explicit local-only CLI boundary; not a service or a source of authorization. */
final class LocalSourceCapture
{
    public function capture(string $repository, string $approvedCommit): array
    {
        if (!preg_match('/^[a-f0-9]{40}$/D', $approvedCommit)
            || trim($this->git($repository, ['rev-parse', 'HEAD'])) !== $approvedCommit
            || '' !== trim($this->git($repository, ['status', '--porcelain', '--untracked-files=all', '--ignore-submodules=all']))) {
            throw new \RuntimeException('REPROOF_SOURCE_NOT_CLEAN_PINNED_HEAD');
        }
        if ('sha1' !== trim($this->git($repository, ['rev-parse', '--show-object-format']))) {
            throw new \RuntimeException('REPROOF_OBJECT_FORMAT_UNSUPPORTED');
        }
        $commit = $this->git($repository, ['cat-file', 'commit', $approvedCommit]);
        preg_match('/^tree ([a-f0-9]{40})\n/', $commit, $match);
        if (!isset($match[1])) { throw new \RuntimeException('REPROOF_COMMIT_INVALID'); }
        $trees = [$match[1] => base64_encode($this->git($repository, ['cat-file', 'tree', $match[1]]))];
        $files = [];
        foreach (SourceBundle::PATHS as $path) {
            $parts = explode('/', $path);
            array_pop($parts);
            $prefix = '';
            foreach ($parts as $part) {
                $prefix = '' === $prefix ? $part : $prefix.'/'.$part;
                $oid = trim($this->git($repository, ['rev-parse', $approvedCommit.':'.$prefix]));
                $trees[$oid] = base64_encode($this->git($repository, ['cat-file', 'tree', $oid]));
            }
            $oid = trim($this->git($repository, ['rev-parse', $approvedCommit.':'.$path]));
            $bytes = $this->git($repository, ['cat-file', 'blob', $oid]);
            if (is_link($repository.'/'.$path)) { throw new \RuntimeException('REPROOF_SOURCE_LINK_REFUSED'); }
            $local = @file_get_contents($repository.'/'.$path);
            if (false === $local || $bytes !== $local) { throw new \RuntimeException('REPROOF_LOADED_BYTES_DIFFER'); }
            $files[$path] = ['blob' => $oid, 'bytes' => base64_encode($bytes)];
        }
        ksort($trees, SORT_STRING);
        ksort($files, SORT_STRING);
        return Records::seal(['schema' => Contract::SCHEMAS['source'], 'object_format' => 'sha1',
            'commit' => $approvedCommit, 'commit_bytes' => base64_encode($commit), 'trees' => $trees, 'files' => $files,
            'manifest_root' => Records::hash(SourceBundle::manifest($files))]);
    }

    private function git(string $repository, array $arguments): string
    {
        $pipes = [];
        $process = proc_open(['git', '--no-pager', '-c', 'core.fsmonitor=false', '-C', $repository, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) { throw new \RuntimeException('REPROOF_GIT_REFUSED'); }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if (0 !== $status || false === $output) { throw new \RuntimeException('REPROOF_GIT_REFUSED'); }
        return $output;
    }
}
