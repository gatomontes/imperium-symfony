<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch12CoverageTest extends TestCase
{
    public function testMechanicalRuntimeCoverageMatchesTheFrozenBatch12Snapshot(): void
    {
        $root = $this->projectRoot();
        $runtime = $root.'/src/Imperium/Runtime';
        $files = [];
        $authorityFiles = [];
        $candidates = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($runtime));
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $source = (string) file_get_contents($file->getPathname());
            $files[] = $path;
            if (preg_match('/authority|Authority/', $source)) {
                $authorityFiles[] = $path;
            }
            if (str_contains($source, 'authority_single_use')
                || str_contains($source, 'authority_consumed')
                || str_contains($source, "'consumed' => true")
                || str_contains($source, 'authority_exercisable')) {
                $candidates[] = $path;
            }
        }
        sort($files, SORT_STRING);
        sort($authorityFiles, SORT_STRING);
        sort($candidates, SORT_STRING);

        $snapshot = $this->snapshot($root.'/docs/transactional-authority-consumption-runtime-coverage-snapshot.tsv');
        $inventory = $this->versionedInventory($root.'/docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv');
        $approvedSuccessors = array_keys($inventory['RUNTIME_CANDIDATE'] ?? []);
        $expectedCandidates = array_values(array_unique(array_merge(array_keys($snapshot), $approvedSuccessors)));
        sort($expectedCandidates, SORT_STRING);

        self::assertSame($expectedCandidates, $candidates);
        self::assertSame([], array_values(array_diff(array_keys($snapshot), $files)));
        self::assertSame([], array_values(array_diff($approvedSuccessors, $files)));
        self::assertCount(26, array_filter($snapshot, static fn (string $value): bool => 'TRANSACTIONAL_CANONICAL' === $value));
        self::assertCount(3, array_filter($snapshot, static fn (string $value): bool => 'LOCKED_FRAGMENTED' === $value));
        self::assertCount(202, array_filter($snapshot, static fn (string $value): bool => 'INVENTORIED_NONCANONICAL_OR_ISSUER' === $value));
    }

    public function testCanonicalAndLockedConsumerSetsAreExact(): void
    {
        $root = $this->projectRoot();
        $snapshot = $this->snapshot($root.'/docs/transactional-authority-consumption-runtime-coverage-snapshot.tsv');
        $canonical = array_keys(array_filter($snapshot, static fn (string $value): bool => 'TRANSACTIONAL_CANONICAL' === $value));
        $locked = array_keys(array_filter($snapshot, static fn (string $value): bool => 'LOCKED_FRAGMENTED' === $value));

        self::assertSame($this->canonicalConsumers(), $canonical);
        self::assertSame([
            'src/Imperium/Runtime/Governance/InternalGovernanceInterruptionEnforcementService.php',
            'src/Imperium/Runtime/Governance/InternalGovernanceLeaseInterruptionEnforcementService.php',
            'src/Imperium/Runtime/Governance/InternalOperationalLeaseInterruptionEnforcementService.php',
        ], $locked);
    }

    public function testEnvelopeStoreAndPerimeterBoundariesAreExact(): void
    {
        $root = $this->projectRoot();
        $runtime = $root.'/src/Imperium/Runtime';
        $envelopeBuilders = $this->filesContaining($root, $runtime, 'TransactionalAuthorityConsumptionEnvelope::complete');
        self::assertSame([
            'src/Imperium/Runtime/Clavium/GovernanceCognitionInvocationClaimService.php',
            'src/Imperium/Runtime/Clavium/OperationalCognitionInvocationClaimService.php',
            'src/Imperium/Runtime/Clavium/ProviderInvocationClaimService.php',
            'src/Imperium/Runtime/Conscription/DelegateMissionModelBindingAuthorityTransition.php',
            'src/Imperium/Runtime/Curia/DelegateMissionModelGovernanceAuthorityTransition.php',
            'src/Imperium/Runtime/Curia/OperationalAdoptionAuthorityTransition.php',
            'src/Imperium/Runtime/Oracle/OracleEligibilityAuthorityTransition.php',
            'src/Imperium/Runtime/Senate/DelegateMissionSenateAuthorityTransition.php',
            'src/Imperium/Runtime/Senate/ProfileSenateAuthorityTransition.php',
        ], $envelopeBuilders);

        $storeUsers = array_values(array_filter(
            $this->filesContaining($root, $runtime, 'AuthorityConsumptionStore'),
            static fn (string $path): bool => !str_ends_with($path, '/Persistence/AuthorityConsumptionStore.php'),
        ));
        $inventory = $this->versionedInventory(
            $root.'/docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv',
        );
        $expectedStoreUsers = array_keys($inventory['AUTHORITY_CONSUMPTION_STORE_CONSUMER'] ?? []);
        self::assertSame($expectedStoreUsers, $storeUsers);

        $perimeter = array_merge(
            $this->phpFiles($root, $runtime.'/LaCortine'),
            $this->phpFiles($root, $runtime.'/Sortie'),
        );
        sort($perimeter, SORT_STRING);
        $expectedPerimeter = array_keys($inventory['LACORTINE_SORTIE_PERIMETER'] ?? []);
        self::assertSame($expectedPerimeter, $perimeter);
        $forbidden = [
            'TransactionalAuthorityConsumptionEnvelope',
            'AuthorityConsumptionStore',
            'DelegateMissionSenateAuthorityTransition',
            'ProfileSenateAuthorityTransition',
            'OperationalAdoptionAuthorityTransition',
            'DelegateMissionModelGovernanceAuthorityTransition',
            'DelegateMissionModelBindingAuthorityTransition',
            'OracleEligibilityAuthorityTransition',
        ];
        $exceptions = $inventory['FORBIDDEN_HELPER_EXCEPTION'] ?? [];
        foreach ($exceptions as $path => $exception) {
            self::assertContains($path, $perimeter, $path);
            self::assertContains($exception['detail'], $forbidden, $path);
            self::assertStringContainsString(
                $exception['detail'],
                (string) file_get_contents($root.'/'.$path),
                $path,
            );
        }
        foreach (array_merge($perimeter, [
            'src/Imperium/Runtime/Oracle/OracleResearchCommissionService.php',
            'src/Imperium/Runtime/Oracle/OracleResearchEvidenceAdmissionService.php',
        ]) as $path) {
            $source = (string) file_get_contents($root.'/'.$path);
            foreach ($forbidden as $helper) {
                if (($exceptions[$path]['detail'] ?? null) === $helper) {
                    continue;
                }
                self::assertStringNotContainsString($helper, $source, $path);
            }
        }
    }

    public function testAuditRecordsTheAdversarialLimitsAndClosedBoundary(): void
    {
        $root = $this->projectRoot();
        $audit = (string) file_get_contents($root.'/docs/transactional-authority-consumption-coverage-audit.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-12-complete.md');
        foreach (['26 canonical consumers are not a canonical runtime', 'global deadlock freedom', 'tripwire, not a substitute', 'No perimeter adoption leaked'] as $limit) {
            self::assertStringContainsString($limit, $audit);
        }
        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`', '`TRANSACTIONAL_CANONICAL`', '`LOCKED_FRAGMENTED`', '`RACE_EXPOSED`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`', 'Runtime behavior is unchanged', 'One estimated batch remains', 'Batch 13 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function snapshot(string $path): array
    {
        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with($line, '#') || "classification\tpath" === $line) {
                continue;
            }
            [$classification, $recordPath] = explode("\t", $line, 2);
            $rows[$recordPath] = $classification;
        }
        ksort($rows, SORT_STRING);

        return $rows;
    }

    private function projectRoot(): string
    {
        $override = getenv('IMPERIUM_FROZEN_COVERAGE_ROOT');

        return is_string($override) && '' !== $override
            ? rtrim($override, '/\\')
            : dirname(__DIR__, 3);
    }

    private function versionedInventory(string $path): array
    {
        $inventories = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with($line, '#')
                || "inventory\tclassification\tpath\tdetail\tauthorizing_batch\tfocused_test" === $line) {
                continue;
            }
            [$inventory, $classification, $recordPath, $detail, $authorizingBatch, $focusedTest]
                = explode("\t", $line, 6);
            self::assertArrayNotHasKey($recordPath, $inventories[$inventory] ?? [], $line);
            $inventories[$inventory][$recordPath] = [
                'classification' => $classification,
                'detail' => $detail,
                'authorizing_batch' => $authorizingBatch,
                'focused_test' => $focusedTest,
            ];
        }
        foreach ($inventories as &$records) {
            ksort($records, SORT_STRING);
        }
        unset($records);
        ksort($inventories, SORT_STRING);
        self::assertSame([
            'AUTHORITY_CONSUMPTION_STORE_CONSUMER',
            'FORBIDDEN_HELPER_EXCEPTION',
            'LACORTINE_SORTIE_PERIMETER',
            'RUNTIME_CANDIDATE',
        ], array_keys($inventories));

        return $inventories;
    }

    private function filesContaining(string $root, string $directory, string $needle): array
    {
        $matches = [];
        foreach ($this->phpFiles($root, $directory) as $path) {
            if (str_contains((string) file_get_contents($root.'/'.$path), $needle)) {
                $matches[] = $path;
            }
        }
        sort($matches, SORT_STRING);

        return $matches;
    }

    private function phpFiles(string $root, string $directory): array
    {
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $paths[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            }
        }

        return $paths;
    }

    private function canonicalConsumers(): array
    {
        $paths = [
            'src/Imperium/Runtime/Citadel/DelegateMissionTurnRecoveryService.php',
            'src/Imperium/Runtime/Clavium/GovernanceCognitionInvocationClaimService.php',
            'src/Imperium/Runtime/Clavium/OperationalCognitionInvocationClaimService.php',
            'src/Imperium/Runtime/Clavium/ProviderInvocationClaimService.php',
            'src/Imperium/Runtime/Conscription/DelegateMissionModelBindingSealingService.php',
            'src/Imperium/Runtime/Conscription/DelegateMissionOperationalManifestationAssemblyService.php',
            'src/Imperium/Runtime/Conscription/DelegateMissionOperationalManifestationSeatBindingService.php',
            'src/Imperium/Runtime/Conscription/DelegateMissionOperationalProfileQualificationService.php',
            'src/Imperium/Runtime/Curia/DelegateMissionModelCriteriaRequestService.php',
            'src/Imperium/Runtime/Curia/DelegateMissionModelSelectionDecisionService.php',
            'src/Imperium/Runtime/Curia/OperationalAdoptionDispositionService.php',
            'src/Imperium/Runtime/Curia/OperationalAdoptionReconciliationService.php',
            'src/Imperium/Runtime/Garrison/DelegateMissionOperationalCustodyTransitionService.php',
            'src/Imperium/Runtime/Garrison/DelegateMissionTerminalReturnService.php',
            'src/Imperium/Runtime/Oracle/ModelEligibilityFindingService.php',
            'src/Imperium/Runtime/Senate/DelegateMissionDeliberationOpeningService.php',
            'src/Imperium/Runtime/Senate/DelegateMissionDispositionAuthorityOpeningService.php',
            'src/Imperium/Runtime/Senate/DelegateMissionFindingAuthorityOpeningService.php',
            'src/Imperium/Runtime/Senate/DelegateMissionFirstQuestionCommissionDispositionService.php',
            'src/Imperium/Runtime/Senate/DelegateMissionQuestionDispatchAuthorizationEngine.php',
            'src/Imperium/Runtime/Senate/DelegateMissionQuestionDispatchEngine.php',
            'src/Imperium/Runtime/Senate/DelegateMissionSubsequentQuestionCommissionDispositionEngine.php',
            'src/Imperium/Runtime/Senate/DelegateMissionSubsequentQuestionCommissionIssuanceEngine.php',
            'src/Imperium/Runtime/Senate/ModelBoundProfileDeliberationOpeningService.php',
            'src/Imperium/Runtime/Senate/ModelBoundProfileExaminationTestimonyOpeningService.php',
            'src/Imperium/Runtime/Senate/ModelBoundProfileFindingAuthorityOpeningService.php',
        ];
        sort($paths, SORT_STRING);

        return $paths;
    }
}
