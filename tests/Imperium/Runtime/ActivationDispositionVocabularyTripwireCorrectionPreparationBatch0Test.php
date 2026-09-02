<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/** Characterizes the defective detector; this is not the Batch 1 acceptance suite. */
final class ActivationDispositionVocabularyTripwireCorrectionPreparationBatch0Test extends TestCase
{
    private const string INVENTORY = 'docs/frozen-runtime-coverage-tripwire-restoration-activation-disposition-exceptions-v1.tsv';
    private const string METHOD = 'testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles';
    private const array VALUES = ['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];
    private string $root;
    private string|false $previousOverride;

    protected function setUp(): void
    {
        $this->previousOverride = getenv('IMPERIUM_FROZEN_COVERAGE_ROOT');
        $this->root = sys_get_temp_dir().'/imperium-vocabulary-preparation-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/docs', 0770, true);
        $this->copyDirectory(dirname(__DIR__, 3).'/src/Imperium/Runtime', $this->root.'/src/Imperium/Runtime');
        copy(dirname(__DIR__, 3).'/'.self::INVENTORY, $this->root.'/'.self::INVENTORY);
        putenv('IMPERIUM_FROZEN_COVERAGE_ROOT='.$this->root);
    }

    protected function tearDown(): void
    {
        putenv(false === $this->previousOverride ? 'IMPERIUM_FROZEN_COVERAGE_ROOT' : 'IMPERIUM_FROZEN_COVERAGE_ROOT='.$this->previousOverride);
        // Delete only the unique directory allocated by this test, never an environment override.
        $resolved = realpath($this->root);
        self::assertNotFalse($resolved);
        self::assertSame(realpath(sys_get_temp_dir()), dirname($resolved));
        self::assertStringStartsWith('imperium-vocabulary-preparation-', basename($resolved));
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($resolved);
    }

    public function testLexicalMatrixCharacterizesActualUnchangedDetector(): void
    {
        $cases = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/docs/activation-disposition-vocabulary-tripwire-correction-preparation-cases-v1.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertCount(27, $cases);
        $this->assertDetector(false, 'baseline');
        foreach (self::VALUES as $value) {
            foreach ($cases as $case) {
                $source = "<?php\n".$this->expand($case['source'], $value)."\n";
                // Parse as inert text. Never include, eval or execute fixture source.
                $tokens = token_get_all($source, TOKEN_PARSE);
                $kinds = array_map(static fn (array|string $token): string => is_array($token) ? token_name($token[0]) : $token, $tokens);
                self::assertContains($case['token'], $kinds, $case['id']);
                self::assertContains($case['batch1_classification'], ['EXACT_LITERAL', 'STATIC_MULTILINE', 'OUT_OF_CONTRACT_DYNAMIC', 'OUT_OF_CONTRACT_CONCAT', 'EXACT_LITERAL_COMPONENT', 'EXACT_LITERAL_DEFINITION', 'OUT_OF_CONTRACT_REFERENCE', 'NON_PRODUCER', 'LARGER_STRING', 'EXACT_LITERAL_NON_DECISION', 'DIFFERENT_VALUE']);
                file_put_contents($this->root.'/src/Imperium/Runtime/PreparationSynthetic.php', $source);
                $this->assertDetector($case['current_rejects'], $value.':'.$case['id']);
            }
        }
    }

