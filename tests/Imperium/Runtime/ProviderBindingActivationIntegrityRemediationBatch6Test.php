<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch6Test extends TestCase
{
    public function testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles(): void
    {
        $override = getenv('IMPERIUM_FROZEN_COVERAGE_ROOT');
        $root = is_string($override) && '' !== $override
            ? rtrim($override, '/\\')
            : dirname(__DIR__, 3);
        $inventory = [];
        foreach (file(
            $root.'/docs/frozen-runtime-coverage-tripwire-restoration-activation-disposition-exceptions-v1.tsv',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
        ) ?: [] as $line) {
            if (str_starts_with($line, '#')
                || "classification\tpath\trole\tauthorizing_batch\tfocused_test" === $line) {
                continue;
            }
            [$classification, $path, $role, $batch, $test] = explode("\t", $line, 5);
            self::assertArrayNotHasKey($path, $inventory, $line);
            self::assertNotSame('', $classification, $path);
            self::assertNotSame('', $role, $path);
            self::assertSame('FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_BATCH_2', $batch, $path);
            self::assertStringContainsString(__FUNCTION__, $test, $path);
            $inventory[$path] = $classification;
        }
        ksort($inventory, SORT_STRING);

        $runtime = $root.'/src/Imperium/Runtime';
        $observed = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($runtime)) as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (str_contains($source, "'QUARANTINED_PENDING_REMEDIATION'")
                || str_contains($source, "'RETIRE_CORRIDOR'")) {
                $observed[] = str_replace(
                    '\\',
                    '/',
                    substr($file->getPathname(), strlen($root) + 1),
                );
            }
        }
        sort($observed, SORT_STRING);

        self::assertSame(array_keys($inventory), $observed);
    }

    public function testCampaignTerminatesWithoutImpliedAuthority(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-integrity-remediation-campaign-terminal.md');
        foreach (['CORRIDOR_DISPOSITION_REFUSED_PRINCIPAL_PROVENANCE_ABSENT', 'No implied continuation', 'authorizes no implementation batch', 'no disposition record', 'no successor authority', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
