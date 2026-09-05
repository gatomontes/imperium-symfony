<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Read-only adapter that derives all inspected bytes from exact Git objects. */
final readonly class GitRepositorySnapshotAdapter
{
    public function __construct(private string $repository)
    {
        if (!is_dir($repository.'/.git') && !is_file($repository.'/.git')) {
            throw new \RuntimeException('MIS430_GIT_REPOSITORY_INVALID');
        }
    }

    /** @param list<string> $paths */
    public function inspect(string $commit, array $paths): array
    {
        if (1 !== preg_match('/^[a-f0-9]{40}$/', $commit) || [] === $paths || !array_is_list($paths)) {
            throw new \RuntimeException('MIS431_GIT_SNAPSHOT_REQUEST_INVALID');
        }
        foreach ($paths as $path) {
            if (!is_string($path) || '' === $path || str_starts_with($path, '-') || str_contains($path, "\0") || str_contains($path, '\\')) {
                throw new \RuntimeException('MIS431_GIT_SNAPSHOT_REQUEST_INVALID');
            }
        }

        $commitId = trim($this->git(['rev-parse', '--verify', $commit.'^{commit}']));
        if (!hash_equals($commit, $commitId)) { throw new \RuntimeException('MIS432_GIT_COMMIT_IDENTITY_INVALID'); }
        $commitBytes = $this->git(['cat-file', 'commit', $commit]);
        if (!hash_equals($commit, trim($this->gitWithInput(['hash-object', '-t', 'commit', '--stdin'], $commitBytes)))) {
            throw new \RuntimeException('MIS432_GIT_COMMIT_IDENTITY_INVALID');
        }
        $treeId = trim($this->git(['rev-parse', '--verify', $commit.'^{tree}']));
        $treeBytes = $this->git(['cat-file', 'tree', $treeId]);
        if (!hash_equals($treeId, trim($this->gitWithInput(['hash-object', '-t', 'tree', '--stdin'], $treeBytes)))) {
            throw new \RuntimeException('MIS433_GIT_TREE_IDENTITY_INVALID');
        }

        $blobs = [];
        foreach ($paths as $path) {
            $entry = $this->git(['ls-tree', '-z', $commit, '--', $path]);
            if ('' === $entry || 1 !== preg_match('/^([0-7]{6}) blob ([a-f0-9]{40})\t([^\0]+)\0$/s', $entry, $matches)
                || $matches[3] !== $path) {
                throw new \RuntimeException('MIS434_GIT_BLOB_IDENTITY_INVALID');
            }
            $bytes = $this->git(['cat-file', 'blob', $matches[2]]);
            $verifiedBlob = trim($this->gitWithInput(['hash-object', '-t', 'blob', '--stdin'], $bytes));
            if (!hash_equals($matches[2], $verifiedBlob)) {
                throw new \RuntimeException('MIS434_GIT_BLOB_IDENTITY_INVALID');
            }
            $blobs[] = [
                'path' => $path,
                'mode' => $matches[1],
                'blob_id' => $matches[2],
                'byte_length' => strlen($bytes),
                'content_sha256' => hash('sha256', $bytes),
                'bytes' => $bytes,
            ];
        }

        return [
            'repository' => realpath($this->repository) ?: $this->repository,
            'commit_id' => $commitId,
            'commit_verified' => true,
            'tree_id' => $treeId,
            'tree_verified' => true,
            'blobs' => $blobs,
        ];
    }

    private function git(array $arguments): string
    {
        return $this->run($arguments, null);
    }

    private function gitWithInput(array $arguments, string $input): string
    {
        return $this->run($arguments, $input);
    }

    private function run(array $arguments, ?string $input): string
    {
        $process = proc_open(
            ['git', '-C', $this->repository, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );
        if (!is_resource($process)) { throw new \RuntimeException('MIS435_GIT_OBJECT_READ_FAILED'); }
        if (null !== $input) { fwrite($pipes[0], $input); }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        stream_get_contents($pipes[2]); fclose($pipes[2]);
        if (0 !== proc_close($process) || false === $output) { throw new \RuntimeException('MIS435_GIT_OBJECT_READ_FAILED'); }
        return $output;
    }
}