    public function testEveryCurrentOccurrenceMatchesTheVersionedPreparationInventory(): void
    {
        $actual = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root.'/src/Imperium/Runtime'));
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $path = str_replace('\\', '/', substr($file->getPathname(), strlen($this->root) + 1));
            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (!is_array($token)) {
                    continue;
                }
                foreach (self::VALUES as $value) {
                    if (str_contains($token[1], $value)) {
                        self::assertSame(T_CONSTANT_ENCAPSED_STRING, $token[0], $path);
                        self::assertSame("'".$value."'", $token[1], $path);
                        $actual[] = $path."\t".$token[2]."\t".$value;
                    }
                }
            }
        }
        $expected = file(dirname(__DIR__, 3).'/docs/activation-disposition-vocabulary-tripwire-correction-preparation-occurrences-v1.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($expected);
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);
        self::assertCount(16, $actual);
        self::assertSame($expected, $actual);
    }

    public function testExactPathAdmissionRemovalAndRoleLimitations(): void
    {
        $inventoryPath = $this->root.'/'.self::INVENTORY;
        $original = (string) file_get_contents($inventoryPath);
        $rows = array_values(array_filter(explode("\n", trim($original)), static fn (string $line): bool => !str_starts_with($line, '#') && !str_starts_with($line, "classification\t")));
        self::assertCount(6, $rows);
        $this->assertDetector(false, 'all six admitted');
        foreach ($rows as $row) {
            $fields = explode("\t", trim($row));
            $path = $this->root.'/'.$fields[1];
            $source = (string) file_get_contents($path);
            file_put_contents($path, "<?php\n");
            $this->assertDetector(true, 'missing admitted path '.$fields[1]);
            file_put_contents($path, $source);
        }
        $synthetic = 'src/Imperium/Runtime/PreparationSynthetic.php';
        file_put_contents($this->root.'/'.$synthetic, "<?php\nconst OUTCOME = 'RETIRE_CORRIDOR';\n");
        $this->assertDetector(true, 'unclassified addition');
        $row = "SYNTHETIC_PREPARATION_ONLY\t".$synthetic."\tDisposable vocabulary definition\tFROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_BATCH_2\tProviderBindingActivationIntegrityRemediationBatch6Test::".self::METHOD."\n";
        file_put_contents($inventoryPath, rtrim($original)."\n".$row);
        $this->assertDetector(false, 'explicitly classified synthetic addition');
        file_put_contents($inventoryPath, $row, FILE_APPEND);
        $this->assertDetector(true, 'duplicate inventory path');
        file_put_contents($inventoryPath, rtrim($original)."\n".$row);
        unlink($this->root.'/'.$synthetic);
        $this->assertDetector(true, 'stale inventory row');
        file_put_contents($inventoryPath, $original);
        $fields = explode("\t", trim($rows[0]));
        $path = $this->root.'/'.$fields[1];
        file_put_contents($path, "<?php\n// 'RETIRE_CORRIDOR'\n");
        $this->assertDetector(false, 'comment alone preserves admitted path: semantic escape');
        $fields[0] = 'ARBITRARY_NONEMPTY_CLASSIFICATION';
        $fields[2] = 'Arbitrary nonempty role';
        file_put_contents($inventoryPath, str_replace(trim($rows[0]), implode("\t", $fields), $original));
        $this->assertDetector(false, 'role meaning is not mechanically validated');
    }

    private function assertDetector(bool $expectedFailure, string $label): void
    {
        $failed = false;
        try {
            (new ProviderBindingActivationIntegrityRemediationBatch6Test(self::METHOD))->testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles();
        } catch (AssertionFailedError) {
            $failed = true;
        }
        self::assertSame($expectedFailure, $failed, $label);
    }

    private function expand(string $source, string $value): string
    {
        return strtr($source, [
            '{{V}}' => $value,
            '{{INDEX}}' => (string) array_search($value, self::VALUES, true),
            '{{LOWER}}' => strtolower($value),
            '{{LEFT}}' => substr($value, 0, 6),
            '{{RIGHT}}' => substr($value, 6),
            '{{HEX}}' => implode('', array_map(static fn (string $char): string => sprintf('\\x%02x', ord($char)), str_split($value))),
            '{{OCT}}' => implode('', array_map(static fn (string $char): string => sprintf('\\%03o', ord($char)), str_split($value))),
            '{{UNICODE}}' => implode('', array_map(static fn (string $char): string => sprintf('\\u{%x}', ord($char)), str_split($value))),
        ]);
    }

    private function copyDirectory(string $source, string $target): void
    {
        mkdir($target, 0770, true);
        foreach (new \DirectoryIterator($source) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $destination = $target.'/'.$item->getFilename();
            $item->isDir() ? $this->copyDirectory($item->getPathname(), $destination) : copy($item->getPathname(), $destination);
        }
    }
}
