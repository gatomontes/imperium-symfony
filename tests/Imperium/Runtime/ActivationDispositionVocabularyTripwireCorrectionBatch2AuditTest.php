<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/** Authored only after Batch 1 reached main at 76d0803ae9931c4470b4f49af216a56a14c111c3. */
final class ActivationDispositionVocabularyTripwireCorrectionBatch2AuditTest extends TestCase
{
    private const string INVENTORY = 'docs/frozen-runtime-coverage-tripwire-restoration-activation-disposition-exceptions-v1.tsv';
    private const string METHOD = 'testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles';
    private string $root;
    private string|false $priorOverride;

    protected function setUp(): void
    {
        $this->priorOverride = getenv('IMPERIUM_FROZEN_COVERAGE_ROOT');
        $this->root = sys_get_temp_dir().'/imperium-vocabulary-terminal-audit-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/docs', 0770, true);
        $this->copyTree(dirname(__DIR__, 3).'/src/Imperium/Runtime', $this->root.'/src/Imperium/Runtime');
        copy(dirname(__DIR__, 3).'/'.self::INVENTORY, $this->root.'/'.self::INVENTORY);
        putenv('IMPERIUM_FROZEN_COVERAGE_ROOT='.$this->root);
    }

    protected function tearDown(): void
    {
        putenv(false === $this->priorOverride ? 'IMPERIUM_FROZEN_COVERAGE_ROOT' : 'IMPERIUM_FROZEN_COVERAGE_ROOT='.$this->priorOverride);
        self::assertSame($this->priorOverride, getenv('IMPERIUM_FROZEN_COVERAGE_ROOT'));
        $resolved = realpath($this->root);
        self::assertNotFalse($resolved);
        self::assertSame(realpath(sys_get_temp_dir()), dirname($resolved));
        self::assertStringStartsWith('imperium-vocabulary-terminal-audit-', basename($resolved));
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($resolved);
    }

    public function testFreshAdversarialCasesAgainstMergedDetector(): void
    {
        $this->assertActual(false, 'unmodified full runtime and six roles');
        // Expectations authored here, not imported from the implementation's matrices or parser.
        $cases = [
            ['single', "return '@VALUE@';", true],
            ['double', 'return "@VALUE@";', true],
            ['binary', 'return b"@VALUE@";', true],
            ['escaped', 'return "@ENCODED@";', true],
            ['mixed', 'return "@FIRST@@REST@";', true],
            ['nowdoc', "return <<<'AUDIT'\n    @VALUE@\n    AUDIT;", true],
            ['heredoc', "return <<<AUDIT\r\n\t@ENCODED@\r\n\tAUDIT;", true],
            ['nested_literal', '$x = "{$row[\'@VALUE@\']}";', true],
            ['match_literal', '$x = match ($input) { 0 => "@VALUE@", default => null };', true],
            ['array_key', '$x = ["@VALUE@" => 1];', true],
            ['comment_quotes', "// '@VALUE@'\n/* \"@VALUE@\" */\n/** @ENCODED@ */", false],
            ['larger_quoted', '$x = "Example: \'@VALUE@\'";', false],
            ['larger_escaped', '$x = "@ENCODED@\\x20";', false],
            ['literal_escape_text', "\$x = '@ENCODED@';", false],
            ['interpolation', '$x = "@VALUE@{$suffix}";', false],
            ['heredoc_interpolation', '$x = <<<AUDIT'."\n".'@VALUE@{$suffix}'."\nAUDIT;", false],
            ['split', 'return "@LEFT@" . "@RIGHT@";', false],
            ['constant_reference', 'return External::OUTCOME;', false],
            ['assembled', 'return implode("", ["@LEFT@", "@RIGHT@"]);', false],
            ['halted_data', "__halt_compiler(); '@VALUE@'", false],
        ];
        foreach (['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'] as $value) {
            foreach ($cases as [$label, $template, $rejects]) {
                $source = "<?php\n".strtr($template, [
                    '@VALUE@' => $value,
                    '@ENCODED@' => implode('', array_map(static fn (string $char): string => sprintf('\\x%02X', ord($char)), str_split($value))),
                    '@FIRST@' => sprintf('\\u{%X}', ord($value[0])),
                    '@REST@' => substr($value, 1),
                    '@LEFT@' => substr($value, 0, 4),
                    '@RIGHT@' => substr($value, 4),
                ])."\n";
                token_get_all($source, TOKEN_PARSE);
                file_put_contents($this->root.'/src/Imperium/Runtime/AuditProbe.php', $source);
                $this->assertActual($rejects, $value.':'.$label);
            }
        }
    }

    public function testCacheCannotConcealSourceChangesOrInventAdmission(): void
    {
        $relative = 'src/Imperium/Runtime/AuditProbe.php';
        $path = $this->root.'/'.$relative;
        $stamp = time() - 100;
        // Same length, same path and same timestamp, but different exact source bytes.
        foreach (['RETIRE_CORRIDOR' => true, 'retire_corridor' => false] as $value => $rejects) {
            file_put_contents($path, '<?php return "'.$value.'";');
            touch($path, $stamp);
            $this->assertActual($rejects, 'same-length source rewrite '.$value);
        }
        file_put_contents($path, '<?php return "RETIRE_CORRIDOR";');
        touch($path, $stamp);
        $this->assertActual(true, 'return to previously rejected exact source');
        $inventory = (string) file_get_contents($this->root.'/'.self::INVENTORY);
        $row = "AUDIT_SYNTHETIC_ONLY\t".$relative."\tDisposable complete literal\tFROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_BATCH_2\tProviderBindingActivationIntegrityRemediationBatch6Test::".self::METHOD."\n";
        file_put_contents($this->root.'/'.self::INVENTORY, rtrim($inventory)."\n".$row);
        $this->assertActual(false, 'same cached source now explicitly admitted');
        file_put_contents($this->root.'/'.self::INVENTORY, $inventory);
        $this->assertActual(true, 'same cached source loses inventory admission');
        unlink($path);
        $this->assertActual(false, 'source addition removed');

        $rows = array_values(array_filter(explode("\n", trim($inventory)), static fn (string $line): bool => !str_starts_with($line, '#') && !str_starts_with($line, "classification\t")));
        self::assertCount(6, $rows);
        foreach ($rows as $row) {
            $admitted = $this->root.'/'.explode("\t", $row)[1];
            $original = (string) file_get_contents($admitted);
            file_put_contents($admitted, "<?php\n/** 'RETIRE_CORRIDOR' */\n");
            $this->assertActual(true, 'comment cannot satisfy '.$admitted);
            file_put_contents($admitted, $original);
            $this->assertActual(false, 'restore original source '.$admitted);
        }
    }

    public function testClosureRetainsTheNarrowClaimAndIndependentVerificationQualification(): void
    {
        $repository = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($repository.'/docs/activation-disposition-vocabulary-tripwire-correction-batch-2-blackquill-audit.md');
        $handoff = (string) file_get_contents($repository.'/docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-complete.md');
        foreach ([$audit, $handoff] as $document) {
            foreach ([
                'ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_COMPLETE',
                'BATCH_2_TERMINAL_BLACKQUILL_AUDIT_PASSED_LITERAL_VOCABULARY_CLAIM',
                'FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CORRECTED_CLOSURE_ACCEPTED_LITERAL_VOCABULARY_ONLY',
                '76d0803ae9931c4470b4f49af216a56a14c111c3',
                'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT',
            ] as $boundary) {
                self::assertStringContainsString($boundary, $document);
            }
        }
        foreach (['OUT_OF_CONTRACT_CONCAT', 'OUT_OF_CONTRACT_REFERENCE', 'OUT_OF_CONTRACT_DYNAMIC', 'same agent', 'not a claim of independent human review'] as $limit) {
            self::assertStringContainsString($limit, $audit);
        }
        self::assertStringContainsString('No correction campaign batch remains', $handoff);
        $historical = (string) file_get_contents($repository.'/docs/next-campaign-activation-disposition-vocabulary-tripwire-correction.md');
        self::assertStringContainsString('FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CLOSURE_REJECTED_WITH_MATERIAL_VOCABULARY_TRIPWIRE_GAP', $historical);
    }

    private function assertActual(bool $rejects, string $case): void
    {
        $failed = false;
        try {
            (new ProviderBindingActivationIntegrityRemediationBatch6Test(self::METHOD))->testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles();
        } catch (AssertionFailedError $error) {
            $failed = true;
            // Valid adversarial cases must fail exact inventory equality, not an unrelated parse error.
            self::assertStringContainsString('Failed asserting that two arrays are identical', $error->getMessage(), $case);
        }
        self::assertSame($rejects, $failed, $case);
    }

    private function copyTree(string $source, string $target): void
    {
        mkdir($target, 0770, true);
        foreach (new \DirectoryIterator($source) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $destination = $target.'/'.$entry->getFilename();
            $entry->isDir() ? $this->copyTree($entry->getPathname(), $destination) : copy($entry->getPathname(), $destination);
        }
    }
}
