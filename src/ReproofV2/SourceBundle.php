<?php

declare(strict_types=1);

namespace App\ReproofV2;

/** Pure manifest projections. Collection and file loading belong to the CLI boundary. */
final class SourceBundle
{
    public const array PATHS = [
        'composer.lock', 'src/Bootstrap/CanonicalJson.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionReceiptContract.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator.php',
        'src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract.php',
        'src/ReproofV2/CaseProfile.php', 'src/ReproofV2/Contract.php',
        'src/ReproofV2/LocalSourceCapture.php', 'src/ReproofV2/PackageStore.php',
        'src/ReproofV2/PayloadExclusion.php', 'src/ReproofV2/Records.php',
        'src/ReproofV2/Runner.php', 'src/ReproofV2/SourceBundle.php',
        'tools/run-atomic-transition-reproof-v2.php',
    ];

    public static function manifest(array $files): array
    {
        $manifest = [];
        foreach ($files as $path => $file) {
            $bytes = base64_decode($file['bytes'], true);
            if (false === $bytes) {
                throw new \RuntimeException('REPROOF_SOURCE_ENCODING_INVALID');
            }
            $manifest[$path] = ['blob' => $file['blob'], 'sha256' => hash('sha256', $bytes)];
        }
        ksort($manifest, SORT_STRING);
        return $manifest;
    }

    public static function graph(array $source): array
    {
        $nodes = [];
        foreach (self::manifest($source['files']) as $path => $binding) {
            $imports = [];
            if (str_ends_with($path, '.php')) {
                $tokens = token_get_all(base64_decode($source['files'][$path]['bytes'], true));
                foreach ($tokens as $i => $token) {
                    if (is_array($token) && T_USE === $token[0]) {
                        for ($j = $i + 1; isset($tokens[$j]) && ';' !== $tokens[$j]; ++$j) {
                            if (is_array($tokens[$j]) && T_NAME_QUALIFIED === $tokens[$j][0]) {
                                $imports[] = $tokens[$j][1];
                            }
                        }
                    }
                }
            }
            $nodes[] = ['path' => $path, 'sha256' => $binding['sha256'], 'imports' => $imports];
        }
        return ['schema' => 'imperium.atomic-transition-reproof.dependency-graph/v2',
            'scope' => 'PINNED_EXPLICIT_LOADER_AND_SOURCE_IMPORTS', 'nodes' => $nodes,
            'limits' => ['PHP_NATIVE_RUNTIME_TRUSTED', 'GIT_READ_ONLY_CAPTURE', 'LOCAL_PACKAGE_WRITER_ONLY', 'NO_VENDOR_BOOTSTRAP']];
    }
}
