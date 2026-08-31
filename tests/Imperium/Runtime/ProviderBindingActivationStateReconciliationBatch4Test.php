<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationAggregateReconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationFixtureStore;

final class ProviderBindingActivationStateReconciliationBatch4Test extends ProviderBindingActivationStateReconciliationBatch3Test
{
    public function testCompleteExactChainReconstructsEligibleAndReadOnly(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeChain($root, $fixture);
            $result = $this->reconstructor($root)->reconstruct(
                'binding-reconciliation-root.1',
                $fixture['principal'],
                $fixture['binding'],
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            );

            self::assertSame('ELIGIBLE_OFFLINE_BINDING_SUCCESSOR', $result['classification']);
            self::assertSame([], $result['reasons']);
            self::assertSame('binding-lifecycle-successor.1', $result['chain']['lifecycle_successor']['id']);
            self::assertTrue($result['read_only']);
            foreach ($result as $name => $value) {
                if (str_ends_with($name, '_created')
                    || str_ends_with($name, '_repaired')
                    || str_ends_with($name, '_replaced')
                    || str_ends_with($name, '_promoted')
                    || str_ends_with($name, '_performed')
                    || str_ends_with($name, '_activated')
                    || str_ends_with($name, '_issued')
                    || str_ends_with($name, '_consumed')
                    || str_ends_with($name, '_handled')
                    || str_ends_with($name, '_invoked')
                    || str_ends_with($name, '_started')) {
                    self::assertFalse($value, $name);
                }
            }
            self::assertFalse($result['continuing_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testMissingChainClassifiesIncompleteWithoutCreatingFixtures(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $result = $this->reconstructor($root)->reconstruct(
                'binding-reconciliation-root.1',
                $fixture['principal'],
                $fixture['binding'],
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            );

            self::assertSame('INCOMPLETE', $result['classification']);
            self::assertSame(['FIXTURE_ABSENT'], $result['reasons']);
            self::assertFalse($result['fixture_created']);
            self::assertDirectoryDoesNotExist(
                $root.'/'.ProviderBindingActivationReconciliationFixtureStore::TARGETS,
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testCorruptImmutableFixtureClassifiesConflictedWithoutRepair(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeChain($root, $fixture);
            $path = $root.'/'.ProviderBindingActivationReconciliationFixtureStore::TARGETS
                .'/binding-reconciliation-root.1.json';
            $corrupt = str_replace(
                '"original_binding_status": "BOUND_INACTIVE"',
                '"original_binding_status": "BOUND_ACTIVE"',
                (string) file_get_contents($path),
            );
            file_put_contents($path, $corrupt);
            $before = hash_file('sha256', $path);

            $result = $this->reconstructor($root)->reconstruct(
                'binding-reconciliation-root.1',
                $fixture['principal'],
                $fixture['binding'],
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            );

            self::assertSame('CONFLICTED', $result['classification']);
            self::assertContains('PST113_IMMUTABLE_RECORD_TAMPERED', $result['reasons']);
            self::assertFalse($result['fixture_repaired']);
            self::assertSame($before, hash_file('sha256', $path));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testLifecycleIneligibleSuppliedBasisClassifiesRefused(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeChain($root, $fixture);
            $binding = $fixture['binding'];
            $binding['status'] = 'BOUND_ACTIVE';
            $binding = $this->seal($binding);

            $result = $this->reconstructor($root)->reconstruct(
                'binding-reconciliation-root.1',
                $fixture['principal'],
                $binding,
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            );

            self::assertSame('REFUSED', $result['classification']);
            self::assertContains('PBR200_RECONCILED_TARGET_INVALID', $result['reasons']);
            self::assertFalse($result['artifact_replaced']);
            self::assertFalse($result['artifact_promoted']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testExactReadOnlyReplayIsStableAndDoesNotRewriteChain(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeChain($root, $fixture);
            $before = $this->snapshot($root);
            $reconstructor = $this->reconstructor($root);
            $arguments = [
                'binding-reconciliation-root.1',
                $fixture['principal'],
                $fixture['binding'],
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            ];

            $first = $reconstructor->reconstruct(...$arguments);
            $second = $reconstructor->reconstruct(...$arguments);

            self::assertSame($first, $second);
            self::assertSame($first['proof_digest'], $second['proof_digest']);
            self::assertSame($before, $this->snapshot($root));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testBatch4DocumentationAuthorizesAdversarialAuditNext(): void
    {
        $doc = $this->document(
            'docs/provider-binding-activation-state-reconciliation-batch-4-reconstruction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-4-complete.md',
        );

        foreach ([
            'BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'ELIGIBLE_OFFLINE_BINDING_SUCCESSOR',
            'INCOMPLETE',
            'CONFLICTED',
            'REFUSED',
            'validates each artifact before reading the next',
            'persists, repairs, replaces or promotes nothing',
            'provider binding remains BOUND_INACTIVE',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Provider Binding Activation State Reconciliation Batch 5',
            'read-only adversarial readiness audit',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'approximately two batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function storeChain(string $root, array $fixture): void
    {
        $store = new ProviderBindingActivationReconciliationFixtureStore($root);
        $store->putTarget(...$this->targetArguments($fixture));
        $store->putDecisionInput(...$this->decisionArguments($fixture));
        $store->putLifecycleSuccessor(...$this->successorArguments($fixture));
    }

    private function reconstructor(string $root): ProviderBindingActivationReconciliationAggregateReconstructor
    {
        return new ProviderBindingActivationReconciliationAggregateReconstructor($root);
    }

    private function temporaryRootForBatch4(): string
    {
        $root = sys_get_temp_dir().'/imperium-pbr-batch4-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);

        return $root;
    }

    private function snapshot(string $root): array
    {
        $snapshot = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $snapshot[substr($file->getPathname(), strlen($root) + 1)] =
                    hash_file('sha256', $file->getPathname());
            }
        }
        ksort($snapshot);

        return $snapshot;
    }
}
