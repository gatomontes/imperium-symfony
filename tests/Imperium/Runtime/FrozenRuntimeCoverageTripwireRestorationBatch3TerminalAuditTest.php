<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

final class FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest extends TestCase
{
    /** @var list<string> */
    private array $roots = [];

    protected function tearDown(): void
    {
        putenv('IMPERIUM_FROZEN_COVERAGE_ROOT');
        foreach ($this->roots as $root) {
            $this->remove($root);
        }
    }

    public function testNewUnsnapshottedCandidateFailsClosed(): void
    {
        $root = $this->seedRoot();
        $this->writePhp(
            $root,
            'src/Imperium/Runtime/AuditSynthetic/UnapprovedCandidate.php',
            "final class UnapprovedCandidate { public bool \$authority_exercisable = true; }",
        );

        $this->assertCoverageFailure(
            'testMechanicalRuntimeCoverageMatchesTheFrozenBatch12Snapshot',
        );
    }

    public function testNewAuthorityConsumptionStoreConsumerFailsClosed(): void
    {
        $root = $this->seedRoot();
        $this->writePhp(
            $root,
            'src/Imperium/Runtime/AuditSynthetic/UnapprovedStoreConsumer.php',
            'final class UnapprovedStoreConsumer { private string $store = "AuthorityConsumptionStore"; }',
        );

        $this->assertCoverageFailure('testEnvelopeStoreAndPerimeterBoundariesAreExact');
    }

    public function testInventoriedPerimeterFileWithUnapprovedHelperFailsClosed(): void
    {
        $root = $this->seedRoot();
        $path = 'src/Imperium/Runtime/LaCortine/UnapprovedTransactionalHelper.php';
        $this->writePhp(
            $root,
            $path,
            'final class UnapprovedTransactionalHelper { private string $helper = "TransactionalAuthorityConsumptionEnvelope"; }',
        );
        $this->appendBatch1Inventory(
            $root,
            "LACORTINE_SORTIE_PERIMETER\tSCANNED_CURRENT_PHP_FILE\t{$path}"
            ."\tSynthetic explicit perimeter addition\tFROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_BATCH_3"
            ."\tFrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest",
        );

        $this->assertCoverageFailure('testEnvelopeStoreAndPerimeterBoundariesAreExact');
    }

    public function testNewDispositionVocabularyProducerFailsClosed(): void
    {
        $root = $this->seedRoot();
        $this->writePhp(
            $root,
            'src/Imperium/Runtime/Imperator/UnapprovedDispositionProducer.php',
            "final class UnapprovedDispositionProducer { public const string OUTCOME = 'RETIRE_CORRIDOR'; }",
        );

        $failed = false;
        try {
            (new ProviderBindingActivationIntegrityRemediationBatch6Test(
                'testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles',
            ))->testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles();
        } catch (AssertionFailedError) {
            $failed = true;
        }
        self::assertTrue($failed, 'Unapproved disposition vocabulary escaped the exact inventory.');
    }

    public function testExplicitlyInventoriedCandidateAdditionPasses(): void
    {
        $root = $this->seedRoot();
        $path = 'src/Imperium/Runtime/AuditSynthetic/ApprovedCandidate.php';
        $this->writePhp(
            $root,
            $path,
            "final class ApprovedCandidate { public bool \$authority_exercisable = true; }",
        );
        $this->appendBatch1Inventory(
            $root,
            "RUNTIME_CANDIDATE\tAPPROVED_POST_BATCH12_SUCCESSOR\t{$path}"
            ."\tSynthetic explicitly classified candidate\tFROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_BATCH_3"
            ."\tFrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest",
        );

        (new TransactionalAuthorityConsumptionBatch12CoverageTest(
            'testMechanicalRuntimeCoverageMatchesTheFrozenBatch12Snapshot',
        ))->testMechanicalRuntimeCoverageMatchesTheFrozenBatch12Snapshot();
        self::addToAssertionCount(1);
    }

    public function testTerminalAuditClosesOnlyTheTripwireCampaign(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents(
            $root.'/docs/frozen-runtime-coverage-tripwire-restoration-batch-3-blackquill-audit.md',
        );
        $handoff = (string) file_get_contents(
            $root.'/docs/handoffs/frozen-runtime-coverage-tripwire-restoration-campaign-complete.md',
        );
        $documents = (string) preg_replace('/\s+/', ' ', $audit.$handoff);
        foreach ([
            'TERMINAL_ADVERSARIAL_AUDIT_PASSED_TRIPWIRES_RESTORED',
            'FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_COMPLETE',
            'alarms, not transactional correctness',
            'does not prove global authority safety',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $documents, $boundary);
        }
        self::assertStringContainsString('There is no Batch 4', $handoff);
    }

    private function assertCoverageFailure(string $method): void
    {
        $failed = false;
        try {
            (new TransactionalAuthorityConsumptionBatch12CoverageTest($method))->{$method}();
        } catch (AssertionFailedError) {
            $failed = true;
        }
        self::assertTrue($failed, $method.' accepted an unapproved synthetic mutation.');
    }

    private function seedRoot(): string
    {
        $sourceRoot = dirname(__DIR__, 3);
        $root = sys_get_temp_dir().'/imperium-frozen-tripwire-audit-'.bin2hex(random_bytes(6));
        $this->roots[] = $root;
        $this->copyDirectory($sourceRoot.'/src/Imperium/Runtime', $root.'/src/Imperium/Runtime');
        foreach ([
            'transactional-authority-consumption-runtime-coverage-snapshot.tsv',
            'frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv',
            'frozen-runtime-coverage-tripwire-restoration-activation-disposition-exceptions-v1.tsv',
        ] as $document) {
            $target = $root.'/docs/'.$document;
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0770, true);
            }
            copy($sourceRoot.'/docs/'.$document, $target);
        }
        putenv('IMPERIUM_FROZEN_COVERAGE_ROOT='.$root);

        return $root;
    }

    private function appendBatch1Inventory(string $root, string $row): void
    {
        file_put_contents(
            $root.'/docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv',
            $row."\n",
            FILE_APPEND,
        );
    }

    private function writePhp(string $root, string $path, string $body): void
    {
        $target = $root.'/'.$path;
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0770, true);
        }
        file_put_contents($target, "<?php\n\ndeclare(strict_types=1);\n\n{$body}\n");
    }

    private function copyDirectory(string $source, string $target): void
    {
        mkdir($target, 0770, true);
        foreach (new \DirectoryIterator($source) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $destination = $target.'/'.$item->getFilename();
            if ($item->isDir()) {
                $this->copyDirectory($item->getPathname(), $destination);
            } else {
                copy($item->getPathname(), $destination);
            }
        }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
