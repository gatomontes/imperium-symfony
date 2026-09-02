<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\ReproofV2\Records;

/** Verifier-owned Git object parser and dependency projection. Never invokes git or producer code. */
final class ReproofV2SourceProof
{
    private const array PATHS = [
        'composer.lock', 'src/Bootstrap/CanonicalJson.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionReceiptContract.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract.php',
        'src/ReproofV2/CaseProfile.php', 'src/ReproofV2/Contract.php', 'src/ReproofV2/LocalSourceCapture.php',
        'src/ReproofV2/PackageStore.php', 'src/ReproofV2/PayloadExclusion.php', 'src/ReproofV2/Records.php',
        'src/ReproofV2/Runner.php', 'src/ReproofV2/SourceBundle.php', 'tools/run-atomic-transition-reproof-v2.php',
    ];

    public function verify(array $source, array $trust): array
    {
        $this->require(Records::same($source, Records::seal($source))
            && $source['schema'] === 'imperium.atomic-transition-reproof.source-manifest/v2'
            && $source['object_format'] === 'sha1'
            && $source['commit'] === $trust['source_commit']);
        $keys = array_keys($source); sort($keys);
        $fields = ['schema', 'object_format', 'commit', 'commit_bytes', 'trees', 'files', 'manifest_root', 'record_digest']; sort($fields);
        $this->require($keys === $fields && array_keys($source['files']) === self::PATHS);
        $commit = $this->decode($source['commit_bytes']);
        $this->require($this->oid('commit', $commit) === $source['commit']);
        $this->require(1 === preg_match('/^tree ([a-f0-9]{40})\n/', $commit, $match));
        $root = $match[1];
        $trees = [];
        foreach ($source['trees'] as $oid => $encoded) {
            $bytes = $this->decode($encoded);
            $this->require($oid === $this->oid('tree', $bytes));
            $trees[$oid] = $this->treeEntries($bytes);
        }
        $manifest = []; $nodes = []; $visited = [];
        foreach (self::PATHS as $path) {
            $tree = $root; $parts = explode('/', $path); $filename = array_pop($parts);
            foreach ($parts as $part) {
                $visited[$tree] = true;
                $this->require(isset($trees[$tree][$part]) && '40000' === $trees[$tree][$part]['mode']);
                $tree = $trees[$tree][$part]['oid'];
            }
            $visited[$tree] = true;
            $entry = $trees[$tree][$filename] ?? null;
            $this->require(is_array($entry) && in_array($entry['mode'], ['100644', '100755'], true));
            $file = $source['files'][$path];
            $this->require(array_keys($file) === ['blob', 'bytes']);
            $bytes = $this->decode($file['bytes']);
            $this->require($file['blob'] === $entry['oid'] && $entry['oid'] === $this->oid('blob', $bytes));
            $hash = hash('sha256', $bytes);
            $manifest[$path] = ['blob' => $entry['oid'], 'sha256' => $hash];
            $imports = [];
            if (str_ends_with($path, '.php')) {
                $inUse = false;
                foreach (token_get_all($bytes) as $token) {
                    if (';' === $token) { $inUse = false; }
                    if (is_array($token) && T_USE === $token[0]) { $inUse = true; }
                    if ($inUse && is_array($token) && T_NAME_QUALIFIED === $token[0]) { $imports[] = $token[1]; }
                }
            }
            $nodes[] = ['path' => $path, 'sha256' => $hash, 'imports' => $imports];
        }
        $used = array_keys($visited); $supplied = array_keys($trees); sort($used); sort($supplied);
        $this->require($used === $supplied);
        $this->require(Records::hash($manifest) === $source['manifest_root'] && $source['manifest_root'] === $trust['source_manifest_root']);
        return ['manifest' => $manifest, 'graph' => [
            'schema' => 'imperium.atomic-transition-reproof.dependency-graph/v2',
            'scope' => 'PINNED_EXPLICIT_LOADER_AND_SOURCE_IMPORTS', 'nodes' => $nodes,
            'limits' => ['PHP_NATIVE_RUNTIME_TRUSTED', 'GIT_READ_ONLY_CAPTURE', 'LOCAL_PACKAGE_WRITER_ONLY', 'NO_VENDOR_BOOTSTRAP']]];
    }

    private function treeEntries(string $bytes): array
    {
        $offset = 0; $entries = [];
        while ($offset < strlen($bytes)) {
            $space = strpos($bytes, ' ', $offset); $nul = strpos($bytes, "\0", $offset);
            $this->require(false !== $space && false !== $nul && $space < $nul && $nul + 21 <= strlen($bytes));
            $mode = substr($bytes, $offset, $space - $offset);
            $name = substr($bytes, $space + 1, $nul - $space - 1);
            $this->require(in_array($mode, ['40000', '100644', '100755', '120000', '160000'], true)
                && '' !== $name && !str_contains($name, '/') && !isset($entries[$name]));
            $entries[$name] = ['mode' => $mode, 'oid' => bin2hex(substr($bytes, $nul + 1, 20))];
            $offset = $nul + 21;
        }
        return $entries;
    }

    private function decode(string $encoded): string
    {
        $this->require(strlen($encoded) < 8000000);
        $value = base64_decode($encoded, true);
        $this->require(false !== $value && base64_encode($value) === $encoded);
        return $value;
    }

    private function oid(string $type, string $bytes): string
    {
        return hash('sha1', $type.' '.strlen($bytes)."\0".$bytes);
    }

    private function require(bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('REPROOF_SOURCE_PROOF_REFUSED'); }
    }
}
